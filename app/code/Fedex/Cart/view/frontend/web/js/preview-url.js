define(["ko", "jquery", "mage/url",'Magento_Customer/js/customer-data','fedex/storage'], function (ko, $, urlBuilder, customerData, fxoStorage) {
    "use strict";

    const DEFAULT_PREVIEW_IMAGE_TIMEOUT = 15000;

    return {

        // To resolve timeout dunc api issue custom fetch timeout set
        async fetchWithTimeout(resource, options = {}) {
            const { timeout = 15000 } = options;

            const controller = new AbortController();
            const id = setTimeout(() => controller.abort(), timeout);

            const response = await fetch(resource, {
                ...options,
                signal: controller.signal
            });
            clearTimeout(id);

            return response;
        },

        async ajaxWithTimeout(resource, timeout = DEFAULT_PREVIEW_IMAGE_TIMEOUT) {
            return $.ajax({
                url: resource,
                timeout: timeout,
                dataType: 'json'
            });
        },

        /**
         * Fetch Base64 Image Url from API By ConferenceReference Id
         *
         * @param {string} previewApiUrl - Preview API Url
         * @return {object}
         */
        async fetchPreviewImg(previewApiUrl) {
            try {
                if (previewApiUrl != '' && typeof (previewApiUrl) != 'undefined') {
                    let responseData = false;

                    if(window.d204823_toggle_undefined_url) {
                        responseData = await this.ajaxWithTimeout(previewApiUrl, DEFAULT_PREVIEW_IMAGE_TIMEOUT);
                    } else {
                        const response = await this.fetchWithTimeout(previewApiUrl,{
                            timeout: DEFAULT_PREVIEW_IMAGE_TIMEOUT
                        });
                        responseData = await response.json();
                    }
                    return responseData;
                }
            } catch (err) {
                console.log(err);
            }
        },

        /**
         * Request for get image Preview
         *
         * @param {string} contentReferenceId - Conference Reference Id for request
         * @param {object} e - Image Dom Object
         */
        getPreviewImg(contentReferenceId, e, newDocumentImage = 0) {
            /* B-1148619- Print Quote Receipt */
            let previewApiUrl;
            if (window.e383157Toggle) {
                previewApiUrl = fxoStorage.get('dunc_office_api_url');
            } else {
                previewApiUrl = window.localStorage.getItem("dunc_office_api_url");
            }

            if (previewApiUrl == null && typeof (window.checkout) != 'undefined' && window.checkout != null) {
                previewApiUrl = typeof (window.checkout.dunc_office_api_url) != 'undefined' && window.checkout.dunc_office_api_url != null ?
                    window.checkout.dunc_office_api_url : null;
            }

            if (previewApiUrl != "" && previewApiUrl != null && contentReferenceId != "") {
                previewApiUrl = previewApiUrl.replace("contentReference", contentReferenceId);

                var srcUrl = $(this).attr('src') ? $(this).attr('src') : '';
                var srcData = contentReferenceId ? contentReferenceId : '';
                var srcConditions = srcUrl.includes('png') || srcUrl.includes('.png') || srcUrl.includes('.jpg') || srcUrl.includes('jpeg') || srcData.includes('.jpg') || srcData.includes('.png') || srcData.includes('.jpeg');

                if ((window.E398131_NewDocumentImage || newDocumentImage) && (!Number(contentReferenceId))) {
                        previewApiUrl = urlBuilder.build("fxocm/newdocapi/index?imageId="+contentReferenceId);
                }
                if (srcConditions != true) {
                    // B-2436408 Remove preview api calls
                    if (window.checkout?.tech_titans_b_2421984_remove_preview_calls_from_catalog_flow == true || window.checkoutConfig?.tech_titans_b_2421984_remove_preview_calls_from_catalog_flow == true) {
                            previewApiUrl = window.checkout.document_image_preview_url ? window.checkout.document_image_preview_url : window.checkoutConfig.document_image_preview_url;
                            previewApiUrl += "v2/documents/" + contentReferenceId + "/previewpages/1?zoomFactor=2&ClientName=POD2.0";
                            var previewImg = (previewApiUrl && contentReferenceId) ? 'true' : 'false';
                            var isDocumentIsNotAvailable = previewImg === 'false' && typeof (window.location.href) != 'undefined' &&
                                window.location.href != null &&
                                (window.location.href.includes('/sales/order/history') || window.location.href.includes('/sales/order/view')) && typeof (window.checkout) !== 'undefined' &&
                                window.checkout != null && window.checkout.is_commercial == false;
                            if (previewImg === 'true') {
                                $(e).attr("loading","lazy");
                                $(e).attr("src", previewApiUrl);
                                $(e).closest(".prev-img-loader").removeClass("prev-img-loader");
                                $(e).parent().find(".product-loader").remove();
                            } else if (isDocumentIsNotAvailable) {
                                // make reorder checkbox uncheck if document not available
                                $(e).attr(
                                    "src",
                                    window.mediaJsImgpath + 'wysiwyg/images/document-not-available-icon.png'
                                );

                                $(e).parents('.expend-order').find('input.checkbox-item').attr('disabled', true);
                                $(e).parents('.expend-order').find('label.item-label, label.reorder-item-label-check').addClass('disable-checkbox');
                                if ($(e).closest("tbody").find('label.item-label input.checkbox-item').length < 2) {
                                    $(e).closest('table.order-item').find('input.checkbox-order').attr('disabled', true);
                                    $(e).closest('table.order-item').find('label.order-label').addClass('disable-checkbox');
                                }
                                var orderItem = 0;
                                var orderItemDisabled = 0;

                                $(e).closest("tbody").find('label.item-label input.checkbox-item').delay(2000).each(function () {
                                    orderItem++;
                                    if (typeof($(this).attr("disabled")) != "undefined") {
                                        orderItemDisabled++;
                                    }
                                    if (orderItem == orderItemDisabled) {
                                        $(e).closest('table.order-item').find('input.checkbox-order').attr('disabled', true);
                                        $(e).closest('table.order-item').find('label.order-label').addClass('disable-checkbox');
                                    } else if ($(e).closest("tbody").find('label.item-label input.checkbox-item').length > 1) {
                                        $(e).closest('table.order-item').find('input.checkbox-order').attr('disabled', false);
                                        $(e).closest('table.order-item').find('label.order-label').removeClass('disable-checkbox');
                                    }
                                });

                                $(e).closest(".prev-img-loader").removeClass("prev-img-loader");
                                $(e).parent().find(".product-loader").remove();
                            } else {
                                $(e).closest(".prev-img-loader").removeClass("prev-img-loader");
                                $(e).parent().find(".product-loader").remove();
                                if(window.location.href.includes('/default/sales/order/print')) {
                                    $(e).attr(
                                        "src",
                                        window.mediaJsImgpath + 'wysiwyg/images/document-not-available-icon.png'
                                    );
                                }
                            }
                    } else {
                        this.fetchPreviewImg(previewApiUrl).then((response) => {
                            var isDocumentIsNotAvailable = response != undefined && response.successful != undefined &&
                                response.successful == false && typeof (window.location.href) != 'undefined' &&
                                window.location.href != null &&
                                (window.location.href.includes('/sales/order/history') || window.location.href.includes('/sales/order/view')) && typeof (window.checkout) !== 'undefined' &&
                                window.checkout != null && window.checkout.is_commercial == false;
                            // B-1173348 - Fix JS Errors - Cannot read property 'output' of undefined
                            if (response != undefined && response.successful != undefined && response.successful == true && response.output != undefined && response.output.imageByteStream != undefined) {
                                /* B-2353117- Remove base64 image, use document API */
                                if(window.checkout?.is_remove_base64_image == true || window.checkoutConfig?.is_remove_base64_image == true){
                                    $(e).attr("loading","lazy");
                                    previewApiUrl = window.checkout.document_image_preview_url ? window.checkout.document_image_preview_url : window.checkoutConfig.document_image_preview_url;
                                    previewApiUrl += "v2/documents/" + contentReferenceId + "/previewpages/1?zoomFactor=2&ClientName=POD2.0";
                                    $(e).attr("src", previewApiUrl);
                                } else {
                                    $(e).attr(
                                        "src",
                                        "data:image/png;base64," +
                                        response.output.imageByteStream
                                    );
                                }
                                $(e).closest(".prev-img-loader").removeClass("prev-img-loader");
                                $(e).parent().find(".product-loader").remove();
                            } else if (isDocumentIsNotAvailable) {
                                // make reorder checkbox uncheck if document not available
                                $(e).attr(
                                    "src",
                                    window.mediaJsImgpath + 'wysiwyg/images/document-not-available-icon.png'
                                );

                                $(e).parents('.expend-order').find('input.checkbox-item').attr('disabled', true);
                                $(e).parents('.expend-order').find('label.item-label, label.reorder-item-label-check').addClass('disable-checkbox');
                                if ($(e).closest("tbody").find('label.item-label input.checkbox-item').length < 2) {
                                    $(e).closest('table.order-item').find('input.checkbox-order').attr('disabled', true);
                                    $(e).closest('table.order-item').find('label.order-label').addClass('disable-checkbox');
                                }
                                var orderItem = 0;
                                var orderItemDisabled = 0;

                                $(e).closest("tbody").find('label.item-label input.checkbox-item').delay(2000).each(function () {
                                    orderItem++;
                                    if (typeof($(this).attr("disabled")) != "undefined") {
                                        orderItemDisabled++;
                                    }
                                    if (orderItem == orderItemDisabled) {
                                        $(e).closest('table.order-item').find('input.checkbox-order').attr('disabled', true);
                                        $(e).closest('table.order-item').find('label.order-label').addClass('disable-checkbox');
                                    } else if ($(e).closest("tbody").find('label.item-label input.checkbox-item').length > 1) {
                                        $(e).closest('table.order-item').find('input.checkbox-order').attr('disabled', false);
                                        $(e).closest('table.order-item').find('label.order-label').removeClass('disable-checkbox');
                                    }
                                });

                                $(e).closest(".prev-img-loader").removeClass("prev-img-loader");
                                $(e).parent().find(".product-loader").remove();
                            } else {
                                $(e).closest(".prev-img-loader").removeClass("prev-img-loader");
                                $(e).parent().find(".product-loader").remove();
                                if(window.location.href.includes('/default/sales/order/print')) {
                                    $(e).attr(
                                        "src",
                                        window.mediaJsImgpath + 'wysiwyg/images/document-not-available-icon.png'
                                    );
                                }
                            }
                        });
                    }
                } else if(typeof window.checkoutConfig !== 'undefined' && window.checkoutConfig.is_sde == true && window.checkoutConfig.sde_product_mask_image_url) {
                    $(e).attr('src', window.checkoutConfig.sde_product_mask_image_url);
                    $(e).parent().find(".product-loader").remove();
                    $(e).closest(".prev-img-loader").removeClass("prev-img-loader");
                }else if(srcConditions == true){
                    $(e).attr('src', srcData);
                    $(e).closest(".prev-img-loader").removeClass("prev-img-loader");
                    $(e).parent().find(".product-loader").remove();
                }
                else {
                    $(e).closest(".prev-img-loader").removeClass("prev-img-loader");
                    $(e).parent().find(".product-loader").remove();
                }
            } else {
                $(e).closest(".prev-img-loader").removeClass("prev-img-loader");
                $(e).parent().find(".product-loader").remove();
            }
        }
    };
});
