/**
 * @file
 * Date Recur Rrule Editor
 */

(function ($, Drupal) {

    'use strict';

    Drupal.behaviors.dateRecurRruleEditor = {
        attach: function (context, settings) {
            $('input[data-date-recur-rrule]', context).each(function(idx, el) {
                var $rrule = $(el);
                var id = $rrule.attr('data-date-recur-rrule');
                var $startDate = $('input[type=date][data-date-recur-start=' + id + ']', context);
                var rrule = $rrule.val();

                var $checkbox = $('<label><input type="checkbox">' + Drupal.t('Repeat?') + '</label>');
                $checkbox.insertAfter($rrule);
                $checkbox.find('input').prop('checked', rrule);
                var $widget = $('<div class="date-recur-widget"></div>').insertAfter($checkbox);

                function initWidget($widget) {
                    var opts = {};
                    if (rrule) {
                        opts.rrule = rrule
                    }
                    opts.dtstart = new Date($startDate.val());
                    $widget.recurringinput(opts);

                    $widget.on('rrule-update',function() {
                        rrule = $('.rrule-output', $widget).html();
                        $rrule.val(rrule);
                    });
                }

                $checkbox.find('input').on('change', function() {
                    if ($(this).is(':checked')) {
                        if (!$widget.data('date-recur-init')) {
                            initWidget($widget);
                            $widget.data('date-recur-init', true);
                        }
                        $widget.show();
                    }
                    else {
                        $widget.hide();
                    }
                });

                $checkbox.find('input').trigger('change');
            });
        }
    };
}(jQuery, Drupal));
