<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;

/**
 * Provides states handler for links provided by the Link module.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_link_field",
 * )
 */
class LinkField extends ConditionalFieldsHandlerBase {

  protected $handler_conditions = [
    '#type' => 'link_field',
  ];

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $link_selectors = [];
    $regex = $options['values_set'] == CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX;

    // Add a condition for each link part (Title and URL)
    if ($field_info['instance']['settings']['title'] == 'optional' || $field_info['instance']['settings']['title'] == 'required') {
      $link_selectors[conditional_fields_field_selector($field['title'])] = ['value' => $regex ? $options['value'] : $options['value_form'][0]['title']];
    }
    $link_selectors[conditional_fields_field_selector($field['url'])] = ['value' => $regex ? $options['value'] : $options['value_form'][0]['url']];

    $state = [$options['state'] => $link_selectors];

    return $state;
  }

}
