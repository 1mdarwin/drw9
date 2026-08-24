<?php

declare(strict_types=1);

namespace Drupal\entityqueue;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for EntityQueueHandler plugins.
 */
abstract class EntityQueueHandlerBase extends PluginBase implements EntityQueueHandlerInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The entity queue that is using this plugin.
   *
   * @var \Drupal\entityqueue\EntityQueueInterface
   */
  protected $queue;

  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Override this.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Override this.
  }

  /**
   * {@inheritdoc}
   */
  public function setQueue(EntityQueueInterface $queue) {
    $this->queue = $queue;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubqueueName(EntitySubqueueInterface $subqueue): string {
    // Derive a unique machine name from the title. Mirrors the transformation
    // the 'machine_name' form element applies: lower case, non [a-z0-9_] runs
    // collapsed to '_', capped at 64 characters. A numeric suffix is appended
    // until the name is free.
    $storage = \Drupal::entityTypeManager()->getStorage($subqueue->getEntityTypeId());
    $transliterated = \Drupal::transliteration()->transliterate((string) $subqueue->getTitle(), $subqueue->language()->getId(), '_');
    $base = mb_strtolower($transliterated);
    $base = preg_replace('/[^a-z0-9_]+/', '_', $base);
    $base = trim((string) preg_replace('/_+/', '_', $base), '_');
    if ($base === '') {
      $base = 'subqueue';
    }
    $base = substr($base, 0, 64);

    $candidate = $base;
    $i = 1;
    while ($storage->load($candidate) !== NULL) {
      $i++;
      $suffix = '_' . $i;
      $candidate = substr($base, 0, 64 - strlen($suffix)) . $suffix;
    }

    return $candidate;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueueListBuilderOperations() {
    // Add an operation to list all subqueues by default.
    $operations['view_subqueues'] = [
      'title' => $this->t('View subqueues'),
      'weight' => -9,
      'url' => $this->queue->toUrl('subqueue-list'),
    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsOperation() {
    return [
      'title' => $this->t('Subqueues'),
      'url' => $this->queue->toUrl('subqueue-list'),
      'weight' => -10,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function onQueuePreSave(EntityQueueInterface $queue, EntityStorageInterface $storage) {}

  /**
   * {@inheritdoc}
   */
  public function onQueuePostSave(EntityQueueInterface $queue, EntityStorageInterface $storage, $update = TRUE) {}

  /**
   * {@inheritdoc}
   */
  public function onQueuePreDelete(EntityQueueInterface $queue, EntityStorageInterface $storage) {}

  /**
   * {@inheritdoc}
   */
  public function onQueuePostDelete(EntityQueueInterface $queue, EntityStorageInterface $storage) {}

  /**
   * {@inheritdoc}
   */
  public function onQueuePostLoad(EntityQueueInterface $queue, EntityStorageInterface $storage) {}

}
