<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;

/**
 * Provides states handler for single on/off checkbox.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_checkbox",
 * )
 */
class Checkbox extends ConditionalFieldsHandlerBase {

  protected $handler_conditions = [
    '#type' => 'checkbox',
  ];

  /**
   * {@inheritdoc}
   *
   * @TODO: Different handlers for boolean and list fields.
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];

    switch ($options['values_set']) {
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        $value = !empty($options['value_form'][0]) ? $options['value_form'][0] : $options['value_form'];
        $checked = $field['#return_value'] == $value['value'];
        break;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
        $checked = preg_match('/' . $options['value']['RegExp'] . '/', $field['#on_value']) ? TRUE : FALSE;
        break;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        // ANDing values of a single checkbox doesn't make sense:
        // just use the first value.
        $checked = $options['values'][0] == $field['#on_value'] ? TRUE : FALSE;
        break;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT:
        $checked = in_array($field['#on_value'], $options['values']) ? TRUE : FALSE;
        break;
    }

    $state[$options['state']][$options['selector']] = array('checked' => $checked);

    return $state;
  }

}
