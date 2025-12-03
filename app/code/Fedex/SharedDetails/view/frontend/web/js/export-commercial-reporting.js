/**
* Copyright © Fedex, Inc. All rights reserved.
* See COPYING.txt for license details.
*/
define([
    'jquery',
    'uiComponent',
    'mage/url',
    'mage/storage',
    'Magento_Ui/js/lib/view/utils/dom-observer'
], function ($, Component, urlBuilder, storage, $dom) {
    'use strict';

    let isSharedOrderEnhancementToggleEnabled = false;
    let timeframeDownIconSrc = '';
    let maxEmailAddressAllowLimit = 0;

    return Component.extend({

        /** @inheritdoc */
        initialize: function () {
            this._super();
            let self = this;
            let crossIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"> <path fill-rule="evenodd" clip-rule="evenodd" d="M4 12C4 16.4183 7.58172 20 12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12ZM19 12C19 15.866 15.866 19 12 19C8.13401 19 5 15.866 5 12C5 8.13401 8.13401 5 12 5C15.866 5 19 8.13401 19 12ZM8.14645 8.85355C7.95118 8.65829 7.95118 8.34171 8.14645 8.14645C8.34171 7.95118 8.65829 7.95118 8.85355 8.14645L12 11.2929L15.1464 8.14645C15.3417 7.95118 15.6583 7.95118 15.8536 8.14645C16.0488 8.34171 16.0488 8.65829 15.8536 8.85355L12.7071 12L15.8536 15.1464C16.0488 15.3417 16.0488 15.6583 15.8536 15.8536C15.6583 16.0488 15.3417 16.0488 15.1464 15.8536L12 12.7071L8.85355 15.8536C8.65829 16.0488 8.34171 16.0488 8.14645 15.8536C7.95118 15.6583 7.95118 15.3417 8.14645 15.1464L11.2929 12L8.14645 8.85355Z" fill="#333333"/> </svg>';

            maxEmailAddressAllowLimit = this.recipientEmailAddressLimit;
            isSharedOrderEnhancementToggleEnabled = this.isSharedOrderEnhancementToggleEnabled;
            timeframeDownIconSrc = this.timeframeDownIcon;

            let end = new Date();

            if (isSharedOrderEnhancementToggleEnabled) {
                let allOptionsUlLiList = $('.timeframe-ul-li-ul').children('.timeframe-li-ul-li');
                $('.timeframe-ul').on('click keydown', '.timeframe-down-icon, .init', function(e) {
                    e.stopPropagation();
                    if (e.type === "click" || (e.type === "keydown" && (e.which === 13 || e.which === 32))) {
                        let optionValue = $('.timeframe-li-ul-li.selected').attr('value');
                        $('#timeframe').val(optionValue).trigger('change');
                        $(this).html($('.timeframe-li-ul-li.selected').html());
                        $(this).attr('value', optionValue);
                        $(this).closest('.timeframe-ul').children('.timeframe-li:not(.init)').slideToggle('fast');

                        $dom.get('.timeframe-li:not(.init)', function() {
                            if ($('.timeframe-li:not(.init)').css('display') == 'list-item') {
                                $('.timeframe-li-ul-li').each(function() {
                                    $(this).attr('tabindex', 0);
                                });
                                if (!$('.timeframe-li-ul-li.selected').length) {
                                    $('.timeframe-li-ul-li:first').focus();
                                } else {
                                    $('.timeframe-li-ul-li.selected').focus();
                                }
                            }
                        });
                    }
                });

                $('.timeframe-ul-li-ul').on('click keydown', '.timeframe-li-ul-li', function(e) {
                    if (e.type === "click" || (e.type === "keydown" && (e.which === 13 || e.which === 32))) {
                        e.stopPropagation();
                        allOptionsUlLiList.removeClass('selected');
                        // start trigger timeframe select dropdown
                        let optionValue = $(this).attr('value');
                        $('#timeframe').val(optionValue).trigger('change');
                        // end
                        $(this).addClass('selected');
                        $('.timeframe-ul').children('.init').html($(this).html());
                        $('.timeframe-ul').children('.init').attr('value', optionValue);
                        $('.timeframe-ul').children('.timeframe-li:not(.init)').slideToggle('fast');
                    }
                });

                $(document).on('keydown', '.timeframe-li-ul-li:last-child', function (event) {
                    if (event.keyCode === 9) { // Tab keys
                        event.preventDefault();
                        $('.timeframe-ul').children('.timeframe-li:not(.init)').slideToggle('fast');
                    }
                });

                $(document).ready(function() {
                    $('#export-date-range-picker').bind('copy paste cut',function(e) { 
                        e.preventDefault();
                    });
                });

                // start code Initialize a calendar after that default start date and end date select based on timeframe value
                let defaultStartDate = new Date();
                defaultStartDate.toLocaleDateString('en-US');

                let defaultEndDate = new Date();
                defaultEndDate.toLocaleDateString('en-US');

                $dom.get('.export-commerical-reporting-poup-model.modal-slide._show', function(elem) {
                    let timeFrame = $(elem).find('#timeframe').val().trim();
                    if (timeFrame != '' && timeFrame != null && timeFrame != undefined) {
                        $(elem).find('.order-date-range-field').css('pointer-events', 'unset');
                        for (let i = 1; i <= 12; i++) {
                            if (i == timeFrame) {
                                defaultStartDate.setMonth(defaultStartDate.getMonth() - i);
                            }
                        }
                    } else {
                        $(elem).find('.order-date-range-field').css('pointer-events', 'none');
                    }
                });
                // end

                $('#export-date-range-picker').daterangepicker({
                    opens: 'left',
                    maxDate: end,
                    startDate: defaultStartDate,
                    endDate: defaultEndDate,
                    autoUpdateInput: false,
                    autoApply: false,
                    applyButtonClasses: 'export-apply-btn',
                    cancelButtonClasses: 'export-cancel-btn',
                    locale: {
                        cancelLabel: 'Clear'
                    }
                }, function(start, end, label) {});

                $('#export-date-range-picker').on('apply.daterangepicker', function(ev, picker) {
                    let userSelectedStartDate = picker.startDate.format('MM/DD/YYYY');
                    let userSelectedEndDate = picker.endDate.format('MM/DD/YYYY');
                    let calculatedDate = new Date(userSelectedStartDate);
                    let currentDate = new Date();
                    let currentEndDate = self.getDateFormat(currentDate);
                    let timeFrameValue = $('#timeframe').val().trim();
                    if (timeFrameValue != '' && timeFrameValue != null && timeFrameValue != undefined) {
                        if (timeFrameValue.indexOf('custom') != -1) {
                            let customDateRange = timeFrameValue.split('-');
                            for (let i = 1; i <= 12; i++) {
                                if (i == customDateRange[1]) {
                                    currentEndDate = userSelectedEndDate;
                                    calculatedDate.setMonth(calculatedDate.getMonth() + i);
                                }
                            }
                        } else {
                            for (let i = 1; i <= 12; i++) {
                                if (i == timeFrameValue) {
                                    calculatedDate.setMonth(calculatedDate.getMonth() + i);
                                }
                            }
                        }
                    }

                    let calculatedEndDate = self.getDateFormat(calculatedDate);
                    
                    const compareDates = (d1, d2) => {
                        let date1 = new Date(d1).getTime();
                        let date2 = new Date(d2).getTime();

                        if (date1 > date2) {
                            $('#export-date-range-picker').data('daterangepicker').setStartDate(userSelectedStartDate);
                            $('#export-date-range-picker').data('daterangepicker').setEndDate(calculatedEndDate);
                            $(this).val(userSelectedStartDate + ' - ' + calculatedEndDate);
                        } else {
                            $('#export-date-range-picker').data('daterangepicker').setStartDate(userSelectedStartDate);
                            $('#export-date-range-picker').data('daterangepicker').setEndDate(currentEndDate);
                            $(this).val(userSelectedStartDate + ' - ' + currentEndDate);
                        }
                    };

                    compareDates(currentEndDate, calculatedEndDate);
                    $('.export-data').removeClass('disable');
                    $('.export-data').prop('disabled', false);
                });

                $('#timeframe').on('change', function () {
                    let onSelectTimeFrame = $(this).val().trim();
                    $('.export-data:not(.disable)').addClass('disable');
                    $('.export-data').prop('disabled', true);
                    if (onSelectTimeFrame != '' && onSelectTimeFrame != null && onSelectTimeFrame != undefined) {
                        $('.order-date-range-field').css('pointer-events', 'unset');
                        if (onSelectTimeFrame.indexOf('custom') != -1) {
                            let customDate = new Date();
                            customDate.toLocaleDateString('en-US');
                            $('#export-date-range-picker').data('daterangepicker').setStartDate(customDate);
                            $('#export-date-range-picker').data('daterangepicker').setEndDate(customDate);
                            $('#export-date-range-picker').val('');
                        } else {
                            for (let i = 1; i <= 12; i++) {
                                if (i == onSelectTimeFrame) {
                                    let threeMonthStartDate = new Date();
                                    threeMonthStartDate.setMonth(threeMonthStartDate.getMonth() - i);
                                    threeMonthStartDate.toLocaleDateString('en-US');
                                    $('#export-date-range-picker').data('daterangepicker').setStartDate(threeMonthStartDate);
                                    let threeMonthEndDate = new Date();
                                    threeMonthEndDate.toLocaleDateString('en-US');
                                    $('#export-date-range-picker').data('daterangepicker').setEndDate(threeMonthEndDate);
                                    $('#export-date-range-picker').val('');
                                }
                            }
                        }
                    } else {
                        $('.order-date-range-field').css('pointer-events', 'none');
                        let byDefaultSelectDate = new Date();
                        byDefaultSelectDate.toLocaleDateString('en-US');
                        $('#export-date-range-picker').data('daterangepicker').setStartDate(byDefaultSelectDate);
                        $('#export-date-range-picker').data('daterangepicker').setEndDate(byDefaultSelectDate);
                        $('#export-date-range-picker').val('');
                    }
                });

                $('#export-date-range-picker').on('cancel.daterangepicker', function(ev, picker) {
                    picker.setStartDate(new Date());
                    picker.setEndDate(new Date());
                    $(this).val('');
                    $('.order-date-range-field').css('background-color', '#ffffff');
                    $('.date-range-filter-field').css('background-color', '#ffffff');
                    $('.export-data:not(.disable)').addClass('disable');
                    $('.export-data').prop('disabled', true);
                    let timeFrameSelectedValue = $('#timeframe').val().trim();
                    if (timeFrameSelectedValue != '' && timeFrameSelectedValue != null && timeFrameSelectedValue != undefined) {
                        $('.order-date-range-field').css('pointer-events', 'unset');
                        if (timeFrameSelectedValue.indexOf('custom') != -1) {
                            let customDate = new Date();
                            customDate.toLocaleDateString('en-US');
                            $('#export-date-range-picker').data('daterangepicker').setStartDate(customDate);
                            $('#export-date-range-picker').data('daterangepicker').setEndDate(customDate);
                            $('#export-date-range-picker').val('');
                        } else {
                            for (let i = 1; i <= 12; i++) {
                                if (i == timeFrameSelectedValue) {
                                    let threeMonthStartDate = new Date();
                                    threeMonthStartDate.setMonth(threeMonthStartDate.getMonth() - i);
                                    threeMonthStartDate.toLocaleDateString('en-US');
                                    $('#export-date-range-picker').data('daterangepicker').setStartDate(threeMonthStartDate);
                                    let threeMonthEndDate = new Date();
                                    threeMonthEndDate.toLocaleDateString('en-US');
                                    $('#export-date-range-picker').data('daterangepicker').setEndDate(threeMonthEndDate);
                                    $('#export-date-range-picker').val('');
                                }
                            }
                        }
                    } else {
                        $('.order-date-range-field').css('pointer-events', 'none');
                        let byDefaultSelectDate = new Date();
                        byDefaultSelectDate.toLocaleDateString('en-US');
                        $('#export-date-range-picker').data('daterangepicker').setStartDate(byDefaultSelectDate);
                        $('#export-date-range-picker').data('daterangepicker').setEndDate(byDefaultSelectDate);
                        $('#export-date-range-picker').val('');
                    }
                });
            } else {
                $('#export-date-range-picker').daterangepicker({
                    opens: 'left',
                    maxDate: end,
                    autoUpdateInput: false,
                    autoApply: false,
                    applyButtonClasses: 'export-apply-btn',
                    cancelButtonClasses: 'export-cancel-btn',
                    locale: {
                        cancelLabel: 'Clear'
                    }
                }, function(start, end, label) {});

                $('#export-date-range-picker').on('apply.daterangepicker', function(ev, picker) {
                    let userSelectedStartDate = picker.startDate.format('MM/DD/YYYY');
                    let userSelectedEndDate = picker.endDate.format('MM/DD/YYYY');
                    let calculatedDate = new Date(userSelectedStartDate);
                    calculatedDate.setDate(calculatedDate.getDate() + 30);
                    let calculatedEndDate = self.getDateFormat(calculatedDate);
    
                    const compareDates = (d1, d2) => {
                        let date1 = new Date(d1).getTime();
                        let date2 = new Date(d2).getTime();
    
                        if (date1 > date2) {
                            $('#export-date-range-picker').data('daterangepicker').setEndDate(calculatedEndDate);
                            $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + calculatedEndDate);
                        } else {
                            $('#export-date-range-picker').data('daterangepicker').setEndDate(userSelectedEndDate);
                            $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + userSelectedEndDate);
                        }
                    };
    
                    compareDates(userSelectedEndDate, calculatedEndDate);
                });

                $('#export-date-range-picker').on('cancel.daterangepicker', function(ev, picker) {
                    picker.setStartDate(new Date());
                    picker.setEndDate(new Date());
                    $(this).val('');
                    $('.order-date-range-field').css('background-color', '#ffffff');
                    $('.date-range-filter-field').css('background-color', '#ffffff');
                });
            }

            $('.date-range-filter-field').on('keyup change blur', function () {
                setTimeout(function () {
                    if ($('.export-date-range-calendar').css('display') == 'block') {
                        $('.prev.available').attr('tabindex', '0').focus();
                        $('.next.available').attr('tabindex', '1');
                    }
                }, 100);
            });

            $('#export-date-range-picker-icon').on('click', function() {
                $('#export-date-range-picker').trigger('click');
            });

            let monthName = {
                'Jan': 'January',
                'Feb': 'February',
                'Mar': 'March',
                'Apr': 'April',
                'May': 'May',
                'Jun': 'June',
                'Jul': 'July',
                'Aug': 'August',
                'Sep': 'September',
                'Oct': 'October',
                'Nov': 'November',
                'Dec': 'December'
            };

            $dom.get('td.active', function(elem) {
                $(elem).html('<div class="date-circle">' + $(elem).html() + '</div>');
            });

            $dom.get('.modal-popup.export-commerical-reporting-poup-model.modal-slide', function(elem) {
                $(elem).find('header button.action-close').remove();
            });

            $dom.get('.export-date-range-calendar .calendar-table .table-condensed thead > tr:first-child', function(elem) {
                for (let key in monthName) {
                    $(elem).find('th.month').each(function(){
                        if ($(this).text().includes(key)) {
                            $(this).text(monthName[key]+' ' + $(this).text().replaceAll(key, '').trim());
                        }
                    });
                }
            });

            $dom.get('.export-date-range-calendar .calendar-table .table-condensed tbody > tr:first-child, tr:last-child', function(elem) {
                let weekendDaysCount = 0;
                $(elem).find('td.off.ends.available').each(function(){
                    weekendDaysCount++;
                });
                if (weekendDaysCount == 7) {
                    $(elem).remove();
                }
            });

            $dom.get('.export-date-range-calendar .calendar-table .table-condensed', function(elem) {
                $(elem).find('.off.ends.available').each(function(){
                    $(this).text('');
                });
            });

            $dom.get('#export-date-range-picker', function(elem) {
                $(elem).val('');
                $('.order-date-range-field').css('background-color', '#ffffff');
                $('.date-range-filter-field').css('background-color', '#ffffff');
            });

            if (isSharedOrderEnhancementToggleEnabled) {
                $(document).on('keydown', '.init', function (event) {
                    if (event.keyCode === 9) { // Tab keys
                        event.preventDefault();
                        setTimeout(function () {
                            if ($('.export-date-range-calendar').css('display') == 'block') {
                                $('.prev.available').attr('tabindex', '0').focus();
                                $('.next.available').attr('tabindex', '1');
                            }
                        }, 100);
                    }
                });

                $(document).on("click", function (e) {
                    if ($('.timeframe-li:not(.init)').css('display') == 'list-item') {
                        $('.timeframe-ul').children('.timeframe-li:not(.init)').slideToggle('fast');
                    }
                });

                $(document).on("keydown", function (e) {
                    if (e.which == 40 && $('.timeframe-li:not(.init)').css('display') == 'list-item') {
                        if (!$('ul.timeframe-ul-li-ul > li').is(":focus")) {
                            e.preventDefault();
                            $('ul.timeframe-ul-li-ul > li').eq(1).focus();
                        }
                        if ($('ul.timeframe-ul-li-ul > li').is(":focus")) {
                            e.preventDefault();
                            $('ul.timeframe-ul-li-ul > li:focus').next().focus();
                        }
                    } else if (e.which == 38 && $('.timeframe-li:not(.init)').css('display') == 'list-item') {
                       e.preventDefault();
                       $('ul.timeframe-ul-li-ul > li:focus').prev().focus();
                    }
                });

                $('.export-commercial-reporting-section span.action-close').on('keydown', function(e) {
                    if (e.keyCode == 13) {
                        $('.export-data:not(.disable)').addClass('disable');
                        $('.export-data').prop('disabled', true);
                        $(this).trigger('click');
                        let allOptionsUlLiList = $('.timeframe-ul-li-ul').children('.timeframe-li-ul-li');
                        allOptionsUlLiList.removeClass('selected');
                        $('.timeframe-ul').children('.init').attr('value', '');
                        let arrowAltTag = ' alt="timeframe-down-icon"';
                        let arrowClass = ' class="timeframe-down-icon"';
                        let arrowIcon = ' <img src="'+timeframeDownIconSrc+'"'+arrowAltTag+arrowClass+'/>';
                        $('.timeframe-ul').children('.init').html('Select a timeframe'+arrowIcon);
                        $('.email-input-text').css('border', '');
                        $('.email-input-text').css('box-shadow', '');
                    }
                    if (e.keyCode == 9) { // Tab keys
                        e.preventDefault();
                        $('.init').attr('tabindex', '0').focus();
                    }
                });

                $('.export-commercial-reporting-section span.action-close').on('click', function() {
                    $('.email-ids').remove();
                    $('#email_address').val('');
                    if ($('#export-date-range-picker').val()) {
                        $('.export-cancel-btn').trigger('click');
                    }
                    $('.export-data:not(.disable)').addClass('disable');
                    $('.export-data').prop('disabled', true);
                    let allOptionsUlLiList = $('.timeframe-ul-li-ul').children('.timeframe-li-ul-li');
                    allOptionsUlLiList.removeClass('selected');
                    $('.timeframe-ul').children('.init').attr('value', '');
                    let arrowAltTag = ' alt="timeframe-down-icon"';
                    let arrowClass = ' class="timeframe-down-icon"';
                    let arrowIcon = ' <img src="'+timeframeDownIconSrc+'"'+arrowAltTag+arrowClass+'/>';
                    $('.timeframe-ul').children('.init').html('Select a timeframe'+arrowIcon);
                    $('#email_address').attr("placeholder", 'Enter recipient email');
                    $('.email-input-text').css('border', '');
                    $('.email-input-text').css('box-shadow', '');
                });

                $('.cancel-export').on('click', function() {
                    $('.email-ids').remove();
                    $('#email_address').val('');
                    if ($('#export-date-range-picker').val()) {
                        $('.export-cancel-btn').trigger('click');
                    }
                    $('.export-data:not(.disable)').addClass('disable');
                    $('.export-data').prop('disabled', true);
                    let allOptionsUlLiList = $('.timeframe-ul-li-ul').children('.timeframe-li-ul-li');
                    allOptionsUlLiList.removeClass('selected');
                    $('.timeframe-ul').children('.init').attr('value', '');
                    let arrowAltTag = ' alt="timeframe-down-icon"';
                    let arrowClass = ' class="timeframe-down-icon"';
                    let arrowIcon = ' <img src="'+timeframeDownIconSrc+'"'+arrowAltTag+arrowClass+'/>';
                    $('.timeframe-ul').children('.init').html('Select a timeframe'+arrowIcon);
                    $('.action-close').trigger('click');
                    $('#email_address').attr("placeholder", 'Enter recipient email');
                    $('.email-input-text').css('border', '');
                    $('.email-input-text').css('box-shadow', '');
                });

                $('.export-commercial-reporting-btn').on('keydown', function(e) {
                    if (e.keyCode == 13) {
                        setTimeout(function () {
                            $('.action-close').focus();
                        }, 100);
                    }
                });
            } else {
                $('.cancel-export').on('click', function() {
                    $('.email-ids').remove();
                    $('#email_address').val('');
                    if ($('#export-date-range-picker').val()) {
                        $('.export-cancel-btn').trigger('click');
                    }
                    $('.action-close').trigger('click');
                    $('#email_address').attr("placeholder", 'Enter recipient email');
                    $('.email-input-text').css('border', '');
                    $('.email-input-text').css('box-shadow', '');
                });

                $('.export-commercial-reporting-section span.action-close').on('click', function() {
                    $('.email-ids').remove();
                    $('#email_address').val('');
                    if ($('#export-date-range-picker').val()) {
                        $('.export-cancel-btn').trigger('click');
                    }
                    $('#email_address').attr("placeholder", 'Enter recipient email');
                    $('.email-input-text').css('border', '');
                    $('.email-input-text').css('box-shadow', '');
                });

                $('.export-commercial-reporting-section span.action-close').on('keydown', function(e) {
                    if (e.keyCode == 13) {
                        $('.email-input-text').css('border', '');
                        $('.email-input-text').css('box-shadow', '');
                        $(this).trigger('click');
                    }
                });
            }

            $('.recipients-email-address').on('click', function() {
                $(this).find('input').focus();
            });

            $('#email_address').on('keypress blur', function(e) {
                if (e.keyCode === 13 || e.handleObj.type == "blur") {
                    let finalEmailsInputValue = '';
                    let emailAddressFieldValue = $(this).val();
                    let validateEmailAddress = /^[A-Z0-9._%+-]+@([A-Z0-9-]+\.)+[A-Z]{2,4}$/i;
                    let emailTabsLimit = 0;
                    let emailTabs = $('.all-mail .email-ids');

                    if (emailAddressFieldValue) {
                        emailAddressFieldValue = emailAddressFieldValue.replace(/,\s*$/, "");
                        let countEmailSeparateByComma = (emailAddressFieldValue.match(/,/g) || []).length;
                        emailTabsLimit = emailTabs.length;
                        if (countEmailSeparateByComma > 0) {
                            let emailAddressFieldArrays = emailAddressFieldValue.split(",");
                            $.each(emailAddressFieldArrays, function(i, emailAddress) {
                                if (emailTabsLimit && emailTabsLimit < maxEmailAddressAllowLimit && validateEmailAddress.test(emailAddress)) {
                                    finalEmailsInputValue+= '<span class="email-ids">'+emailAddress.trim()+'<span class="cancel-email" tabindex="0">' + crossIcon + '</span></span>';
                                    emailTabsLimit++;
                                } else if (!emailTabsLimit && i < maxEmailAddressAllowLimit && validateEmailAddress.test(emailAddress)) {
                                    finalEmailsInputValue+= '<span class="email-ids">'+emailAddress.trim()+'<span class="cancel-email" tabindex="0">' + crossIcon + '</span></span>';
                                }
                            });
                            $('.all-mail').append(finalEmailsInputValue);
                            $('.email-input-text').css('border', '0px');
                            $('.email-input-text').css('box-shadow', 'none');
                        } else {
                            if (emailTabsLimit < maxEmailAddressAllowLimit && validateEmailAddress.test(emailAddressFieldValue)) {
                                $('.all-mail').append('<span class="email-ids">'+emailAddressFieldValue+'<span class="cancel-email" tabindex="0">' + crossIcon + '</span></span>');
                                $('.email-input-text').css('border', '0px');
                                $('.email-input-text').css('box-shadow', 'none');
                            }
                        }
                    }
                    $(this).val('');

                    if ($('.all-mail').html()) {
                        $('#email_address').attr("placeholder", '');
                    }
                }
            });

            //removing the email with enter key
            $(document).on('click keypress', '.cancel-email', function (e) {
                if (e.type === "click" || (e.type === "keypress" && (e.which === 13 || e.which === 32))) {
                    $(this).closest('.email-ids').remove();
                    if (!$('.all-mail').html()) {
                        $('.email-input-text').css('border', '');
                        $('.email-input-text').css('box-shadow', '');
                        $('#email_address').attr("placeholder", 'Enter recipient email');
                    }
                }
            });

            //Go back to calendar input when clear btn is click
            $(document).on('click keypress', '.export-cancel-btn', function (e) {
                if (e.type === "click" || (e.type === "keypress" && (e.which === 13 || e.which === 32))) {
                    e.preventDefault();
                    $('.date-range-filter-field').val('');
                    $('.order-date-range-field').css('background-color', '#ffffff');
                    $('.date-range-filter-field').css('background-color', '#ffffff');
                    $('#export-date-range-picker').focus();
                    $('#export-date-range-picker').data('daterangepicker').hide();
                    $('.email-input-text').css('border', '');
                    $('.email-input-text').css('box-shadow', '');
                    $('.export-data:not(.disable)').addClass('disable');
                    $('.export-data').prop('disabled', true);
                }
            });
            $(document).on('keydown', '.export-cancel-btn', function (e) {
                if (e.which === 9) { // Tab key
                    $('.export-apply-btn').attr('tabindex', '2').css('box-shadow', '0 0 3px 1px #00699d');
                }
            });
            //Go back to calendar input when apply is click
            $(document).on('click keypress', '.export-apply-btn', function (e) {
                if (e.type === "click" || (e.type === "keypress" && (e.which === 13 || e.which === 32))) {
                    $('#export-date-range-picker').focus();
                    $('#export-date-range-picker').data('daterangepicker').hide();
                }
            });
            ///Go back to email when tab key is click after apply
            $(document).on('keydown', '.export-apply-btn', function (e) {
                if (e.which === 9) { // Tab key
                    $('#export-date-range-picker').focus();
                    $('#export-date-range-picker').data('daterangepicker').hide();
                }
            });

            // moving the arrow with click of prev.avaliable
            $(document).on('keydown', '.prev.available', function (event) {
                let _this = this;
                if (event.which === 13) { // Enter key
                    // Find the previous month's 'prev.available' element
                    $(_this).trigger("click");
                    if ($('.export-date-range-calendar').css('display') == 'block') {
                        $('.prev.available').attr('tabindex', '0').css('box-shadow', '0 0 3px 1px #00699d');
                        $('.prev.available').css('box-shadow', '0 0 3px 1px #00699d');
                    }
                }

                if (event.which === 9) { // Tab key
                    event.preventDefault(); // Prevent default tab behavior
                    if ($('.next.available').length > 0) { // if not undefined
                        $('.next.available').attr('tabindex', '1').focus(); // Move focus to the next button
                    } else {
                        $('.drp-buttons .export-cancel-btn').attr('tabindex', '1').focus();
                    }
                }
            });

            $(document).on('keydown', '.next.available', function (event) {
                let _this = this;
                if (event.which === 13) { // Enter key
                    $(_this).trigger("click");
                    // Now move focus into table td's
                    setTimeout(function () {
                        if ($('.export-date-range-calendar').css('display') == 'block') {
                            $('.next.available').attr('tabindex', '0').css('box-shadow', '0 0 3px 1px #00699d');
                            $('.next.available').css('box-shadow', '0 0 3px 1px #00699d');
                        }
                    }, 100);
                }

                if (event.which === 9) { // Tab key
                    event.preventDefault(); // Prevent default tab behavior
                    $('.drp-buttons .export-cancel-btn').attr('tabindex', '1').focus();
                }
            });

            $('.drp-buttons .export-cancel-btn, .drp-buttons .export-apply-btn').on('keydown', function (event) {
                if (event.shiftKey && event.keyCode === 9) { // Shift + Tab keys
                    event.preventDefault();
                    $('#export-date-range-picker').focus();
                }
            });

            let currentItem = $('.items li.nav.current'),
                titleText = currentItem.find('a').text();

            if (!titleText){
                titleText = currentItem.find('strong').text();
            }
            if(titleText) {
                $('.title strong').html(titleText);
            }
            $('.export-data').on('click', function() {
                self.generateCommercialReport();
            });
        },

        getDateFormat: function(prepareEndDate) {
            let preparedEndDate = prepareEndDate.toLocaleDateString('en-US');
            let prepareEndDateArray = preparedEndDate.split("/");
            if (prepareEndDateArray[0] < 10 && prepareEndDateArray[1] > 9) {
                preparedEndDate = '0'+prepareEndDateArray[0]+'/'+prepareEndDateArray[1]+'/'+prepareEndDateArray[2];
            } else if (prepareEndDateArray[0] > 9 && prepareEndDateArray[1] < 10) {
                preparedEndDate = prepareEndDateArray[0]+'/0'+prepareEndDateArray[1]+'/'+prepareEndDateArray[2];
            } else if (prepareEndDateArray[0] < 10 && prepareEndDateArray[1] < 10) {
                preparedEndDate = '0'+prepareEndDateArray[0]+'/0'+prepareEndDateArray[1]+'/'+prepareEndDateArray[2];
            }

            return preparedEndDate;
        },

        commercialReportingPopupModel: function() {
            let options = {
                type: 'popup',
                modalClass: 'export-commerical-reporting-poup-model',
                responsive: true,
                innerScroll: false,
                title: '',
                buttons: [{
                    class: '',
                    click: function () {
                        this.closeModal();
                    }
                }]
            };

            $('.shared-order-history .daterangepicker').each(function() {
                let isAutoApplyClassExit = $(this).hasClass('auto-apply');
                if (isAutoApplyClassExit === false) {
                    $(this).addClass('export-date-range-calendar');
                }
            });

            $('.export-commercial-reporting-section').modal(options).modal('openModal');
            $('.export-commercial-reporting-section').on('modalclosed', function() {
                $('#export-commercial-reporting-form').trigger("reset");
                $('.order-date-range-field').css('pointer-events', 'none');
                $('.email-ids').remove();
                $('#email_address').val('');
                if ($('#export-date-range-picker').val()) {
                    $('.export-cancel-btn').trigger('click');
                }
                if (isSharedOrderEnhancementToggleEnabled) {
                    $('.export-data:not(.disable)').addClass('disable');
                    $('.export-data').prop('disabled', true);
                    let allOptionsUlLiList = $('.timeframe-ul-li-ul').children('.timeframe-li-ul-li');
                    allOptionsUlLiList.removeClass('selected');
                    $('.timeframe-ul').children('.init').attr('value', '');
                    let arrowAltTag = ' alt="timeframe-down-icon"';
                    let arrowClass = ' class="timeframe-down-icon"';
                    let arrowIcon = ' <img src="'+timeframeDownIconSrc+'"'+arrowAltTag+arrowClass+'/>';
                    $('.timeframe-ul').children('.init').html('Select a timeframe'+arrowIcon);
                    $('#email_address').attr("placeholder", 'Enter recipient email');
                }
            });
        },

        /**
         * Generate report
         */
        generateCommercialReport: function() {
            let dateRange = $('#export-date-range-picker').val();
            let emailIds = [];
            let counter = 0;
            $('.email-ids').each(function() { 
                let emailHtml = '';
                emailHtml = $(this).html().split("<span");
                emailIds[counter] = emailHtml[0];
                counter++;
            });

            // dataRange & Emails and make Ajax call to a controller to get Spreadsheet
            let requestUrl = "shared/order/generatereport/?dateRange="+dateRange+"&emailData="+emailIds;
            $.ajax({
                url: urlBuilder.build(
                    requestUrl
                ),
                type: "GET",
                data: '',
                showLoader: true,
                async: true
            }).done(function (response) {
                $('.cancel-export').trigger('click');
                $('.reorder-success-msg').html('');
                $('.reorder-success-msg').append('<p class="reorder-notification-msg"><b>Report exported</b></p>');
                $('.reorder-success-msg').append('<p>You will receive the report in your inbox shortly.</p>'); 
                $('.reorder-view-cart').hide();
                $('.reorder-success').show();
            });
        }
    });
});
