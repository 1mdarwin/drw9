<?php

namespace Drupal\google_tag;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\google_tag\Entity\TagContainer;

/**
 * Defines a listing of tag container configuration entities.
 *
 * @see \Drupal\google_tag\Entity\TagContainer
 */
class TagContainerListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Label');
    $header['id'] = t('Machine name');
    $header['container_ids'] = t('Container ID(s)');
    $header['weight'] = t('Weight');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    assert($entity instanceof TagContainer);
    // @todo Add JS for drag handle on weight.
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['container_ids'] = implode(', ', $entity->get('tag_container_ids'));
    $row['weight'] = $entity->get('weight');
    return $row + parent::buildRow($entity);
  }

}
