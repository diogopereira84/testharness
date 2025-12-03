/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'ko',
    'uiComponent',
    'Fedex_Delivery/js/model/toggles-and-settings',
    'mage/translate',
    'Fedex_Delivery/js/model/pickup-data',
    'Fedex_Delivery/js/view/google-places-api',
    'Fedex_Delivery/js/model/pickup-service',
    'Fedex_Delivery/js/model/google-maps-service'
], function ($, ko, Component, togglesAndSettings, $t, pickupData, googlePlacesApi, pickupService, googleMapsService) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Fedex_Delivery/pickup/pickup-search',

            // Shared state
            searchRadius: pickupData.searchRadius,
            searchQuery: pickupData.searchQuery,
            sugestedAddresses: ko.observableArray([]),
            infoUrl: ko.observable(togglesAndSettings.infoUrl),
            isEproUploadtoQuoteToggle: ko.observable(togglesAndSettings.isEproUploadtoQuoteToggle),

            // Local UI state
            showRadiusDropdown: ko.observable(false)
        },

        initialize: function () {
            this._super();
            this._bindOutsideClick();

            if (window?.checkoutConfig?.is_restricted_store_production_location_option) {
                return this;
            }

            this._initPrepopulated();
            return this;
        },

        _initPrepopulated: function () {
            // Wait for Google Maps to be ready before running pre-populated flow
            googleMapsService.waitForGoogleMapsToLoad().then(() => {
                pickupData.prePopulatedPickupData.subscribe((pre) => {
                    if (pre) {
                        this.applyPreselectedPickupAndSearch(pre.address, pre.zipCode, pre.locationId);
                    }
                }, this);

                const current = pickupData.prePopulatedPickupData();
                if (current) {
                    this.applyPreselectedPickupAndSearch(current.address, current.zipCode, current.locationId);
                }
            });
        },

        _bindOutsideClick: function () {
            this._outsideClickHandler = (event) => {
                var $results = $('#results');
                var $input = $('#zipcodePickup');
                var clickedInsideResults = $results.is(event.target) || $results.has(event.target).length > 0;
                var clickedOnInput = $input.is(event.target);
                if (!clickedInsideResults && !clickedOnInput) {
                    this.sugestedAddresses([]);
                }
            };
            $(document).on('click', this._outsideClickHandler);
        },

        dispose: function () {
            if (this._outsideClickHandler) {
                $(document).off('click', this._outsideClickHandler);
            }
            this._super();
        },

        toggleRadiusDropdown: function () {
            this.showRadiusDropdown(!this.showRadiusDropdown());
        },

        selectRadius: function (radius) {
            this.searchRadius(radius);
            this.showRadiusDropdown(false);
        },

        applyPreselectedPickupAndSearch: function (address, zipCode, locationId) {
            pickupData.preSelectedPickupLocationId(locationId || false);
            this.searchQuery(address || zipCode);
            this.onClickSearchLocation();
        },

        handleSearchInputEvent: function (_data, event) {
            if (event.keyCode === 13) {
                this.onClickSearchLocation();
                return true;
            }

            if (this.searchQuery().length >= 2) {
                this.debouncedAutocomplete();
            }

            return true;
        },

        debouncedAutocomplete: _.debounce(function () {
            googlePlacesApi.loadAutocomplete(this.searchQuery())
                .then(predictions => {
                    this.sugestedAddresses(predictions || []);
                })
                .catch(() => {
                    this.sugestedAddresses([]);
                });
        }, 300),

        handleAddressSuggestionSelect: function (address) {
            pickupData.clearSelection();
            this.searchQuery(address.description);
            this.sugestedAddresses([]);

            googlePlacesApi.handleGooglePlacesGeocoding(address.description)
                .then(geoData => {
                    this.applyGeocodedAddressAndSearch(geoData);
                })
                .catch(() => {
                    // Swallow errors; upstream UI already handles generic errors
                    this.sugestedAddresses([]);
                });
        },

        applyGeocodedAddressAndSearch: function (geoData) {
            const addressComponents = geoData.address_components;
            let city = '', state = '', zipcode = '';

            addressComponents.forEach(component => {
                const types = component.types;
                if (types.includes('locality')) {
                    city = component.long_name;
                } else if (types.includes('administrative_area_level_1')) {
                    state = component.short_name;
                } else if (types.includes('postal_code')) {
                    zipcode = component.long_name;
                }
            });

            if (zipcode) {
                this.searchQuery(zipcode);
                this.searchPickupLocations(city, state, zipcode, geoData.lat, geoData.lng);
            } else if (city && state) {
                this.searchPickupLocations(city, state, null, geoData.lat, geoData.lng);
            }
        },

        onClickSearchLocation: function () {
            pickupData.clearSelection();
            const query = this.searchQuery();
            if (!query) {
                return;
            }

            googlePlacesApi.handleGooglePlacesGeocoding(query)
                .then(addressComponents => {
                    this.applyGeocodedAddressAndSearch(addressComponents);
                })
                .catch(() => {
                });
        },

        searchPickupLocations: function (city, state, pinCode, lat, lng) {
            pickupService.getPickupAddress(
                city,
                state,
                pinCode,
                this.searchRadius(),
                lat,
                lng
            ).then(data => {
                const locations = (data && data.locations) || [];
                pickupData.pickupSearchResults(locations);

                if (locations.length > 0 && typeof google !== 'undefined' && google.maps) {
                    googleMapsService.initMap(locations, 'googleMap');
                    // If there is a pre-selected pickup location, this should be marked as selected
                    const preId = pickupData.preSelectedPickupLocationId();
                    if (preId) {
                        const match = locations.find(p => p && p.location && p.location.id === preId);
                        if (match) {
                            pickupData.applySelectedPickup(match);
                            pickupData.preSelectedPickupLocationId(false);
                            googleMapsService.highlightPickupLocation(match, locations);
                        }
                    }
                }
            }).catch(() => {
                pickupData.pickupSearchResults([]);
                pickupData.selectedPickupIsHCO(false);
            });
        }
    });
});
