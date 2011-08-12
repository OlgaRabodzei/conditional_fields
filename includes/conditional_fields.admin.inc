<?php
/**
 * @file
 * Administration of dependencies.
 */

/**
 * Dependencies administration page.
 *
 * If the callback is called from the overview page, it builds a list of
 * dependencies for each entity type, grouped by bundle.
 * If called from one of the local tasks, it builds a list of dependencies
 * only for the selected entity type.
 * If called from a task under a specific bundle administration page, it
 * builds a list just for that entity type and bundle name pair.
 */
function conditional_fields_dependencies_overview_page($bundle_name = NULL, $entity_type = NULL) {
  // When displaying the page, make sure the list of fields is up-to-date.
  field_info_cache_clear();

  // Gather entities information.
  $entities = $entity_type ? array($entity_type => entity_get_info($entity_type)) : entity_get_info();
  
  // Unused here, but saves queries in conditional_fields_dependency_add_form().
  conditional_fields_load_dependencies();

  // Extract bundle name from path, if present.
  if ($bundle_name) {
    $bundle_name = strtr($bundle_name, array('-' => '_'));

    // Hacky exception for hanlding comments.
    if ($entity_type == 'comment') {
      $bundle_name = 'comment_node_' . $bundle_name;
    }
  }

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

    foreach ($entity_info['bundles'] as $bundle_info_name => $bundle_info) {
      if ($bundle_name && $bundle_name != $bundle_info_name) {
        continue;
      }

      $build[$entity_name][$bundle_info_name] = array();

      if (!$bundle_name && count($entity_info['bundles']) > 1) {
        $build[$entity_name][$bundle_info_name]['title'] = array(
          '#markup' => '<h4 class="conditional-fields-bundles-list clearfix">' . $bundle_info['label'] . '</h4>',
        );
      }

      $build[$entity_name][$bundle_info_name] += drupal_get_form('conditional_fields_dependency_add_form_' . $entity_name . '_' . $bundle_info_name);

      if (!$entity_type && !isset($build[$entity_name][$bundle_info_name]['no_fields'])) {
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
 * @see conditional_fields_dependency_edit_form_validate()
 * @see conditional_fields_dependency_edit_form_submit()
 * @ingroup forms
 */
function conditional_fields_dependency_edit_form($form, &$form_state, $id) {
  if (!is_numeric($id) || !$form['#dependency'] = $dependency = conditional_fields_dependency_load_by_id($id)) {
    drupal_access_denied();
    drupal_exit();
  }

  $form['#attached']['js'] = array(drupal_get_path('module', 'conditional_fields') . '/js/conditional_fields.admin.js');
  $form['#attached']['css'][] = drupal_get_path('module', 'conditional_fields') . '/conditional_fields.css';

  // Retrieve needed information from the dependee instance.
  // Since we only have the instance id here (id column of the
  // field_config_instance table), not the entity id (id column of field_config)
  // we can't call field_info_field_by_id. This is needed because we don't
  // want dependencies to be shared between bundles.
  // So we first load instance information to obtain the entity id, then we load
  // the entity using field_info_field().
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

  $checkboxes = ($dependee_instance['widget']['type'] == 'options_buttons' && $dependee['cardinality'] != 1) || $dependee_instance['widget']['type'] == 'options_onoff' ? TRUE : FALSE;

  $form['condition'] = array(
    '#type' => 'select',
    '#title' => t('Condition'),
    '#description' => t('The condition that should be met by the dependee %field to trigger the dependency.', array('%field' => $dependee_instance['label'])),
    '#options' => conditional_fields_conditions($checkboxes),
    '#default_value' => $dependency['options']['condition'],
    '#required' => TRUE,
  );

  $form['values_set'] = array(
    '#type' => 'select',
    '#title' => t('Values'),
    '#description' => t('The values of the dependee %field that trigger the dependency.', array('%field' => $dependee_instance['label'])),
    '#options' => array(
      CONDITIONAL_FIELDS_DEPENDENCY_VALUES_SET_SINGLE => t('Insert value from widget...'),
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

  $form['value'] = array(
    '#type' => 'fieldset',
    '#title' => t('Value'),
    '#description' => t('The value that triggers the dependency.'),
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

  $form['values'] = array(
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
      'required' => array(
        ':input[name="condition"]' => array('value' => 'value'),
      ),
    ),
  );

  $form['state'] = array(
    '#type' => 'select',
    '#title' => t('Form state'),
    '#description' => t('The form state that is applied to the dependent field when the condition is met.'),
    '#options' => conditional_fields_states(),
    '#default_value' => $dependency['options']['state'],
    '#required' => TRUE,
    '#ajax' => array(
      'callback' => 'conditional_fields_ajax_admin_state_callback',
      'wrapper' => 'effects-wrapper',
     ),
  );

  $effects = $effects_options = array();
  $selected_state = isset($form_state['values']['state']) ? $form_state['values']['state'] : $dependency['options']['state'];
  foreach (conditional_fields_effects() as $effect_name => $effect) {
    if (in_array($selected_state, $effect['states'])) {
      $effects[$effect_name] = $effect['label'];
      if (isset($effect['options'])) {
        $effects_options[$effect_name] = $effect['options'];
      }
    }
  }

  $form['effects_wrapper'] = array(
    '#type' => 'container',
    '#attributes' => array(
      'id' => 'effects-wrapper',
    ),
  );

  $effect = isset($form_state['values']['effect']) ? $form_state['values']['effect'] : $dependency['options']['effect'];

  if (count($effects) == 1) {
    $form['effects_wrapper']['effect'] = array(
      '#type' => 'hidden',
      '#value' => array_shift(array_keys($effects)),
      '#default_value' => array_shift(array_keys($effects)),
    );    
  }
  elseif (count($effects) > 1) {
    $form['effects_wrapper']['effect'] = array(
      '#type' => 'select',
      '#title' => t('Effect'),
      '#description' => t('The effect that is applied to the dependent when its state is changed.'),
      '#options' => $effects,
      '#default_value' => $effect,
      '#states' => array(
        'visible' => array(
          ':input[name="state"]' => array(
            array('value' => 'visible'),
            array('value' => '!visible'),
          ),
        ),
      ),
    );
  }

  $form['effects_wrapper']['effect_options'] = array('#tree' => TRUE);

  foreach ($effects_options as $effect_name => $effect_options) {
    foreach ($effect_options as $effect_option_name => $effect_option) {
      $effect_option += array(
        '#title' => t('@effect effect option: @effect_option', array('@effect' => $effects[$effect_name], '@effect_option' => $effect_option_name)),
        '#states' => array(
          'visible' => array(
            ':input[name="effect"]' => array(
              array('value' => $effect_name),
            ),
          ),
        ),
      );

      if (isset($form_state['values']['effect_options'][$effect_name][$effect_option_name])) {
        $effect_option['#default_value'] = $form_state['values']['effect_options'][$effect_name][$effect_option_name];        
      }
      else if ($dependency['options']['effect'] == $effect_name) {
        $effect_option['#default_value'] = $dependency['options']['effect_options'][$effect_option_name];
      }

      $form['effects_wrapper']['effect_options'][$effect_name][$effect_option_name] = $effect_option;      
    }
  }

  $form['settings'] = array(
    '#type' => 'vertical_tabs',
  );

  $form['edit_settings'] = array(
    '#type' => 'fieldset',
    '#group' => 'settings',
    '#title' => t('Field edit settings'),
  );

  $form['edit_settings']['element_edit_per_role'] = array(
    '#type' => 'checkbox',
    '#title' => t('Activate per user role field edit settings'),
    '#description' => t('If the user has more than one role, the first matching role will be used.'),
    '#default_value' => $dependency['options']['element_edit_per_role'],
  );

  $field_edit_options = conditional_fields_field_edit_settings();

  $form['edit_settings']['element_edit'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Field edit settings for all roles'),
    '#description' => t('Behaviors of fields when edited in forms. Only apply if the condition is Value.'),
    '#options' => $field_edit_options,
    '#default_value' => $dependency['options']['element_edit'],
    '#states' => array(
      'visible' => array(
        ':input[name="element_edit_per_role"]' => array('checked' => FALSE),
      ),
    ),
  );

  $roles = user_roles();
  $element_edit_roles = array('element_edit_roles' => array('#tree' => TRUE));
  foreach ($roles as $rid => $role) {
    $element_edit_roles['element_edit_roles'][$rid] = array(
      '#type' => 'checkboxes',
      '#title' => t('Field edit settings for %role', array('%role' => $role)),
      '#options' => $field_edit_options,
      '#default_value' => isset($dependency['options']['element_edit_roles'][$rid]) ? $dependency['options']['element_edit_roles'][$rid] : $dependency['options']['element_edit'],
      '#states' => array(
        'visible' => array(
          ':input[name="element_edit_per_role"]' => array('checked' => TRUE),
        ),
      ),
    );
  }

  array_push($form['edit_settings'], $element_edit_roles);

  $form['view_settings'] = array(
    '#type' => 'fieldset',
    '#group' => 'settings',
    '#title' => t('Field view settings'),
  );

  $form['view_settings']['element_view_per_role'] = array(
    '#type' => 'checkbox',
    '#title' => t('Activate per user role field view settings'),
    '#description' => t('If the user has more than one role, the first matching role will be used.'),
    '#default_value' => $dependency['options']['element_view_per_role'],
  );

  $field_view_options = conditional_fields_field_view_settings();

  $form['view_settings']['element_view'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Field view settings for all roles'),
    '#description' => t('Behaviors of fields when shown in content. Only apply if the Condition is Value.'),
    '#options' => $field_view_options,
    '#default_value' => $dependency['options']['element_view'],
    '#states' => array(
      'visible' => array(
        ':input[name="element_view_per_role"]' => array('checked' => FALSE),
      ),
    ),
  );

  $element_view_roles = array('element_view_roles' => array('#tree' => TRUE));
  foreach ($roles as $rid => $role) {
    $element_view_roles['element_view_roles'][$rid] = array(
      '#type' => 'checkboxes',
      '#title' => t('Field view settings for %role', array('%role' => $role)),
      '#options' => $field_view_options,
      '#default_value' => isset($dependency['options']['element_view_roles'][$rid]) ? $dependency['options']['element_view_roles'][$rid] : $dependency['options']['element_view'],
      '#states' => array(
        'visible' => array(
          ':input[name="element_view_per_role"]' => array('checked' => TRUE),
        ),
      ),
    );
  }

  array_push($form['view_settings'], $element_view_roles);

  $form['dependency_advanced'] = array(
    '#type' => 'fieldset',
    '#group' => 'settings',
    '#title' => t('Advanced dependency settings'),
  );

  // Build the default jQuery selector.
  $form['#default_selector'] = conditional_fields_field_selector($dependee['field_name'], $dependee_instance['widget']['type'], $dummy_field[$dependee['field_name']]);

  $selector_description = t('You need to change this only if the automatically generated selector does not work or for advanced selection. You can use the following placeholders:');
  $selector_description .= "<ul>\n";
  $selector_description .= '<li>' . t('%lang: current language of the field.') . "</li>\n";
  $selector_description .= '<li>' . t('%key: part identifier for fields composed of multiple form elements, like checkboxes.') . "</li>\n";
  $selector_description .= '</ul>';
  $selector_description .= t('Leave the field empty to reset the selector to its default value.');

  $form['dependency_advanced']['selector'] = array(
    '#type' => 'textfield',
    '#title' => t('Dependee jQuery selector'),
    '#description' => $selector_description,
    '#default_value' => $dependency['options']['selector_custom'] ? $dependency['options']['selector'] : $form['#default_selector'],
  );

  $form['dependency_advanced']['selector_custom'] = array(
    '#type' => 'hidden',
    '#default_value' => $dependency['options']['selector_custom'],
  );

  /* TODO: Add OR and XOR to multiple dependencies on same dependent field
  $form['grouping'] = array(
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
 * Dependency edit form validate.
 *
 * @see conditional_fields_dependency_edit_form()
 */
function conditional_fields_dependency_edit_form_validate($form, &$form_state) {
  if ($form_state['values']['condition'] == 'value' && $form_state['values']['values_set'] != CONDITIONAL_FIELDS_DEPENDENCY_VALUES_SET_SINGLE && drupal_strlen(trim($form_state['values']['values'])) == 0) {
    form_set_error('values', t('!name field is required.', array('!name' => t('Set of values'))));
  }
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
  field_default_extract_form_values(NULL, NULL, array('field_name' => $dependee), NULL, LANGUAGE_NONE, $value, $form['value']['field'], $form_state);

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
      'effect'                => isset($form_state['values']['effect']) ? $form_state['values']['effect'] : FALSE,
      'effect_options'        => isset($form_state['values']['effect']) && isset($form_state['values']['effect_options'][$form_state['values']['effect']]) ? $form_state['values']['effect_options'][$form_state['values']['effect']] : array(),
      'element_view'          => $form_state['values']['element_view'],
      'element_view_per_role' => $form_state['values']['element_view_per_role'],
      'element_view_roles'    => $form_state['values']['element_view_roles'],
      'element_edit'          => $form_state['values']['element_edit'],
      'element_edit_per_role' => $form_state['values']['element_edit_per_role'],
      'element_edit_roles'    => $form_state['values']['element_edit_roles'],
    ),
  );

  conditional_fields_dependency_update($dependency);
  drupal_set_message(t('Saved dependency configuration.'));
}

/**
 * Ajax callback for effects list.
 */
function conditional_fields_ajax_admin_state_callback($form, &$form_state) {
  return $form['effects_wrapper'];
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
