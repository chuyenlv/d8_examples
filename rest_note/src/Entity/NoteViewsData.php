<?php

namespace Drupal\rest_note\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Note entities.
 */
class NoteViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['note']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Note'),
      'help' => $this->t('The Note ID.'),
    );

    return $data;
  }

}
