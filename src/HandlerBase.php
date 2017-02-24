<?php

namespace Drupal\conditional_fields;

/**
 * Defines a base handler implementation that most handlers plugins will extend.
 */
abstract class HandlerBase implements HandlersPluginInterface {

  protected $handler_conditions;

  /**
   * {@inheritdoc}
   */
  public function checkHandler($field) {
    if (array_intersect_assoc($this->handler_conditions, $field) == $this->handler_conditions) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options, &$state) {
  }

}
