/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'ko',
    './shipping-rates-validation-rules',
    '../model/address-converter',
    '../action/select-shipping-address',
    './postcode-validator',
    './default-validator',
    'mage/translate',
    'uiRegistry',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Checkout/js/model/quote'
], function (
    $,
    ko,
    shippingRatesValidationRules,
    addressConverter,
    selectShippingAddress,
    postcodeValidator,
    defaultValidator,
    $t,
    uiRegistry,
    formPopUpState
) {
    'use strict';

    let checkoutConfig = window.checkoutConfig,
        validators = [],
        observedElements = [],
        postcodeElements = [],
        postcodeElementName = 'postcode';

    validators.push(defaultValidator);

    return {
        validateAddressTimeout: 0,
        validateZipCodeTimeout: 0,
        validateDelay: 2000,

        /**
         * @param {String} carrier
         * @param {Object} validator
         */
        registerValidator: function (carrier, validator) {
            if (checkoutConfig.activeCarriers.indexOf(carrier) !== -1) {
                validators.push(validator);
            }
        },

        /**
         * @param {Object} address
         * @return {Boolean}
         */
        validateAddressData: function (address) {
            return validators.some(function (validator) {
                return validator.validate(address);
            });
        },

        /**
         * Perform postponed binding for fieldset elements
         *
         * @param {String} formPath
         */
        initFields: function (formPath) {
            let self = this,
                elements = shippingRatesValidationRules.getObservableFields();

            if ($.inArray(postcodeElementName, elements) === -1) {
                // Add postcode field to observables if not exist for zip code validation support
                elements.push(postcodeElementName);
            }

            $.each(elements, function (index, field) {
                uiRegistry.async(formPath + '.' + field)(self.doElementBinding.bind(self));
            });
        },

        /**
         * Bind shipping rates request to form element
         *
         * @param {Object} element
         * @param {Boolean} force
         * @param {Number} delay
         */
        doElementBinding: function (element, force, delay) {
            let observableFields = shippingRatesValidationRules.getObservableFields();

            if (element && (observableFields.indexOf(element.index) !== -1 || force)) {
                if (element.index !== postcodeElementName) {
                    this.bindHandler(element, delay);
                }
            }

            if (element.index === postcodeElementName) {
                this.bindHandler(element, delay);
                postcodeElements.push(element);
            }
        },

        /**
         * @param {*} elements
         * @param {Boolean} force
         * @param {Number} delay
         */
        bindChangeHandlers: function (elements, force, delay) {
            let self = this;

            $.each(elements, function (index, elem) {
                self.doElementBinding(elem, force, delay);
            });
        },

        /**
         * @param {Object} element
         * @param {Number} delay
         */
        bindHandler: function (element, delay) {
            let self = this;

            delay = typeof delay === 'undefined' ? self.validateDelay : delay;

            if (element.component.indexOf('/group') !== -1) {
                $.each(element.elems(), function (index, elem) {
                    self.bindHandler(elem);
                });
            } else {
                element.on('value', function () {
                    clearTimeout(self.validateZipCodeTimeout);
                    self.validateZipCodeTimeout = setTimeout(function () {
                        if (element.index === postcodeElementName) {
                            self.postcodeValidation(element);
                        } else {
                            $.each(postcodeElements, function (index, elem) {
                                self.postcodeValidation(elem);
                            });
                        }
                    }, delay);

                    if (!formPopUpState.isVisible()) {
                        clearTimeout(self.validateAddressTimeout);
                        self.validateAddressTimeout = setTimeout(function () {
                            self.validateFields();
                        }, delay);
                    }
                });
                observedElements.push(element);
            }
        },

        /**
         * @return {*}
         */
        postcodeValidation: function (postcodeElement) {
            let countryId = $('select[name="country_id"]:visible').val(),
                validationResult,
                errorMessage;

            if (postcodeElement == null || postcodeElement.value() == null) {
                return true;
            }

            postcodeElement.error(null);
            validationResult = postcodeValidator.validate(postcodeElement.value(), countryId);

            if (!validationResult) {
                errorMessage = $t('Please enter a valid zip code.');
                postcodeElement.error(errorMessage);
            }

            return validationResult;
        },

        /**
         * Convert form data to quote address and validate fields for shipping rates
         */
        validateFields: function () {
            let addressFlat = addressConverter.formDataProviderToFlatData(
                this.collectObservedData(),
                'shippingAddress'
                ),
                address;

            if (this.validateAddressData(addressFlat)) {
                addressFlat = uiRegistry.get('checkoutProvider').shippingAddress;
                address = addressConverter.formAddressDataToQuoteAddress(addressFlat);
                //selectShippingAddress(address);
            }
        },

        /**
         * Collect observed fields data to object
         *
         * @returns {*}
         */
        collectObservedData: function () {
            let observedValues = {};

            $.each(observedElements, function (index, field) {
                observedValues[field.dataScope] = field.value();
            });

            return observedValues;
        }
    };
});
