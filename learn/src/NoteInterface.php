<?php

namespace Drupal\learn;

/*
 * @file
 * Contains \Drupal\learn\ContactInterface.
 */

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Contact entity.
 *
 * @ingroup learn
 */
interface NoteInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {
}
