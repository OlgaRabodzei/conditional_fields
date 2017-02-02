<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\Tests\conditional_fields\FunctionalJavascript\ConditionalFieldBase as JavascriptTestBase;

/**
 * Test Conditional Fields Text Handler.
 *
 * @group conditional_fields
 */
class ConditionalFieldTextTest extends JavascriptTestBase {

  /**
   * Tests creating Conditional Field: Visible if has value from Title value.
   */
  public function testCreateConfig() {
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
    $this->createCondition('admin/structure/types/manage/article/conditionals', 'body', 'title', 'visible', 'value' );

    // Change a condition's values set and the value.
    $this->changeField('#edit-values-set', CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND);
    // Random term id to check necessary value.
    $text = $this->getRandomGenerator()->word(8);
    $this->changeField('#edit-values', $text);
    // Submit the form.
    $this->getSession()
      ->executeScript("jQuery('#conditional-field-edit-form-tab').submit();");
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
//    $this->createScreenshot('sites/simpletest/scr1BodyVis.jpg');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeField('.field--name-title input', $text . 'a');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
//    $this->createScreenshot('sites/simpletest/scr2BodyHid.jpg');
  }

  /**
   * Tests creating Conditional Field: Visible if has value from Title Widget.
   */
  public function testCreateConfigWidget() {
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
    $this->createCondition('admin/structure/types/manage/article/conditionals', 'body', 'title', 'visible', 'value' );

    // Change a condition's values set and the value.
    $this->changeField('#edit-values-set', CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET);
    // Random term id to check necessary value.
    $text = $this->getRandomGenerator()->word(8);
    $this->changeField('#edit-title-0-value', $text);
    // Submit the form.
    $this->getSession()
      ->executeScript("jQuery('#conditional-field-edit-form-tab').submit();");
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
//    $this->createScreenshot('sites/simpletest/scr1BodyVis.jpg');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeField('.field--name-title input', $text . 'a');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
//    $this->createScreenshot('sites/simpletest/scr2BodyHid.jpg');
  }
}
