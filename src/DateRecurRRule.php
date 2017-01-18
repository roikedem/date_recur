<?php

/**
 * @file
 * Contains \Drupal\date_recur\DateRecurRRule
 */

namespace Drupal\date_recur;

/**
 * This class handles all RRule related calculations. It calls out to
 * DateRecurDefaultRRule for actual calculations, so that this can possibly
 * be made pluggable for other implementations.
 *
 * @todo:
 * - Load occurrences from database cache instead of recalculating for each
 *   view (or at least benchmark this for different rules).
 * - Properly document and add an interface.
 * - Properly set cache tags, either here or in the formatter.
 */

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Component\Datetime\DateTimePlus;
use RRule\RRule;

class DateRecurRRule {

  const RFC_DATE_FORMAT = 'Ymd\THis\Z';

  /**
   * @var \RRule\RRule
   */
  protected $rrule;

  /**
   * @var \DateTime
   */
  protected $startDate;
  protected $startDateEnd;

  /**
   * @var string
   */
  protected $recurTime;

  /**
   * @var \DateInterval
   */
  protected $recurDiff;

  /**
   * @var string
   */
  protected $originalRuleString;

  /**
   * @var array
   */
  protected $parts;

  /**
   * @var array
   */
  protected $occurrences;

  protected $timezone;
  protected $timezoneOffset;

  /**
   * @param string $rrule The repeat rule.
   * @param \DateTime|DrupalDateTime $startDate The start date (DTSTART).
   * @param \DateTime|DrupalDateTime|NULL $startDateEnd Optionally, the initial event's end date.
   * @throws \InvalidArgumentException
   */
  public function __construct($rrule, $startDate, $startDateEnd = NULL, $timezone = NULL) {
    $this->originalRuleString = $rrule;
    $this->startDate = $startDate;
    $this->recurTime = $this->startDate->format('H:i');
    if (empty($startDateEnd)) {
      $startDateEnd = clone $startDate;
    }
    $this->startDateEnd = $startDateEnd;
    $this->recurDiff = $this->startDate->diff($startDateEnd);

    $this->parts = self::parseRrule($rrule, $startDate);
    $this->rrule = new DateRecurDefaultRRule($this->parts);

    if ($timezone) {
      $this->timezone = $timezone;
      $start = clone $this->startDate;
      $start->setTimezone(new \DateTimeZone($this->timezone));
      $this->timezoneOffset = $start->getOffset();
      $this->rrule->setTimezoneOffset($this->timezoneOffset);
    }
  }

  public function getParts() {
    return $this->parts;
  }

  /**
   * Parse an RFC rrule string and add a start date (DTSTART).
   *
   * @param string $rrule
   * @param \DateTime|DrupalDateTime $startDate
   * @return array An array of rrule parts.
   */
  public static function parseRrule($rrule, $startDate) {
    $rrule = sprintf(
      "DTSTART:%s\nRRULE:%s",
      $startDate->format(self::RFC_DATE_FORMAT),
      $rrule
    );

    $parts = RRule::parseRfcString($rrule);
    if (empty($parts['WKST'])) {
      $parts['WKST'] = 'MO';
    }
    return $parts;
  }

  /**
  /**
   * Validate that an rrule string is parseable.
   *
   * @param string $rrule
   * @param \DateTime|DrupalDateTime $startDate
   * @throws \InvalidArgumentException
   */
  public static function validateRule($rrule, $startDate) {
    self::parseRrule($rrule, $startDate);
  }


  /**
   * Get occurrences, optionally limited by a start date, end date and count.
   *
   * @param null|\DateTime $start
   * @param null|\DateTime $end
   * @param null|int $num
   * @return array
   */
  public function getOccurrences($start = NULL, $end = NULL, $num = NULL) {
    return $this->createOccurrences($start, $end, $num);
  }

  /**
   * Get occurrences between a start and an end date.
   *
   * @param \DateTime|DrupalDateTime $start
   * @param \DateTime|DrupalDateTime $end
   * @return array
   */
  public function getOccurrencesBetween($start, $end) {
    return $this->getOccurrences($start, $end);
  }

  /**
   * Get the next occurrences after a start date.
   *
   * @param \DateTime|DrupalDateTime $start
   * @param int $num
   * @return array
   */
  public function getNextOccurrences($start, $num) {
    return $this->getOccurrences($start, NULL, $num);
  }

  /**
   * Check if the rule is infinite.
   *
   * @return bool
   */
  public function isInfinite() {
    return $this->rrule->isInfinite();
  }

  /**
   * Get the occurrences for storage in the cache table (for views).
   *
   * @see DateRecurFieldItemList::postSave()
   *
   * @param \DateTime $until For infinite dates create until that date.
   * @param string $storageFormat The desired date format.
   * @return array
   */
  public function getOccurrencesForCacheStorage(\DateTime $until, $storageFormat) {
    $occurrences = [];
    if (!$this->rrule->isInfinite()) {
      $occurrences += $this->createOccurrences(NULL, NULL, NULL, FALSE);
    }
    else {
      $occurrences += $this->createOccurrences(NULL, $until, NULL, FALSE);
    }

    foreach ($occurrences as &$row) {
      foreach ($row as $key => $date) {
        if (!empty($date)) {
          $row[$key] = self::massageDateValueForStorage($date, $storageFormat);
        }
      }
    }

    return $occurrences;
  }

  /**
   * @param null|\DateTime $start
   * @param null|\DateTime $end
   * @param null|int $num
   * @return array
   */
  protected function createOccurrences($start = NULL, $end = NULL, $num = NULL, $display = TRUE) {
    if ($this->rrule->isInfinite() && $end === NULL && $num === NULL) {
      throw new \LogicException('Cannot get all occurrences of an infinite recurrence rule.');
    }

    $occurrences = [];
    foreach ($this->rrule as $occurrence) {
      if ($start !== NULL && $occurrence < $start) {
        continue;
      }
      if ($end !== NULL && $occurrence > $end) {
        break;
      }
      if ($num !== NULL && count($occurrences)  >= $num) {
        break;
      }
      $occurrences[] = $this->massageOccurrence($occurrence, $display);
    }

    return $occurrences;
  }

  /**
   * @param \DateTime $occurrence
   * @param bool|TRUE $display
   * @return array[[value => DrupalDateTime, end_value => DrupalDateTime], ...]
   */
  protected function massageOccurrence(\DateTime $occurrence, $display = TRUE) {
    /** @var DateTimePlus $date */
    $date = DrupalDateTime::createFromFormat('Ymd H:i', $occurrence->format('Ymd') . ' ' . $this->recurTime, $this->startDate->getTimezone());
    if ($display) {
      $date = $this->adjustDateForDisplay($date);
    }

    $date_end = clone $date;
    if (!empty($this->recurDiff)) {
      $date_end = $date_end->add($this->recurDiff);
    }
    return ['value' => $date, 'end_value' => $date_end];
  }

  /**
   * @param $date
   * @return \DateTime $date
   */
  public function adjustDateForDisplay($date) {
    if (empty($this->timezone)) {
      return $date;
    }
    return $date->setTimezone(new \DateTimeZone($this->timezone));
  }

  public static function massageDateValueForStorage($date, $format) {
    if ($format == DATETIME_DATE_STORAGE_FORMAT) {
      datetime_date_default_time($date);
    }
    $date->setTimezone(new \DateTimeZone(DATETIME_STORAGE_TIMEZONE));
    // Adjust the date for storage.
    return $date->format($format);
  }

  /**
   * Get a human-readable representation of the repeat rule.
   *
   * @todo: Make this translatable.
   *
   * @return string
   */
  public function humanReadable() {
    return $this->rrule->humanReadable();
  }
}
