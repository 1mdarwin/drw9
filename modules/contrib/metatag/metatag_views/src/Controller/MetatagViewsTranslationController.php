<?php

namespace Drupal\metatag_views\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\metatag\MetatagManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Translate Views meta tags.
 */
class MetatagViewsTranslationController extends ControllerBase {

  /**
   * The View storage interface.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $viewStorage;

  /**
   * The Metatag manager.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityStorageInterface $viewStorage, MetatagManagerInterface $metatagManager, LanguageManagerInterface $languageManager, RequestStack $request_stack, ConfigFactoryInterface $config_factory) {
    $this->viewStorage = $viewStorage;
    $this->metatagManager = $metatagManager;
    $this->languageManager = $languageManager;
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('view'),
      $container->get('metatag.manager'),
      $container->get('language_manager'),
      $container->get('request_stack'),
      $container->get('config.factory')
    );
  }

  /**
   * Language translations overview page for a views.
   *
   * @return array
   *   Page render array.
   */
  public function itemPage() {
    // @todo This method doesn't exist?
    $view_id = $this->requestStack->get('view_id');
    // @todo This method doesn't exist?
    $display_id = $this->requestStack->get('display_id');

    $view = $this->viewStorage->load($view_id);
    $original_langcode = $view->language()->getId();

    $config_name = $view->getConfigDependencyName();
    $config_path = 'display.' . $display_id . '.display_options.display_extenders.metatag_display_extender.metatags';

    $configuration = $this->configFactory->get($config_name);
    $config_source = $configuration->getOriginal($config_path, FALSE);

    $page['languages'] = [
      '#type' => 'table',
      '#header' => [$this->t('Language'), $this->t('Operations')],
    ];

    $languages = $this->languageManager->getLanguages();
    foreach ($languages as $language) {
      $langcode = $language->getId();
      $language_name = $language->getName();
      $operations = [];

      // Prepare the language name and the operations depending on whether this
      // is the original language or not.
      if ($langcode == $original_langcode) {
        $language_name = '<strong>' . $this->t('@language (original)', [
          '@language' => $language_name,
        ]) . '</strong>';

        // Default language can only be edited, no add/delete.
        $operations['edit'] = [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('metatag_views.metatags.edit', [
            'view_id' => $view_id,
            'display_id' => $display_id,
          ]),
        ];
      }
      else {
        // Get the metatag translation for this language.
        // @todo The getLanguageConfigOverride() method doesn't exist?
        $config_translation = $this->languageManager
          ->getLanguageConfigOverride($langcode, $config_name)
          ->get($config_path);

        // If no translation exists for this language, link to add one.
        if (!$config_translation || $config_translation == $config_source) {
          $operations['add'] = [
            'title' => $this->t('Add'),
            'url' => Url::fromRoute('metatag_views.metatags.translate', [
              'view_id' => $view_id,
              'display_id' => $display_id,
              'langcode' => $langcode,
            ]),
          ];
        }
        else {
          // Otherwise, link to edit the existing translation.
          $operations['edit'] = [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('metatag_views.metatags.translate', [
              'view_id' => $view_id,
              'display_id' => $display_id,
              'langcode' => $langcode,
            ]),
          ];
          // @todo Operations delete.
        }
      }

      $page['languages'][$langcode]['language'] = [
        '#markup' => $language_name,
      ];

      $page['languages'][$langcode]['operations'] = [
        '#type' => 'operations',
        '#links' => $operations,
        // Even if the mapper contains multiple language codes, the source
        // configuration can still be edited.
        // @code
        // '#access' => ($langcode == $original_langcode) || $operations_access,
        // @endcode
      ];
    }

    return $page;
  }

}
