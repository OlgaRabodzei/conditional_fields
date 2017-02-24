<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\HandlerBase;

/**
 * Provides states handler for text fields.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_text",
 * )
 */
class Text extends HandlerBase {

  protected $handler_conditions = [
    '#type' => 'textfield',
  ];

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options, &$state) {
    // Text fields values are keyed by cardinality, so we have to flatten them.
    // TODO: support multiple values.
    if ($options['values_set'] == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET) {
      // Cast as array to handle the exception of autocomplete text fields.
      $_info = $field_info['array_parents'][0];
      $value = (array) $options[$_info];
      $state[$options['state']][$options['selector']] = array_shift($value);
    }
  }

}
