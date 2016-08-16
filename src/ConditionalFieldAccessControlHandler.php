<?php

/**
 * @file
 * Contains \Drupal\conditional_fields\ConditionalFieldAccessControlHandler.
 */

namespace Drupal\conditional_fields;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Conditional field entity.
 *
 * @see \Drupal\conditional_fields\Entity\ConditionalField.
 */
class ConditionalFieldAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\conditional_fields\ConditionalFieldInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view conditional field entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit conditional field entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete conditional field entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add conditional field entities');
  }

}
