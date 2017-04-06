<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;

/**
 * Provides states handler for select list.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_select",
 * )
 */
class Select extends ConditionalFieldsHandlerBase {

  protected $handler_conditions = [
    '#type' => 'select',
    '#multiple' => FALSE,
  ];

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options, &$state) { }

}
