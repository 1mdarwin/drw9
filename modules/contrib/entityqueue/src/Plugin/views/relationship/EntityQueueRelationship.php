<?php

declare(strict_types=1);

namespace Drupal\entityqueue\Plugin\views\relationship;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Plugin\ViewsHandlerManager;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A relationship handler for entity queues.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("entity_queue")
 */
class EntityQueueRelationship extends RelationshipPluginBase implements CacheableDependencyInterface {

  /**
   * The alias for the left table.
   */
  public string $firstAlias;

  /**
   * The Views join manager.
   */
  protected ViewsHandlerManager $joinManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewsHandlerManager $join_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->joinManager = $join_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.views.join')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['limit_queue'] = ['default' => []];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $queues = EntityQueue::loadMultipleByTargetType($this->getEntityType());
    $options = [];
    foreach ($queues as $queue) {
      $options[$queue->id()] = $queue->label();
    }

    $form['limit_queue'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Limit to a specific entity queue'),
      '#options' => $options,
      // Normalize to a list so the checkboxes pre-check correctly even when
      // config still holds the pre-update scalar value.
      '#default_value' => $this->getLimitQueueIds(),
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // The checkboxes element keys every option, storing unchecked ones as a
    // falsy value. Keep only the selected queues and store them as a plain
    // list, which is what the 'limit_queue' sequence schema expects.
    $options = &$form_state->getValue('options');
    $options['limit_queue'] = array_values(array_filter($options['limit_queue']));

    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * Returns the IDs of the queues this relationship is limited to.
   *
   * The 'limit_queue' option comes from a checkboxes element, which stores an
   * unchecked box as a falsy value, so those are filtered out.
   *
   * @return string[]
   *   The selected queue IDs.
   */
  protected function getLimitQueueIds(): array {
    return array_values(array_filter((array) $this->options['limit_queue']));
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    foreach (EntityQueue::loadMultiple($this->getLimitQueueIds()) as $queue) {
      $dependencies[$queue->getConfigDependencyKey()][] = $queue->getConfigDependencyName();
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // The config tag of each queue this relationship is limited to. Subqueue
    // content (membership/order) invalidation is already provided by Views,
    // which loads the referenced entity_subqueue per row and merges its cache
    // tags into the view.
    $tags = [];
    foreach (EntityQueue::loadMultiple($this->getLimitQueueIds()) as $queue) {
      $tags = Cache::mergeTags($tags, $queue->getCacheTags());
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $bundles = $this->getLimitQueueIds();

    // Limit the relationship's join to the selected queues.
    if ($bundles) {
      $this->definition['join_extra'][] = [
        'field' => 'bundle',
        'value' => $bundles,
      ];
    }

    // Editing a subqueue in a workspace creates a pending revision instead of
    // changing the live one, so the current items are in the revision field
    // table, not the default one. In a workspace, join that table and keep only
    // each subqueue's active revision. Outside a workspace, use the default
    // field table.
    $workspace_aware = $this->isWorkspaceAware();
    if ($workspace_aware) {
      // Put this on the join, not in a WHERE clause. With a non-required (LEFT)
      // relationship a node that is only in an older revision of the queue
      // should still show up as a non-member. Filtering after the join would
      // match its old-revision row and then drop it; restricting the join means
      // it matches no row and stays as a NULL. Revision IDs are unique across
      // all subqueues, so an IN list picks the right rows on its own.
      $this->definition['join_extra'][] = [
        'field' => 'revision_id',
        'value' => $this->getActiveRevisionIds($bundles),
      ];
    }

    // Now - let's build the query.
    // @todo We can't simply call parent::query() because the parent class does
    //   not handle the 'join_id' configuration correctly, so we can't use our
    //   custom 'casted_field_join' plugin.
    $this->ensureMyTable();

    $field_table = $workspace_aware ? $this->definition['field revision table'] : $this->definition['field table'];

    // First, relate our base table to the current base table to the
    // field, using the base table's id field to the field's column.
    $views_data = Views::viewsData()->get($this->table);
    $left_field = $views_data['table']['base']['field'];

    $first = [
      'left_table' => $this->tableAlias,
      'left_field' => $left_field,
      'table' => $field_table,
      'field' => $this->definition['field field'],
      'adjusted' => TRUE,
      'entity_type' => $views_data['table']['entity type'] ?? NULL,
    ];
    if (!empty($this->options['required'])) {
      $first['type'] = 'INNER';
    }

    if (!empty($this->definition['join_extra'])) {
      $first['extra'] = $this->definition['join_extra'];
    }

    // Use our custom 'casted_field_join' handler in order to handle
    // relationships to integers and strings IDs from the same table properly.
    $first_join = $this->joinManager->createInstance('casted_field_join', $first);

    $this->firstAlias = $this->query->addTable($field_table, $this->relationship, $first_join);

    // Second, relate the field table to the entity specified using
    // the entity id on the field table and the entity's id field.
    $second = [
      'left_table' => $this->firstAlias,
      'left_field' => 'entity_id',
      'table' => $this->definition['base'],
      'field' => $this->definition['base field'],
      'adjusted' => TRUE,
    ];

    if (!empty($this->options['required'])) {
      $second['type'] = 'INNER';
    }

    if (!empty($this->definition['join_id'])) {
      $id = $this->definition['join_id'];
    }
    else {
      $id = 'standard';
    }
    $second_join = $this->joinManager->createInstance($id, $second);
    $second_join->adjusted = TRUE;

    // Use a short alias for this:
    $alias = $this->definition['field_name'] . '_' . $this->table;

    $this->alias = $this->query->addRelationship($alias, $second_join, $this->definition['base'], $this->relationship);
  }

  /**
   * Whether the relationship's query should target workspace revisions.
   */
  protected function isWorkspaceAware(): bool {
    return \Drupal::moduleHandler()->moduleExists('workspaces')
      && \Drupal::service('workspaces.manager')->hasActiveWorkspace();
  }

  /**
   * Returns the active subqueue revision IDs for the current workspace.
   *
   * This relationship joins from the target entity into the subqueue's items
   * field, which is the opposite direction from what the core Workspaces query
   * alter handles, so it does not pick the workspace revision for us. We work
   * it out instead: a subqueue entity query is already workspace-aware, and its
   * result is keyed by each subqueue's active revision, which is the workspace
   * revision for subqueues edited there and the default revision otherwise.
   *
   * @param string[] $bundles
   *   The queue IDs the relationship is limited to, or an empty array for all.
   *
   * @return int[]
   *   The active revision ID of each relevant subqueue.
   */
  protected function getActiveRevisionIds(array $bundles): array {
    $query = \Drupal::entityQuery('entity_subqueue')->accessCheck(FALSE);
    if ($bundles) {
      $query->condition('queue', $bundles, 'IN');
    }
    $revision_ids = array_keys($query->execute());

    // Guard against an empty IN list producing invalid SQL when no subqueue
    // exists; a non-existent revision simply matches no rows.
    return $revision_ids ?: [0];
  }

}
