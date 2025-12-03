/**
* Copyright © Fedex, Inc. All rights reserved.
* See COPYING.txt for license details.
*/

define([
    'jquery',
    'uiComponent',
    'ko',
    'mage/storage',
    'Fedex_Cart/js/product-engine-check-product-availability',
    'Fedex_Cart/js/preview-url',
    'domReady!'
], function ($, Component,  ko, storage, peProductAvailability, previewUrl) {
    'use strict';

    return Component.extend({
        loaders: [],
        orderDetailReorderCounter: ko.observable(0),

        /** @inheritdoc */
        initialize: function () {
            this._super();
            if(typeof this.pageActionName != 'undefined' && this.pageActionName != null
            && this.pageActionName == 'sales_order_view' && this.productEngineCall == 1) {
                this.checkReorderItemAvailability(this.reorderable);
            }
        },

        /**
         * Check Item availability
         *
         * @param {object} data
         * @param {int} index
         * @return {void}
         */
        checkItemAvailability: function (data, index) {
            let self = this;
            let element = index.currentTarget;
            let itemsCount = $(element).parents('tr.table-row').next().find('tbody tr.expend-order').length;
            let reorderOrderId = $(element).parents('tr.table-row').next().find('tbody tr.expend-order').attr('order-id');
            let productsNotAvailableCount = 0;
            let promiseObject = '';

            $(window).on('resize', function () {
                if ($(window).width() > 767) {
                    $('#products-engine-loader-'+reorderOrderId).show();
                    $('.order-items-row.order-row.order-id-'+reorderOrderId).next().find('.table.order-item').css({"opacity": "0.5", "pointer-events": "none"});
                }
            });

            $(element).parents('tr.table-row').next('tr').find('tbody tr.expend-order').each( function () {
                //Get serialized product json instance data and product preset id
                let orderId = $(this).attr('order-id');
                let itemId = $(this).attr('item-id');
                let productInstanceData = $(this).attr('product-instance');
                let productPresetId = $(this).attr('product-preset-id');
                let productEngine = $(this).attr('product-engine');
                let productStatus = $(this).attr('product-status');
                let contentReferenceId = $(this).attr('product-content-reference');
                let newDocumentImage = $(this).attr('newDocumentImage');
                let imageElement = $(this).find('#content-reference-image-'+itemId);
                previewUrl.getPreviewImg(contentReferenceId, imageElement, newDocumentImage);

                if (productStatus == 1 && productEngine == 1) {
                    //Check product if available on product engine
                    promiseObject = peProductAvailability.isProductAvailableRequest(productInstanceData, productPresetId).then(
                        (onResolved) => {},
                        (onRejected) => {
                            productsNotAvailableCount++;
                            //Product instance if not available then disabled item checkbox
                            $(this).find('input.checkbox-item').prop('disabled', true);
                            $(this).find('label.item-label').addClass('disable-checkbox');
                            $('#unavailable-order-' + orderId).show();
                        }
                    );
                } else {
                    if(productStatus == 0) {
                        productsNotAvailableCount++;
                        $('#unavailable-order-' + orderId).show();
                    }
                }
            });

            Promise.all([promiseObject]).then((values) => {
                if(productsNotAvailableCount == itemsCount) {
                    $('.unavailable-order-item-'+reorderOrderId).hide();
                    $('.reorder-unavailable-'+reorderOrderId).show();
                    $('.checkbox-order.selected-order-'+reorderOrderId).prop('disabled', true);
                    $('.checkbox-order.selected-order-'+reorderOrderId).parents('.order-label').addClass('disable-checkbox');
                    $('.checkbox-order.selected-order-'+reorderOrderId).parents('.order-label-items').addClass('disable-checkbox');
                    $(window).on('resize', function () {
                        if ($(window).width() > 767) {
                            $('#products-engine-loader-'+reorderOrderId).hide();
                            $('.order-items-row.order-row.order-id-'+reorderOrderId).next().find('.table.order-item').removeAttr('style');
                        }
                    });
                } else {
                    if ($('.checkbox-order.selected-order-'+reorderOrderId).parents('.order-label').hasClass('disable-checkbox')
                    || $('.checkbox-order.selected-order-'+reorderOrderId).parents('.order-label-items').hasClass('disable-checkbox')) {
                        $('.reorder-unavailable-'+reorderOrderId).show();
                    } else {
                        $('.checkbox-order.selected-order-'+reorderOrderId).prop('disabled', false);
                        $('.checkbox-order.selected-order-'+reorderOrderId).parents('.order-label').removeClass('disable-checkbox');
                        $('.checkbox-order.selected-order-'+reorderOrderId).parents('.order-label-items').hasClass('disable-checkbox')
                    }
                    $(window).on('resize', function () {
                        if ($(window).width() > 767) {
                            $('#products-engine-loader-'+reorderOrderId).hide();
                            $('.order-items-row.order-row.order-id-'+reorderOrderId).next().find('.table.order-item').removeAttr('style');
                        }
                    });
                }
            });
        },

        /**
         * Get Product Preview Url
         *
         * @param {object} data
         * @param {int} index
         * @return {void}
         */
        getProductPreviewUrl: function (data, index) {
            let element = index.currentTarget;
            $(element).parents('tr.table-row').next('tr').find('tbody tr.expend-order').each( function () {
                let itemId = $(this).attr('item-id');
                let contentReferenceId = $(this).attr('product-content-reference');
                let imageElement = $(this).find('#content-reference-image-'+itemId);
                previewUrl.getPreviewImg(contentReferenceId, imageElement);
            });
        },

        /**
         * Check Order Item availability
         *
         * @param {boolean} reorderable
         * @return {void}
         */
        checkReorderItemAvailability: function (reorderable) {
            let self = this,
                itemsCount = $('.epro-order-items-rows').length,
                reorderDisableButton = $('.disable-link').length,
                legacyDocumentOrder= $('.legacy-document-order').length;
            this.orderDetailReorderCounter.subscribe((value) => {
                if (reorderable && value == itemsCount && reorderDisableButton > 0 && !legacyDocumentOrder) {
                    $('.epro-reorder-action').removeClass('disable-link');
                    $('.reorder-btn-view').prop('disabled', false);
                }
            });


            $('.epro-order-items-rows').each( function () {
                //Get serialized product json instance data and product preset id
                let productInstanceData = $(this).attr('product-instance'),
                    productPresetId = $(this).attr('product-preset-id'),
                    productStatus = $(this).attr('product-status'),
                    brokerConfigId = $(this).data('broker-config-id'),
                    itemQty = $(this).data('item-qty'),
                    itemId = $(this).find('input').data('item-id'),
                    productSku = $(this).find('input').data('item-sku'),
                    orderIncrementId = $(this).find('input').data('order-increment-id'),
                    isMiraklOffer = $(this).find('input').data('is-mirakl-offer'),
                    offerId = $(this).find('input').data('offer-id'),
                    is3PCustomizable = $(this).find('input').data('3p-customizable');

                if (productStatus == 1) {
                    peProductAvailability.isProductAvailableRequest(productInstanceData, productPresetId).then(
                        (onResolved) => {
                            self.orderDetailReorderCounter(self.orderDetailReorderCounter() + 1);
                        },
                        (onRejected) => {
                        }
                    );
                } else if (brokerConfigId) {
                    self.loaders[itemId] = ko.observable(false);

                    self.checkViewReceiptBrokerConfigId(
                        brokerConfigId,
                        itemQty,
                        productSku,
                        orderIncrementId,
                        self.loaders[itemId],
                        self.orderDetailReorderCounter
                    );
                } else if (isMiraklOffer && !is3PCustomizable) {
                    self.orderDetailReorderCounter(self.orderDetailReorderCounter() + 1);
                }
            });
        },
        /**
         * Call API to verify config is available for reorder in order view page
         * @param {string} brokerId
         * @param itemQty
         * @param {string} productSku
         * @param {string} orderIncrementId
         * @param {observable} loader
         * @param {observable} counter
         */
        checkViewReceiptBrokerConfigId: function (brokerId, itemQty, productSku, orderIncrementId, loader, counter) {
            let serviceUrl = BASE_URL + 'rest/V1/reorder/bulk/service',
                isLoading = loader,
                payload = {
                    brokerConfigId: brokerId,
                    productSku: productSku,
                    orderIncrementId: orderIncrementId,
                    itemQty: parseInt(itemQty, 10)
                };

            isLoading(true);
            storage.post(
                serviceUrl,
                JSON.stringify(payload)
            ).done(function (response) {
                response = JSON.parse(response);
                console.log({response})
                if (response.status == 200 && response.response[0].isSuccess) {
                    counter(counter() + 1);
                }
            }).always(function () {
                isLoading(false);
            });
        }
    });
});
