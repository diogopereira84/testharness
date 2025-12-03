/*
 * @category Fedex
 * @package Fedex_Rate
 * @copyright Fedex (c) 2021.
 * @author Iago Lima <ilima@mcfadyen.com>
 */

define([
    'jquery',
    'mage/storage',
    'mage/template',
    'domReady!'
], function($, storage, mageTemplate, doc) {
    'use strict';
    let priceApiRequest,
        priceBoxTemplate = mageTemplate('#price-box-template'),
        priceBoxErrorTemplate = mageTemplate('#price-box-error-template'),
        ratePriceUrl  = 'rest/V1/rate/product',
        tokenizeUrl  = 'rest/V1/rate/tokenize',
        key = 0,
        inProgressRequests = 0,
        statusFlag = 0,
        defaultPrice =  {
            net_amount: '--',
            gross_amount: '--',
            qty: '--',
            unit: {
                net_amount: '--',
                gross_amount: '--',
            },
            amount_saved: false
        };

    let priceRateApi = {
        callRateApiAjax: function(serializedProductInstance) {
            inProgressRequests++;
            statusFlag = 0;
            $('.pricing-skeleton').removeClass('hide');
            if($('#price-box-error').length) {
                $('#price-box-error').remove();
            }
            if($('#price-box').length) {
                $('#price-box').remove();
            }

            var productsList = [];
            productsList.push(serializedProductInstance);

            let productsPayload = {
                products: {},
                validateContent: false
            };
            productsPayload.products = productsList;
            window.productsPayload = productsPayload;

            if(priceApiRequest) {
                priceApiRequest.abort();
            }

            priceApiRequest = storage.post(
                ratePriceUrl,
                JSON.stringify({rateRequest: productsPayload})
            );

            return priceApiRequest
        },

        callRateApiAjaxAsync: async function(serializedProductInstance) {
            inProgressRequests++;
            statusFlag = 0;
            $('.pricing-skeleton').removeClass('hide');
            if($('#price-box-error').length) {
                $('#price-box-error').remove();
            }
            if($('#price-box').length) {
                $('#price-box').remove();
            }

            var productsList = [];
            productsList.push(serializedProductInstance);

            let productsPayload = {
                products: {},
                validateContent: false
            };
            productsPayload.products = productsList;
            window.productsPayload = productsPayload;

            if(priceApiRequest) {
                priceApiRequest.abort();
            }

            return await storage.post(
                ratePriceUrl,
                JSON.stringify({rateRequest: productsPayload})
            );
        },

        applyPriceBox: function(result) {
            inProgressRequests--;
            let price = {};
            if(result) {
                var result = result instanceof Object ? result.response : JSON.parse(result);

                // B-1173348 - Fix JS Errors - Cannot read property 'output' of undefined
                if(result != undefined && !result.errors && result.output != undefined && result.output.rate != undefined
                    && result.output.rate.rateDetails != undefined) {
                    result = !result.output ? result.response : result;
                    var rateDetails = result.output.rate.rateDetails.shift();
                    if (rateDetails) {

                        if (rateDetails.netAmount)
                            price.net_amount = rateDetails.netAmount;

                        if (rateDetails.totalDiscountAmount !== '$0.00') {

                            let discountAmount = parseFloat(rateDetails.totalDiscountAmount.replace(/[^0-9.]/g, ''));
                            if (discountAmount) {

                                price.amount_saved = discountAmount.toLocaleString('en-US', {
                                    style: 'currency',
                                    currency: 'USD'
                                });
                                price.gross_amount = rateDetails.grossAmount.toLocaleString('en-US', {
                                    style: 'currency',
                                    currency: 'USD'
                                });
                            }
                        }
                        var productLines = rateDetails.productLines;
                        productLines.forEach(function (productLine) {

                            if (productLine.unitQuantity)
                                price.qty = productLine.unitQuantity;

                            if (price.net_amount && price.qty) {

                                var unitNetAmount = parseFloat(price.net_amount.replace(/[^0-9.]/g, '')) / price.qty;

                                price.unit = {};
                                price.unit.net_amount = unitNetAmount.toLocaleString('en-US', {
                                    style: 'currency',
                                    currency: 'USD'
                                });
                                if (price.gross_amount) {

                                    var unitGrossAmount = parseFloat(price.gross_amount.replace(/[^0-9.]/g, '')) / price.qty;
                                    price.unit.gross_amount = unitGrossAmount.toLocaleString('en-US', {
                                        style: 'currency',
                                        currency: 'USD'
                                    });
                                }
                            }
                        });
                    }
                    statusFlag = 1;
                } else {
                    statusFlag = 2;
                }
            }
            if(inProgressRequests < 1) {
                if(statusFlag == 1) {
                    var priceBox = priceBoxTemplate({price: (Object.keys(price).length !== 0 ? price : defaultPrice)});
                    if($('#price-box').length) {
                        $('#price-box').remove();
                    }
                    $('#config-accordion').parent().after(priceBox);
                } else if(statusFlag == 2) {
                    var priceBoxError = priceBoxErrorTemplate();
                    if($('#price-box-error').length) {
                        $('#price-box-error').remove();
                    }
                    $('#config-accordion').after(priceBoxError);
                }
                $('.pricing-skeleton').addClass('hide');
            }
            if(key > 4)
                key = 0;
        },
        resetPriceBox: function () {
            var priceBox = priceBoxTemplate({price: defaultPrice});
            if($('#price-box-error').length) {
                $('#price-box-error').remove();
            }
            if($('#price-box').length) {
                $('#price-box').remove();
            }
            $('#config-accordion').after(priceBox);
        }
    };

    return priceRateApi;
});
