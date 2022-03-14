<?php

namespace Drupal\module_permissions_ui\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\module_permissions\Helper;
use Drupal\user\PermissionHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Permission Blacklist config form for Module Permissions.
 */
class PermissionBlacklistForm extends ConfigFormBase {

  /**
   * Helper.
   *
   * @var \Drupal\module_permissions\Helper
   */
  protected $helper;

  /**
   * Module handler service.
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
   * ProtectedModulesForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\module_permissions\Helper $helper
   *   Helper service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   Permission handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Helper $helper, ModuleHandlerInterface $module_handler, PermissionHandlerInterface $permission_handler) {
    parent::__construct($config_factory);
    $this->helper = $helper;
    $this->moduleHandler = $module_handler;
    $this->permissionHandler = $permission_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_permissions.helper'),
      $container->get('module_handler'),
      $container->get('user.permissions')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'module_permissions_ui_permission_blacklist';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['module_permissions.settings'];
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\user\Form\UserPermissionsForm::buildForm()
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $managed_modules = $this->helper->getManagedModules();
    $blacklist = $this->helper->getPermissionBlacklist();

    if ($this->moduleHandler->moduleExists('module_filter')) {
      $form['filters'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['table-filter', 'js-show'],
        ],
      ];

      $form['filters']['text'] = [
        '#type' => 'search',
        '#title' => $this->t('Filter modules'),
        '#title_display' => 'invisible',
        '#size' => 30,
        '#placeholder' => $this->t('Filter by name or description'),
        '#description' => $this->t('Enter a part of the module name or description'),
        '#attributes' => [
          'class' => ['table-filter-text'],
          'data-table' => '#module-permissions-ui-permission-blacklist',
          'autocomplete' => 'off',
        ],
      ];

      $form['#attached']['library'][] = 'module_filter/permissions';
    }

    $hide_descriptions = system_admin_compact_mode();
    $form['system_compact_link'] = [
      '#id' => FALSE,
      '#type' => 'system_compact_link',
    ];

    $form['permissions'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => 'Blacklist', 'class' => ['checkbox']],
        $this->t('Permission'),
      ],
      '#id' => 'permissions',
      '#attributes' => ['class' => ['permissions', 'js-permissions']],
      '#sticky' => TRUE,
    ];

    $permissions = $this->permissionHandler->getPermissions();
    $permissions_by_provider = [];
    foreach ($permissions as $permission_name => $permission) {
      $permissions_by_provider[$permission['provider']][$permission_name] = $permission;
    }

    // Move the access content permission to the Node module if it is installed
    // and managed.
    if ($this->moduleHandler->moduleExists('node') && isset($managed_modules['node'])) {
      // Insert 'access content' before the 'view own unpublished content' key
      // in order to maintain the UI even though the permission is provided by
      // the system module.
      $keys = array_keys($permissions_by_provider['node']);
      $offset = (int) array_search('view own unpublished content', $keys);
      $permissions_by_provider['node'] = array_merge(
        array_slice($permissions_by_provider['node'], 0, $offset),
        ['access content' => $permissions_by_provider['system']['access content']],
        array_slice($permissions_by_provider['node'], $offset)
      );
      unset($permissions_by_provider['system']['access content']);
    }

    foreach ($permissions_by_provider as $provider => $permissions) {
      if (!isset($managed_modules[$provider])) {
        continue;
      }

      // Module name.
      $form['permissions'][$provider] = [
        ['#markup' => ''],
        [
          '#wrapper_attributes' => [
            'class' => ['module'],
            'id' => 'module-' . $provider,
          ],
          '#markup' => $this->moduleHandler->getName($provider),
        ],
      ];
      foreach ($permissions as $perm => $perm_item) {
        // Fill in default values for the permission.
        $perm_item += [
          'description' => '',
          'restrict access' => FALSE,
          'warning' => !empty($perm_item['restrict access']) ? $this->t('Warning: Give to trusted roles only; this permission has security implications.') : '',
        ];
        $form['permissions'][$perm]['blacklist'] = [
          '#title' => $this->t('Blacklist: %permission', ['%permission' => $perm_item['title']]),
          '#title_display' => 'invisible',
          '#wrapper_attributes' => [
            'class' => ['checkbox'],
          ],
          '#type' => 'checkbox',
          '#default_value' => isset($blacklist[$perm]) ? 1 : 0,
          '#attributes' => ['class' => ['blacklist']],
          '#parents' => ['blacklist', $perm],
        ];
        $form['permissions'][$perm]['description'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="permission"><span class="title">{{ title }}</span>{% if description or warning %}<div class="description">{% if warning %}<em class="permission-warning">{{ warning }}</em> {% endif %}{{ description }}</div>{% endif %}</div>',
          '#context' => [
            'title' => $perm_item['title'],
          ],
        ];
        // Show the permission description.
        if (!$hide_descriptions) {
          $form['permissions'][$perm]['description']['#context']['description'] = $perm_item['description'];
          $form['permissions'][$perm]['description']['#context']['warning'] = $perm_item['warning'];
        }

      }
    }

    $form['#attached']['library'][] = 'user/drupal.user.admin';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $black_list = array_keys(array_filter($values['blacklist']));
    $this->config('module_permissions.settings')
      ->set('permission_blacklist', $black_list)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
