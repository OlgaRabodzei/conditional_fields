<?php

/**
 * @file
 * Contains \Drupal\conditional_fields\ConditionalFieldListBuilder.
 */

namespace Drupal\conditional_fields;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Conditional field entities.
 *
 * @ingroup conditional_fields
 */
class ConditionalFieldListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Conditional field ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\conditional_fields\Entity\ConditionalField */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.conditional_field.edit_form', array(
          'conditional_field' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
