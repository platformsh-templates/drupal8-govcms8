<?php

namespace Drupal\tfa\Plugin\TfaValidation;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\encrypt\Exception\EncryptException;
use Drupal\encrypt\Exception\EncryptionMethodCanNotDecryptException;
use Drupal\tfa\Plugin\TfaBasePlugin;
use Drupal\tfa\Plugin\TfaValidationInterface;
use Drupal\tfa\Plugin\TfaValidationSendInterface;
use Drupal\tfa\TfaRandomTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Performs validation of code sent to user.
 *
 * @TfaValidation(
 *   id = "tfa_email_code",
 *   label = @Translation("TFA Email Code Validation"),
 *   description = @Translation("TFA Email Code Validation Plugin"),
 *   isFallback = FALSE
 * )
 */
class TfaEmailCode extends TfaBasePlugin implements TfaValidationInterface, TfaValidationSendInterface, ContainerFactoryPluginInterface {

  use TfaRandomTrait;
  use StringTranslationTrait;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Logger factory.
   *
   * @var LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * A mail manager for sending email.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The time service.
   *
   * @var TimeInterface
   */
  protected $time;

  /**
   * Default code validity in seconds.
   *
   * @var int
   */
  protected $validityPeriod = 300;

  /**
   * Authentication email setting.
   *
   * @var array
   */
  protected $emailSetting;

  /**
   * Tfa user.
   *
   * @var \Drupal\user\Entity\User
   */
  public $recipient;

  /**
   * Indicator of email being sent.
   *
   * @var bool
   */
  protected  $isSent = FALSE;

  /**
   * Drupal hook_mail array.
   *
   * @var array
   */
  protected $message;

  /**
   * The user's language code.
   *
   * @var string
   */
  protected $langCode;

  /**
   * TfaEmailCode constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\user\UserDataInterface $user_data
   * @param \Drupal\encrypt\EncryptionProfileManagerInterface $encryption_profile_manager
   * @param \Drupal\encrypt\EncryptServiceInterface $encrypt_service
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   * @param \Drupal\Component\Datetime\TimeInterface $time
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserDataInterface $user_data, EncryptionProfileManagerInterface $encryption_profile_manager, EncryptServiceInterface $encrypt_service, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, MailManagerInterface $mail_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $user_data, $encryption_profile_manager, $encrypt_service);

    $this->validityPeriod = $config_factory->get('tfa.settings')->get('validation_plugin_settings.tfa_email_code.code_validity_period');
    $this->emailSetting = $config_factory->get('tfa.settings')->get('validation_plugin_settings.tfa_email_code.email_setting');
    $this->loggerFactory = $logger_factory;
    $this->mailManager = $mail_manager;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.data'),
      $container->get('encrypt.encryption_profile.manager'),
      $container->get('encryption'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('plugin.manager.mail'),
      $container->get('datetime.time')
    );
  }

  /**
   * TFA process begin.
   */
  public function begin() {
    $userData = $this->userData->get('tfa', $this->uid, 'tfa_email_code');
    $code = $this->randomCharacters(9, '1234567890');
    $userData['code'] = $this->encryptService->encrypt($code, $this->encryptionProfile);;
    $userData['expiry'] = $this->time->getCurrentTime() + $this->validityPeriod;
    $this->userData->set('tfa', $this->uid, 'tfa_email_code', $userData);
    $length = $this->validityPeriod / 60;

    $this->recipient =  User::load($this->uid);
    $search = ['[length]', '[code]'];
    $replace = [$length, $code];
    $body = str_replace($search, $replace, $this->emailSetting['body']);

    $this->message = [
      'subject' => $this->emailSetting['subject'],
      'langcode' => $this->langCode,
      'body' => $body,
    ];
    $params = [
      'account' => $this->recipient,
      'message' => $this->message,
    ];
    $logger = $this->loggerFactory->get('tfa');
    $result = $this->mailManager->mail('tfa', 'tfa_email_send', $this->recipient->getEmail(), $this->langCode, $params, NULL, true);

    if ($result['result'] != true) {
      $logger->error($this->t('There was a problem sending authentication code to @email.', [
        '@email' => $this->recipient->getEmail(),
      ]));
    }

    $this->isSent = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array $form, FormStateInterface $form_state) {
    $form['code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the received code'),
      '#required' => TRUE,
      '#description' => $this->t('The authentication code has been sent to your registered email. Check your email and enter the code.'),
      '#attributes' => ['autocomplete' => 'off'],
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['login'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Verify'),
    ];
    $form['actions']['resend'] = [
      '#type' => 'button',
      '#button_type' => 'primary',
      '#value' => $this->t('Resend'),
      '#limit_validation_errors' => [],
    ];

    if ($form_state->isRebuilding()) {
      $values = $form_state->getValues();
      // If user is asking for resending the code,
      // generate a new code and resend it.
      if (isset($values['op']) && $values['op']->getUntranslatedString() == 'Resend') {
        $this->ready();
      }
    }

    return $form;
  }
  /**
   * {@inheritdoc}
   */
  protected function validate($code) {
    $this->isValid = FALSE;

    // Remove empty spaces.
    $code = trim(str_replace(' ', '', $code));
    $userData = $this->userData->get('tfa', $this->uid, 'tfa_email_code');
    $timestamp = $this->time->getCurrentTime();

    if ($timestamp > $userData['expiry']){
      unset($userData['code']);
      unset($userData['expiry']);
      $this->errorMessages['email_code'] = $this->t('Expired. Try login again.');
      return $this->isValid;
    }
    $storedCode = $userData['code'];
    try {
      $storedCode = $this->encryptService->decrypt($storedCode, $this->encryptionProfile);
    } catch (EncryptException $e) {
      $this->loggerFactory->get('tfa')->error($e->getMessage());
    } catch (EncryptionMethodCanNotDecryptException $e) {
      $this->loggerFactory->get('tfa')->error($e->getMessage());
    }

    if (hash_equals(trim(str_replace(' ', '', $storedCode)) ,$code)) {
      $this->isValid = TRUE;
      unset($userData['code']);
      unset($userData['expiry']);
      $this->userData->set('tfa', $this->uid, 'tfa_email_code', $userData);
      return $this->isValid;
    }

    if (!$this->isValid) {
      $this->errorMessages['email_code'] = $this->t('Invalid code.');
    }

    return $this->isValid;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // If user is asking for resending the code,
    // no need to validate the input.
    if (isset($values['op']) && $values['op']->getUntranslatedString() == 'Resend') {
      // Resend user the access code.
      $this->isSent = FALSE;
      return FALSE;
    }
    return $this->validate($values['code']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(Config $config, array $state = []) {
    $options = [60 => 1, 120 => 2, 180 => 3, 240 => 4, 300 => 5];

    $settings_form = [];

    $settings_form['code_validity_period'] = [
      '#type' => 'select',
      '#title' => $this->t('Code validity period in minutes'),
      '#description' => $this->t('Select the validity period of code sent.'),
      '#options' => $options,
      '#default_value' => $this->validityPeriod,
    ];

    $settings_form['email_setting'] = [
      '#type' => 'details',
      '#title' => $this->t('Authentication code email'),
      '#description' => $this->t('This email is sent the authentication code to the user. <br>Available tokens are: <ul><li>Valid minutes: [length]</li><li>Authentication code: [code]</li><li>Site information: [site]</li><li>User information: [user]</li></ul> Common variables are: [site:name], [site:url], [user:display-name], [user:account-name], and [user:mail].'),
      'subject' => [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $this->emailSetting['subject'] ?: $this->t('[site:name] Authentication code'),
        '#required' => TRUE,
      ],
      'body' => [
        '#type' => 'textarea',
        '#title' => $this->t('Body'),
        '#default_value' => $this->emailSetting['body'] ?: $this->t('[user:display-name],

This code is valid for [length] minutes. Your code is: [code]

This code will be expired after login.'),
        '#required' => TRUE,
        '#attributes' => [
          'rows' => 10,
        ],
      ],
    ];

    return $settings_form;
  }

  /**
   * {@inheritdoc}
   */
  public function ready() {
    $userData = $this->userData->get('tfa', $this->uid, 'tfa_user_settings');

    if (empty($userData['data']['plugins'])){
      return FALSE;
    }

    // Send user the access code via email,
    // if it hasn't been sent yet.
    if (!$this->isSent) {
      $this->begin();
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbacks() {
    return ($this->pluginDefinition['fallbacks']) ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function isFallback() {
    return ($this->pluginDefinition['isFallback']) ?: FALSE;
  }

}
