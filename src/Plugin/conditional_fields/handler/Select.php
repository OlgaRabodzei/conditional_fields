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
  public function statesHandler($field, $field_info, $options) {
    $state = [];

    switch ($options['values_set']) {
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        if (count($options['value_form']) == 1 && ($key = key($options['value_form'][0]))) {
          // Get value depending on field type.
          switch ($key) {
            // Field type 'options_select'.
            case 'value':
              $state[$options['state']][$options['selector']] = [
                'value' => $options['value_form'][0]['value']
              ];
              break;

            // Field type 'entity_reference'.
            case 'target_id':
              $state[$options['state']][$options['selector']] = [
                'value' => $options['value_form'][0]['target_id']
              ];
              break;
          }
        }
        break;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        // This input mode is not available for single select.
        break;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
        // Works, there are no implementation here.
        break;

      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
        $state[$options['state']][] = 'xor';
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT:
      case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
        $values = $options['values'];
        if (!is_array($values)) {
          $values = explode("\r\n", $options['values']);
        }
        foreach ($values as $value) {
          $state[$options['state']][] = [
            $options['selector'] => [
              $options['condition'] => $value,
            ],
          ];
        }
        break;
    }

    return $state;
  }

}
