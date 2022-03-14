<?php

namespace Drupal\module_permissions_ui\Form;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Form\FormStateInterface;

/**
 * Managed Modules config form for Module Permissions.
 */
class ManagedModulesForm extends ProtectedModulesForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'module_permissions_ui_managed_modules';
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
    if ($this->config('module_permissions_ui.settings')->get('exclude_core')) {
      return ($module->info['package'] == 'Core' || $module->info['package'] == 'Core (Experimental)');
    }
    return FALSE;
  }

  /**
   * Get the list of selected modules.
   *
   * @return array
   *   Selected modules.
   */
  protected function getSelectedModules() {
    return $this->helper->getManagedModules();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#module_operation'] = $this->t('Managed');
    $form['filters']['text']['#attributes']['data-table'] = '#module-permissions-ui-managed-modules';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $selected_modules = array_keys(array_filter($values['selected_modules']));
    $this->config('module_permissions.settings')
      ->set('managed_modules', $selected_modules)
      ->save();

    $this->messenger()->addStatus($this->t('The configuration options have been saved.'));
  }

}
