<?php

namespace Drupal\Tests\conditional_fields\Functional;

use Drupal\Tests\BrowserTestBase;

// use Drupal\Component\Utility\Unicode;
// use Drupal\field\Entity\FieldStorageConfig;
// use Drupal\field\Entity\FieldConfig;

/**
 * Tests BrowserTestBase functionality.
 *
 * @group conditional_fields
 */
abstract class ConditionalFieldBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'conditional_fields',
    'node',
    'datetime',
    'field_ui',
    'field_test',
    'taxonomy',
    'image',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array(
        'type' => 'page',
        'name' => 'Basic page',
        'display_submitted' => FALSE,
      ));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }
    $this->accessHandler = \Drupal::entityManager()->getAccessControlHandler('node');

    // // Create content type, with underscores.
    // $type_name = strtolower($this->randomMachineName(8)) . '_test';
    // $type = $this->drupalCreateContentType(array('name' => $type_name, 'type' => $type_name));
    // $this->contentType = $type->id();

    // // Create random field name with markup to test escaping.
    // $this->fieldLabel = '<em>' . $this->randomMachineName(8) . '</em>';
    // $this->fieldNameInput = strtolower($this->randomMachineName(8));
    // $this->fieldName = 'field_' . $this->fieldNameInput;

    // // Create Basic page and Article node types.
    // $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    // $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));

    // // Create a vocabulary named "Tags".
    // $vocabulary = Vocabulary::create(array(
    //   'name' => 'Tags',
    //   'vid' => 'tags',
    //   'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    // ));
    // $vocabulary->save();

    // $handler_settings = array(
    //   'target_bundles' => array(
    //     $vocabulary->id() => $vocabulary->id(),
    //   ),
    // );
    // $this->createEntityReferenceField('node', 'article', 'field_' . $vocabulary->id(), 'Tags', 'taxonomy_term', 'default', $handler_settings);

    // entity_get_form_display('node', 'article', 'default')
    //   ->setComponent('field_' . $vocabulary->id())
    //   ->save();

    // // Create a checkbox field for test.
    // $on = $this->randomMachineName();
    // $off = $this->randomMachineName();
    // $label = $this->randomMachineName();

    // // Create a field with settings to validate.
    // $field_name = Unicode::strtolower($this->randomMachineName());
    // // $this->determinator_field_name = $field_name;
    // $this->fieldStorage = FieldStorageConfig::create(array(
    //   'field_name' => $field_name,
    //   'entity_type' => 'node',
    //   'type' => 'boolean',
    // ));
    // $this->determinator_fieldStorage->save();
    // $this->determinator_field = FieldConfig::create(array(
    //   'field_name' => $field_name,
    //   'entity_type' => 'node',
    //   'bundle' => 'article',
    //   'label' => $label,
    //   'required' => TRUE,
    //   'settings' => array(
    //     'on_label' => $on,
    //     'off_label' => $off,
    //   ),
    // ));
    // $this->determinator_field->save();

    // // Create a form display for the default form mode.
    // entity_get_form_display('node', 'article', 'default')
    //   ->setComponent($field_name, array(
    //     'type' => 'boolean_checkbox',
    //   ))
    //   ->save();
    // // Create a display for the full view mode.
    // entity_get_display('node', 'article', 'full')
    //   ->setComponent($field_name, array(
    //     'type' => 'boolean',
    //   ))
    //   ->save();

  }

}
