/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Checkout/js/model/error-processor',
    'jquery',
    'fedex/storage',
    'uiRegistry',
], function (resourceUrlManager, quote, storage, shippingService, rateRegistry, errorProcessor, $,fxoStorage,registry) {
    'use strict';
    var maegeeks_pobox_validation = typeof window.checkoutConfig.maegeeks_pobox_validation != 'undefined' ? window.checkoutConfig.maegeeks_pobox_validation : false;
    
    /**
     * Synchronizes residence shipping attribute between data model and UI
     * @param {Object} payload - The payload containing address data
     * @return {void}
     */
    function synchronizeResidenceShippingAttribute(payload) {
        // Safely get custom attributes array or default to empty array
        var customAttributes = Array.isArray(payload?.address?.custom_attributes) 
            ? payload.address.custom_attributes 
            : [];
        
        // Find residence_shipping attribute if it exists
        const residenceAttr = customAttributes.find(
            attr => attr && attr.attribute_code === 'residence_shipping'
        );
        
        // Get current checkbox state from the DOM
        const isResidenceCheckboxChecked = $('input[name="custom_attributes[residence_shipping]"]').is(':checked');

        // Synchronize data model with UI state
        if (isResidenceCheckboxChecked) {
            // If checked and attribute missing or not set to 1, set/add it to 1
            if (!residenceAttr) {
                // Add the attribute
                customAttributes.push({
                    attribute_code: 'residence_shipping',
                    value: 1
                });
                payload.address.custom_attributes = customAttributes;
            } else if (residenceAttr.value !== 1) {
                // Update the attribute value to 1
                payload.address.custom_attributes = customAttributes.map(attr =>
                    (attr && attr.attribute_code === 'residence_shipping')
                        ? { ...attr, value: 1 }
                        : attr
                );
            }
        } else {
            // If unchecked and attribute exists and set to 1, set it to 0/false
            if (residenceAttr && residenceAttr.value === 1) {
                payload.address.custom_attributes = customAttributes.map(attr =>
                    (attr && attr.attribute_code === 'residence_shipping')
                        ? { ...attr, value: 0 }
                        : attr
                );
            }
        }
    }
    return {
        /**
         * Get shipping rates for specified address.
         * @param {Object} address
         */
        getRates: function (address) {
            var serviceUrl,
                payload,
                product_location = null,
                selectedOption;
            let nearestLocation,selectedProductionId;
            if(window.e383157Toggle){
                selectedOption = fxoStorage.get("product_location_option");
                nearestLocation = fxoStorage.get("pl_nearest_location");
                selectedProductionId = fxoStorage.get('selected_production_id');
            }else{
                selectedOption = localStorage.getItem("product_location_option");
                nearestLocation = localStorage.getItem("pl_nearest_location");
                selectedProductionId = localStorage.getItem('selected_production_id');
            }
            if (selectedProductionId != null && selectedOption == "choose_self") {
                product_location = selectedProductionId;
            } else if (nearestLocation != null && selectedOption == "choose_self") {
                product_location = nearestLocation;
            }

            if(!window.d196640_toggle) {
                shippingService.isLoading(true);
            }
            serviceUrl = resourceUrlManager.getUrlForEstimationShippingMethodsForNewAddress(quote);
            payload = {
                address: {
                    'street': address.street,
                    'city': address.city,
                    'region_id': address.regionId,
                    'region': address.region,
                    'country_id': address.countryId,
                    'postcode': address.postcode,
                    'email': address.email,
                    'customer_id': address.customerId,
                    'firstname': address.firstname,
                    'lastname': address.lastname,
                    'middlename': address.middlename,
                    'prefix': address.prefix,
                    'suffix': address.suffix,
                    'vat_id': address.vatId,
                    'company': address.company,
                    'telephone': address.telephone,
                    'fax': address.fax,
                    'custom_attributes': address.customAttributes,
                    'save_in_address_book': address.saveInAddressBook
                },
                productionLocation: product_location
            };

            if (window?.checkoutConfig?.tech_titans_d217174) {
                // Execute the synchronization
                synchronizeResidenceShippingAttribute(payload);
            }

            var streetValue = address.street ? address.street : $(".form-shipping-address input[name^='street[0]']").val();
            var cityValue = address.city ? address.city : $(".form-shipping-address input[name^='city']").val();
            var regionIdValue = address.regionId ? address.regionId : $(".form-shipping-address select[name^='region_id']").val();
            var postcodeValue = address.postcode ? address.postcode : $(".form-shipping-address input[name^='postcode']").val();

            payload.isPickup = window.checkoutConfig.both && localStorage.chosenDeliveryMethod === 'pick-up';

            const storageKey = 'shipping-freight';
            const useNewLocalStorage = window.e383157Toggle;
            const hasLiftGate = useNewLocalStorage ? fxoStorage.get(storageKey) : localStorage.getItem(storageKey);
            if (hasLiftGate) {
                payload.hasLiftGate = hasLiftGate === 'true';
            }

            let shippingComponent = registry.get('checkout.steps.shipping-step.shippingAddress');
            if (window.checkoutConfig.isCustomerShippingAccount3PEnabled && shippingComponent && !shippingComponent.isFullFirstPartyQuote()) {
                payload.fedEx_account_number = shippingComponent.shippingAccountNumber()
            }

            var maegeeks_pobox_validation = typeof window.checkoutConfig.maegeeks_pobox_validation != 'undefined' ? window.checkoutConfig.maegeeks_pobox_validation : false;
            
            let isRecipientAddressEnable = typeof window.checkoutConfig.is_recipient_address_from_po != 'undefined' ? window.checkoutConfig.is_recipient_address_from_po : false;
            if (window.d196640_toggle && isRecipientAddressEnable) {
                if (window.e383157Toggle) {
                    fxoStorage.set('isAddressValidated', true);
                } else {
                    localStorage.setItem('isAddressValidated', true);
                }
            }

            if (window.e383157Toggle) {
                var isAddressValidated = fxoStorage.get('isAddressValidated').toString();
            } else {
                var isAddressValidated = localStorage.getItem('isAddressValidated').toString();
            }

            if(window.d196640_toggle){
                payload.reRate = true;
                if ((window.callDeliveryOptions == undefined || window.callDeliveryOptions) && streetValue && cityValue && regionIdValue && postcodeValue && isAddressValidated == 'true') {
                    shippingService.isLoading(true);
                    storage.post(
                        serviceUrl, JSON.stringify(payload), false
                    ).done(function (result) {
                        shippingService.isLoading(true);
                        if (streetValue && cityValue && regionIdValue && postcodeValue) {
                            if (typeof result != 'undefined' && result != null && result.length > 0) {
                                $('.error-container').addClass('api-error-hide');
                                rateRegistry.set(address.getCacheKey(), result);
                                
                                shippingService.setShippingRates(result);
                                
                                /* update item row total B-1105765 */
                                if (window.checkoutConfig.hco_price_update) {
                                    $("#opc-shipping_method .checkout-shipping-method").css('display', 'block');
                                }
                            } else {
                                shippingService.setShippingRates([]);
                                // D-156286 Prevent system error message from showing when the user is in pickup mode

                                if( !payload.isPickup ) {
                                    $('.error-container').removeClass('api-error-hide');
                                    let stingStreetValue = streetValue.toString().trim().replace('.', '').substr(0, 2).toLowerCase();
                                    if(stingStreetValue == 'po' && !maegeeks_pobox_validation) {
                                        $(".error-container .message-container").text("We can't deliver to a P.O. Box. Please enter a physical address.").show();
                                    } else {
                                        $(".error-container .message-container").text('System error. Please Try Again').show();
                                    }
                                }
                            }
                        } else {
                            rateRegistry.set(address.getCacheKey(), result);
                            shippingService.setShippingRates(result);
                            /* update item row total B-1105765 */
                            if (window.checkoutConfig.hco_price_update) {
                                $("#opc-shipping_method .checkout-shipping-method").css('display', 'block');
                            }
                        }
                        if (window.checkoutConfig.isCustomerShippingAccount3PEnabled && shippingComponent && !shippingComponent.isFullFirstPartyQuote()) {
                            if (typeof result != 'undefined' && result != null && result.length > 0 && Array.isArray(result[1])) {
                                let error = result[1][0];
                                if (error.hasOwnProperty('code') && error.code === "ACCOUNT.NUMBER.INVALID") {
                                    $("#fedExAccountNumber_validate").html(error.message);
                                    $("#fedExAccountNumber").prop("disabled", false);
                                    $("#addFedExAccountNumberButton").prop("disabled", false).removeClass('disabled');
                                }
                            }
                        }
                    }).fail(function (response) {
                        shippingService.setShippingRates([]);
                        errorProcessor.process(response);
                    }).always(function () {
                        window.callDeliveryOptions = false;
                        shippingService.isLoading(false);
                    });
                }
            } else {
                storage.post(
                    serviceUrl, JSON.stringify(payload), false
                ).done(function (result) {
                    if (streetValue && cityValue && regionIdValue && postcodeValue) {
                        if (typeof result != 'undefined' && result != null && result.length > 0) {
                            $('.error-container').addClass('api-error-hide');
                            rateRegistry.set(address.getCacheKey(), result);
                            if (window.e383157Toggle) {
                                var isAddressValidated = fxoStorage.get('isAddressValidated').toString();
                            } else {
                                var isAddressValidated = localStorage.getItem('isAddressValidated').toString();
                            }

                            if (isAddressValidated == 'true') {
                                shippingService.setShippingRates(result);
                            }

                            /* update item row total B-1105765 */
                            if (window.checkoutConfig.hco_price_update) {
                                $("#opc-shipping_method .checkout-shipping-method").css('display', 'block');
                            }
                        } else {
                            shippingService.setShippingRates([]);
                            // D-156286 Prevent system error message from showing when the user is in pickup mode
    
                            if( !payload.isPickup ) {
                                $('.error-container').removeClass('api-error-hide');
                                let stingStreetValue = streetValue.toString().trim().replace('.', '').substr(0, 2).toLowerCase();
                                if(stingStreetValue == 'po' && !maegeeks_pobox_validation) {
                                    $(".error-container .message-container").text("We can't deliver to a P.O. Box. Please enter a physical address.").show();
                                } else {
                                    $(".error-container .message-container").text('System error. Please Try Again').show();
                                }
                            }
                        }
                    } else {
                        rateRegistry.set(address.getCacheKey(), result);
                        shippingService.setShippingRates(result);
                        /* update item row total B-1105765 */
                        if (window.checkoutConfig.hco_price_update) {
                            $("#opc-shipping_method .checkout-shipping-method").css('display', 'block');
                        }
                    }
                    if (window.checkoutConfig.isCustomerShippingAccount3PEnabled && shippingComponent && !shippingComponent.isFullFirstPartyQuote()) {
                        if (typeof result != 'undefined' && result != null && result.length > 0 && Array.isArray(result[1])) {
                            let error = result[1][0];
                            if (error.hasOwnProperty('code') && error.code === "ACCOUNT.NUMBER.INVALID") {
                                $("#fedExAccountNumber_validate").html(error.message);
                                $("#fedExAccountNumber").prop("disabled", false);
                                $("#addFedExAccountNumberButton").prop("disabled", false).removeClass('disabled');
                            }
                        }
                    }
                }).fail(function (response) {
                    shippingService.setShippingRates([]);
                    errorProcessor.process(response);
                }).always(function () {
                    shippingService.isLoading(false);
                });
            }
            
        }
    };
});
