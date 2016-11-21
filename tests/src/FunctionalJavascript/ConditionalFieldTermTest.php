<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\Tests\conditional_fields\FunctionalJavascript\ConditionalFieldBase as JavascriptTestBase;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

/**
 * Test Conditional Fields States.
 *
 * @group conditional_fields
 */
class ConditionalFieldTermTest extends JavascriptTestBase {

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
  public function testCreateConfig() {
    $user = $this->drupalCreateUser([
      'administer nodes',
      'view conditional fields',
      'edit conditional fields',
      'delete conditional fields',
      'create article content',
    ]);
    $this->drupalLogin($user);

    // Visit a ConditionalFields configuration page that requires login.
    $this->drupalGet('admin/structure/conditional_fields');
    $this->assertSession()->statusCodeEquals(200);

    // Configuration page contains the `Content` entity type.
    $this->assertSession()->pageTextContains('Content');

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->drupalGet('admin/structure/conditional_fields/node');
    $this->assertSession()->statusCodeEquals(200);

    // Configuration page contains the `Article` bundle of Content entity type.
    $this->assertSession()->pageTextContains('Article');

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'table[add_new_dependency][dependent]' => 'body',
      'table[add_new_dependency][dependee]' => 'field_' . $this->taxonomyName,
      'table[add_new_dependency][state]' => 'visible',
      'table[add_new_dependency][condition]' => 'value',
    ];
    $this->submitForm($edit, 'Add dependency');
    $this->assertSession()->statusCodeEquals(200);

    // Change a condition's values set and the value.
    $this->changeField('#edit-values-set', CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND);
    // Random term id to check necessary value.
    $term_id = mt_rand(1, $this->termsCount);
    $this->changeField('#edit-values', $term_id);
    // Submit the form.
    $this->getSession()
      ->executeScript("jQuery('#conditional-field-edit-form').submit();");
    $this->assertSession()->statusCodeEquals(200);

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, 'Article Body field is visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id, $term_id);
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');
//    $this->createScreenshot('sites/simpletest/scr1BodyVis.jpg');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id);
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
//    $this->createScreenshot('sites/simpletest/scr2BodyHid.jpg');
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
    $this->termsCount = mt_rand(2, 5);
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
    $this->createEntityReferenceField('node', 'article', 'field_' . $this->taxonomyName, $this->taxonomyName, 'taxonomy_term', 'default', $handler_settings);
    entity_get_form_display('node', 'article', 'default')
      ->setComponent('field_' . $this->taxonomyName, ['type' => 'options_buttons'])
      ->save();
  }

}
