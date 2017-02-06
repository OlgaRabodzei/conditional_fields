<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\Tests\conditional_fields\FunctionalJavascript\ConditionalFieldBase as JavascriptTestBase;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

/**
 * Test Conditional Fields Checkboxes Plugin.
 *
 * @group conditional_fields
 */
class ConditionalFieldCheckboxesTest extends JavascriptTestBase {

  use EntityReferenceTestTrait;

  /**
   * The name and vid of vocabulary, created for testing.
   *
   * @var string
   */
  protected $taxonomyName;

  /**
   * The amount of generated terms in created vocabulary.
   *
   * @var int
   */
  protected $termsCount;

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
    $this->createCondition('admin/structure/types/manage/article/conditionals', 'body', 'field_' . $this->taxonomyName, 'visible', 'value' );

    // Change a condition's values set and the value.
    $this->changeField('#edit-values-set', CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND);
    // Random term id to check necessary values.
    $term_id_1 = mt_rand(1, $this->termsCount);
    do {$term_id_2 = mt_rand(1, $this->termsCount);}
    while ($term_id_2 == $term_id_1);
    $values = $term_id_1.'\r\n'.$term_id_2;
    $this->changeField('#edit-values', $values);

    // Submit the form.
    $this->getSession()
      ->executeScript("jQuery('#conditional-field-edit-form-tab').submit();");
    $this->assertSession()->statusCodeEquals(200);

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, 'Article Body field is visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->createScreenshot('sites/simpletest/scr1BodyVisCheckboxes.jpg');
    $this->waitUntilVisible('.field--name-body', 60, 'Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->waitUntilHidden('.field--name-body', 60, 'Article Body field is visible');
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilHidden('.field--name-body', 60, 'Article Body field is visible');
    $this->createScreenshot('sites/simpletest/scr2BodyHidCheckboxes.jpg');
  }

  /**
   * Tests creating Conditional Field: Visible if has value from taxonomy.
   */
  public function testCreateConfigVisibleValueOr() {
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
    $this->createCondition('admin/structure/types/manage/article/conditionals', 'body', 'field_' . $this->taxonomyName, 'visible', 'value' );

    // Change a condition's values set and the value.
    $this->changeField('#edit-values-set', CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR);
    // Random term id to check necessary values.
    $term_id_1 = mt_rand(1, $this->termsCount);
    do {$term_id_2 = mt_rand(1, $this->termsCount);}
    while ($term_id_2 == $term_id_1);
    $values = $term_id_1.'\r\n'.$term_id_2;
    $this->changeField('#edit-values', $values);

    // Submit the form.
    $this->getSession()
      ->executeScript("jQuery('#conditional-field-edit-form-tab').submit();");
    $this->assertSession()->statusCodeEquals(200);

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, 'Article Body field is visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->waitUntilVisible('.field--name-body', 60, 'Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->waitUntilHidden('.field--name-body', 60, 'Article Body field is visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilVisible('.field--name-body', 60, 'Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilHidden('.field--name-body', 60, 'Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create a vocabulary with random name.
    $this->taxonomyName = $this->getRandomGenerator()->word(8);
    $vocabulary = Vocabulary::create([
      'name' => $this->taxonomyName,
      'vid' => $this->taxonomyName,
    ]);
    $vocabulary->save();
    // Create a random taxonomy terms for vocabulary.
    $this->termsCount = mt_rand(3, 6);
    for ($i = 1; $i <= $this->termsCount; $i++) {
      $termName = $this->getRandomGenerator()->word(8);
      Term::create([
        'parent' => [],
        'name' => $termName,
        'vid' => $this->taxonomyName,
      ])->save();
    }
    // Add a custom field with taxonomy terms to 'Article'.
    // The field label is a machine name of created vocabulary.
    $handler_settings = [
      'target_bundles' => [
        $vocabulary->id() => $vocabulary->id(),
      ],
    ];
    $this->createEntityReferenceField('node', 'article', 'field_' . $this->taxonomyName, $this->taxonomyName, 'taxonomy_term', 'default', $handler_settings, -1);
    entity_get_form_display('node', 'article', 'default')
      ->setComponent('field_' . $this->taxonomyName, ['type' => 'options_buttons'])
      ->save();
  }

}
