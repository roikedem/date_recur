<?php

namespace Drupal\date_recur;


use Drupal\Core\Datetime\DrupalDateTime;
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

  /**
   * @param string $rrule The repeat rule.
   * @param \DateTime|DrupalDateTime $startDate The start date (DTSTART).
   * @param \DateTime|DrupalDateTime|NULL $startDateEnd Optionally, the initial event's end date.
   * @throws \InvalidArgumentException
   */
  public function __construct($rrule, $startDate, $startDateEnd = NULL) {
    $this->originalRuleString = $rrule;
    $this->startDate = $startDate;
    $this->recurTime = $this->startDate->format('H:i');
    if (empty($startDateEnd)) {
      $startDateEnd = clone $startDate;
    }
    $this->startDateEnd = $startDateEnd;
    $this->recurDiff = $this->startDate->diff($startDateEnd);

    $this->parts = self::parseRrule($rrule, $startDate);
    $this->rrule = new RRule($this->parts);
  }

  public function getParts() {
    return $this->parts;
  }

  public function getEndDate() {
    if (!empty($this->parts['UNTIL'])) {
      if ($this->parts['UNTIL'] instanceof \DateTime) {
        return DrupalDateTime::createFromDateTime($this->parts['UNTIL']);
      }
      else if (is_string($this->parts['UNTIL'])) {
        return DrupalDateTime::createFromTimestamp(strtotime($this->parts['UNTIL']));
      }
    }
    return FALSE;
  }


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

  public static function validateRule($rrule, $startDate) {
    self::parseRrule($rrule, $startDate);
  }


  /**
   * Get occurrences.
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
    return $this->createOccurrences($start, $end);
  }

  /**
   * Get the next occurrences after a start date.
   * @param \DateTime|DrupalDateTime $start
   * @param int $num
   * @return array
   */
  public function getNextOccurrences($start, $num) {
    return $this->createOccurrences($start, NULL, $num);
  }

  /**
   * Check if the rule is inifinite.
   *
   * @return bool
   */
  public function isInfinite() {
    return $this->rrule->isInfinite();
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
    $occurrences[] = ['value' => $this->startDate, 'end_value' => $this->startDateEnd];
    if (!$this->rrule->isInfinite()) {
      $occurrences += $this->createOccurrences();
    }
    else {
      $occurrences += $this->createOccurrences(NULL, $until);
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
  protected function createOccurrences($start = NULL, $end = NULL, $num = NULL) {
    if ($this->rrule->isInfinite() && $end === NULL && $num === NULL) {
      throw new \LogicException('Cannot get all occurrences of an infinite recurrence rule.');
    }
    // If a start date was supplied, create a new rule object with the supplied
    // start date.
    // @todo: Not sure if this is more performant than iterating through all occurrences.
    if (!empty($start)) {
      $rrule = sprintf(
        "DTSTART:%s\nRRULE:%s",
        $start->format(self::RFC_DATE_FORMAT),
        $this->originalRuleString
      );
      $parts = RRule::parseRfcString($rrule);
      $rrule = new RRule ($parts);
    }
    else {
      $rrule = $this->rrule;
    }

    $i = 0;
    $occurrences = [];
    foreach ($rrule as $occurrence) {
      $i++;
      if ( $end !== NULL && $occurrence > $end ) {
        break;
      }
      if ($num !== NULL && $i > $num) {
        break;
      }
      $occurrences[] = $this->massageOccurrence($occurrence);
    }

    return $occurrences;
  }

  protected function massageOccurrence(\DateTime $occurrence) {
    $date = \DateTime::createFromFormat('Ymd H:i', $occurrence->format('Ymd') . ' ' . $this->recurTime, $this->startDate->getTimezone());
    $date_end = clone $date;
    if (!empty($this->recurDiff)) {
      $date_end = $date_end->add($this->recurDiff);
    }
    return ['value' => $date, 'end_value' => $date_end];
  }

  public static function massageDateValueForStorage($date, $format) {
    if ($format == DATETIME_DATE_STORAGE_FORMAT) {
      datetime_date_default_time($date);
    }
    // Adjust the date for storage.
    return $date->format($format);
  }
}
