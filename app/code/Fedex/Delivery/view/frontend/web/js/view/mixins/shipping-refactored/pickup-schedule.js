define([
    "jquery",
    "mage/url",
    "Magento_Checkout/js/model/shipping-service",
    "Fedex_Delivery/js/model/toggles-and-settings",
    "fedex/storage"
], function (
    $,
    urlBuilder,
    shippingService,
    togglesAndSettings,
    fxoStorage
) {
    'use strict';

    function convertToTwentyFourHours(timeToConvert) {
        let [time, modifier] = timeToConvert.split(' ');
        let [hours, minutes] = time.split(':');
        hours = (hours === '12') ? '00' : hours;
        if (modifier === 'PM') {
            hours = parseInt(hours, 10) + 12;
        }
        return hours;
    };

    async function callRateQuoteApi() {
        let locationId = null,
            requestedPickupDateTime = null,
            requestUrl = urlBuilder.build("delivery/index/ratequoteapi");
        if(window.e383157Toggle) {
            locationId = fxoStorage.get('locationId');
        } else {
            locationId = localStorage.getItem('locationId');
        }
        if(window.e383157Toggle){
            requestedPickupDateTime = fxoStorage.get("pickupDateTimeForApi");
        }else{
            requestedPickupDateTime = localStorage.getItem("pickupDateTimeForApi");
        }
        requestUrl = requestUrl + "?requestedPickupDateTime="+requestedPickupDateTime+"&locationId="+locationId;
        await fetch(requestUrl);
    }

    return function (shipping) {
        return shipping.extend({

            initialize: function (config) {
                this._super();
            },

            /**
             * Function to set time slots to be filled inside pickup date and time picker
             */
            setTimeSlots: function () {
                let items = [];
                for (var hour = 0; hour < 24; hour++) {
                    items.push([hour, 0]);
                    items.push([hour, 30]);
                }

                const date = new Date();
                const formatter = new Intl.DateTimeFormat('en-US', {
                    hour: 'numeric',
                    minute: 'numeric',
                    hour12: true
                });

                const range = items.map(time => {
                    const [hour, minute] = time;
                    date.setHours(hour);
                    date.setMinutes(minute);

                    return formatter.format(date);
                });
                var times = [];
                range.forEach(item => {
                    var time = "";
                    if (item.includes("PM")) {
                        var splitSpace = item.split(" ");
                        var splitcolan = splitSpace[0].split(":");
                        var pmTime = parseInt(splitcolan[0]) + 12;
                        time = pmTime.toString() + ":" + splitcolan[1] + ":00";
                    } else {
                        var splitSpace = item.split(" ");
                        time = splitSpace[0] + ":00";
                    }
                    item = item.replace("AM", "a.m.");
                    item = item.replace("PM", "p.m.");
                    times.push({ label: item, value: time });
                });
                this.timeSlots(times);
            },

            getSelectedTimeRange: function (day, flagToday, flagFutureEarliest) {
                let timeRanges;
                this.koFlagIsToday(flagToday);
                this.koFlagIsFutureEarlist(flagFutureEarliest);
                switch (day) {
                    case 0:
                        timeRanges = this.koPickupTimeAvailability().find(element => element.day === 'Sun');
                        break;
                    case 1:
                        timeRanges = this.koPickupTimeAvailability().find(element => element.day === 'Mon');
                        break;
                    case 2:
                        timeRanges = this.koPickupTimeAvailability().find(element => element.day === 'Tue');
                        break;
                    case 3:
                        timeRanges = this.koPickupTimeAvailability().find(element => element.day === 'Wed');
                        break;
                    case 4:
                        timeRanges = this.koPickupTimeAvailability().find(element => element.day === 'Thu');
                        break;
                    case 5:
                        timeRanges = this.koPickupTimeAvailability().find(element => element.day === 'Fri');
                        break;
                    case  6:
                        timeRanges = this.koPickupTimeAvailability().find(element => element.day === 'Sat');
                }

                this.koSelectedTimeRange(timeRanges.range);
            },

            getTimeSlotsByRange: function () {
                let self = this;
                let timeSlots = [];
                let flag = self.koFlagIsToday();
                let flagFutureDay = self.koFlagIsFutureEarlist();
                let timenow = new Date();
                timenow = timenow.getHours();
                let earliestHour = parseInt(self.koEarliestHour());
                let timeRange = self.koSelectedTimeRange();
                let timeRangeArray = timeRange.split(" - ");
                let openTime = timeRangeArray[0];
                let closeTime = timeRangeArray[1];

                let initOpenTimeArray = openTime.split(":");
                let initOpenTime = initOpenTimeArray[0];
                let initOpenTimeMin = initOpenTimeArray[1].split(' ')[0];
                let initCloseTimeArray = closeTime.split(":");
                let initCloseTime = initCloseTimeArray[0];
                let twelveHour = 12;
                let twelvePM = twelveHour +':00 PM';
                let twelveAM = twelveHour +':00 AM';
                let timeFormat;

                if (openTime.indexOf('A.M') > -1 && closeTime.indexOf('A.M') == -1) {
                    if (initOpenTime < twelveHour) {
                        for (let k = initOpenTime; k < twelveHour; k++) {
                            if(k === initOpenTime){
                                timeFormat = k + ':' + initOpenTimeMin + ' AM';
                            } else {
                                timeFormat = k + ':00 AM';
                            }

                            let checkHours = convertToTwentyFourHours(timeFormat);
                            if((flag && checkHours <= timenow) || (flag && checkHours < earliestHour)) {

                            } else if((flagFutureDay && checkHours < earliestHour)) {

                            } else {
                                var option = {
                                    'value': timeFormat,
                                    'label': timeFormat
                                };
                            }

                            //checking iff valid option
                            if(typeof(option) !== "undefined") {
                                timeSlots.push(option);
                            }
                        }
                        if(initCloseTime < twelveHour) {
                            let checkHours = convertToTwentyFourHours(twelvePM);
                            if((flag && checkHours <= timenow) || (flag && checkHours < earliestHour)) {

                            } else if((flagFutureDay && checkHours < earliestHour)) {

                            } else {
                                var option = {
                                    'value': twelvePM,
                                    'label': twelvePM
                                };
                            }

                            //checking iff valid option
                            if(typeof(option) !== "undefined") {
                                timeSlots.push(option);
                            }
                            for (let m = 1; m <= initCloseTime; m++) {
                                timeFormat = m + ':00 PM';
                                let checkHours = convertToTwentyFourHours(timeFormat);
                                if((flag && checkHours <= timenow) || (flag && checkHours < earliestHour)) {

                                } else if((flagFutureDay && checkHours < earliestHour)) {

                                } else {
                                    var option = {
                                        'value': timeFormat,
                                        'label': timeFormat
                                    };
                                }

                                //checking iff valid option
                                if(typeof(option) !== "undefined") {
                                    timeSlots.push(option);
                                }
                            }
                        } else {
                            let checkHours = convertToTwentyFourHours(twelvePM);
                            if((flag && checkHours <= timenow) || (flag && checkHours < earliestHour)) {

                            } else if((flagFutureDay && checkHours < earliestHour)) {

                            } else {
                                var option = {
                                    'value': twelvePM,
                                    'label': twelvePM
                                };
                            }

                            //checking iff valid option
                            if(typeof(option) !== "undefined") {
                                timeSlots.push(option);
                            }
                        }
                    }
                } else if (openTime.indexOf('P.M') > -1  && closeTime.indexOf('P.M') == -1) {
                    if(initOpenTime < twelveHour) {
                        for (let k = initOpenTime; k < twelveHour; k++) {
                            if(k === initOpenTime){
                                timeFormat = k + ':' + initOpenTimeMin + ' PM';
                            } else {
                                timeFormat = k + ':00 PM';
                            }

                            let checkHours = convertToTwentyFourHours(timeFormat);
                            if((flag && checkHours <= timenow) || (flag && checkHours < earliestHour)) {

                            } else if((flagFutureDay && checkHours < earliestHour)) {

                            } else {
                                var option = {
                                    'value': timeFormat,
                                    'label': timeFormat
                                };
                            }

                            //checking iff valid option
                            if(typeof(option) !== "undefined") {
                                timeSlots.push(option);
                            }
                        }
                        if(initCloseTime < twelveHour) {
                            let checkHours = convertToTwentyFourHours(twelveAM);
                            if((flag && checkHours <= timenow) || (flag && checkHours < earliestHour)) {

                            } else if((flagFutureDay && checkHours < earliestHour)) {

                            } else {
                                var option = {
                                    'value': twelveAM,
                                    'label': twelveAM
                                };
                            }

                            //checking iff valid option
                            if(typeof(option) !== "undefined") {
                                timeSlots.push(option);
                            }
                            for (let m = 1; m <= initCloseTime; m++) {
                                timeFormat = m + ':00 AM';
                                let checkHours = convertToTwentyFourHours(timeFormat);
                                if((flag && checkHours <= timenow) || (flag && checkHours < earliestHour)) {

                                } else if((flagFutureDay && checkHours < earliestHour)) {

                                } else {
                                    var option = {
                                        'value': timeFormat,
                                        'label': timeFormat
                                    };
                                }

                                //checking iff valid option
                                if(typeof(option) !== "undefined") {
                                    timeSlots.push(option);
                                }
                            }
                        } else {
                            let checkHours = convertToTwentyFourHours(twelveAM);
                            if((flag && checkHours <= timenow) || (flag && checkHours < earliestHour)) {

                            } else if((flagFutureDay && checkHours < earliestHour)) {

                            } else {
                                var option = {
                                    'value': twelveAM,
                                    'label': twelveAM
                                };
                            }

                            //checking iff valid option
                            if(typeof(option) !== "undefined") {
                                timeSlots.push(option);
                            }
                        }
                    }
                } else if (openTime.indexOf('A.M') > -1  && closeTime.indexOf('A.M') > -1) {
                if(initOpenTime <= twelveHour) {
                    if(initOpenTime == twelveHour) {
                        let checkHours = twelveHour;
                        if((flag && checkHours <= timenow) || (flag && checkHours < earliestHour)) {

                        } else if((flagFutureDay && checkHours < earliestHour)) {

                        } else {
                            var twelveAMWithMin = '12:'+initOpenTimeMin+' AM';
                            var option = {
                                'value': twelveAMWithMin,
                                'label': twelveAMWithMin
                            };
                        }
                        initOpenTime = 1;

                        //checking iff valid option
                        if(typeof(option) !== "undefined") {
                            timeSlots.push(option);
                        }
                    }
                    for (let n = initOpenTime; n <= initCloseTime; n++) {

                        if(n === initOpenTime && !timeSlots.length){
                          timeFormat = n + ':' + initOpenTimeMin + ' AM';
                        } else {
                          timeFormat = n + ':00 AM';
                        }

                        let checkHours = convertToTwentyFourHours(timeFormat);
                        if((flag && checkHours <= timenow) || (flag && checkHours < earliestHour)) {

                        } else if((flagFutureDay && checkHours < earliestHour)) {

                        } else {
                            var option = {
                                'value': timeFormat,
                                'label': timeFormat
                            };
                        }

                        //checking iff valid option
                        if(typeof(option) !== "undefined") {
                            timeSlots.push(option);
                        }
                    }
                }
                } else if (openTime.indexOf('P.M') > -1  && closeTime.indexOf('P.M') > -1) {
                    if(initOpenTime <= twelveHour) {
                        if(initOpenTime == twelveHour) {
                            let checkHours = twelveHour;
                            if((flag && checkHours <= timenow) || (flag && checkHours < earliestHour)) {

                            } else if((flagFutureDay && checkHours < earliestHour)) {

                            } else {
                                var twelvePMwithMin = '12:'+initOpenTimeMin+' PM';
                                var option = {
                                    'value': twelvePMwithMin,
                                    'label': twelvePMwithMin
                                };
                            }
                            initOpenTime = 1;

                            //checking iff valid option
                            if(typeof(option) !== "undefined") {
                                timeSlots.push(option);
                            }
                        }
                        for (let n = initOpenTime; n <= initCloseTime; n++) {

                            if(n === initOpenTime && !timeSlots.length){
                                timeFormat = n + ':' + initOpenTimeMin + ' PM';
                            } else {
                                timeFormat = n + ':00 PM';
                            }

                            let checkHours = convertToTwentyFourHours(timeFormat);
                            if((flag && checkHours <= timenow) || (flag && checkHours < earliestHour)) {

                            } else if((flagFutureDay && checkHours < earliestHour)) {

                            } else {
                                var option = {
                                    'value': timeFormat,
                                    'label': timeFormat
                                };
                            }

                            //checking iff valid option
                            if(typeof(option) !== "undefined") {
                                timeSlots.push(option);
                            }
                        }
                    }
                }
                timeSlots.pop()
                const lastHour = timeSlots[timeSlots.length - 1];
                var halfHourValue = lastHour.value.replace(':00', ':30');
                var halfHourLabel = lastHour.label.replace(':00', ':30');
                var halfHourArray = {value: halfHourValue, label: halfHourLabel};
                timeSlots.push(halfHourArray);
                self.availableTimeslotOptions(timeSlots)
            },

            setPickupTimeOnOpen: function () {
                let self = this;
                if (self.isPromiseTimePickupOptionsToggle()) {
                    let pickupTimeOnOpen = parseInt(self.koEarliestHour());
                    let period = pickupTimeOnOpen < 12 ? 'AM' : 'PM';
                    let hour = pickupTimeOnOpen % 12 ? pickupTimeOnOpen % 12 : 12;
                    self.selectedTimeslot(`${hour}:00 ${period}`);
                }
            },

            getPickupLocationTimeSlots: function (locationId) {
                let self = this;
                self.koSelectedDate(null);
                $.ajax({
                    url: urlBuilder.build(
                        "delivery/index/centerDetails"
                    ),
                    type: "POST",
                    data: { locationId: locationId },
                    dataType: "json",
                    showLoader: false,
                    success: function (data) {
                        if (data.hasOwnProperty("errors")) {
                            $('.error-container').removeClass('api-error-hide');
                            return true;
                        }

                        data.hoursOfOperation = shippingService.getHoursOfFirstWeek(data.hoursOfOperation);
                        self.koPickupTimeAvailability(data.hoursOfOperation);
                        return true;
                    }
                }).done(function (response) {
                    if (self.isPromiseTimePickupOptionsToggle() && self.openPickupTimeModal()) {
                        self.showPickupTimeModal();
                        self.openPickupTimeModal(false);
                    }
                    if (response.hasOwnProperty("errors")) {
                        $('.error-container').removeClass('api-error-hide');
                        return true;
                    }
                });
            },

            removeTimeFromDateFormat: function (date) {
                return new Date(date.getFullYear(), date.getMonth(), date.getDate());
            },

            selectedTimeSlotDropdown: function (selectedDate) {
                this.koSelectedDate(selectedDate);
                    let selectedDateFormat = new Date(selectedDate);
                    let selectedDateCompare = this.removeTimeFromDateFormat(selectedDateFormat);
                    let selectDay = selectedDateFormat.getDay();
                    let defaultDateFormat = new Date();
                    let defaultDateCompare = this.removeTimeFromDateFormat(defaultDateFormat);

                    let checkFlag = (selectedDateCompare.getTime() === defaultDateCompare.getTime()) ? true : false;
                    let isFutureDate = (selectedDateCompare.getTime() === this.koEarliestDayFormat()) ? true : false;

                    //To find Selected Time Range
                    this.getSelectedTimeRange(selectDay, checkFlag, isFutureDate);

                    //To find Time Slots based on Range
                    this.getTimeSlotsByRange();
            },

            updatePickupDateTimeFormat: function () {
                let month = ["January","February","March","April","May","June","July","August","September","October","November","December"];
                let day = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
                let optDate = this.pickupDatePicker.datepicker("getDate");
                optDate = new Date(optDate);
                this.koSelectedDate(optDate);// resetting selected Date
                if (this.isPromiseTimePickupOptionsToggle()) {
                    this.koSelectedPickupLaterDate(optDate);
                }
                let optTime = this.selectedTimeslot();

                if(typeof(optTime) !== "undefined") {
                    let hours = convertToTwentyFourHours(optTime);

                    let codeMonth = optDate.getMonth();
                    let updateMonth = month[codeMonth];
                    let updateDay = day[optDate.getDay()];
                    let updateDate = optDate.getDate();

                    let updatedTime = optTime.replace(/\s/g, '').toLowerCase();
                    let pickupDateTimeDisplay = updateDay+', '+updateMonth+' '+updateDate+', '+updatedTime;
                    let pickupLaterDateTimeDisplay = updateDay+', '+updateMonth+' '+updateDate+' at '+optTime.toUpperCase();

                    let saveYear = optDate.getFullYear();
                    let saveMonth = codeMonth;
                    saveMonth = parseInt(saveMonth) + 1;
                    let saveDate = updateDate;
                    let [pickupTime, pickupModifier] = optTime.split(' ');
                    let [pickupHours, pickupMinutes] = pickupTime.split(':');
                    let dateToSave = saveYear + '-' + saveMonth + '-' + saveDate + 'T' + hours + ':'+ pickupMinutes +':00';

                    //saving into DB through Api call
                    if(window.e383157Toggle){
                        fxoStorage.set('updatedChangedPickupDateTime', dateToSave);
                    }else{
                        localStorage.setItem("updatedChangedPickupDateTime", dateToSave);
                    }
                    var datePickup = optDate;
                    datePickup.setHours(hours);
                    datePickup.setMinutes(pickupMinutes);
                    if(window.e383157Toggle){
                        fxoStorage.set(
                            'pickupDateTimeForApi', new Date(datePickup.getTime() - datePickup.getTimezoneOffset()*60*1000).toISOString().substr(0,19)
                        );
                    }else{
                        localStorage.setItem(
                            'pickupDateTimeForApi', new Date(datePickup.getTime() - datePickup.getTimezoneOffset()*60*1000).toISOString().substr(0,19)
                        );
                    }
                    callRateQuoteApi();
                    if(window.e383157Toggle){
                        fxoStorage.set("pickupDateTime", pickupDateTimeDisplay);
                    }else{
                        localStorage.setItem("pickupDateTime", pickupDateTimeDisplay);
                    }
                    this.koEarliestPickupDateTime(pickupDateTimeDisplay);
                    if (this.isPromiseTimePickupOptionsToggle()) {
                        this.koFormattedPickupLaterDate(pickupLaterDateTimeDisplay);
                        if (this.currentPickupLaterRadio()) {
                            this.currentPickupLaterRadio().siblings('#pickup_later_date').show();
                        }
                    }
                }
            },

            updatePickupTime: function () {
                let month = ["January","February","March","April","May","June","July","August","September","October","November","December"];
                let day = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
                let optDate = this.pickupDatePicker.datepicker( "getDate" );
                optDate = new Date(optDate);
                this.koSelectedDate(null);// resetting selected Date
                let optTime = this.selectedTimeslot();

                if(typeof(optTime) !== "undefined") {
                    let hours = convertToTwentyFourHours(optTime);

                    let codeMonth = optDate.getMonth();
                    let updateMonth = month[codeMonth];
                    let updateDay = day[optDate.getDay()];
                    let updateDate = optDate.getDate();

                    let saveYear = optDate.getFullYear();
                    let saveMonth = codeMonth;
                    saveMonth = parseInt(saveMonth) + 1;
                    let saveDate = updateDate;
                    let [pickupTime, pickupModifier] = optTime.split(' ');
                    let [pickupHours, pickupMinutes] = pickupTime.split(':');
                    let dateToSave = saveYear + '-' + saveMonth + '-' + saveDate + 'T' + hours + ':'+ pickupMinutes +':00';

                    //saving into DB through Api call

                    var datePickup = optDate;
                    datePickup.setHours(hours);
                    datePickup.setMinutes(pickupMinutes);
                    if(window.e383157Toggle){
                        fxoStorage.set("updatedChangedPickupDateTime", dateToSave);
                        fxoStorage.set('pickupDateTimeForApi', new Date(datePickup.getTime() - datePickup.getTimezoneOffset()*60*1000).toISOString().substr(0,19));
                    }else{
                        localStorage.setItem("updatedChangedPickupDateTime", dateToSave);
                        localStorage.setItem('pickupDateTimeForApi', new Date(datePickup.getTime() - datePickup.getTimezoneOffset()*60*1000).toISOString().substr(0,19));
                    }
                    //disply the pickup time and date in review summary page
                    let dateAppendTxt;
                    if(updateDate === 1  || updateDate === 21 || updateDate === 31){
                        dateAppendTxt = 'st';
                    } else if (updateDate === 2  || updateDate === 22) {
                        dateAppendTxt = 'nd';
                    } else if (updateDate === 3  || updateDate === 23) {
                        dateAppendTxt = 'rd';
                    } else {
                        dateAppendTxt = 'th';
                    }
                    // B-1516871: Pickup date formating.
                    let optTimeFormated = optTime.replace('AM', 'A.M.').replace('PM', 'P.M.')

                    let pickupDateTimeDisply = updateDay + ', ' + updateMonth + ' ' + updateDate + dateAppendTxt + ' '+ optTimeFormated;
                    if (window.e383157Toggle) {
                        fxoStorage.set("pickupDateTime", pickupDateTimeDisply);
                    } else {
                        localStorage.setItem("pickupDateTime", pickupDateTimeDisply);
                    }
                    //disply the changed pickup time and date in Shipping page
                    let earliestPickupDateTimeLabel = updateDay + ', ' + updateMonth + ' ' + updateDate + dateAppendTxt + "<br />" + optTimeFormated;
                    this.koEarliestPickupDateTime(earliestPickupDateTimeLabel);
                }
            },

            initPickUpModal: function () {
                let self = this;
                this.pickupTimeModal = $('#pickuptimeModal');
                this.modalOptions = {
                    type: 'popup',
                    title: 'Select a Pickup Time',
                    modalClass: 'preferred-pickup-time-popup',
                    buttons: [{
                        text: $.mage.__('CANCEL'),
                        class: 'pickup-time-clear fs-18 fedex-bold ls-1 lh-24 p-15'
                    },
                    {
                        text: $.mage.__('APPLY'),
                        class: 'pickup-time-apply fs-18 fedex-bold ls-1 lh-24 mb-8 p-15'
                    }]
                };
                this.pickupTimeModal.modal(this.modalOptions);

                this.pickupTimeModal.on('modalopened', function () {

                    //Setting to next 30days
                    let now = new Date();
                    now.setDate(now.getDate() + 29);

                    let earliestpickupDate;
                    if(self.koSelectedDate() === null) {
                        earliestpickupDate = self.isPromiseTimePickupOptionsToggle() ? self.koStandardDeliveryLocalTime() : self.koPickupDateHidden();
                    } else {
                        earliestpickupDate = self.koSelectedDate();
                    }
                    let [earliestPickupDay, earliestPickupTime] = earliestpickupDate.split('T');
                    earliestPickupDay = earliestPickupDay.split('-').join('/');
                    let [earliestPickupHour, earliestPickupMin, earliestPickupSec] = earliestPickupTime.split(':');
                    let startDate = new Date(earliestPickupDay);
                    let earliest = new Date(earliestPickupDay);
                    earliest.setHours(earliestPickupHour);
                    let earliestCompare = self.removeTimeFromDateFormat(earliest);

                    let weekDay = earliest.getDay();
                    let earliestDate = self.isPromiseTimePickupOptionsToggle() ? startDate.getDate() : earliest.getDate();
                    let earliestHour = self.isPromiseTimePickupOptionsToggle() && self.koSelectedPickupLaterDate() ? earliest.getHours().toString() : earliestPickupHour;
                    let todayFormat = new Date();
                    let todayFormatCompare = self.removeTimeFromDateFormat(todayFormat);

                    let boolean = (earliestCompare.getTime() === todayFormatCompare.getTime()) ? true : false;
                    let isFutureEarliest = (earliestCompare.getTime() > todayFormatCompare.getTime()) ? true : false;

                    self.koEarliestHour(earliestHour);
                    self.koEarliestDate(earliestDate);
                    self.koEarliestDayFormat(earliestCompare.getTime());
                    //To find Selected Time Range
                    self.getSelectedTimeRange(weekDay, boolean, isFutureEarliest);

                    //To find Time Slots based on Range
                    self.getTimeSlotsByRange();

                    self.setPickupTimeOnOpen();

                    //Finding Holidays from API response
                    let closedDays = [];
                    for (const timeRange of self.koPickupTimeAvailability()) {
                        let counter = timeRange,
                        schedule = counter.schedule;
                        if(schedule === "Closed") {
                            switch (counter.day) {
                                case 'Sun':
                                    closedDays.push(0);
                                    break;
                                case 'Mon':
                                    closedDays.push(1);
                                    break;
                                case 'Tue':
                                    closedDays.push(2);
                                    break;
                                case 'Wed':
                                    closedDays.push(3);
                                    break;
                                case 'Thu':
                                    closedDays.push(4);
                                    break;
                                case 'Fri':
                                    closedDays.push(5);
                                    break;
                                case  'Sat':
                                    closedDays.push(6);
                            }
                        }
                    }

                    self.pickupDatePicker.datepicker( "option", "beforeShowDay", function (date){ return noActiveDay(date, closedDays); } );
                    if (self.isPromiseTimePickupOptionsToggle()) {
                        self.pickupDatePicker.datepicker( "option", "minDate", startDate );
                    } else {
                        self.pickupDatePicker.datepicker( "option", "minDate", earliest );
                    }
                    self.pickupDatePicker.datepicker( "option", "maxDate", now );
                    self.pickupDatePicker.datepicker( "setDate", earliest );

                    self.tabFocusPrevNextIcons();

                    function noActiveDay(date, closedDays){
                        let day = date.getDay();
                        return [(jQuery.inArray(day, closedDays) == -1), ''];
                    };

                });

                this.pickupTimeModal.on('modalclosed', function () {
                    if (self.isPromiseTimePickupOptionsToggle() && !self.koSelectedPickupLaterDate()) {
                        let defaultDate = self.koStandardPickupTime();
                        const regex = /, (\d{1,2}:\d{2})([ap]m)/i;
                        let formattedDefaultDate = defaultDate.replace(regex, ' at $1 $2');
                        formattedDefaultDate = formattedDefaultDate.replace(/([ap]m)/i, match => match.toUpperCase());
                        let selectedDefaultDate = new Date(self.koPickupDateHidden());
    
                        self.koSelectedPickupLaterDate(selectedDefaultDate);
                        self.koFormattedPickupLaterDate(formattedDefaultDate);
                        if(window.e383157Toggle){
                            fxoStorage.set("updatedChangedPickupDateTime");
                            fxoStorage.set("pickupDateTime", defaultDate);
                            fxoStorage.set('pickupDateTimeForApi', self.koPickupDateHidden());
                        }else{
                            localStorage.removeItem("updatedChangedPickupDateTime");
                            localStorage.setItem("pickupDateTime", defaultDate);
                            localStorage.setItem("pickupDateTimeForApi", self.koPickupDateHidden());
                        }
                        if (self.currentPickupLaterRadio()) {
                            self.currentPickupLaterRadio().siblings('#pickup_later_date').show();
                        }
                    }
                });

                $('.pickup-time-clear').on('click', function(event) {
                    if (!self.isPromiseTimePickupOptionsToggle()) {
                        let prevDefaultDateTime = self.koPickupDate();
                        let prevDefaultDateTimeHidden = self.koPickupDateHidden();

                        self.koEarliestPickupDateTime(prevDefaultDateTime);
                        if(window.e383157Toggle){
                            fxoStorage.set("updatedChangedPickupDateTime");
                            fxoStorage.set("pickupDateTime", prevDefaultDateTime);
                            fxoStorage.set('pickupDateTimeForApi', prevDefaultDateTimeHidden);
                        }else{
                            localStorage.removeItem("updatedChangedPickupDateTime");
                            localStorage.setItem("pickupDateTime", prevDefaultDateTime);
                            localStorage.setItem("pickupDateTimeForApi", prevDefaultDateTimeHidden);
                        }
                    }
                });

                $('.pickup-time-apply').on('click', function(event) {
                    self.updatePickupDateTimeFormat();
                });

                this.pickupDatePicker = $('#prefdatetimepicker');
                this.pickupDatePicker.datepicker({
                    hideIfNoPrevNext: true,
                    dateFormat: 'yy/mm/dd',
                    onSelect:function(selectedDate) {
                        return self.selectedTimeSlotDropdown(selectedDate);
                    }
                });

                $(document).on('keypress', '#prefdatetimepicker .ui-datepicker-prev, #prefdatetimepicker .ui-datepicker-next', function (e) {
                    if (e.which === 13) {
                        $(this).trigger("click");
                    }
                });

                $(document).on('click','.ui-datepicker-next, .ui-datepicker-prev',function() {
                    self.tabFocusPrevNextIcons();
                });

            },

            showPickupTimeModal: function () {
                this.initPickUpModal();
                this.pickupTimeModal.modal(this.modalOptions).modal("openModal");
            },

            tabFocusPrevNextIcons: function () {
                // add href to prev/next anchors so that they can receive TAB focus
                $('#prefdatetimepicker .ui-datepicker-prev, #prefdatetimepicker .ui-datepicker-next').attr("tabindex", "0");
            },

            isB2bOrderApproval: function() {
                if (togglesAndSettings.xmenOrderApprovalB2b) {
                    $('.pref-pickup-container .pref-pickup-btn').hide();
                }
            },
        });
    };
});
