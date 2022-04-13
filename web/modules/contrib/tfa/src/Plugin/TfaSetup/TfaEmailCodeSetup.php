<?php


namespace Drupal\tfa\Plugin\TfaSetup;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\tfa\Plugin\TfaSetupInterface;
use Drupal\tfa\Plugin\TfaValidation\TfaEmailCode;

/**
 * Class TfaEmailCodeSetup
 *
 * @TfaSetup(
 *   id = "tfa_email_code_setup",
 *   label = @Translation("Email Send Setup"),
 *   description = @Translation("Email Send Setup Plugin"),
 *   setupMessages = {
 *    "saved" = @Translation("Email Send codes ***."),
 *    "skipped" = @Translation("Email send codes ^^^^.")
 *   }
 * )
 */
class TfaEmailCodeSetup extends TfaEmailCode implements TfaSetupInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getSetupForm(array $form, FormStateInterface $form_state) {
    $params = $form_state->getValues();
    $userData = $this->userData->get('tfa', $params['account']->id(), 'tfa_email_code');

    $form['email_code'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Receive authentication code by email'),
      '#description' => $this->t('Enables TFA code be sent by email associated to your account'),
      '#default_value' => isset($userData['email']) ? isset($userData['email']) : 0,
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['save'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSetupForm(array $form, FormStateInterface $form_state) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitSetupForm(array $form, FormStateInterface $form_state) {
    $params = $form_state->getValues();
    $userData = $this->userData->get('tfa', $params['account']->id(), 'tfa_email_code');
    $userData['email'] = $form_state->getValue('email_code');
    $this->userData->set('tfa', $params['account']->id(), 'tfa_email_code', $userData);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelpLinks() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSetupMessages() {
    return ($this->pluginDefinition['setupMessages']) ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOverview(array $params) {
    $output = array(
      'heading' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Enable Code Delivery'),
      ],
      'email_codes' => [
        '#theme' => 'links',
        '#access' => !$params['enabled'],
        '#links' => [
          'show' => [
            'title' => $this->t('Email codes'),
            'url' => Url::fromRoute('tfa.validation.setup', [
              'user' => $params['account']->id(),
              'method' => $params['plugin_id'],
            ]),
          ],
        ],
      ],
    );

    return $output;
  }
  public function ready() {
    return TRUE;
  }
}
