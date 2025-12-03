/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'ko',
    'uiComponent',
    'Fedex_Delivery/js/model/pickup-data',
    'Fedex_Delivery/js/model/pickup-service',
    'Fedex_Delivery/js/model/google-maps-service',
    'underscore',
    'mage/translate',
    'Fedex_Delivery/js/model/toggles-and-settings',
    'fedex/storage'
], function ($, ko, Component, pickupData, pickupService, googleMapsService, _, $t, togglesAndSettings, fxoStorage) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Fedex_Delivery/pickup/pickup-selector',
            selectedLocationIsHCO: ko.observable(false),
            hcoInfoIcon: ko.observable(togglesAndSettings.hcoinfoIcon),
            mapMarkerIcon: ko.observable(togglesAndSettings.mapMarkerIcon),
            infoUrl: ko.observable(togglesAndSettings.infoUrl),
            koEarliestPickupLabelIndex: ko.observable(0),
            koClosestPickupLabelIndex: ko.observable(0),
            koAssignedPickupLabelIndex: ko.observable(false),

            // Add this line
            isEproUploadtoQuoteToggle: ko.observable(togglesAndSettings.isEproUploadtoQuoteToggle),

            // TODO: Enable this in default shipping flow.
            // For now, its disabled in U2Q flow.
            isPromiseTimePickupOptionsToggle: ko.observable(false),
            isPriorityPrintLimitedTimeToggle: ko.observable(false),

            showMap: ko.observable(true),
            enableCenterDetails: ko.observable(false),
            center: ko.observable({}),
            checkoutPickupFormTitle: ko.observable(togglesAndSettings.isEproUploadtoQuoteToggle ? $t('Store Selection') : $t('Pick up location')),
        },

        /**
         * Component initialization.
         * - Binds shared observables from `pickupData`.
         * - Sets up subscriptions and computed lists (e.g., visible items).
         * - Registers a document click handler to close suggestions on outside click.
         *
         * @returns {this}
         */
        initialize: function () {
            this._super();

            // Bind to shared state
            this.selectedPickupLocation = pickupData.selectedPickupLocation;
            this.selectedPickupTime = pickupData.selectedPickupTime;
            this.pickupSearchResults = pickupData.pickupSearchResults;
            this.searchRadius = pickupData.searchRadius;
            this.searchQuery = pickupData.searchQuery;
            this.showPickupContent = pickupData.showPickupContent;
            this.pickupTimeAvailability = pickupData.pickupTimeAvailability;
            this.selectedPickupId = pickupData.selectedPickupId;
            this.selectedPickupDate = pickupData.selectedPickupDate;
            this.selectedPickupDateHidden = pickupData.selectedPickupDateHidden;
            this.selectedPickupName = pickupData.selectedPickupName;
            this.selectedPickupAddress = pickupData.selectedPickupAddress;
            this.koEarliestPickupDateTime = pickupData.earliestPickupDateTime;
            this.koFormattedPickupLaterDate = pickupData.selectedPickupLaterDate;

            this.selectedPickupIsHCO = pickupData.selectedPickupIsHCO;

            this.initComponentState();

            this.pickupSearchResults.subscribe(function (results) {
                var indices = pickupService.calculateLabelIndices(results, this.isPromiseTimePickupOptionsToggle());
                this.koEarliestPickupLabelIndex(indices.earliestIndex);
                this.koClosestPickupLabelIndex(indices.closestIndex);

            }, this);

            this.visibleItemsCount = ko.observable(this.isPromiseTimePickupOptionsToggle() ? 3 : 10);
            this.visiblePickupLocations = ko.computed(() => {
                return this.pickupSearchResults().slice(0, this.visibleItemsCount());
            }, this)

            return this;
        },

        /**
         * Initializes component state and triggers pre-populated data check.
         *
         * @returns {void}
         */
        initComponentState: function () {
            this.pickupSearchResults([]);
            this.selectedPickupLocation(null);
            this.selectedPickupTime(null);
            this.enableCenterDetails(false);
        },

        /**
         * Selects a pickup location and updates the pickup data.
         * Save selected pickup location in storage.
         * @param {Object} pickup - The pickup location to select.
         */
        selectPickupLocation: function (pickup) {
            if (!pickup?.location) {
                return;
            }
            pickupData.applySelectedPickup(pickup);

            this.pickupTimeAvailability([]);
            pickupService.getCenterDetails(pickup.location.id).then(centerDetails => {
                this.pickupTimeAvailability(centerDetails.hoursOfOperation || []);
            });

            googleMapsService.highlightPickupLocation(pickup, this.pickupSearchResults());

            this.persistSelectedPickupInStorage();
        },

        persistSelectedPickupInStorage: function () {
            if (window.e383157Toggle) {
                fxoStorage.set("pickupDateTime", pickupData.selectedPickupDate());
                fxoStorage.set('pickupDateTimeForApi', pickupData.selectedPickupDateHidden());
            } else {
                localStorage.setItem("pickupDateTime", pickupData.selectedPickupDate());
                localStorage.setItem('pickupDateTimeForApi', pickupData.selectedPickupDateHidden());
            }
        },

        selectPickupTime: function (time) {
            this.selectedPickupTime(time);
        },

        showCenterDetails: function (location) {
            this.enableCenterDetails(false);
            pickupService.getCenterDetails(location.id).then(centerDetails => {
                this.center(centerDetails || {});
                this.enableCenterDetails(true);
            });
        },

        hideCenterDetails: function () {
            this.enableCenterDetails(true);
            this.center({});
        },

        shouldShowLoadMoreButton: function () {
            return this.visibleItemsCount() < this.pickupSearchResults().length;
        },

        loadMorePickupLocations: function () {
            this.visibleItemsCount(this.visibleItemsCount() + (this.isPromiseTimePickupOptionsToggle() ? 3 : 10));
        }
    });
});
