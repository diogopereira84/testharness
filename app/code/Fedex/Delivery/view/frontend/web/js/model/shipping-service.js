/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Ui/js/model/messageList',
    'fedex/storage'
], function (ko, checkoutDataResolver, errorProcessor,messageList,fxoStorage) {
    'use strict';

    let shippingRates = ko.observableArray([]);

    let shippingRateGroups = ko.observableArray([]);

    /**
     * WeekDays array used for Date to string formatting
     */
    let weekDays = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];

    /**
     * Moths array used to Date to string formatting
     */
    let months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    /**
     * Formats the calculated dollar amount for display.
     * @param dollarAmount - dollar amount to format for display.
     */
    function _displayDollarAmount(dollarAmount) {
        dollarAmount = dollarAmount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return '$' + dollarAmount;
    }

    return {
        isLoading: ko.observable(false),

        /**
         * Set shipping rates
         *
         * @param {*} ratesData
         */
        setShippingRates: function (ratesData) {
            if(!ratesData.length){
                messageList.addErrorMessage({message :'Unable to retrieve shipping options and prices. Refresh the page or try again at a later.'});


            }
            shippingRates(ratesData);
            checkoutDataResolver.resolveShippingRates(ratesData);
        },

        /**
         * Get shipping rates
         *
         * @returns {*}
         */
        getShippingRates: function () {
            return shippingRates;
        },

        /**
         * Gets line price from delivery line with line type of 'SHIPPING'.
         * @param response - rates api response
         */
        getShippingLinePrice(response) {
            let shippingDetail = null;

            if (!response ||
                !response.rateQuoteDetails) {
                return 0;
            }

            response.rateDetails = response.rateQuoteDetails;
                response.rateDetails.forEach((rateDetail) => {
                    rateDetail.deliveryLines.forEach((deliveryLine) => {
                        if (deliveryLine.lineType === 'SHIPPING') {
                            shippingDetail = deliveryLine.linePrice;
                        }
                    });
                });

            return shippingDetail;

        },

        initializeGroundPromoMessage:function($){
          shippingRates.subscribe(function (rates) {
            shippingRateGroups([]);
            _.each(rates, function (rate) {
                $(".covid-19-message-promo-box").show();
                let carrierTitle = rate['carrier_title'];
                if (shippingRateGroups.indexOf(carrierTitle) === -1) {
                    if(carrierTitle==="FedEx Ground US"){
                        if(rate['amount']===9.99){
                            $(".opc-free-ground-promo-box").show();
                        }else{
                            $(".opc-free-ground-promo-box").hide();
                        }
                    }
                    shippingRateGroups.push(carrierTitle);
                }
            });
          });
        },

        /**
         * Calculates and formats category dollar amount.
         * @param response - rates api response
         */
        calculateDollarAmount(response, isRateApi) {
            return function(category) {
                if (!window.checkoutConfig.is_epro && !isRateApi) {
                  response.rateDetails = response.rateQuoteDetails;
                }
                let dollarAmount;
                dollarAmount = response.rateDetails.reduce(
                  (acc, rateDetail) => acc + parseFloat(typeof rateDetail[category] == 'string' ?
                    rateDetail[category].replace('(','').replace(')','').replace('$', '').replace(',', '') : rateDetail[category]
                  ), 0);

                dollarAmount = Math.round(dollarAmount * 100) / 100;
                return dollarAmount === 0 ? '$0.00' : _displayDollarAmount(dollarAmount);
            }
        },

        /**
         * Fromated date to string as per it needs to be displayed on UI
         * @param {*} date
         * @returns string
         */
         getDates(date){
          let estimated=new Date(date);
          let minutes=parseInt(estimated.getMinutes())===0?"00":estimated.getMinutes();
          let am=estimated.getHours()>=12?" p.m.":" a.m.";
          let fromattedDate=weekDays[estimated.getDay()]
          +", "+months[estimated.getMonth()]
          +" "+estimated.getDate()+
          "th, "+
          estimated.getFullYear()+
          " at "+(estimated.getHours()>12?estimated.getHours()-12:estimated.getHours())+":"+minutes+am;

          if(window.e383157Toggle){
              fxoStorage.set("pickupDateTime",fromattedDate);
          }else{
              localStorage.setItem('pickupDateTime', fromattedDate);
          }
          return fromattedDate;
        },

        /**
         * Converts AM or PM to A.M. to P.M.
         * @param {*} value
         * @returns string
         */
        transform:function(value){
          let toReturn=value;
          if((value.indexOf('AM')>-1)||(value.indexOf('PM')>-1)){
              toReturn=value.replace('AM',"A.M.").replace('PM',"P.M.");
          }
          return toReturn;
        },

        /**
         * Sorts the week array for center details UI
         * @param {*} week
         * @returns array
         */
        sortWeek:function(week){
            const sorter = {
              'Mon': 1,
              'Tue': 2,
              'Wed': 3,
              'Thu': 4,
              'Fri': 5,
              'Sat': 6,
              'Sun': 7
            };
            week.sort((a, b) => {
              return sorter[a.day] - sorter[b.day];
            });
            return week;
          },

        /**
         * Return hours of operation in a week for center details
         * @param {*} hoursOfOperation
         * @returns array
         */
        getHoursOfFirstWeek(hoursOfOperation){
            let toReturn=[];
            if (hoursOfOperation && hoursOfOperation.length >= 7) {
                hoursOfOperation = hoursOfOperation.slice(0, 7);
                toReturn = hoursOfOperation.map((operation) => {
                  let range = '';
                  if (operation.openTime && operation.closeTime && operation.schedule === 'Open') {
                    range = this.transform(operation.openTime)
                      + ' - ' + this.transform(operation.closeTime);
                  } else if (operation.schedule === 'Open 24hrs') {
                    range = 'Open 24hrs';
                  } else if (operation.schedule === 'Closed') {
                    range = 'Closed';
                  }
                  return {
                    day: operation.day.substring(0, 3).charAt(0) + operation.day.substring(1, 3).toLowerCase(),
                    schedule: operation.schedule,
                    range
                  };
                });
              }
              return this.sortWeek(toReturn);
        },

        distance(lat1, lon1, lat2, lon2, unit){
            let radlat1 = Math.PI * parseFloat(lat1) / 180;
            let radlat2 = Math.PI * parseFloat(lat2) / 180;
            let theta = parseFloat(lon1) - parseFloat(lon2);
            let radtheta = Math.PI * theta / 180;
            let dist = Math.sin(radlat1) * Math.sin(radlat2) + Math.cos(radlat1) * Math.cos(radlat2) * Math.cos(radtheta);
            if (dist > 1) {
              dist = 1;
            }
            dist = Math.acos(dist);
            dist = dist * 180 / Math.PI;
            dist = dist * 60 * 1.1515;
            if (unit === 'K') {
              dist = dist * 1.609344;
            }
            if (unit === 'N') {
              dist = dist * 0.8684;
            }
            return dist;
          },
    };
});
