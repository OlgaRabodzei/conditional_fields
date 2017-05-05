<?php

namespace Drupal\conditional_fields;

/**
 * Defines a base handler implementation that most handlers plugins will extend.
 */
abstract class ConditionalFieldsHandlerBase implements ConditionalFieldsHandlersPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function checkHandler($field) {

    if (empty($this->handler_conditions) || array_intersect_assoc($this->handler_conditions, $field) == $this->handler_conditions) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetValue(array $value_form) {
    return $value_form[0]['value'];
  }

}
