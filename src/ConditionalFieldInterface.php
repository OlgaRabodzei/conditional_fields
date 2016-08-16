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

}
