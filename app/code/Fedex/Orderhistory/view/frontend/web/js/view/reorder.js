/**
* Copyright © Fedex, Inc. All rights reserved.
* See COPYING.txt for license details.
*/

define([
    'jquery',
    'uiComponent',
    'mage/url',
    'mage/storage',
    'Magento_Customer/js/customer-data',
    'fedex/storage'
], function ($, Component, urlBuilder, storage, customerData,fxoStorage) {
    'use strict';

    return Component.extend({

        /** @inheritdoc */
        initialize: function () {
            this._super();
            var self = this;
            $('body').on('click', function () {
                self.closeAlertErrorBox();
            });
            $('body a').on('click', function () {
                self.closeAlertErrorBox();
            });
        },

        closeAlertErrorBox: function() {
            let alertBoxContainer = $('.reorder-success');
            if (typeof alertBoxContainer != 'undefined') {
                alertBoxContainer.hide();
            }
        },

        submitReorder: function () {
            //Request url controller url for reorder
            let body = $('body').loader();
            body.loader('show');
            let reorderRequestUrl =  urlBuilder.build('orderhistory/order/reorder');
            let reorderCheckedItems;
            if(window.e383157Toggle){
                reorderCheckedItems = fxoStorage.get('reorder-items-data');
            }else{
                reorderCheckedItems = localStorage.getItem('reorder-items-data');
            }
            if (typeof(reorderCheckedItems) !== "undefined" && reorderCheckedItems !== null) {
                let productInstance;
                let isOutSourced = false;
                let isEmptyCart = false;

                let premiumItem;
                let nonPremiumItem;

                if(window.e383157Toggle){
                    premiumItem = !!fxoStorage.get('reorder-items-data').includes('"isOutSourced":"1"');
                    nonPremiumItem = !!fxoStorage.get('reorder-items-data').includes('"isOutSourced":""');
                }else{
                    premiumItem = localStorage.getItem('reorder-items-data').includes('"isOutSourced":"1"') ? true : false;
                    nonPremiumItem = localStorage.getItem('reorder-items-data').includes('"isOutSourced":""') ? true : false;
                }

                const cacheStorage = JSON.parse(window.localStorage.getItem('mage-cache-storage'));
                if (typeof(cacheStorage.cart) != "undefined" && cacheStorage.cart != null) {
                    if(cacheStorage.cart.items.length) {
                        isEmptyCart = true;
                    }
                    productInstance = cacheStorage.cart.items.find((item) => {
                        if ((typeof(item.externalProductInstance) !== 'undefined' && item.externalProductInstance !== null && item.externalProductInstance !== "")) {
                            if(typeof item.externalProductInstance !== 'object' && typeof(JSON.parse(item.externalProductInstance)['fxoProductInstance']['productConfig']['product']['isOutSourced']) !== "undefined"
                                && (JSON.parse(item.externalProductInstance)['fxoProductInstance']['productConfig']['product']['isOutSourced'] != null)){
                                isOutSourced = JSON.parse(item.externalProductInstance)['fxoProductInstance']['productConfig']['product']['isOutSourced'];
                            } else if(typeof(item.externalProductInstance['isOutSourced']) !== "undefined"
                                && (item.externalProductInstance)['isOutSourced'] != null) {
                                isOutSourced = item.externalProductInstance['isOutSourced'];
                            }
                            return isOutSourced;
                        }
                    });
                }
                if((isEmptyCart && premiumItem && !nonPremiumItem && isOutSourced) || (isEmptyCart && !premiumItem && nonPremiumItem && !isOutSourced) || (!isEmptyCart && premiumItem && !nonPremiumItem && !isOutSourced) || (!isEmptyCart && !premiumItem && nonPremiumItem && !isOutSourced)) {
                    storage.post(
                        reorderRequestUrl,
                        reorderCheckedItems,
                        false
                    ).done(function (response) {
                        if(window.e383157Toggle){
                            fxoStorage.set('reorder-items-data', "{}")
                        }else{
                            localStorage.setItem('reorder-items-data', "{}");
                        }
                        customerData.reload(['cart'], true);
                        customerData.reload(['customer'], true);
                        body.loader('hide');
                        $('.checkbox-item').prop('checked', false);
                        $('.checkbox-item').css("display", "block");
                        $('.item-checked-icon').css("display", "none");
                        $('.checkbox-order').prop('checked', false);
                        $('.checkbox-order').css("display", "block");
                        $('.order-checked-icon').css("display", "none");
                        $('.retail-reorder-btn').removeClass('active');
                        $('.table-row').removeClass('row-active');
                        $('.order-items-row').css('display', 'none');

                        if (response.status == 1) {
                            let alertBoxContainer = $('.reorder-success');
                            alertBoxContainer.show();
                            $(".retail-reorder-btn").prop("disabled", true);
                            if (response.success > 1) {
                                let msg = response.success + ' item(s) have been added to your cart.';
                                $('.reorder-notification-msg').text(msg);
                            } else {
                                let msg = response.success + ' item has been added to your cart.';
                                $('.reorder-notification-msg').text(msg);
                            }
                            $('.reorder-close-icon').on('click', function () {
                                alertBoxContainer.hide();
                            });
                        } else {
                            let error = response.error;
                            let errorAlertBoxContainer = $('.reorder-error');
                            if (error == 'cart_max_limit_exceeded') {
                                window.location.href = window.checkout.shoppingCartUrl;
                            } else {
                                errorAlertBoxContainer.show();
                                $(".retail-reorder-btn").prop("disabled", true);
                                $('.reorder-error-notification-msg').text(error);
                                $('.reorder-error-close-icon').on('click', function () {
                                    errorAlertBoxContainer.hide();
                                });
                            }
                        }
                    }).fail(function(response) {
                        customerData.reload(['cart'], true);
                        customerData.reload(['customer'], true);
                        body.loader('hide');
                        let error = response.error;
                        let errorAlertBoxContainer = $('.reorder-error');
                        errorAlertBoxContainer.show();
                        $(".retail-reorder-btn").prop("disabled", true);
                        $('.reorder-error-notification-msg').text(error);
                        $('.reorder-error-close-icon').on('click', function () {
                            errorAlertBoxContainer.hide();
                        });
                    });
                } else {
                    body.loader('hide');
                    let errorAlertBoxContainer = $('.reorder-error');
                    errorAlertBoxContainer.show();
                    $(".retail-reorder-btn").prop("disabled", true);
                    $('.reorder-error-notification-msg').text('We are unable to combine this item with items in your cart or with other items selected for reorder.');
                    $('.reorder-error-close-icon').on('click', function () {
                        errorAlertBoxContainer.hide();
                    });
                }
            }
        },

        submitReorderView: function () {
            let body = $('body').loader();
            body.loader('show');
            let reorderRequestUrl = urlBuilder.build('orderhistory/order/reorder');
            let reorderData = {};
            let reorderCheckedItems = {};
            $('.reorder-item').each(function() {
                let orderId = $(this).attr('data-order-id');
                let itemId = $(this).attr('data-item-id');
                let productId = $(this).attr('data-product-id');
                let isOutSourced = $(this).attr('data-is-OutSourced');
                let productSku = $(this).data('item-sku');
                let itemQty = $(this).data('item-qty');
                let orderIncrementId = $(this).attr('data-order-increment-id');
                let isMiraklOffer = $(this).attr('data-is-mirakl-offer');
                let offerId = $(this).attr('data-offer-id');
                reorderData[itemId] = {
                    order_id: orderId,
                    product_id: productId,
                    item_id: itemId,
                    isOutSourced: isOutSourced,
                    itemQty: itemQty,
                    isMiraklOffer: isMiraklOffer,
                    offerId: offerId,
                    order_increment_id: orderIncrementId,
                    product_sku: productSku
                };
                reorderCheckedItems = JSON.stringify(reorderData);
            });

            if (typeof(reorderCheckedItems) !== "undefined" && reorderCheckedItems !== null) {
                let productInstance;
                let isOutSourced = false;
                let isEmptyCart = false;
                let itemType = reorderCheckedItems.includes('"isOutSourced":"1"') ? true : false;

                const cacheStorage = JSON.parse(window.localStorage.getItem('mage-cache-storage'));
                if (typeof(cacheStorage.cart) != "undefined" && cacheStorage.cart != null) {
                    if(cacheStorage.cart.items.length) {
                        isEmptyCart = true;
                    }
                    productInstance = cacheStorage.cart.items.find((item) => {
                        if ((typeof(item.externalProductInstance) !== 'undefined' && item.externalProductInstance !== null && item.externalProductInstance !== "")) {
                            if(typeof item.externalProductInstance !== 'object' && typeof(JSON.parse(item.externalProductInstance)['fxoProductInstance']['productConfig']['product']['isOutSourced']) !== "undefined"
                                && (JSON.parse(item.externalProductInstance)['fxoProductInstance']['productConfig']['product']['isOutSourced'] != null)){
                                isOutSourced = JSON.parse(item.externalProductInstance)['fxoProductInstance']['productConfig']['product']['isOutSourced'];
                            } else if(typeof(item.externalProductInstance['isOutSourced']) !== "undefined"
                                && (item.externalProductInstance)['isOutSourced'] != null) {
                                isOutSourced = item.externalProductInstance['isOutSourced'];
                            }
                            return isOutSourced;
                        }
                    });
                }

                if((isEmptyCart && itemType && isOutSourced) || (isEmptyCart && !itemType && !isOutSourced) || (!isEmptyCart && itemType && !isOutSourced) || (!isEmptyCart && !itemType && !isOutSourced)) {
                    storage.post(
                        reorderRequestUrl,
                        reorderCheckedItems,
                        false
                    ).done(function (response) {
                        customerData.reload(['cart'], true);
                        customerData.reload(['customer'], true);
                        body.loader('hide');
                        if (response.status == 1) {
                            let alertBoxContainer = $('.reorder-success');
                            alertBoxContainer.show();
                            if (response.success > 1) {
                                let msg = response.success + ' item(s) have been added to your cart.';
                                $('.reorder-notification-msg').text(msg);
                            } else {
                                let msg = response.success + ' item has been added to your cart.';
                                $('.reorder-notification-msg').text(msg);
                            }
                            $('.reorder-close-icon').on('click', function () {
                                alertBoxContainer.hide();
                            });
                        } else {
                            let error = response.error;
                            let errorAlertBoxContainer = $('.reorder-error');
                            if (error == 'cart_max_limit_exceeded') {
                                window.location.href = window.checkout.shoppingCartUrl;
                            } else {
                                errorAlertBoxContainer.show();
                                $('.reorder-error-notification-msg').text(error);
                                $('.reorder-error-close-icon').on('click', function () {
                                    errorAlertBoxContainer.hide();
                                });
                            }
                        }
                    }).fail(function(response) {
                        customerData.reload(['cart'], true);
                        customerData.reload(['customer'], true);
                        body.loader('hide');
                        let error = response.error;
                        let errorAlertBoxContainer = $('.reorder-error');
                        errorAlertBoxContainer.show();
                        $('.reorder-error-notification-msg').text(error);
                        $('.reorder-error-close-icon').on('click', function () {
                            errorAlertBoxContainer.hide();
                        });
                    });
                } else {
                    body.loader('hide');
                    let errorAlertBoxViewContainer = $('.reorder-error-order-view');
                    errorAlertBoxViewContainer.show();
                    $('.reorder-error-order-view-notification-msg').text('We are unable to combine this order with items in your cart. Please checkout to proceed with these items.');
                    $('.reorder-error-order-view-close-icon').on('click', function () {
                        errorAlertBoxViewContainer.hide();
                    });
                }
            }
        }
    });
});
