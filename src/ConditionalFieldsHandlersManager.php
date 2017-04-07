<?php

namespace Drupal\conditional_fields;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of handler plugins.
 */
class ConditionalFieldsHandlersManager extends DefaultPluginManager {

  /**
   * Constructs a new ConditionalFieldsHandlersManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/conditional_fields/handler', $namespaces, $module_handler, 'Drupal\conditional_fields\ConditionalFieldsHandlersPluginInterface', 'Drupal\conditional_fields\Annotation\ConditionalFieldsHandler');

    $this->alterInfo('handler_info');
    $this->setCacheBackend($cache_backend, 'handler_plugins');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    // Remove the default plugin from the array.
    $definitions = parent::getDefinitions();
    unset($definitions['states_handler_default_state']);
    return $definitions;
  }

  /**
   * A default plugin that should be used if better one was not found.
   *
   * @return object
   *   A fully configured plugin instance.
   */
  public function getDefaultPlugin() {
    return $this->createInstance('states_handler_default_state');
  }

}
