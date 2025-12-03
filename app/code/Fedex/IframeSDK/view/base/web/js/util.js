define([
    'jquery',
    'sdk',
    'loader',
], function($, sdk) {
    'use strict';
    /**
     * @param initConfig - Configuration for SDK.
     * @param callback - Handles fxoProduct when added to cart.
     * @param successCallback - Called when iframe has loaded successfully.
     * @param errorCallback - Handles any error with SDK.
     * @param productInstance - fxoProduct to be edited.
     * @param podData - data for PoD initialization from Magento iframe.
     */
    return function loadIframe(initConfig, callback, successCallback, errorCallback, productInstance, podData) {
        window.FedExSDK.initialize(
            initConfig,
            successCallback,
            errorCallback
        ).then((api) => {
            const setupIframe = (data) => {
                api.setMagentoPODData(data);

                var fxoProductInstance = false;
                if(productInstance && productInstance.fxoProductInstance) {
                    fxoProductInstance = productInstance.fxoProductInstance;
                } else if(productInstance && !productInstance.fxoProductInstance){
                    fxoProductInstance = productInstance;
                }

                if (productInstance && fxoProductInstance && fxoProductInstance.isEditable) {
                    api.onEditConfiguredProduct(productInstance, callback);
                }else {
                    api.onConfigureProduct(callback, (fxoProduct) => {
                        window.location.href = data.productPageToGoBack;
                    });
                }
            }

            // Checking if there is a Promise to retrieve product's contentAssociation
            if (podData.waitForContentAssociation) {
                podData.waitForContentAssociation().then((contentAssociation) => {
                    podData.contentAssociation = contentAssociation;

                    if (contentAssociation.success === false) {
                        $("body").loader("hide");

                        throw new Error('Product Engine ID not set for this product');
                    } else {
                        setupIframe(podData);
                    }
                });
            } else {
                setupIframe(podData);
            }
        }).catch((err) => errorCallback(err));
    }

});
