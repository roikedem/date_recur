/*
 * A JQuery UI Widget to create a RRule compatible inputs for use inside a form
 * Requires: rrule.js (http://jkbr.github.io/rrule/)
 *          underscore.js
 * Original author: Josh Levinger, 2013
 * Original source: https://github.com/rootio/rootio_web/blob/master/rootio/static/js/plugins/rrule.recurringinput.js
 * Relicensed by Josh Levinger to GPL v2+
 * Modified and adapted to Drupal 8 by Frando, 2016.
 */


(function ($, Drupal, RRule) {

    // add helpful constants to RRule
    RRule.FREQUENCY_NAMES = ['year','month','week','day','hour','minute','second'];
    RRule.FREQUENCY_NAMES_PLURAL = ['years','months','weeks','days','hours','minutes','seconds'];
    RRule.DAYCODES = ['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'];
    RRule.DAYNAMES = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    RRule.MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    RRule.SETPOS = {
        '1': 'first',
        '2': 'second',
        '3': 'third',
        '4': 'forth',
        '-1': 'last',
        '1,2': '1. and 2.',
        '1,3': '1. and 3.',
        '1,2,3': '1., 2. and 3.',
        '1,3,-1': '1., 3. and last',
        '1,2,3,4': '1.,2.,3. and 4.',
        '2,4': '2. and 4.',
        '2,-1': '2. and last'
    };

    // @todo: localize. @see: locale.datepicker.js.

    // note, month num for these values should be one-based, not zero-based
    $.widget("rrule.recurringinput", {
        // default options
        options: {
            rrule: '',
            dtstart: null
        },

        _create: function () {
            //set up inputs
            // TODO: convert to underscore template
            var tmpl = '';

            //frequency
            tmpl += '<div class="container-inline">';
            tmpl += '<label class="controls">Repeat ';
            tmpl += '<select name="freq">';
            _.each(RRule.FREQUENCIES, function (element, index) {
                var f = RRule[element];
                tmpl += '<option value=' + f + '>' + element.toLowerCase() + '</option>';
            });
            tmpl += '</select>';
            tmpl += '</label>';


            tmpl += '<label class="controls"> every ';
            tmpl += '<input type="number" value="1" min="1" max="100" name="interval"/>';
            tmpl += '&nbsp;<span id="frequency_name"></span>';
            tmpl += '</label>';
            tmpl += '</div>';

            // repeat options, frequency specific
            // data-freq should be lowercase value from FREQUENCY_NAMES

            //bymonth: weekdays
            tmpl += '<div class="repeat-options controls container-inline" data-freq="monthly">';
            tmpl += '<label for="byweekday-pos">On the</label>';
            tmpl += '<select name="byweekday-pos">';
            _.each(RRule.SETPOS, function (element, index) {
                tmpl += '<option value=' + index + '>' + element + '</option>';
            });
            tmpl += '</select>';
            _.each(RRule.DAYCODES, function (element, index) {
                var d = RRule[element];
                tmpl += '<label class="inline">';
                tmpl += '<input type="checkbox" name="byweekday" value="' + d.weekday + '" />';
                tmpl += RRule.DAYNAMES[index] + '</label>';
            });
            tmpl += '</div>';

            //bymonth: months
            tmpl += '<div class="repeat-options controls container-inline" data-freq="monthly"><label>Only in ';
            _.each(RRule.MONTHS, function (element, index) {
                tmpl += '<label class="inline">';
                tmpl += '<input type="checkbox" name="bymonth" value="' + (index + 1) + '" />';
                tmpl += element + '</label>';
            });
            tmpl += '</div>';

            //byweekday
            tmpl += '<div class="repeat-options controls container-inline" data-freq="weekly">';
            tmpl += '<label for="byweekday">On </label>';
            _.each(RRule.DAYCODES, function (element, index) {
                var d = RRule[element];
                tmpl += '<label class="inline">';
                tmpl += '<input type="checkbox" name="byweekday" value="' + d.weekday + '" />';
                tmpl += RRule.DAYNAMES[index] + '</label>';
            });
            tmpl += '</div>';

            //byhour
            tmpl += '<label class="repeat-options" data-freq="hourly">Only at ';
            tmpl += '<input name="byhour" /> <span>o\'clock</span></label>';

            //byminute
            tmpl += '<label class="repeat-options" data-freq="minutely">Only at ';
            tmpl += '<input name="byminute" />  <span>minutes<span></label>';

            //bysecond
            tmpl += '<label class="repeat-options" data-freq="secondly">Only at ';
            tmpl += '<input name="bysecond" /> <span>seconds</span></label>';

            // end repeat options


            // end on
            tmpl += '<div class="end-options controls">';
            tmpl += '<label for="end">End </label>';

            tmpl += '<label class="inline">';
            tmpl += '<input type="radio" name="end" value="0" checked="checked"/> Never</label>';
            tmpl += '<label class="inline">';
            tmpl += '<input type="radio" name="end" value="1" /> After <input type="number" max="1000" min="1" value="" name="count"/> occurences';
            tmpl += '</label>';
            tmpl += '<label class="inline">';
            tmpl += '<input type="radio" name="end" value="2"> On date <input type="date" name="until"/>';
            tmpl += '</label>';

            tmpl += '</div>';

            // summary
            tmpl += '<label for="output">Summary ';
            tmpl += '<em class="text-output"></em></label>'; // human readable
            tmpl += '<br><label>RRule <code class="rrule-output"></code></label>'; // ugly rrule
            //TODO: show next few instances to help user debug

            //render template
            this.element.append(tmpl);

            //save input references to widget for later use
            this.frequency_select = this.element.find('select[name="freq"]');
            this.interval_input = this.element.find('input[name="interval"]');
            this.end_input = this.element.find('input[type="radio"][name="end"]');

            //bind event handlers
            this._on(this.element.find('select, input'), {
                change: this._refresh
            });

            //set sensible defaults
            this.frequency_select.val(2);
            this.interval_input.val(1);

            // setup default.
            var rruleOpts = {};
            if (this.options.dtstart) {
                rruleOpts.dtstart = this.options.dtstart;
            }
            if (this.options.rrule.length) {
                rruleOpts = RRule.parseString(this.options.rrule)
            }
            else {
                rruleOpts.freq = RRule.WEEKLY;
                if (this.options.dtstart) {
                    rruleOpts.byweekday = [6, 0, 1, 2, 3, 4, 5][this.options.dtstart.getDay()];
                }
            }
            try {
                var rrule = new RRule(rruleOpts);
                this._applyRRule(rrule);
            } catch (_error) {
                e = _error;
                $(".text-output", this.element).append($('<pre class="error"/>').text('=> ' + String(e || null)));
                return;
            }

            //refresh
            this._refresh();
        },

        _applyRRule: function(rrule) {
            var opts = rrule.options;
            var freq = RRule.FREQUENCY_NAMES[opts.freq].toLowerCase();

            // split byweekday.
            var byweekdayPos = [];
            if (opts.byweekday == null) {
                opts.byweekday = [];
            }
            if (opts.bynweekday instanceof Array) {
                _.each(opts.bynweekday, function(el) {
                    opts.byweekday.push(el[0]);
                    byweekdayPos.push(el[1]);
                });
            }
            byweekdayPos.sort(function(a, b) {
                if (a === -1) {
                    return 1;
                }
                return a - b;
            });
            opts['byweekday-pos'] = byweekdayPos.join(',');

            var $sel = $('[data-freq!=' + freq + ']', this.element);
            for (k in opts) {
                var v = opts[k];

                // Try to set the value.
                if($('input[name=' + k + '][type!=checkbox]', $sel).val(v).length) {
                }
                else if ($('select[name=' + k + ']', $sel).val(v).length) {
                }
                else if (v instanceof Array) {
                  $('input[name=' + k + '][type=checkbox]', $sel).val(v);
                }

            }
        },

        // called on create and when changing options
        _refresh: function () {
            //determine selected frequency
            var frequency = this.frequency_select.find("option:selected");
            // fill in frequency-name span
            this.element.find('#frequency_name').text(RRule.FREQUENCY_NAMES[frequency.val()]);
            // and pluralize
            if (this.interval_input.val() > 1) {
                this.element.find('#frequency_name').text(RRule.FREQUENCY_NAMES_PLURAL[frequency.val()]);
            }

            // display appropriate repeat options
            var repeatOptions = this.element.find('.repeat-options');
            repeatOptions.hide();

            if (frequency !== "") {
                //show options for the selected frequency
                repeatOptions.filter('[data-freq=' + frequency.text() + ']').show();

                //and clear descendent fields for the others
                var nonSelectedOptions = repeatOptions.filter('[data-freq!=' + frequency.text() + ']');
                nonSelectedOptions.find('input[type=checkbox]:checked').removeAttr('checked');
                nonSelectedOptions.find('input[type!=checkbox]').val('');
                nonSelectedOptions.find('select').val('');
            }

            //reset end
            switch (this.end_input.filter(':checked').val()) {
                case "0":
                    //never, clear count and until
                    this.end_input.siblings('input[name=count]').val('');
                    this.end_input.siblings('input[name=until]').val('');
                    break;
                case "1":
                    //after, clear until
                    this.end_input.siblings('input[name=until]').val('');
                    break;
                case "2":
                    //date, clear count
                    this.end_input.siblings('input[name=count]').val('');
                    break;
            }

            //determine rrule
            var rrule = this._getRRule();

            if (rrule) {
                $('.rrule-output', this.element).text(rrule.toString());
                $('.text-output', this.element).text(rrule.toText());
                this.element.trigger('rrule-update');
            }
        },

        _getFormValues: function ($form) {
            //modified from rrule/tests/demo/demo.js
            var paramObj;
            paramObj = {};

            $.each($form.serializeArray(), function (_, kv) {
                if (paramObj.hasOwnProperty(kv.name)) {
                    paramObj[kv.name] = $.makeArray(paramObj[kv.name]);
                    return paramObj[kv.name].push(kv.value);
                } else {
                    return paramObj[kv.name] = kv.value;
                }
            });
            return paramObj;
        },
        _getRRule: function () {
            //modified from rrule/tests/demo/demo.js
            //ignore 'end', because it's part of the ui but not the spec
            var values = this._getFormValues($(this.element).find('select, input[name!=end]'));
            options = {};

            if (_.has(values, 'byweekday-pos') && _.has(values, 'byweekday')) {
                var weekdayPos = values['byweekday-pos'].split(',');
            }
            delete values['byweekday-pos'];

            getDay = function (i) {
                var days = [RRule.MO, RRule.TU, RRule.WE, RRule.TH, RRule.FR, RRule.SA, RRule.SU];
                if (typeof weekdayPos !== 'undefined') {
                   return _.map(weekdayPos, function(pos) { return days[i].nth(pos) });
                }
                return [days[i]];
            };

            var k,v;
            for (k in values) {
                v = values[k];
                if (!v) {
                    continue;
                }
                if (_.contains(["dtstart", "until"], k)) {
                    d = new Date(Date.parse(v));
                    v = new Date(d.getTime() + (d.getTimezoneOffset() * 60 * 1000));
                } else if (k === 'byweekday') {
                    if (v instanceof Array) {
                        v = _.flatten(_.map(v, getDay), true);
                    } else {
                        v = getDay(v);
                    }
                } else if (/^by/.test(k)) {
                    if (!(v instanceof Array)) {
                        v = _.compact(v.split(/[,\s]+/));
                    }
                    v = _.map(v, function (n) {
                        return parseInt(n, 10);
                    });
                } else {
                    v = parseInt(v, 10);
                }
                if (k === 'wkst') {
                    v = getDay(v);
                }
                if (k === 'interval' && v === 1) {
                    continue;
                }
                options[k] = v;
            }

            try {
                rule = new RRule(options);
            } catch (_error) {
                e = _error;
                $(".text-output", this.element).append($('<pre class="error"/>').text('=> ' + String(e || null)));
                return;
            }
            return rule;
        },

        // _setOptions is called with a hash of all options that are changing
        // always refresh when changing options
        _setOptions: function () {
            this._superApply(arguments);
            this._refresh();
        },

        destroy: function () {
            // remove references
            this.frequency_select.remove();
            this.interval_input.remove();

            // unbind events

            // clear templated html
            this.element.html("");

            $.Widget.prototype.destroy.apply(this);
        }
    });
}(jQuery, Drupal, RRule));
