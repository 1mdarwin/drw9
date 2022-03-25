<?php

namespace Drupal\widget\Plugin\Block;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\ctools\Plugin\BlockPluginCollection;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'widget' block.
 *
 * @Block(
 *   id = "widget_block",
 *   admin_label = @Translation("Widget"),
 *   category = @Translation("Widget")
 * )
 */

class WidgetBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use PluginDependencyTrait;
  use ContextAwarePluginAssignmentTrait;

  /**
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutManager;

  /**
   * @var \Drupal\ctools\Plugin\BlockPluginCollection
   */
  protected $blockPluginCollection;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockManagerInterface $block_manager, LayoutPluginManagerInterface $layout_manager, AccountInterface $current_user, ContextHandlerInterface $context_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockManager = $block_manager;
    $this->layoutManager = $layout_manager;
    $this->currentUser = $current_user;
    $this->contextHandler = $context_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.block'),
      $container->get('plugin.manager.core.layout'),
      $container->get('current_user'),
      $container->get('context.handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'blocks' => [],
      'layout' => NULL,
      'classes' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLayoutRegions() {
    if ($this->layoutManager->hasDefinition($this->configuration['layout'])) {
      $layout_definition = $this->getLayout()->getPluginDefinition();
      return $layout_definition->getRegionLabels();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (isset($this->configuration['layout']) && !empty($this->configuration['blocks'])) {
      $regions = [];
      foreach ($this->getBlocksWithContext() as $region_id => $block_plugin) {
        if ($block_plugin->access($this->currentUser)) {
          $regions[$region_id] = $this->buildBlock($block_plugin);
        }
      }
      return $this->getLayout()->build($regions);
    }
    return [];
  }

  /**
   * Builds a block.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $block_plugin
   *
   * @return array|null
   */
  protected function buildBlock(BlockPluginInterface $block_plugin) {
    $block_build = [
      '#theme' => 'block',
      '#configuration' => $block_plugin->getConfiguration(),
      '#plugin_id' => $block_plugin->getPluginId(),
      '#base_plugin_id' => $block_plugin->getBaseId(),
      '#derivative_plugin_id' => $block_plugin->getDerivativeId(),
    ];
    $block_build['content'] = $block_plugin->build();
    return $block_build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $block_plugins = $this->blockManager->getDefinitionsForContexts($this->getContexts());

    $block_options = [];
    foreach ($block_plugins as $plugin_id => $block_definition) {
      $block_options[(string) $block_definition['category']][$plugin_id] = (string) $block_definition['admin_label'];
    }

    // @todo: Remove Workaround for https://www.drupal.org/node/2798261.
    $complete_form_state = $form_state;
    if ($form_state instanceof SubformStateInterface) {
      $complete_form_state = $form_state->getCompleteFormState();
    }
    $widget_blocks = (array) ($complete_form_state->getValue(['settings', 'blocks']) ?: $this->configuration['blocks']);
    $layout = $complete_form_state->getValue(['settings', 'layout']) ?: $this->configuration['layout'];
    $classes = $complete_form_state->getValue(['settings', 'classes']) ?: $this->configuration['classes'];

    $ajax_properties = [
      '#ajax' => [
        'callback' => [$this, 'widgetBlockAJAXCallback'],
        'wrapper' => 'widget-block-wrapper',
        'effect' => 'fade',
      ],
    ];

    $form = parent::blockForm($form, $form_state);

    $layouts = [];
    foreach (\Drupal::service('plugin.manager.core.layout')->getDefinitions() as $id => $definition) {
      //if ($definition['type'] == 'partial') {
        $layouts[$id] = $definition->getLabel();
      //}
    }

    $form['layout'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Widget layout'),
      '#options' => $layouts,
      '#default_value' => $layout,
    ] + $ajax_properties;

    $form['blocks'] = [
      '#type' => 'container',
      '#prefix' => '<div id="widget-block-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSS Classes'),
      '#default_value' => $classes,
    ];

    if (!$layout) {
      return $form;
    }
    if ($layout != $this->configuration['layout']) {
      $this->configuration['layout'] = $layout;
    }
    foreach ($this->getLayoutRegions() as $region_id => $region_label) {
      $block_config = isset($widget_blocks[$region_id]) ? $widget_blocks[$region_id] : [];
      $form['blocks'][$region_id] = [
        '#type' => 'details',
        '#title' => $region_label,
        '#open' => TRUE,
      ];

      $form['blocks'][$region_id]['id'] = [
        '#type' => 'select',
        '#title' => $this->t('Block'),
        '#options' => $block_options,
        '#default_value' => isset($block_config['id']) ? $block_config['id'] : NULL,
        '#empty_option' => $this->t('- None -'),
      ] + $ajax_properties;

      if (!empty($block_config['id'])) {
        $block_plugin = $this->blockManager->createInstance($block_config['id'], $block_config);
        $form['blocks'][$region_id] += $block_plugin->buildConfigurationForm([], $form_state);

        if ($block_plugin instanceof ContextAwarePluginInterface) {
          $form['blocks'][$region_id]['context_mapping'] = $this->addContextAssignmentElement($block_plugin, $this->getContexts());
        }
      }
    }

    return $form;
  }

  /**
   * @{@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['blocks'] = [];
    $this->configuration['layout'] = $form_state->getValue('layout');
    $this->configuration['classes'] = $form_state->getValue('classes');
    // Set empty block ID's to NULL.
    foreach ($form_state->getValue('blocks') as $region_id => $block) {
      if (!empty($block['id'])) {
        $block_plugin = $this->blockManager->createInstance($block['id'], []);
        if ($form_state instanceof SubformState) {
          $complete_form_state = $form_state->getCompleteFormState();
          $sub_form_state = SubformState::createForSubform($form['settings']['blocks'][$region_id], $form, $complete_form_state);
        }
        else {
          $sub_form_state = (new FormState())->setValues($form_state->getValue(['blocks', $region_id]));
        }
        $block_plugin->submitConfigurationForm($form['settings']['blocks'][$region_id], $sub_form_state);
        $this->configuration['blocks'][$region_id] = $block_plugin->getConfiguration();
      }
    }
  }

  /**
   * Used by select widgets of block configuration form.
   */
  public function widgetBlockAJAXCallback($form, FormStateInterface $form_state) {
    return $form['settings']['blocks'];
  }

  /**
   * @return \Drupal\ctools\Plugin\BlockPluginCollection
   */
  protected function getBlockCollection() {
    if (!$this->blockPluginCollection) {
      $this->blockPluginCollection = new BlockPluginCollection($this->blockManager, $this->configuration['blocks']);
    }
    return $this->blockPluginCollection;
  }

  /**
   * Yields blocks with contexts assigned.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface[]
   */
  protected function getBlocksWithContext() {
    foreach ($this->getBlockCollection() as $region_id => $block) {
      if ($block instanceof ContextAwarePluginInterface) {
        try {
          $this->contextHandler->applyContextMapping($block, $this->getContexts());
        } catch (ContextException $e) {
          // Ignore blocks that fail to apply context.
          continue;
        }

      }
      yield $region_id => $block;
    }
  }

  /**
   * @return \Drupal\Core\Layout\LayoutInterface
   */
  protected function getLayout() {
    if ($this->configuration['layout']) {
      return $this->layoutManager->createInstance($this->configuration['layout']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts() {
    // When editing, attempt to get the contexts from the block display.
    if ($block_display = \Drupal::routeMatch()->getParameter('block_display')) {
      $cached_values = \Drupal::service('user.shared_tempstore')
        ->get('page_manager.block_display')
        ->get($block_display);
      if (!empty($cached_values['contexts'])) {
        $contexts = [];
        foreach ($cached_values['contexts'] as $context_name => $context_definition) {
          $contexts[$context_name] = new Context($context_definition);
        }
        return $contexts;
      }
    }
    // If we are on a page manager page, return the available context.
    if (\Drupal::routeMatch()->getParameter('page_manager_page_variant')) {
      return \Drupal::routeMatch()->getParameter('page_manager_page_variant')->getContexts();
    }
    return (array) parent::getContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    foreach ($this->getBlockCollection() as $block) {
      $this->calculatePluginDependencies($block);
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Do not call the parent since that would add cache contexts of all
    //  contexts.
    $cache_contexts = [];
    foreach ($this->getBlocksWithContext() as $block) {
      $cache_contexts = Cache::mergeContexts($cache_contexts, $block->getCacheContexts());
    }
    return $cache_contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Do not call the parent since that would all cache tags of all contexts.
    $cache_tags = [];

    foreach ($this->getBlocksWithContext() as $block) {
      $cache_tags = Cache::mergeTags($cache_tags, $block->getCacheTags());
    }
    return $cache_tags;
  }


  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Do not call the parent since that would add cache max age of all contexts.
    $max_age = Cache::PERMANENT;
    foreach ($this->getBlocksWithContext() as $block) {
      $max_age = Cache::mergeMaxAges($max_age, $block->getCacheMaxAge());
    }
    return $max_age;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $result = AccessResult::allowed();
    // @todo How to determine visibiliy of the whole widget? For now, look for
    //   the "primary" inner block, assume either "main" or "left".
    foreach ($this->getBlocksWithContext() as $region => $block) {
      if (in_array($region, ['main', 'left'])) {
        $result = $result->andIf($block->access($account, TRUE));
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    if ($this->blockPluginCollection) {
      $this->configuration['blocks'] = $this->blockPluginCollection->getConfiguration();
      $this->blockPluginCollection = NULL;
    }

    return parent::__sleep();
  }

}
