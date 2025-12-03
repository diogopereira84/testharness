/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'jquery',
    'mage/url',
    'uploadToQuoteQueueListener',
    'uploadToQuoteDetail',
     'fedex/storage'
], function (
    $,
    url,
    uploadToQuoteQueueListener,
    uploadToQuoteDetail,
    fxoStorage
) {
    'use strict';

    /**
     * Add decline action to UploadtoQuoteActionQuote on click of decline button
     */
    $(".btn-decline-deny").on('click',function() {
        let currentdate = new Date();
        // Options for formatting the date and time in PST
        let options = {
            timeZone: 'America/Los_Angeles',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        };
        let formatter = new Intl.DateTimeFormat('en-US', options);
        let declinedDate = formatter.format(currentdate);
        let declinedTime = declinedDate.split(', ')[1];
        let quoteId = $("#uploadtoquote_quoteid").attr("data-quote-id");
        let reasonForDeclining = $("#reason_for_declining").val();
        let additionalComments = $("#additional_comments").val();
        let quoteActionSuccMsg = $("#quote-action-succ-msg").attr("data-declined-undo-msg");
        let uploadtoQuoteActionQueue = {
            action: 'declined',
            quoteId: quoteId,
            reasonForDeclining: reasonForDeclining,
            additionalComments: additionalComments,
            declinedDate: declinedDate,
            declinedTime:declinedTime
        };
        let setQueueUrl = url.build('uploadtoquote/index/setqueue');
        $.ajax({
            url: setQueueUrl,
            showLoader: true,
            type: "POST",
            dataType: 'json',
            data: uploadtoQuoteActionQueue,
            success: function (result) {
                if(result.Queue == 'declinedFailed') {
                    $('.err-msg .message').html(result.message);
                    $('.err-msg').show();
                    $('.btn-decline-cancel').trigger('click');
                    $(".detail-action-btn").hide();
                    $('html, body').animate({
                        scrollTop: $(".msg-container").offset().top
                    }, 1000);
                } else if (result.Queue) {
                    let requestedTime = new Date();
                    if(window.e383157Toggle){
                        fxoStorage.set("uploadToQuoteActionQueueResquestedTime", requestedTime.getTime());
                    }else{
                        localStorage.setItem("uploadToQuoteActionQueueResquestedTime", requestedTime.getTime());
                    }
                    $(' <tr><td class="date-title">Declined</td><td class="date-info">'+result.leftDate+' </td></tr>').insertAfter(".negotiable-qoute-created-at");
                    $('.progessbar-status-title').text("Declined");
                    $('.email-notification').text(result.declinedProgessBarMsg);
                    $('.btn-continue-checkout').empty().append("Continue To Checkout");
                    $('.btn-quote-approve').show();
                    $('.btn-quote-decline').hide();
                    $('#quote-action-succ-msg').text(quoteActionSuccMsg);
                    $('.succ-msg').show();
                    $('#undo_quote').attr("data-undo-action", "declined");
                    $('.btn-decline-cancel').trigger('click');
                    $('.progress-bar').css({'width':'100%'});
                    $('.store-review').addClass('quote-status-expired-and-declined');
                    $('.store-review').removeClass('quote-status-created quote-status-submitted-admin quote-status-submitted-customer quote-status-created');
                    $(".detail-action-btn").hide();
                    uploadToQuoteDetail.setNextStepforDeclineQuote();
                    uploadToQuoteQueueListener.initiateUploadToQuoteActionQueue();
                    $('html, body').animate({
                        scrollTop: $(".msg-container").offset().top
                    }, 1000);
                }
            }
        });
    });
});
