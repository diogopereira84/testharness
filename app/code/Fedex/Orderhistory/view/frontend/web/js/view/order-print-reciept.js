/**
* Copyright © Fedex, Inc. All rights reserved.
* See COPYING.txt for license details.
*/

define([
    'jquery',
    'uiComponent',
    'mage/url',
    'mage/storage',
    'domReady!'
], function ($, Component, urlBuilder, storage) {
    'use strict';

    return Component.extend({

        /**
         * Build Order print recciept
         *
         * @return {void}
         */
        buildOrderPrintReciept: function () {
            let body = $('body').loader();
            body.loader('show');
            let requestParams = {};
            let transactionId = $("#fujitsu-print-reciept").attr("data-id");
            let gtnNumber = $("#fujitsu-print-reciept").attr("data-order-id");
            let printRecieptRequestUrl = urlBuilder.build('orderhistory/order/receipt');
            let postData = {'transaction_id': transactionId, 'gtn_number': gtnNumber};
            requestParams = JSON.stringify(postData);
            storage.post(
                printRecieptRequestUrl,
                requestParams,
                false
            ).done(function (response) {
                if(response.status) {
                    body.loader('hide');
                    let printScreen = null;
                    printScreen = window.open(response.response, '_blank');
                    if (typeof(printScreen) != 'undefined' && printScreen != null && typeof(printScreen.window) != 'undefined'
                    && printScreen.window != null) {
                        printScreen.window.print();
                    }
                } else {
                    body.loader('hide');
                    $('.fujitsu-receipt-error-order-view').show();
                }
            }).fail(function(response) {
                body.loader('hide');
                $('.fujitsu-receipt-error-order-view').show();
            });
        }
    });
});
