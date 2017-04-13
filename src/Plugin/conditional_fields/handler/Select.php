<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;

/**
 * Provides states handler for select list.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_select",
 * )
 */
class Select extends ConditionalFieldsHandlerBase {

  /**
   * Define field settings to apply proper plugin.
   *
   * @var array
   */
  protected $handler_conditions = [
    '#type' => 'select',
    '#multiple' => FALSE,
  ];

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options, &$state) {
    switch ($options['values_set']) {
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        if (count($options['value_form']) == 1) {
          $state[$options['state']][$options['selector']] = array('value' => $options['value_form'][0]['value']);
        }
        return;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        // This input mode is not available for single select.
        return;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
        $state = [];
        $state[$options['state']][] = 'xor';
        break;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
        $regex = TRUE;
        break;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT:
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
        $state = [];
        break;
    }

    $values = explode("\r\n", $options['values']);
    foreach ($values as $value) {
      $state[$options['state']][] = [
        $options['selector'] => [
          $options['condition'] => empty($regex) ? $value : ['regex' => $options['regex']],
        ],
      ];
    }
  }

}
