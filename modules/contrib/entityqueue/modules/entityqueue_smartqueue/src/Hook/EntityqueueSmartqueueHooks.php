<?php

declare(strict_types=1);

namespace Drupal\entityqueue_smartqueue\Hook;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\entityqueue\EntityQueueInterface;

/**
 * Hook implementations for the Entityqueue Smartqueue module.
 */
class EntityqueueSmartqueueHooks {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManager $entityFieldManager,
    protected RouteBuilderInterface $routeBuilder,
    protected LocalTaskManagerInterface $localTaskManager,
  ) {}

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      // Main module help for the entityqueue_smartqueue module.
      case 'help.page.entityqueue_smartqueue':
        $output = '';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('Entityqueue Smartqueue - Automated queues for each entity of a given entity type.') . '</p>';
        return $output;
    }
  }

  /**
   * Implements hook_entity_field_storage_info().
   */
  #[Hook('entity_field_storage_info')]
  public function entityFieldStorageInfo(EntityTypeInterface $entity_type) {
    if ($entity_type->id() === 'entity_subqueue') {
      $field_storage_definitions['attached_entity'] = BaseFieldDefinition::create('entity_reference')
        ->setName('attached_entity')
        ->setTargetEntityTypeId('entity_subqueue')
        ->setTargetBundle(NULL)
        ->setLabel($this->t('Attached entity'))
        // This setting is overridden per bundle (queue) in
        // entity_bundle_field_info(), but we need to default to a target entity
        // type that uses strings IDs, in order to allow both integers and
        // strings to be stored by the default entity reference field storage.
        ->setSetting('target_type', 'entity_subqueue');

      return $field_storage_definitions;
    }
  }

  /**
   * Implements hook_entity_bundle_field_info().
   */
  #[Hook('entity_bundle_field_info')]
  public function entityBundleFieldInfo(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    if ($entity_type->id() === 'entity_subqueue' && ($queue = EntityQueue::load($bundle)) && $queue->getHandler() === 'smartqueue') {
      $field_storage_definitions = $this->entityFieldManager->getActiveFieldStorageDefinitions('entity_subqueue');

      $entity_type_id = $queue->getHandlerConfiguration()['entity_type'];
      $field_definitions['attached_entity'] = FieldDefinition::createFromFieldStorageDefinition($field_storage_definitions['attached_entity']);
      $field_definitions['attached_entity']->setTargetBundle($bundle);
      $field_definitions['attached_entity']->setSetting('target_type', $entity_type_id);
      // The selection handler reads target_type from the field storage
      // definition, which is 'entity_subqueue' so both integer and string IDs
      // can be stored. Pass the real target type through handler_settings so
      // reference validation runs against the right entity type instead of
      // querying the entity_subqueue table.
      $field_definitions['attached_entity']->setSetting('handler_settings', ['target_type' => $entity_type_id]);

      return $field_definitions;
    }
  }

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity) {
    if ($entity instanceof EntityQueueInterface) {
      $this->refreshSmartqueueNavigation($entity);
      return;
    }

    $queues = $this->getQueues($entity->getEntityTypeId());
    foreach ($queues as $queue) {
      // Check if the entity that got inserted is of the relevant bundle.
      if (!in_array($entity->bundle(), $queue->getHandlerConfiguration()['bundles'], TRUE)) {
        continue;
      }

      $subqueue = EntitySubqueue::create([
        'queue' => $queue->id(),
        'name' => $queue->id() . '__' . $entity->id(),
        'title' => $entity->label(),
        'langcode' => $queue->language()->getId(),
        'attached_entity' => $entity,
      ]);
      $subqueue->save();
    }
  }

  /**
   * Implements hook_entity_update().
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity) {
    if ($entity instanceof EntityQueueInterface) {
      // The local task tab shows the queue label, so rebuild it when the queue
      // changes.
      $this->refreshSmartqueueNavigation($entity);
      return;
    }

    // Subqueues only mirror the source entity's label, so there's nothing to
    // do unless the label changed. Skipping early avoids loading and saving
    // every related subqueue on saves that don't touch the label, such as
    // reordering taxonomy terms.
    $original = method_exists($entity, 'getOriginal')
      ? $entity->getOriginal()
      : ($entity->original ?? NULL);
    if ($original?->label() === $entity->label()) {
      return;
    }

    $queues = $this->getQueues($entity->getEntityTypeId());
    foreach ($queues as $queue) {
      // Check if the entity that got updated is of the relevant bundle.
      if (!in_array($entity->bundle(), $queue->getHandlerConfiguration()['bundles'], TRUE)) {
        continue;
      }

      if ($subqueue = EntitySubqueue::load($queue->id() . '__' . $entity->id())) {
        $subqueue->set('title', $entity->label());
        $subqueue->save();
      }
    }
  }

  /**
   * Implements hook_entity_delete().
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity) {
    if ($entity instanceof EntityQueueInterface) {
      $this->refreshSmartqueueNavigation($entity);
      return;
    }

    $queues = $this->getQueues($entity->getEntityTypeId());
    foreach ($queues as $queue) {
      // Check if the entity that got deleted is of the relevant bundle.
      if (!in_array($entity->bundle(), $queue->getHandlerConfiguration()['bundles'], TRUE)) {
        continue;
      }

      if ($subqueue = EntitySubqueue::load($queue->id() . '__' . $entity->id())) {
        $subqueue->delete();
      }
    }
  }

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data) {
    $data['entity_subqueue']['name']['argument']['title'] = $this->t('Entityqueue smartqueue name');
    $data['entity_subqueue']['name']['argument']['id'] = 'entityqueue_smartqueue_name';
  }

  /**
   * Implements hook_entity_operation().
   *
   * Adds an operation linking each entity to its smartqueue subqueue, so the
   * queue items can be managed straight from the entity's list row. An entity
   * can belong to more than one smartqueue, so this returns one operation per
   * queue.
   *
   * The $cacheability argument is optional because Drupal 10.2 invokes
   * hook_entity_operation() with only the entity; it was added in Drupal 11.
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity, ?CacheableMetadata $cacheability = NULL): array {
    $cacheability ??= new CacheableMetadata();
    $operations = [];
    foreach ($this->getQueues($entity->getEntityTypeId()) as $queue) {
      if (!in_array($entity->bundle(), $queue->getHandlerConfiguration()['bundles'], TRUE)) {
        continue;
      }

      $subqueue = EntitySubqueue::load($queue->id() . '__' . $entity->id());
      if ($subqueue === NULL) {
        continue;
      }

      $url = $subqueue->toUrl('edit-form');
      $access = $url->access(NULL, TRUE);
      $cacheability->addCacheableDependency($access);
      if (!$access->isAllowed()) {
        continue;
      }

      $operations['entityqueue_smartqueue__' . $queue->id()] = [
        'title' => $this->t('Manage @queue items', ['@queue' => $queue->label()]),
        'url' => $url,
        'weight' => 100,
      ];
    }

    return $operations;
  }

  /**
   * Rebuilds the smartqueue routes and local tasks after a queue changes.
   *
   * Creating, renaming, or deleting a smartqueue changes the redirect route and
   * the local task tab it powers, so the router and local task caches need to
   * be refreshed.
   */
  protected function refreshSmartqueueNavigation(EntityQueueInterface $queue): void {
    if ($queue->getHandler() !== 'smartqueue') {
      return;
    }
    $this->routeBuilder->setRebuildNeeded();
    $this->localTaskManager->clearCachedDefinitions();
  }

  /**
   * Gets the smartqueues that are configured for an entity type.
   *
   * @param string $entity_type_id
   *   The ID of the target entity type to check for.
   *
   * @return \Drupal\entityqueue\EntityQueueInterface[]
   *   An array of queue entities that are using the 'smartqueue' handler.
   */
  protected function getQueues($entity_type_id) {
    /** @var \Drupal\entityqueue\EntityQueueInterface[] $queues */
    $queues = $this->entityTypeManager->getStorage('entity_queue')
      ->loadByProperties([
        'handler' => 'smartqueue',
        'handler_configuration.entity_type' => $entity_type_id,
      ]);

    return $queues;
  }

}
