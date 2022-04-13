<?php

namespace Drupal\tfa;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provide context for the current login attempt.
 */
interface TfaContextInterface {

  /**
   * TfaContextInterface constructor.
   *
   * @param \Drupal\tfa\TfaValidationPluginManager $tfa_validation_manager
   *   The plugin manager for TFA validation plugins.
   * @param \Drupal\tfa\TfaLoginPluginManager $tfa_plugin_manager
   *   The plugin manager for TFA login plugins.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration service.
   * @param \Drupal\user\UserInterface $user
   *   The user currently attempting to log in.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function __construct(TfaValidationPluginManager $tfa_validation_manager, TfaLoginPluginManager $tfa_plugin_manager, ConfigFactoryInterface $config_factory, UserInterface $user, UserDataInterface $user_data, Request $request);

  /**
   * Get the user object.
   *
   * @return \Drupal\user\UserInterface
   *   The entity object of the user attempting to log in.
   */
  public function getUser();

  /**
   * Is TFA enabled and configured?
   *
   * @return bool
   *   Whether or not the TFA module is configured for use.
   */
  public function isModuleSetup();

  /**
   * Check whether $this->getUser() is required to use TFA.
   *
   * @return bool
   *   TRUE if $this->getUser() is required to use TFA.
   */
  public function isTfaRequired();

  /**
   * Check whether the Validation Plugin is set and ready for use.
   *
   * @return bool
   *   TRUE if Validation Plugin exists and is ready for use.
   */
  public function isReady();

  /**
   * Remaining number of allowed logins without setting up TFA.
   *
   * @return int|false
   *   FALSE if users are never allowed to log in without setting up TFA.
   *   The remaining number of times $this->getUser() may log in without setting
   *   up TFA.
   */
  public function remainingSkips();

  /**
   * Increment the count of $this->getUser() logins without setting up TFA.
   */
  public function hasSkipped();

  /**
   * Whether at least one plugin allows authentication.
   *
   * If any plugin returns TRUE then authentication is not interrupted by TFA.
   *
   * @return bool
   *   TRUE if login allowed otherwise FALSE.
   */
  public function pluginAllowsLogin();

  /**
   * Wrapper for user_login_finalize().
   */
  public function doUserLogin();

}
