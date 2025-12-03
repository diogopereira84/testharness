/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/url',
    'uploadToQuoteQueueListener',
    'uploadToQuoteDetail',
     'fedex/storage'
], function (
    $,
    modal,
    urlBuilder,
    uploadToQuoteQueueListener,
    uploadToQuoteDetail,
    fxoStorage
) {
    'use strict';

    /**
     * Decline model options
     */
    let declineModelOptions = {
        type: 'popup',
        innerScroll: true,
        modalClass: 'quote-decline-popup',
        buttons: []
    };

    /**
     * Request Change model options
     */
    let requestChangeModelOptions = {
        type: 'popup',
        innerScroll: true,
        modalClass: 'request-change-popup',
        buttons: []
    };

    /**
     * Request Change model options
     */
     let reviewRequestModelOptions = {
        type: 'popup',
        innerScroll: true,
        modalClass: 'review-request-popup',
        buttons: []
    };

    /**
     * Delete model options
     */
    let deleteItemModelOptions = {
        type: 'popup',
        innerScroll: true,
        modalClass: 'delete-item-popup',
        buttons: []
    };

    /**
     * Open decline model on click on decline button
     */
    $(".btn-quote-decline").on('click',function(){
        $("#decline-model-popup").modal(declineModelOptions).modal('openModal');
        $(".decline-reason-list").addClass("decline-hide");
    });

    /**
     * Open decline reason list on click of decline reason text box
     */
    $("#reason_for_declining, .decline-down-arrow").on('click',function(){
        $(".decline-reason-list").toggleClass("decline-hide");
    });

    /**
     * Enable, disable and make additional comments required or optional based on list
     * selection
     */
    $(".decline-reason-list li").on('click',function(){
        let _this = this;
        let declineReason = $(_this).text();
        let additionalComments = $("#additional_comments");
        let btnDecline = $(".btn-decline-deny");
        let commentOptional = $(".decline-comment-opational");

        $("#reason_for_declining").val(declineReason);
        $(".decline-reason-list").toggleClass("decline-hide");
        additionalComments.removeAttr("readonly");

        if (declineReason == 'Other' && additionalComments.val().trim() == '') {
            btnDecline.attr("disabled", "disabled");
            btnDecline.text("Deny Quote");
            commentOptional.text("");
        } else {
            btnDecline.removeAttr("disabled", "disabled");
            btnDecline.text("Decline Quote");
            commentOptional.text("(Optional)");
        }
    });

    /**
     * Enable or disable decline button based message check
     */
    $("#additional_comments").on('keyup',function(){
        let _this = this;
        let additionalComments = $(_this).val().trim();
        let btnDecline = $(".btn-decline-deny");
        let reasonForDeclining = $("#reason_for_declining").val();

        $(".decline-additional-comment-character-count").text(400 - additionalComments.length);

        if (additionalComments.length == 0 && reasonForDeclining == 'Other') {
            btnDecline.attr("disabled", "disabled");
            btnDecline.text("Deny Quote");
        } else if (reasonForDeclining != ''){
            btnDecline.removeAttr("disabled");
            btnDecline.text("Decline Quote");
        }
    });

    /**
     * Open Change Request Popup model on click on Request Change link in quote detail page
     */
    $(".request-change-link").on('click',function() {
        $("#request_change_popup").modal(requestChangeModelOptions).modal('openModal');
        $(".request-change-popup").find(".action-close").attr({'tabindex': 0});
        $(".quoted-item-list").addClass("item-list-hide");
        //get details from the quote detail page
        let _this = this;
        let productId = $(_this).attr("data-productitemid");
        let productName = $(_this).attr("data-productname");
        let productImgUrl = $(_this).attr("src");
        //set values in modal pop up
        $("#product_detail").attr("data-productid", productId);
        $("#product_detail").attr("data-productname", productName);
        $("#product_detail").attr("data-productimgurl", productImgUrl);
        $("#quoted_product").val(productName);
        $("#product_name").text('Request Change for ' + productName);
        //show green message on open dropdown
        showChangeRequestMsg();
        //hide review button when no si
        disableReviewBtn();
        //form product name for options
        formateProductOptions();
        //set the formated product name in text
        toFormateProductName(_this, '#quoted_product');
    });

    /**
     * Open dropdown when multiple quoted product in request change popup
     */
    $("#quoted_product, .request-change-down-arrow").on('click',function() {
        $(".quoted-item-list").toggleClass("item-list-hide");
    });

    /**
     * Open dropdown when multiple quoted product in request change popup
     */
    $(document).on('keypress', '.request-change-down-arrow, #quoted_product', function (e) {
        let keycode = (e.keyCode ? e.keyCode : e.which);
        if(keycode  == 13 || keycode  == 32){
            $(".quoted-item-list").toggleClass("item-list-hide");
        }
    });

    /**
     * Show Hide dropdown when  multiple quoted product in request change popup
     */
    $(".quoted-item-list li").on('click keypress',function(e) {
        let keycode = (e.keyCode ? e.keyCode : e.which);
        if (keycode == 13 || keycode  == 32) {
            $(this).trigger("click", true);
            $(".quoted-item-list").toggleClass("item-list-hide");
        }
        let _this = this;
        let productName = $(_this).attr("data-productname");
        $("#quoted_product").val(productName);
        $("#product_name").text('Request Change for '+productName);
        $(".quoted-item-list").toggleClass("item-list-hide");
        //to formate product name
        toFormateProductName(_this, '#quoted_product');
    });

    /**
     * Enable or disable request change button when enter SI
     */
     $("#print_instructions").on('keyup',function(){
        let _this = this;
        let si = $(_this).val().trim();
        let btnCahngeRequest = $(".btn-request-change");
        $(".print-instructions-character-count").text(400 - si.length);
        if (si.length > 0) {
            btnCahngeRequest.removeAttr("disabled");
        } else {
            btnCahngeRequest.attr("disabled", "disabled");
        }
    });

    /**
     * Close popup on click of cancel
     */
    $(".btn-decline-cancel, .btn-request-change-cancel, .btn-review-request-cancel, .btn-delete-cancel").on('click',function(){
        if($(this).hasClass('btn-decline-cancel')) {
            $("#decline-model-popup").modal('closeModal');
        }else if($(this).hasClass('btn-request-change-cancel')) {
            $("#request_change_popup").modal('closeModal');
        }else if($(this).hasClass('btn-review-request-cancel')) {
            $("#review_request_popup").modal('closeModal');
        } else if($(this).hasClass('btn-delete-cancel')) {
            $("#delete_item_popup").modal('closeModal');
        }
    });

    /**
     * Trigger request change on click of request change CTA
     */
    $(".btn-single-item, .btn-review-request").on('click',function(){
        let _this = this;
        let formData = $('#request-change-form');
        if($(_this).hasClass('btn-single-item')) {
            formData = $('#request-change-form');
        } else if($(_this).hasClass('btn-review-request')){
            formData = $('#review-request-form');
        }
        triggerRequestChange(this, formData);
    });

    /**
     * Trigger review request modal
     */
    $(document).on('click','.btn-multiple-item',function() {
        triggerReviewRequest(this);
    });

    function triggerReviewRequest(btnObj){
        let reviewRequestBtn = $(btnObj);
        reviewRequestBtn.prop('disabled', true);
        $('body').trigger('processStart');
        let reviewRequestUrl = urlBuilder.build('uploadtoquote/index/reviewrequest');
        let siProductItems = window.e383157Toggle
            ? fxoStorage.get('siProductItems')
            : JSON.parse(localStorage.getItem('siProductItems'));
        $.ajax({
            type: 'post',
            url: reviewRequestUrl,
            data: {siItems : siProductItems},
            dataType: 'json',
            cache: false,
            success: function(data) {
                $("#review_request_popup").html(data.block);
                $("#request_change_popup").modal('closeModal');
                $('body').trigger('processStop');
                $("#review_request_popup").modal(reviewRequestModelOptions).modal('openModal');
            }
        });
    }


    /**
     * Trigger request change
     */
    function triggerRequestChange(btnObj, formData){
        let submitBtn = $(btnObj);
        submitBtn.prop('disabled', true);
        let setQueueUrl = urlBuilder.build('uploadtoquote/index/setqueue');
        let quoteId = $("#uploadtoquote_quoteid").attr("data-quote-id");
        let itemId = $("#product_detail").attr("data-productid");
        let si = $("#print_instructions").val();
        let items = [
            {
                item_id : itemId,
                si : si
            }
        ];
        let currentdate = new Date();
        let changeRequestedDate = currentdate.getFullYear() + "-"
                            + (currentdate.getMonth()+1)  + "-"
                            + currentdate.getDate() + " "
                            + currentdate.getHours() + ":"
                            + currentdate.getMinutes() + ":"
                            + currentdate.getSeconds();
        let changeRequestedTime = currentdate.toLocaleString(['en-US'], {
                hour: '2-digit',
                minute: '2-digit'
            }).toLowerCase();
        let quoteActionSuccMsg = $("#quote-action-succ-msg").attr("data-change-request-undo-msg");
        let uploadtoQuoteActionQueue = {
            action: 'changeRequested',
            quoteId: quoteId,
            items: items,
            changeRequestedDate: changeRequestedDate,
            changeRequestedTime: changeRequestedTime
        };
        $.ajax({
            type: 'post',
            url: setQueueUrl,
            showLoader: true,
            data: uploadtoQuoteActionQueue,
            dataType: 'json',
            cache: false,
            success: function(result) {
                $('.btn-request-change-cancel').trigger('click');
                if (result.Queue) {
                    let requestedTime = new Date();
                    if(window.e383157Toggle){
                        fxoStorage.set("uploadToQuoteActionQueueResquestedTime", requestedTime.getTime());
                    }else{
                        localStorage.setItem("uploadToQuoteActionQueueResquestedTime", requestedTime.getTime());
                    }
                    $('#quote-action-succ-msg').text(quoteActionSuccMsg);
                    $('#undo_quote').attr("data-undo-action", "changeRequested");
                    $('#undo_quote').attr("data-change-requested-item-ids", result.Queue[0].itemIds);
                    $('.succ-msg').show();
                    $('.err-msg').hide();
                    $('.progessbar-status-title').text("Change requested");
                    $('.email-notification').text(result.requestChangeProgessBarMsg);
                    $('.btn-quote-approve').hide();
                    $('.btn-quote-decline').hide();
                    $('.preview_btn').hide();
                    $('#undo_quote').attr("data-undo-action", "declined");
                    $('.progress-bar').css({'width':'62%'});
                    $('.store-review').addClass('quote-status-submitted-customer');
                    $('.store-review').removeClass('quote-status-created');
                    $('.store-review').removeClass('quote-status-submitted-admin');
                    $('.quote-note-container').remove();
                    $('.btn-action-container').removeClass('quote-note-visible');
                    uploadToQuoteDetail.addGreenCheckmark('.next-steps-div-one');
                    changeQuoteDetailsView(result.Queue[0].items);
                    uploadToQuoteQueueListener.initiateUploadToQuoteActionQueue();
                } else {
                    $('.succ-msg').hide();
                    $('.err-msg').show();
                }
                $('html, body').animate({scrollTop: 0}, 1000);
            }
        });
    }

    /**
     * To show and hide 'Save Draft' message
     */
     function changeQuoteDetailsView(items) {
        let priceDashformat = '$--.--';
        let itemRowIdInit = '#item_row_id_';
        let orderSummaryClass = '.order-transaction-summary';
        let itemRequestMsg = '<tr class="quote-req-change-inline-msg"><td colspan="5">'+
                '<div class="quote-req-change-info">'+
                '<div class="request-info-icon-container">'+
                '<img class="request-info-change-icon" alt="Request Change" src="'+
                $('#undo_quote').attr("data-request-chnage-msg-icon")+'">'+
                '</div><p class="quote-req-change-msg">You requested a change: "';
        let itemRequestMsgOpen = '<div class="quote-request-change-inline-msg-info">'+
                '<div class="request-info-icon-container">'+
                '<img class="request-info-change-icon" alt="Request Change" src="'+
                $('#undo_quote').attr("data-request-chnage-msg-icon")+'">'+
                '</div><p class="quote-req-change-msg">You requested a change: "';
        let itemRequestMsgMob = '<div class="quote-request-change-inline-msg-info-mobile">'+
                '<div class="request-info-icon-container">'+
                '<img class="request-info-change-icon" alt="Request Change" src="'+
                $('#undo_quote').attr("data-request-chnage-msg-icon")+'">'+
                '</div><p class="quote-req-change-msg">You requested a change: "';
        $(orderSummaryClass).find('#column_item_total').find('.price').text(priceDashformat);
        $(orderSummaryClass).find('#column_discount').text('-');
        $(orderSummaryClass).find('#column_total').find('.price').text(priceDashformat);
        $(orderSummaryClass).find('#column_tax').find('.price').text('TBD');
        $.each(items, function (i, e) {
            let itemrowId = itemRowIdInit+items[i].item_id;
            $(itemrowId).find('.product-item-name').
            after('<div class="upload-to-quote-details-store-review-lable">Needs Store Review</div>');
            $(itemrowId).find('.detail-action-btn').hide();
            $(".configurater-product-attr-"+items[i].item_id).find(".cart-table-sku").hide();
            $(itemrowId).find('.product-price').find('.price').remove();
            $(itemrowId).find('.product-price').find('.quote-product-price').after(priceDashformat);
            $(itemrowId).find('.product-total').find('.price').remove();
            $(itemrowId).find('.product-total').find('.quote-product-subtotal').after(priceDashformat);
            $(itemrowId).find('.product-dis').find('.quote-product-discount').after('-');
            if ($(window).width() <= 320) {
                $(itemrowId).next('tr.quote-req-change-inline-msg').hide();
                $(itemrowId).find('.product-img-div').after(itemRequestMsgMob+items[i].si+'"</p></div>');
            }
            else {
                $(itemrowId).find('.product-img-div').find('.quote-request-change-inline-msg-info-mobile').hide();
                $(itemrowId).after(itemRequestMsg+items[i].si+'"</p></div></td></tr>');
                $('#configurater-product-attr-'+items[i].item_id+' > ul').
                after(itemRequestMsgOpen+items[i].si+'"</p></div>');
            }
        });
    }

    /**
     * Add change request dropdown items in array with detail
     */
    $(".quoted-item-list li").on('click', function () {
        let _this = this;
        let productName = $(_this).attr("data-productname");
        let productId = $(_this).val();
        let productImgUrl = $(_this).attr("src");
        //set productId in data attribute
        $("#product_detail").attr("data-productid", productId);
        $("#product_detail").attr("data-productname", productName);
        $("#product_detail").attr("data-productimgurl", productImgUrl);
        //set si on change of dropdown
        let siProductItems = window.e383157Toggle
            ? fxoStorage.get('siProductItems')
            : JSON.parse(localStorage.getItem('siProductItems'));
        // get si by productId
        let simsg = getSiByProductId(siProductItems, productId);
        //to select the option and add value in si textarea
        $("#print_instructions").val(simsg);
        //set msg count
        let siMsgcount = (simsg) ? simsg.length : 0;
        $(".print-instructions-character-count").text(400 - siMsgcount);
        //hide review button when no si is not available
        disableReviewBtn();
    });

    /**
     * Update the special instruction in array element on keyup
     */
    $("#print_instructions").on('keyup', function () {
        let _this = this;
        let si = $(_this).val().trim();
        let siProductItems = window.e383157Toggle
            ? fxoStorage.get('siProductItems')
            : JSON.parse(localStorage.getItem('siProductItems'));
        let productArr = siProductItems || [];
        let productId = $("#product_detail").attr("data-productid");
        let productName = $("#product_detail").attr("data-productname");
        let productImgUrl = $("#product_detail").attr("data-productimgurl");

        let newData = { "productId": productId, "productName": productName, "si": si, "productImgUrl": productImgUrl };

        //update the special instructions
        updatesi(productArr, newData, si);
        //remove empty si items
        toRemoveEmptySi(productArr);
        //show and hide draft message
        showDraftMsg();
        //show reaquest change message
        showChangeRequestMsg();
    });

    /**
     * To show and hide 'Save Draft' message
     */
    function showDraftMsg() {
        setTimeout(function () {
            $(".save-draft").fadeIn(500, function () {
                $(this).delay(500).fadeOut(500);
            });
        }, 500);
    }

    /**
     * Remove empty si items
     */
    function toRemoveEmptySi(productArr) {
        productArr = productArr.filter(function (item) {
            return item.si !== "";
        });
        if (window.e383157Toggle) {
            fxoStorage.set("siProductItems", productArr);
        } else {
            localStorage.setItem("siProductItems", JSON.stringify(productArr));
        }
    }

    /**
     * Function to update the 'special instructions' based on productId
     */
    function updatesi(productArr, newData, si) {
        //for empty array add new one
        if (productArr.length === 0 && !newData.si) {
            productArr.push(newData);
            if (window.e383157Toggle) {
                fxoStorage.set("siProductItems", productArr);
            } else {
                localStorage.setItem("siProductItems", JSON.stringify(productArr));
            }
        } else {
            let found = false;
            $.each(productArr, function (i, e) {
                if (e.productId === newData.productId) {
                    // ProductId found, update the 'si' property
                    productArr[i].si = si;
                    if (window.e383157Toggle) {
                        fxoStorage.set("siProductItems", productArr);
                    } else {
                        localStorage.setItem("siProductItems", JSON.stringify(productArr));
                    }
                    found = true;
                    return false;
                }
            });
            if (!found) {
                // ProductId not found, add a new object to the array
                productArr.push(newData);
                if (window.e383157Toggle) {
                    fxoStorage.set("siProductItems", productArr);
                } else {
                    localStorage.setItem("siProductItems", JSON.stringify(productArr));
                }
            }
        }
    }

    /**
     * Function to add dynamically show/hide change requested msg
     */
    function showChangeRequestMsg() {
        //code run only bigger screen
        if (window.innerWidth > 767) {
            let siProductItems = window.e383157Toggle
                ? fxoStorage.get('siProductItems')
                : JSON.parse(localStorage.getItem('siProductItems'));
            // Hide all spans initially
            $('ul.quoted-item-list span.change-request-check').css('display', 'none');
            $.each(siProductItems, function (i, e) {
                let targetLi = $('ul.quoted-item-list li[value="' + e.productId + '"]');
                // Check if the li element was found
                if (targetLi.length > 0) {
                    // Show the specific span when the li element is found
                    targetLi.next('span.change-request-check').css('display', 'block');
                }
            });
        } else {
            $('ul.quoted-item-list span.change-request-check').css('display', 'none');
        }
    }

    /**
     * Function to get si by productId
     */
    function getSiByProductId(productArr, productId) {
        let si;
        $.each(productArr, function (i, e) {
            if (e.productId == productId) {
                si = productArr[i].si;
                return false;
            }
        });
        return si;
    }

    /**
     * To clear localstorage on click
     */
    $('body').on('click', '.btn-request-change-cancel, .action-close', function () {
        clearProductDetail();
    });

    /**
     * Modal close to clear local storage 'siProductItems' data
     */
    $("body").click(function(e){
        let checkclass = e.target.className ;
        if (checkclass == "modals-overlay"){
            clearProductDetail();
        }
    });

    /**
     * Clear local storage 'siProductItems' data on load page
     */
    $(document).ready(function () {
        clearProductDetail();
    });

    /**
     * Call functions on resize page
     */
    $(window).resize(function () {
        showChangeRequestMsg();
        formateProductOptions();
        let _this = $('#quoted_product');
        toFormateProductName(_this, '#quoted_product');
    });

    /**
     * Function to clear local storage
     */
    function clearProductDetail() {
        if (window.e383157Toggle) {
            fxoStorage.delete('siProductItems');
        } else {
            localStorage.removeItem('siProductItems');
        }
        $("#print_instructions").val('');
    }

    /**
     * Function to hide button if no special instructions
     */
    function disableReviewBtn() {
        let btnCahngeRequest = $(".btn-request-change");
        let si = $("#print_instructions").val().trim();
        if (si.length > 0) {
            btnCahngeRequest.removeAttr("disabled");
        } else {
            btnCahngeRequest.attr("disabled", "disabled");
        }
    }

    /**
     * Function to formate the options of dropdown
     */
    function formateProductOptions() {
        $('.quote-item-li').each(function () {
            let _this = this;
            toFormateProductName(_this);
        });
    }

    /**
     * Function to formate the product name
     */
    function toFormateProductName(_this, toSetId = '') {
        let name = $(_this).attr("data-productname");
        let lastWord;
        let firstChars;
        if (name && name.length > 46 && window.innerWidth > 767) {
            firstChars = name.substring(0, 19);
            lastWord = name.substr(-23);
            if (toSetId) {
                $(toSetId).val(firstChars + '...' + lastWord);
            } else {
                $(_this).text(firstChars + '...' + lastWord);
            }
        }
        else if (name && name.length > 23 && window.innerWidth < 768) {
            firstChars = name.substring(0, 13);
            lastWord = name.substr(-6);
            if (toSetId) {
                $(toSetId).val(firstChars + '...' + lastWord);
            } else {
                $(_this).text(firstChars + '...' + lastWord);
            }
        } else {
            if (toSetId) {
                $(toSetId).val(name);
            } else {
                $(_this).text(name);
            }
        }
    }

    /**
     * Open delete model on click on delete link of item
     */
    $(".delete-item-link").on('click',function() {
        let _this = this;
        let itemId = $(_this).attr("data-itemid");
        let itemName = $(this).attr("date-item-title");
        let ModelTitle = 'Are you sure you want to delete this "'+itemName+'" item?';
        $(".delete-item-title").text(ModelTitle);
        $("#delete_item_popup").modal(deleteItemModelOptions).modal('openModal');
        $(".btn-delete-submit").attr("data-itemid", itemId);
        $(".btn-delete-submit").attr("data-item-name", itemName);
    });
});
