<?php

namespace Drupal\tfa;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provide context for the current login attempt.
 *
 * This class collects data needed to decide whether TFA is required and, if so,
 * whether it is successful. This includes configuration of the module, the
 * current request, and the user that is attempting to log in.
 */
class TfaContext implements TfaContextInterface {
  // Access to user's TFA settings.
  use TfaDataTrait;
  // Provides the getLoginHash() method.
  use TfaLoginTrait;

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
   * The current validation plugin id being used by this context instance.
   *
   * @var string
   */
  protected $validationPluginName;

  /**
   * The tfaValidation plugin.
   *
   * @var \Drupal\tfa\Plugin\TfaValidationInterface|null
   */
  protected $tfaValidationPlugin;

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
   * Array of login plugins for a given user.
   *
   * @var \Drupal\tfa\Plugin\TfaLoginInterface[]
   */
  protected $userLoginPlugins;

  /**
   * Array of login plugins.
   *
   * @var \Drupal\tfa\Plugin\TfaLoginInterface[]
   */
  protected $tfaLoginPlugins;

  /**
   * {@inheritdoc}
   */
  public function __construct(TfaValidationPluginManager $tfa_validation_manager, TfaLoginPluginManager $tfa_plugin_manager, ConfigFactoryInterface $config_factory, UserInterface $user, UserDataInterface $user_data, Request $request) {
    $this->tfaValidationManager = $tfa_validation_manager;
    $this->tfaLoginManager = $tfa_plugin_manager;
    $this->tfaSettings = $config_factory->get('tfa.settings');
    $this->user = $user;
    $this->userData = $user_data;
    $this->request = $request;

    $this->tfaLoginPlugins = $this->tfaLoginManager->getPlugins(['uid' => $user->id()]);
    // If possible, set up an instance of tfaValidationPlugin and the user's
    // list of plugins.
    $this->validationPluginName = $this->tfaSettings->get('default_validation_plugin');
    if (!empty($this->validationPluginName)) {
      $this->tfaValidationPlugin = $this->tfaValidationManager
        ->createInstance($this->validationPluginName, ['uid' => $user->id()]);
      $this->userLoginPlugins = $this->tfaLoginManager
        ->getPlugins(['uid' => $user->id()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function isModuleSetup() {
    return intval($this->tfaSettings->get('enabled')) && !empty($this->validationPluginName);
  }

  /**
   * {@inheritdoc}
   */
  public function isTfaRequired() {
    // If TFA has been set up for the user, then it is required.
    $user_tfa_data = $this->tfaGetTfaData($this->getUser()->id(), $this->userData);
    if (!empty($user_tfa_data['status']) && !empty($user_tfa_data['data']['plugins'])) {
      return TRUE;
    }

    // If the user has a role that is required to use TFA, then return TRUE.
    $required_roles = array_filter($this->tfaSettings->get('required_roles'));
    $user_roles = $this->getUser()->getRoles();
    return (bool) array_intersect($required_roles, $user_roles);
  }

  /**
   * {@inheritdoc}
   */
  public function isReady() {
    return isset($this->tfaValidationPlugin) && $this->tfaValidationPlugin->ready();
  }

  /**
   * {@inheritdoc}
   */
  public function remainingSkips() {
    $allowed_skips = intval($this->tfaSettings->get('validation_skip'));
    // Skipping TFA setup is not allowed.
    if (!$allowed_skips) {
      return FALSE;
    }

    $user_tfa_data = $this->tfaGetTfaData($this->getUser()->id(), $this->userData);
    $validation_skipped = isset($user_tfa_data['validation_skipped']) ? $user_tfa_data['validation_skipped'] : 0;
    return max(0, $allowed_skips - $validation_skipped);
  }

  /**
   * {@inheritdoc}
   */
  public function hasSkipped() {
    $user_tfa_data = $this->tfaGetTfaData($this->getUser()->id(), $this->userData);
    $validation_skipped = isset($user_tfa_data['validation_skipped'])
      ? $user_tfa_data['validation_skipped']
      : 0;
    $user_tfa_data['validation_skipped'] = $validation_skipped + 1;
    $this->tfaSaveTfaData($this->getUser()->id(), $this->userData, $user_tfa_data);
  }

  /**
   * {@inheritdoc}
   */
  public function pluginAllowsLogin() {
    if (!empty($this->tfaLoginPlugins)) {
      foreach ($this->tfaLoginPlugins as $plugin) {
        if ($plugin->loginAllowed()) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Set a hash mark to indicate TFA authorization has passed.
   */
  public function doUserLogin() {
    user_login_finalize($this->getUser());
  }

}
