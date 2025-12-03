define([
    'jquery'
], function ($) {
    'use strict';

    var explorersD174773Fix = false;

    var isCheckoutConfigAvaliable = typeof (window.checkoutConfig) !== 'undefined' && window.checkoutConfig !== null ? true : false;

    if (isCheckoutConfigAvaliable) {
        explorersD174773Fix = typeof (window.checkoutConfig.explorers_d_174773_fix) != 'undefined' && window.checkoutConfig.explorers_d_174773_fix != null ? window.checkoutConfig.explorers_d_174773_fix : false;
    }

    return {
        // Load Auto Complete Web Service - New Approach
        loadAutocompleteService: function (input, scope, event = null) {
            var self = this;
            var parent = scope;
            var autocomplete = new google.maps.places.AutocompleteService();
            var htmlDiv = this.resetResults();
            autocomplete.getPredictions({input: input, componentRestrictions: {country: 'us'}},
                predictions => {
                    if (!predictions || predictions.length === 0) {
                        return;
                    }
                    predictions.forEach(function (component) {
                        let hightlightResult = self.hightlightResult(input, component.description);
                        var div = document.createElement('div');
                        $(div).addClass('result-wrapper');
                        div.innerHTML += "<div class='result'>" + hightlightResult + "</div>";
                        htmlDiv.appendChild(div);
                        if (explorersD174773Fix && event !== null && event.type == 'keypress') {
                            self.resetResults();
                        }
                        // Bind a click event
                        div.onclick = function () {
                            let geocoder = new google.maps.Geocoder();
                            geocoder.geocode({'address': component.description}, function (results, status) {
                                if (status == google.maps.GeocoderStatus.OK) {
                                    let latitude = results[0].geometry.location.lat();
                                    let longitude = results[0].geometry.location.lng();
                                    let address = results[0].address_components;
                                    self.setAddressInMap(address, latitude, longitude, parent);
                                    self.resetResults();
                                }
                            });
                        }
                    });
                });
        },

        // Refactored loadAutocompleteService
        // The scope of the function is to get the list of predictions from the Google Places API
        // We will not use it to render the results
        loadAutocomplete(input) {
            return new Promise((resolve, reject) => {
                let autocompleteService = new google.maps.places.AutocompleteService();
                autocompleteService.getPredictions({input: input, componentRestrictions: {country: 'us'}},
                    predictions => {
                        if (!predictions || predictions.length === 0) {
                            reject(predictions);
                        }
                        resolve(predictions);
                    });
            })
        },

        // Load Auto Complete Service For Shipping Address Form - Geocoder
        loadAutocompleteServiceShippingAddress: function (input) {
            var self = this;
            var autocomplete = new google.maps.places.AutocompleteService();
            var htmlDiv = this.resetGeoCoderResults();
            autocomplete.getPredictions({input: input, componentRestrictions: {country: 'us'}},
                predictions => {
                    if (!predictions || predictions.length === 0) {
                        return;
                    }
                    predictions.forEach(function (component) {
                        let hightlightResult = self.hightlightResult(input, component.description);
                        var div = document.createElement('div');
                        $(div).addClass('result-wrapper-shipping-address-suggestions');
                        div.innerHTML += "<div class='result'>" + hightlightResult + "</div>";
                        htmlDiv.appendChild(div);
                        // Bind a click event
                        div.onclick = function () {
                            let geocoder = new google.maps.Geocoder();
                            geocoder.geocode({'address': component.description}, function (results, status) {
                                if (status == google.maps.GeocoderStatus.OK) {
                                    let address = results[0].address_components;
                                    self.setShippingAddressOnAutoComplete(address);
                                    self.resetGeoCoderResults();
                                }
                            });
                        }
                    });
                });
        },

        // Load Auto Complete Service For Shipping Address Form - Geocoder
        loadAutocompleteServiceBillingAddress: function (input) {
            var self = this;
            var autocomplete = new google.maps.places.AutocompleteService();
            var htmlDiv = this.resetBillingResults();
            autocomplete.getPredictions({input: input, componentRestrictions: {country: 'us'}},
                predictions => {
                    if (!predictions || predictions.length === 0) {
                        return;
                    }
                    predictions.forEach(function (component) {
                        let hightlightResult = self.hightlightResult(input, component.description);
                        var div = document.createElement('div');
                        $(div).addClass('result-wrapper');
                        div.innerHTML += "<div class='result'>" + hightlightResult + "</div>";                        
                        htmlDiv.appendChild(div);
                        // Bind a click event
                        div.onclick = function () {
                            let geocoder = new google.maps.Geocoder();
                            geocoder.geocode({'address': component.description}, function (results, status) {
                                if (status == google.maps.GeocoderStatus.OK) {
                                    let address = results[0].address_components;
                                    self.setAddressInMapForAutoCompleteBillingForm(address);
                                    self.resetBillingResults();
                                }
                            });
                        }
                    });
                });
        },


        // Load Auto Complete Service For Shipping Address Form - Geocoder
        loadAutocompleteServicePersonalAddress: function (input) {
            var self = this;
            var autocomplete = new google.maps.places.AutocompleteService();
            var htmlDiv = this.resetGeoCoderResults();
            autocomplete.getPredictions({input: input, componentRestrictions: {country: 'us'}},
                predictions => {
                    if (!predictions || predictions.length === 0) {
                        return;
                    }
                    predictions.forEach(function (component) {
                        let hightlightResult = self.hightlightResult(input, component.description);
                        var div = document.createElement('div');
                        $(div).addClass('result-wrapper-shipping-address-suggestions');
                        div.innerHTML += "<div id='address' class='result'>" + hightlightResult + "</div>";
                        htmlDiv.appendChild(div);
                        // Bind a click event
                        div.onclick = function () {
                            let geocoder = new google.maps.Geocoder();
                            geocoder.geocode({'address': component.description}, function (results, status) {
                                if (status == google.maps.GeocoderStatus.OK) {
                                    let address = results[0].address_components;
                                    self.setAddressInMapForAutoCompletePersonalAddress(address);
                                    self.resetGeoCoderResults();
                                }
                            });
                        }
                    });
                });
        },

        hightlightResult: function (input, result) {
            let hightlightedResult = result;

            // Making all words from input to be capitalized for a better match
            input = input.replace(/\b\w/g, function(match) {
                return match.toUpperCase();
            });

            // searching and making it bold if found
            if ( result.indexOf(input) !== -1 ) {
                hightlightedResult = result.replace(input, "<b>" + input + "</b>");
            }

            return hightlightedResult;
        },

        // Reset Auto Complete Web Service Results container
        resetResults: function () {
            let htmlDiv = document.getElementById('geocoder-results');
            if (htmlDiv !== null) {
                htmlDiv.innerHTML = '';
                return htmlDiv;
            }
        },

        // Reset Auto Complete Web Service Results container
        resetBillingResults: function () {
            let htmlBillingDiv = document.getElementById('geocoder-results-billing');
            if (htmlBillingDiv !== null) {
                htmlBillingDiv.innerHTML = '';
                return htmlBillingDiv;
            }
        },

        // Reset Auto Complete Web Service Results container for Shipping
        resetGeoCoderResults: function () {
            let htmlDiv = document.getElementById('geocoder-results-shipping');
            htmlDiv.innerHTML = '';
            return htmlDiv;
        },

        // Load Auto Complete Web Service - Old Approach
        loadAutocompleteWidget: function (input, scope) {
            var self = this;
            var parent = scope;
            let autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.setComponentRestrictions({
                country: ["us"]
            });

            google.maps.event.addListener(autocomplete, 'place_changed', function () {
                let place = autocomplete.getPlace();
                if (typeof (place) != 'undefined' && typeof (place) == 'object' && Object.keys(place).length > 1) {
                    let latitude = place.geometry.location.lat();
                    let longitude = place.geometry.location.lng();

                    if (latitude != "" && longitude != "") {
                        $("#search-pickup").attr("disabled", false);
                    }
                    let address = place.address_components;

                    self.setAddressInMap(address, latitude, longitude, parent);
                }
            });
        },

        // Set address and show google maps
        setAddressInMap: function (address, lat, lng, parent) {
            let city = null,
                stateCode = null,
                pinCode = null;

            address.forEach(function (selected) {
                let types = selected.types;
                if (types.indexOf('locality') > -1) {
                    city = selected.long_name;
                }
                if (types.indexOf('administrative_area_level_1') > -1) {
                    stateCode = selected.short_name;
                }
                if (types.indexOf('postal_code') > -1) {
                    pinCode = selected.long_name;
                    $(".zipcodePickup").val(pinCode);
                }
            });
            parent.getPickupAddress(city, stateCode, pinCode, lat, lng);
        },

    	// Set Shipping address with google suggestion autocomplete
        setShippingAddressOnAutoComplete: function (address) {
            let city = null,
                stateCode = null,
                pinCode = null,
                streetNumber = '',
                route = '';
            address.forEach(function (selected) {
                let types = selected.types;
                if (types.indexOf('locality') > -1) {
                    city = selected.long_name;
                }
                if (types.indexOf('administrative_area_level_1') > -1) {
                    stateCode = selected.short_name;
                    let regionSelect = document.querySelector('select[name="region_id"]');
                    for (let option of regionSelect.options) {
                        if (option.getAttribute('data-title') === stateCode) {
                            option.selected = true;
                            break;
                        }
                    }
                }
                if (types.indexOf('postal_code') > -1) {
                    pinCode = selected.long_name;
                }
                if (types.indexOf('street_number') > -1) {
                    streetNumber = selected.long_name;
                }
                if (types.indexOf('route') > -1) {
                    route = selected.long_name;
                }
            });

            if (streetNumber || route) {
                document.querySelector('div[name="shippingAddress.street.0"]').getElementsByTagName('input')[0].value = `${streetNumber} ${route}`.trim();
                $('input[name="street[0]"]').trigger("change");
            }

            let inputCity = document.querySelector('input[name="city"]');
            inputCity.value = city;
            $('input[name="city"]').trigger("change");
            let inputPincode = document.querySelector('input[name="postcode"]');
            inputPincode.value = pinCode;
            $('input[name="postcode"]').trigger("change");
        },

        // Set Billing address with google suggestion autocomplete
        setAddressInMapForAutoCompleteBillingForm: function(address) {
            let streetNumber = '';
            let route = '';
        
            address.forEach(function(selected) {
                let types = selected.types;
                let longName = selected.long_name;
                let shortName = selected.short_name;
                let stateCode = null;
                if (types.includes('locality')) {
                    document.querySelector('#add-city').value = longName;
                }
                if (types.includes('administrative_area_level_1')) {
                    stateCode = shortName;
                    let stateSelect = document.querySelector('#add-state');
                    if (stateSelect) {
                        stateSelect.value = stateCode;
                        $("#add-state").val(stateSelect.value).change();
                    }
                }
                if (types.includes('postal_code')) {
                    document.querySelector('#add-zip').value = longName;
                }
                if (types.includes('street_number')) {
                    streetNumber = longName;
                }
                if (types.includes('route')) {
                    route = longName;
                }
            });
            if (streetNumber || route) {
                document.querySelector('#address-one').value = `${streetNumber} ${route}`.trim();
            }
        },

        // Set Billing address with google suggestion autocomplete
        setAddressInMapForAutoCompletePersonalAddress: function(address) {
            let streetNumber = '';
            let route = '';
        
            address.forEach(function(selected) {
                let types = selected.types;
                let longName = selected.long_name;
                let shortName = selected.short_name;
                let stateCode = null;
                if (types.includes('locality')) {
                    document.querySelector('#city').value = longName;
                }
                if (types.includes('administrative_area_level_1')) {
                    stateCode = shortName;
                    let stateSelect = document.querySelector('#state');
                    if (stateSelect) {
                        stateSelect.value = stateCode;
                        $("#state").val(stateSelect.value).change();
                    }
                }
                if (types.includes('postal_code')) {
                    document.querySelector('#zipCode').value = longName;
                }
                if (types.includes('street_number')) {
                    streetNumber = longName;
                }
                if (types.includes('route')) {
                    route = longName;
                }
            });
            if (streetNumber || route) {
                document.querySelector('#addressLine1').value = `${streetNumber} ${route}`.trim();
            }
        },

        /**
         * Handle Google Places API geocoding for address suggestions
         * @param {string} description - Address description from Google Places
         * @returns {Promise} - Promise resolving to address components, lat, and lng
         */
        handleGooglePlacesGeocoding: function(description) {
            return new Promise(function(resolve, reject) {
                if (!window.google || !window.google.maps) {
                    reject('Google Maps not loaded');
                    return;
                }
                
                let geocoder = new google.maps.Geocoder();
                geocoder.geocode({'address': description}, function(results, status) {
                    if (status === google.maps.GeocoderStatus.OK && results[0]) {
                        resolve({
                            address_components: results[0].address_components,
                            lat: results[0].geometry.location.lat(),
                            lng: results[0].geometry.location.lng()
                        });
                    } else {
                        reject('Geocoding failed: ' + status);
                    }
                });
            });
        },

        // Normalize Google address_components array into a simple object
        // Returns: { streetNumber, route, addressLine1, city, stateCode, postalCode }
        // TODO: Replace all other address components with this function
        normalizeAddressComponents: function (components) {
            const result = {
                streetNumber: '',
                route: '',
                addressLine1: '',
                city: '',
                stateCode: '',
                postalCode: ''
            };

            if (!Array.isArray(components)) {
                return result;
            }

            components.forEach(function (component) {
                if (!component || !Array.isArray(component.types)) {
                    return;
                }
                const types = component.types;
                if (types.indexOf('street_number') !== -1) {
                    result.streetNumber = component.long_name || '';
                }
                if (types.indexOf('route') !== -1) {
                    result.route = component.long_name || '';
                }
                if (types.indexOf('locality') !== -1) {
                    result.city = component.long_name || '';
                }
                if (types.indexOf('administrative_area_level_1') !== -1) {
                    result.stateCode = component.short_name || component.long_name || '';
                }
                if (types.indexOf('postal_code') !== -1) {
                    result.postalCode = component.long_name || '';
                }
            });

            const line1 = `${result.streetNumber} ${result.route}`.trim();
            result.addressLine1 = line1 || '';
            return result;
        }
    };
});
