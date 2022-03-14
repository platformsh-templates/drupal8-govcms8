<?php

namespace Drupal\Tests\tfa\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Tests\UnitTestCase;
use Drupal\tfa\Plugin\TfaValidationInterface;
use Drupal\tfa\TfaContext;
use Drupal\tfa\TfaLoginPluginManager;
use Drupal\tfa\TfaValidationPluginManager;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\tfa\TfaContext
 *
 * @group tfa
 */
class TfaContextTest extends UnitTestCase {

  /**
   * Validation plugin manager.
   *
   * @var \Drupal\tfa\TfaValidationPluginManager
   */
  protected $tfaValidationManager;

  /**
   * Login plugin manager.
   *
   * @var \Drupal\tfa\TfaLoginPluginManager
   */
  protected $tfaLoginManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Tfa settings config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $tfaSettings;

  /**
   * Entity for the user that is attempting to login.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * User data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Setup default mocked services. These can be overridden by
    // re-instantiating them as needed prior to calling ::getFixture().
    $this->tfaValidationManager = $this->prophesize(TfaValidationPluginManager::class)->reveal();
    $this->tfaLoginManager = $this->prophesize(TfaLoginPluginManager::class)->reveal();
    $this->tfaSettings = $this->prophesize(ImmutableConfig::class)->reveal();
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->configFactory->get('tfa.settings')->willReturn($this->tfaSettings);
    $this->configFactory = $this->configFactory->reveal();
    $this->user = $this->prophesize(UserInterface::class);
    $this->user->id()->willReturn(3);
    $this->user = $this->user->reveal();
    $this->userData = $this->prophesize(UserDataInterface::class)->reveal();
    $this->request = $this->prophesize(Request::class)->reveal();
  }

  /**
   * Helper method to instantiate the test fixture.
   *
   * @return \Drupal\tfa\TfaContext
   *   TFA context.
   */
  protected function getFixture() {
    return new TfaContext(
      $this->tfaValidationManager,
      $this->tfaLoginManager,
      $this->configFactory,
      $this->user,
      $this->userData,
      $this->request
    );
  }

  /**
   * @covers ::getUser
   */
  public function testGetUser() {
    $fixture = $this->getFixture();
    $this->assertEquals(3, $fixture->getUser()->id());
  }

  /**
   * @covers ::isModuleSetup
   */
  public function testIsModuleSetup() {
    // Defaults to false with empty mocked services.
    $fixture = $this->getFixture();
    $this->assertFalse($fixture->isModuleSetup());

    // Enable.
    $settings = $this->prophesize(ImmutableConfig::class);
    $settings->get('enabled')->willReturn(TRUE);
    $settings->get('default_validation_plugin')->willReturn('foo');
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('tfa.settings')->willReturn($settings->reveal());
    $this->configFactory = $config_factory->reveal();
    $fixture = $this->getFixture();
    $this->assertTrue($fixture->isModuleSetup());
  }

  /**
   * @covers ::isTfaRequired
   */
  public function testIsTfaRequired() {
    // User has setup TFA.
    $user_data = $this->prophesize(UserDataInterface::class);
    $user_data->get('tfa', 3, 'tfa_user_settings')->willReturn([
      'status' => 1,
      'saved' => FALSE,
      'data' => ['plugins' => ['foo']],
      'validation_skipped' => 1,
    ]);
    $this->userData = $user_data->reveal();
    $fixture = $this->getFixture();
    $this->assertTrue($fixture->isTfaRequired());

    // Not setup, no required roles matching the user.
    $user_data->get('tfa', 3, 'tfa_user_settings')->willReturn([
      'status' => 0,
      'saved' => FALSE,
      'data' => ['plugins' => ['foo']],
      'validation_skipped' => 1,
    ]);
    $this->userData = $user_data->reveal();
    $settings = $this->prophesize(ImmutableConfig::class);
    $settings->get('default_validation_plugin')->willReturn('foo');
    $settings->get('required_roles')->willReturn(['foo' => 'foo']);
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('tfa.settings')->willReturn($settings->reveal());
    $this->configFactory = $config_factory->reveal();
    $user = $this->prophesize(UserInterface::class);
    $user->id()->willReturn(3);
    $user->getRoles()->willReturn(['bar' => 'bar']);
    $this->user = $user->reveal();
    $fixture = $this->getFixture();
    $this->assertFalse($fixture->isTfaRequired());

    // Setup, matching roles.
    $user_data->get('tfa', 3, 'tfa_user_settings')->willReturn([
      'status' => 1,
      'saved' => FALSE,
      'data' => ['plugins' => ['foo']],
      'validation_skipped' => 1,
    ]);
    $this->userData = $user_data->reveal();
    $user = $this->prophesize(UserInterface::class);
    $user->id()->willReturn(3);
    $user->getRoles()->willReturn(['foo' => 'foo', 'bar' => 'bar']);
    $this->user = $user->reveal();
    $fixture = $this->getFixture();
    $this->assertTrue($fixture->isTfaRequired());
  }

  /**
   * @covers ::isReady
   */
  public function testIsReady() {
    // Not ready.
    $settings = $this->prophesize(ImmutableConfig::class);
    $settings->get('default_validation_plugin')->willReturn(FALSE);
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('tfa.settings')->willReturn($settings->reveal());
    $this->configFactory = $config_factory->reveal();
    $fixture = $this->getFixture();
    $this->assertFalse($fixture->isReady());

    // Is ready.
    $settings->get('default_validation_plugin')->willReturn('foo');
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('tfa.settings')->willReturn($settings->reveal());
    $this->configFactory = $config_factory->reveal();
    $validator = $this->prophesize(TfaValidationInterface::class);
    $validator->ready()->willReturn(TRUE);
    $manager = $this->prophesize(TfaValidationPluginManager::class);
    $manager->createInstance('foo', ['uid' => 3])->willReturn($validator->reveal());
    $this->tfaValidationManager = $manager->reveal();
    $fixture = $this->getFixture();
    $this->assertTrue($fixture->isReady());

    // Plugin set, but not ready.
    $validator = $this->prophesize(TfaValidationInterface::class);
    $validator->ready()->willReturn(FALSE);
    $manager = $this->prophesize(TfaValidationPluginManager::class);
    $manager->createInstance('foo', ['uid' => 3])->willReturn($validator->reveal());
    $this->tfaValidationManager = $manager->reveal();
    $fixture = $this->getFixture();
    $this->assertFalse($fixture->isReady());
  }

  /**
   * @covers ::remainingSkips
   */
  public function testRemainingSkips() {
    // No allowed skips.
    $settings = $this->prophesize(ImmutableConfig::class);
    $settings->get('default_validation_plugin')->willReturn(FALSE);
    $settings->get('validation_skip')->willReturn(0);
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('tfa.settings')->willReturn($settings->reveal());
    $this->configFactory = $config_factory->reveal();
    $fixture = $this->getFixture();
    $this->assertFalse($fixture->remainingSkips());

    // 3 allowed skips, user hasn't skipped any.
    $settings->get('validation_skip')->willReturn(3);
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('tfa.settings')->willReturn($settings->reveal());
    $this->configFactory = $config_factory->reveal();
    $fixture = $this->getFixture();
    $this->assertEquals(3, $fixture->remainingSkips());

    // 3 allowed skips, user has skipped 2.
    $user_data = $this->prophesize(UserDataInterface::class);
    $user_data->get('tfa', 3, 'tfa_user_settings')->willReturn([
      'status' => 1,
      'saved' => FALSE,
      'data' => ['plugins' => ['foo']],
      'validation_skipped' => 2,
    ]);
    $this->userData = $user_data->reveal();
    $fixture = $this->getFixture();
    $this->assertEquals(1, $fixture->remainingSkips());

    // User has exceeded attempts, check for 0 return.
    $user_data->get('tfa', 3, 'tfa_user_settings')->willReturn([
      'status' => 1,
      'saved' => FALSE,
      'data' => ['plugins' => ['foo']],
      'validation_skipped' => 9,
    ]);
    $this->userData = $user_data->reveal();
    $fixture = $this->getFixture();
    $this->assertEquals(0, $fixture->remainingSkips());
  }

}
