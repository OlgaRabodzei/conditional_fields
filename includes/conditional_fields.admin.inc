<?php
/**
 * @file
 * Administration of dependencies.
 */

/**
 * Dependencies administration page.
 *
 * If the callback is called from the overview page, it builds a list of
 * dependencies for each entity type, grouped by bundle. Otherwise, it builds
 * a list of dependencies only for the selected entity type.
 */
function conditional_fields_dependencies_overview_page($entity_type = NULL) {
  // When displaying the page, make sure the list of fields is up-to-date.
  field_info_cache_clear();

  // Gather entities information.
  $entities = $entity_type ? array($entity_type => entity_get_info($entity_type)) : entity_get_info();
  
  // Unused here, but saves queries in conditional_fields_dependency_add_form().
  conditional_fields_load_dependencies();

  $build = array();

  foreach ($entities as $entity_name => $entity_info) {
    if (!$entity_info['fieldable']) {
      continue;
    }

    $items = array();

    if ($entity_type) {
      $build[$entity_name] = array();
    }
    else {
      $build[$entity_name] = array(
        '#type' => 'fieldset',
        '#title' => $entity_info['label'],
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#attached' => array(
          'js' => array(
            'misc/form.js',
            'misc/collapse.js',
          ),
        ),
        '#attributes' => array(
          'class' => array('collapsible collapsed'),
        ),
      );
    }

    foreach ($entity_info['bundles'] as $bundle_name => $bundle_info) {
      $build[$entity_name][$bundle_name]['title'] = array(
        '#markup' => '<h4 class="conditional-fields-bundles-list clearfix">' . $bundle_info['label'] . '</h4>',
      );

      $build[$entity_name][$bundle_name] += drupal_get_form($entity_name . '_' . $bundle_name . '_conditional_fields_dependency_add_form');

      if (!$entity_type && !isset($build[$entity_name][$bundle_name]['no_fields'])) {
        $build[$entity_name]['#collapsed'] = FALSE;
        $build[$entity_name]['#attributes']['class'] = array('collapsible');
      }
    }
  }

  return $build;
}

/**
 * Dependency add form.
 *
 * @see conditional_fields_dependency_add_form_submit()
 * @see conditional_fields_dependency_add_form_validate()
 * @ingroup forms
 */
function conditional_fields_dependency_add_form($form, &$form_state, $entity_type, $bundle_name) {
  $instances = field_info_instances($entity_type, $bundle_name);

  $form = array();

  if (count($instances) < 2) {
    $form['no_fields'] = array(
      '#markup' => '<p>' . t('Add at least two fields to enable dependencies.') . '</p>',
    );

    return $form;
  }

  $dependencies = conditional_fields_load_dependencies($entity_type, $bundle_name);

  $form['table'] = array(
    '#type' => 'conditional_fields_table',
    '#entity_type' => $entity_type,
    '#bundle_name' => $bundle_name,
    '#header' => array(
      t('Dependent'),
      t('Dependee'),
      t('Description'),
      array('data' => t('Operations'), 'colspan' => 2),
    ),
    '#attributes' => array(
      'class' => array('conditional-fields-overview'),
    ),
    'dependencies' => array(),
  );

  $form['table']['#attached']['css'][] = drupal_get_path('module', 'conditional_fields') . '/conditional_fields.css';

  if ($dependencies) {
    $destination = drupal_get_destination();

    foreach ($dependencies as $dependent_name => $field_dependencies) {
      if (!isset($field_dependencies['dependees'])) {
        continue;
      }

      foreach ($field_dependencies['dependees'] as $dependee_name => $options) {
        $form['table']['dependencies'][$options['id']] = array(
          'dependent' => array(
            '#markup' => $dependent_name,
          ),
          'dependee' => array(
            '#markup' => $dependee_name,
          ),
          'description' => array(
            '#markup' => conditional_fields_dependency_description($dependee_name, $dependent_name, $options['options']),
          ),
          'edit' => array(
            '#type' => 'link',
            '#title' => t('edit'),
            '#href' => 'admin/structure/dependencies/edit/' . $options['id'],
            '#options' => array('query' => $destination, 'attributes' => array('title' => t('Edit dependency settings.'))),
            '#query' => drupal_get_destination(),
          ),
          'delete' => array(
            '#type' => 'link',
            '#title' => t('delete'),
            '#href' => 'admin/structure/dependencies/delete/' . $options['id'],
            '#options' => array('query' => $destination, 'attributes' => array('title' => t('Delete dependency.'))),
          ),
        );
      }
    }
  }

  // Build list of available fields.
  $fields = array();

  foreach ($instances as $field) {
    if ($field['deleted']) {
      continue;
    }

    $fields[$field['id']] = check_plain($field['label']);
  }

  // Build list of states.
  $states = array_map('drupal_strtolower', conditional_fields_states());

  // Build list of conditions.
  foreach (conditional_fields_conditions() as $condition => $label) {
    $conditions[$condition] = $condition == 'value' ? t('has value...') : t('is !label', array('!label' => drupal_strtolower($label)));
  }

  // Add new dependency row.
  $form['table']['add_new_dependency'] = array(
    'dependent' => array(
      '#type' => 'select',
      '#title' => t('Dependent'),
      '#title_display' => 'invisible',
      '#description' => t('Dependent'),
      '#options' => $fields,
      '#prefix' => '<div class="add-new-placeholder">' . t('Add new dependency') . '</div>',
    ),
    'dependee' => array(
      '#type' => 'select',
      '#title' => t('Dependee'),
      '#title_display' => 'invisible',
      '#description' => t('Dependee'),
      '#options' => $fields,
      '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
    ),
    'state' => array(
      '#type' => 'select',
      '#title' => t('State'),
      '#title_display' => 'invisible',
      '#options' => $states,
      '#default_value' => 'visible',
      '#prefix' => t('The dependent field is') . '&nbsp;<span class="description-select">',
      '#suffix' => '</span>&nbsp;' . t('when the dependee'),
    ),
    'condition' => array(
      '#type' => 'select',
      '#title' => t('Condition'),
      '#title_display' => 'invisible',
      '#options' => $conditions,
      '#default_value' => 'value',
      '#prefix' => '&nbsp;<span class="description-select">',
      '#suffix' => '</span>',
    ),
    'actions' => array(
      'submit' => array(
        '#type' => 'submit',
        '#value' => t('Add dependency'),
      ),
    ),
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
    form_set_error('dependee', t('You should select two different fields.'));
    // Workaround to avoid duplicate error messages.
    array_pop($_SESSION['messages']['error']);
  }
}

/**
 * Dependency add form submit.
 *
 * @see conditional_fields_dependency_add_form()
 * @see conditional_fields_dependency_add_form_validate()
 */
function conditional_fields_dependency_add_form_submit($form, &$form_state) {
  $options = array(
    'state' => $form_state['values']['state'],
    'condition' => $form_state['values']['condition']
  );


  if (!$id = conditional_fields_dependency_insert($form_state['values']['dependee'], $form_state['values']['dependent'], $options)) {
    drupal_set_message(t('There was an error while trying to create the dependency.'), 'error');
    return;
  }

  $destination = drupal_get_destination();
  drupal_goto("admin/structure/dependencies/edit/$id", array('query' => $destination));
}

/**
 * Returns HTML for Conditional Fields dependencies tables.
 *
 * @param $variables
 *   An associative array containing:
 *   - elements: An associative array containing a Form API structure to be
 *     rendered as a table.
 *
 * @ingroup themeable
 */
function theme_conditional_fields_table($variables) {
  $elements = $variables['elements'];
  $table = array();

  // Add table headers and attributes.
  foreach (array('header', 'attributes') as $key) {
    if (isset($elements["#$key"])) {
      $table[$key] = $elements["#$key"];
    }
  }

  // Dependencies rows.
  foreach (element_children($elements['dependencies']) as $dependency) {
    foreach (element_children($elements['dependencies'][$dependency]) as $cell_key) {
      $table['rows'][$dependency][] = drupal_render($elements['dependencies'][$dependency][$cell_key]);
    }
  }

  // Add new dependency row.
  $table['rows'][] = array(
    drupal_render($elements['add_new_dependency']['dependent']),
    drupal_render($elements['add_new_dependency']['dependee']),
    drupal_render($elements['add_new_dependency']['state']) . drupal_render($elements['add_new_dependency']['condition']),
    array(
      'data' => drupal_render($elements['add_new_dependency']['actions']),
      'colspan' => 2,
    ),
  );

  return theme('table', $table);
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
  $instances = field_read_instances(array('id' => $dependency['dependee']));
  $dependee_instance = array_shift($instances);
  $dependee = field_info_field($dependee_instance['field_name']);

  // Build a dummy field widget to extract the jQuery selector and to use as
  // form field in single value selection option.
  $dummy_form = array('#parents' => array());
  $dependee_instance['default_value'] = $dependency['options']['value'];
  $dependee_instance['default_value_function'] = '';
  $dependee_instance['required'] = FALSE;

  $dummy_field = field_default_form($dependee_instance['entity_type'], NULL, $dependee, $dependee_instance, LANGUAGE_NONE, array(), $dummy_form, $form_state);

  // Save dependee name in form.
  $form['dependee'] = array(
    '#type' => 'value',
    '#value' => $dependee_instance['field_name'],
  );

  // Build the default jQuery selector.
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
    '#default_value' => $dependency['options']['selector_custom'] ? $dependency['options']['selector'] : $form['#default_selector'],
  );

  $form['dependee_fieldset']['selector_custom'] = array(
    '#type' => 'hidden',
    '#default_value' => $dependency['options']['selector_custom'],
  );

  $checkboxes = ($dependee_instance['widget']['type'] == 'options_buttons' && $dependee['cardinality'] != 1) || $dependee_instance['widget']['type'] == 'options_onoff' ? TRUE : FALSE;

  $form['dependee_fieldset']['condition'] = array(
    '#type' => 'radios',
    '#title' => t('Condition'),
    '#description' => t('The condition that should be met by the dependee field to activate the dependency.'),
    '#options' => conditional_fields_conditions($checkboxes),
    '#default_value' => $dependency['options']['condition'],
    '#required' => TRUE,
  );

  $form['dependee_fieldset']['values_set'] = array(
    '#type' => 'select',
    '#title' => t('Values'),
    '#description' => t('Select which set of values of the dependee field will activate the dependency.'),
    '#options' => array(
      CONDITIONAL_FIELDS_DEPENDENCY_VALUES_SET_SINGLE => t('Insert values from widget...'),
      t('Set of values') => array(
        CONDITIONAL_FIELDS_DEPENDENCY_VALUES_SET_AND    => t('All these values...'),
        CONDITIONAL_FIELDS_DEPENDENCY_VALUES_SET_OR     => t('Any of these values...'),
        CONDITIONAL_FIELDS_DEPENDENCY_VALUES_SET_XOR    => t('Only one of these values...'),
        CONDITIONAL_FIELDS_DEPENDENCY_VALUES_SET_NOT    => t('None of these values...'),
        // TODO: PHP evaluation
      ),
    ),
    '#default_value' => $dependency['options']['values_set'],
    '#required' => TRUE,
    '#states' => array(
      'visible' => array(
        ':input[name="condition"]' => array('value' => 'value'),
      ),
    ),
  );

  $form['dependee_fieldset']['value'] = array(
    '#type' => 'fieldset',
    '#title' => t('Values'),
    '#description' => t('Insert the value that will trigger the dependency.'),
    '#states' => array(
      'visible' => array(
        ':input[name="values_set"]' => array(
          'value' => (string) CONDITIONAL_FIELDS_DEPENDENCY_VALUES_SET_SINGLE,
        ),
        ':input[name="condition"]' => array('value' => 'value'),
      ),
    ),
    '#tree' => TRUE,
    'field' => $dummy_field,
  );

  $form['dependee_fieldset']['values'] = array(
    '#type' => 'textarea',
    '#title' => t('Set of values'),
    '#description' => t('Enter one value per line. Note that if the dependee has allowed values, these are actually the keys, not the labels, of those values.'),
    '#default_value' => implode("\n", $dependency['options']['values']),
    '#states' => array(
      'visible' => array(
        ':input[name="values_set"]' => array(
          array('value' => (string) CONDITIONAL_FIELDS_DEPENDENCY_VALUES_SET_AND),
          array('value' => (string) CONDITIONAL_FIELDS_DEPENDENCY_VALUES_SET_OR),
          array('value' => (string) CONDITIONAL_FIELDS_DEPENDENCY_VALUES_SET_XOR),
          array('value' => (string) CONDITIONAL_FIELDS_DEPENDENCY_VALUES_SET_NOT),
        ),
        ':input[name="condition"]' => array('value' => 'value'),
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
    '#description' => t('The form state that should be applied to the dependent field when the Condition is met.'),
    '#options' => conditional_fields_states(),
    '#default_value' => $dependency['options']['state'],
    '#required' => TRUE,
  );

  $form['dependent_fieldset']['effect'] = array(
    '#type' => 'select',
    '#title' => t('Effect'),
    '#description' => t('The effect that should be applied when the element visibility is toggled.'),
    '#options' => conditional_fields_effects(),
    '#default_value' => $dependency['options']['effect'],
    '#states' => array(
      'visible' => array(
        ':input[name="state"]' => array(
          array('value' => 'visible'),
          array('value' => '!visible'),
        ),
      ),
    ),
  );

  $element_view_options = array(
    CONDITIONAL_FIELDS_ELEMENT_VIEW_EVALUATE    => t('Hide the dependent field if the dependency is not activated'),
    CONDITIONAL_FIELDS_ELEMENT_VIEW_HIDE_ORPHAN => t('Hide the dependent field if the dependee is not viewable by the user'),
    CONDITIONAL_FIELDS_ELEMENT_VIEW_HIGHLIGHT   => t('Theme the dependent field like an error message if the dependency is not activated'),
    CONDITIONAL_FIELDS_ELEMENT_VIEW_DESCRIBE    => t('Show a textual description of the dependency under the dependent field'),
    /* TODO: allow plugins
    FUNCTION_NAME => 'Description',
    */
  );

  $form['dependent_fieldset']['element_view'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Rendered field behaviour'),
    '#description' => t('The behaviours of the dependent field when shown in content. This only applies when the Condition is Value.'),
    '#options' => $element_view_options,
    '#default_value' => $dependency['options']['element_view'],
    '#states' => array(
      'visible' => array(
        ':input[name="element_view_per_role"]' => array('checked' => FALSE),
        ':input[name="condition"]' => array('value' => 'value'),
      ),
    ),
  );

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

  // Redirect to bundle dependencies form if destination is set.
  $destination = drupal_get_destination();
  if ($destination['destination'] != 'admin/structure/dependencies') {
    $form_state['redirect'] = $destination['destination'];
  }

  return $form;
}

/**
 * Dependency edit form submit.
 *
 * @see conditional_fields_dependency_edit_form()
 */
function conditional_fields_dependency_edit_form_submit($form, &$form_state) {
  $dependee = $form_state['values']['dependee'];
  $widget_type = $form_state['field'][$dependee][LANGUAGE_NONE]['instance']['widget']['type'];

  $value = array();
  field_default_extract_form_values(NULL, NULL, array('field_name' => $dependee), NULL, LANGUAGE_NONE, $value, $form['dependee_fieldset']['value']['field'], $form_state);

  $values = explode("\n", $form_state['values']['values']);
  $values = array_map('trim', $values);
  $values = array_filter($values, 'strlen');

  // TODO: validate values against allowed values.
  // TODO: unify 'value' and 'values' fields.

  $dependency = array(
    'id'        => $form['#dependency']['id'],
    'dependee'  => $form['#dependency']['dependee'],
    'dependent' => $form['#dependency']['dependent'],
    'options'   => array(
      // If the selector was left empty, switch back to the default selector.
      'selector'              => empty($form_state['values']['selector']) ? $form['#default_selector'] : $form_state['values']['selector'],
      'selector_custom'       => empty($form_state['values']['selector']) || $form_state['values']['selector'] == $form['#default_selector'] ? 0 : $form_state['values']['selector_custom'],
      'state'                 => $form_state['values']['state'],
      'condition'             => $form_state['values']['condition'],
      'values_set'            => $form_state['values']['values_set'],
      // Single value is saved both as it is submitted by the form
      // and as it is saved in the database.
      // The latter is needed for fields that do not store directly
      // the user input but a processed value (such as date_combo).
      'value'                 => $value,
      'value_form'            => $form_state['input']['value']['field'][$dependee][LANGUAGE_NONE],
      'values'                => $values,
//      'grouping'              => $form_state['values']['grouping'],
      'effect'                => $form_state['values']['effect'],
      'element_view'          => $form_state['values']['element_view'],
      'element_view_per_role' => $form_state['values']['element_view_per_role'],
      'element_view_roles'    => $form_state['values']['element_view_roles'],
    ),
  );

  conditional_fields_dependency_update($dependency);
  drupal_set_message(t('Saved dependency configuration.'));
}

/**
 * Confirm form for the deletion of a dependency.
 */
function conditional_fields_dependency_delete_form($form, $form_state, $dependency) {
  if (!is_numeric($dependency)) {
    drupal_access_denied();
  }

  $destination = drupal_get_destination();

  return confirm_form(
    array(
    'dependency' => array(
      '#type' => 'value',
      '#value' => $dependency,
    ),
  ),
    t('Are you sure you want to delete this dependency?'),
    $destination['destination'],
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
}
