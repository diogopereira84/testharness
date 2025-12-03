define([
    'jquery',
    'ko',
    'Fedex_ProductEngine/js/product-controller',
], function($, ko, productController) {
    'use strict'

    return {

        isRetail: window.checkout.is_retail,
        // Fedex Internal Configurations > Features Toggle Configuration > Sharks - Enable Check Product Availability Only in Retail

        /**
         * Check is product available or not
         *
         * @param {string} productInstanceData
         * @param {string} productPresetId
         * @return {boolean}
         */
        isProductAvailableRequest: async function(productInstanceData, productPresetId) {
            if (!this.isRetail) {
                return true;
            }

            let fxoInstanceData = JSON.parse(productInstanceData);
            let controlId = null;
            fxoInstanceData.properties.forEach(fxoInstanceDataItem => {
                if (fxoInstanceDataItem.name === "CONTROL_ID") {
                    controlId = fxoInstanceDataItem.id;
                }
            });

            let productEngineUrl = typeof window.checkout.mix_cart_product_engine_url === 'string'
                ? window.checkout.mix_cart_product_engine_url
                : '';

            let productCont = new productController.ProductController(productEngineUrl);
            let instanceId = fxoInstanceData.id;
            let versionId = fxoInstanceData.version;

            //Request for product vailable on product engine to selected by product id
            let isProductAvailable = new Promise(function(resolve, reject) {
                productCont.selectProductById(instanceId, versionId, controlId, (responseData) => {
                    const productResult = responseData.productResult;
                    const isVersionInactive = !!productResult.alerts.find((result) => result.code === 'PRODUCT.VERSION.INACTIVE');

                    if (isVersionInactive) {
                        reject(false);
                        return;
                    }

                    productCont.loadSerializedProduct(productInstanceData);
                    try {
                        if (typeof productPresetId !== 'undefined' && productPresetId != null && productPresetId !== "") {
                            productCont.applyPreset(productPresetId);
                        }
                    } catch (err) {
                        console.warn(err);
                    }

                    resolve(true);
                }, () => { reject(false) });
            });

            return isProductAvailable;
        }
    }
});
