<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldValueInterface;

/**
 * Test Conditional Fields Select Plugin.
 *
 * @group conditional_fields
 */
class ConditionalFieldSelectTest extends ConditionalFieldTestBase implements ConditionalFieldValueInterface {

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
   * {@inheritdoc}
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/select/';

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'single_select';

  /**
   * Jquery selector of field in a document.
   *
   * @var string
   */
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
   * The field to use in this test.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->fieldSelector = "[name=\"{$this->fieldName}\"]";
    $this->fieldStorageDefinition = [
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'list_integer',
      'cardinality' => 1,
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

    EntityFormDisplay::load('node.article.default')
      ->setComponent($this->fieldName, ['type' => 'options_select'])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueWidget() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-select-single-add-filed-conditions.png');

    // Set up conditions.
    $data = [
      '[name="condition"]' => 'value',
      '[name="values_set"]' => CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
      $this->fieldSelector => 2,
      '[name="grouping"]' => 'AND',
      '[name="state"]' => 'visible',
      '[name="effect"]' => 'show',
    ];
    foreach ($data as $selector => $value) {
      $this->changeField($selector, $value);
    }
    $this->getSession()->wait(1000, '!jQuery.active');
    $this->getSession()->executeScript("jQuery('#conditional-field-edit-form').submit();");
    $this->assertSession()->statusCodeEquals(200);
    $this->createScreenshot($this->screenshotPath . '02-select-single-post-add-list-options-filed-conditions.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-select-single-submit-list-options-filed-conditions.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-select-single-body-invisible-when-controlled-field-has-no-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, 1);
    $this->createScreenshot($this->screenshotPath . '05-select-single-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');

    // Change a select value set to show the body.
    $this->changeField($this->fieldSelector, 2);
    $this->createScreenshot($this->screenshotPath . '06-select-single-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set to hide the body again.
    $this->changeField($this->fieldSelector, '_none');
    $this->createScreenshot($this->screenshotPath . '07-select-single-body-invisible-when-controlled-field-has-no-value-again.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueRegExp() {
    // TODO: Implement testVisibleValueRegExp() method.
    $this->markTestIncomplete();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueAnd() {
    // TODO: Implement testVisibleValueAnd() method.
    $this->markTestIncomplete();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueOr() {
    // TODO: Implement testVisibleValueOr() method.
    $this->markTestIncomplete();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueNot() {
    // TODO: Implement testVisibleValueNot() method.
    $this->markTestIncomplete();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueXor() {
    // TODO: Implement testVisibleValueXor() method.
    $this->markTestIncomplete();
  }

}
