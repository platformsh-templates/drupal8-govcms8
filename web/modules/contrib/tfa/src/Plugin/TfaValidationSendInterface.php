<?php

namespace Drupal\tfa\Plugin;

/**
 * Interface TfaValidationSendInterface.
 *
 * Validation plugins that implement this interface are able to interact with
 * the Tfa begin() process to communicate a code during the start of the TFA
 * process.
 */
interface TfaValidationSendInterface {

  /**
   * TFA process begin. If the plugin needs to deliver a validation code, this
   * is the ideal place to perform that delivery.
   */
  public function begin();

}
