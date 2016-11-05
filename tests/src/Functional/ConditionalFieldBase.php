<?php

namespace Drupal\Tests\conditional_fields\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base setup for ConditionalField tests.
 *
 * @group conditional_fields
 */
abstract class ConditionalFieldBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'conditional_fields',
    'node',
    'datetime',
    'field_ui',
    'field_test',
    'taxonomy',
    'image',
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
  }

}
