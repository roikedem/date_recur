<?php

namespace Drupal\Tests\date_recur\Unit;

use Drupal\date_recur\Rl\RlHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests Rlanvin implementation of helper.
 *
 * @coversDefaultClass \Drupal\date_recur\Rl\RlHelper
 * @group date_recur
 *
 * @ingroup RLanvinPhpRrule
 */
class DateRecurRlHelperUnitTest extends UnitTestCase {

  /**
   * Tests frequency method of rules returned by helper.
   */
  public function testFrequency() {
    $dtStart = new \DateTime('9am 16 June 2014');
    $rrule = 'FREQ=DAILY;COUNT=10';
    $instance = $this->createHelper($rrule, $dtStart);

    $rules = $instance->getRules();
    $this->assertCount(1, $rules);
    $rule = $rules[0];
    $this->assertEquals('DAILY', $rule->getFrequency());
  }

  /**
   * Tests parts that were not passed originally, are not returned.
   */
  public function testRedundantPartsOmitted() {
    $dtStart = new \DateTime('9am 16 June 2014');
    $rrule = 'FREQ=DAILY;COUNT=10';
    $instance = $this->createHelper($rrule, $dtStart);

    $rules = $instance->getRules();
    $this->assertCount(1, $rules);
    $rule = $rules[0];

    $parts = $rule->getParts();
    // Rlanvin/rrule will return parts: 'DTSTART', 'FREQ', 'COUNT', 'INTERVAL',
    // 'WKST'. However we just need to test completely unrelated parts such as
    // BYMONTHDAY etc arn't returned here.
    $this->assertArrayHasKey('DTSTART', $parts);
    $this->assertArrayHasKey('COUNT', $parts);
    $this->assertArrayNotHasKey('BYMONTHDAY', $parts);
  }

  /**
   * Creates a new helper.
   *
   * Uses same arguments as
   * \Drupal\date_recur\DateRecurHelperInterface::createInstance.
   *
   * @param string $string
   *   The repeat rule.
   * @param \DateTimeInterface $dtStart
   *   The initial occurrence start date.
   * @param \DateTimeInterface|null $dtStartEnd
   *   The initial occurrence end date, or NULL to use start date.
   *
   * @return \Drupal\date_recur\DateRecurHelperInterface
   *   A new date recur helper instance.
   *
   * @see \Drupal\date_recur\DateRecurHelperInterface::createInstance
   */
  protected function createHelper($string, \DateTimeInterface $dtStart, \DateTimeInterface $dtStartEnd = NULL) {
    // @todo convert to splat for PHP5.6/7 version upgrade.
    return RlHelper::createInstance($string, $dtStart);
  }

}
