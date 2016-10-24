<?php

namespace Drupal\date_recur\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\date_recur\DateRecurRRule;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeDefaultWidget;
use RRule\RRule;

/**
 * Plugin implementation of the 'date_recur_default_widget' widget.
 *
 * @FieldWidget(
 *   id = "date_recur_default_widget",
 *   label = @Translation("Date recur default widget"),
 *   field_types = {
 *     "date_recur"
 *   }
 * )
 */
class DateRecurDefaultWidget extends DateRangeDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // date_recur omits timezone handling at the moment.
    foreach (array('value', 'end_value') as $key) {
      $element[$key]['#date_timezone'] = DATETIME_STORAGE_TIMEZONE;
    }

    $element['#element_validate'][] = [$this, 'validateRrule'];

    $element['end_value']['#required'] = FALSE;

    $element['rrule'] = array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->rrule) ? $items[$delta]->rrule : NULL,
      '#title' => $this->t('Repeat rule (RRULE)'),
      '#value_callback' => [$this, 'rruleValueCallback']
    );
    return $element;
  }

  /**
   * Creates a date object for use as a default value.
   *
   * This overrides DateRangeWidgetBase to remove timezone handling.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   * @param string $timezone
   * @return \Drupal\Core\Datetime\DrupalDateTime
   */
  protected function createDefaultValue($date, $timezone) {
    if ($this->getFieldSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE) {
      datetime_date_default_time($date);
    }
    return $date;
  }

  /**
   * #element_validate callback to validate the repeat rule.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function validateRrule(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (!empty($element['rrule']['#value']) && $element['value']['#value']['object'] instanceof DrupalDateTime) {
      try {
        DateRecurRRule::validateRule($element['rrule']['#value'], $element['value']['#value']['object']);
      }
      catch (\InvalidArgumentException $e) {
        $form_state->setError($element, $this->t('Invalid repeat rule: %message', ['%message' => $e->getMessage()]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      $item['infinite'] = 0;
      if (empty($item['rrule'])) {
        $item['rrule'] = '';
      }
      else {
        $rule = new DateRecurRRule($item['rrule'], $item['value']);
        if ($rule->isInfinite()) {
          $item['infinite'] = 1;
        }
      }
    }
    return parent::massageFormValues($values, $form, $form_state);
  }
}
