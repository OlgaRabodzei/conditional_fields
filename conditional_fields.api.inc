<?php
/**
 * @file
 * Contains conditional fields logic of modify forms.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Loads all dependencies from the database.
 *
 * The result can be filtered by providing an entity type and a bundle name.
 */
function conditional_fields_load_dependencies_entity($entity_type = NULL, $bundle = NULL) {
  $dependencies = \Drupal::entityTypeManager()
    ->getStorage('conditional_field')
    ->loadByProperties([
      'entity_type' => $entity_type,
      'bundle' => $bundle,
    ]);
  return $dependencies;
}

/**
 * Builds a list of special fields handlers to be executed when building the
 * #states array. The keys are handler function names and the key/value pairs
 * are field properties and their values that trigger the execution
 * of the handler.
 *
 * The handlers themselves must accept the parameters $field, $field_info,
 * $options and $state.
 */
function conditional_fields_states_handlers() {
  module_load_include('inc', 'conditional_fields', 'conditional_fields.states');

  $handlers = [
    'conditional_fields_states_handler_select_multiple' => [
      '#type' => 'select',
      '#multiple' => TRUE,
    ],
    'conditional_fields_states_handler_checkbox' => [
      '#type' => 'checkbox',
    ],
    'conditional_fields_states_handler_checkboxes' => [
      '#type' => 'checkboxes',
    ],
    'conditional_fields_states_handler_text' => [
      '#type' => 'textfield',
    ],
    'conditional_fields_states_handler_textarea' => [
      '#type' => 'textarea',
    ],
    'conditional_fields_states_handler_date_combo' => [
      '#type' => 'date_combo',
    ],
    'conditional_fields_states_handler_link_field' => [
      '#type' => 'link_field',
    ],
    'conditional_fields_states_handler_link_addressfield' => [
      '#addressfield' => 1,
    ],
  ];

  // Allow other modules to modify the handlers.
  \Drupal::moduleHandler()
    ->alter('conditional_fields_states_handlers', $handlers);

  return $handlers;
}

/**
 * after_build callback for forms with dependencies.
 *
 * Builds and attaches #states properties to dependent fields, adds additional
 * visual effects handling to the States API and attaches a validation callback
 * to the form that handles validation of dependent fields.
 */
function conditional_fields_form_after_build($form, FormStateInterface &$form_state) {
  // TODO: 1) Add #conditional_fields to form.
  // TODO: 2) Check this func for attaching states.

  // Dependencies data is attached in conditional_fields_element_after_build().
  if (empty($form['#conditional_fields'])) {
    return $form;
  }

  $effects = [];
  $state_handlers = conditional_fields_states_handlers();

  // Cycle all dependents.
  foreach ($form['#conditional_fields'] as $dependent => $dependent_info) {
    $states = array();

    if (empty($dependent_info['dependees'])) {
      continue;
    }

//    $dependent_location = array_merge($dependent_info['field_parents'], array($dependent));
    $dependent_location = array_merge([], array($dependent));
    $dependent_form_field = NestedArray::getValue($form, $dependent_location);

    // Cycle the dependant's dependees.
    foreach ($dependent_info['dependees'] as $dependency) {
      $dependee = $dependency['dependee'];

      if (empty($form['#conditional_fields'][$dependee])) {
        continue;
      }

      $dependee_info = $form['#conditional_fields'][$dependee];
      $dependee_form_field = NestedArray::getValue($form, $dependee_info['parents']);
      $options = $dependency['options'];

      // Load field edit behaviors.
      // If this dependent has multiple dependees, only the logic of the first
      // dependency will be taken into account.
      if (!isset($behaviors)) {
        $behaviors = conditional_fields_field_behaviors('edit', $options);
      }

      // Determine if the dependee is in the form.
      if (empty($dependee_form_field) || (isset($dependee_form_field['#access']) && $dependee_form_field['#access'] == FALSE)) {
        // Apply orphan dependent behaviors.
        /*
        if (in_array(CONDITIONAL_FIELDS_FIELD_EDIT_HIDE_UNTRIGGERED_ORPHAN, $behaviors)) {
          // TODO
          $is_triggered = TRUE;

          if ($is_orphan && !$is_triggered) {
            $form[$dependent]['#access'] = FALSE;
          }
        }
        */
        if (in_array(CONDITIONAL_FIELDS_FIELD_EDIT_HIDE_ORPHAN, $behaviors)) {
          $dependent_form_field['#access'] = FALSE;
        }
        unset($behaviors[CONDITIONAL_FIELDS_FIELD_EDIT_HIDE_UNTRIGGERED_ORPHAN]);
        unset($behaviors[CONDITIONAL_FIELDS_FIELD_EDIT_HIDE_ORPHAN]);
        unset($behaviors[CONDITIONAL_FIELDS_FIELD_EDIT_RESET_UNTRIGGERED]);
        continue;
      }

      unset($behaviors[CONDITIONAL_FIELDS_FIELD_EDIT_HIDE_UNTRIGGERED_ORPHAN]);
      unset($behaviors[CONDITIONAL_FIELDS_FIELD_EDIT_HIDE_ORPHAN]);

      // Build a jQuery selector if it was not overridden by a custom value.
      // Note that this may be overridden later by a state handler.
      if (!$options['selector']) {
        $options['selector'] = conditional_fields_field_selector($dependee_form_field);
      }
      else {
        // Replace the language placeholder in the selector with current language.
        $options['selector'] = str_replace('%lang', $dependee_form_field['#language'], $options['selector']);
      }

      if ($options['condition'] != 'value') {
        // Conditions different than "value" are always evaluated against TRUE.
        $state = array($options['state'] => array($options['selector'] => array($options['condition'] => TRUE)));
      }
      else {
        // Build the values that trigger the dependency.
        $values = array();

        if ($options['values_set'] == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET) {
          $values[$options['condition']] = $options['value_form'];
        }
        elseif ($options['values_set'] == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX) {
          $values[$options['condition']] = $options['value'];
        }
        elseif ($options['values_set'] == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND) {
          $values[$options['condition']] = count($options['values']) == 1 ? $options['values'][0] : $options['values'];
        }
        else {
          if ($options['values_set'] == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR) {
            // XOR behaves like OR with added 'xor' element.
            $values[] = 'xor';
          }
          elseif ($options['values_set'] == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT) {
            // NOT behaves like OR with switched state.
            $options['state'] = strpos($options['state'], '!') === 0 ? drupal_substr($options['state'], 1) : '!' . $options['state'];
          }

          // OR, NOT and XOR conditions are obtained with a nested array.
          foreach ($options['values'] as $value) {
            $values[] = array($options['condition'] => $value);
          }
        }

        $state = array($options['state'] => array($options['selector'] => $values));
        $dependee_form_state = isset($dependee_form_field['#field_parents'], $dependee_form_field['#field_name'], $dependee_form_field['#language']) ? field_form_get_state($dependee_form_field['#field_parents'], $dependee_form_field['#field_name'], $dependee_form_field['#language'], $form_state) : NULL;

        // Execute special handler for fields that need further processing.
        // The handler has no return value. Modify the $state parameter by
        // reference if needed.
        foreach ($state_handlers as $handler => $handler_conditions) {
          if (array_intersect_assoc($handler_conditions, $dependee_form_field) == $handler_conditions) {
            $handler($dependee_form_field, $dependee_form_state, $options, $state);
          }
        }
      }

      // Add validation callback to element if the dependency can be evaluated.
      if (in_array($options['condition'], array('value', 'empty', '!empty'))) {
        _conditional_fields_element_add_property($dependent_form_field, '#element_validate', 'conditional_fields_dependent_validate', 'append');
      }

      // Add the $state into the correct logic group in $states.
      foreach ($state as $key => $constraints) {
        foreach ($constraints as $selector => $constraint) {
          // Add the constraint in an array to avoid overwriting other
          // dependencies' states with the same selector.
          $states[$key][$options['grouping']][$selector][] = $constraint;
        }
      }

      // Build effect settings for effects with options.
      // TODO: add dependee key to allow different effects on the same selector.
      if ($options['effect'] && $options['effect'] != 'show') {
        $selector = conditional_fields_field_selector(NestedArray::getValue($form, array($dependent_location[0])));
        // Convert numeric strings to numbers.
        foreach ($options['effect_options'] as &$effect_option) {
          if (is_numeric($effect_option)) {
            $effect_option += 0;
          }
        }
        $effects[$selector] = array(
          'effect' => $options['effect'],
          'options' => $options['effect_options'],
        );
      }

      // Apply reset dependent to default if untriggered behavior.
      if (in_array(CONDITIONAL_FIELDS_FIELD_EDIT_RESET_UNTRIGGERED, $behaviors)) {
        // Add property to element so conditional_fields_dependent_validate()
        // can pick it up.
        $dependent_form_field['#conditional_fields_reset_if_untriggered'] = TRUE;
        unset($behaviors[CONDITIONAL_FIELDS_FIELD_EDIT_RESET_UNTRIGGERED]);
      }
    }

    // Execute custom behaviors callbacks.
    if (!empty($behaviors)) {
      foreach ($behaviors as $behavior) {
        $behavior($form, $form_state, $dependent, $dependent_info);
      }
    }

    unset($behaviors);

    if (empty($states)) {
      continue;
    }

    // Save the modified field back into the form.
    NestedArray::setValue($form, $dependent_location, $dependent_form_field);

    // Map the states based on the conjunctions.
    $states_new = array();
    foreach ($states as $state_key => $value) {
      // As the main object is ANDed together we can add the AND items directly.
      if (!empty($states[$state_key]['AND'])) {
        $states_new[$state_key] = $states[$state_key]['AND'];
      }
      // The OR and XOR groups are moved into a sub-array that has numeric keys
      // so that we get a JSON array and not an object, as required by the States
      // API for OR and XOR groupings.
      if (!empty($states[$state_key]['OR'])) {
        $or = array();
        foreach ($states[$state_key]['OR'] as $constraint_key => $constraint_value) {
          $or[] = array($constraint_key => $constraint_value);
        }
        // '1' as a string so that we get an object (which means logic groups
        // are ANDed together).
        $states_new[$state_key]['1'] = $or;
      }
      if (!empty($states[$state_key]['XOR'])) {
        $xor = array('xor');
        foreach ($states[$state_key]['XOR'] as $constraint_key => $constraint_value) {
          $xor[] = array($constraint_key => $constraint_value);
        }
        // '2' as a string so that we get an object.
        $states_new[$state_key]['2'] = $xor;
      }
    }
    $states = $states_new;

    // Add the #states property to the dependent field.
    NestedArray::setValue($form, array_merge($dependent_location, array('#states')), $states);

    $has_states = TRUE;
  }

  if (empty($has_states)) {
    return $form;
  }

  $form['#attached']['library'][] = 'conditional_fields/conditional_fields';

  // Add effect settings to the form.
  if ($effects) {
    $form['#attached']['js'][] = array(
      'data' => array(
        'conditionalFields' => array(
          'effects' => $effects,
        ),
      ),
      'type' => 'setting',
    );
  }

  // Validation callback to manage dependent fields validation.
  $form['#validate'][] = 'conditional_fields_form_validate';
  // Initialize validation information every time the form is rendered to avoid
  // stale data after a failed submission.
  $form_state->setValue('conditional_fields_untriggered_dependents', []);

  return $form;
}
