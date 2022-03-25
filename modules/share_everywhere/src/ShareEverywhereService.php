<?php

namespace Drupal\share_everywhere;

use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a ShareEverywhereService service.
 */
class ShareEverywhereService implements ShareEverywhereServiceInterface {

  use StringTranslationTrait;

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The condition manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * Constructs an ShareEverywhereService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Configuration Factory.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ConditionManager $condition_manager) {
    $this->configFactory = $config_factory;
    $this->conditionManager = $condition_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function build($url, $id) {
    global $base_url;
    $config = $this->configFactory->get('share_everywhere.settings');
    $module_path = drupal_get_path('module', 'share_everywhere');
    $build = ['#theme' => 'share_everywhere'];
    $buttons = [];
    $library = [];

    switch ($config->get('alignment')) {
      case 'left':
        $build['#attributes']['class'] = [
          'se-align-left',
        ];
        break;

      case 'right':
        $build['#attributes']['class'] = [
          'se-align-right',
        ];
        break;
    }

    $share_buttons = $config->get('buttons');
    uasort($share_buttons, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    foreach ($share_buttons as $key => $button) {
      if ($key == 'facebook_like' && $button['enabled']) {
        $build['#facebook_like'] = [
          '#theme' => 'se_facebook_like',
          '#url' => $url,
        ];
        array_push($build['#attributes']['class'], 'se-has-like');
      }
      elseif ($button['enabled']) {
        $buttons[$key] = [
          '#theme' => 'se_' . $key,
          '#url' => $url,
        ];

        if ($key != 'facebook_like' && $config->get('style') == 'share_everywhere') {
          $buttons[$key]['#content'] = [
            '#type' => 'html_tag',
            '#tag' => 'img',
            '#attributes' => [
              'src' => $base_url . '/' . $module_path . '/img/' . $button['image'],
              'title' => $this->t($button['title']),
              'alt' => $this->t($button['title']),
            ],
          ];
        }
        elseif ($config->get('style') == 'custom') {
          $buttons[$key]['#content'] = $this->t($button['name']);
        }
      }
    }
    $build['#buttons'] = $buttons;
    $build['#se_links_id'] = 'se-links-' . $id;

    if ($config->get('display_title')) {
      $build['#title'] = $this->t($config->get('title'));
    }

    $build['#share_icon'] = [
      'id' => 'se-trigger-' . $id,
      'src' => $base_url . '/' . $module_path . '/img/' . $config->get('share_icon.image'),
      'alt' => $this->t($config->get('share_icon.alt')),
    ];

    if (!$config->get('collapsible')) {
      $build['#share_icon']['class'] = 'se-disabled';
    }

    if ($config->get('style') == 'share_everywhere') {
      if ($config->get('collapsible')) {
        $library = [
          'share_everywhere/share_everywhere.css',
          'share_everywhere/share_everywhere.js',
        ];
      }
      else {
        $library = [
          'share_everywhere/share_everywhere.css',
        ];
      }
    }
    elseif ($config->get('style') == 'custom') {
      if ($config->get('include_css')) {
        $library = [
          'share_everywhere/share_everywhere.css',
        ];
      }

      if ($config->get('include_js') && $config->get('collapsible')) {
        array_push($library, 'share_everywhere/share_everywhere.js');
      }
    }

    if (!$config->get('collapsible') || ($config->get('style') == 'custom' && !$config->get('include_js'))) {
      $build['#is_active'] = 'se-active';
    }
    elseif ($config->get('collapsible')) {
      $build['#is_active'] = 'se-inactive';
    }

    if (!empty($library)) {
      $build['#attached'] = [
        'library' => $library,
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function isRestricted($view_mode) {
    $config = $this->configFactory->get('share_everywhere.settings');

    switch ($view_mode) {
      case 'search_result':
      case 'search_index':
      case 'rss':
        return TRUE;
    }

    $restricted_pages = $config->get('restricted_pages.pages');

    if (is_array($restricted_pages) && !empty($restricted_pages)) {
      $restriction_type = $config->get('restricted_pages.type');

      // Replace a single / with <front> so it matches with the front path.
      if (($index = array_search('/', $restricted_pages)) !== FALSE) {
        $restricted_pages[$index] = '<front>';
      }

      /** @var \Drupal\system\Plugin\Condition\RequestPath $request_path_condition */
      $request_path_condition = $this->conditionManager->createInstance('request_path', [
        'pages' => implode("\n", $restricted_pages),
        'negate' => $restriction_type == 'show' ? TRUE : FALSE,
      ]);

      return $request_path_condition->execute();
    }

    return FALSE;
  }

}
