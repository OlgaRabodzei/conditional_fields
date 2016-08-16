<?php

/**
 * @file
 * Contains \Drupal\conditional_fields\Entity\ConditionalField.
 */

namespace Drupal\conditional_fields\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Conditional field entities.
 */
class ConditionalFieldViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['conditional_field']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Conditional field'),
      'help' => $this->t('The Conditional field ID.'),
    );

    return $data;
  }

}
