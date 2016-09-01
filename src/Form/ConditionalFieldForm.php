<?php

namespace Drupal\conditional_fields\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class ConditionalFieldForm.
 *
 * @package Drupal\conditional_fields\Form
 */
class ConditionalFieldForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conditional_field_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    module_load_include('inc', 'conditional_fields', 'conditional_fields.conditions');

    $entity_type_options = \Drupal::entityTypeManager()->getDefinitions();
    foreach ($entity_type_options as $key => $entity_type) {
      $entity_type_options[$key] = $entity_type->getLabel();
    }

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select entity type'),
      '#options' => $this->filterContentEntityTypes($entity_type_options),
      '#ajax' => [
        'callback' => '::entityTypeCallback',
        'wrapper' => 'entity-type-wrapper',
      ],
      '#required' => TRUE,
    ];

    $form['entity_type_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'entity-type-wrapper'],
    ];

    // Get entity type value.
    if (!$form_state->hasValue('entity_type')) {
      return $form;
    }
    $entity_type = $form_state->getValue('entity_type');

    // Get entity type bundles.
    $entity_types_options = \Drupal::getContainer()
      ->get('entity_type.bundle.info')
      ->getBundleInfo($entity_type);
    foreach ($entity_types_options as $key => $entity_types_def) {
      $entity_types_options[$key] = array_key_exists('label', $entity_types_def) ? $entity_types_def['label'] : $key;
    }

    $form['entity_type_wrapper']['bundle'] = [
      '#title' => $this->t('Bundle name'),
      '#type' => 'select',
      '#options' => $entity_types_options,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::conditionalFieldsCallback',
        'wrapper' => 'conditional-fields-wrapper',
      ],
    ];

    $form['entity_type_wrapper']['conditional_fields_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'conditional-fields-wrapper'],
    ];
    if ($bundle_name = $form_state->getValue('bundle')) {
      $form['entity_type_wrapper']['conditional_fields_wrapper']['table'] = $this->buildTable($form, $form_state, $entity_type, $bundle_name);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $table = $form_state->getValue('table');
    if (empty($table['add_new_dependency']) || !is_array($table['add_new_dependency'])) {
      parent::validateForm($form, $form_state);
    }
    $conditional_values = $table['add_new_dependency'];
    // Check dependency.
    if (array_key_exists('dependee', $conditional_values) &&
      array_key_exists('dependent', $conditional_values) &&
      $conditional_values['dependee'] == $conditional_values['dependent']
    ) {
      $form_state->setErrorByName('dependee', $this->t('You should select two different fields.'));
      $form_state->setErrorByName('dependent', $this->t('You should select two different fields.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $table = $form_state->getValue('table');
    if (empty($table['add_new_dependency']) || !is_array($table['add_new_dependency'])) {
      parent::submitForm($form, $form_state);
    }

    $field_name = '';
    $form_state->set('plugin_settings_edit', $field_name);

    $conditional_values = $table['add_new_dependency'];
    // Copy values from table for submit.
    $component_value = [];
    $settings = [];
    foreach ($conditional_values as $key => $value) {
      if ($key == 'dependent') {
        $field_name = $value;
        continue;
      }
      if (in_array($key, ['entity_type', 'bundle', 'dependee'])) {
        $component_value[$key] = $value;
        continue;
      }
      $settings[$key] = $value;
    }
    $settings += conditional_fields_dependency_default_settings();
    $component_value['settings'] = $settings;

    $component_value['entity_type'] = $form_state->getValue('entity_type');
    $component_value['bundle'] = $form_state->getValue('bundle');

    $uuid = $form_state->hasValue('uuid') ? $form_state->getValue('uuid') : \Drupal::service('uuid')
      ->generate();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity */
    $entity = entity_get_form_display($component_value['entity_type'], $component_value['bundle'], 'default');
    $field = $entity->getComponent($field_name);
    $field['third_party_settings']['conditional_fields'][$uuid] = $component_value;
    $entity->setComponent($field_name, $field);
    $entity->save();
    $form_state->setRedirect(
      'conditional_fields.edit_form', [
        'entity_type' => $component_value['entity_type'],
        'bundle' => $component_value['bundle'],
        'field_name' => $field_name,
        'uuid' => $uuid,
      ]
    );
  }

  /**
   * Builds table with conditional fields.
   */
  protected function buildTable(array $form, FormStateInterface $form_state, $entity_type, $bundle_name = NULL) {
    $form['table'] = [
      '#type' => 'table',
      '#entity_type' => $entity_type,
      '#bundle_name' => $bundle_name,
      '#header' => [
        $this->t('Dependent'),
        $this->t('Dependees'),
        ['data' => $this->t('Description'), 'colspan' => 2],
        ['data' => $this->t('Operations'), 'colspan' => 2],
      ],
    ];

    // Build list of available fields.
    $fields = array();
    $instances = \Drupal::getContainer()->get('entity_field.manager')
      ->getFieldDefinitions($entity_type, $bundle_name);
    foreach ($instances as $field) {
      $fields[$field->getName()] = $field->getLabel() . ' (' . $field->getName() . ')';
    }

    asort($fields);

    /* Existing conditions. */

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity */
    $form_display_entity = entity_get_form_display($entity_type, $bundle_name, 'default');
    foreach ($fields as $field_name => $label) {
      $field = $form_display_entity->getComponent($field_name);
      if (empty($field['third_party_settings']['conditional_fields'])) {
        continue;
      }
      // Create row for existing field's conditions.
      foreach ($field['third_party_settings']['conditional_fields'] as $uuid => $condition) {
        $form['table'][] = [
          'dependent' => ['#markup' => $field_name],
          'dependee' => ['#markup' => $condition['dependee']],
          'state' => ['#markup' => $condition['settings']['state']],
          'condition' => ['#markup' => $condition['settings']['condition']],
          'actions' => [
            '#type' => 'operations',
            '#links' => [
              'edit' => [
                'title' => $this->t('Edit'),
                'url' => Url::fromRoute('conditional_fields.edit_form', [
                  'entity_type' => $condition['entity_type'],
                  'bundle' => $condition['bundle'],
                  'field_name' => $field_name,
                  'uuid' => $uuid,
                ]),
              ],
              'delete' => [
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute('conditional_fields.delete_form', [
                  'entity_type' => $condition['entity_type'],
                  'bundle' => $condition['bundle'],
                  'field_name' => $field_name,
                  'uuid' => $uuid,
                ]),
              ],
            ],
          ],
        ];
      }
    }

    /* Row for creating new condition. */

    // Build list of states.
    $states = conditional_fields_states();

    // Build list of conditions.
    $conditions = [];
    foreach (conditional_fields_conditions() as $condition => $label) {
      $conditions[$condition] = $condition == 'value' ? $this->t('has value...') : $this->t('is !label', ['!label' => $label]);
    }

    // Add new dependency row.
    $form['table']['add_new_dependency'] = [
      'dependent' => [
        '#type' => 'select',
        '#title' => $this->t('Dependent'),
        '#title_display' => 'invisible',
        '#description' => $this->t('Dependent'),
        '#options' => $fields,
        '#prefix' => '<div class="add-new-placeholder">' . $this->t('Add new dependency') . '</div>',
        '#required' => TRUE,
      ],
      'dependee' => [
        '#type' => 'select',
        '#title' => $this->t('Dependee'),
        '#title_display' => 'invisible',
        '#description' => $this->t('Dependee'),
        '#options' => $fields,
        '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
        '#required' => TRUE,
      ],
      'state' => [
        '#type' => 'select',
        '#title' => $this->t('State'),
        '#title_display' => 'invisible',
        '#options' => $states,
        '#default_value' => 'visible',
        '#prefix' => $this->t('The dependent field is'),
      ],
      'condition' => [
        '#type' => 'select',
        '#title' => $this->t('Condition'),
        '#title_display' => 'invisible',
        '#options' => $conditions,
        '#default_value' => 'value',
        '#prefix' => $this->t('when the dependee'),
      ],
      'actions' => [
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Add dependency'),
        ],
      ],
    ];
    return $form['table'];
  }

  /**
   * Implements callback for Ajax event on entity bundle selection.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   *
   * @return array
   *   Fields section of the form.
   */
  public function conditionalFieldsCallback(array &$form, FormStateInterface $form_state) {
    return $form['entity_type_wrapper']['conditional_fields_wrapper'];
  }

  /**
   * Implements callback for Ajax event on entity type selection.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   *
   * @return array
   *   Fields section of the form.
   */
  public function entityTypeCallback(array &$form, FormStateInterface $form_state) {
    return $form['entity_type_wrapper'];
  }

  /**
   * Filter ContentEntity entity_types out of all entity_types.
   *
   * @param array $entity_types
   *   List of all EntityTypes available.
   *
   * @return array $entity_types
   *   Filtered list of available EntityTypes.
   */
  protected function filterContentEntityTypes(array $entity_types = []) {
    $entity_type_manager = \Drupal::entityTypeManager();
    foreach ($entity_types as $entity_type_id => $entity_type_label) {
      if ('_none' == $entity_type_id) {
        continue;
      }
      if (!($entity_type_manager->getStorage($entity_type_id)
        ->getEntityType()
        ->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface'))
      ) {
        unset($entity_types[$entity_type_id]);
      }
    }
    return $entity_types;
  }

}
