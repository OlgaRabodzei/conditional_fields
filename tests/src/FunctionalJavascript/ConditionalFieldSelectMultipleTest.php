<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\conditional_fields\FunctionalJavascript\ConditionalFieldBase as JavascriptTestBase;

/**
 * Test Conditional Fields SelectMultiple Plugin.
 *
 * @group conditional_fields
 */
class ConditionalFieldSelectMultipleTest extends JavascriptTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'conditional_fields',
    'node',
    'options',
  ];

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'test_options';

  protected $fieldSelector;

  /**
   * The field storage definition used to created the field storage.
   *
   * @var array
   */
  protected $fieldStorageDefinition;

  /**
   * The list field storage used in the test.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The list field used in the test.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * Tests creating Conditional Field: Visible if has value from taxonomy.
   */
  public function testCreateConfigVisibleValueAnd() {
    $user = $this->drupalCreateUser([
      'administer nodes',
      'administer content types',
      'view conditional fields',
      'edit conditional fields',
      'delete conditional fields',
      'create article content',
    ]);
    $this->drupalLogin($user);

    // Visit a ConditionalFields configuration page that requires login.
    $this->drupalGet('admin/structure/types');
    $this->assertSession()->statusCodeEquals(200);

    // Configuration page contains the `Content` entity type.
    $this->assertSession()->pageTextContains('Article Dependencies');

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('admin/structure/types/manage/article/conditionals', 'body', $this->fieldName, 'visible', 'value' );
    $this->createScreenshot('sites/simpletest/01-add-list-options-filed-conditions.png');

    // Set up conditions.
    $data = [
      '[name="condition"]' => 'value',
      '[name="values_set"]' => CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
      $this->fieldSelector => [0, 1],
      '[name="grouping"]' => 'AND',
      '[name="state"]' => 'visible',
      '[name="effect"]' => 'show',
      '[name="element_edit[1]"]' => 1,
      '[name="element_view[1]"]' => 1,
      '[name="element_view[2]"]' => 2,
    ];
    foreach ($data as $selector => $value) {
      $this->changeField($selector, $value);
    }
    $this->getSession()->wait(1000, '!jQuery.active');
    $this->getSession()->executeScript("jQuery('#conditional-field-edit-form-tab').submit();");
    $this->assertSession()->statusCodeEquals(200);
    $this->createScreenshot('sites/simpletest/02-post-add-list-options-filed-conditions.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot('sites/simpletest/03-submit-list-options-filed-conditions.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the field Body is not visible.
    $this->createScreenshot('sites/simpletest/04-body-invisible-when-controlled-field-has-no-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, [0]);
    $this->createScreenshot('sites/simpletest/05-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set to show the body.
    $this->changeField($this->fieldSelector, [0, 1]);
    $this->createScreenshot('sites/simpletest/06-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change a select value set to hide the body again.
    $this->changeField($this->fieldSelector, ['_none']);
    $this->createScreenshot('sites/simpletest/07-body-invisible-when-controlled-field-has-no-value-again.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
  }

  /**
   * Helper to change Field value with Javascript.
   */
  protected function changeField($selector, $value = '') {
    $value = json_encode($value);
    $this->getSession()->executeScript("jQuery('{$selector}').val({$value}).trigger('keyup').trigger('change');");
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->fieldSelector = "[name=\"{$this->fieldName}[]\"]";
    $this->fieldStorageDefinition = [
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'list_integer',
      'cardinality' => -1,
      'settings' => [
        'allowed_values' => ['One', 'Two', 'Three'],
      ],
    ];
    $this->fieldStorage = FieldStorageConfig::create($this->fieldStorageDefinition);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'article',
    ]);
    $this->field->save();

    entity_get_form_display('node', 'article', 'default')
      ->setComponent($this->fieldName, [
        'type' => 'options_select',
      ])
      ->save();
  }
}
