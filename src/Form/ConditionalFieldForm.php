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
    return 'conditional_field_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $bundle = NULL) {
    module_load_include('inc', 'conditional_fields', 'conditional_fields.conditions');

    $form['entity_type'] = [
      '#type' => 'hidden',
      '#value' => $entity_type,
    ];

    $form['bundle'] = [
      '#type' => 'hidden',
      '#value' => $bundle,
    ];

    $form['conditional_fields_wrapper']['table'] = $this->buildTable($form, $form_state, $entity_type, $bundle);

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
    $settings = conditional_fields_dependency_default_settings();
    foreach ($conditional_values as $key => $value) {
      if ($key == 'dependent') {
        $field_name = $value;
        continue;
      }
      if (in_array($key, ['entity_type', 'bundle', 'dependee'])) {
        $component_value[$key] = $value;
        continue;
      }
      // @TODO: it seems reasonable 
      // to only set values allowed by field schema,
      // @see conditional_fields.schema.yml
      $settings[$key] = $value;
    }
    unset($settings['actions']);
    $component_value['settings'] = $settings;

    $component_value['entity_type'] = $form_state->getValue('entity_type');
    $component_value['bundle'] = $form_state->getValue('bundle');

    $uuid = $form_state->hasValue('uuid') ? $form_state->getValue('uuid') : \Drupal::service('uuid')
      ->generate();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($component_value['entity_type'] . '.' . $component_value['bundle'] . '.' . 'default');
    if (!$entity) {
      return;
    }

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

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display_entity */
    $form_display_entity = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load("$entity_type.$bundle_name.default");

    if (!$form_display_entity) {
      return $form['table'];
    }

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
      $label = (string) $label;
      $conditions[$condition] = $condition == 'value' ? $this->t('has value...') : $this->t('is @label', ['@label' => (string) $label]);
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
        '#attributes' => ['class' => ['conditional-fields-selector']],
      ],
      'dependee' => [
        '#type' => 'select',
        '#title' => $this->t('Dependee'),
        '#title_display' => 'invisible',
        '#description' => $this->t('Dependee'),
        '#options' => $fields,
        '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
        '#required' => TRUE,
        '#attributes' => ['class' => ['conditional-fields-selector']],
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

    $form['table']['#attached']['library'][] = 'conditional_fields/admin';

    return $form['table'];
  }

}
