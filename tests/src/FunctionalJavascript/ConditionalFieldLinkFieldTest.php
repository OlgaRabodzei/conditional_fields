<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;
use Drupal\Tests\conditional_fields\FunctionalJavascript\ConditionalFieldBase as JavascriptTestBase;

/**
 * Test Conditional Fields Link field plugin.
 *
 * @group conditional_fields
 */
class ConditionalFieldLinkFieldTest extends JavascriptTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'conditional_fields',
    'node',
    'link',
  ];

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'link_field';

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

    $this->fieldSelector = "[name=\"{$this->fieldName}[0][uri]\"]";
    $this->fieldStorageDefinition = [
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'link',
    ];
    $this->fieldStorage = FieldStorageConfig::create($this->fieldStorageDefinition);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'article',
      'settings' => array(
        'title' => DRUPAL_DISABLED,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ),
    ]);
    $this->field->save();

    EntityFormDisplay::load('node.article.default')
      ->setComponent($this->fieldName, [
        'type' => 'link_default',
      ])
      ->save();
  }

  /**
   * Tests creating Conditional Field: Visible, values input mode - OR.
   */
  public function testFieldLinkVisibleValueOr() {
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
    $this->createScreenshot('sites/simpletest/01-link-field-add-filed-conditions.png');

    // Set up conditions.
    $urls = ['<front>', 'node/add'];
    $data = [
      '[name="condition"]' => 'value',
      '[name="values_set"]' => CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR,
      '[name="values"]' => implode(PHP_EOL, $urls),
      '[name="grouping"]' => 'AND',
      '[name="state"]' => 'visible',
      '[name="effect"]' => 'show',
    ];
    foreach ($data as $selector => $value) {
      $this->changeField($selector, $value);
    }
    $this->getSession()->wait(1000, '!jQuery.active');
    $this->getSession()->executeScript("jQuery('#conditional-field-edit-form-tab').submit();");
    $this->assertSession()->statusCodeEquals(200);
    $this->createScreenshot('sites/simpletest/02-link-field-post-add-list-options-filed-conditions.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot('sites/simpletest/03-link-field-submit-options-filed-conditions.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the field Body is not visible.
    $this->createScreenshot('sites/simpletest/04-link-field-body-invisible-when-controlled-field-has-no-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');

    // Change a link that should not show the body.
    $this->changeField($this->fieldSelector, 'https://drupal.org');
    $this->createScreenshot('sites/simpletest/05-link-field-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');

    // Change a link value to show the body.
    $this->changeField($this->fieldSelector, $urls[0]);
    $this->createScreenshot('sites/simpletest/06-link-field-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is visible');

    // Change a link value to show the body.
    $this->changeField($this->fieldSelector, $urls[1]);
    $this->createScreenshot('sites/simpletest/07-link-field-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is visible');

    // Change a link value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot('sites/simpletest/08-link-field-body-invisible-when-controlled-field-has-no-value-again.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');
  }

  /**
   * Tests creating Conditional Field: Visible, values input mode - NOT.
   */
  public function testFieldLinkVisibleValueNot() {
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
    $this->createScreenshot('sites/simpletest/01-testFieldLinkVisibleValueNot.png');

    // Set up conditions.
    $urls = ['<front>', 'node/add'];
    $data = [
      '[name="condition"]' => 'value',
      '[name="values_set"]' => CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT,
      '[name="values"]' => implode(PHP_EOL, $urls),
      '[name="grouping"]' => 'AND',
      '[name="state"]' => 'visible',
      '[name="effect"]' => 'show',
    ];
    foreach ($data as $selector => $value) {
      $this->changeField($selector, $value);
    }
    $this->getSession()->wait(1000, '!jQuery.active');
    $this->getSession()->executeScript("jQuery('#conditional-field-edit-form-tab').submit();");
    $this->assertSession()->statusCodeEquals(200);
    $this->createScreenshot('sites/simpletest/02-testFieldLinkVisibleValueNot.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot('sites/simpletest/03-testFieldLinkVisibleValueNot.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the field Body is visible.
    $this->createScreenshot('sites/simpletest/04-testFieldLinkVisibleValueNot.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is visible');

    // Change a link that should not show the body.
    $this->changeField($this->fieldSelector, $urls[0]);
    $this->createScreenshot('sites/simpletest/05-testFieldLinkVisibleValueNot.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');

    // Change a link that should not show the body again.
    $this->changeField($this->fieldSelector, $urls[1]);
    $this->createScreenshot('sites/simpletest/06-testFieldLinkVisibleValueNot.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is not visible');

    // Change a link value to show the body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot('sites/simpletest/08-testFieldLinkVisibleValueNot.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is visible');
  }

  /**
   * Helper to change Field value with Javascript.
   */
  protected function changeField($selector, $value = '') {
    $value = json_encode($value);
    $this->getSession()->executeScript("jQuery('{$selector}').val({$value}).trigger('keyup').trigger('change');");
  }

}
