<?php

namespace Drupal\entity_class_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_class_formatter'.
 *
 * @FieldFormatter(
 *   id = "entity_class_formatter",
 *   label = @Translation("Entity Class"),
 *   field_types = {
 *     "boolean",
 *     "decimal",
 *     "entity_reference",
 *     "float",
 *     "integer",
 *     "list_string",
 *     "string",
 *   }
 * )
 */
class EntityClassFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'prefix' => '',
      'suffix' => '',
      'attr' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix to be attached before each class'),
      '#default_value' => $this->getSetting('prefix'),
    ];
    $form['suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix to be attached after each class'),
      '#default_value' => $this->getSetting('suffix'),
    ];
    $form['attr'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Attribute name to be used instead of class'),
      '#description' => $this->t('The field value will be escaped and assigned to the attribute you specify here (e.g. "data-value").'),
      '#default_value' => $this->getSetting('attr'),
      '#required' => in_array($this->fieldDefinition->getType(), [
        'decimal',
        'float',
        'integer',
      ]),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $prefix = $this->getSetting('prefix');
    if (!empty($prefix)) {
      $summary[] = $this->t('Prefix: "@prefix"', [
        '@prefix' => $prefix,
      ]);
    }
    $suffix = $this->getSetting('suffix');
    if (!empty($suffix)) {
      $summary[] = $this->t('Suffix: "@suffix".', [
        '@suffix' => $suffix,
      ]);
    }
    $attr = $this->getSetting('attr');
    if (!empty($attr)) {
      $summary[] = $this->t('Attribute: "@attr".', [
        '@attr' => $attr,
      ]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Instead of outputting the value on the page
    // we are inserting it as a class into the markup.
    return [];
  }

}
