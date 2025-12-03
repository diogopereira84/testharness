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
    'jquery', /* update item row total B-1105765 */
    'fedex/storage',
    'uiRegistry',
], function (resourceUrlManager, quote, storage, shippingService, rateRegistry, errorProcessor,$,fxoStorage, registry) {
    'use strict';

    return {
        /**
         * @param {Object} address
         */
        getRates: function (address) {
            var payload,
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
            payload = {
                addressId: address.customerAddressId,
                productionLocation: product_location
            };

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
            if (window.e383157Toggle) {
                var isAddressValidated = fxoStorage.get('isAddressValidated').toString();
            } else {
                var isAddressValidated = localStorage.getItem('isAddressValidated').toString();
            }
            if(!window.d196640_toggle) {
                shippingService.isLoading(true);
            }
            if(window.d196640_toggle) {
                payload.reRate = true;
                if ((window.callDeliveryOptions == undefined || window.callDeliveryOptions) && isAddressValidated == 'true') {
                    shippingService.isLoading(true);
                    storage.post(
                        resourceUrlManager.getUrlForEstimationShippingMethodsByAddressId(),
                        JSON.stringify(payload),
                        false
                    ).done(function (result) {
                        window.callDeliveryOptions = false;
                        shippingService.isLoading(true);
                        if (typeof result != 'undefined' && result != null && result.length > 0) {
                            $('.error-container').addClass('api-error-hide');
                            rateRegistry.set(address.getKey(), result);
                            shippingService.setShippingRates(result);
                            /* update item row total B-1105765 */
                            if (window.checkoutConfig.hco_price_update) {
                                $("#opc-shipping_method .checkout-shipping-method").css('display', 'block');
                            }
                        } else {
                            // D-156286
                            // Prevent system error message from showing when the user is in pickup mode
                            if (!payload.isPickup ) {
                                $('.error-container').removeClass('api-error-hide');
                                $(".error-container .message-container").text('System error. Please Try Again').show();
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
                        window.callDeliveryOptions = false;
                        shippingService.setShippingRates([]);
                        errorProcessor.process(response);
                    }).always(function () {
                        window.callDeliveryOptions = false;
                        shippingService.isLoading(false);
                    });
                }
            } else {
                storage.post(
                    resourceUrlManager.getUrlForEstimationShippingMethodsByAddressId(),
                    JSON.stringify(payload),
                    false
                ).done(function (result) {
                    if (typeof result != 'undefined' && result != null && result.length > 0) {
                        $('.error-container').addClass('api-error-hide');
                        rateRegistry.set(address.getKey(), result);
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
                        // D-156286
                        // Prevent system error message from showing when the user is in pickup mode
                        if (!payload.isPickup ) {
                            $('.error-container').removeClass('api-error-hide');
                            $(".error-container .message-container").text('System error. Please Try Again').show();
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
