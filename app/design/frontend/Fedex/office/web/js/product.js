define([
    'jquery',
    'env',
    'ajaxUtils',
    'mage/url',
    'Magento_Customer/js/customer-data',
    'ko',
    'Fedex_Canva/js/model/canva',
    'fedex/storage',
    'inBranchWarning',
    'Fedex_MarketplacePunchout/js/model/auth'
], function ($, env, ajaxUtils, urlBuilder, customerData, ko, canvaModel, fxoStorage, inBranchWarning, auth) {
    'use strict';
    window.contentAssociation = ko.observable(0);
    const bundleProductJsons = {};

    return {
        /**
         * Navigates to iframe module.
         * @param id - id of print product.
         * @param siteName - site name
         * @param token - access token
         */
        uploadPrintProduct(e) {
            e.preventDefault();
            return function(id, siteName, token, contentAssociation, isDyeSubFromCatalog=false) {
                var podData = {
                    id:id,
                    siteName:siteName,
                    accessToken:token,
                    productType:'PRINT_PRODUCT',
                    contentAssociation:contentAssociation,
                    productPageToGoBack: window.location.href
                };
                canvaModel.resetProcess(canvaModel.process.POD, null, null, null, null);
                if(window.e383157Toggle) {
                    fxoStorage.set('pod-data', podData);
                } else {
                    localStorage.setItem('pod-data', JSON.stringify(podData));
                }
                const queryString = isDyeSubFromCatalog === 1 ? '?isDyeSubFromCatalog=1' : '';
                window.location.href = urlBuilder.build(`configurator/index/index${queryString}`);
            }
        },
        uploadPrintBundleChildProduct(e) {
            e.preventDefault();
            return function(id, siteName, token, itemId) {
                var podData = {
                    id: id,
                    siteName: siteName,
                    accessToken: token,
                    productType: 'PRINT_PRODUCT',
                    contentAssociation: bundleProductJsons[itemId] || null,
                    productPageToGoBack: window.location.href
                };
                canvaModel.resetProcess(canvaModel.process.POD, bundleProductJsons[itemId]['integratorProductReference'], null, null, null);
                if (window.e383157Toggle) {
                    fxoStorage.set('pod-data', podData);
                } else {
                    localStorage.setItem('pod-data', JSON.stringify(podData));
                }
                window.location.href = urlBuilder.build(
                    `configurator/index/index?isBundle=1`
                );
            }
        },
        updateCart(){
            $('#update_shopping_cart').trigger('click');
        },
        /**
         * Navigates to iframe module.
         * @param instanceId - cart item to be edited.
         * @param designId - cart item to be edited.
         * @param product - product data is passed if the item is 3P.
         * @param sku
         * @param isCustomize
         * @returns {function(*): function(*): (function(): void)}
         */
        editItem(instanceId, designId = false, product = null, sku = false, isCustomize = false) {
            return function(token) {
                return function(siteName) {
                    if(product && product.mirakl_offer_id) {
                        return function () {
                            const sku = product.sku;
                            const itemId = product.item_id;

                            let additionalData = typeof product.additional_data === 'string'
                                ? JSON.parse(product.additional_data)
                                : product.additional_data;
                            const offerId = additionalData.offer_id;
                            const sellerSku = additionalData.seller_sku;
                            const supplierPartID = additionalData.supplierPartID;
                            const supplierPartAuxiliaryId = additionalData.supplierPartAuxiliaryID;

                            let cartData = customerData.get('cart'),
                                cartItem = cartData().items.find((item) => item.item_id === itemId);

                            if (window.checkout.is_cbb_toggle_enable && cartItem && cartItem.punchout_enable){
                                const verifier = auth.getVerifier(),
                                    config = {
                                        payload: {
                                            form_key: window.FORM_KEY || $.mage.cookies.get('form_key') || '',
                                            sku: sku,
                                            offer_id: offerId,
                                            seller_sku: sellerSku,
                                            quote_item_id: itemId,
                                            supplier_part_auxiliary_id: supplierPartAuxiliaryId,
                                            supplier_part_id: supplierPartID,
                                            source: "CART"
                                        },
                                        form_action: cartItem.punchout_url,
                                        error_url: BASE_URL
                                    };

                                if (!config.payload.form_key) {
                                    console.error('Form key is missing');

                                    return;
                                }

                                auth.getChallenger(verifier).then((challenger) => auth.punchout(verifier, challenger, config)).catch((error) => {
                                    console.error('Punchout process failed:', error);
                                });
                            }else{
                                const companyUrlExtension = fxoStorage.get('companyUrlExtension') !== undefined ? fxoStorage.get('companyUrlExtension') : '';
                                const url = companyUrlExtension + '/marketplacepunchout/index/index';
                                const parameters =
                                    `?sku=${sku}&offer_id=${offerId}&seller_sku=${sellerSku}&quote_item_id=${itemId}&supplier_part_auxiliary_id=${supplierPartAuxiliaryId}&supplier_part_id=${supplierPartID}`;

                                window.location.href = urlBuilder.build(url + parameters);
                            }
                        }
                      } else if(designId) {
                        return function() {
                            canvaModel.resetProcess(canvaModel.process.EDIT, null, null, null, window.location.pathname);
                            let canvaProcess;
                            if(window.e383157Toggle){
                                canvaProcess = fxoStorage.get('canva-process');
                                if (canvaProcess === "EDIT") {
                                    fxoStorage.set('canva-instanceId', instanceId);
                                } else {
                                    fxoStorage.set('canva-instanceId', '');
                                }
                            }else{
                                canvaProcess = localStorage.getItem("canva-process");
                                if (canvaProcess === "EDIT") {
                                    localStorage.setItem('canva-instanceId', instanceId);
                                } else {
                                    localStorage.setItem('canva-instanceId', '');
                                }
                            }
                            window.location.href = urlBuilder.build(
                                `canva/index/index?designId=${designId}`
                            );
                        }
                    } else if(isCustomize) {
                        return function() {
                            if(siteName != "") {
                                if (window.fxocmEproCustomDoc) {
                                    window.location.href = urlBuilder.build(
                                        `iframe/index/index?edit=${instanceId}&id=${sku}&productType=COMMERCIAL_PRODUCT`
                                    );
                                } else {
                                    window.location.href = urlBuilder.build(
                                        `iframe/index/index?edit=${instanceId}&id=${sku}&siteName=${siteName}&accessToken=${token}&productType=COMMERCIAL_PRODUCT`
                                    );
                                }
                            } else {
                                window.location.href = urlBuilder.build(
                                    `catalogmvp/configurator/index?edit=${instanceId}&sku=${sku}&configurationType=customize`
                                );
                            }

                        }
                    } else {
                        return function() {
                            canvaModel.resetProcess(canvaModel.process.POD_EDIT, null, null, null, window.location.pathname);
                            window.location.href = urlBuilder.build(
                                `configurator/index/index?edit=${instanceId}`
                            );
                        }
                    }
                }
            }
        },
        /**
         * Navigate to iframe module
         * @param token - TAZ token.
         * @param siteName - site names.
         * @param catalogId - catalog id
         */
        uploadCustomDocProduct(e) {
            e.preventDefault();
            return function(token, siteName, catalogId) {
                if (window.fxocmEproCustomDoc && siteName != "") {
                    window.location.href = urlBuilder.build(
                        `iframe/index/index?id=${catalogId}&configurationType=customize`
                    );
                } else {
                    window.location.href = urlBuilder.build(
                        `iframe/index/index?id=${catalogId}&siteName=${siteName}&accessToken=${token}&productType=COMMERCIAL_PRODUCT`
                    );
                }
            }
        },
        /**
         * Add Catalog product to cart
         * @param sku - sku
         */
        addProductToCart(e) {
            e.preventDefault();
            return function(sku, selectedOptions = null) {
                let postData = {sku: sku};

                if (selectedOptions) {
                    postData = {...postData, selectedOptions};
                }

                ajaxUtils.post(
                    urlBuilder.build('livesearch/product/add'),
                    {},
                    postData,
                    true,
                    'json',
                    function (data) {
                        //TEMP fix for Essendat add to cart success message.
                        let isToastMsgAvailable = false;
                        if (window.E443304StopRedirectMvpAddtocart){
                            if (jQuery("#add-to-cart-toast-message-search").length) {
                                isToastMsgAvailable = true;
                                jQuery("#add-to-cart-toast-message-search").show();
                                jQuery("#add-to-cart-toast-message-search .success-toast-msg p").text('1 Item has been added to your cart.');
                            } else if (jQuery('body').hasClass('catalogsearch-result-index')) {
                                isToastMsgAvailable = true;
                                jQuery("#search-result-page-add-to-cart-toast").show();
                                jQuery("#search-result-page-add-to-cart-toast .success-toast-msg p").text('1 Item has been added to your cart.');
                                jQuery('html, body').animate({
                                    scrollTop: jQuery(".header-top").offset().top
                                }, 500);
                            }
                        }
                        if(!isToastMsgAvailable && jQuery("#add-to-cart-toast-message").length) {
                            jQuery("#add-to-cart-toast-message .success-toast-msg p").text('1 Item has been added to your cart.');
                            jQuery("#add-to-cart-toast-message").show();
                            jQuery('html, body').animate({
                                scrollTop: jQuery(".header-top").offset().top
                            }, 500);
                        }
                        if(data.isInBranchProductExist) {
                            inBranchWarning.inBranchWarningPopup();
                        }
                    }
                );
            }
        },
        setBundleProductJson: function(itemId, productJson) {
            bundleProductJsons[itemId] = productJson;
        },
        /**
         * Add bundle product to cart
         * @param sku - product identifier
         */
        addBundleProductToCart(e) {
            e.preventDefault();
            return function(sku) {
                window.location.href = urlBuilder.build(
                    `productbundle/cart/addfromals?sku=`+sku
                );
            }
        }
    }
});
