<?php

namespace Drupal\tfa;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\user\UserDataInterface;

/**
 * The login plugin manager.
 */
class TfaLoginPluginManager extends DefaultPluginManager {

  /**
   * Provides the user data service object.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * TFA configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $tfaSettings;

  /**
   * Encryption profile manager.
   *
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface
   */
  protected $encryptionProfileManager;

  /**
   * Encryption service.
   *
   * @var \Drupal\encrypt\EncryptService
   */
  protected $encryptService;

  /**
   * Constructs a new TfaValidation plugin manager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\user\UserDataInterface $user_data
   *   User data service.
   * @param \Drupal\encrypt\EncryptionProfileManagerInterface $encryption_profile_manager
   *   Encryption profile manager.
   * @param \Drupal\encrypt\EncryptServiceInterface $encrypt_service
   *   Encryption service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, UserDataInterface $user_data, EncryptionProfileManagerInterface $encryption_profile_manager, EncryptServiceInterface $encrypt_service) {
    parent::__construct('Plugin/TfaLogin', $namespaces, $module_handler, 'Drupal\tfa\Plugin\TfaLoginInterface', 'Drupal\tfa\Annotation\TfaLogin');
    $this->alterInfo('tfa_login_info');
    $this->setCacheBackend($cache_backend, 'tfa_login');
    $this->tfaSettings = $config_factory->get('tfa.settings');
    $this->userData = $user_data;
    $this->encryptService = $encrypt_service;
    $this->encryptionProfileManager = $encryption_profile_manager;
  }

  /**
   * Create an instance of a plugin.
   *
   * @param string $plugin_id
   *   The id of the setup plugin.
   * @param array $configuration
   *   Configuration data for the setup plugin.
   *
   * @return object
   *   The plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $plugin_definition = $this->getDefinition($plugin_id);
    /** @var \Drupal\tfa\Plugin\TfaBasePlugin $plugin_class */
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);
    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($plugin_class, 'Drupal\Core\Plugin\ContainerFactoryPluginInterface')) {
      // phpcs:ignore DrupalPractice.Objects.GlobalDrupal.GlobalDrupal
      $plugin = $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition, $this->userData, $this->encryptionProfileManager, $this->encryptService);
    }
    else {
      $plugin = new $plugin_class($configuration, $plugin_id, $plugin_definition, $this->userData, $this->encryptionProfileManager, $this->encryptService);
    }
    return $plugin;
  }

  /**
   * Returns an array of enabled login plugins.
   *
   * @param array $configuration
   *   The configuration array.
   *
   * @return array|null
   *   An array of login plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getPlugins(array $configuration = []) {
    $plugin_ids = $this->tfaSettings->get('login_plugins');
    $plugins = [];
    if (!empty($plugin_ids)) {
      foreach ($plugin_ids as $plugin_id) {
        $plugins[$plugin_id] = $this->createInstance($plugin_id, $configuration);
      }
      return $plugins;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * Provide some backwards compatibility with the old implicit setupPluginId.
   * This will give other modules more time to update their plugins.
   *
   * @deprecated in tfa:8.x-1.0-alpha7 and is removed from tfa:8.x-2.0. Please
   * specify the setupPluginId property in the plugin annotation.
   * @see https://www.drupal.org/project/tfa/issues/2925066
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    foreach ($definitions as &$definition) {
      if (empty($definition['setupPluginId'])) {
        $definition['setupPluginId'] = $definition['id'] . '_setup';
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   *
   * Provide some backwards compatibility with the old implicit setupPluginId.
   * This will give other modules more time to update their plugins.
   *
   * @deprecated in tfa:8.x-1.0-alpha7 and is removed from tfa:8.x-2.0. Please
   * specify the setupPluginId property in the plugin annotation.
   * @see https://www.drupal.org/project/tfa/issues/2925066
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    $plugin = parent::getDefinition($plugin_id, $exception_on_invalid);
    if (is_array($plugin) && empty($plugin['setupPluginId'])) {
      $plugin['setupPluginId'] = $plugin_id . '_setup';
    }
    return $plugin;
  }

}
