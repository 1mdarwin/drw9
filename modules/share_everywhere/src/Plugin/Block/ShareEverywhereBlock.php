<?php

namespace Drupal\share_everywhere\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\share_everywhere\ShareEverywhereServiceInterface;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Provides a 'Share Everywhere' Block.
 *
 * @Block(
 *   id = "share_everywhere_block",
 *   admin_label = @Translation("Share Everywhere Block"),
 * )
 */
class ShareEverywhereBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The ShareEverywhere service.
   *
   * @var \Drupal\share_everywhere\ShareEverywhereServiceInterface
   */
  protected $shareService;

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs an ShareEverywhereBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\share_everywhere\ShareEverywhereServiceInterface $share_service
   *   The module manager service.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ShareEverywhereServiceInterface $share_service, AliasManagerInterface $alias_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->shareService = $share_service;
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('share_everywhere.service'),
      $container->get('path_alias.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $url = Url::fromRoute('<current>');
    $id = str_replace('/', '', $this->aliasManager->getPathByAlias($url->toString()));
    $build = $this->shareService->build($url->setAbsolute()->toString(), $id);

    return $build;
  }

}
