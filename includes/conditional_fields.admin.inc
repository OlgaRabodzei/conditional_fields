<?php
// $Id$

function conditional_fields_admin_settings() {
  $bundles = field_info_bundles();
  $instances = field_info_instances();
  $entities_names = conditional_fields_entities_names();
  $dependencies = conditional_fields_load_dependencies();
  $output = '';
  $header = array(t('Dependee'), t('Dependent'), t('Description'), array('data' => t('Operations'), 'colspan' => 2));

  foreach ($bundles as $entity_type => $bundle) {
    $entity_name = (isset($entities_names[$entity_type])) ? $entities_names[$entity_type] : $entity_type;
    $output .= '<div class="conditional-fields-entities-list clearfix">';
    $items = array();

    foreach ($bundle as $bundle_name => $bundle_info) {
      $item = '<h4 class="conditional-fields-bundles-list clearfix">'. $bundles[$entity_type][$bundle_name]['label'] . '</h4>';

      if (count($instances[$entity_type][$bundle_name]) < 2) {
        $items[] = $item . '<p>' . t('Add at least two fields to enable dependencies') . '</p>';
        continue;
      }

      $item .= '<p>' . l(t('Add dependency'), 'admin/structure/dependencies/add/' . $entity_type . '/' . $bundle_name)  . '</p>';

      if (isset($dependencies[$entity_type][$bundle_name])) {
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

    $output .= theme('item_list', array('items' => $items,  'title' => $entity_name, 'type' => 'ul')) . '</div>';
  }

  return $output;
}

/**
 * 
 * 
 * @param 
 *   
 * @return void
 *   
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

function conditional_fields_dependency_add_form_validate($form, &$form_state) {
  if ($form_state['values']['dependee'] == $form_state['values']['dependent']) {
    form_set_error('dependent', t('You should select two different fields.'));
    return;
  }

  if (conditional_fields_dependency_load($form_state['values']['dependee'], $form_state['values']['dependent'], $form['#entity_type']['#value'], $form['#bundle_name']['#value'])) {
    form_set_error('dependent', t('These fields already share a dependency.'));
    return;    
  }
}

function conditional_fields_dependency_add_form_submit($form, &$form_state) {
  if (!$id = conditional_fields_dependency_insert($form_state['values']['dependee'], $form_state['values']['dependent'])) {
    drupal_set_message(t('There was an error while trying to create the dependency.'), 'error');
    return;
  }

  drupal_goto('admin/structure/dependencies/edit/' . $id);
}

/**
 * 
 * 
 * @param 
 *   
 * @return void
 *   
 */
function conditional_fields_dependency_edit_form($form, &$form_state, $id) {
  if (!is_numeric($id)) {
    drupal_access_denied();
    drupal_exit();    
  }

  if (!$form['#dependency'] = $dependency = conditional_fields_dependency_load_by_id($id)) {
    drupal_access_denied();
    drupal_exit();
  }

  $form['#attached']['js'] = array(drupal_get_path('module', 'conditional_fields') . '/js/conditional_fields.admin.js');

  // Retrieve the allowed values for the dependee instance.
  // We do have the bundle and entity type, but we would have to build a create form for the entity to obtain the allowed values.
  // Moreover, we only have the instance id here (id column of the field_config_instance table), not the entity id (id column of field_config)
  // so we can't call field_info_field_by_id. This is needed because we don't want dependencies to be shared between bundles.
  // So we first load the instance information, so we can obtain the entity id. Then we load the entity using field_info_field().
  // Should not be too much a performance problem, since we only use this in administration.
  $dependee_instance = array_shift(field_read_instances(array('id' => $dependency['dependee'])));
  $dependee = field_info_field($dependee_instance['field_name']);

  $dummy_form = array(
    '#parents' => array(),
  );
  $dummy_form_state = array();
  $dummy_field = field_default_form($dependee_instance['entity_type'], NULL, $dependee, $dependee_instance, LANGUAGE_NONE, array(), $dummy_form, $dummy_form_state, 0);

  if (empty($dependency['options']['selector'])) {
    // TODO: add language token
    $selector = conditional_fields_field_selector($dependee['field_name'], $dependee_instance['widget']['type'], $dummy_field[$dependee['field_name']]);
  }
  else {
    $selector = $dependency['options']['selector'];
  }

  $form['dependee_fieldset'] = array(
    '#type' => 'fieldset',
    '#title' => t('Dependee field settings'),
    '#description' => t('<p>These settings define the behavior of the dependee field in forms.</p>'),
  );
  
  $form['dependee_fieldset']['selector'] = array(
    '#type' => 'textfield',
    '#title' => t('Selector'),
    '#description' => t('jQuery selector for the dependee field. You need to change this only if the automatically generated selector does not work or for advanced selection. Leave the field empty to reset the selector to its default value.'),
    '#default_value' => $selector,
  );

  $form['dependee_fieldset']['selector_custom'] = array(
    '#type' => 'hidden',
    '#default_value' => $dependency['options']['selector_custom'],
  );

  $form['dependee_fieldset']['condition'] = array(
    '#type' => 'radios',
    '#title' => t('Condition'),
    '#description' => t('Select the condition that should be met by the dependee field to activate the dependency.'),
    '#options' => conditional_fields_conditions($dependee_instance['widget']['type'] == 'options_buttons' ? TRUE : FALSE),
    '#default_value' => $dependency['options']['condition'],
    '#required' => TRUE,
  );

  // In Drupal 7, allowed values are a feature of the List module
  // TODO: include also allowed values from latest CCK (PHP + ?)
  $allowed_values = array();

  if (function_exists($function = $dependee['module'] . '_options_list')) {
    $allowed_values[t('From allowed values')] = $function($dependee);
  }

  $form['dependee_fieldset']['value'] = array(
    '#type' => 'select',
    '#title' => t('Values'),
    '#description' => t('Select the values of the dependee field that will activate the dependency.'),
    '#options' => array_merge(
      array(t('Set of values') => array(
        'conditional_fields_dependency_value_all'    => t('All these values...'),
        'conditional_fields_dependency_value_any'    => t('Any of these values...'),
        'conditional_fields_dependency_value_none'   => t('None of these values...'),
      )),
      $allowed_values
    ),
    '#default_value' => $dependency['options']['value'],
    '#states' => array(
      'visible' => array(
        ':input[name="condition"]' => array('value' => 'value'),
      ),
    ),
    // TODO: add #dependency information. Like an addon to the Sates API?
    // Something like:
    // '#dependencies' => array('dependency_1' => options, ...)
  );

  // TODO: special conditions for checkboxes and radio buttons...
  $form['dependee_fieldset']['values_set'] = array(
    '#type' => 'textarea',
    '#title' => t('Set of values'),
    '#description' => t('Enter one value per line.'),
    '#default_value' => implode("\n", $dependency['options']['values_set']),
    '#states' => array(
      'visible' => array(
        ':input[name="value"]' => array(
          array('value' => 'conditional_fields_dependency_value_all'),
          array('value' => 'conditional_fields_dependency_value_any'),
          array('value' => 'conditional_fields_dependency_value_none'),
        ),
        ':input[name="condition"]' => array('value' => 'value'),
      ),
      'required' => array(
        ':input[name="value"]' => array(
          array('value' => 'conditional_fields_dependency_value_all'),
          array('value' => 'conditional_fields_dependency_value_any'),
          array('value' => 'conditional_fields_dependency_value_none'),
        ),
      ),
    ),
  );

  /* TODO: Find a way to add effects to visible/invisible transitions of States API.
  $form['effect'] = array(
    '#type' => 'select',
    '#title' => t('Effect'),
    '#multiple' => TRUE,
    '#description' => 'TODO',
    '#options' => array('fade in...'),
    '#default_value' => $dependency['options']['effect'],
  );
  */

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


  $form['dependent_fieldset']['element_view'] = array(
    '#type' => 'radios',
    '#title' => t('Rendered field behaviour'),
    '#description' => t('Choose the behaviour of the dependent field when shown in content. This only applies when the Condition is Checked, Unchecked or Value.'),
    '#options' => array(
      CONDITIONAL_FIELDS_ELEMENT_VIEW_NOTHING  => t('Always show the dependent field'), 
      CONDITIONAL_FIELDS_ELEMENT_VIEW_EVALUATE => t('Show the dependent field only if the dependency is activated'),
      /* TODO: allow plugins
      KEY => 'Custom...',
      */
    ),
    '#default_value' => $dependency['options']['element_view'],
    '#states' => array(
      'visible' => array(
        ':input[name="element_view_per_role"]' => array('checked' => FALSE),
      ),
    ),
  );

  $roles = user_roles();
  $element_view_roles = array('element_view_roles' => array('#tree' => TRUE));
  foreach ($roles as $rid => $role) {
    $element_view_roles['element_view_roles']['element_view_' . $rid] = array(
      '#type' => 'radios',
      '#title' => t('Rendered field behaviour') . ' (' . check_plain($role) . ')',
      '#options' => array(
        CONDITIONAL_FIELDS_ELEMENT_VIEW_NOTHING  => t('Always show the dependent field'), 
        CONDITIONAL_FIELDS_ELEMENT_VIEW_EVALUATE => t('Show the dependent field only if the dependency is activated.'),
        /* TODO: allow plugins
        KEY => 'Custom...',
        */
      ),
      '#default_value' => isset($dependency['options']['element_view_roles']['element_view_' . $rid]) ? $dependency['options']['element_view_roles']['element_view_' . $rid] : $dependency['options']['element_view'],
      '#states' => array(
        'visible' => array(
          ':input[name="element_view_per_role"]' => array('checked' => TRUE),
        ),
      ),
    );
  }

  $form['dependent_fieldset']['element_view_per_role'] = array(
    '#type' => 'checkbox',
    '#title' => t('Activate per user role field view logic'),
    '#default_value' => $dependency['options']['element_view_per_role'],
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
      '#value' => t('Save'),
    ),
    /* TODO
    'delete' => array(
      '#type' => 'submit',
      '#value' => t('Save'),
    ),
    */
  );

  return $form;
}

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
      'selector'              => $form_state['values']['selector'],
      'selector_custom'       => $form_state['values']['selector_custom'],
      'state'                 => $form_state['values']['state'],
      'condition'             => $form_state['values']['condition'],
      'value'                 => $form_state['values']['value'],
      'values_set'            => $values_set,
//      'grouping'              => $form_state['values']['grouping'],
//      'effect'              => $form_state['values']['effect'],
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
 * Confirm form for the deletion of a dependency
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

function conditional_fields_dependency_delete_form_submit($form, &$form_state) {
  conditional_fields_dependency_delete(array($form_state['values']['dependency']));
  $form_state['redirect'] = 'admin/structure/dependencies';
}

/**
 * Automatically build a textual description of a dependency
 * 
 * @param 
 *   
 * @return string
 *   
 */
function conditional_fields_dependency_description($dependee_name, $dependent_name, $options) {
  if ($options['condition'] == 'value') {
    $values_set = implode(', ', $options['values_set']);

    switch ($options['value']) {
      case 'conditional_fields_dependency_value_all':
        $string = '%dependent_name is !state when %dependee_name has all the values: @values.';
        break;
      case 'conditional_fields_dependency_value_any':
        $string = '%dependent_name is !state when %dependee_name has at least one of the values: @values.';
        break;
      case 'conditional_fields_dependency_value_none':
        $string = '%dependent_name is !state when %dependee_name has none of the values: @values.';
        break;

      default:
        $string = '%dependent_name is !state when %dependee_name has the value @values.';
        $values_set = $options['value'];
        break;
    }

    return t($string, array(
      '%dependent_name' => $dependent_name,
      '!state' => $options['state'],
      '%dependee_name' => $dependee_name,
      '@values' => $values_set,
    ));
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