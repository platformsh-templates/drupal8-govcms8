<?php

namespace Drupal\module_permissions;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\user\PermissionHandlerInterface;

/**
 * Help class.
 *
 * @package Drupal\module_permissions
 */
class Helper {
  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The user permissions.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Helper constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   System messenger.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   Translation service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module Handler service.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   User permission handler service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Messenger $messenger, TranslationInterface $string_translation, ModuleHandlerInterface $module_handler, PermissionHandlerInterface $permission_handler, AccountProxyInterface $current_user) {
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->stringTranslation = $string_translation;
    $this->moduleHandler = $module_handler;
    $this->permissionHandler = $permission_handler;
    $this->currentUser = $current_user;
  }

  /**
   * Get managed modules list.
   *
   * @return array
   *   Managed modules.
   */
  public function getManagedModules() {
    $managed_modules = &drupal_static(__FUNCTION__);

    if (!isset($managed_modules)) {
      $settings = $this->configFactory->get('module_permissions.settings');
      $managed_modules = $settings->get('managed_modules');
      if (empty($managed_modules)) {
        $managed_modules = [];
      }
      $managed_modules = array_combine($managed_modules, $managed_modules);
      // Remove module permissions and UI from list.
      unset($managed_modules['module_permissions']);
      unset($managed_modules['module_permissions_ui']);
    }

    return $managed_modules;
  }

  /**
   * Get protected modules list.
   *
   * @return array
   *   Protected modules.
   */
  public function getProtectedModules() {
    $protected_modules = &drupal_static(__FUNCTION__);

    if (!isset($protected_modules)) {
      $settings = $this->configFactory->get('module_permissions.settings');
      $protected_modules = $settings->get('protected_modules');
      if (empty($protected_modules)) {
        $protected_modules = [];
      }
      $protected_modules = array_combine($protected_modules, $protected_modules);
    }

    return $protected_modules;
  }

  /**
   * Get blacklisted permissions.
   *
   * @return array
   *   Blacklisted permissions.
   */
  public function getPermissionBlacklist() {
    $blacklist = &drupal_static(__FUNCTION__);

    if (!isset($blacklist)) {
      $settings = $this->configFactory->get('module_permissions.settings');
      $blacklist = $settings->get('permission_blacklist');
      if (empty($blacklist)) {
        $blacklist = [];
      }
      $blacklist = array_combine($blacklist, $blacklist);
    }

    return $blacklist;
  }

  /**
   * Alter the system_modules form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function alterSystemModulesForm(array &$form, FormStateInterface $form_state) {
    if (isset($form['modules'])) {
      $managed_modules = $this->getManagedModules();
      if (empty($managed_modules)) {
        $this->messenger->addMessage($this->t('Managed module list is empty. Please contact your site administrator.'));
      }

      // Hide module permissions.
      unset($form['modules']['Administration']['module_permissions']);
      unset($form['modules']['module_permissions']);

      // Hide module permissions interface .
      unset($form['modules']['Administration']['module_permissions_ui']);
      unset($form['modules']['module_permissions_ui']);

      // Hide unmanaged modules.
      foreach (Element::children($form['modules']) as $package) {
        foreach (Element::children($form['modules'][$package]) as $module) {
          if (!isset($managed_modules[$module])) {
            // Remove from display.
            unset($form['modules'][$package][$module]);
          }
        }
        // Hide empty packages.
        $modules = Element::children($form['modules'][$package]);
        if (empty($modules)) {
          unset($form['modules'][$package]);
        }
      }

      // Our submit callback must go first.
      array_unshift($form['#submit'], [static::class, 'submitFormSystemModules']);
    }
  }

  /**
   * Alter the system_modules_uninstall form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function alterSystemModulesUninstallForm(array &$form, FormStateInterface $form_state) {
    if (isset($form['modules'])) {
      $managed_modules = $this->getManagedModules();
      if (empty($managed_modules)) {
        $this->messenger->addMessage($this->t('Managed module list is empty. Please contact your site administrator.'));
      }

      // Hide module permissions.
      unset($form['modules']['module_permissions']);
      unset($form['uninstall']['module_permissions']);

      // Hide module permissions interface.
      unset($form['modules']['module_permissions_ui']);
      unset($form['uninstall']['module_permissions_ui']);

      // Hide unmanaged modules.
      foreach (array_keys($form['modules']) as $module) {
        if (!isset($managed_modules[$module])) {
          unset($form['modules'][$module]);
          unset($form['uninstall'][$module]);
        }
      }

      $form['#submit'][] = [static::class, 'submitFormSystemModulesUninstall'];
    }
  }

  /**
   * Alter the user_admin_permissions form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function alterUserAdminPermissionsForm(array &$form, FormStateInterface $form_state) {
    if (isset($form['permissions'])) {
      $managed_modules = $this->getManagedModules();
      if (empty($managed_modules)) {
        $this->messenger->addMessage($this->t('Managed module list is empty. Please contact your site administrator.'));
      }

      $blacklisted_permissions = $this->getPermissionBlacklist();

      $permissions = $this->permissionHandler->getPermissions();
      foreach ($permissions as $permission_name => $permission) {
        $module_name = $permission['provider'];
        // Remove the permissions not provided by a managed module.
        if (!isset($managed_modules[$module_name])) {
          unset($form['permissions'][$permission_name]);
          if ($module_name !== 'node') {
            unset($form['permissions'][$module_name]);
          }
          // Only remove Node module if the System module is not managed,
          // as Drupal moved the 'access content' permission from System
          // to Node on the Permissions UI.
          else {
            if (!isset($managed_modules['system'])) {
              unset($form['permissions'][$module_name]);
            }
          }
        }
        // Remove blacklisted permissions.
        if (isset($blacklisted_permissions[$permission_name])) {
          unset($form['permissions'][$permission_name]);
        }
      }

      $form['#submit'][] = [static::class, 'submitFormUserAdminPermissions'];
    }
  }

  /**
   * Submit callback to log actions on system_modules form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function submitFormSystemModules(array $form, FormStateInterface $form_state) {
    $recent_modules = [];
    // Drupal 8.3.0 simplified the module form structure which requires checking
    // the version of Drupal and building the $modules array accordingly.
    // @see https://www.drupal.org/node/2851653
    $modules = [];
    if (version_compare(\Drupal::VERSION, '8.3.0', '<')) {
      foreach ($form_state->getValue('modules') as $package) {
        $modules += $package;
      }
    }
    else {
      $modules = $form_state->getValue('modules');
    }

    foreach ($modules as $module => $details) {
      if (!\Drupal::moduleHandler()->moduleExists($module)) {
        $recent_modules[] = $module;
      }
    }

    if (!empty($recent_modules)) {
      \Drupal::logger('module_permissions')->info('User %name selected to install the following modules: %modules.', [
        '%name' => \Drupal::currentUser()->getAccountName(),
        '%modules' => implode(', ', $recent_modules),
      ]);
    }
  }

  /**
   * Submit callback to log actions on system_modules_uninstall form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function submitFormSystemModulesUninstall(array $form, FormStateInterface $form_state) {
    $modules = $form_state->getValue('uninstall');
    foreach ($modules as $module => $uninstall) {
      if ($uninstall) {
        $recent_modules[] = $module;
      }
    }
    if (!empty($recent_modules)) {
      \Drupal::logger('module_permissions')->info('User %name selected to uninstall the following modules: %modules.', [
        '%name' => \Drupal::currentUser()->getAccountName(),
        '%modules' => implode(', ', $recent_modules),
      ]);
    }
  }

  /**
   * Submit callback to log actions on user_admin_permissions form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function submitFormUserAdminPermissions(array $form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('role_names') as $role_name => $name) {
      $permissions = (array) $form_state->getValue($role_name);
      // Grant new permissions for the role.
      $grant = array_filter($permissions);
      if (!empty($grant)) {
        \Drupal::logger('module_permissions')->info('User %name granted the role %role the following permissions: %permissions.', [
          '%name' => \Drupal::currentUser()->getAccountName(),
          '%role' => $role_name,
          '%permissions' => implode(', ', array_keys($grant)),
        ]);
      }
      // Revoke permissions for the role.
      $revoke = array_diff_assoc($permissions, $grant);
      if (!empty($revoke)) {
        \Drupal::logger('module_permissions')->info('User %name revoked following permissions: %permissions from the role %role.', [
          '%name' => \Drupal::currentUser()->getAccountName(),
          '%role' => $role_name,
          '%permissions' => implode(', ', array_keys($revoke)),
        ]);
      }
    }
  }

  /**
   * Check for restrict status when accessing managed forms.
   *
   * @param string $form_id
   *   Form ID.
   *
   * @return bool
   *   Restrict status.
   */
  public function isRestrictedForm($form_id) {
    $restricted = TRUE;

    switch ($form_id) {
      case 'system_modules':
      case 'system_modules_uninstall':
        if ($this->currentUser->hasPermission('administer modules')) {
          $restricted = FALSE;
        }
        elseif ($this->currentUser->hasPermission('administer managed modules')) {
          $restricted = TRUE;
        }
        break;

      case 'user_admin_permissions':
        if ($this->currentUser->hasPermission('administer permissions')) {
          $restricted = FALSE;
        }
        elseif ($this->currentUser->hasPermission('administer managed modules')
          && $this->currentUser->hasPermission('administer managed modules permissions')
        ) {
          $restricted = TRUE;
        }
        break;
    }

    return $restricted;
  }

}
