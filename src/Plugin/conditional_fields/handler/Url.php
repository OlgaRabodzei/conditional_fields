<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;

/**
 * Provides states handler for links with type url.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_url",
 * )
 */
class Url extends ConditionalFieldsHandlerBase {

  protected $handler_conditions = [
    '#type' => 'url'
  ];

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];

    return $state;
  }

}
