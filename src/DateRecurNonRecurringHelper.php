<?php

namespace Drupal\date_recur;

/**
 * Dummy helper for handling non-recurring values.
 */
class DateRecurNonRecurringHelper implements DateRecurHelperInterface {

  /**
   * The occurrences.
   *
   * @var \Drupal\date_recur\DateRange[]
   */
  protected $occurrences = [];

  /**
   * Constructor for DateRecurNonRecurringHelper.
   *
   * @param \DateTimeInterface $dtStart
   *   The initial occurrence start date.
   * @param \DateTimeInterface|null $dtStartEnd
   *   The initial occurrence end date, or NULL to use start date.
   */
  public function __construct(\DateTimeInterface $dtStart, \DateTimeInterface $dtStartEnd = NULL) {
    $dtStartEnd = isset($dtStartEnd) ? $dtStartEnd : clone $dtStart;
    $this->occurrences = [new DateRange($dtStart, $dtStartEnd)];
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance($string, \DateTimeInterface $dtStart, \DateTimeInterface $dtStartEnd = NULL) {
    return new static($dtStart, $dtStartEnd);
  }

  /**
   * {@inheritdoc}
   */
  public function getRules() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isInfinite() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function generateOccurrences(\DateTimeInterface $rangeStart = NULL, \DateTimeInterface $rangeEnd = NULL) {
    foreach ($this->occurrences as $occurrence) {
      yield $occurrence;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOccurrences(\DateTimeInterface $rangeStart = NULL, \DateTimeInterface $rangeEnd = NULL, $limit = NULL) {
    return $this->occurrences;
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    return current($this->occurrences);
  }

  /**
   * {@inheritdoc}
   */
  public function next() {
    next($this->occurrences);
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return key($this->occurrences);
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    reset($this->occurrences);
  }

}
