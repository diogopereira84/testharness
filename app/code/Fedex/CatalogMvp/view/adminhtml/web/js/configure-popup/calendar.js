define(['jquery', 'mage/calendar'], function ($, calendar) {
    'use strict';

    return {
        init: function () {
            this.initCalendar();
        },

        getTodayWithTime: function () {
            let now = new Date();
            let month = ("0" + (now.getMonth() + 1)).slice(-2);
            let day = ("0" + now.getDate()).slice(-2);
            let year = now.getFullYear();
            let hours = now.getHours();
            let minutes = ("0" + now.getMinutes()).slice(-2);
            let ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            return `${month}/${day}/${year} ${hours}:${minutes} ${ampm}`;
        },

        restrictMonthYear: function (inst) {
            let now = new Date();
            let currentMonth = now.getMonth();
            let currentYear = now.getFullYear();

            // Year dropdown
            let $yearSelect = inst.dpDiv.find('.ui-datepicker-year');
            $yearSelect.children().each(function (_, opt) {
                let val = parseInt($(opt).val(), 10);
                $(opt).toggle(val >= currentYear);
            });

            let selectedYear = typeof inst.selectedYear !== 'undefined' ? inst.selectedYear : parseInt($yearSelect.val(), 10);

            // Month dropdown
            let $monthSelect = inst.dpDiv.find('.ui-datepicker-month');
            $monthSelect.children().each(function (_, opt) {
                let monthIndex = parseInt($(opt).val(), 10);
                if (selectedYear === currentYear) {
                    $(opt).toggle(monthIndex >= currentMonth);
                } else {
                    $(opt).show();
                }
            });
        },

        initDatePicker: function (selector) {
            const self = this;
            $(selector).calendar({
                showsTime: true,
                dateFormat: "mm/dd/yy",
                timeFormat: "hh:mm TT",
                showOn: "both",
                minDate: 0,
                changeMonth: true,
                changeYear: true,
                yearRange: `${new Date().getFullYear()}:2050`,
                beforeShow: function (input, inst) {
                    setTimeout(() => self.restrictMonthYear(inst), 10);
                },
                onChangeMonthYear: function (year, month, inst) {
                    setTimeout(() => self.restrictMonthYear(inst), 10);
                }
            });
        },

        initCalendar: function () {
            $("#start-date").val(this.getTodayWithTime());
            this.initDatePicker("#start-date");
            this.initDatePicker("#end-date");

            const $endDateInput = $('#end-date');
            const $noEndDateCheckbox = $('#no-end-date');

            function toggleEndDate() {
                if ($noEndDateCheckbox.is(':checked')) {
                    $endDateInput.prop('disabled', true).val('').attr('placeholder', '--');
                } else {
                    $endDateInput.prop('disabled', false).attr('placeholder', 'MM/DD/YYYY 00:00 AM');
                }
            }

            toggleEndDate();
            $noEndDateCheckbox.on('change', toggleEndDate);
        }
    };
});