/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/url',
    'orderApprovalgdlEvent'
],function(
    $,
    urlBuilder,
    orderApprovalgdlEvent
) {

    /**
     * Function to trigger Approve process.
     * @param {Object} _this
     * @param {String} from
     * @return void
     */
    function triggerOrderApprove(_this, from = ""){
        let orderId = $(_this).attr("data-itemid");
        let orderPrice = $(_this).attr("data-order-price");
        let approveOrderUrl = urlBuilder.build("orderb2b/index/approveorder");
        $.ajax({
            url: approveOrderUrl,
            showLoader: true,
            type: "POST",
            data: {order_id: orderId},
            success: function (response) {
                if (!response.success && response.msg && from && from == "review-detail") {
                    window.location.href = $(location).attr('href');
                } else {
                    orderApprovalgdlEvent.appendGDLScript(orderPrice);
                    window.location.href = urlBuilder.build('orderb2b/revieworder/history');
                }
            }
        });
    }

    /**
     * Fix ADA for Review order details page
     */
     $('.commercial-order-approval .review-order-breadcrumb .item.myoreder').on('keydown', function (event) {
        if (event.keyCode === 9) {
            event.preventDefault();
            $('.btn-order-approve').focus();
        }
    });

    /**
     * Fix ADA for Decline button
     */
    $('.commercial-order-approval .review-order-action-container .order-approval-action-container .btn-order-approve').on('keydown', function (event) {
        if (event.keyCode === 9) {
            event.preventDefault();
            $('.commercial-order-approval .review-order-action-container .order-approval-action-container .btn-order-decline').focus();
        }
    });

    /**
     * Fix ADA for Approve button
     */
    $('.commercial-order-approval .review-order-action-container .order-approval-action-container .btn-order-decline').on('keydown', function (event) {
        if (event.keyCode === 9) {
            event.preventDefault();
            $('.commercial-order-approval .show-properties-view-order').focus();
        }
    });

    /**
     * Return function call
     */
    return {
        triggerOrderApprove: triggerOrderApprove
    };

});
