<?php

namespace Drupal\conditional_fields\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Render\Element;
use Drupal\conditional_fields\Conditions;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConditionalFieldEditForm.
 *
 * @package Drupal\conditional_fields\Form
 */
class ConditionalFieldEditForm extends FormBase {

  protected $redirectPath = 'conditional_fields.conditions_list';

  /**
   * @var Conditions $list
   */
  protected $list;

  /**
   * Class constructor.
   */
  public function __construct(Conditions $list) {
    $this->list = $list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('conditional_fields.conditions')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conditional_field_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $bundle = NULL, $field_name = NULL, $uuid = NULL) {

    if (empty($entity_type) || empty($bundle) || empty($field_name) || empty($uuid)) {
      return $form;
    }

    $form_display_entity = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load("$entity_type.$bundle.default");
    if (!$form_display_entity) {
      return $form;
    }
    // Retrieve first field from the list.
    $field = count(explode('-', $field_name)) > 0 ? explode('-', $field_name)[0] : $field_name;
    $field = $form_display_entity->getComponent($field);

    if (empty($field['third_party_settings']['conditional_fields'][$uuid])) {
      return $form;
    }
    $condition = $field['third_party_settings']['conditional_fields'][$uuid];
    $settings = $condition['settings'];
    // @TODO: it's not label but machine_name.
    $label = $condition['dependee'];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
      '#weight' => 50,
    ];

    // Save parameters for submit saving.
    $form['entity_type'] = [
      '#type' => 'textfield',
      '#value' => $entity_type,
      '#access' => FALSE,
    ];
    $form['bundle'] = [
      '#type' => 'textfield',
      '#value' => $bundle,
      '#access' => FALSE,
    ];
    $form['field_name'] = [
      '#type' => 'textfield',
      '#value' => $field_name,
      '#access' => FALSE,
    ];
    $form['uuid'] = [
      '#type' => 'textfield',
      '#value' => $uuid,
      '#access' => FALSE,
    ];

    $form['condition'] = [
      '#type' => 'select',
      '#title' => $this->t('Condition'),
      '#description' => $this->t('The condition that should be met by the dependee %field to trigger the dependency.', ['%field' => $label]),
      '#options' => $this->list->conditionalFieldsConditions(),
      '#default_value' => array_key_exists('condition', $settings) ? $settings['condition'] : '',
      '#required' => TRUE,
    ];

    $form['values_set'] = [
      '#type' => 'select',
      '#title' => $this->t('Values input mode'),
      '#description' => $this->t('The input mode of the values that trigger the dependency.'),
      '#options' => [
        CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET => $this->t('Insert value from widget...'),
        CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX => $this->t('Regular expression...'),
        'Set of values' => [
          CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND => $this->t('All these values (AND)...'),
          CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR => $this->t('Any of these values (OR)...'),
          CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR => $this->t('Only one of these values (XOR)...'),
          CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT => $this->t('None of these values (NOT)...'),
          // TODO: PHP evaluation.
        ],
      ],
      '#default_value' => array_key_exists('values_set', $settings) ? $settings['values_set'] : 0,
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="condition"]' => ['value' => 'value'],
        ],
      ],
    ];

    // @TODO: refactor this code.
    if (isset($settings[$label]) && is_array($settings[$label])) {
      if (is_int(key($settings[$label]))) {
        $dummy_field = $this->getDummyField($entity_type, $bundle, $condition, $form_state, $settings[$label]);
      }
      else {
        $dummy_field = $this->getDummyField($entity_type, $bundle, $condition, $form_state, reset($settings[$label]));
      }
    }
    else {
      $dummy_field = $this->getDummyField($entity_type, $bundle, $condition, $form_state);
    }

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
        'required' => [
          ':input[name="values_set"]' => [
            'value' => (string) CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
          ],
          ':input[name="condition"]' => ['value' => 'value'],
        ],
      ],
      '#tree' => FALSE,
      'field' => $dummy_field,
    ];

    $form['values'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Set of values'),
      '#description' => $this->t('The values of the dependee %field that trigger the dependency.', ['%field' => $label]) . '<br>' . $this->t('Enter one value per line. Note: if the dependee has allowed values, these are actually the keys, not the labels, of those values.'),
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
          ':input[name="values_set"]' => [
            ['value' => (string) CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND],
            ['value' => (string) CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR],
            ['value' => (string) CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR],
            ['value' => (string) CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT],
          ],
          ':input[name="condition"]' => ['value' => 'value'],
        ],
      ],
    ];

    if (!empty($settings['values']) && is_array($settings['values'])) {
      $form['values']['#default_value'] = implode("\n", $settings['values']);
    }
    elseif (!empty($settings['values']) && is_string($settings['values'])) {
      $form['values']['#default_value'] = $settings['values'];
    }
    else {
      $form['values']['#default_value'] = '';
    }

    $form['regex'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Regular expression'),
      '#description' => $this->t('The dependency is triggered when all the values of the dependee %field match the regular expression. The expression should be valid both in PHP and in Javascript. Do not include delimiters.', ['%field' => $label]) . '<br>' . $this->t('Note: If the dependee has allowed values, these are actually the keys, not the labels, of those values.'),
      '#maxlength' => 2048,
      '#size' => 120,
      '#default_value' => isset($settings['regex']) ? $settings['regex'] : '',
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
      '#default_value' => array_key_exists('grouping', $settings) ? $settings['grouping'] : 'AND',
      '#required' => TRUE,
    ];

    $form['entity_edit'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Edit context settings'),
      '#description' => $this->t('These settings apply when the @entity is being added or edited in a form.', ['@entity' => $label]),
      '#collapsible' => FALSE,
    ];

    $form['entity_edit'] += $this->buildEditContextSettings([], $form_state, $condition);

    $form['entity_view'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('View context settings'),
      '#description' => $this->t('These settings apply when the @entity is viewed.', ['@entity' => $label]),
      '#collapsible' => FALSE,
    ];

    $form['entity_view'] += $this->buildViewContextSettings([], $form_state, $condition);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $allowed_values_set = [
      CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND,
      CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR,
      CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR,
      CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT,
    ];
    if ($form_state->getValue('condition') == 'value') {
      if (in_array($form_state->getValue('values_set'), $allowed_values_set) &&
        Unicode::strlen(trim($form_state->getValue('values')) === 0)
      ) {
        $form_state->setErrorByName('values', $this->t('@name field is required.', ['@name' => $this->t('Set of values')]));
      }
      elseif ($form_state->getValue('values_set') == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX && Unicode::strlen(trim($form_state->getValue('regex'))) == 0) {
        $form_state->setErrorByName('regex', $this->t('@name field is required.', ['@name' => $this->t('Regular expression')]));
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Inprogress.
    $values = $form_state->cleanValues()->getValues();
    $uuid = $values['uuid'];
    $entity_type = $values['entity_type'];
    $bundle = $values['bundle'];

    /** @var EntityFormDisplay $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load("$entity_type.$bundle.default");
    if (!$entity) {
      return;
    }

    $field_names = explode('-', $values['field_name']);
    foreach ($field_names as $field_name) {
      $field = $entity->getComponent($field_name);

      $settings = &$field['third_party_settings']['conditional_fields'][$uuid]['settings'];

      $exclude_fields = [
        'entity_type',
        'bundle',
        'field_name',
        'uuid',
        // FIXME: provide saving for parameters below.
        'element_edit_roles',
        'element_view_roles',
      ];

      foreach ($values as $key => $value) {
        if (in_array($key, $exclude_fields) || empty($value)) {
          continue;
        }
        else {
          $settings[$key] = $value;
        }
      }

      if ($settings['effect'] == 'show') {
        $settings['effect_options'] = [];
      }

      $entity->setComponent($field_name, $field);
    }
    $entity->save();

    $parameters = [
      'entity_type' => $values['entity_type'],
      'bundle' => $values['bundle'],
    ];

    $form_state->setRedirect($this->redirectPath, $parameters);

  }

  /**
   * Builds Edit Context Settings block.
   */
  public function buildEditContextSettings(array $form, FormStateInterface $form_state, $condition) {
    $label = array_key_exists('dependee', $condition) ? $condition['dependee'] : '?';
    $settings = array_key_exists('settings', $condition) ? $condition['settings'] : [];

    $form['state'] = [
      '#type' => 'select',
      '#title' => $this->t('Form state'),
      '#description' => $this->t('The Javascript form state that is applied to the dependent field when the condition is met. Note: this has no effect on server-side logic and validation.'),
      '#options' => $this->list->conditionalFieldsStates(),
      '#default_value' => array_key_exists('state', $settings) ? $settings['state'] : 0,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxAdminStateCallback',
        'wrapper' => 'effects-wrapper',
      ],
    ];

    $effects = $effects_options = [];
    $selected_state = $form_state->getValue('state') ?: $condition['settings']['state'];
    foreach ($this->list->conditionalFieldsEffects() as $effect_name => $effect) {
      if (empty($selected_state)) {
        continue;
      }
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
    $effect = array_key_exists('effect', $settings) ? $settings['effect'] : '';
    $effect = $form_state->hasValue('effect') ? $form_state->getValue('effect') : $effect;

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
          '#title' => $this->t('@effect effect option: @effect_option', [
            '@effect' => $effects[$effect_name],
            '@effect_option' => $effect_option_name,
          ]),
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
        elseif ($settings['effect'] == $effect_name) {
          $effect_option['#default_value'] = $settings['effect_options'][$effect_name][$effect_option_name];
        }

        $form['effects_wrapper']['effect_options'][$effect_name][$effect_option_name] = $effect_option;
      }
    }

    $form['element_edit_per_role'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate per user role settings in edit context'),
      '#description' => $this->t('If the user has more than one role, the first matching role will be used.'),
      '#default_value' => array_key_exists('element_edit_per_role', $settings) ? $settings['element_edit_per_role'] : FALSE,
    ];

    $behaviors = $this->list->conditionalFieldsBehaviors();

    $form['element_edit'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Edit context settings for all roles'),
      '#title_display' => 'invisible',
      '#options' => $behaviors['edit'],
      '#default_value' => array_key_exists('element_edit', $settings) ? $settings['element_edit'] : 0,
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
        '#default_value' => isset($settings['element_edit_roles'][$rid]) ? $settings['element_edit_roles'][$rid] : $settings['element_edit'],
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
      '#default_value' => array_key_exists('selector', $settings) ? $settings['selector'] : '',
    ];

    return $form;
  }

  /**
   * Builds View Context Settings block.
   */
  public function buildViewContextSettings(array $form, FormStateInterface $form_state, $condition) {
    $settings = array_key_exists('settings', $condition) ? $condition['settings'] : [];

    $form['element_view_per_role'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate per user role settings in view context'),
      '#description' => $this->t('If the user has more than one role, the first matching role will be used.'),
      '#default_value' => array_key_exists('element_view_per_role', $settings) ? $settings['element_view_per_role'] : 0,
    ];

    $behaviors = $this->list->conditionalFieldsBehaviors();

    $form['element_view'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('View context settings for all roles'),
      '#title_display' => 'invisible',
      '#description' => $this->t('Note: Options that need to evaluate if the dependency is triggered only apply if the condition is "Value", "Empty", or "Filled".'),
      '#options' => $behaviors['view'],
      '#default_value' => array_key_exists('element_view', $settings) ? $settings['element_view'] : 0,
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
        '#default_value' => isset($settings['element_view_roles'][$rid]) ? $settings['element_view_roles'][$rid] : $settings['element_view'],
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
  public function ajaxAdminStateCallback(array $form, FormStateInterface $form_state) {
    return $form['entity_edit']['effects_wrapper'];
  }

  /**
   * Creates dummy field instance.
   */
  protected function getDummyField($entity_type, $bundle, $condition, FormStateInterface $form_state, $default_value = NULL) {
    $field_name = $condition['dependee'];
    $dummy_field = [];

    $entityTypeManager = \Drupal::entityTypeManager();
    $storage = $entityTypeManager->getStorage($entity_type);
    $bundle_key = $storage->getEntityType()->getKey('bundle');

    $dummy_entity = $storage->create([
      'uid' => \Drupal::currentUser()->id(),
      $bundle_key => $bundle,
    ]);

    // Set current value.
    if ($default_value) {
      $dummy_entity->set($field_name, $default_value);
    }

    try {
      // Be able to add new and edit existing conditional fields on entities,
      // where "edit" form class is not defined.
      $handlers = $entityTypeManager->getDefinition($entity_type)->getHandlerClasses();
      $operation = isset($handlers['form']['edit']) ? 'edit' : 'default';
      $form_object = $entityTypeManager->getFormObject($entity_type, $operation);
      $form_object->setEntity($dummy_entity);
    } catch (InvalidPluginDefinitionException $e) {
      watchdog_exception('conditional_fields', $e);
      // @TODO May be it make sense to return markup?
      return NULL;
    }

    $form_builder_service = \Drupal::service('form_builder');
    $form_state_additions = [];
    $form_state_new = (new FormState())->setFormState($form_state_additions);

    if ($form_state->isMethodType("POST")) {
      $form_state_new->setRequestMethod("POST");
    }

    // Set Submitted value.
    $user_input = $form_state->getUserInput();
    if (isset($user_input[$field_name])) {
      // Set field value.
      $form_state_new->setUserInput([$field_name => $user_input[$field_name]]);
      $form_state_new->setProgrammed(TRUE);
      $form_state_new->setValidationComplete(TRUE);
    }

    $dummy_entity_form = $form_builder_service->buildForm($form_object, $form_state_new);
    if (isset($dummy_entity_form[$field_name])) {
      $dummy_field = $dummy_entity_form[$field_name];
      // Unset required for dummy field in case field will be hidden.
      $this->setFieldProperty($dummy_field, '#required', FALSE);
    }

    return $dummy_field;
  }

  /**
   * Set render array property and all child elements.
   */
  protected function setFieldProperty(&$field, $property, $value) {
    $elements = Element::children($field);
    if (isset($elements) && count($elements) > 0) {
      foreach ($elements as $element) {
        $field[$element][$property] = $value;
        $this->setFieldProperty($field[$element], $property, $value);
      }
    }
  }

}
