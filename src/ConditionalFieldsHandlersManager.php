<?php

namespace Drupal\conditional_fields;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Component\Plugin\Discovery\StaticDiscovery;
use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Component\Plugin\Factory\ReflectionFactory;



/**
 * Manages discovery and instantiation of handler plugins.
 */
class ConditionalFieldsHandlersManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

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

    $this->discovery = new StaticDiscovery();
    $this->discovery = new DerivativeDiscoveryDecorator($this->discovery);

    $this->discovery->setDefinition('states_handler_string_textfield', [
      'id' => 'states_handler_string_textfield',
      'label' => t('String textfield'),
      'class' => 'Drupal\conditional_fields\Plugin\conditional_fields\handler\TextDefault',
    ]);

    $this->discovery->setDefinition('states_handler_string_textarea', [
      'id' => 'states_handler_string_textarea',
      'label' => t('String textarea'),
      'class' => 'Drupal\conditional_fields\Plugin\conditional_fields\handler\TextDefault',
    ]);
    $this->discovery->setDefinition('states_handler_text_textfield', [
      'id' => 'states_handler_text_textfield',
      'label' => t('String textfield'),
      'class' => 'Drupal\conditional_fields\Plugin\conditional_fields\handler\TextDefault',
    ]);

    $this->discovery->setDefinition('states_handler_text_textarea', [
      'id' => 'states_handler_text_textarea',
      'label' => t('String textarea'),
      'class' => 'Drupal\conditional_fields\Plugin\conditional_fields\handler\TextDefault',
    ]);
    $this->factory = new ReflectionFactory($this->discovery);

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
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = array()) {
    return 'states_handler_default_state';
  }

}
