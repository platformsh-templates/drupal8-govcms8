<?php

namespace Drupal\module_permissions;

use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Uninstall Validator class.
 *
 * @package Drupal\module_permissions
 */
class UninstallValidator implements ModuleUninstallValidatorInterface {
  use StringTranslationTrait;

  /**
   * Helper service.
   *
   * @var \Drupal\module_permissions\Helper
   */
  protected $helper;

  /**
   * UninstallValidator constructor.
   *
   * @param \Drupal\module_permissions\Helper $helper
   *   Helper.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   Translation service.
   */
  public function __construct(Helper $helper, TranslationInterface $string_translation) {
    $this->helper = $helper;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];
    $protected_modules = $this->helper->getProtectedModules();
    if (isset($protected_modules[$module])) {
      // Mark the module as required and prevent it from being uninstalled.
      $reasons[] = $this->t('This module is required and protected in current site.');
    }

    return $reasons;
  }

}
