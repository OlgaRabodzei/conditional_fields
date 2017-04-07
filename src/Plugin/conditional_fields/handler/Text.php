<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;

/**
 * Provides states handler for text fields.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_text",
 * )
 */
class Text extends ConditionalFieldsHandlerBase {

  protected $handler_conditions = [
    '#type' => 'textfield',
  ];

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];
    // Text fields values are keyed by cardinality, so we have to flatten them.
    // TODO: support multiple values.
    if ($options['values_set'] == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET && !empty($options['value_form'][0]['value'])) {
      // TODO: Support autocommit.
      $value = $options['value_form'][0]['value'];
      $state[$options['state']][$options['selector']] = ['value' => $value];
    }
    return $state;
  }

}
