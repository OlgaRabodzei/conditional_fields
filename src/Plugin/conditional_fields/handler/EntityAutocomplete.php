<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;

/**
 * Provides states handler for links with type entity_autocomplete.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_entity_autocomplete",
 * )
 */
class EntityAutocomplete extends ConditionalFieldsHandlerBase {

  protected $handler_conditions = [
    '#type' => 'entity_autocomplete'
  ];

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];

    switch ($options['values_set']) {
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        if (isset($options['value_form'][0]['target_id'])) {
          $state[$options['state']][$options['selector']] = [
            'value' => $options['value_form'][0]['target_id'],
          ];
          break;
        }

        // Get path for allowed link types 'Internal links only' and
        // 'Both internal and external links'.
        $uri = $this->getLinkTypeFromUri($options['value_form'][0]['uri']);

        $title = $options['value_form'][0]['title'];

        // Prepare selector for title of link.
        $title_selector = str_replace('uri', 'title', $options['selector']);

        $state[$options['state']][$options['selector']] = [
          'value' => $uri
        ];
        $state[$options['state']][$title_selector] = [
          'value' => $title
        ];
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
