<?php

/**
 * @file
 * Contains conditional_field.page.inc..
 *
 * Page callback for Conditional field entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Conditional field templates.
 *
 * Default template: conditional_field.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_conditional_field(array &$variables) {
  // Fetch ConditionalField Entity Object.
  $conditional_field = $variables['elements']['#conditional_field'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
