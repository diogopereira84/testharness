/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true*/
/*global alert*/
define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'fedex/storage',
    ],
    function ($, quote, fxoStorage) {
        'use strict';

        return {
            /**
             * @returns {Boolean}
             */
            isFullMarketplaceQuote: function() {
                if (typeof window.checkoutConfig.is_full_marketplace_quote !== 'undefined') {
                    return window.checkoutConfig.is_full_marketplace_quote;
                }

                var result = true;
                $.each(quote.getItems(), function(i, item) {
                    if (!item.mirakl_offer_id && !item.mirakl_shop_id) {
                        result = false;
                        return false;
                    }
                });

                return result;
            },

            /**
             * @returns {Boolean}
             */
            isFullMarketplaceQuoteWithAnyPunchoutDisabled: function() {
                if (!window?.checkoutConfig?.isMktCbbEnabled) {
                    return false;
                }

                var flag = false;
                $.each(quote.getItems(), function(i, item) {
                    if (item.mirakl_offer_id && item.mirakl_shop_id) {
                        var additionalData = JSON.parse(item.additional_data)
                        if (additionalData && additionalData.punchout_enabled === false) {
                            flag = true;
                        }
                    }
                });

                return flag;
            },

            /**
             * @returns {Boolean}
             */
            isMixedQuote: function() {
                let hasFirstPartyItem = false;
                let hasMarketplaceItem = false;
                let isMixedQuote = false;

                $.each(quote.getItems(), function(i, item)
                {
                    let isMarketplaceProduct = false;

                    if(item.additional_data !== null) {
                        isMarketplaceProduct = JSON.parse(item.additional_data);
                        var offerIdFromadditionalData = isMarketplaceProduct?.offer_id
                        var miraklOfferId = item?.mirakl_offer_id;
                        //D180031 -> Determine MixedQuote only if item has offer_id and mirakl_offer_id instead of simple checking additional_data
                        if(window?.checkoutConfig?.toggle_D180031_fix && !offerIdFromadditionalData && !miraklOfferId) {
                            isMarketplaceProduct = false;
                        }
                    }

                    if (isMarketplaceProduct) {
                        hasMarketplaceItem = true;
                    }
                    else {
                        hasFirstPartyItem = true;
                    }

                    if (hasFirstPartyItem && hasMarketplaceItem) {
                        isMixedQuote = true;
                        return true;
                    }
                });

                return isMixedQuote;
            },

            /**
             * @returns {Array}
             */
            getAllProducts: function() {
               return quote.getItems();
            },

            /**
            * @returns Bool
            */
            isFullFirstPartyQuote: function () {

                if (!this.isMixedQuote() && !this.isFullMarketplaceQuote()) {
                    return true;
                }

                return false;
            },

            /**
            * @returns Bool
            */
            containsFirstPartyProduct: function () {
                var hasFirstPartyItem = false;
                $.each(quote.getItems(), function(i, item) {
                    if (item.mirakl_shop_id === null) {
                        hasFirstPartyItem = true;
                    }
                });

                return hasFirstPartyItem;
            },

            /**
            * @returns Bool
            */
            checkIfSomeSellerIsUsingShippingAccount: function () {
                const products = this.getAllProducts();

                return products.find(product => product.seller_ship_account_enabled) ? true : false;
            },

            /**
            * @returns Bool
            */
            isAbleToUseShippingAccount: function (deliveryMethod = undefined) {
                const chosenDeliveryMethod = deliveryMethod !== undefined ? 
                    deliveryMethod : (fxoStorage.get('chosenDeliveryMethod') || '').toLowerCase();

                const showFor1POnlyQuote = this.isFullFirstPartyQuote() && chosenDeliveryMethod === 'shipping';

                const showForMixedPickupQuote = 
                    this.isMixedQuote() && chosenDeliveryMethod === 'pick-up' && this.checkIfSomeSellerIsUsingShippingAccount();

                const showForMixedShippingQuote = this.containsFirstPartyProduct() && chosenDeliveryMethod === 'shipping';

                const showForFull3PQuote = 
                    this.isFullMarketplaceQuote() && this.checkIfSomeSellerIsUsingShippingAccount();

                return (
                    showFor1POnlyQuote ||
                    showForMixedPickupQuote ||
                    showForMixedShippingQuote ||
                    showForFull3PQuote ||
                    this.checkIfSomeSellerIsUsingShippingAccount()
                );
            },
        };
    }
);
