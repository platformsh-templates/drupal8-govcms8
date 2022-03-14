<?php

namespace Drupal\Tests\tfa\Functional;

use Drupal\Core\Test\AssertMailTrait;

/**
 * Test the email send plugin.
 *
 * @group Tfa
 */
class TfaEmailCodePluginTest extends TfaTestBase {

  use AssertMailTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Plugin id for the recovery code validation plugin.
   *
   * @var string
   */
  protected $validationPluginId = 'tfa_email_code';

  /**
   * Setup plugin manager.
   *
   * @var \Drupal\tfa\TfaSetupPluginManager
   */
  public $tfaSetupManager;

  /**
   * Validation plugin manager.
   *
   * @var \Drupal\tfa\TfaValidationPluginManager
   */
  public $tfaValidationManager;

  /**
   * Non-admin user account. Standard tfa user.
   *
   * @var \Drupal\user\Entity\User
   */
  public $userAccount;

  /**
   * Instance of the setup plugin for the $validationPluginId.
   *
   * @var \Drupal\tfa\Plugin\TfaSetup\TfaRecoveryCodeSetup
   */
  public $setupPlugin;

  /**
   * Instance of the validation plugin for the $validationPluginId.
   *
   * @var \Drupal\tfa\Plugin\TfaValidation\TfaRecoveryCode
   */
  public $validationPlugin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $config = $this->config('tfa.settings');
    $config->set('enabled', TRUE)
      ->set('default_validation_plugin', $this->validationPluginId)
      ->set('allowed_validation_plugins', [$this->validationPluginId => $this->validationPluginId])
      ->set('encryption', $this->encryptionProfile->id())
      ->set('required_roles', ['authenticated' => 'authenticated'])
      ->set('validation_plugin_settings', [
        $this->validationPluginId => [
          'code_validity_period' => 180,
          'email_setting' => [
            'subject' => '[site:name] Authentication code',
            'body' => "[user:display-name],\n\nThis code is valid for [length] minutes. Your code is: [code]\n\nThis code will be expired after login.",
          ],
        ],
      ])
      ->save();

    $this->userAccount = $this->createUser(['setup own tfa', 'disable own tfa']);

    $this->tfaSetupManager = \Drupal::service('plugin.manager.tfa.setup');
    $this->setupPlugin = $this->tfaSetupManager->createInstance($this->validationPluginId . '_setup', ['uid' => $this->userAccount->id()]);

    $this->tfaValidationManager = \Drupal::service('plugin.manager.tfa.validation');
    $this->validationPlugin = $this->tfaValidationManager->createInstance($this->validationPluginId, ['uid' => $this->userAccount->id()]);
  }

  /**
   * Helper function to setup the accounts validation plugin.
   */
  protected function setupEmailCodeValidationPlugin() {
    $this->drupalLogin($this->userAccount);
    $this->drupalGet('user/' . $this->userAccount->id() . '/security/tfa/' . $this->validationPluginId . '/1');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->responseContains('Enter your current password');

    // Provide the user's password to continue.
    $edit = ['current_pass' => $this->userAccount->passRaw];
    $this->drupalPostForm(NULL, $edit, 'Confirm');

    $assert->responseContains('sent by email associated to your account');
    $this->drupalPostForm(NULL, ['email_code' => 1], 'Save');
    $assert->pageTextContains('TFA setup complete.');
    $this->drupalLogout();
  }

  /**
   * Test that we can enable the plugin.
   */
  public function testEnableEmailCodeValidationPlugin() {
    $this->canEnableValidationPlugin($this->validationPluginId);
  }

  /**
   * Check that recovery code plugin appear on the user overview page.
   */
  public function testEmailCodeOverviewExists() {
    $this->drupalLogin($this->userAccount);
    $this->drupalGet('user/' . $this->userAccount->id() . '/security/tfa');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Email codes');
  }

  /**
   * Check that the user can setup email codes plugin.
   */
  public function testEmailCodeSetup() {
    $this->setupEmailCodeValidationPlugin();
  }

  /**
   * Check that the user can login with emailed codes.
   */
  public function testEmailCodeValidation() {
    $this->setupEmailCodeValidationPlugin();
    $assert = $this->assertSession();

    // Password form.
    $edit = [
      'name' => $this->userAccount->getAccountName(),
      'pass' => $this->userAccount->passRaw,
    ];
    $this->drupalPostForm('user/login', $edit, 'Log in');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Enter the received code');

    // Validate an email was sent.
    $emails = $this->getMails();
    // First email is "enabled tfa", second is our code.
    $this->assertCount(2, $emails);
    $email = end($emails);
    $this->assertEquals('text/plain; charset=UTF-8; format=flowed; delsp=yes', $email['headers']['Content-Type']);
    $this->assertEquals('8Bit', $email['headers']['Content-Transfer-Encoding']);
    $this->assertFalse(isset($email['headers']['Bcc']));
    $this->assertEquals($this->userAccount->getEmail(), $email['to']);
    $this->assertContains('Authentication code', $email['subject']);
    $this->assertContains('code is: ', $email['body']);

    // Try an invalid code.
    $edit = ['code' => 'definitely not real'];
    $this->drupalPostForm(NULL, $edit, 'Verify');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Invalid code.');

    // Try a valid code.
    $code = substr(explode('code is: ', $email['body'])[1], 0, 9);
    $edit = ['code' => $code];
    $this->drupalPostForm(NULL, $edit, 'Verify');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains($this->userAccount->getDisplayName());
    $assert->assert($this->userAccount->isAuthenticated(), 'User is logged in.');

    // Try replay attack with a valid code that has already been used.
    $this->drupalLogout();
    $edit = [
      'name' => $this->userAccount->getAccountName(),
      'pass' => $this->userAccount->passRaw,
    ];
    $this->drupalPostForm('user/login', $edit, 'Log in');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Enter the received code');

    $edit = ['code' => $code];
    $this->drupalPostForm(NULL, $edit, 'Verify');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Invalid code.');
  }

}
