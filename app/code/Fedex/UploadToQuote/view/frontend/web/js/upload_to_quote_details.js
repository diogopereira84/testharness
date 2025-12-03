/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'jquery',
    'mage/url',
    'fedex/storage',
    'emptyCartModalPopup',
    'Magento_Customer/js/customer-data'
], function (
    $,
    urlBuilder,
    fxoStorage,
    emptyCartModalOptions,
    customerData
) {
    'use strict';

    let isFusebidToggleEnabled = typeof (window.checkout.is_fusebid_toggle_enabled) !== "undefined" && window.checkout.is_fusebid_toggle_enabled !== null ? window.checkout.is_fusebid_toggle_enabled : false;

    let myQuotesMaitenaceFixToggle = typeof (window.checkout.my_quotes_maitenace_fix_toggle) !== "undefined" && window.checkout.my_quotes_maitenace_fix_toggle !== null ? window.checkout.my_quotes_maitenace_fix_toggle : false;

    $(document).on('click', '.show-detail', function (e) {
        e.preventDefault();
        let isExpanded = $(this).attr('aria-expanded');
        let id = $(this).attr('data-item-id');
        let txt = $('.configurater-product-attr-' + id).is(':visible') ? 'Show details' : 'Hide details';

        if (isExpanded == "true") {
            $(this).attr('aria-expanded', 'false');
        } else {
            $(this).attr('aria-expanded', 'true');
        }

        $('#show-detail-' + id).text(txt);
        $(this).toggleClass('icon-arrow');
        $('.configurater-product-attr-' + id).slideToggle(0);
        $(this).closest('tr').next().closest('.quote-req-change-inline-msg').slideToggle(0);
        if (parseInt($(window).width()) >= 320 && parseInt($(window).width()) <= 767) {
            $(this).closest('tr').next().closest('.quote-req-change-inline-msg').css('display','none');
        }
    });

    $(document).on('click', '.show-detail.icon-arrow', function (e) {
        if (parseInt($(window).width()) >= 320 && parseInt($(window).width()) <= 767) {
            $(this).closest('tr').next().closest('.quote-req-change-inline-msg').css('display','revert');
        }
    });

    //to add dynamically green check mark
    $(window).on('load', function () {
        $('body').trigger('processStop');
        let status = window.quoteStatus;

        if (status == 'submitted_by_customer') {
            addGreenCheckmark('.next-steps-div-one');
        } else if (status == 'submitted_by_admin') {
            addGreenCheckmark('.next-steps-div-one');
            addGreenCheckmark('.next-steps-div-two');
        } else if (status == 'ordered') {
            addGreenCheckmark('.next-steps-div-one');
            addGreenCheckmark('.next-steps-div-two');
            addGreenCheckmark('.next-steps-div-three');
        } else if (status == 'declined' || status == 'closed') {
            addGreenCheckmark('.next-steps-div-one');
            addGreenCheckmark('.next-steps-div-two');
            addBlackCrossMark('.next-steps-div-three');
        } else if (status == 'expired') {
            addBlackCrossMark('.next-steps-div-one');
            addBlackCrossMark('.next-steps-div-two');
            addBlackCrossMark('.next-steps-div-three');
        }

    });

    /**
     * Approve quote by click on approve button
     */
    $(document).on('click', '.btn-quote-approve', function (e) {
        let _this = this;
        let quoteId = $(_this).attr("data-quote-id");
        let locationId = $(_this).attr("data-location-id");
        let isnonNegotiableQuotePresentInCart = $(_this).attr("data-non-negotiable-items-count");
        let addToCartUrl =  urlBuilder.build("uploadtoquote/index/addtocart");
        $('.err-msg').hide();
        if (isFusebidToggleEnabled && isnonNegotiableQuotePresentInCart == "1") {
                $("#retail-empty-cart-modal-popup").modal(emptyCartModalOptions).modal('openModal');
                return;
        }
        $.ajax({
            url: addToCartUrl,
            showLoader: true,
            type: "POST",
            data: {
                quoteId: quoteId
            },
            success: function (response) {
                if (response.isItemAdded) {
                    if (locationId && isFusebidToggleEnabled) {
                        let centerDetailsUrl = urlBuilder.build("delivery/index/centerdetails");
                        $.ajax({
                            url: centerDetailsUrl,
                            showLoader: true,
                            type: "POST",
                            data: {
                                locationId: locationId
                            },
                            success: function (response) {
                                let postalCode = typeof (response.address.postalCode) != 'undefined' && response.address.postalCode != null ? response.address.postalCode : false;
                                if (postalCode) {
                                    let qouteLocationDetails = {
                                        quoteId: quoteId,
                                        locationId: locationId,
                                        postalCode: response.address.postalCode
                                    }
                                    if (window.e383157Toggle) {
                                        fxoStorage.set("pickupkey", true);
                                        fxoStorage.set("shipkey", false);
                                        fxoStorage.set("qouteLocationDetails", JSON.stringify(qouteLocationDetails));
                                    } else {
                                        localStorage.setItem("pickupkey", true);
                                        localStorage.setItem("shipkey", false);
                                        localStorage.setItem("qouteLocationDetails", JSON.stringify(qouteLocationDetails));
                                    }
                                }
                                window.location.href = urlBuilder.build('checkout');
                            }
                        });
                    } else {
                        window.location.href = urlBuilder.build('checkout/cart/');
                    }
                } else {
                    $('.err-msg .message').html(response.message);
                    $('.err-msg').show();
                }
            }
        });
    });
     if (isFusebidToggleEnabled) {
         $(document).on('click', '.btn-goto-cart', function (e) {
            e.preventDefault();
            window.open(urlBuilder.build('checkout/cart'), '_blank');
         });
     }

    /**
     * Close success message on click of close icon
     */
    $("#succ_msg_close").on('click',function() {
        $(".succ-msg").hide();
    });

    /**
     * Trigger to close of success message when space and enter key is pressed
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
     * Close success message on click of close icon
     */
     $("#erro_msg_close").on('click',function() {
        $(".err-msg").hide();
    });

    /**
     * Trigger to close of error message when space and enter key is pressed
     */
     $("#erro_msg_close").on('keypress',$.proxy(function (evt) {
        evt = (evt) ? evt : window.event;
        let charCode = (evt.which) ? evt.which : evt.keyCode;
        if (charCode == 13 || charCode == 32) {
            $("#erro_msg_close").trigger('click');
            return false;
        }
    }, this));

    /**
     * Stop queue on click of undo
     */
    $("#undo_quote").on('click',function() {
        let quoteId = $("#uploadtoquote_quoteid").attr("data-quote-id");
        let undoAction = $(this).attr("data-undo-action");
        let itemId = $(this).attr("data-deleted-item-id").trim();
        let changeRequestedItemIds = $(this).attr("data-change-requested-item-ids").trim();
        let undoUrl =  urlBuilder.build("uploadtoquote/index/undoquoteactionqueue");
        $.ajax({
            url: undoUrl,
            showLoader: true,
            type: "POST",
            data: {
                undoAction: undoAction,
                quoteId: quoteId,
                itemId: itemId,
                changeRequestedItemIds: changeRequestedItemIds
            },
            success: function (response) {
                if (response.undoAction) {
                    if(window.e383157Toggle){
                        fxoStorage.delete("uploadToQuoteActionQueueResquestedTime");
                    }else{
                        localStorage.removeItem("uploadToQuoteActionQueueResquestedTime");
                    }
                    window.location.href = window.location.href;
                }
            }
        });

    });

    /**
     * Trigger to undo when space and enter key is pressed
     */
     $("#undo_quote").on('keypress',$.proxy(function (evt) {
        evt = (evt) ? evt : window.event;
        let charCode = (evt.which) ? evt.which : evt.keyCode;
        if (charCode == 13 || charCode == 32) {
            $("#undo_quote").trigger('click');
            return false;
        }
    }, this));

    /**
     * Redirect to quote history page on click on My Quotes link
     */
     $(".next-step-contact-info a").on('click',function(e) {
        if (myQuotesMaitenaceFixToggle) {
            e.preventDefault();
            let companyExtensionUrl = $(".next-step-contact-info").attr("data-company-extention-url");
            let quoteHistoryUrl =  urlBuilder.build(companyExtensionUrl+"uploadtoquote/index/quotehistory/");
            location.href = quoteHistoryUrl;
        }
    });

    /**
     * Add green check mark
     *
     * @return void
     */
    function addGreenCheckmark(spanClass) {
        $(spanClass).find('span').remove();
        $(spanClass).prepend('<span class="custom-checkbox"><span class="check-tik"></span></span>');
    }

    /**
     * Add black cross mark
     *
     * @return void
     */
    function addBlackCrossMark(spanClass) {
        $(spanClass).find('span').remove();
        $(spanClass).prepend('<span class="black-cross-mark"></span>');
    }

    /**
     * Set next step for declined quote
     *
     * @return void
     */
    function setNextStepforDeclineQuote() {
        addGreenCheckmark('.next-steps-div-one');
        addGreenCheckmark('.next-steps-div-two');
        addBlackCrossMark('.next-steps-div-three');
    }

    /**
     * Return function
     */
    return {
        setNextStepforDeclineQuote: setNextStepforDeclineQuote,
        addGreenCheckmark: addGreenCheckmark
    };
});
