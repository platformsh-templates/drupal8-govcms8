<?php

namespace Drupal\tfa\Plugin\TfaLogin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\tfa\Plugin\TfaBasePlugin;
use Drupal\tfa\Plugin\TfaLoginInterface;
use Drupal\tfa\Plugin\TfaValidationInterface;
use Drupal\user\UserDataInterface;

/**
 * Trusted browser validation class.
 *
 * @TfaLogin(
 *   id = "tfa_trusted_browser",
 *   label = @Translation("TFA Trusted Browser"),
 *   description = @Translation("TFA Trusted Browser Plugin"),
 *   setupPluginId = "tfa_trusted_browser_setup",
 * )
 */
class TfaTrustedBrowser extends TfaBasePlugin implements TfaLoginInterface, TfaValidationInterface {
  use StringTranslationTrait;

  /**
   * Trust browser.
   *
   * @var bool
   */
  protected $trustBrowser;

  /**
   * The cookie name.
   *
   * @var string
   */
  protected $cookieName;

  /**
   * Cookie expiration time.
   *
   * @var string
   */
  protected $expiration;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserDataInterface $user_data, EncryptionProfileManagerInterface $encryption_profile_manager, EncryptServiceInterface $encrypt_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $user_data, $encryption_profile_manager, $encrypt_service);
    $config = \Drupal::config('tfa.settings');
    $this->cookieName = $config->get('cookie_name') ?: 'TFA';
    // Expiration defaults to 30 days.
    $this->expiration = $config->get('trust_cookie_expiration') ?: 86400 * 30;
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public function loginAllowed() {
    if (isset($_COOKIE[$this->cookieName]) && $this->trustedBrowser($_COOKIE[$this->cookieName]) !== FALSE) {
      $this->setUsed($_COOKIE[$this->cookieName]);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array $form, FormStateInterface $form_state) {
    $form['trust_browser'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remember this browser?'),
      '#description' => $this->t('Not recommended if you are on a public or shared computer.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface &$form_state) {
    $trust_browser = $form_state->getValue('trust_browser');
    if (!empty($trust_browser)) {
      $this->setTrusted($this->generateBrowserId(), $this->getAgent());
    }
  }

  /**
   * Finalize the browser setup.
   *
   * @throws \Exception
   */
  public function finalize() {
    if ($this->trustBrowser) {
      $name = $this->getAgent();
      $this->setTrusted($this->generateBrowserId(), $name);
    }
  }

  /**
   * Generate a random value to identify the browser.
   *
   * @return string
   *   Base64 encoded browser id.
   *
   * @throws \Exception
   */
  protected function generateBrowserId() {
    $id = base64_encode(random_bytes(32));
    return strtr($id, ['+' => '-', '/' => '_', '=' => '']);
  }

  /**
   * Store browser value and issue cookie for user.
   *
   * @param string $id
   *   Trusted browser id.
   * @param string $name
   *   The custom browser name.
   */
  protected function setTrusted($id, $name = '') {
    // Currently broken.
    // Store id for account.
    $records = $this->getUserData('tfa', 'tfa_trusted_browser', $this->configuration['uid'], $this->userData) ?: [];
    $request_time = \Drupal::time()->getRequestTime();

    $records[$id] = [
      'created' => $request_time,
      'ip' => \Drupal::request()->getClientIp(),
      'name' => $name,
    ];

    $data = [
      'tfa_trusted_browser' => $records,
    ];

    $this->setUserData('tfa', $data, $this->configuration['uid'], $this->userData);
    // Issue cookie with ID.
    $cookie_secure = ini_get('session.cookie_secure');
    $expiration = $request_time + $this->expiration;
    $domain = strpos($_SERVER['HTTP_HOST'], 'localhost') === FALSE ? $_SERVER['HTTP_HOST'] : FALSE;
    setcookie($this->cookieName, $id, $expiration, '/', $domain, (empty($cookie_secure) ? FALSE : TRUE), TRUE);
    $name = empty($name) ? $this->getAgent() : $name;
    // @todo use services defined in module instead this procedural way.
    \Drupal::logger('tfa')->info('Set trusted browser for user UID @uid, browser @name', [
      '@name' => $name,
      '@uid' => $this->uid,
    ]);
  }

  /**
   * Updated browser last used time.
   *
   * @param int $id
   *   Internal browser ID to update.
   */
  protected function setUsed($id) {
    $result = $this->getUserData('tfa', 'tfa_trusted_browser', $this->uid, $this->userData);
    $result[$id]['last_used'] = \Drupal::time()->getRequestTime();
    $data = [
      'tfa_trusted_browser' => $result,
    ];
    $this->setUserData('tfa', $data, $this->uid, $this->userData);
  }

  /**
   * Check if browser id matches user's saved browser.
   *
   * @param string $id
   *   The browser ID.
   *
   * @return bool
   *   TRUE if ID exists otherwise FALSE.
   */
  protected function trustedBrowser($id) {
    // Check if $id has been saved for this user.
    $result = $this->getUserData('tfa', 'tfa_trusted_browser', $this->uid, $this->userData);
    if (isset($result[$id])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Delete users trusted browser.
   *
   * @param string $id
   *   (optional) Id of the browser to be purged.
   *
   * @return bool
   *   TRUE is id found and purged otherwise FALSE.
   */
  protected function deleteTrusted($id = '') {
    $result = $this->getUserData('tfa', 'tfa_trusted_browser', $this->uid, $this->userData);
    if ($id) {
      if (isset($result[$id])) {
        unset($result[$id]);
        $data = [
          'tfa_trusted_browser' => $result,
        ];
        $this->setUserData('tfa', $data, $this->uid, $this->userData);
        return TRUE;
      }
    }
    else {
      $this->deleteUserData('tfa', 'tfa_trusted_browser', $this->uid, $this->userData);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get simplified browser name from user agent.
   *
   * @param string $name
   *   Default browser name.
   *
   * @return string
   *   Simplified browser name.
   */
  protected function getAgent($name = '') {
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
      // Match popular user agents.
      $agent = $_SERVER['HTTP_USER_AGENT'];
      if (preg_match("/like\sGecko\)\sChrome\//", $agent)) {
        $name = 'Chrome';
      }
      elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== FALSE) {
        $name = 'Firefox';
      }
      elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE) {
        $name = 'Internet Explorer';
      }
      elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== FALSE) {
        $name = 'Safari';
      }
      else {
        // Otherwise filter agent and truncate to column size.
        $name = substr($agent, 0, 255);
      }
    }
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function ready() {
    return TRUE;
  }

}
