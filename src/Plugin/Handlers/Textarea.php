<?php

namespace Drupal\conditional_fields\Plugin\Handlers;

use Drupal\conditional_fields\Plugin\Handlers\Text;

/**
 * Provides states handler for text areas.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_textarea",
 * )
 */
class Textarea extends Text {

  protected $handler_conditions = [
    '#type' => 'textarea',
  ];
}
