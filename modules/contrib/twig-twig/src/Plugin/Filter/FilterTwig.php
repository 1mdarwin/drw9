<?php

namespace Drupal\twig\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter that allows text to be rendered using the Twig engine.
 *
 * @Filter(
 *   id = "filter_twig",
 *   title = @Translation("Twig filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = {},
 *   weight = -22
 * )
 */
class FilterTwig extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    /* @var $twig_service \Drupal\Core\Template\TwigEnvironment */
    $twig_service = \Drupal::service('twig');

    return new FilterProcessResult((string) $twig_service->renderInline($text, ['langcode' => $langcode]));
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE, $context = []) {
    return $this->t('Use the Twig templating engine to render the text. See <a href=":url">@url</a> for more information.', [
      ':url' => 'http://twig.sensiolabs.org/documentation',
      '@url' => 'http://twig.sensiolabs.org/documentation',
    ]);
  }

}
