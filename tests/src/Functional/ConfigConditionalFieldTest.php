<?php

namespace Drupal\Tests\conditional_fields\Functional;

/**
 * Tests BrowserTestBase functionality.
 *
 * @group conditional_fields
 */
class ConfigConditionalFieldTest extends ConditionalFieldBase {

  /**
   * Tests basic Configuration test.
   */
  public function testGoTo() {
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
    $this->drupalGet('admin/structure/conditional_fields/node/article', array('query' => array('XDEBUG_SESSION_START' => 'phpstorm')));
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'table[add_new_dependency][dependent]' => 'body',
      'table[add_new_dependency][dependee]' => 'title',
      'table[add_new_dependency][state]' => 'visible',
      'table[add_new_dependency][condition]' => '!empty',
    ];
    $this->submitForm($edit, 'Add dependency');
    $this->assertSession()->statusCodeEquals(200);

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

  }

}
