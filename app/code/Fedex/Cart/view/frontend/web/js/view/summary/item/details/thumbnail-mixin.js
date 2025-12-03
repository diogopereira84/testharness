/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define(["uiComponent", "previewImg",'Magento_Customer/js/customer-data','jquery'], function (Component, previewImg,customerData,$) {
    "use strict";

    return function (Component) {
        return Component.extend({
            /**
             * Request for get image Preview
             *
             * @param {string} contentReferenceId - Conference Reference Id for request
             * @param {object} e - Image Dom Object
             */
            imgLoad: function (contentReferenceId, e) {
                contentReferenceId = String(contentReferenceId);
                // Check if the contentReferenceId is an url
                // If positive, set the src attribute of the element.
                if (contentReferenceId && contentReferenceId.includes("http")) {
                    $(e).attr('src', contentReferenceId);
                }
                if (typeof(previewImg) !== "undefined" && previewImg !== null) {
                    previewImg.getPreviewImg(contentReferenceId, e);
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
             * @param {object} product - product data
             * @return {boolean} true|false
             */
            isCheckImage: function (product) {
                let srcUrl = this.getSrc(product);
                srcUrl = String(srcUrl) ? String(srcUrl) : "";
                let srcConditions =
                    srcUrl.includes("png") ||
                    srcUrl.includes(".png") ||
                    srcUrl.includes(".jpg") ||
                    srcUrl.includes("jpeg");

                if (srcConditions || Boolean(product.is_marketplace_product)) {
                    return true;
                } else {
                    return false;
                }
            },

            /**
             * @param {Object} item
             * @return {null}
             */
            getSrc: function (item) {
                let imagePreview = checkoutConfig.imagePreview[item['item_id']];
                if (window.checkoutConfig.is_sde_store) {
                    return window.checkoutConfig.sde_product_mask_image_url;
                }

                if ( item.is_marketplace_product ) {
                    return item.marketplace_image;
                }

                let previewUrl = '';
                if (customerData.get('cart')) {
                    let cartData = customerData.get('cart');
                    if (cartData().items.length !== 0) {
                        // Getting preview url id
                        cartData().items.find((cartItem) => {
                            if (cartItem.item_id == item['item_id']) {
                                previewUrl = cartItem.product_image.src;
                            }
                        });
                    }
                    return previewUrl;
                } else if(imagePreview !== undefined){
                    return imagePreview;
                }else {
                    if (this.imageData[item['item_id']]) {
                        return this.imageData[item['item_id']].src;
                    }
                }

                return null;
            }
        });
    };
});
