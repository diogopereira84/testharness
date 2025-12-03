/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
require(
[
    'jquery',
    'Magento_Ui/js/modal/modal',
    'orderViewDetail'
],
function(
    $,
    modal,
    orderViewDetail
) {
    let options = {
        type: 'popup',
        innerScroll: true,
        modalClass: 'orderb2b-approve-modal-popup'
    };

   /**
    * Open popup on click of approve link
    */
    $(".order-approve-link, .btn-order-approve").on('click',function() {
        let _this = this;
        let itemId = $(_this).attr("data-itemid");
        let orderPrice = $(_this).attr("data-order-price");
        $(".b2b-order-error-toast-msg, .b2b-order-success-toast-msg").hide();
        $("#order-approve-modal-popup").modal(options).modal("openModal");
        $(".btn-approve-order").attr("data-itemid", itemId);
        $(".btn-approve-order").attr("data-order-price", orderPrice);
        if ($(_this).hasClass('btn-order-approve')) {
            $(".btn-approve-order").attr("data-from", "review-detail");
        }
    });
            
    /**
     * Close popup on click of cancel
     */
    $(".btn-approve-cancel").on('click',function() {
        if ($(this).hasClass('btn-approve-cancel')) {
            $("#order-approve-modal-popup").modal('closeModal');
        }
    });

    /**
     * trigger approve process.
     */
    $(".btn-approve-order").on('click',function() {
        let dataFrom = $(this).attr("data-from");
        orderViewDetail.triggerOrderApprove(this, dataFrom);
        $("#order-approve-modal-popup").modal('closeModal');
    });

});     
