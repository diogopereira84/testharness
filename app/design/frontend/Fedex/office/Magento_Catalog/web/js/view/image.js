/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'uiComponent',
    'previewImg'
], function (Component, previewImg) {
    'use strict';

    return Component.extend({
        /** @inheritdoc */
        initialize: function () {
            this._super();

            this.template = window.checkout.imageTemplate || this.template;
        },
        /**
         * Request for get image Preview
         *
         * @param {string} contentReferenceId - Conference Reference Id for request
         * @param {object} e - Image Dom Object
         */
        imgLoad: function(contentReferenceId, e, newDocumentImage = 0) {
            contentReferenceId = String(contentReferenceId) ? String(contentReferenceId) : '';
            if(isNaN(contentReferenceId)){
                if (typeof(previewImg) !== "undefined" && previewImg !== null) {
                    previewImg.getPreviewImg(contentReferenceId, e, newDocumentImage);
                }
            }else{
             jQuery(e).parent().find(".product-loader").remove();
            }
        },

        /**
         * Get image loader url
         *
         * @return {string} - get image loader url
         */
        imgLoaderUrl: function () {
            return window.LoaderImgUrl;
        },

        /**
         * Check image url with jpg, png and jpeg
         *
         * @param {object} item - src url
         * @return {boolean} true|false
         */
        isCheckImage:  function (item) {
            let src = item.product_image.src;
            let srcUrl = String(src) ? String(src) : '';
            let srcConditions = srcUrl.includes('png') || srcUrl.includes('.png') || srcUrl.includes('.jpg') || srcUrl.includes('jpeg');

            if (srcConditions || item.is_third_party_product) {
                return true;
            } else {
                return false;
            }
        }
    });
});
