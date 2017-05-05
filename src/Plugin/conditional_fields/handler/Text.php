<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;

/**
 * Provides states handler for text fields.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_string_textfield",
 * )
 */
class Text extends ConditionalFieldsHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];
    // Text fields values are keyed by cardinality, so we have to flatten them.
    // TODO: support multiple values.
    if ($options['values_set'] == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET) {
      $widget_value = $this->getWidgetValue($options['value_form']);
      // TODO: Support autocommit.
      $state[$options['state']][$options['selector']] = ['value' => $widget_value];
    }
    return $state;
  }

}
