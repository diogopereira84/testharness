/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'jquery',
    'mage/url',
    'uploadToQuoteQueueListener',
     'fedex/storage'
], function (
    $,
    url,
    uploadToQuoteQueueListener,
    fxoStorage
) {
    'use strict';

    /**
     * Add decline action to UploadtoQuoteActionQuote on click of decline button
     */
    $(".btn-delete-submit").on('click',function() {
        let _this = this;
        let currentdate = new Date();
        let deletedDate = currentdate.getFullYear() + "-"
                            + (currentdate.getMonth()+1)  + "-"
                            + currentdate.getDate() + " "
                            + currentdate.getHours() + ":"
                            + currentdate.getMinutes() + ":"
                            + currentdate.getSeconds();

        let deletedTime = currentdate.toLocaleString(['en-US'], {
                hour: '2-digit',
                minute: '2-digit'
            }).toLowerCase();
        let quoteId = $("#uploadtoquote_quoteid").attr("data-quote-id");
        let itemId = $(_this).attr("data-itemid");
        let itemName = $(_this).attr("data-item-name");
        let undoSuccMsg = $("#quote-action-succ-msg").attr("data-delete-item-undo-msg");
        undoSuccMsg = undoSuccMsg.replace("%project_name%", itemName);
        let uploadtoQuoteActionQueue = {
            action: 'deleteItem',
            quoteId: quoteId,
            itemId: itemId,
            deletedDate: deletedDate,
            deletedTime: deletedTime
        };
        let setQueueUrl = url.build('uploadtoquote/index/setqueue');
        $.ajax({
            url: setQueueUrl,
            showLoader: true,
            type: "POST",
            dataType: 'json',
            data: uploadtoQuoteActionQueue,
            success: function (result) {
                if (result.Queue) {
                    let zeroDollorSkuWarning = false;
                    $.each( result.rateQuoteResponse.alerts, function( alertKey, alertValue ) {
                        if (alertValue.code == 'QCXS.SERVICE.ZERODOLLARSKU') {
                            zeroDollorSkuWarning = true;
                        }
                    });
                    let requestedTime = new Date();
                    if(window.e383157Toggle){
                        fxoStorage.set("uploadToQuoteActionQueueResquestedTime", requestedTime.getTime());
                    }else{
                        localStorage.setItem("uploadToQuoteActionQueueResquestedTime", requestedTime.getTime());
                    }
                    if (!zeroDollorSkuWarning) {
                        updateQuoteTotal(result);
                    }
                    let quoteTotalItem = (result.rateQuoteResponse.rateQuote.
                        rateQuoteDetails[0].productLines).length;
                    let cartSummaryItemCount = 'FedEx Office ('+quoteTotalItem+' Item)';
                    if (quoteTotalItem > 1) {
                        cartSummaryItemCount = 'FedEx Office ('+quoteTotalItem+' Items)';
                    }
                    $('#column_items_count_cart_summary').text(cartSummaryItemCount);
                    $('#column_items_count').text('Items('+quoteTotalItem+')');
                    $(".btn-delete-cancel").trigger('click');
                    $('#quote-action-succ-msg').text(undoSuccMsg);
                    $('.err-msg').hide();
                    $('.succ-msg').show();
                    $('#undo_quote').attr("data-undo-action", "deleteItem");
                    $('#undo_quote').attr("data-deleted-item-id", itemId);
                    $('#item_row_id_'+itemId).next("tr").remove();
                    $('#item_row_id_'+itemId).remove();
                    uploadToQuoteQueueListener.initiateUploadToQuoteActionQueue();
                } else {
                    $(".btn-delete-cancel").trigger('click');
                    $('.succ-msg').hide();
                    $('.err-msg').show();
                }
                $('html, body').animate({
                    scrollTop: $(".msg-container").offset().top
                }, 1000);
            }
        });
    });

    /**
     * Update quote total
     *
     * @return void
     */
    function updateQuoteTotal(result)
    {
        let itemTotal = parseFloat(result.rateQuoteResponse.rateQuote.rateQuoteDetails[0].productsTotalAmount);
        let taxAmount = parseFloat(result.rateQuoteResponse.rateQuote.rateQuoteDetails[0].taxAmount);
        let totalDiscountAmount = parseFloat(result.rateQuoteResponse.rateQuote.rateQuoteDetails[0].totalDiscountAmount);
        let totalAmount = parseFloat(result.rateQuoteResponse.rateQuote.rateQuoteDetails[0].totalAmount);
        $("#column_item_total").text("$"+itemTotal);
        let displayTaxAmount = 'TBD';
        if (taxAmount > 0) {
            displayTaxAmount = "$"+taxAmount;
        }
        $("#column_tax").text(displayTaxAmount);
        let displayTotalDiscountAmount = '-';
        if (displayTaxAmount > 0) {
            displayTotalDiscountAmount = "-$"+totalDiscountAmount;
        }
        $("#column_discount").text(displayTotalDiscountAmount);
        $("#column_total").text("$"+totalAmount);
    }
});
