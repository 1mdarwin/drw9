<?php

namespace Drupal\entityqueue\Plugin\views\relationship;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Plugin\ViewsHandlerManager;
use Drupal\views\ViewExecutable;
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
  public string $first_alias;

  /**
   * The Views join manager.
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $joinManager;
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs an EntityQueueRelationship object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\views\Plugin\ViewsHandlerManager $join_manager
   *   The views plugin join manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewsHandlerManager $join_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->joinManager = $join_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.views.join'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['limit_queue'] = ['default' => NULL];
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
      '#type' => 'radios',
      '#title' => $this->t('Limit to a specific entity queue'),
      '#options' => $options,
      '#default_value' => $this->options['limit_queue'],
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    parent::init($view, $display, $options);

    // Add an extra condition to limit results based on the queue selection.
    if ($this->options['limit_queue']) {
      $this->definition['extra'][] = [
        'field' => 'bundle',
        'value' => $this->options['limit_queue'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    if ($this->options['limit_queue']) {
      $queue = EntityQueue::load($this->options['limit_queue']);
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
    $tags = [];

    if ($this->options['limit_queue']) {
      $queue = EntityQueue::load($this->options['limit_queue']);
      $tags = $queue->getCacheTags();
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
    $this->addBundleWhereCondition();

    if (\Drupal::moduleHandler()->moduleExists('workspaces') && \Drupal::service('workspaces.manager')->hasActiveWorkspace()) {
      $field_name = 'items';
      $field_storage = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('entity_subqueue')[$field_name];
      $table_mapping = $this->entityTypeManager->getStorage('entity_subqueue')->getTableMapping();
      $this->addBaseJoins($table_mapping->getDedicatedRevisionTableName($field_storage));

      // Add the join to the workspace association.
      $definition = [
        'table' => 'workspace_association',
        'field' => 'target_entity_revision_id',
        'left_table' => $this->definition['field table'],
        'left_field' => 'revision_id',
        'extra' => [
          [
            'field' => 'target_entity_type_id',
            'value' => 'entity_subqueue',
          ],
          [
            'field' => 'workspace',
            'value' => \Drupal::service('workspaces.manager')->getActiveWorkspace()->id(),
          ],
        ],
        'type' => 'LEFT',
      ];

      $join = $this->joinManager->createInstance('standard', $definition);
      $join->adjusted = TRUE;
      $alias = 'entity_subqueue_workspace_association';
      $this->query->addRelationship($alias, $join, $this->definition['field table']);
      return;
    }

    $this->addBaseJoins($this->definition['field table']);
  }

  protected function addBundleWhereCondition(): void {
    // Add a 'where' condition if needed.
    if (!empty($this->definition['extra'])) {
      $bundles = [];

      // Future-proofing: support any number of selected bundles.
      foreach ($this->definition['extra'] as $extra) {
        if ($extra['field'] == 'bundle') {
          $bundles[] = $extra['value'];
        }
      }
      if (count($bundles) > 0) {
        $this->definition['join_extra'][] = [
          'field' => 'bundle',
          'value' => $bundles,
        ];
      }
    }
  }

  protected function addBaseJoins($items_field_table) {
    // Now - let's build the query.
    // @todo We can't simply call parent::query() because the parent class does
    //   not handle the 'join_id' configuration correctly, so we can't use our
    //   custom 'casted_field_join' plugin.
    $this->ensureMyTable();

    // First, relate our base table to the current base table to the
    // field, using the base table's id field to the field's column.
    $views_data = Views::viewsData()->get($this->table);
    $left_field = $views_data['table']['base']['field'];

    $first = [
      'left_table' => $this->tableAlias,
      'left_field' => $left_field,
      'table' => $items_field_table,
      'field' => $this->definition['field field'],
      'adjusted' => TRUE,
      'entity_type' => isset($views_data['table']['entity type']) ? $views_data['table']['entity type'] : NULL,
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

    $this->first_alias = $this->query->addTable($this->definition['field table'], $this->relationship, $first_join, $items_field_table);

    // Second, relate the field table to the entity specified using
    // the entity id on the field table and the entity's id field.
    $second = [
      'left_table' => $this->first_alias,
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

}
