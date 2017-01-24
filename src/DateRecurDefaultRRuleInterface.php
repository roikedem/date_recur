<?php

namespace Drupal\date_recur;


use RRule\RRuleInterface;

interface DateRecurDefaultRRuleInterface extends RRuleInterface {
  public function humanReadable();
  public function setTimezoneOffset($offset);
  public function getStartDate();
}
