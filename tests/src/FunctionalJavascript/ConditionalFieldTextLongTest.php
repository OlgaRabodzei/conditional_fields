<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldValueInterface;

/**
 * Test Conditional Fields Text Handler.
 *
 * @group conditional_fields
 */
class ConditionalFieldTextLongTest extends ConditionalFieldTestBase implements ConditionalFieldValueInterface {

  /**
   * {@inheritdoc}
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/textlong/';

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'single_textlong';

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

    $this->fieldSelector = "[name=\"{$this->fieldName}\"][0][value]";
    $this->fieldStorageDefinition = [
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'text_long',
      'cardinality' => 1,
    ];
    $this->fieldStorage = FieldStorageConfig::create($this->fieldStorageDefinition);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'article',
    ]);
    $this->field->save();

    EntityFormDisplay::load('node.article.default')
      ->setComponent($this->fieldName, ['type' => 'text_long'])
      ->save();
  }

  /**
   * Tests creating Conditional Field: Visible if has value from Title Widget.
   */
  public function testVisibleValueWidget() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testFieldTextLongVisibleValueWidget.png');

    // Set up conditions.
    $text = 'drupal test textlong';
    $data = [
      '[name="condition"]' => 'value',
      '[name="values_set"]' => CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
      $this->fieldSelector => $text,
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
    $this->createScreenshot($this->screenshotPath . '02-testFieldTextLongVisibleValueWidget.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()->statusCodeEquals(200);
    $this->createScreenshot($this->screenshotPath . '03-testFieldTextLongVisibleValueWidget.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Change field that should not show the body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '04-testFieldTextLongVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');

    // Check that the field Body is visible.
    $this->changeField($this->fieldSelector, $text);
    $this->createScreenshot($this->screenshotPath . '05-testFieldTextLongVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is visible');

    // Change field that should not show the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '06-testFieldTextLongVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');
  }

  /**
   * Tests creating Conditional Field: Visible if has value from Title value.
   */
  public function testVisibleValueAnd() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testFieldTextLongVisibleValueAnd.png');

    // Set up conditions.
    $text = ['drupal textlong text first', 'drupal textlong text second'];
    $data = [
      '[name="condition"]' => 'value',
      '[name="values_set"]' => CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND,
      '[name="values"]' => implode('\n', $text),
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
    $this->createScreenshot($this->screenshotPath . '02-testFieldTextLongVisibleValueAnd.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()->statusCodeEquals(200);
    $this->createScreenshot($this->screenshotPath . '03-testFieldTextLongVisibleValueAnd.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testFieldTextLongVisibleValueAnd.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');

    // Change field that should not show the body.
    $this->changeField($this->fieldSelector, 'https://drupal.org');
    $this->createScreenshot($this->screenshotPath . '05-testFieldTextLongVisibleValueAnd.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');

    // Change field value to show the body.
    $this->changeField($this->fieldSelector, implode('\n', $text));
    $this->createScreenshot($this->screenshotPath . '06-testFieldTextLongVisibleValueAnd.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is visible');

    // Change field value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '07-testFieldTextLongVisibleValueAnd.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');
  }

  /**
   * Tests creating Conditional Field: Visible if has value from Title value.
   */
  public function testVisibleValueOr() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testFieldTextLongVisibleValueOr.png');

    // Set up conditions.
    $text = ['drupal textlong text first', 'drupal textlong text second'];
    $data = [
      '[name="condition"]' => 'value',
      '[name="values_set"]' => CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR,
      '[name="values"]' => implode('\n', $text),
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
    $this->createScreenshot($this->screenshotPath . '02-testFieldTextLongVisibleValueOr.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()->statusCodeEquals(200);
    $this->createScreenshot($this->screenshotPath . '03-testFieldTextLongVisibleValueOr.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testFieldTextLongVisibleValueOr.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');

    // Change field that should not show the body.
    $this->changeField($this->fieldSelector, 'https://drupal.org');
    $this->createScreenshot($this->screenshotPath . '05-testFieldTextLongVisibleValueOr.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');

    // Change field value to show the body.
    $this->changeField($this->fieldSelector, $text[0]);
    $this->createScreenshot($this->screenshotPath . '06-testFieldTextLongVisibleValueOr.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is visible');

    // Change field value to show the body.
    $this->changeField($this->fieldSelector, $text[1]);
    $this->createScreenshot($this->screenshotPath . '07-testFieldTextLongVisibleValueOr.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is visible');

    // Change field value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testFieldTextLongVisibleValueOr.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueRegExp() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testFieldTextLongVisibleValueRegExp.png');

    // Set up conditions.
    $text = 'text_';
    $data = [
      '[name="condition"]' => 'value',
      '[name="values_set"]' => CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX,
      $this->fieldSelector => $text,
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
    $this->createScreenshot($this->screenshotPath . '02-testFieldTextLongVisibleValueRegExp.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()->statusCodeEquals(200);
    $this->createScreenshot($this->screenshotPath . '03-testFieldTextLongVisibleValueRegExp.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Change field that should not show the body.
    $this->changeField($this->fieldSelector, 'wrong text');
    $this->createScreenshot($this->screenshotPath . '04-testFieldTextWithSummaryVisibleValueRegExp.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');

    // Check that the field Body is visible.
    $this->changeField($this->fieldSelector, 'text_correct');
    $this->createScreenshot($this->screenshotPath . '05-testFieldTextWithSummaryVisibleValueRegExp.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is visible');

    // Change field that should not show the body again.
    $this->changeField($this->fieldSelector, 'wrong text');
    $this->createScreenshot($this->screenshotPath . '06-testFieldTextWithSummaryVisibleValueRegExp.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueNot() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testFieldTextLongVisibleValueNot.png');

    // Set up conditions.
    $text = ['drupal textlong text first', 'drupal textlong text second'];
    $data = [
      '[name="condition"]' => 'value',
      '[name="values_set"]' => CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT,
      '[name="values"]' => implode('\n', $text),
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
    $this->createScreenshot($this->screenshotPath . '02-testFieldTextLongVisibleValueNot.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()->statusCodeEquals(200);
    $this->createScreenshot($this->screenshotPath . '03-testFieldTextLongVisibleValueNot.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the field Body is visible.
    $this->createScreenshot($this->screenshotPath . '04-testFieldTextLongVisibleValueNot.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is visible');

    // Change field that should not show the body.
    $this->changeField($this->fieldSelector, $text[0]);
    $this->createScreenshot($this->screenshotPath . '05-testFieldTextLongVisibleValueNot.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');

    // Change field that should not show the body again.
    $this->changeField($this->fieldSelector, $text[1]);
    $this->createScreenshot($this->screenshotPath . '06-testFieldTextLongVisibleValueNot.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');

    // Change field value to show the body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testFieldTextLongVisibleValueNot.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueXor() {
    // TODO: Implement testVisibleValueXor() method.
  }

  /**
   * Tests creating Conditional Field: Visible if isFilled.
   */
  public function testCreateConfigVisibleFilled() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', $this->fieldName, 'visible', '!empty');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->pageTextContains('body title visible !empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, 'Article Body field is visible');
    $this->changeField($this->fieldSelector, 'This field is not empty.');
    $this->waitUntilVisible('.field--name-body', 10, 'Article Body field is not visible');
  }

  /**
   * Tests creating Conditional Field: inVisible if isFilled.
   */
  public function testCreateConfigInvisibleEmpty() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', $this->fieldName, '!visible', 'empty');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->pageTextContains('body title !visible empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    $this->waitUntilHidden('.field--name-body', 0, 'Article Body field is visible');
    $this->changeField($this->fieldSelector, 'This field is not empty.');
    $this->waitUntilVisible('.field--name-body', 10, 'Article Body field is not visible');
  }
}
