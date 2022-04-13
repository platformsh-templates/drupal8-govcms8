<?php

namespace Drupal\module_permissions_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Module Permissions.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'module_permissions_ui_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['module_permissions_ui.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('module_permissions_ui.settings');
    $form = parent::buildForm($form, $form_state);

    $form['exclude_core'] = [
      '#type' => 'radios',
      '#title' => $this->t('Exclude core modules from Managed modules list'),
      '#description' => $this->t('Warning: for security reasons, it is advisable to always exclude core modules from the  managed module list.'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $config->get('exclude_core'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('module_permissions_ui.settings')
      ->set('exclude_core', $values['exclude_core'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
