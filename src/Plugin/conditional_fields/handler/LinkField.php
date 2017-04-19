<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;

/**
 * Provides states handler for links provided by the Link module.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_link_default",
 * )
 */
class LinkField extends ConditionalFieldsHandlerBase {

  protected $handler_conditions = [
    '#type' => 'link_field',
  ];

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];

    switch ($options['values_set']) {
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        // Add a condition for title of URL.
        if (!empty($options['value_form'][0]['title'])) {
          // Prepare selector for title of link.
          $title_selector = str_replace('uri', 'title', $options['selector']);
          $state[$options['state']][$title_selector] = [
            'value' => $options['value_form'][0]['title']
          ];
        }

        $uri = $this->getLinkTypeFromUri($options['value_form'][0]['uri']);
        $state[$options['state']][$options['selector']] = [
          $options['condition'] => $uri,
        ];
        break;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
        // Works, there are not implementation here.
        break;


      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        // @todo: Send field settings to statesHandler to check field cardinality.
        break;

    }

    return $state;
  }

  /**
   * Get value for internal and entity links.
   *
   * For example:
   *  - internal:/node/1 returns /node/1
   *  - entity:node/1 returns node/1
   *
   * @todo: It would be better return a node title instead of the path, is not it?
   */
  private function getLinkTypeFromUri($uri) {
    $parts = explode(':', $uri);

    return (count($parts) > 1) ? $parts[1] : $parts[0];
  }

}
