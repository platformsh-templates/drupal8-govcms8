<?php

/**
 * @file
 * Contains \Drupal\metatag\Form\MetatagUnsetForm.
 */

namespace Drupal\metatag\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Class MetatagUnsetForm.
 *
 * @package Drupal\metatag\Form
 */
class MetatagUnsetForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'metatag_unset_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'metatag.unset',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get config values.
    $config = $this->config('metatag.unset');
    $rel_list = $config->get('rel_list');
    if (!$rel_list) {
      $rel_list = [];
    }
    $name_list = $config->get('name_list');
    if (!$name_list) {
      $name_list = [];
    }

    // Unset by "rel" attribute.
    $form['unset_by_rel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unset meta tags by "rel" attribute'),
      '#default_value' => $config->get('unset_by_rel'),
    ];
    // Values list for "rel" attribute.
    $form['rel_list'] = [
      '#type' => 'textarea',
      '#description' => $this->t('List of "rel" attribute values (one value per line). Meta tags with a "rel" attribute that has one of these values will be removed.'),
      '#rows' => 10,
      '#states' => [
        // Only show this field when the 'unset_by_rel' checkbox is enabled.
        'visible' => [
          ':input[name="unset_by_rel"]' => ['checked' => TRUE],
        ],
      ],
      '#default_value' => implode(PHP_EOL, $rel_list),
    ];
    // Unset by "name" attribute.
    $form['unset_by_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unset meta tags by "name" attribute'),
      '#default_value' => $config->get('unset_by_name'),
    ];
    // Values list for "name" attribute.
    $form['name_list'] = [
      '#type' => 'textarea',
      '#description' => $this->t('List of "name" attribute values (one value per line). Meta tags with a "name" attribute that has one of these values will be removed.'),
      '#rows' => 10,
      '#states' => [
        // Only show this field when the 'unset_by_name' checkbox is enabled.
        'visible' => [
          ':input[name="unset_by_name"]' => ['checked' => TRUE],
        ],
      ],
      '#default_value' => implode(PHP_EOL, $name_list),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\core\Config\Config $config */
    $config = \Drupal::service('config.factory')->getEditable('metatag.unset');
    $unset_by_rel = (bool) $form_state->getValue('unset_by_rel');
    $unset_by_name = (bool) $form_state->getValue('unset_by_name');
    // Define list filter callback.
    $list_filter_callback = function ($value) {
      return !empty($value);
    };

    // Process "rel" values list.
    if ($unset_by_rel) {
      $rel_list = explode(PHP_EOL, $form_state->getValue('rel_list', ''));
      $rel_list = array_map('trim', $rel_list);
      $rel_list = array_filter($rel_list, $list_filter_callback);
      $config->set('rel_list', $rel_list);
    }
    // Process "name" values list.
    if ($unset_by_name) {
      $name_list = explode(PHP_EOL, $form_state->getValue('name_list', ''));
      $name_list = array_map('trim', $name_list);
      $name_list = array_filter($name_list, $list_filter_callback);
      $config->set('name_list', $name_list);
    }

    // Save config values.
    $config->set('unset_by_rel', $unset_by_rel);
    $config->set('unset_by_name', $unset_by_name);
    $config->save();
    parent::submitForm($form, $form_state);
  }

}