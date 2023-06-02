<?php

namespace Drupal\a12s_layout\DisplayOptions;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of display options instances.
 */
class InstanceListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritDoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritDoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\a12s_layout\Entity\DisplayOptionsInstanceInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

}
