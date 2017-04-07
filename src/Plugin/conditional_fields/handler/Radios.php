<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;

/**
 * Provides states handler for radios.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_radios",
 * )
 */
class Radios extends ConditionalFieldsHandlerBase {

  protected $handler_conditions = [
    // Temporary solution.
    // Will be fixed during refactoring conditions on widget type.
    '#type' => 'radio',
  ];

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $select_states = [];
    $values_array = explode("\r\n", $options['values']);
    $state = [];
    switch ($options['values_set']) {
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        if (empty($options['value_form'][0]['value'])) {
          break;
        }
        $select_states[$options['selector']] = [$options['condition'] => $options['value_form'][0]['value']];
        $state = [$options['state'] => $select_states];
        break;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        if (is_array($values_array)) {
          // Will take the first value
          // because there is no possibility to choose more with radio buttons.
          $select_states[$options['selector']] = [$options['condition'] => $values_array[0]];
        }
        else {
          $select_states[$options['selector']] = [$options['condition'] => $values_array];
        }
        $state = [$options['state'] => $select_states];
        break;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
        // This just works.
        break;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
        $select_states[$options['state']][] = 'xor';
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT:
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
        if (is_array($values_array)) {
          foreach ($values_array as $value) {
            $select_states[$options['selector']][] = [
              $options['condition'] => $value,
            ];
          }
        }
        else {
          $select_states[$options['selector']] = [
            $options['condition'] => $values_array,
          ];
        }

        $state = [$options['state'] => $select_states];
        break;
    }
    return $state;
  }

}
