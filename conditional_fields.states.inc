<?php
/**
 * @file
 * Special fields handlers to be executed when building the #states array.
 */

/**
 * States handler for multiple select lists.
 *
 * Multiple select fields always require an array as value.
 * In addition, since our modified States API triggers a dependency only if all
 * reference values of type Array are selected, a different selector must be
 * added for each value of a set for OR, XOR and NOT evaluations.
 */
function conditional_fields_states_handler_select_multiple($field, $field_info, $options, &$state) {
  switch ($options['values_set']) {
    case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
    case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
      $state[$options['state']][$options['selector']]['value'] = (array) $state[$options['state']][$options['selector']]['value'];
      return;

    case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
      $select_states[$options['state']][] = 'xor';

    case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
      $regex = TRUE;
    case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT:
    case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
      foreach ($options['values'] as $value) {
        $select_states[$options['state']][] = [
          $options['selector'] => [
            $options['condition'] => empty($regex) ? [$value] : $options['value'],
          ],
        ];
      }
      break;
  }

  $state = $select_states;
}

/**
 * States handler for single on/off checkbox.
 */
function conditional_fields_states_handler_checkbox($field, $field_info, $options, &$state) {
  switch ($options['values_set']) {
    case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
      $checked = $options['value'][0]['value'] == $field['#on_value'] ? TRUE : FALSE;
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
}

/**
 * States handler for checkboxes.
 */
function conditional_fields_states_handler_checkboxes($field, $field_info, $options, &$state) {
  // Checkboxes are actually different form fields, so the #states property
  // has to include a state for each checkbox.
  $checkboxes_selectors = [];

  switch ($options['values_set']) {
    case CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
      foreach ($options['value'] as $value) {
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
      foreach ($options['values'] as $value) {
        $checkboxes_selectors[conditional_fields_field_selector($field[$value])] = ['checked' => TRUE];
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
}

/**
 * States handler for text fields.
 */
function conditional_fields_states_handler_text($field, $field_info, $options, &$state) {
  // Text fields values are keyed by cardinality, so we have to flatten them.
  // TODO: support multiple values.
  if ($options['values_set'] == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET) {
    // Cast as array to handle the exception of autocomplete text fields.
    $_info = $field_info['array_parents'][0];
    $value = (array) $options[$_info];
    $state[$options['state']][$options['selector']] = array_shift($value);
  }
}

/**
 * States handler for text areas.
 */
function conditional_fields_states_handler_textarea($field, $field_info, $options, &$state) {
  conditional_fields_states_handler_text($field, $field_info, $options, $state);
}

/**
 * States handler for date combos.
 */
function conditional_fields_states_handler_date_combo($field, $field_info, $options, &$state) {
  // Date text.
  if ($field_info['instance']['widget']['type'] == 'date_text') {
    if ($options['values_set'] == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET) {
      $state[$options['state']][$options['selector']]['value'] = $state[$options['state']][$options['selector']]['value'][0]['value']['date'];
    }
    return;
  }

  // Add a condition for each date part.
  $date_selectors = [];

  $regex = $options['values_set'] == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX;

  // Date popup.
  if ($field_info['instance']['widget']['type'] == 'date_popup') {
    $date_selectors[conditional_fields_field_selector($field['value']['date'])] = [
      'value' => $regex ? $options['value'] : $options['value_form'][0]['value']['date'],
    ];

    if ($field_info['field']['settings']['granularity']['hour'] || $field_info['field']['settings']['granularity']['minute'] || $field_info['field']['settings']['granularity']['second']) {
      $date_selectors[conditional_fields_field_selector($field['value']['time'])] = [
        'value' => $regex ? $options['value'] : $options['value_form'][0]['value']['time'],
      ];
    }
  }
  // Date select.
  else {
    foreach ($field_info['field']['settings']['granularity'] as $date_part) {
      if ($date_part) {
        $date_selectors[conditional_fields_field_selector($field['value'][$date_part])] = [
          'value' => $regex ? $options['value'] : $options['value_form'][0]['value'][$date_part],
        ];
      }
    }
  }

  $state = [$options['state'] => $date_selectors];
}

/**
 * States handler for links provided by the Link module.
 */
function conditional_fields_states_handler_link_field($field, $field_info, $options, &$state) {
  $link_selectors = [];
  $regex = $options['values_set'] == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX;

  // Add a condition for each link part (Title and URL)
  if ($field_info['instance']['settings']['title'] == 'optional' || $field_info['instance']['settings']['title'] == 'required') {
    $link_selectors[conditional_fields_field_selector($field['title'])] = ['value' => $regex ? $options['value'] : $options['value_form'][0]['title']];
  }
  $link_selectors[conditional_fields_field_selector($field['url'])] = ['value' => $regex ? $options['value'] : $options['value_form'][0]['url']];

  $state = [$options['state'] => $link_selectors];
}

/**
 * States handler for links provided by the Addressfield module.
 */
function conditional_fields_states_handler_link_addressfield($field, $field_info, $options, &$state) {
  if ($options['values_set'] != CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET) {
    return;
  }

  $regex = $options['values_set'] == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX;
  $keys = [];

  if ($field['#handlers']['address']) {
    $keys += [
      'country',
      'thoroughfare',
      'premise',
      'postal_code',
      'locality',
      'administrative_area',
    ];
  }

  if ($field['#handlers']['organisation']) {
    $keys += ['organisation_name'];
  }

  if ($field['#handlers']['name-oneline']) {
    $keys += ['name_line'];
  }
  elseif ($field['#handlers']['name-full']) {
    $keys += ['first_name', 'last_name'];
  }

  $addressfield_selectors = [];

  foreach ($keys as $key) {
    $addressfield_selectors[str_replace('%key', $key, $options['selector'])] = ['value' => $regex ? $options['value'] : $options['value'][0][$key]];
  }

  $state = [$options['state'] => $addressfield_selectors];
}

/**
 * Builds a jQuery selector from the name or id attribute of a field.
 *
 * @todo support custom selectors with %lang and %key placeholders.
 *
 * @param $field
 *   The field form element.
 *
 * @return
 *   A jQuery selector string.
 */
function conditional_fields_field_selector($field) {
  if (isset($field['#attributes']['name'])) {
    return '[name="' . $field['#attributes']['name'] . '"]';
  }

  if (isset($field['#name'])) {
    return '[name="' . $field['#name'] . '"]';
  }

  // Try with id if name is not found.
  if (isset($field['#attributes']['id'])) {
    return '#' . $field['#attributes']['id'];
  }

  if (isset($field['#id'])) {
    return '#' . $field['#id'];
  }

  return FALSE;
}