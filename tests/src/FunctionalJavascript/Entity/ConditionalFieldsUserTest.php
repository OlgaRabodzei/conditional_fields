<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript\Entity;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\conditional_fields\FunctionalJavascript\ConditionalFieldBase as JavascriptTestBase;

/**
 * Test Conditional Fields check User entity.
 *
 * @group conditional_fields
 */
class ConditionalFieldsUserTest extends JavascriptTestBase {

  protected $dependee = 'field_dependee';
  protected $dependent = 'field_dependent';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'conditional_fields',
    'field_ui',
  ];

  public function testUserEntity() {
    $user = $this->drupalCreateUser([
      'administer users',
      'administer account settings',
      'view conditional fields',
      'edit conditional fields',
      'delete conditional fields',
    ]);
    $this->drupalLogin($user);
    $this->createCondition('admin/structure/conditional_fields/user/user', $this->dependent, $this->dependee, 'visible', 'checked');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/user/user');
    $this->createScreenshot('sites/simpletest/01-config-was-added.png');
    $this->assertSession()->pageTextContains($this->dependent . ' ' . $this->dependee . ' visible checked');

    // Visit user register form to check that conditions are applied.
    $this->drupalGet('admin/people/create');
    $this->assertSession()->statusCodeEquals(200);
    $this->createScreenshot('sites/simpletest/02-mail-not-visible.png');
    $this->waitUntilHidden('.field--name-field-dependent', 50, 'Dependent field is not visible');
    $this->changeSelect('#edit-field-dependee-value', TRUE);
    $this->createScreenshot('sites/simpletest/03-mail-visible.png');
    $this->waitUntilVisible('.field--name-field-dependent', 50, 'Dependent field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->addField($this->dependee, 'boolean', 'boolean_checkbox');
    $this->addField($this->dependent, 'text', 'text_textfield');
  }

  protected function addField($field_name, $type, $widget) {
    $fieldStorageDefinition = [
      'field_name' => $field_name,
      'entity_type' => 'user',
      'type' => $type,
      'cardinality' => -1,
    ];
    $fieldStorage = FieldStorageConfig::create($fieldStorageDefinition);
    $fieldStorage->save();

    $field = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'user',
    ]);
    $field->save();
    entity_get_form_display('user', 'user', 'default')
      ->setComponent($field_name, [
        'type' => $widget,
      ])
      ->save();
  }
}
