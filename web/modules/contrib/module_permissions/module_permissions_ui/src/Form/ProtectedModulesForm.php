<?php

namespace Drupal\module_permissions_ui\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\module_permissions\Helper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Protected Modules config form for Module Permissions.
 */
class ProtectedModulesForm extends ConfigFormBase {

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
   * Module Installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * Module list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

  /**
   * ProtectedModulesForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\module_permissions\Helper $helper
   *   Helper service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   Module installer service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   Module list service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Helper $helper, ModuleHandlerInterface $module_handler, ModuleInstallerInterface $module_installer, ModuleExtensionList $module_list) {
    parent::__construct($config_factory);
    $this->helper = $helper;
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
    $this->moduleList = $module_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_permissions.helper'),
      $container->get('module_handler'),
      $container->get('module_installer'),
      $container->get('extension.list.module')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'module_permissions_ui_protected_modules';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['module_permissions.settings'];
  }

  /**
   * Check if a module should be excluded from the list.
   *
   * @param \Drupal\Core\Extension\Extension $module
   *   Module info.
   *
   * @return bool
   *   TRUE if the module should be excluded.
   */
  protected function isExcludedModule(Extension $module) {
    return FALSE;
  }

  /**
   * Get the list of selected modules.
   *
   * @return array
   *   Selected modules.
   */
  protected function getSelectedModules() {
    return $this->helper->getProtectedModules();
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\system\Form\ModulesUninstallForm::buildForm()
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $selected_modules = $this->getSelectedModules();

    // Get a list of all available modules.
    $modules = $this->moduleList->getList();

    $form['#module_operation'] = $this->t('Protect');

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
        'data-table' => '#module-permissions-ui-protected-modules',
        'autocomplete' => 'off',
      ],
    ];

    $hide_descriptions = system_admin_compact_mode();
    $form['system_compact_link'] = [
      '#id' => FALSE,
      '#type' => 'system_compact_link',
    ];

    $form['modules'] = [];

    uasort($modules, 'system_sort_modules_by_info_name');

    $form['selected_modules'] = ['#tree' => TRUE];

    foreach ($modules as $module) {
      if ($this->isExcludedModule($module)) {
        continue;
      }
      $name = $module->info['name'] ?: $module->getName();
      $form['modules'][$module->getName()]['#module_name'] = $name;
      $form['modules'][$module->getName()]['name']['#markup'] = $name;
      $form['modules'][$module->getName()]['description']['#markup'] = $module->info['description'];

      $form['selected_modules'][$module->getName()] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Protect @module module', ['@module' => $name]),
        '#title_display' => 'invisible',
        '#default_value' => isset($selected_modules[$module->getName()]),
      ];

      if (!$hide_descriptions) {
        foreach (array_keys($module->requires) as $dependency) {
          $name = isset($modules[$dependency]->info['name']) ? $modules[$dependency]->info['name'] : $dependency;
          $form['modules'][$module->getName()]['#requires'][] = $name;
        }

        foreach (array_keys($module->required_by) as $dependent) {
          if (drupal_get_installed_schema_version($dependent) != SCHEMA_UNINSTALLED) {
            $name = isset($modules[$dependent]->info['name']) ? $modules[$dependent]->info['name'] : $dependent;
            $form['modules'][$module->getName()]['#required_by'][] = $name;
          }
        }
      }
    }

    $form['#attached']['library'][] = 'system/drupal.system.modules';
    $form['#theme'] = 'module_permissions_ui_system_modules';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $selected_modules = array_keys(array_filter($values['selected_modules']));
    $this->config('module_permissions.settings')
      ->set('protected_modules', $selected_modules)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
