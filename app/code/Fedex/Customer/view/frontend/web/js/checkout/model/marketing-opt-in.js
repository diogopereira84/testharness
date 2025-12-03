/**
 * Copyright © 2013-2023 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true*/
/*global alert*/
define(['jquery','fedex/storage'], function ($,fxoStorage) {
        'use strict';

        return {
            build: function () {
                try {
                    let isCheckoutConfig = typeof (window.checkoutConfig) !== "undefined" && window.checkoutConfig !== null ? true : false;
                    let isMarketingOptInToggle = false;
                    if (isCheckoutConfig) {
                        isMarketingOptInToggle = typeof (window.checkoutConfig.marketing_opt_in_toggle) !== "undefined" && window.checkoutConfig.marketing_opt_in_toggle !== null ? window.checkoutConfig.marketing_opt_in_toggle : false;
                        let marketingOptInElement = $("#marketing-opt-in-checkbox");
                        if (isMarketingOptInToggle && marketingOptInElement.length && marketingOptInElement.prop("checked")) {
                            var isShip,isPick;
                            if(window.e383157Toggle){
                                isShip = fxoStorage.get("shipkey");
                                isPick = fxoStorage.get("pickupkey");
                            }else{
                                isShip = localStorage.getItem("shipkey");
                                isPick = localStorage.getItem("pickupkey");
                            }
                            let optInObj = {};
                            optInObj.languageCode = 'EN';
                            if (isShip === 'false' && isPick === 'true') {
                                let pickupData;
                                if(window.e383157Toggle){
                                    pickupData = fxoStorage.get("pickupData");
                                }else{
                                    pickupData = JSON.parse(localStorage.getItem('pickupData'));
                                }
                                optInObj.emailAddress = pickupData.contactInformation.contact_email;
                                optInObj.firstName = pickupData.contactInformation.contact_fname;
                                optInObj.lastName = pickupData.contactInformation.contact_lname;
                                //@TODO Check logic for inserting companyName here
                                optInObj.companyName = '';

                                optInObj.countryCode = pickupData.addressInformation.pickup_location_country;
                                optInObj.streetAddress = pickupData.addressInformation.pickup_location_street;
                                optInObj.cityName = pickupData.addressInformation.pickup_location_city;
                                optInObj.stateProvince = pickupData.addressInformation.pickup_location_state;
                                optInObj.postalCode = pickupData.addressInformation.pickup_location_zipcode;
                            } else if (isShip === 'true' && isPick === 'false') {

                                let shippingData;
                                if(window.e383157Toggle){
                                    shippingData = fxoStorage.get("shippingData");
                                }else{
                                    shippingData = JSON.parse(localStorage.getItem('shippingData'));
                                }
                                shippingData.addressInformation.shipping_address.customAttributes.forEach(
                                    (element) => {
                                        if (element.attribute_code === 'email_id') {
                                            optInObj.emailAddress = element.value;
                                        }
                                    }
                                );
                                optInObj.firstName = shippingData.addressInformation.shipping_address.firstname;
                                optInObj.lastName = shippingData.addressInformation.shipping_address.lastname;
                                //@TODO Check logic for inserting companyName here
                                optInObj.companyName = '';

                                optInObj.countryCode = shippingData.addressInformation.shipping_address.countryId;
                                optInObj.streetAddress = shippingData.addressInformation.shipping_address.street.join(' ');
                                optInObj.cityName = shippingData.addressInformation.shipping_address.city;
                                var regionId = shippingData.addressInformation.shipping_address.regionId ? shippingData.addressInformation.shipping_address.regionId : '';
                                if (regionId) {
                                    var stateProvince = $('option[value="'+regionId+'"]').text();
                                    optInObj.stateProvince = stateProvince;
                                } else {
                                    optInObj.stateProvince = regionId;
                                }
                                optInObj.postalCode = shippingData.addressInformation.shipping_address.postcode;
                            }
                            return JSON.stringify(optInObj);
                        }
                    }
                } catch (error) {
                    console.error(error);
                    return false;
                }
            }
        };
    }
);
