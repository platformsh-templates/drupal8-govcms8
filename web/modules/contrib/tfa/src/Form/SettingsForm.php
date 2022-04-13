<?php

namespace Drupal\tfa\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\tfa\TfaDataTrait;
use Drupal\tfa\TfaLoginPluginManager;
use Drupal\tfa\TfaSendPluginManager;
use Drupal\tfa\TfaSetupPluginManager;
use Drupal\tfa\TfaValidationPluginManager;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The admin configuration page.
 */
class SettingsForm extends ConfigFormBase {
  use TfaDataTrait;

  /**
   * The login plugin manager to fetch plugin information.
   *
   * @var \Drupal\tfa\TfaLoginPluginManager
   */
  protected $tfaLogin;

  /**
   * The send plugin manager to fetch plugin information.
   *
   * @var \Drupal\tfa\TfaSendPluginManager
   */
  protected $tfaSend;

  /**
   * The validation plugin manager to fetch plugin information.
   *
   * @var \Drupal\tfa\TfaValidationPluginManager
   */
  protected $tfaValidation;

  /**
   * The setup plugin manager to fetch plugin information.
   *
   * @var \Drupal\tfa\TfaSetupPluginManager
   */
  protected $tfaSetup;

  /**
   * Provides the user data service object.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Encryption profile manager to fetch the existing encryption profiles.
   *
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface
   */
  protected $encryptionProfileManager;

  /**
   * The admin configuration form constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory object.
   * @param \Drupal\tfa\TfaLoginPluginManager $tfa_login
   *   The login plugin manager.
   * @param \Drupal\tfa\TfaSendPluginManager $tfa_send
   *   The send plugin manager.
   * @param \Drupal\tfa\TfaValidationPluginManager $tfa_validation
   *   The validation plugin manager.
   * @param \Drupal\tfa\TfaSetupPluginManager $tfa_setup
   *   The setup plugin manager.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Drupal\encrypt\EncryptionProfileManagerInterface $encryption_profile_manager
   *   Encrypt profile manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TfaLoginPluginManager $tfa_login, TfaSendPluginManager $tfa_send, TfaValidationPluginManager $tfa_validation, TfaSetupPluginManager $tfa_setup, UserDataInterface $user_data, EncryptionProfileManagerInterface $encryption_profile_manager) {
    parent::__construct($config_factory);
    $this->tfaLogin = $tfa_login;
    $this->tfaSend = $tfa_send;
    $this->tfaSetup = $tfa_setup;
    $this->tfaValidation = $tfa_validation;
    $this->encryptionProfileManager = $encryption_profile_manager;
    // User Data service to store user-based data in key value pairs.
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.tfa.login'),
      $container->get('plugin.manager.tfa.send'),
      $container->get('plugin.manager.tfa.validation'),
      $container->get('plugin.manager.tfa.setup'),
      $container->get('user.data'),
      $container->get('encrypt.encryption_profile.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tfa_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tfa.settings');
    $form = [];

    // Get Login Plugins.
    $login_plugins = $this->tfaLogin->getDefinitions();

    // Get Send Plugins.
    $send_plugins = $this->tfaSend->getDefinitions();

    // Get Validation Plugins.
    $validation_plugins = $this->tfaValidation->getDefinitions();
    // Get validation plugin labels.
    $validation_plugins_labels = [];
    foreach ($validation_plugins as $key => $plugin) {
      $validation_plugins_labels[$plugin['id']] = $plugin['label']->render();
    }
    // Fetching all available encryption profiles.
    $encryption_profiles = $this->encryptionProfileManager->getAllEncryptionProfiles();

    $plugins_empty = $this->dataEmptyCheck($validation_plugins, $this->t('No plugins available for validation. See the TFA help documentation for setup.'));
    $encryption_profiles_empty = $this->dataEmptyCheck($encryption_profiles, $this->t('No Encryption profiles available. <a href=":add_profile_url">Add an encryption profile</a>.', [':add_profile_url' => Url::fromRoute('entity.encryption_profile.add_form')->toString()]));

    if ($plugins_empty || $encryption_profiles_empty) {
      $form_state->cleanValues();
      // Return form instead of parent::BuildForm to avoid the save button.
      return $form;
    }

    // Enable TFA checkbox.
    $form['tfa_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable TFA'),
      '#default_value' => $config->get('enabled') && !empty($encryption_profiles),
      '#description' => $this->t('Enable TFA for account authentication.'),
      '#disabled' => empty($encryption_profiles),
    ];

    $enabled_state = [
      'visible' => [
        ':input[name="tfa_enabled"]' => ['checked' => TRUE],
      ],
    ];

    $form['tfa_required_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles required to set up TFA'),
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names(TRUE)),
      '#default_value' => $config->get('required_roles') ?: [],
      '#description' => $this->t('Require users with these roles to set up TFA'),
      '#states' => $enabled_state,
      '#required' => FALSE,
    ];

    $form['tfa_allowed_validation_plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed Validation plugins'),
      '#options' => $validation_plugins_labels,
      '#default_value' => $config->get('allowed_validation_plugins') ?: ['tfa_totp'],
      '#description' => $this->t('Plugins that can be setup by users for various TFA processes.'),
      // Show only when TFA is enabled.
      '#states' => $enabled_state,
      '#required' => TRUE,
    ];
    $form['tfa_validate'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Validation plugin'),
      '#options' => $validation_plugins_labels,
      '#default_value' => $config->get('default_validation_plugin') ?: 'tfa_totp',
      '#description' => $this->t('Plugin that will be used as the default TFA process.'),
      // Show only when TFA is enabled.
      '#states' => $enabled_state,
      '#required' => TRUE,
    ];

    // Validation plugin related settings.
    // $validation_plugins_labels has the plugin ids as the key.
    $form['validation_plugin_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Extra Settings'),
      '#descrption' => $this->t('Extra plugin settings.'),
      '#tree' => TRUE,
      '#states' => $enabled_state,
    ];
    foreach ($validation_plugins_labels as $key => $val) {
      $instance = $this->tfaValidation->createInstance($key, [
        'uid' => $this->currentUser()->id(),
      ]);

      if (method_exists($instance, 'buildConfigurationForm')) {
        $validation_enabled_state = [
          'visible' => [
            [
              ':input[name="tfa_enabled"]' => ['checked' => TRUE],
              ':input[name="tfa_allowed_validation_plugins[' . $key . ']"]' => ['checked' => TRUE],
            ],
            [
              'select[name="tfa_validate"]' => ['value' => $key],
            ],
          ],
        ];
        $form['validation_plugin_settings'][$key . '_container'] = [
          '#type' => 'container',
          '#states' => $validation_enabled_state,
        ];
        $form['validation_plugin_settings'][$key . '_container']['title'] = [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $val,
        ];
        $form['validation_plugin_settings'][$key . '_container']['form'] = $instance->buildConfigurationForm($config, $validation_enabled_state);
        $form['validation_plugin_settings'][$key . '_container']['form']['#parents'] = [
          'validation_plugin_settings',
          $key,
        ];
      }
    }

    // The encryption profiles select box.
    $encryption_profile_labels = [];
    foreach ($encryption_profiles as $encryption_profile) {
      $encryption_profile_labels[$encryption_profile->id()] = $encryption_profile->label();
    }
    $form['encryption_profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Encryption Profile'),
      '#options' => $encryption_profile_labels,
      '#description' => $this->t('Encryption profiles to encrypt the secret'),
      '#default_value' => $config->get('encryption'),
      '#states' => $enabled_state,
      '#required' => TRUE,
    ];

    $skip_value = $config->get('validation_skip');
    $form['validation_skip'] = [
      '#type' => 'number',
      '#title' => $this->t('Skip Validation'),
      '#default_value' => isset($skip_value) ? $skip_value : 3,
      '#description' => $this->t('No. of times a user without having setup tfa validation can login.'),
      '#size' => 2,
      '#states' => $enabled_state,
      '#required' => TRUE,
    ];

    // Enable login plugins.
    $login_form_array = [];

    foreach ($login_plugins as $login_plugin) {
      $id = $login_plugin['id'];
      $title = $login_plugin['label']->render();
      $login_form_array[$id] = (string) $title;
    }

    $form['tfa_login'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Login plugins'),
      '#options' => $login_form_array,
      '#default_value' => ($config->get('login_plugins')) ? $config->get('login_plugins') : [],
      '#description' => $this->t('Plugins that can allow a user to skip the TFA process. If any plugin returns true the user will not be required to follow TFA. <strong>Use with caution.</strong>'),
    ];

    // Enable send plugins.
    if (count($send_plugins)) {
      $send_form_array = [];

      foreach ($send_plugins as $send_plugin) {
        $id = $send_plugin['id'];
        $title = $send_plugin['label']->render();
        $send_form_array[$id] = (string) $title;
      }

      $form['tfa_send'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Send plugins'),
        '#options' => $send_form_array,
        '#default_value' => ($config->get('send_plugins')) ? $config->get('send_plugins') : [],
        '#description' => $this->t('TFA Send Plugins, like TFA Twilio'),
      ];
    }

    $form['tfa_flood'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('TFA Flood Settings'),
      '#description' => $this->t('Configure the TFA Flood Settings.'),
      '#states' => $enabled_state,
    ];

    // Flood control identifier.
    $form['tfa_flood']['tfa_flood_uid_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flood Control With UID Only'),
      '#default_value' => ($config->get('tfa_flood_uid_only')) ?: 0,
      '#description' => $this->t('Flood control on the basis of uid only.'),
    ];

    // Flood window. Defaults to 5min.
    $form['tfa_flood']['tfa_flood_window'] = [
      '#type' => 'number',
      '#title' => $this->t('TFA Flood Window'),
      '#default_value' => ($config->get('tfa_flood_window')) ?: 300,
      '#description' => $this->t('TFA Flood Window.'),
      '#min' => 1,
      '#size' => 5,
      '#required' => TRUE,
    ];

    // Flood threshold. Defaults to 6 failed attempts.
    $form['tfa_flood']['tfa_flood_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('TFA Flood Threshold'),
      '#default_value' => ($config->get('tfa_flood_threshold')) ?: 6,
      '#description' => $this->t('TFA Flood Threshold.'),
      '#min' => 1,
      '#size' => 2,
      '#required' => TRUE,
    ];

    // Email configurations.
    if ($config->get('mail') === NULL) {
      $message = $this->t('Email settings missing. If this is the first time you are seeing this error after upgrading the TFA module, then please make sure you have run the required @update_link function.', [
        '@update_link' => Link::createFromRoute('update', 'system.status')->toString(),
      ]);
      $this->messenger()->addError($message);
    }
    $form['mail'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Emails'),
      '#default_tab' => 'edit-tfa-enabled-configuration',
    ];
    $form['tfa_enabled_configuration'] = [
      '#type' => 'details',
      '#title' => $this->t('User enabled TFA validation method'),
      '#description' => $this->t('This email is sent to the user when they enable a TFA validation method on their account. Available tokens are: [site] and [user]. Common variables are: [site:name], [site:url], [user:display-name], [user:account-name], and [user:mail].'),
      '#group' => 'mail',
      'tfa_enabled_configuration_subject' => [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $config->get('mail.tfa_enabled_configuration.subject'),
        '#required' => TRUE,
      ],
      'tfa_enabled_configuration_body' => [
        '#type' => 'textarea',
        '#title' => $this->t('Body'),
        '#default_value' => $config->get('mail.tfa_enabled_configuration.body'),
        '#required' => TRUE,
        '#attributes' => [
          'rows' => 10,
        ],
      ],
    ];
    $form['tfa_disabled_configuration'] = [
      '#type' => 'details',
      '#title' => $this->t('User disabled TFA validation method'),
      '#description' => $this->t('This email is sent to the user when they disable a TFA validation method on their account. Available tokens are: [site] and [user]. Common variables are: [site:name], [site:url], [user:display-name], [user:account-name], and [user:mail].'),
      '#group' => 'mail',
      'tfa_disabled_configuration_subject' => [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $config->get('mail.tfa_disabled_configuration.subject'),
        '#required' => TRUE,
      ],
      'tfa_disabled_configuration_body' => [
        '#type' => 'textarea',
        '#title' => $this->t('Body'),
        '#default_value' => $config->get('mail.tfa_disabled_configuration.body'),
        '#required' => TRUE,
        '#attributes' => [
          'rows' => 10,
        ],
      ],
    ];
    $form['help_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Help text'),
      '#description' => $this->t('Text to display when a user is locked out and blocked from logging in.'),
      '#default_value' => $config->get('help_text'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    if ($form_state->getValue('validation_skip') > 50) {
      $form_state->setErrorByName('validation_skip', $this->t('The validation_skip number is too high. Please enter a value between 0 and 50.'));
    }
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $validation_plugin = $form_state->getValue('tfa_validate');
    $allowed_validation_plugins = $form_state->getValue('tfa_allowed_validation_plugins');
    // Default validation plugin must always be allowed.
    $allowed_validation_plugins[$validation_plugin] = $validation_plugin;
    $validation_plugin_settings = $form_state->getValue('validation_plugin_settings');
    if (empty($validation_plugin_settings)) {
      $validation_plugin_settings = [];
    }

    // Delete tfa data if plugin is disabled.
    if ($this->config('tfa.settings')->get('enabled') && !$form_state->getValue('tfa_enabled')) {
      $this->userData->delete('tfa');
    }

    $send_plugins = $form_state->getValue('tfa_send') ?: [];
    $login_plugins = $form_state->getValue('tfa_login') ?: [];
    $this->config('tfa.settings')
      ->set('enabled', $form_state->getValue('tfa_enabled'))
      ->set('required_roles', $form_state->getValue('tfa_required_roles'))
      ->set('send_plugins', array_filter($send_plugins))
      ->set('login_plugins', array_filter($login_plugins))
      ->set('allowed_validation_plugins', array_filter($allowed_validation_plugins))
      ->set('default_validation_plugin', $validation_plugin)
      ->set('validation_plugin_settings', $validation_plugin_settings)
      ->set('validation_skip', $form_state->getValue('validation_skip'))
      ->set('encryption', $form_state->getValue('encryption_profile'))
      ->set('tfa_flood_uid_only', $form_state->getValue('tfa_flood_uid_only'))
      ->set('tfa_flood_window', $form_state->getValue('tfa_flood_window'))
      ->set('tfa_flood_threshold', $form_state->getValue('tfa_flood_threshold'))
      ->set('mail.tfa_enabled_configuration.subject', $form_state->getValue('tfa_enabled_configuration_subject'))
      ->set('mail.tfa_enabled_configuration.body', $form_state->getValue('tfa_enabled_configuration_body'))
      ->set('mail.tfa_disabled_configuration.subject', $form_state->getValue('tfa_disabled_configuration_subject'))
      ->set('mail.tfa_disabled_configuration.body', $form_state->getValue('tfa_disabled_configuration_body'))
      ->set('help_text', $form_state->getValue('help_text'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tfa.settings'];
  }

  /**
   * Check whether the given data is empty and set appropriate message.
   *
   * @param array $data
   *   Data to be checked.
   * @param string $message
   *   Error message to show if data is empty.
   *
   * @return bool
   *   TRUE if data is empty otherwise FALSE.
   */
  protected function dataEmptyCheck(array $data, $message) {
    if (empty($data)) {
      $this->messenger()->addError($message);
      return TRUE;
    }

    return FALSE;
  }

}
