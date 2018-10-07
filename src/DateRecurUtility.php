<?php

namespace Drupal\date_recur;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provide standalone utilities.
 */
class DateRecurUtility {

  /**
   * Downgrades a DrupalDateTime object to PHP date time.
   *
   * Useful in situations where object comparison is used.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $drupalDateTime
   *   A Drupal datetime object.
   *
   * @see https://www.drupal.org/node/2936388
   *
   * @return \Datetime
   *   A PHP datetime object.
   *
   * @deprecated remove when at least Drupal 8.6 is minimum supported version.
   *   Use \Drupal\Core\Datetime\DrupalDateTime::getPhpDateTime() instead.
   *
   * @internal
   */
  public static function toPhpDateTime(DrupalDateTime $drupalDateTime) {
    $date = new \Datetime($drupalDateTime->format('r'));
    // Using 'r' format overrides whichever timezone is passed as 2nd arg, set
    // it here instead.
    $date->setTimezone($drupalDateTime->getTimezone());
    return $date;
  }

  /**
   * Get the smallest date given a granularity and input.
   *
   * @param string $granularity
   *   The granularity of the input.
   * @param string $value
   *   User date input.
   * @param \DateTimeZone $timezone
   *   The timezone of the input.
   *
   * @return \DateTime
   *   A date time with the smallest value given granularity and input.
   */
  public static function createSmallestDateFromInput($granularity, $value, \DateTimeZone $timezone) {
    return static::createDateFromInput($granularity, $value, $timezone, 'start');
  }

  /**
   * Get the largest date given a granularity and input.
   *
   * @param string $granularity
   *   The granularity of the input.
   * @param string $value
   *   User date input.
   * @param \DateTimeZone $timezone
   *   The timezone of the input.
   *
   * @return \DateTime
   *   A date time with the smallest value given granularity and input.
   */
  public static function createLargestDateFromInput($granularity, $value, \DateTimeZone $timezone) {
    return static::createDateFromInput($granularity, $value, $timezone, 'end');
  }

  /**
   * Get the smallest or largest date given a granularity and input.
   *
   * @param string $granularity
   *   The granularity of the input. E.g 'year', 'month', etc.
   * @param string $value
   *   User date input.
   * @param \DateTimeZone $timezone
   *   The timezone of the input.
   * @param string $end
   *   Either 'start' or 'end' to get a date at the beginning or end of a
   *   granularity period.
   *
   * @return \DateTime
   *   A date time with the smallest value given granularity and input.
   *
   * @internal
   */
  protected static function createDateFromInput($granularity, $value, \DateTimeZone $timezone, $end) {
    assert(in_array($end, ['start', 'end']));
    $start = $end === 'start';

    $granularityFormats = [
      'year' => 'Y',
      'month' => 'Y-m',
      'day' => 'Y-m-d',
      'second' => 'Y-m-d\TH:i:s',
    ];
    $format = $granularityFormats[$granularity];

    // PHP fills missing granularity parts with current datetime. Use this
    // object to reconstruct the date at the beginning of the granularity
    // period.
    $knownDate = \DateTime::createFromFormat($format, $value, $timezone);

    $granularityComparison = [
      1 => 'year',
      2 => 'month',
      3 => 'day',
      6 => 'second',
    ];
    $granularityInt = array_search($granularity, $granularityComparison);

    $dateParts = [
      'year' => (int) $knownDate->format('Y'),
      'month' => $granularityInt >= 2 ? (int) $knownDate->format('m') : ($start ? 1 : 12),
      'day' => $granularityInt >= 3 ? (int) $knownDate->format('d') : 1,
      'hour' => $granularityInt >= 4 ? (int) $knownDate->format('H') : ($start ? 0 : 23),
      'minute' => $granularityInt >= 5 ? (int) $knownDate->format('i') : ($start ? 0 : 59),
      'second' => $granularityInt >= 6 ? (int) $knownDate->format('s') : ($start ? 0 : 59),
    ];

    $date = DrupalDateTime::createFromArray($dateParts, $knownDate->getTimezone());

    // Getting the last day of a month is a little more complex. Use the created
    // date to get number of days in the month.
    if (!$start && $granularityInt < 3) {
      $dateParts['day'] = $date->format('t');
      $date = DrupalDateTime::createFromArray($dateParts, $date->getTimezone());
    }

    return static::toPhpDateTime($date);
  }

}
