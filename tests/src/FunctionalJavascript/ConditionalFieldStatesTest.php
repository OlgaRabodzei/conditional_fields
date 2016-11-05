<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\video_embed_field\Functional\EntityDisplaySetupTrait;

/**
 * Test the colorbox formatter.
 *
 * @group conditional_fields
 */
class ConditionalFieldStatesTest extends JavascriptTestBase {

  use EntityDisplaySetupTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'conditional_fields',
    'node',
    'datetime',
    'field_ui',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array(
        'type' => 'page',
        'name' => 'Basic page',
        'display_submitted' => FALSE,
      ));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }
    $this->accessHandler = \Drupal::entityManager()->getAccessControlHandler('node');

    // $this->setupEntityDisplays();
  }

  /**
   * Tests creating Conditional Field Config.
   */
  public function testCreateConfig() {
    // $this->setDisplayComponentSettings('video_embed_field_colorbox', [
    //   'autoplay' => FALSE,
    //   'responsive' => TRUE,
    // ]);
    // $node = $this->createVideoNode('https://example.com/mock_video');
    // $this->drupalGet('node/' . $node->id());
    // $this->click('.video-embed-field-launch-modal');
    // $this->getSession()->wait(static::COLORBOX_LAUNCH_TIME);
    // $this->assertSession()->elementExists('css', '#colorbox .video-embed-field-responsive-video');
    // // Make sure the right library files are loaded on the page.
    // $this->assertSession()->elementContains('css', 'style', 'colorbox/styles/default/colorbox_style.css');
    // $this->assertSession()->elementContains('css', 'style', 'video_embed_field/css/video_embed_field.responsive-video.css');

    $admin_account = $this->drupalCreateUser([
      'view conditional fields',
      'edit conditional fields',
      'delete conditional fields',
      'administer nodes',
      'create article content',
    ]);
    $this->drupalLogin($admin_account);

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
      'table[add_new_dependency][dependee]' => 'title',
      'table[add_new_dependency][state]' => 'visible',
      'table[add_new_dependency][condition]' => '!empty',
    ];
    $this->submitForm($edit, 'Add dependency');
    $this->assertSession()->statusCodeEquals(200);

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->pageTextContains('body title visible !empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);
    $this->createScreenshot('screenshot.jpg');
    $this->assertFileExists('screenshot.jpg');

    // Check that the field Body is not visible.
    // $a = $this->cssSelectToXpath('.field--name-body');
    // $this->assertElementNotVisible('.field--name-body', 'Article Body field is not visible');
    $this->waitUntilHidden('.field--name-body', 0, 'Article Body field is visible');
    $this->changeField('.field--name-title input', 'Not empty Title');
    $this->waitUntilHidden('.field--name-body', 100, 'Article Body field is not visible');
    $this->createScreenshot('screenshot_visible.jpg');
    $this->assertFileExists('screenshot_visible.jpg');

  }

  /**
   * Waits and asserts that a given element is visible.
   *
   * @param string $selector
   *   The CSS selector.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 1000.
   * @param string $message
   *   (Optional) Message to pass to assertJsCondition().
   */
  protected function waitUntilVisible($selector, $timeout = 1000, $message = '') {
    $condition = "jQuery('" . $selector . ":visible').length > 0";
    $this->assertJsCondition($condition, $timeout, $message);
  }

  /**
   * Waits and asserts that a given element is hidden (invisible).
   *
   * @param string $selector
   *   The CSS selector.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 1000.
   * @param string $message
   *   (Optional) Message to pass to assertJsCondition().
   */
  protected function waitUntilHidden($selector, $timeout = 1000, $message = '') {
    $condition = "jQuery('" . $selector . ":hidden').length > 0";
    $this->assertJsCondition($condition, $timeout, $message);
  }

  /**
   * 
   */
  protected function changeField($selector, $value = '') {
    $this->getSession()->executeScript("jQuery('" . $selector . "').val('" . $value . "');");
  }

}
