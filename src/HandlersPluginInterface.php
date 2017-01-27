<?php

namespace Drupal\conditional_fields;

/**
 * Defines the required interface for all handler plugins.
 */
interface HandlersPluginInterface {

  /**
   * Check if the handler is appropriate for the field that need further processing.
   */
  public function checkHandler($field);

  /**
   * Executes states handler according to conditional fields settings.
   */
  public function statesHandler($field, $field_info, $options, &$state);
}
