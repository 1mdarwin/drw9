<?php

namespace Drupal\libraries\StreamWrapper;

use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a stream wrapper for PHP file libraries.
 *
 * Can be used with the 'php-file://' scheme, for example
 * 'php-file-library://guzzle/src/functions_include.php'.
 */
class PhpFileLibrariesStream extends LocalStream {
  use StringTranslationTrait;

  use LocalHiddenStreamTrait;
  use PrivateStreamTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('PHP library files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Provides access to PHP library files.');
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    // @todo Provide support for site-specific directories, etc.
    return 'sites/all/libraries';
  }

}
