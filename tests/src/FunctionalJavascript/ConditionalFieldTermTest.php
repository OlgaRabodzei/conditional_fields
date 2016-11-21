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
   * Taxonomy name.
   *
   * @var string
   */
  protected $taxonomyName;

  /**
   * The amount of generated terms.
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

    // Random term id to check necessary value.
    $term_id = mt_rand(1, $this->termsCount);

    $this->changeField('#edit-values-set', CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND);
    $this->changeField('#edit-values', $term_id);
    $this->getSession()
      ->executeScript("jQuery('#conditional-field-edit-form').submit();");
    $this->assertSession()->statusCodeEquals(200);

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 10, 'Article Body field is visible');
    $this->changeSelect('#edit-field-' . $this->taxonomyName . "-" . $term_id, $term_id);

    // Check that the field Body is visible.
    $this->waitUntilVisible('.field--name-body', 10, 'Article Body field is not visible');
    $this->changeSelect('#edit-field-' . $this->taxonomyName . "-" . $term_id);

    // Check that the field Body is visible.
    $this->waitUntilHidden('.field--name-body', 10, 'Article Body field is visible');

  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create a vocabulary with random name.
    $this->taxonomyName = $this->getRandomGenerator()->word(8);
    $vocabulary = Vocabulary::create(array(
      'name' => $this->taxonomyName,
      'vid' => $this->taxonomyName,
    ));
    $vocabulary->save();
    // Create a random taxonomy terms for vocabulary.
    $this->termsCount = mt_rand(2, 5);
    for ($i = 1; $i <= $this->termsCount; $i++) {
      $termName = $this->getRandomGenerator()->word(8);
      Term::create(array(
        'parent' => array(),
        'name' => $termName,
        'vid' => $this->taxonomyName,
      ))->save();
    }
    // Add a custom field with taxonomy terms to 'Article'.
    // The field label is a machine name of created vocabulary.
    $handler_settings = array(
      'target_bundles' => [
        $vocabulary->id() => $vocabulary->id(),
      ],
    );
    $this->createEntityReferenceField('node', 'article', 'field_' . $this->taxonomyName, $this->taxonomyName, 'taxonomy_term', 'default', $handler_settings);
    entity_get_form_display('node', 'article', 'default')
      ->setComponent('field_' . $this->taxonomyName, ['type' => 'options_buttons'])
      ->save();
  }

}
