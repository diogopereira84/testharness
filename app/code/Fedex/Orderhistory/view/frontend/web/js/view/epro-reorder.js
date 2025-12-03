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
    'inBranchWarning',
    'fedex/storage'
], function ($, Component, urlBuilder, storage, customerData, inBranchWarning,fxoStorage) {
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
            let errorAlertBoxContainer = $('.reorder-error');
            if (typeof errorAlertBoxContainer != 'undefined') {
                errorAlertBoxContainer.hide();
            }
        },

        submitReorder: function () {

            if(window.location.toString().includes("sales/order/view")){
                var baseUrl = $('#baseUrl').val();
                urlBuilder.setBaseUrl(baseUrl);
            }

            //Request url controller url for reorder
            let body = $('body').loader();
            body.loader('show');
            let reorderRequestUrl =  urlBuilder.build('orderhistory/order/reorder');
            let reorderCheckedItems = window.e383157Toggle ? fxoStorage.get('reorder-items-data') : localStorage.getItem('reorder-items-data');

            if (typeof(reorderCheckedItems) !== "undefined" && reorderCheckedItems !== null) {
                storage.post(
                    reorderRequestUrl,
                    reorderCheckedItems,
                    false
                ).done(function (response) {
                    if(window.e383157Toggle){
                        fxoStorage.set('reorder-items-data', '{}');
                    }else{
                        localStorage.setItem('reorder-items-data', "{}");
                    }
                    customerData.reload(['cart'], true);
                    customerData.reload(['customer'], true);
                    body.loader('hide');
                    $('.checkbox-item').prop('checked', false);
                    $('.checkbox-item').css('display', 'block');
                    $('.item-checked-icon').css('display', 'none');
                    $('.checkbox-order').prop('checked', false);
                    $('.checkbox-order').css('display', 'block');
                    $('.order-checked-icon').css('display', 'none');
                    $('.epro-reorder-btn').removeClass('active');
                    $('.table-row').removeClass('row-active');
                    $('.order-items-row').css('display', 'none');
                    //inbranch logic added
                    if (response.isInBranchProductExist == 1) {
                        inBranchWarning.inBranchWarningPopup();
                        $(".action-close").on("click", function() {
                            $(".action.epro-reorder-btn").addClass( "active");
                        });
                        return false;
                    }
                    //inbranch logic ended
                    if (response.status == 1) {
                        let alertBoxContainer = $('.reorder-success');
                        alertBoxContainer.show();
                        $('.epro-reorder-btn').prop('disabled', true);
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
                            $('.epro-reorder-btn').prop('disabled', true);
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
                    $('.epro-reorder-btn').prop('disabled', true);
                    $('.reorder-error-notification-msg').text(error);
                    $('.reorder-error-close-icon').on('click', function () {
                        errorAlertBoxContainer.hide();
                    });
                });
            }
        },

        submitReorderView: function () {

            if(window.location.toString().includes("sales/order/view")){
                let baseUrl = $('#baseUrl').val();
                urlBuilder.setBaseUrl(baseUrl);
            }

            let body = $('body').loader();
            body.loader('show');
            let reorderRequestUrl = urlBuilder.build('orderhistory/order/reorder');
            let reorderData = {};
            let reorderCheckedItems = {};
            $('.reorder-item').each(function() {
                let orderId = $(this).attr('data-order-id');
                let itemId = $(this).attr('data-item-id');
                let productId = $(this).attr('data-product-id');
                let itemQty = $(this).attr('data-item-qty');
                reorderData[itemId] = {order_id: orderId, product_id: productId, item_id: itemId, item_qty: itemQty};
                reorderCheckedItems = JSON.stringify(reorderData);
            });

            if (typeof(reorderCheckedItems) !== "undefined" && reorderCheckedItems !== null) {
                storage.post(
                    reorderRequestUrl,
                    reorderCheckedItems,
                    false
                ).done(function (response) {
                    customerData.reload(['cart'], true);
                    customerData.reload(['customer'], true);
                    body.loader('hide');
                    //inbranch logic added
                    if (response.isInBranchProductExist == 1) {
                        inBranchWarning.inBranchWarningPopup();
                        return false;
                    }
                    //inbranch logic ended
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
            }
        }
    });
});
