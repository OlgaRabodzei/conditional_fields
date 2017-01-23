<?php

namespace Drupal\conditional_fields;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provide conditional field's lists.
 */
class Conditions {

  use StringTranslationTrait;

  /**
   * Provides default options for a dependency.
   */
  public function conditionalFieldsDependencyDefaultSettings() {
    return array(
      'state' => 'visible',
      'condition' => 'value',
      'grouping' => 'AND',
      'values_set' => CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
      // !Important, the param default value MUST match to schema declaration,
      // @see conditional_fields.schema.yml
      // 'value' => array(),
      'value' => '',
      'values' => array(),
      'value_form' => array(),
      'effect' => 'show',
      'effect_options' => array(),
      'element_view' => array(
        CONDITIONAL_FIELDS_FIELD_VIEW_EVALUATE => CONDITIONAL_FIELDS_FIELD_VIEW_EVALUATE,
        CONDITIONAL_FIELDS_FIELD_VIEW_HIDE_ORPHAN => CONDITIONAL_FIELDS_FIELD_VIEW_HIDE_ORPHAN,
        CONDITIONAL_FIELDS_FIELD_VIEW_HIDE_UNTRIGGERED_ORPHAN => 0,
        CONDITIONAL_FIELDS_FIELD_VIEW_HIGHLIGHT => 0,
        CONDITIONAL_FIELDS_FIELD_VIEW_DESCRIBE => 0,
      ),
      'element_view_per_role' => 0,
      'element_view_roles' => array(),
      'element_edit' => array(
        CONDITIONAL_FIELDS_FIELD_EDIT_HIDE_ORPHAN => CONDITIONAL_FIELDS_FIELD_EDIT_HIDE_ORPHAN,
        CONDITIONAL_FIELDS_FIELD_EDIT_HIDE_UNTRIGGERED_ORPHAN => 0,
        CONDITIONAL_FIELDS_FIELD_EDIT_RESET_UNTRIGGERED => 0,
      ),
      'element_edit_per_role' => 0,
      'element_edit_roles' => array(),
      'selector' => '',
    );
  }

  /**
   * Builds a list of supported states that may be applied to a dependent field.
   */
  public function conditionalFieldsStates() {
    $states = array(
      // Supported by States API.
      'visible' => $this->t('Visible'),
      '!visible' => $this->t('Invisible'),
      '!empty' => $this->t('Filled with a value'),
      'empty' => $this->t('Emptied'),
      '!disabled' => $this->t('Enabled'),
      'disabled' => $this->t('Disabled'),
      'checked' => $this->t('Checked'),
      '!checked' => $this->t('Unchecked'),
      'required' => $this->t('Required'),
      '!required' => $this->t('Optional'),
      '!collapsed' => $this->t('Expanded'),
      'collapsed' => $this->t('Collapsed'),
      // Supported by Conditional Fields.
      'unchanged' => $this->t('Unchanged (no state)'),
      // TODO: Add support to these states:
      /*
      'relevant'   => $this->t('Relevant'),
      '!relevant'  => $this->t('Irrelevant'),
      'valid'      => $this->t('Valid'),
      '!valid'     => $this->t('Invalid'),
      'touched'    => $this->t('Touched'),
      '!touched'   => $this->t('Untouched'),
      '!readonly'  => $this->t('Read/Write'),
      'readonly'   => $this->t('Read Only'),
      */
    );

    // Allow other modules to modify the states.
    \Drupal::moduleHandler()->alter('conditionalFieldsStates', $states);

    return $states;
  }

  /**
   * Builds a list of supported effects.
   *
   * That may be applied to a dependent field
   * when it changes from visible to invisible and viceversa. The effects may
   * have options that will be passed as Javascript settings and used by
   * conditional_fields.js.
   *
   * @return array
   *   An associative array of effects. Each key is an unique name for the effect.
   *   The value is an associative array:
   *   - label: The human readable name of the effect.
   *   - states: The states that can be associated with this effect.
   *   - options: An associative array of effect options names, field types,
   *     descriptions and default values.
   */
  public function conditionalFieldsEffects() {
    $effects = array(
      'show' => array(
        'label' => $this->t('Show/Hide'),
        'states' => array('visible', '!visible'),
      ),
      'fade' => array(
        'label' => $this->t('Fade in/Fade out'),
        'states' => array('visible', '!visible'),
        'options' => array(
          'speed' => array(
            '#type' => 'textfield',
            '#description' => $this->t('The speed at which the animation is performed, in milliseconds.'),
            '#default_value' => 400,
          ),
        ),
      ),
      'slide' => array(
        'label' => $this->t('Slide down/Slide up'),
        'states' => array('visible', '!visible'),
        'options' => array(
          'speed' => array(
            '#type' => 'textfield',
            '#description' => $this->t('The speed at which the animation is performed, in milliseconds.'),
            '#default_value' => 400,
          ),
        ),
      ),
      'fill' => array(
        'label' => $this->t('Fill field with a value'),
        'states' => array('!empty'),
        'options' => array(
          'value' => array(
            '#type' => 'textfield',
            '#description' => $this->t('The value that should be given to the field when automatically filled.'),
            '#default_value' => '',
          ),
          'reset' => array(
            '#type' => 'checkbox',
            '#title' => $this->t('Restore previous value when untriggered'),
            '#default_value' => 1,
          ),
        ),
      ),
      'empty' => array(
        'label' => $this->t('Empty field'),
        'states' => array('empty'),
        'options' => array(
          'value' => array(
            '#type' => 'hidden',
            '#description' => $this->t('The value that should be given to the field when automatically emptied.'),
            '#value' => '',
            '#default_value' => '',
          ),
          'reset' => array(
            '#type' => 'checkbox',
            '#title' => $this->t('Restore previous value when untriggered'),
            '#default_value' => 1,
          ),
        ),
      ),
    );

    // Allow other modules to modify the effects.
    \Drupal::moduleHandler()->alter('conditionalFieldsEffects', $effects);

    return $effects;
  }

  /**
   * List of states of a dependee field that may be used to evaluate a condition.
   */
  public function conditionalFieldsConditions($checkboxes = TRUE) {
    // Supported by States API.
    $conditions = array(
      '!empty' => $this->t('Filled'),
      'empty' => $this->t('Empty'),
      'touched' => $this->t('Touched'),
      '!touched' => $this->t('Untouched'),
      'focused' => $this->t('Focused'),
      '!focused' => $this->t('Unfocused'),
    );

    if ($checkboxes) {
      // Relevant only if dependee is a list of checkboxes.
      $conditions['checked'] = $this->t('Checked');
      $conditions['!checked'] = $this->t('Unchecked');
    }

    $conditions['value'] = $this->t('Value');

    // TODO: Add support from Conditional Fields to these conditions
    /*
    '!disabled'  => $this->t('Enabled'),
    'disabled'   => $this->t('Disabled'),
    'required'   => $this->t('Required'),
    '!required'  => $this->t('Optional'),
    'relevant'   => $this->t('Relevant'),
    '!relevant'  => $this->t('Irrelevant'),
    'valid'      => $this->t('Valid'),
    '!valid'     => $this->t('Invalid'),
    '!readonly'  => $this->t('Read/Write'),
    'readonly'   => $this->t('Read Only'),
    '!collapsed' => $this->t('Expanded'),
    'collapsed'  => $this->t('Collapsed'),
     */

    // Allow other modules to modify the conditions.
    \Drupal::moduleHandler()
      ->alter('conditionalFieldsConditions', $conditions);

    return $conditions;
  }

  /**
   * List of behaviors that can be applied when editing forms and viewing content
   * with dependencies.
   */
  public function conditionalFieldsBehaviors($op = NULL) {
    $behaviors = array(
      'edit' => array(
        CONDITIONAL_FIELDS_FIELD_EDIT_HIDE_ORPHAN => $this->t('Hide the dependent if the dependee is not in the form'),
        CONDITIONAL_FIELDS_FIELD_EDIT_RESET_UNTRIGGERED => $this->t('Reset the dependent to its default values when the form is submitted if the dependency is not triggered.') . '<br /><em>' . $this->t('Note: This setting only applies if the condition is "Value", "Empty", or "Filled" and may not work with some field types. Also, ensure that the default values are valid, since they will not be validated.') . '</em>',
        // TODO: Implement. Settings are imported from D6 though, they just do nothing for now.
        /*
        CONDITIONAL_FIELDS_FIELD_EDIT_HIDE_UNTRIGGERED_ORPHAN => $this->t('Hide the dependent if the dependee is not in the form and the dependency is not triggered.') . '<br /><em>' . $this->t('Note: This setting is not currently not implemented and has no effect.') . '</em>',
        */
      ),
      'view' => array(
        CONDITIONAL_FIELDS_FIELD_VIEW_EVALUATE => $this->t('Hide the dependent if the dependency is not triggered'),
        CONDITIONAL_FIELDS_FIELD_VIEW_HIDE_ORPHAN => $this->t('Hide the dependent if the dependee is not viewable by the user'),
        CONDITIONAL_FIELDS_FIELD_VIEW_HIDE_UNTRIGGERED_ORPHAN => $this->t('Hide the dependent if the dependee is not viewable by the user and the dependency is not triggered'),
        CONDITIONAL_FIELDS_FIELD_VIEW_HIGHLIGHT => $this->t('Theme the dependent like an error message if the dependency is not triggered'),
        CONDITIONAL_FIELDS_FIELD_VIEW_DESCRIBE => $this->t('Show a textual description of the dependency under the dependent'),
      ),
    );

    // Allow other modules to modify the options.
    \Drupal::moduleHandler()->alter('conditionalFieldsBehaviors', $behaviors);

    if (isset($behaviors[$op])) {
      return $behaviors[$op];
    }

    return $behaviors;
  }

}
