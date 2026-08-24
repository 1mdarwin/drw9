<?php

declare(strict_types=1);

namespace Drupal\entityqueue\Plugin\EntityQueueHandler;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entityqueue\Attribute\EntityQueueHandler;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\entityqueue\EntityQueueHandlerBase;
use Drupal\entityqueue\EntityQueueInterface;
use Drupal\entityqueue\EntitySubqueueInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an entity queue handler that manages a single subqueue.
 *
 * @EntityQueueHandler(
 *   id = "simple",
 *   title = @Translation("Simple queue"),
 *   description = @Translation("Provides a queue with a single (fixed) subqueue."),
 * )
 */
#[EntityQueueHandler(
  id: 'simple',
  title: new TranslatableMarkup('Simple queue'),
  description: new TranslatableMarkup('Provides a queue with a single (fixed) subqueue.'),
)]
class Simple extends EntityQueueHandlerBase implements ContainerFactoryPluginInterface {

  use RedirectDestinationTrait;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityRepositoryInterface $entity_repository, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityRepository = $entity_repository;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository'),
      $container->get('module_handler'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function supportsMultipleSubqueues() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAutomatedSubqueues() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubqueueName(EntitySubqueueInterface $subqueue): string {
    // A simple queue has exactly one subqueue, identified by the queue ID.
    return $this->queue->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getQueueListBuilderOperations() {
    // Simple queues have just one subqueue, so we can link directly to the edit
    // form.
    $subqueue = EntitySubqueue::load($this->queue->id());
    $subqueue = $this->entityRepository->getTranslationFromContext($subqueue);
    $operations['edit_subqueue'] = [
      'title' => $this->t('Edit items'),
      'weight' => -9,
      'url' => $subqueue->toUrl('edit-form', [
        'language' => $this->languageManager->getCurrentLanguage(),
        'query' => $this->getRedirectDestination()->getAsArray(),
      ]),
    ];

    // Add a 'Translate' operation if translation is enabled for this queue.
    $translate_access = $this->moduleHandler->moduleExists('content_translation') && DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '11.4.0',
      fn() => \Drupal::service('content_translation.manager')->access($subqueue),
      fn() => content_translation_translate_access($subqueue),
    )->isAllowed();
    if ($translate_access) {
      $operations['translate_subqueue'] = [
        'title' => $this->t('Translate subqueue'),
        'url' => $subqueue->toUrl('drupal:content-translation-overview'),
        'weight' => -8,
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsOperation() {
    // Simple queues have just one subqueue, so link directly to its items.
    $subqueue = EntitySubqueue::load($this->queue->id());
    return [
      'title' => $this->t('Edit items'),
      'url' => $subqueue->toUrl('canonical'),
      'weight' => -10,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function onQueuePostSave(EntityQueueInterface $queue, EntityStorageInterface $storage, $update = TRUE) {
    // Make sure that every simple queue has a subqueue.
    if ($update) {
      $subqueue = EntitySubqueue::load($queue->id());
      $subqueue->setTitle($queue->label());
    }
    else {
      $subqueue = EntitySubqueue::create([
        'queue' => $queue->id(),
        'name' => $queue->id(),
        'title' => $queue->label(),
        'langcode' => $queue->language()->getId(),
      ]);
    }

    $subqueue->save();
  }

  /**
   * {@inheritdoc}
   */
  public function onQueuePostDelete(EntityQueueInterface $queue, EntityStorageInterface $storage) {
    // Delete the subqueue when the parent queue is deleted.
    if ($subqueue = EntitySubqueue::load($queue->id())) {
      $subqueue->delete();
    }
  }

}
