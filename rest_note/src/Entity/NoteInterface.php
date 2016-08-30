<?php

namespace Drupal\rest_note\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Note entities.
 *
 * @ingroup rest_note
 */
interface NoteInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Note name.
   *
   * @return string
   *   Name of the Note.
   */
  public function getName();

  /**
   * Sets the Note name.
   *
   * @param string $name
   *   The Note name.
   *
   * @return \Drupal\rest_note\Entity\NoteInterface
   *   The called Note entity.
   */
  public function setName($name);

  /**
   * Gets the Note creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Note.
   */
  public function getCreatedTime();

  /**
   * Sets the Note creation timestamp.
   *
   * @param int $timestamp
   *   The Note creation timestamp.
   *
   * @return \Drupal\rest_note\Entity\NoteInterface
   *   The called Note entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Note published status indicator.
   *
   * Unpublished Note are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Note is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Note.
   *
   * @param bool $published
   *   TRUE to set this Note to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\rest_note\Entity\NoteInterface
   *   The called Note entity.
   */
  public function setPublished($published);

}
