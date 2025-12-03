/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'underscore',
    'mage/translate',
    'mage/url',
    'ajaxUtils',
    'Magento_Checkout/js/model/shipping-service',
    'Fedex_Delivery/js/model/pickup-errors'
], function ($, _, $t, urlBuilder, ajaxUtils, shippingService, pickupErrors) {
    'use strict';

    return {
        getPickupAddress: function(city, stateCode, zipCode, searchRadius, lat, lng) {
            return new Promise(function(resolve, reject) {

                let payload = {
                    radius: searchRadius,
                    isCalledForPickup: true
                }

                if (zipCode) {
                    payload.zipcode = zipCode;
                } else {
                    payload.city = city;
                    payload.stateCode = stateCode;
                }

                ajaxUtils.post(
                    urlBuilder.build('delivery/index/getpickup'),
                    {},
                    payload,
                    true,
                    'json',
                    function(response) {

                        if (response.errors && response.errors.length > 0) {
                            let error = response.errors[0];
                            
                            if (error.code === pickupErrors.ERROR_CODES.SESSION_EXPIRED) {
                                reject(new Error({
                                    code: pickupErrors.ERROR_CODES.SESSION_EXPIRED,
                                    message: pickupErrors.ERROR_MESSAGES.DEFAULT
                                }));
                                return;
                            }
                            
                            if (error.code === pickupErrors.ERROR_CODES.HOLD_DATE_INVALID ||
                                error.code === pickupErrors.ERROR_CODES.HOLD_DATE_INVALID_ALT) {
                                reject({
                                    code: pickupErrors.ERROR_CODES.HOLD_DATE_INVALID,
                                    message: pickupErrors.ERROR_MESSAGES.DEFAULT
                                });
                                return;
                            }
                            
                            if (response.noLocation) {
                                reject({
                                    code: pickupErrors.ERROR_CODES.NO_LOCATIONS,
                                    message: pickupErrors.ERROR_MESSAGES.NO_LOCATIONS,
                                    transactionId: response.transactionId
                                });
                                return;
                            }
                        }
                        
                        // Return empty array if no locations
                        if (Array.isArray(response) && response.length === 0) {
                            reject({
                                code: pickupErrors.ERROR_CODES.NO_LOCATIONS,
                                message: pickupErrors.ERROR_MESSAGES.NO_LOCATIONS,
                                transactionId: response.transactionId
                            });
                            return;
                        }
                        
                        // Map per-location derived fields in a single pass
                        if (response && Array.isArray(response)) {
                            const isPromiseTimeEnabled = !!(window.checkoutConfig && window.checkoutConfig.sgc_promise_time_pickup_options);
                            const PRIORITY_PRINT_PICKUP = 'Priority Print Pickup';
                            const STANDARD_PICKUP = 'Standard Pickup';

                            response.forEach(function (pickup) {
                                // Distance calculation when coordinates available and lat/lng provided
                                if (lat && lng && pickup && pickup.location && pickup.location.geoCode &&
                                    typeof pickup.location.geoCode.latitude !== 'undefined' &&
                                    typeof pickup.location.geoCode.longitude !== 'undefined') {
                                    const distance = shippingService.distance(
                                        lat,
                                        lng,
                                        pickup.location.geoCode.latitude,
                                        pickup.location.geoCode.longitude, 'M').toFixed(2);
                                    pickup.distance = lat ? distance.toString() + ' mi' : '';
                                }

                                if (pickup && pickup.estimatedDeliveryLocalTime) {
                                    shippingService.getDates(pickup.estimatedDeliveryLocalTime);
                                    pickup.date = pickup.estimatedDeliveryLocalTimeShow;
                                    pickup.datehidden = pickup.estimatedDeliveryLocalTime;
                                }

                                if (isPromiseTimeEnabled) {
                                    pickup.priorityPrintPickup = null;
                                    pickup.standardPriorityPickup = null;

                                    if (Array.isArray(pickup.availableOrderPriorities)) {
                                        for (let i = 0; i < pickup.availableOrderPriorities.length; i++) {
                                            const priority = pickup.availableOrderPriorities[i];
                                            if (priority && priority.orderPriorityText === PRIORITY_PRINT_PICKUP) {
                                                pickup.priorityPrintPickup = priority;
                                            }
                                            if (priority && priority.orderPriorityText === STANDARD_PICKUP) {
                                                pickup.standardPriorityPickup = priority;
                                            }
                                        }
                                    }
                                }
                            });
                        }
                        
                        resolve({ locations: response || [], noLocation: false });
                    }
                );
            });
        },

        getCenterDetails: function(locationId) {
            return new Promise(function(resolve, reject) {
                ajaxUtils.post(
                    urlBuilder.build('delivery/index/centerDetails'),
                    {},
                    { locationId: locationId },
                    true,
                    'json',
                    function(response) {
                        response.hoursOfOperation = shippingService.getHoursOfFirstWeek(response.hoursOfOperation);
                        resolve(response);
                    },
                    function() {
                        reject(new Error($t('Unable to get center details. Please try again.')));
                    }
                );
            });
        },
        /**
         * Calculate label indices for earliest and closest pickup locations
         * @param {Array} results - Array of pickup location results
         * @param {boolean} isPromiseTimeEnabled - Whether promise time feature is enabled
         * @returns {Object} Object containing earliestIndex and closestIndex
         */
        calculateLabelIndices: function(results, isPromiseTimeEnabled) {
            if (!results || !isPromiseTimeEnabled) {
                return {
                    earliestIndex: 0,
                    closestIndex: 0
                };
            }

            let earliestIndex = 0;
            let closestIndex = 0;
            let minDistance = Infinity;
            let earliestDate = null;

            results.forEach(function(location, index) {
                // Find closest location
                if (location.distance < minDistance) {
                    minDistance = location.distance;
                    closestIndex = index;
                }

                // Find earliest pickup location
                if (!earliestDate || location.estimatedDeliveryLocalTime < earliestDate) {
                    earliestDate = location.estimatedDeliveryLocalTime;
                    earliestIndex = index;
                }
            });

            return {
                earliestIndex: earliestIndex,
                closestIndex: closestIndex
            };
        },
    };
});
