<?php

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\date_recur_entity_test\Entity\DrEntityTest;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests basic functionality of date_recur fields.
 *
 * @group date_recur
 */
class DateRecurTest extends KernelTestBase {

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
   * Tests adding a field, setting values, reading occurrences.
   */
  public function testGetOccurrences() {
    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'abc',
      'type' => 'date_recur',
      'settings' => [
        'datetime_type' => DateRecurItem::DATETIME_TYPE_DATETIME,
        'occurrence_handler_plugin' => 'date_recur_occurrence_handler',
      ],
    ]);
    $field_storage->save();

    $field = [
      'field_name' => 'abc',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ];
    FieldConfig::create($field)->save();

    $entity = EntityTest::create();
    $entity->abc = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
      'infinite' => '1',
      'timezone' => 'Australia/Sydney',
    ];

    // No need to save the entity.
    $this->assertTrue($entity->isNew());
    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $item */
    $item = $entity->abc[0];
    $occurrences = $item->getOccurrenceHandler()
      ->getHelper()
      ->getOccurrences(NULL, NULL, 2);
    $this->assertEquals('Mon, 16 Jun 2014 09:00:00 +1000', $occurrences[0]->getStart()->format('r'));
    $this->assertEquals('Mon, 16 Jun 2014 17:00:00 +1000', $occurrences[0]->getEnd()->format('r'));
    $this->assertEquals('Tue, 17 Jun 2014 09:00:00 +1000', $occurrences[1]->getStart()->format('r'));
    $this->assertEquals('Tue, 17 Jun 2014 17:00:00 +1000', $occurrences[1]->getEnd()->format('r'));
  }

  /**
   * Tests accessing occurrences with fields with no end date or rule.
   */
  public function testHelperNonRecurringWithNoEnd() {
    $entity = DrEntityTest::create();
    $entity->dr = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '',
      'rrule' => '',
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ];

    // Ensure a non repeating field value generates a single occurrence.
    /** @var \Drupal\date_recur\DateRange[] $occurrences */
    $occurrences = iterator_to_array($entity->dr->occurrences);
    $this->assertCount(1, $occurrences);

    $tz = new \DateTimeZone('Australia/Sydney');
    $startAssert = new \DateTime('9am 16 June 2014', $tz);
    $this->assertEquals($startAssert, $occurrences[0]->getStart());
    $this->assertEquals($startAssert, $occurrences[0]->getEnd());
  }

  /**
   * Tests accessing occurrences with fields with end date or rule.
   */
  public function testHelperNonRecurringWithEnd() {
    $entity = DrEntityTest::create();
    $entity->dr = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => '',
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ];

    // Ensure a non repeating field value generates a single occurrence.
    /** @var \Drupal\date_recur\DateRange[] $occurrences */
    $occurrences = iterator_to_array($entity->dr->occurrences);
    $this->assertCount(1, $occurrences);

    $tz = new \DateTimeZone('Australia/Sydney');
    $startAssert = new \DateTime('9am 16 June 2014', $tz);
    $this->assertEquals($startAssert, $occurrences[0]->getStart());
    $endAssert = new \DateTime('5pm 16 June 2014', $tz);
    $this->assertEquals($endAssert, $occurrences[0]->getEnd());
  }

}
