<?php

/**
 * @file
 * Contains \Drupal\conditional_fields\Form\ConditionalFieldSettingsForm.
 */

namespace Drupal\conditional_fields\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConditionalFieldSettingsForm.
 *
 * @package Drupal\conditional_fields\Form
 *
 * @ingroup conditional_fields
 */
class ConditionalFieldSettingsForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'ConditionalField_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }


  /**
   * Defines the settings form for Conditional field entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['ConditionalField_settings']['#markup'] = 'Settings form for Conditional field entities. Manage field settings here.';
    return $form;
  }

}
