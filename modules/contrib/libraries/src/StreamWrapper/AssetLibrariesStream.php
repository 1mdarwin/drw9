<?php

namespace Drupal\libraries\StreamWrapper;

use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a stream wrapper for asset libraries.
 *
 * Can be used with the 'asset://' scheme, for example
 * 'asset://jquery/jquery.js'.
 */
class AssetLibrariesStream extends LocalStream {

  use StringTranslationTrait;
  use LocalHiddenStreamTrait;
  use PrivateStreamTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Assets');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Provides access to asset library files.');
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    // @todo Provide support for site-specific directories, etc.
    return 'sites/all/assets/vendor';
  }

}
