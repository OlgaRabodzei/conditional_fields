<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;

/**
 * Provides states handler for checkboxes.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_checkboxes",
 * )
 */
class Checkboxes extends ConditionalFieldsHandlerBase {

  protected $handler_conditions = [
    '#type' => 'checkboxes',
  ];

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    // Checkboxes are actually different form fields, so the #states property
    // has to include a state for each checkbox.
    $checkboxes_selectors = [];

    switch ($options['values_set']) {
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        foreach ($options['value_form'] as $value) {
          $checkboxes_selectors[conditional_fields_field_selector($field[current($value)])] = ['checked' => TRUE];
        }
        break;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
        // We interpret this as: checkboxes whose values match the regular
        // expression should be checked.
        foreach ($field['#options'] as $key => $label) {
          if (preg_match('/' . $options['value']['RegExp'] . '/', $key)) {
            $checkboxes_selectors[conditional_fields_field_selector($field[$key])] = ['checked' => TRUE];
          }
        }
        break;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        $values_array = explode("\r\n", $options['values']);
        if (is_array($values_array)) {
          foreach ($values_array as $value) {
            $checkboxes_selectors[conditional_fields_field_selector($field[$value])] = ['checked' => TRUE];
          }
        }
        else {
          $checkboxes_selectors[conditional_fields_field_selector($field[$options['values']])] = ['checked' => TRUE];
        }
        break;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
        $checkboxes_selectors[] = 'xor';
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT:
        $values_array = explode("\r\n", $options['values']);
        foreach ($values_array as $value) {
          $checkboxes_selectors[] = [conditional_fields_field_selector($field[$value]) => ['checked' => TRUE]];
        }
        break;
    }

    $state = [$options['state'] => $checkboxes_selectors];

    return $state;
  }

}
