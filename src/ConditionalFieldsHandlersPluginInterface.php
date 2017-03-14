<?php

namespace Drupal\conditional_fields;

/**
 * Defines the required interface for all conditional field's handler plugins.
 */
interface ConditionalFieldsHandlersPluginInterface {

  /**
   * Check if the handler is appropriate for the field.
   *
   * @param string $field
   *   The field that needs further processing.
   *
   * @return bool
   *   A result of the checking.
   */
  public function checkHandler($field);

  /**
   * Executes states handler according to conditional fields settings.
   */
  public function statesHandler($field, $field_info, $options);

}
