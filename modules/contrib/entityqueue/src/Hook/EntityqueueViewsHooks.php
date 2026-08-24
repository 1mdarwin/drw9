<?php

declare(strict_types=1);

namespace Drupal\entityqueue\Hook;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entityqueue\Entity\EntityQueue;

/**
 * Views data hook implementations for the Entityqueue module.
 */
class EntityqueueViewsHooks {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
  ) {}

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data) {
    $entity_subqueue = $this->entityTypeManager->getDefinition('entity_subqueue');

    // Find all entity types that need an 'entityqueue' relationship.
    $target_entity_type_ids = [];
    $queues = EntityQueue::loadMultiple();
    foreach ($queues as $queue) {
      $target_entity_type_ids[$queue->getTargetEntityTypeId()] = TRUE;
    }

    // Filter entity types to those that have a 'views_data' handler and use a
    // SQL storage.
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    $entity_types = [];
    foreach (array_keys($target_entity_type_ids) as $entity_type_id) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      if ($entity_type->hasHandlerClass('views_data') && $this->entityTypeManager->getStorage($entity_type->id()) instanceof SqlEntityStorageInterface) {
        $entity_types[$entity_type_id] = $entity_type;
      }
    }

    foreach ($entity_types as $entity_type) {
      $field_name = 'items';
      $field_storage = $this->entityFieldManager->getFieldStorageDefinitions('entity_subqueue')[$field_name];
      $target_base_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();

      /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = $this->entityTypeManager->getStorage('entity_subqueue')->getTableMapping();
      $columns = $table_mapping->getColumnNames($field_name);
      $subqueue_items_table_name = $table_mapping->getDedicatedDataTableName($field_storage);

      $data[$target_base_table]['entityqueue_relationship']['relationship'] = [
        'id' => 'entity_queue',
        'title' => $this->t('@target_label queue', ['@target_label' => $entity_type->getLabel()]),
        'label' => $this->t('@target_label queue', ['@target_label' => $entity_type->getLabel()]),
        'group' => $this->t('Entityqueue'),
        'help' => $this->t('Create a relationship from @target_label to an entityqueue.', ['@target_label' => $entity_type->getLabel()]),
        'base' => $entity_subqueue->getDataTable() ?: $entity_subqueue->getBaseTable(),
        'entity_type' => 'entity_subqueue',
        'base field' => $entity_subqueue->getKey('id'),
        'field_name' => $field_storage->getName(),
        'field table' => $subqueue_items_table_name,
        // When a workspace is active, the relationship joins this revision
        // table instead, to read a subqueue's edited items from the workspace.
        'field revision table' => $table_mapping->getDedicatedRevisionTableName($field_storage),
        'field field' => $columns['target_id'],
      ];

      $data[$target_base_table]['entityqueue_relationship_in_queue']['sort'] = [
        'id' => 'entity_queue_in_queue',
        'group' => $this->t('Entityqueue'),
        'title' => $this->t('In @target_label queue', [
          '@target_label' => $entity_type->getLabel(),
        ]),
        'label' => $this->t('In @target_label queue', [
          '@target_label' => $entity_type->getLabel(),
        ]),
        'help' => $this->t('Filter to ensure a(n) @target_label IS or IS NOT in the related queue', [
          '@target_label' => $entity_type->getLabel(),
        ]),
        'field' => 'delta',
        'field table' => $subqueue_items_table_name,
        'field_name' => $field_name,
      ];

      $data[$target_base_table]['entityqueue_relationship']['sort'] = [
        'id' => 'entity_queue_position',
        'group' => $this->t('Entityqueue'),
        'title' => $this->t('@target_label Queue Position', [
          '@target_label' => $entity_type->getLabel(),
        ]),
        'label' => $this->t('@target_label Queue Position', [
          '@target_label' => $entity_type->getLabel(),
        ]),
        'help' => $this->t('Position of item in the @target_label queue.', [
          '@target_label' => $entity_type->getLabel(),
        ]),
        'field' => 'delta',
        'field table' => $subqueue_items_table_name,
        'field_name' => $field_name,
      ];

      $data[$target_base_table]['entityqueue_relationship']['filter'] = [
        'id' => 'entity_queue_in_queue',
        'type' => 'yes-no',
        'group' => $this->t('Entityqueue'),
        'title' => $this->t('@target_label In Queue', [
          '@target_label' => $entity_type->getLabel(),
        ]),
        'label' => $this->t('@target_label In Queue', [
          '@target_label' => $entity_type->getLabel(),
        ]),
        'help' => $this->t('Filter for entities that are available or not in the @target_label entity queue.', [
          '@target_label' => $entity_type->getLabel(),
        ]),
        'field table' => $subqueue_items_table_name,
        'field field' => $columns['target_id'],
      ];

      $data[$target_base_table]['entityqueue_relationship_position']['field'] = [
        'id' => 'entity_queue_position',
        'group' => $this->t('Entityqueue'),
        'title' => $this->t('@target_label Position In Queue', [
          '@target_label' => $entity_type->getLabel(),
        ]),
        'label' => $this->t('@target_label Position In Queue', [
          '@target_label' => $entity_type->getLabel(),
        ]),
        'help' => $this->t('Position of item in the @target_label queue.', [
          '@target_label' => $entity_type->getLabel(),
        ]),
        'field' => 'delta',
        'field table' => $subqueue_items_table_name,
      ];

      $data[$target_base_table]['entityqueue_relationship_position']['filter'] = [
        'id' => 'entity_queue_position',
        'group' => $this->t('Entityqueue'),
        'title' => $this->t('@target_label Position In Queue', [
          '@target_label' => $entity_type->getLabel(),
        ]),
        'label' => $this->t('@target_label Position In Queue', [
          '@target_label' => $entity_type->getLabel(),
        ]),
        'help' => $this->t('Filter by the position of an item in the @target_label queue.', [
          '@target_label' => $entity_type->getLabel(),
        ]),
        'field' => 'delta',
        'field table' => $subqueue_items_table_name,
      ];
    }
  }

}
