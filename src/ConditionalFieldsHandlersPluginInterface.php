<?php

namespace Drupal\conditional_fields;

/**
 * Defines the required interface for all conditional field's handler plugins.
 */
interface ConditionalFieldsHandlersPluginInterface {

  /**
   * Executes states handler according to conditional fields settings.
   */
  public function statesHandler($field, $field_info, $options);

}
