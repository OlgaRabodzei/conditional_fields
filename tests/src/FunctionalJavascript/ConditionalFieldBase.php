<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Base setup for ConditionalField tests.
 *
 * @group conditional_fields
 */
abstract class ConditionalFieldBase extends JavascriptTestBase {

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
   * Helper to change Field value with Javascript.
   */
  protected function changeField($selector, $value = '') {
    $this->getSession()->executeScript("jQuery('" . $selector . "').val('" . $value . "').trigger('keyup').trigger('change');");
  }

  /**
   * Helper to change selection with Javascript.
   */
  protected function changeSelect($selector, $value = '') {
    $this->getSession()->executeScript("jQuery('" . $selector . "').val('" . $value . "').trigger('click').trigger('change');");
  }

}