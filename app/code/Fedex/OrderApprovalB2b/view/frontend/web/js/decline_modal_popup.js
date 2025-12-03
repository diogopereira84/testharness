/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
require(
[
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/url'
],
function(
    $,
    modal,
    url
) {
    let options = {
        type: 'popup',
        innerScroll: true,
        modalClass: 'orderb2b-decline-modal-popup',
    };
    
    /**
     * Open popup on click of decline link
     */
    $(".order-decline-link, .btn-order-decline").on('click',function() {
        $(".b2b-order-error-toast-msg, .b2b-order-success-toast-msg").hide();
        $("#order-decline-modal-popup").modal(options).modal("openModal");
        $("#order-decline-modal-popup").find("#order_id").val($(this).attr('data-itemid'));
    });
    
    /**
     * Close popup on click of cancel
     */
    $(".btn-decline-cancel").on('click',function() {
        if ($(this).hasClass('btn-decline-cancel')) {
            $("#order-decline-modal-popup").modal('closeModal');
        }
    });

    /**
     * Set char count msg
     */
    $("#additional_comments").on('keyup',function() {
        let _this = this;
        let additionalComments = $(_this).val().trim();
        $(".decline-additional-comment-character-count").text(350 - additionalComments.length);
    });


    /**
     * Close success toast message on click of close icon
     */
     $("#succ_msg_close").on('click',function() { 
        $(".succ-msg").hide();
    });

    /**
     * Trigger to close of success toast message when space and enter key is pressed
     */
    $("#succ_msg_close").on('keypress',$.proxy(function (evt) {
        evt = (evt) ? evt : window.event;
        let charCode = (evt.which) ? evt.which : evt.keyCode;
        if (charCode == 13 || charCode == 32) {
            $("#succ_msg_close").trigger('click');
            return false;
        }
    }, this));

    /**
     * Decline order by order id
     */
     $(".btn-decline-deny").on('click',function() {
        let additionalComments = $("#additional_comments").val();
        let orderId = $("#order_id").val();
        let orderDeclineAction = {
            orderId : orderId,
            action: 'declined',
            additionalComments: additionalComments
        };
        let setUrl = url.build('orderb2b/index/declineorder');
        $.ajax({
            url: setUrl,
            showLoader: true,
            type: "POST",
            dataType: 'json',
            data: orderDeclineAction,
            success: function (result) {
                $("#order-decline-modal-popup").modal('closeModal');
                window.location.href = url.build('orderb2b/revieworder/history');
            }
        });
    });
});
    
