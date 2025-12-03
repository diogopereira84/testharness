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
], function ($, Component, ko, storage, peProductAvailability, previewUrl) {
    'use strict';

    return Component.extend({
        loaders: [],
        reorderCounter: [],
        orderDetailReorderCounter: ko.observable(0),
        apiCalls: [],

        /** @inheritdoc */
        initialize: function () {
            this._super();
            if(typeof this.pageActionName != 'undefined' && this.pageActionName != null
            && this.pageActionName == 'sales_order_view' && this.productEngineCall == 1) {
                this.checkReorderItemAvailability(this.reorderable);
            }
        },

        /**
         * Check Item availability from order listing page
         *
         * @param {object} data
         * @param {object} event
         * @return {void}
         */
        checkItemAvailability: function (data, event, unavailableMessage = '') {
            let self = this,
                element = event.currentTarget,
                orderTable = $(element).parents('tr.table-row').next('tr').find('.table.order-item'),
                productRows = $(element).parents('tr.table-row').next('tr').find('tbody tr.expend-order:not(.expend-order-bundle)'),
                orderId = $(element).data('id'),
                orderStatus = $(element).parents('tr.table-row').next('tr').find('.reorder-unavailable.reorder-unavailable-' + orderId),
                orderCheckbox = $('.checkbox-order.selected-order-' + orderId),
                itemsCount = productRows.length;

            let tiger_enable_essendant = window.checkout?.tiger_enable_essendant === true;

            if (this.apiCalls[orderId]) {
                return;
            }

            if (this.reorderCounter[orderId] === undefined) {
                this.reorderCounter[orderId] = ko.observable(0);
            }

            if (this.apiCalls[orderId] === undefined) {
                this.apiCalls[orderId] = ko.observable(0);
                this.apiCalls[orderId].subscribe((value) => {
                    if (productRows.length > 0 && value == itemsCount) {
                        orderTable.removeAttr('style');
                        if (this.reorderCounter[orderId]() !== itemsCount) {
                            orderStatus.show()
                            orderCheckbox.prop('disabled', true);
                            orderCheckbox.parents('.order-label').addClass('disable-checkbox');
                        } else {
                            orderStatus.hide();
                            orderCheckbox.prop('disabled', false);
                            orderCheckbox.parents('.order-label').removeClass('disable-checkbox');
                        }
                    }
                });
            }

            orderTable.css({ "opacity": "0.5", "pointer-events": "none" });

            productRows.each(function () {
                let contentReferenceId = $(this).attr('product-content-reference');
                let orderItemId = $(this).attr('item-id');
                let imageElement = $(this).find('#content-reference-image-' + orderItemId);
                let newDocumentImage = $(this).attr('newDocumentImage');
                let isMarketplaceProduct = $(this).attr('data-broker-config-id') || $(this).data('is-mirakl-offer');
                let is3pCustomizable = $(this).data('3p-customizable');
                let productType = $(this).attr('item-product-type');
                let parentItemId = $(this).attr('parent-item-id');

                if(isMarketplaceProduct) {
                    // If its a 3pl product then we don't need to load the image using getPreviewImg function.
                    // The image is already loaded in the template file.
                    $(this).find(".prev-img-loader").removeClass("prev-img-loader");
                    $(this).find(".product-loader").remove();
                } else {
                    previewUrl.getPreviewImg(contentReferenceId, imageElement, newDocumentImage);
                }

                $(this).find('.product-engine-loader').show();

                //Get serialized product json instance data and product preset id
                let productInstanceData = $(this).attr('product-instance'),
                    productPresetId = $(this).attr('product-preset-id'),
                    productStatus = $(this).attr('product-status'),
                    brokerConfigId = $(this).data('broker-config-id');

                if (productStatus == 1 && productType !== 'bundle') {
                    //Check product if available on product engine
                    peProductAvailability.isProductAvailableRequest(productInstanceData, productPresetId).then(
                        (onResolved) => {
                            self.reorderCounter[orderId](self.reorderCounter[orderId]() + 1);
                            self.apiCalls[orderId](self.apiCalls[orderId]() + 1);
                            $(this).find('.product-engine-loader').hide();
                        },
                        (onRejected) => {
                            self.apiCalls[orderId](self.apiCalls[orderId]() + 1);
                            //Product instance if ont available then disabled item checkbox
                            $(this).find('input.checkbox-item').prop('disabled', true);
                            $(this).find('label.item-label').addClass('disable-checkbox');
                            $(this).find('.product-engine-loader').hide();

                            if (itemsCount == 1) {
                                self.showUnAvailableMessage($(this), unavailableMessage);
                            } else {
                                $(this).next('tr').next('tr').next('tr').next('tr').find('.reorder-items-not-available-msg-cantainer').show();
                            }
                        }
                    );
                } else if (brokerConfigId || (tiger_enable_essendant && isMarketplaceProduct)) {
                    self.checkBrokerConfigId(this);
                } else if (isMarketplaceProduct && !is3pCustomizable) {
                    self.reorderCounter[orderId](self.reorderCounter[orderId]() + 1);
                    self.apiCalls[orderId](self.apiCalls[orderId]() + 1);

                    $(this).find('input.checkbox-item').prop('disabled', false);
                    $(this).find('label.item-label').removeClass('disable-checkbox');
                    $(this).find('.product-engine-loader').hide();
                } else {
                    self.apiCalls[orderId](self.apiCalls[orderId]() + 1);
                    $(this).find('.product-engine-loader').hide();

                    if (itemsCount != self.reorderCounter[orderId]() && !orderStatus.length) {
                        $(this).next('tr').next('tr').next('tr').next('tr').find('.reorder-items-not-available-msg-cantainer').show();
                    }
                }
            });
        },

        /**
         * Call API to verify config is available for reorder in order list page
         * @param {jquery} row
         */
        checkBrokerConfigId: function (row) {
            let self = this,
                serviceUrl = BASE_URL + 'rest/V1/reorder/bulk/service',
                tableRow = $(row),
                orderId = tableRow.attr('order-id'),
                payload = {
                    brokerConfigId: tableRow.data('broker-config-id'),
                    productSku: tableRow.attr('item-sku'),
                    itemQty: tableRow.attr('item-qty'),
                    orderIncrementId: tableRow.attr('order-increment-id'),
                    orderItemId: tableRow.attr('item-id')
                };

            storage.post(
                serviceUrl,
                JSON.stringify(payload)
            ).done(function (response) {
                response = JSON.parse(response);

                if(response.track && response.track.length > 0) {
                    self.addTrackingNumbers(tableRow, response.track);
                }

                if (response.status == 200 && response.response[0].isSuccess) {
                    self.reorderCounter[orderId](self.reorderCounter[orderId]() + 1);
                    tableRow.find('input.checkbox-item').prop('disabled', false);
                    tableRow.find('label.item-label').removeClass('disable-checkbox');
                } else {
                    tableRow.find('input.checkbox-item').prop('disabled', true);
                    tableRow.find('label.item-label').addClass('disable-checkbox');
                }
            }).fail(function () {
                tableRow.find('input.checkbox-item').prop('disabled', true);
                tableRow.find('label.item-label').addClass('disable-checkbox');
            }).always(function () {
                self.apiCalls[orderId](self.apiCalls[orderId]() + 1);
                tableRow.find('.product-engine-loader').hide();
            });
        },

        /**
         * Add Tracking Numbers from API response
         * @param tableRow
         * @param trackingNumbersArray
         */
        addTrackingNumbers: function (tableRow, trackingNumbersArray) {
            var container = tableRow.find('.tracking-numbers-container'),
                element = $('<span className="tracking-numbers">Tracking number(s)</span>');

            trackingNumbersArray.forEach(function (trackingNumber) {
                element.append('<a href="' + trackingNumber.url + '" target="_blank">' + trackingNumber.number + '</a>');
            });

            container.append(element);
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
                let contentReferenceId = $(this).attr('product-content-reference');
                let orderItemId = $(this).attr('item-id');
                let imageElement = $(this).find('#content-reference-image-'+orderItemId);
                previewUrl.getPreviewImg(contentReferenceId, imageElement);
            });
        },

        /**
         * Show Unavailable message if one product is unavailable
         *
         * @param {object} element
         * @return {void}
         */
        showUnAvailableMessage: function(element, message) {
            let viewReceiptElement = element.parent();
            let productUnAvailableMessage = message !== '' ? message : 'One or more of your previous product configurations is unavailable.';
            if (viewReceiptElement.find('td.reorder-unavailable').length) {
                viewReceiptElement.find('td.reorder-unavailable').find('p').text(productUnAvailableMessage);
            } else {
                let warningImgUrl = element.next('tr').next('tr').next('tr').next('tr').find('.warning-mes-img').attr('src');
                let unAvailableTdCol = '<td colspan="3" class="reorder-unavailable">';
                unAvailableTdCol += '<img class="warning-mes-img" alt="warning img" src="' + warningImgUrl + '">';
                unAvailableTdCol += 'Reorder is unavailable.';
                unAvailableTdCol += `<p>${message!== '' ? message : 'One or more of your previous product configurations is unavailable.'}</p></td>`;
                viewReceiptElement.find('tr.view-receipt').prepend(unAvailableTdCol);
            }
        },

        /**
         * Check Order Item availability from order view page
         *
         * @param {boolean} reorderable
         * @return {void}
         */
        checkReorderItemAvailability: function (reorderable) {
            let self = this,
                itemsCount = $('tr.product-item-detail:not(.bundle-product)').length,
                reorderActionContainer = $('.retail-reorder-action'),
                legacyDocumentOrder= $('.legacy-document-order').length;

            this.orderDetailReorderCounter.subscribe((value) => {
                if (reorderable && value == itemsCount && reorderActionContainer.length > 0 && !legacyDocumentOrder) {
                    reorderActionContainer.removeClass('disable-link');
                    reorderActionContainer.find('.reorder-btn-view').prop('disabled', false);
                }
            });

            $('tbody tr.product-item-detail:not(.bundle-product)').each(function () {
                //Get serialized product json instance data and product preset id
                let productInstanceData = $(this).attr('product-instance'),
                    productPresetId = $(this).attr('product-preset-id'),
                    productStatus = $(this).attr('product-status'),
                    brokerConfigId = $(this).data('broker-config-id'),
                    itemId = $(this).find('input').data('item-id'),
                    productSku = $(this).find('input').data('item-sku'),
                    orderIncrementId = $(this).find('input').data('order-increment-id'),
                    isMiraklOffer = $(this).find('input').data('is-mirakl-offer'),
                    offerId = $(this).find('input').data('offer-id'),
                    quantity = $(this).find('.item-qty').text().trim(),
                    is3pCustomizable = $(this).data('3p-customizable');

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
                        productSku,
                        orderIncrementId,
                        itemId,
                        quantity,
                        self.loaders[itemId],
                        self.orderDetailReorderCounter
                    );
                } else if (isMiraklOffer && !is3pCustomizable) {
                    self.orderDetailReorderCounter(self.orderDetailReorderCounter() + 1);
                }
            });
        },

        /**
         * Call API to verify config is available for reorder in order view page
         * @param {string} brokerId
         * @param {string} productSku
         * @param {string} orderIncrementId
         * @param {observable} loader
         * @param {observable} counter
         */
        checkViewReceiptBrokerConfigId: function (brokerId, productSku, orderIncrementId, itemId, quantity, loader, counter) {
            let serviceUrl = BASE_URL + 'rest/V1/reorder/bulk/service',
                isLoading = loader,
                payload = {
                    brokerConfigId: brokerId,
                    productSku: productSku,
                    orderIncrementId: orderIncrementId,
                    itemId: itemId,
                    itemQty: quantity
                };

            isLoading(true);
            storage.post(
                serviceUrl,
                JSON.stringify(payload)
            ).done(function (response) {
                response = JSON.parse(response);

                if (response.status == 200 && response.response[0].isSuccess) {
                    counter(counter() + 1);
                }
            }).always(function () {
                isLoading(false);
            });
        }
    });
});
