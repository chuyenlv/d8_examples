<?php

namespace Drupal\rest_note;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Note entity.
 *
 * @see \Drupal\rest_note\Entity\Note.
 */
class NoteAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\rest_note\Entity\NoteInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished note entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published note entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit note entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete note entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add note entities');
  }

}
