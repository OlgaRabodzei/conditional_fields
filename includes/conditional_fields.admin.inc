<?php
/**
 * @file
 * Administration of dependencies.
 */

/**
 * Dependencies administration page.
 *
 * Builds a table for each entity type, grouped by bundle.
 */
function conditional_fields_admin_settings() {
  $bundles = field_info_bundles();
  $instances = field_info_instances();
  $entities_names = conditional_fields_entities_names();
  $dependencies = conditional_fields_load_dependencies();
  $output = '';
  $header = array(t('Dependee'), t('Dependent'), t('Description'), array('data' => t('Operations'), 'colspan' => 2));

  foreach ($bundles as $entity_type => $bundle) {
    $entity_name = (isset($entities_names[$entity_type])) ? $entities_names[$entity_type] : $entity_type;
    $collapsed = TRUE;
    $items = array();

    foreach ($bundle as $bundle_name => $bundle_info) {
      $item = '<h4 class="conditional-fields-bundles-list clearfix">' . $bundles[$entity_type][$bundle_name]['label'] . '</h4>';

      if (count($instances[$entity_type][$bundle_name]) < 2) {
        $items[] = $item . '<p>' . t('Add at least two fields to enable dependencies') . '</p>';
        continue;
      }

      $item .= '<p>' . l(t('Add dependency'), 'admin/structure/dependencies/add/' . $entity_type . '/' . $bundle_name) . '</p>';

      if (isset($dependencies[$entity_type][$bundle_name])) {
        $collapsed = FALSE;
        $rows = array();

        foreach ($dependencies[$entity_type][$bundle_name] as $dependent_name => $field_dependencies) {
          if (!isset($field_dependencies['dependees'])) {
            continue;
          }

          foreach ($field_dependencies['dependees'] as $dependee_name => $options) {
            $description = conditional_fields_dependency_description($dependee_name, $dependent_name, $options['options']);
            $edit = l(t('edit'), 'admin/structure/dependencies/edit/' . $options['id']);
            $delete = l(t('delete'), 'admin/structure/dependencies/delete/' . $options['id']);
            $rows[] = array('data' => array($dependee_name, $dependent_name, $description, $edit, $delete));
          }
        }

        ksort($rows);
        $item .= theme('table', array('header' => $header, 'rows' => $rows));
      }

      $items[] = $item;
    }

    $entity_fieldset = array(
      '#theme' => 'fieldset',
      '#title' => $entity_name,
      '#collapsible' => TRUE,
      '#collapsed' => $collapsed,
      '#attached' => array(
        'js' => array(
          'misc/form.js',
          'misc/collapse.js',
        ),
      ),
      '#attributes' => array(
        'class' => array('collapsible'),
      ),
      '#children' => theme('item_list', array('items' => $items, 'type' => 'ul')),
    );

    if ($collapsed) {
      $entity_fieldset['#attributes']['class'][] = 'collapsed';
    }

    $output .= drupal_render($entity_fieldset);
  }

  return $output;
}

/**
 * Dependency add form with selection of dependee and dependent fields.
 *
 * @param $entity_type
 *   The entity type where fields are instantiated.
 * @param $bundle_name
 *   The bundle where fields are instantiated.
 *
 * @see conditional_fields_dependency_add_form_validate()
 * @see conditional_fields_dependency_add_form_submit()
 * @ingroup forms
 */
function conditional_fields_dependency_add_form($form, &$form_state, $entity_type, $bundle_name) {
  $instances = field_info_instances($entity_type, $bundle_name);

  $options = array();

  foreach ($instances as $field) {
    if ($field['deleted']) {
      continue;
    }

    $options[$field['id']] = $field['label'];
  }

  $form['dependee'] = array(
    '#type' => 'select',
    '#title' => t('Dependee'),
    '#description' => t('Select a dependee field.'),
    '#options' => $options,
  );

  $form['dependent'] = array(
    '#type' => 'select',
    '#title' => t('Dependent'),
    '#description' => t('Select a dependent field.'),
    '#options' => $options,
  );

  $form['actions'] = array(
    '#type' => 'action',
    'create' => array(
      '#type' => 'submit',
      '#value' => t('Create'),
    ),
  );

  $form['#entity_type'] = array(
    '#type' => 'value',
    '#value' => $entity_type,
  );

  $form['#bundle_name'] = array(
    '#type' => 'value',
    '#value' => $bundle_name,
  );

  return $form;
}

/**
 * Dependency add form validate.
 *
 * @see conditional_fields_dependency_add_form()
 * @see conditional_fields_dependency_add_form_submit()
 */
function conditional_fields_dependency_add_form_validate($form, &$form_state) {
  if ($form_state['values']['dependee'] == $form_state['values']['dependent']) {
    form_set_error('dependent', t('You should select two different fields.'));
    return;
  }
}

/**
 * Dependency add form submit.
 *
 * @see conditional_fields_dependency_add_form()
 * @see conditional_fields_dependency_add_form_validate()
 */
function conditional_fields_dependency_add_form_submit($form, &$form_state) {
  if (!$id = conditional_fields_dependency_insert($form_state['values']['dependee'], $form_state['values']['dependent'])) {
    drupal_set_message(t('There was an error while trying to create the dependency.'), 'error');
    return;
  }

  drupal_goto('admin/structure/dependencies/edit/' . $id);
}

/**
 * Dependency edit form.
 *
 * @see conditional_fields_dependency_edit_form_submit()
 * @ingroup forms
 */
function conditional_fields_dependency_edit_form($form, &$form_state, $id) {
  if (!is_numeric($id) || !$form['#dependency'] = $dependency = conditional_fields_dependency_load_by_id($id)) {
    drupal_access_denied();
    drupal_exit();
  }

  $form['#attached']['js'] = array(drupal_get_path('module', 'conditional_fields') . '/js/conditional_fields.admin.js');

  // Retrieve needed information from the dependee instance.
  // We do have the bundle and entity type, but we would have to build a form
  // for the entity to obtain the allowed values and the name attribute.
  // Moreover, we only have the instance id here (id column of the
  // field_config_instance table), not the entity id (id column of field_config)
  // so we can't call field_info_field_by_id. This is needed because we don't
  // want dependencies to be shared between bundles.
  // So we first load the instance information, so we can obtain the entity id.
  // Then we load the entity using field_info_field().
  $dependee_instance = array_shift(field_read_instances(array('id' => $dependency['dependee'])));
  $dependee = field_info_field($dependee_instance['field_name']);

  // Build the default jQuery selector
  $dummy_form = array('#parents' => array());
  $dummy_form_state = array();
  $dummy_field = field_default_form($dependee_instance['entity_type'], NULL, $dependee, $dependee_instance, LANGUAGE_NONE, array(), $dummy_form, $dummy_form_state, 0);

  $form['#default_selector'] = conditional_fields_field_selector($dependee['field_name'], $dependee_instance['widget']['type'], $dummy_field[$dependee['field_name']]);

  $form['dependee_fieldset'] = array(
    '#type' => 'fieldset',
    '#title' => t('Dependee field settings'),
    '#description' => t('<p>These settings define the behavior of the dependee field in forms.</p>'),
  );

  $selector_description = t('You need to change this only if the automatically generated selector does not work or for advanced selection. You can use the following placeholders:');

  $selector_description .= "<ul>\n";
  $selector_description .= '<li>' . t('%lang: current language of the field.') . "</li>\n";
  $selector_description .= '<li>' . t('%key: the key corresponding to the value or set of values inserted below (use only with checkboxes).') . "</li>\n";
  $selector_description .= '</ul>';
  $selector_description .= t('Leave the field empty to reset the selector to its default value.');

  $form['dependee_fieldset']['selector'] = array(
    '#type' => 'textfield',
    '#title' => t('jQuery selector'),
    '#description' => $selector_description,
    // If the selector was left empty, switch back to the default selector.
    '#default_value' => empty($dependency['options']['selector']) ? $form['#default_selector'] : $dependency['options']['selector'],
  );

  $form['dependee_fieldset']['selector_custom'] = array(
    '#type' => 'hidden',
    '#default_value' => $dependency['options']['selector_custom'],
  );

  $form['dependee_fieldset']['condition'] = array(
    '#type' => 'radios',
    '#title' => t('Condition'),
    '#description' => t('Select the condition that should be met by the dependee field to activate the dependency.'),
    '#options' => conditional_fields_conditions($dependee_instance['widget']['type'] == 'options_buttons' && $dependee['cardinality'] != 1 ? TRUE : FALSE),
    '#default_value' => $dependency['options']['condition'],
    '#required' => TRUE,
  );

  // In Drupal 7, allowed values are a feature of the List module
  // TODO: include also allowed values from latest CCK (PHP + ?)
  $allowed_values = array();

  if (function_exists($function = $dependee['module'] . '_options_list')) {
    $allowed_values[t('Single value from allowed values')] = $function($dependee);
  }

  $form['dependee_fieldset']['value'] = array(
    '#type' => 'select',
    '#title' => t('Values'),
    '#description' => t('Select the value(s) of the dependee field that will activate the dependency.'),
    '#options' => array_merge(
      array(t('Set of values') => array(
        'conditional_fields_dependency_value_and'    => t('All these values...'),
        'conditional_fields_dependency_value_or'     => t('Any of these values...'),
        'conditional_fields_dependency_value_xor'    => t('Only one of these values...'),
        'conditional_fields_dependency_value_not'    => t('None of these values...'),
        // TODO: PHP evaluation
      )),
      $allowed_values
    ),
    '#default_value' => $dependency['options']['value'],
    '#required' => TRUE,
    '#states' => array(
      'visible' => array(
        ':input[name="condition"]' => array('value' => 'value'),
      ),
    ),
  );

  $form['dependee_fieldset']['values_set'] = array(
    '#type' => 'textarea',
    '#title' => t('Set of values'),
    '#description' => t('Enter one value per line. Note that if the dependee has allowed values, these are actually the keys, not the labels, of those values.'),
    '#default_value' => implode("\n", $dependency['options']['values_set']),
    '#states' => array(
      'visible' => array(
        ':input[name="value"]' => array(
          array('value' => 'conditional_fields_dependency_value_and'),
          array('value' => 'conditional_fields_dependency_value_or'),
          array('value' => 'conditional_fields_dependency_value_xor'),
          array('value' => 'conditional_fields_dependency_value_not'),
        ),
        ':input[name="condition"]' => array('value' => 'value'),
      ),
      'required' => array(
        ':input[name="value"]' => array(
          array('value' => 'conditional_fields_dependency_value_and'),
          array('value' => 'conditional_fields_dependency_value_or'),
          array('value' => 'conditional_fields_dependency_value_xor'),
          array('value' => 'conditional_fields_dependency_value_not'),
        ),
      ),
    ),
  );

  $form['dependent_fieldset'] = array(
    '#type' => 'fieldset',
    '#title' => t('Dependent field settings'),
    '#description' => t('<p>These settings define the behavior of the dependent field in forms and when viewed.</p>'),
  );

  $form['dependent_fieldset']['state'] = array(
    '#type' => 'select',
    '#title' => t('Form state'),
    '#description' => t('Select the form state that should be applied to the dependent field when the Condition is met.'),
    '#options' => conditional_fields_states(),
    '#default_value' => $dependency['options']['state'],
    '#required' => TRUE,
  );

  $form['dependent_fieldset']['effect'] = array(
    '#type' => 'select',
    '#title' => t('Effect'),
    '#description' => t('Select the effect that should be applied when the element visibility is toggled.'),
    '#options' => conditional_fields_effects(),
    '#default_value' => $dependency['options']['effect'],
    '#states' => array(
      'visible' => array(
        ':input[name="state"]' => array(
          array('value' => 'visible'),
          array('value' => 'invisible'),
        ),
      ),
    ),
  );

  $element_view_options = array(
    CONDITIONAL_FIELDS_ELEMENT_VIEW_EVALUATE    => t('Hide the dependent field if the dependency is not activated'),
    CONDITIONAL_FIELDS_ELEMENT_VIEW_HIDE_ORPHAN => t('Hide the dependent field if the dependee is not viewable by the user'),
    CONDITIONAL_FIELDS_ELEMENT_VIEW_HIGHLIGHT   => t('Theme the dependent field like an error message if the dependency is not activated'),
    /* TODO: allow plugins
    FUNCTION_NAME => 'Description',
    */
  );

  $form['dependent_fieldset']['element_view'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Rendered field behaviour'),
    '#description' => t('Choose the behaviours of the dependent field when shown in content. This only applies when the Condition is Value.'),
    '#options' => $element_view_options,
    '#default_value' => $dependency['options']['element_view'],
    '#states' => array(
      'visible' => array(
        ':input[name="element_view_per_role"]' => array('checked' => FALSE),
        ':input[name="condition"]' => array('value' => 'value'),
      ),
    ),
  );

  $roles = user_roles();
  $element_view_roles = array('element_view_roles' => array('#tree' => TRUE));
  foreach ($roles as $rid => $role) {
    $element_view_roles['element_view_roles'][$rid] = array(
      '#type' => 'checkboxes',
      '#title' => t('Rendered field behaviour for %role', array('%role' => $role)),
      '#options' => $element_view_options,
      '#default_value' => isset($dependency['options']['element_view_roles'][$rid]) ? $dependency['options']['element_view_roles'][$rid] : $dependency['options']['element_view'],
      '#states' => array(
        'visible' => array(
          ':input[name="element_view_per_role"]' => array('checked' => TRUE),
          ':input[name="condition"]' => array('value' => 'value'),
        ),
      ),
    );
  }

  $form['dependent_fieldset']['element_view_per_role'] = array(
    '#type' => 'checkbox',
    '#title' => t('Activate per user role field view logic'),
    '#description' => t('If the user has more than one role, the first matching role will be used.'),
    '#default_value' => $dependency['options']['element_view_per_role'],
    '#states' => array(
      'visible' => array(
        ':input[name="condition"]' => array('value' => 'value')
      ),
    ),
  );

  array_push($form['dependent_fieldset'], $element_view_roles);

  /* TODO: Add OR and XOR to multiple dependencies on same dependent field
  $form['dependent_fieldset']['grouping'] = array(
    '#type' => 'radios',
    '#title' => t('Interaction with other dependencies'),
    '#description' => t('When the field [dependent] depends from fields other than [dependee], how should this set of conditions be evaluated against the others? Note that sets will be grouped this way: (ANDs) AND (ORs) AND (XORs).'),
    '#options' => array('AND', 'OR', 'XOR'),
    '#default_value' => $dependency['options']['grouping'],
    '#required' => TRUE,
  );
  */

  $form['actions'] = array(
    '#type' => 'actions',
    'save' => array(
      '#type' => 'submit',
      '#value' => t('Save settings'),
    ),
  );

  return $form;
}

/**
 * Dependency edit form submit.
 *
 * @see conditional_fields_dependency_edit_form()
 */
function conditional_fields_dependency_edit_form_submit($form, &$form_state) {
  // TODO: validate the set of values
  $values_set = explode("\n", $form_state['values']['values_set']);
  $values_set = array_map('trim', $values_set);
  $values_set = array_filter($values_set, 'strlen');

  $dependency = array(
    'id'        => $form['#dependency']['id'],
    'dependee'  => $form['#dependency']['dependee'],
    'dependent' => $form['#dependency']['dependent'],
    'options'   => array(
      'selector'              => empty($form_state['values']['selector']) ? $form['#default_selector'] : $form_state['values']['selector'],
      'selector_custom'       => empty($form_state['values']['selector']) || $form_state['values']['selector'] == $form['#default_selector'] ? 0 : $form_state['values']['selector_custom'],
      'state'                 => $form_state['values']['state'],
      'condition'             => $form_state['values']['condition'],
      'value'                 => $form_state['values']['value'],
      'values_set'            => $values_set,
//      'grouping'              => $form_state['values']['grouping'],
      'effect'                => $form_state['values']['effect'],
      'element_view'          => $form_state['values']['element_view'],
      'element_view_per_role' => $form_state['values']['element_view_per_role'],
      'element_view_roles'    => $form_state['values']['element_view_roles'],
    ),
  );

  conditional_fields_dependency_update($dependency);
  drupal_set_message(t('Saved dependency configuration.'));
  drupal_goto('admin/structure/dependencies');
}

/**
 * Confirm form for the deletion of a dependency.
 */
function conditional_fields_dependency_delete_form($form, $form_state, $dependency) {
  if (!is_numeric($dependency)) {
    drupal_access_denied();
  }

  return confirm_form(
    array(
    'dependency' => array(
      '#type' => 'value',
      '#value' => $dependency,
    ),
  ),
    t('Are you sure you want to delete this dependency?'),
    'admin/structure/dependencies',
    t('This action cannot be undone.'),
    t('Delete dependency'),
    t('Cancel')
  );
}

/**
 * Confirm form submit for the deletion of a dependency.
 */
function conditional_fields_dependency_delete_form_submit($form, &$form_state) {
  conditional_fields_dependency_delete(array($form_state['values']['dependency']));
  $form_state['redirect'] = 'admin/structure/dependencies';
}

/**
 * Build a textual description of a dependency
 */
function conditional_fields_dependency_description($dependee_name, $dependent_name, $options) {
  if ($options['condition'] == 'value') {
    $values_set = implode(', ', $options['values_set']);

    switch ($options['value']) {
      case 'conditional_fields_dependency_value_and':
        return t('%dependent_name is !state when %dependee_name has all the values: @values.', array(
          '%dependent_name' => $dependent_name,
          '!state' => $options['state'],
          '%dependee_name' => $dependee_name,
          '@values' => $values_set,
        ));
        break;
      case 'conditional_fields_dependency_value_or':
        return t('%dependent_name is !state when %dependee_name has at least one of the values: @values.', array(
          '%dependent_name' => $dependent_name,
          '!state' => $options['state'],
          '%dependee_name' => $dependee_name,
          '@values' => $values_set,
        ));
        break;
      case 'conditional_fields_dependency_value_xor':
        return t('%dependent_name is !state when %dependee_name has only one of the values: @values.', array(
          '%dependent_name' => $dependent_name,
          '!state' => $options['state'],
          '%dependee_name' => $dependee_name,
          '@values' => $values_set,
        ));
        break;
      case 'conditional_fields_dependency_value_not':
        return t('%dependent_name is !state when %dependee_name has none of the values: @values.', array(
          '%dependent_name' => $dependent_name,
          '!state' => $options['state'],
          '%dependee_name' => $dependee_name,
          '@values' => $values_set,
        ));
        break;

      default:
        return t('%dependent_name is !state when %dependee_name has the value @values.', array(
          '%dependent_name' => $dependent_name,
          '!state' => $options['state'],
          '%dependee_name' => $dependee_name,
          '@values' => $options['value'],
        ));
        break;
    }
  }
  else {
    return t('%dependent_name is !state when %dependee_name is !condition.', array(
      '%dependent_name' => $dependent_name,
      '!state' => $options['state'],
      '%dependee_name' => $dependee_name,
      '!condition' => $options['condition'],
    ));
  }
}
