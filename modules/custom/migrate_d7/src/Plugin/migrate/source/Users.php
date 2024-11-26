<?php

declare(strict_types=1);

namespace Drupal\migrate_d7\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * The 'users' source plugin.
 *
 * @MigrateSource(
 *   id = "users",
 *   source_module = "migrate_d7",
 * )
 */
final class Users extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    $query = $this->select('users', 'u')
      ->fields('u', ['uid', 'name', 'pass','mail','created','access','login','status']);
    $query->condition('u.uid',0,'>');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [
      'id' => $this->t('The record ID.'),
      'name' => $this->t('The record name.'),
      'pass' => $this->t('The record pass.'),
      'mail' => $this->t('The record mail.'),
      'created' => $this->t('The record created.'),
      'access' => $this->t('The record access.'),
      'login' => $this->t('The record login.'),
      'status' => $this->t('The record status'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    $ids['uid'] = [
      'type' => 'integer',
      'unsigned' => TRUE,
      'size' => 'big',
    ];
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row): bool {
    // @DCG
    // Modify the row here if needed.
    // Example:
    // @code
    //   $name = $row->getSourceProperty('name');
    //   $row->setSourceProperty('name', Html::escape('$name'));
    // @endcode
    return parent::prepareRow($row);
  }

}
