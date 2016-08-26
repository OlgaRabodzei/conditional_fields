<?php

/**
 * @file
 * Contains \Drupal\conditional_fields\ConditionalFieldInterface.
 */

namespace Drupal\conditional_fields;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Conditional field entities.
 *
 * @ingroup conditional_fields
 */
interface ConditionalFieldInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Conditional field dependee.
   *
   * @return string
   *   Dependee of the Conditional field.
   */
  public function getDependee();

  /**
   * Sets the Conditional field dependee.
   *
   * @param string $dependee
   *   The Conditional field dependee.
   *
   * @return \Drupal\conditional_fields\ConditionalFieldInterface
   *   The called Conditional field entity.
   */
  public function setDependee($dependee);

  /**
   * Gets the Conditional field dependent.
   *
   * @return string
   *   Dependent of the Conditional field.
   */
  public function getDependent();

  /**
   * Sets the Conditional field dependent.
   *
   * @param string $dependent
   *   The Conditional field dependent.
   *
   * @return \Drupal\conditional_fields\ConditionalFieldInterface
   *   The called Conditional field entity.
   */
  public function setDependent($dependent);

  /**
   * Gets the Conditional field creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Conditional field.
   */
  public function getCreatedTime();

  /**
   * Sets the Conditional field creation timestamp.
   *
   * @param int $timestamp
   *   The Conditional field creation timestamp.
   *
   * @return \Drupal\conditional_fields\ConditionalFieldInterface
   *   The called Conditional field entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Conditional field entity_type.
   *
   * @return string
   *   Entity type id of the Conditional field.
   */
  public function getEntityTypeValue();

  /**
   * Sets the Conditional field entity_type.
   *
   * @param string $entity_type
   *   Entity type id of the Conditional field.
   *
   * @return \Drupal\conditional_fields\ConditionalFieldInterface
   *   The called Conditional field entity.
   */
  public function setEntityTypeValue($entity_type);

  /**
   * Gets the Conditional field bundle.
   *
   * @return string
   *   Bundle id of the Conditional field.
   */
  public function getBundleValue();

  /**
   * Sets the Conditional field bundle.
   *
   * @param string $bundle
   *   Bundle id of the Conditional field.
   *
   * @return \Drupal\conditional_fields\ConditionalFieldInterface
   *   The called Conditional field entity.
   */
  public function setBundleValue($bundle);

  /**
   * Gets the Conditional field dependent as an object.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Dependent of the Conditional field.
   */
  public function getDependentField();

  /**
   * Gets the Conditional field dependee as an object.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Dependee of the Conditional field.
   */
  public function getDependeeField();

}
