<?php

declare(strict_types=1);

namespace Drupal\Tests\entityqueue_smartqueue\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the config schema of the smartqueue Views argument.
 *
 * @group entityqueue
 */
#[RunTestsInSeparateProcesses]
class SmartQueueViewsArgumentTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views', 'entityqueue', 'entityqueue_smartqueue'];

  /**
   * A view using the smartqueue argument complies with config schema on save.
   */
  public function testViewsArgumentConfigSchema(): void {
    // Saving the view validates it against config schema because kernel tests
    // run with strict schema checking, so a missing schema for the argument's
    // 'smartqueue' option fails here.
    View::create([
      'id' => 'sq_arg',
      'label' => 'SQ argument',
      'base_table' => 'entity_subqueue',
      'base_field' => 'name',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'id' => 'default',
          'display_title' => 'Default',
          'position' => 0,
          'display_options' => [
            'arguments' => [
              'name' => [
                'id' => 'name',
                'table' => 'entity_subqueue',
                'field' => 'name',
                'plugin_id' => 'entityqueue_smartqueue_name',
                'relationship' => 'none',
                'smartqueue' => 'my_queue',
              ],
            ],
          ],
        ],
      ],
    ])->save();

    $this->assertInstanceOf(View::class, View::load('sq_arg'));
  }

}
