/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(['ko', 'underscore'], function (ko, _) {
    'use strict';

    const DEFAULT_PICKUP_RADIUS = 10;
    
    return {
        selectedPickupLocation: ko.observable({}),
        selectedPickupTime: ko.observable(),
        pickupSearchResults: ko.observableArray([]),
        selectedPickupIsHCO: ko.observable(false),
        
        searchRadius: ko.observable(DEFAULT_PICKUP_RADIUS),
        searchQuery: ko.observable(''),
        
        showPickupContent: ko.observable(false),
        selectedPickupOption: ko.observable(),

        prePopulatedPickupData: ko.observable(null),
        preSelectedPickupLocationId: ko.observable(false),

        // Promise Time Pickup Options Feature
        // This feature is disabled for u2q flow.
        selectedPickupLaterDate: ko.observable(''),
        
        // Legacy pickup data
        // Required for backward compatibility
        selectedPickupId: ko.observable(''),
        selectedPickupDate: ko.observable(''),
        selectedPickupDateHidden: ko.observable(''),
        selectedPickupName: ko.observable(''),
        selectedPickupAddress: ko.observable(''),
        earliestPickupDateTime: ko.observable(''),
        pickupTimeAvailability: ko.observableArray([]),

        setPrePopulatedPickupData: function(address, zipCode, locationId) {
            this.prePopulatedPickupData({
                address: address,
                zipCode: zipCode,
                locationId: locationId
            });
        },
        
        clearSelection: function() {
            this.selectedPickupLocation(null);
            this.selectedPickupTime(null);
            this.selectedPickupOption(null);
        },

        /**
         * Extract normalized metadata from a pickup object.
         * @param {Object} pickup
         * @returns {{locationId: string, pickupDate: string, pickupDateHidden: string, pickupName: string, pickupAddress: string}}
         */
        extractPickupMeta: function(pickup) {
            const hasLocation = pickup && pickup.location;
            const locationId = hasLocation && pickup.location.id ? pickup.location.id.toString().substring(0, 4) : '';
            const pickupDate = pickup && pickup.date ? pickup.date : '';
            const pickupDateHidden = pickup && pickup.datehidden ? pickup.datehidden : '';
            const pickupName = hasLocation && pickup.location.name ? pickup.location.name : '';

            const address = hasLocation && pickup.location.address ? pickup.location.address : {};
            const street0 = address.streetLines && address.streetLines[0] ? address.streetLines[0] : '';
            const city = address.city || '';
            const state = address.stateOrProvinceCode || '';
            const postalCode = address.postalCode || '';
            const parts = [];
            if (street0) parts.push(street0);
            if (city) parts.push(city);
            if (state) parts.push(state);
            if (postalCode) parts.push(postalCode);
            const pickupAddress = parts.join(', ');

            return { locationId, pickupDate, pickupDateHidden, pickupName, pickupAddress };
        },

        /**
         * Apply selected pickup to shared observables.
         * @param {Object} pickup
         */
        applySelectedPickup: function(pickup) {
            if (!pickup?.location) {
                return;
            }

            const meta = this.extractPickupMeta(pickup);
            this.selectedPickupLocation(pickup.location);
            // Set HCO flag based on selected pickup location premium status
            this.selectedPickupIsHCO(!!pickup.location.premium);
            this.selectedPickupId(meta.locationId);
            this.selectedPickupDate(meta.pickupDate);
            this.selectedPickupDateHidden(meta.pickupDateHidden);
            this.selectedPickupName(meta.pickupName);
            this.selectedPickupAddress(meta.pickupAddress);
            this.earliestPickupDateTime(meta.pickupDate);
        }
    };
});
