<?php

namespace Drupal\conditional_fields\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;

/**
 * Form controller for Conditional field edit forms.
 *
 * @ingroup conditional_fields
 */
class ConditionalFieldEditForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    module_load_include('inc', 'conditional_fields', 'conditional_fields.conditions');
    $form = parent::buildForm($form, $form_state);

    // Disable entity_type.
    $form['entity_type']['widget']['#attributes']['disabled'] = TRUE;

    $options = array_shift($this->entity->options->getValue());
    //$checkboxes = ($dependee_instance['widget']['type'] == 'options_buttons' && $dependee['cardinality'] != 1) || $dependee_instance['widget']['type'] == 'options_onoff' ? TRUE : FALSE;
    $label = $this->entity->getDependee();

    // TODO: Build a dummy field widget to use as form field in single value selection
    // option.

    $form['condition'] = [
      '#type' => 'select',
      '#title' => $this->t('Condition'),
      '#description' => $this->t('The condition that should be met by the dependee %field to trigger the dependency.', ['%field' => $label]),
      '#options' => conditional_fields_conditions(),
      //$checkboxes),
      '#default_value' => array_key_exists('condition', $options) ? $options['condition'] : '',
      '#required' => TRUE,
    ];

    $form['values_set'] = [
      '#type' => 'select',
      '#title' => $this->t('Values input mode'),
      '#description' => $this->t('The input mode of the values that trigger the dependency.'),
      '#options' => array(
        CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET => $this->t('Insert value from widget...'),
        CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX => $this->t('Regular expression...'),
        'Set of values' => [
          CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND => $this->t('All these values (AND)...'),
          CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR => $this->t('Any of these values (OR)...'),
          CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR => $this->t('Only one of these values (XOR)...'),
          CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT => $this->t('None of these values (NOT)...'),
          // TODO: PHP evaluation.
        ],
      ),
      '#default_value' => array_key_exists('values_set', $options) ? $options['values_set'] : 0,
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="condition"]' => ['value' => 'value'],
        ],
      ],
    ];

    $form['value'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Insert value from widget'),
      '#description' => $this->t('The dependency is triggered when the field has exactly the same value(s) inserted in the widget below.'),
      '#states' => [
        'visible' => [
          ':input[name="values_set"]' => [
            'value' => (string) CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
          ],
          ':input[name="condition"]' => ['value' => 'value'],
        ],
      ],
      '#tree' => TRUE,
      // 'field' => $dummy_field,
    ];

    $form['values'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Set of values'),
      '#description' => $this->t('The values of the dependee %field that trigger the dependency.', ['%field' => $label]) . '<br>' . $this->t('Enter one value per line. Note: if the dependee has allowed values, these are actually the keys, not the labels, of those values.'),
      '#default_value' => array_key_exists('values', $options) ? implode("\n", $options['values']) : '',
      '#states' => [
        'visible' => [
          ':input[name="values_set"]' => [
            ['value' => (string) CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND],
            ['value' => (string) CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR],
            ['value' => (string) CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR],
            ['value' => (string) CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT],
          ],
          ':input[name="condition"]' => ['value' => 'value'],
        ],
        'required' => [
          ':input[name="condition"]' => ['value' => 'value'],
        ],
      ],
    ];

    $form['regex'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Regular expression'),
      '#description' => $this->t('The dependency is triggered when all the values of the dependee %field match the regular expression. The expression should be valid both in PHP and in Javascript. Do not include delimiters.', ['%field' => $label]) . '<br>' . $this->t('Note: If the dependee has allowed values, these are actually the keys, not the labels, of those values.'),
      '#maxlength' => 2048,
      '#size' => 120,
      '#default_value' => isset($options['value']['RegExp']) ? $options['value']['RegExp'] : '',
      '#states' => [
        'visible' => [
          ':input[name="values_set"]' => ['value' => (string) CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX],
          ':input[name="condition"]' => ['value' => 'value'],
        ],
        'required' => [
          ':input[name="values_set"]' => ['value' => (string) CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX],
          ':input[name="condition"]' => ['value' => 'value'],
        ],
      ],
    ];

    $form['grouping'] = [
      '#type' => 'radios',
      '#title' => $this->t('Interaction with other dependencies'),
      '#description' => $this->t('When this dependent has more than one dependee, how should this condition be evaluated against the others?') . '<br />' . $this->t('Note that sets will be grouped this way: (ANDs) AND (ORs) AND (XORs).'),
      '#options' => ['AND' => 'AND', 'OR' => 'OR', 'XOR' => 'XOR'],
      '#default_value' => array_key_exists('grouping', $options) ? $options['grouping'] : 'AND',
      '#required' => TRUE,
    ];

    $form['entity_edit'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Edit context settings'),
      '#description' => $this->t('These settings apply when the @entity is being added or edited in a form.', ['@entity' => $label]),
      '#collapsible' => FALSE,
    ];

    $form['entity_edit'] += $this->buildEditContextSettings([], $form_state);

    $form['entity_view'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('View context settings'),
      '#description' => $this->t('These settings apply when the @entity is viewed.', ['@entity' => $label]),
      '#collapsible' => FALSE,
    ];

    $form['entity_view'] += $this->buildViewContextSettings([], $form_state);

    return $form;

  }

  /**
   * Builds Edit Context Settings block.
   */
  public function buildEditContextSettings(array $form, FormStateInterface $form_state) {
    module_load_include('inc', 'conditional_fields', 'conditional_fields.conditions');
    $options = array_shift($this->entity->options->getValue());
    $label = $this->entity->dependee->value;

    $form['state'] = [
      '#type' => 'select',
      '#title' => $this->t('Form state'),
      '#description' => t('The Javascript form state that is applied to the dependent field when the condition is met. Note: this has no effect on server-side logic and validation.'),
      '#options' => conditional_fields_states(),
      '#default_value' => array_key_exists('state', $options) ? $options['state'] : 0,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxAdminStateCallback',
        'wrapper' => 'effects-wrapper',
      ],
    ];

    $effects = $effects_options = [];
    // TODO Rewrite statement.
    $selected_state = $form_state->hasValue('state') ? $form_state->getValue('state') : $options['state'];
    foreach (conditional_fields_effects() as $effect_name => $effect) {
      if (in_array($selected_state, $effect['states'])) {
        $effects[$effect_name] = $effect['label'];
        if (isset($effect['options'])) {
          $effects_options[$effect_name] = $effect['options'];
        }
      }
    }

    $form['effects_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'effects-wrapper',
      ],
    ];
    // TODO Rewrite statement.
    $effect = $form_state->hasValue('effect') ? $form_state->getValue('effect') : $options['effect'];

    if (count($effects) == 1) {
      $effects_keys = array_keys($effects);
      $form['effects_wrapper']['effect'] = [
        '#type' => 'hidden',
        '#value' => array_shift($effects_keys),
        '#default_value' => array_shift($effects_keys),
      ];
    }
    elseif (count($effects) > 1) {
      $form['effects_wrapper']['effect'] = [
        '#type' => 'select',
        '#title' => $this->t('Effect'),
        '#description' => $this->t('The effect that is applied to the dependent when its state is changed.'),
        '#options' => $effects,
        '#default_value' => $effect,
        '#states' => [
          'visible' => [
            ':input[name="state"]' => [
              ['value' => 'visible'],
              ['value' => '!visible'],
            ],
          ],
        ],
      ];
    }

    $form['effects_wrapper']['effect_options'] = ['#tree' => TRUE];

    foreach ($effects_options as $effect_name => $effect_options) {
      foreach ($effect_options as $effect_option_name => $effect_option) {
        $effect_option += [
          '#title' => t('@effect effect option: @effect_option', array(
            '@effect' => $effects[$effect_name],
            '@effect_option' => $effect_option_name,
          )),
          '#states' => [
            'visible' => [
              ':input[name="effect"]' => [
                ['value' => $effect_name],
              ],
            ],
          ],
        ];

        if (isset($form_state->getValue('effect_options')[$effect_name][$effect_option_name])) {
          $effect_option['#default_value'] = $form_state->getValue('effect_options')[$effect_name][$effect_option_name];
        }
        elseif ($options['effect'] == $effect_name) {
          $effect_option['#default_value'] = $options['effect_options'][$effect_name][$effect_option_name];
        }

        $form['effects_wrapper']['effect_options'][$effect_name][$effect_option_name] = $effect_option;
      }
    }

    $form['element_edit_per_role'] = [
      '#type' => 'checkbox',
      '#title' => t('Activate per user role settings in edit context'),
      '#description' => t('If the user has more than one role, the first matching role will be used.'),
      '#default_value' => $options['element_edit_per_role'],
    ];

    $behaviors = conditional_fields_behaviors();

    $form['element_edit'] = [
      '#type' => 'checkboxes',
      '#title' => t('Edit context settings for all roles'),
      '#title_display' => 'invisible',
      '#options' => $behaviors['edit'],
      '#default_value' => $options['element_edit'],
      '#states' => [
        'visible' => [
          ':input[name="element_edit_per_role"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $roles = user_roles();
    $element_edit_roles = ['element_edit_roles' => ['#tree' => TRUE]];
    foreach ($roles as $rid => $role) {
      $element_edit_roles['element_edit_roles'][$rid] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Edit context settings for %role', ['%role' => $role->label()]),
        '#options' => $behaviors['edit'],
        '#default_value' => isset($options['element_edit_roles'][$rid]) ? $options['element_edit_roles'][$rid] : $options['element_edit'],
        '#states' => [
          'visible' => [
            ':input[name="element_edit_per_role"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    $form += $element_edit_roles;

    $form['dependency_advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced edit context settings', ['@entity' => $label]),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $selector_description = $this->t('Only use if you know what you are doing, otherwise leave the field empty to let the dependency use an automatically generated selector.');
    $selector_description .= '<br />' . $this->t('You can use the following placeholders:');
    $selector_description .= "<ul>\n";
    $selector_description .= '<li>' . $this->t('%lang: current language of the field.') . "</li>\n";
    $selector_description .= '<li>' . $this->t('%key: part identifier for fields composed of multiple form elements, like checkboxes.') . "</li>\n";
    $selector_description .= '</ul>';

    $form['dependency_advanced']['selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom jQuery selector for dependee'),
      '#description' => $selector_description,
      '#default_value' => $options['selector'],
    ];
    return $form;
  }

  /**
   * Builds View Context Settings block.
   */
  public function buildViewContextSettings(array $form, FormStateInterface $form_state) {
    module_load_include('inc', 'conditional_fields', 'conditional_fields.conditions');
    $options = array_shift($this->entity->options->getValue());

    $form['element_view_per_role'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate per user role settings in view context'),
      '#description' => $this->t('If the user has more than one role, the first matching role will be used.'),
      '#default_value' => $options['element_view_per_role'],
    ];

    $behaviors = conditional_fields_behaviors();

    $form['element_view'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('View context settings for all roles'),
      '#title_display' => 'invisible',
      '#description' => $this->t('Note: Options that need to evaluate if the dependency is triggered only apply if the condition is "Value", "Empty", or "Filled".'),
      '#options' => $behaviors['view'],
      '#default_value' => $options['element_view'],
      '#states' => [
        'visible' => [
          ':input[name="element_view_per_role"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $roles = user_roles();

    $element_view_roles = ['element_view_roles' => ['#tree' => TRUE]];
    foreach ($roles as $rid => $role) {
      $element_view_roles['element_view_roles'][$rid] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('View context settings for %role', ['%role' => $role->label()]),
        '#options' => $behaviors['view'],
        '#default_value' => isset($options['element_view_roles'][$rid]) ? $options['element_view_roles'][$rid] : $options['element_view'],
        '#states' => [
          'visible' => [
            ':input[name="element_view_per_role"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    $form += $element_view_roles;

    return $form;
  }

  /**
   * Ajax callback for effects list.
   */
  protected function ajaxAdminStateCallback(array $form, FormStateInterface $form_state) {
    return $form['entity_edit']['effects_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('condition') == 'value') {
      if (in_array($form_state->getValue('values_set'), [
          CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND,
          CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR,
          CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR,
          CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT,
        ]) &&
        Unicode::strlen(trim($form_state->getValue('values')) == 0)
      ) {
        $form_state->setErrorByName('values', $this->t('!name field is required.', ['!name' => $this->t('Set of values')]));
      }
      elseif ($form_state->getValue('values_set') == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX && Unicode::strlen(trim($form_state->getValue('regex'))) == 0) {
        $form_state->setErrorByName('regex', $this->t('!name field is required.', ['!name' => $this->t('Regular expression')]));
      }
    }
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $options = $form_state->cleanValues()->getValues();
    $form_state->setValues([]);
    $form_state->setValue('options', $options);
    parent::submitForm($form, $form_state);
  }

}
