<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldFilledEmptyInterface;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldValueInterface;

/**
 * Test Conditional Fields Text Handler.
 *
 * @group conditional_fields
 */
class ConditionalFieldTextTest extends ConditionalFieldTestBase implements
  ConditionalFieldValueInterface,
  ConditionalFieldFilledEmptyInterface {

  /**
   * {@inheritdoc}
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/text/';

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueWidget() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'title', 'visible', 'value');

    // Change a condition's values set and the value.
    $this->changeField('#edit-values-set', CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET);
    // Random term id to check necessary value.
    $text = $this->getRandomGenerator()->word(8);
    $this->changeField('#edit-title-0-value', $text);
    // Submit the form.
    $this->getSession()
      ->executeScript("jQuery('#conditional-field-edit-form').submit();");
    $this->assertSession()->statusCodeEquals(200);

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()
      ->pageTextContains('body title visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, 'Article Body field is visible');
    $this->changeField('.field--name-title input', $text);
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeField('.field--name-title input', $text . 'a');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueRegExp() {
    // TODO: Implement testVisibleValueRegExp() method.
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueAnd() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'title', 'visible', 'value');

    // Change a condition's values set and the value.
    $this->changeField('#edit-values-set', CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND);
    // Random term id to check necessary value.
    $text = $this->getRandomGenerator()->word(8);
    $this->changeField('#edit-values', $text);
    // Submit the form.
    $this->getSession()
      ->executeScript("jQuery('#conditional-field-edit-form').submit();");
    $this->assertSession()->statusCodeEquals(200);

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()
      ->pageTextContains('body title visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, 'Article Body field is visible');

    // Change a select value set to show the body.
    $this->changeField('.field--name-title input', $text);
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change a select value set to hide the body again.
    $this->changeField('.field--name-title input', $text . 'a');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueOr() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'title', 'visible', 'value');

    // Change a condition's values set and the value.
    $this->changeField('#edit-values-set', CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR);
    // Random term id to check necessary value.
    $text1 = $this->getRandomGenerator()->word(8);
    $text2 = $this->getRandomGenerator()->word(7);
    $values = $text1 . '\r\n' . $text2;
    $this->changeField('#edit-values', $values);
    // Submit the form.
    $this->getSession()
      ->executeScript("jQuery('#conditional-field-edit-form').submit();");
    $this->assertSession()->statusCodeEquals(200);

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()
      ->pageTextContains('body title visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, 'Article Body field is visible');
    $this->changeField('.field--name-title input', $text1);
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeField('.field--name-title input', $text1 . 'a');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
    $this->changeField('.field--name-title input', $text2);
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeField('.field--name-title input', $text2 . 'ma');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueNot() {
    // TODO: Implement testVisibleValueNot() method.
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
  public function testVisibleFilled() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'title', 'visible', '!empty');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->pageTextContains('body title visible !empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, 'Article Body field is visible');
    $this->changeField('.field--name-title input', 'This field is not empty.');
    $this->waitUntilVisible('.field--name-body', 10, 'Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleEmpty() {
    // TODO: Implement testVisibleEmpty() method.
  }

  /**
   * {@inheritdoc}
   */
  public function testInvisibleFilled() {
    // TODO: Implement testInvisibleFilled() method.
  }

  /**
   * {@inheritdoc}
   */
  public function testInvisibleEmpty() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'title', '!visible', 'empty');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->pageTextContains('body title !visible empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    $this->waitUntilHidden('.field--name-body', 0, 'Article Body field is visible');
    $this->changeField('.field--name-title input', 'This field is not empty.');
    $this->waitUntilVisible('.field--name-body', 10, 'Article Body field is not visible');
  }

}
