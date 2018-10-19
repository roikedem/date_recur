<?php

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\date_recur_entity_test\Entity\DrEntityTest;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests date_recur field.
 *
 * @group date_recur
 * @coversDefaultClass \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem
 */
class DateRecurFieldItemTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'date_recur_entity_test',
    'entity_test',
    'datetime',
    'datetime_range',
    'date_recur',
    'field',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('dr_entity_test');
  }

  /**
   * Tests infinite flag is set if an infinite RRULE is set.
   */
  public function testInfinite() {
    $entity = DrEntityTest::create();
    $entity->dr = [
      [
        'value' => '2008-06-16T00:00:00',
        'end_value' => '2008-06-16T06:00:00',
        'rrule' => 'FREQ=DAILY',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity->save();
    $this->assertTrue($entity->dr[0]->infinite === TRUE);
  }

  /**
   * Tests infinite flag is set if an non-infinite RRULE is set.
   */
  public function testNonInfinite() {
    $entity = DrEntityTest::create();
    $entity->dr = [
      [
        'value' => '2008-06-16T00:00:00',
        'end_value' => '2008-06-16T06:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=100',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity->save();
    $this->assertTrue($entity->dr[0]->infinite === FALSE);
  }

}
