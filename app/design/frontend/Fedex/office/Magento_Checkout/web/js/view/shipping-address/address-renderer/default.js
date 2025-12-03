/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'ko',
    'uiComponent',
    'underscore',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/customer-data',
    'fedex/storage',
    'shippingFormAdditionalScript',
    'mage/url',
], function ($, ko, Component, _, selectShippingAddressAction, quote, formPopUpState, checkoutData, customerData,fxoStorage,shippingFormAdditionalScript,urlBuilder) {
    'use strict';
    customerData.reload(["directory-data"], true);
    var countryData = customerData.get('directory-data');

    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/shipping-address/address-renderer/default'
        },


        /** @inheritdoc */
        initObservable: function () {
            this._super();
            this.isSelected = ko.computed(function () {
                var isSelected = false,
                    shippingAddress = quote.shippingAddress();

                if (shippingAddress) {
                    isSelected = shippingAddress.getKey() == this.address().getKey(); //eslint-disable-line eqeqeq
                }

                let isFclCustomer = typeof(window.checkoutConfig.is_fcl_customer) != 'undefined' && window.checkoutConfig.is_fcl_customer != null ? window.checkoutConfig.is_fcl_customer : false;
                let isSelfRegCustomer = false;
                if(window.checkoutConfig.is_selfreg_customer !== undefined){
                    isSelfRegCustomer = window.checkoutConfig.is_selfreg_customer;
                }
                let isLoggedIn = typeof(window.checkoutConfig.is_logged_in) != 'undefined' && window.checkoutConfig.is_logged_in != null ? window.checkoutConfig.is_logged_in : false;
                let isOutSourced = typeof(window.checkoutConfig.is_out_sourced) != 'undefined' && window.checkoutConfig.is_out_sourced != null ? window.checkoutConfig.is_out_sourced : false;
                let isShip;
                if(window.e383157Toggle){
                    isShip = fxoStorage.get("shipkey");
                }else{
                    isShip = localStorage.getItem("shipkey");
                }
                if (isSelfRegCustomer || (isFclCustomer && !isLoggedIn && (isShip || isOutSourced))) {
                    var defaultShippingAddress = window.checkoutConfig.fcl_customer_default_shipping_data;
                    $(document).ajaxComplete(function() {
                        let customerInfo = customerData.get('checkout-data')();
                        var email = $(".form-shipping-address input[name^='custom_attributes[email_id]']").val();
                        if (typeof (email) == 'undefined' || !email) {
                                email = customerInfo
                                    && customerInfo.shippingAddressFromData
                                    && customerInfo.shippingAddressFromData.custom_attributes
                                    && customerInfo.shippingAddressFromData.custom_attributes['email_id'];

                            if (typeof (email) == 'undefined' || !email) {
                                email = defaultShippingAddress['email'];
                            }
                            $(".form-shipping-address input[name^='custom_attributes[email_id]']").val(email);
                        }
                        var company = $(".form-shipping-address input[name^='company']").val();
                        if (typeof (company) == 'undefined' || !company) {
                            company = customerInfo.shippingAddressFromData && customerInfo.shippingAddressFromData.company;
                            if (typeof (company) == 'undefined' || !company) {
                                company = defaultShippingAddress['company'];
                            }
                            $(".form-shipping-address input[name^='company']").val(company);
                        }

                        var street_0 = $(".form-shipping-address input[name^='street[0]']").val();
                        if (typeof (street_0) == 'undefined' || !street_0) {
                                street_0 = customerInfo.shippingAddressFromData && customerInfo.shippingAddressFromData.street[0];
                            if (typeof (street_0) == 'undefined' || !street_0) {
                                street_0 = defaultShippingAddress['streetOne'];
                            }
                            $(".form-shipping-address input[name^='street[0]']").val(street_0);
                        }

                        var street_1 = $(".form-shipping-address input[name^='street[1]']").val();
                        if (typeof (street_1) == 'undefined' || !street_1) {
                            street_1 = customerInfo.shippingAddressFromData && customerInfo.shippingAddressFromData.street[1];
                            if (typeof (street_1) == 'undefined' || !street_1) {
                                street_1 = defaultShippingAddress['streetTwo'];
                            }
                            $(".form-shipping-address input[name^='street[1]']").val(street_1);
                        }

                        var city = $(".form-shipping-address input[name^='city']").val();
                        if (typeof (city) == 'undefined' || !city) {
                                city = customerInfo.shippingAddressFromData && customerInfo.shippingAddressFromData.city;
                            if (typeof (city) == 'undefined' || !city) {
                                city = defaultShippingAddress['city'];
                            }
                            $(".form-shipping-address input[name^='city']").val(city);
                        }

                        var region_id = customerInfo.shippingAddressFromData ? customerInfo.shippingAddressFromData.region_id : '';
                        if (typeof (region_id) == 'undefined' || !region_id) {
                            region_id = defaultShippingAddress['region'];
                        }
                        $(".form-shipping-address select[name^='region_id']").val(region_id);

                        var postcode = $(".form-shipping-address input[name^='postcode']").val();
                        if (typeof (postcode) == 'undefined' || !postcode) {
                                postcode = customerInfo.shippingAddressFromData && customerInfo.shippingAddressFromData.postcode;
                            if (typeof (postcode) == 'undefined' || !postcode) {
                                postcode = defaultShippingAddress['postcode'];
                            }
                            $(".form-shipping-address input[name^='postcode']").val(postcode);
                        }

                        var telephone = $(".form-shipping-address input[name^='telephone']").val();
                        if (typeof (telephone) == 'undefined' || !telephone) {
                               telephone = customerInfo.shippingAddressFromData && customerInfo.shippingAddressFromData.telephone;
                            if (typeof (telephone) == 'undefined' || !telephone) {
                                telephone = defaultShippingAddress['telephone'];
                            }
                            if (telephone == '(111) 111-1111') {
                                telephone = '';
                            }
                            $(".form-shipping-address input[name^='telephone']").val(telephone);
                        }

                        var ext = $(".form-shipping-address input[name^='custom_attributes[ext]']").val();
                        if (typeof (ext) == 'undefined' || !ext) {
                                ext = customerInfo.shippingAddressFromData && customerInfo.shippingAddressFromData.custom_attributes['ext'];
                            $(".form-shipping-address input[name^='custom_attributes[ext]']").val(ext);
                        }
                        $(".form-shipping-address input[name^='custom_attributes[email_id]']").trigger('change');
                    });
                }
                return isSelected;
            }, this);

            return this;
        },

        /**
         * @param {String} countryId
         * @return {String}
         */
        getCountryName: function (countryId) {
            return countryData()[countryId] != undefined ? countryData()[countryId].name : ''; //eslint-disable-line
        },

        /**
         * Get customer attribute label
         *
         * @param {*} attribute
         * @returns {*}
         */
        getCustomAttributeLabel: function (attribute) {
            var resultAttribute;
            if(attribute.email_id){
                attribute = attribute.email_id;
            }else{
                attribute = attribute;
            }

            if (typeof attribute === 'string') {
                return attribute;
            }

            if (attribute.label) {
                return attribute.label;
            }

            if (typeof this.source.get('customAttributes') !== 'undefined') {
                resultAttribute = _.findWhere(this.source.get('customAttributes')[attribute['attribute_code']], {
                    value: attribute.value
                });
            }

            return resultAttribute && resultAttribute.label || attribute.value;
        },

        /** Set selected customer shipping address  */
        selectAddress: function () {
            selectShippingAddressAction(this.address());
            if (window.d196640_toggle) {
                $('.checkout-shipping-method').hide();
            } else {
                let isRecipientAddressEnable = typeof window.checkoutConfig.is_recipient_address_from_po != 'undefined' ? window.checkoutConfig.is_recipient_address_from_po : false;
                var requestUrl = urlBuilder.build("shippingaddressvalidation/index/addressvalidate");

                var googleSuggestedAddress = shippingFormAdditionalScript.getGoogleSuggestedShippingAddress();
                var checkoutData = customerData.get('checkout-data')();
                var shippingFormData = checkoutData.shippingAddressFromData;
                shippingFormData = shippingFormAdditionalScript.getValidFormData(shippingFormData, googleSuggestedAddress);
                if (!isRecipientAddressEnable) {
                    shippingFormAdditionalScript.getAddress(requestUrl, shippingFormData, true, function (response) {
                        if (typeof response == 'object') {
                            localStorage.setItem('validatedAddress', JSON.stringify(response));
                            localStorage.setItem('shippingFormAddress', JSON.stringify(shippingFormData));
                            let validatedAddress = localStorage.getItem("validatedAddress");
                            validatedAddress = JSON.parse(validatedAddress);
                            if (validatedAddress != null && typeof validatedAddress.output != 'undefined') {
                                shippingFormAdditionalScript.openAddressValidationModal();
                            }
                        }
                    });
                }

                checkoutData.setSelectedShippingAddress(this.address().getKey());
            }
            $('#shipping-method-buttons-container').hide();
        },

        /** Check address is New  */
        isNewAddress: function (address) {
            if(address.customerAddressId){
				return false;
			}
            return true;
        },

        /**
         * Edit address.
         */
        editAddress: function () {
            formPopUpState.isVisible(true);
            this.showPopup();

        },

		 /**
         * remove address.
         */
        removeAddress: function () {
            var data = null;
            checkoutData.setNewCustomerShippingAddress(data);
			//window.location.href='';
			$('.shipping-address-item.newAddress').hide();
			$('.action.action-select-shipping-item').trigger('click');
			$('.new-address-popup button').show();
        },

        /**
         * Show popup.
         */
        showPopup: function () {
            $('[data-open-modal="opc-new-shipping-address"]').trigger('click');
        }
    });
});
