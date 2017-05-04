<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;

/**
 * Provides states handler for date combos.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_datetime_default",
 * )
 */
class DateDefault extends ConditionalFieldsHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];

    // Date text.
    if ($field_info['instance']['widget']['type'] == 'date_text') {
      if ($options['values_set'] == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET) {
        $state[$options['state']][$options['selector']]['value'] = $state[$options['state']][$options['selector']]['value'][0]['value']['date'];
      }
      return $state;
    }

    // Add a condition for each date part.
    $date_selectors = [];

    $regex = $options['values_set'] == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX;

    $widget_values = $this->getWidgetValue($options['value_form']);

    // Date popup.
    if ($field_info['instance']['widget']['type'] == 'date_popup') {
      $date_selectors[conditional_fields_field_selector($field['value']['date'])] = [
        'value' => $regex ? $options['value'] : $widget_values['date'],
      ];

      if ($field_info['field']['settings']['granularity']['hour'] || $field_info['field']['settings']['granularity']['minute'] || $field_info['field']['settings']['granularity']['second']) {
        $date_selectors[conditional_fields_field_selector($field['value']['time'])] = [
          'value' => $regex ? $options['value'] : $widget_values['time'],
        ];
      }
    }
    // Date select.
    else {
      foreach ($field_info['field']['settings']['granularity'] as $date_part) {
        if ($date_part) {
          $date_selectors[conditional_fields_field_selector($field['value'][$date_part])] = [
            'value' => $regex ? $options['value'] : $widget_values[$date_part],
          ];
        }
      }
    }

    $state = [$options['state'] => $date_selectors];

    return $state;
  }

}
