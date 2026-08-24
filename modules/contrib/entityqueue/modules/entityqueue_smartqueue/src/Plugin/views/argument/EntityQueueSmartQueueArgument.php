<?php

declare(strict_types=1);

namespace Drupal\entityqueue_smartqueue\Plugin\views\argument;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument\StringArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a smart queue argument handler for views.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("entityqueue_smartqueue_name")
 */
class EntityQueueSmartQueueArgument extends StringArgument {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['smartqueue'] = ['default' => NULL];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Add list of queues.
    $smartqueues = $this->entityTypeManager->getStorage('entity_queue')->loadByProperties([
      'handler' => 'smartqueue',
    ]);
    $options = [];
    foreach ($smartqueues as $queue) {
      $options[$queue->id()] = $queue->label();
    }
    $form['smartqueue'] = [
      '#type' => 'select',
      '#title' => $this->t('Smartqueue ID'),
      '#default_value' => $this->options['smartqueue'],
      '#options' => $options,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setArgument($arg) {
    $queue = $this->options['smartqueue'];
    if ($arg && $queue && !str_starts_with((string) $arg, $queue)) {
      $arg = $queue . '__' . $arg;
    }
    return parent::setArgument($arg);
  }

}
