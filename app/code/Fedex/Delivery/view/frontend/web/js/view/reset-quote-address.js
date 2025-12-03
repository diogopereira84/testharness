/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 define([
    'jquery',
    'mage/url',
    'Magento_Catalog/js/price-utils',
    'fedex/storage'
], function ($, urlBuilder, priceUtils,fxoStorage) {

    /*Trigger change to shipping*/
    $(document).on('click', '.location-message .checkout-location-shipping-step', function () {
        $("#change2shipping").trigger("click");
    });

    /*Ajax call to reset quote address and update order summary shipping and estimated total*/
    $(document).on('click', '.checkout-pickup-step,.checkout-shipping-step', function () {
        resetQuoteAddress();
    });

    /**
     * Reset quote address and call rate quote API
     *
     * @return void
     */
    function resetQuoteAddress() {

        let requestUrl = urlBuilder.build("delivery/index/resetquoteaddress");
        $.ajax({
            url: requestUrl,
            type: "POST",
            data: {},
            dataType: "json",
            showLoader: false,
            async: true,
            beforeSend: function () {
                $(".opc-summary-wrapper .modal-inner-wrap #opc-sidebar").prepend('<img class="opc-sidebar-loader" alt="Loading..." src="' + window.LoaderImgUrl + '">');
                $(".opc-block-summary .data.table.table-totals").hide();
            },
            complete: function () {
                $(".opc-summary-wrapper .modal-inner-wrap #opc-sidebar .opc-sidebar-loader").remove();
                $(".opc-block-summary .data.table.table-totals").show();
            },
            success: function () {
                $(".opc-summary-wrapper .modal-inner-wrap #opc-sidebar .opc-sidebar-loader").remove();
                $(".opc-block-summary .data.table.table-totals").show();
            },
        }).done(function (response) {
            if(window.e383157Toggle){
                fxoStorage.set('selectedShippingMethods', null);
            }else{
                localStorage.setItem('selectedShippingMethods', null);
            }
            if (typeof response !== 'undefined' && response.length < 1) {
                $('.error-container').removeClass('api-error-hide');
                $('.loadersmall').hide();

                return true;
            } else if (!response.hasOwnProperty("errors") || response.hasOwnProperty("alerts")){
                $('.error-container').addClass('api-error-hide');
            }

            if (response.hasOwnProperty("errors")) {
                $('.error-container').removeClass('api-error-hide');
                $('.message-container').text('System error, Please try again.');
            } else {
                handleResetQuoteAddressResponse(response);
            }
        });

    }

    /**
     * Handle Reset Quote Address Response
     *
     * @param {object} response
     * @return void
     */
    function handleResetQuoteAddressResponse(response) {
        let totalNetAmount = priceFormatWithCurrency(response.netAmount);
        let taxAmount = priceFormatWithCurrency(response.taxAmount);
        let shippingAmount = priceFormatWithCurrency(response.shippingAmount);

        /*Estimated total*/
        if(window.e383157Toggle){
            fxoStorage.set("EstimatedTotal", totalNetAmount);
        }else{
            localStorage.setItem("EstimatedTotal", totalNetAmount);
        }
        $(".opc-block-summary .grand.totals.incl .price").text(totalNetAmount);
        $(".opc-block-summary .grand.totals .amount .price").text(totalNetAmount);
        /*End estimated total*/

        /*Tax amount*/
        if(window.e383157Toggle){
            fxoStorage.set("TaxAmount", taxAmount);
        }else{
            localStorage.setItem("TaxAmount", taxAmount);
        }
        if (response.taxAmount == 0) {
            taxAmount = 'TBD';
        }
        $(".opc-block-summary .totals-tax .price").text(taxAmount);
        /*End tax amount*/

        /*Shipping amount*/
        if (response.shippingAmount == 0) {
            shippingAmount = 'TBD';
        }
        $(".opc-block-summary .totals.shipping.excl .price").text(shippingAmount);
        /*End shipping amount*/

        /*Shipping amount seller*/
        $('.opc-block-summary .shipping-method .method').text('');
        $('.opc-block-summary .shipping-method .price').text(shippingAmount);

        /*End shipping amount seller*/

        let method = 'shipping';
        if (window.e383157Toggle) {
            if (fxoStorage.get('pickupkey') === 'true') {
                method = 'pickup';
            }
        } else {
            if (window.localStorage.pickupkey == 'true') {
                method = 'pickup';
            }
        }
        hideShippingForPickup(method, response.shippingAmount);

    }

    /**
     * Hide shipping for pickup
     *
     * @param {string} method
     * @param {float} shippingPrice
     * @return void
     */
    function hideShippingForPickup(method, shippingPrice) {
        if (method == 'pickup' && shippingPrice == 0) {
            $(".opc-block-summary .table-totals .shipping.excl").hide();
            $(".opc-block-summary .shipping-method").hide();
        } else {
            $(".opc-block-summary .table-totals .shipping.excl").show();
            $(".opc-block-summary .shipping-method").show();
        }
    }

    /**
     * Price format
     *
     * @param {string} price
     * @return float
     */
    function priceFormatWithCurrency(price) {
        let formattedPrice = '';
        if (typeof (price) == 'string') {
            formattedPrice = price.replaceAll('$', '').replaceAll(',', '').replaceAll('(', '').replaceAll(')', '');
            formattedPrice = priceUtils.formatPrice(formattedPrice);
        } else {
            formattedPrice = priceUtils.formatPrice(price);
        }
        return formattedPrice;
    }
});
