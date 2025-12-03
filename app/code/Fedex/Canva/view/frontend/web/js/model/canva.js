define([
    'jquery',
    'ko',
    'underscore',
    'Magento_Customer/js/customer-data',
    'Fedex_Pod/js/model/pod',
    'js-cookie/cookie-wrapper',
    'fedex/storage'
], function ($, ko, _, customerData, podModel, cookies,fxoStorage) {
    'use strict';

    return {

        customer: customerData.get('customer'),
        cart: customerData.get('cart'),
        pod: podModel,

        process: {
            EDITOR: 'EDITOR',
            LISTING: 'LISTING',
            POD: 'POD',
            EDIT: 'EDIT',
            POD_EDIT: 'POD_EDIT',
        },

        /**
         * @param {String} process
         * @param {Object} sku
         * @param {Object} pod
         * @param {Object} artwork
         * @param {String} errorPageReturnUrl
         */
        resetProcess: function (process,sku , pod, artwork, errorPageReturnUrl) {
            this.setProcess(process);
            this.setSku(sku);
            this.setPod(pod);
            this.setArtwork(artwork);
            this.setErrorReturnUrl(errorPageReturnUrl);
        },

        /**
         * @return {String}
         */
        getProcess: function () {
            if(window.e383157Toggle){
                return fxoStorage.get('canva-process') || this.process.LISTING;
            }else{
                return window.localStorage.getItem('canva-process') || this.process.LISTING;
            }
        },

        /**
         * @param {String} process
         */
        setProcess: function (process) {
            if(window.e383157Toggle){
                fxoStorage.set('canva-process', this.process[process] || this.process.LISTING);
            }else{
                window.localStorage.setItem('canva-process', this.process[process] || this.process.LISTING);
            }
        },

        /**
         * @param {String} errorPageReturnUrl
         */
        setErrorReturnUrl: function (errorPageReturnUrl) {
            if (!_.isEmpty(errorPageReturnUrl)) {
                $.cookie('canva_last_product_url', errorPageReturnUrl, {
                    expires: new Date(new Date().getTime() + (5 * 60 * 1000))
                })
            }
        },

        /**
         * @return {*}
         */
        getSku: function () {
            if(window.e383157Toggle){
                return fxoStorage.get('canva-sku') || {};
            }else{
                return JSON.parse(window.localStorage.getItem('canva-sku') || '{}');
            }
        },

        /**
         * @param {Object} data
         */
        setSku: function (data) {
            if(window.e383157Toggle){
                fxoStorage.set('canva-sku',data);
            }else{
                window.localStorage.setItem(`canva-sku`, JSON.stringify(data || {}));
            }
        },

        /**
         * @return {Object}
         */
        getPod: function () {
            if(window.e383157Toggle){
                return fxoStorage.get('canva-pod') || '{}';
            }else{
                return JSON.parse(window.localStorage.getItem('canva-pod') || '{}');
            }
        },

        /**
         * @param {Object} data
         */
        setPod: function (data) {
            if(window.e383157Toggle){
                fxoStorage.set('canva-pod',data);
            }else{
                window.localStorage.setItem(`canva-pod`, JSON.stringify(data || {}));
            }
        },

        /**
         * @return {Object}
         */
        getArtwork: function () {
            if(window.e383157Toggle){
                return fxoStorage.get('canva-artwork') || '{}';
            }else{
                return JSON.parse(window.localStorage.getItem('canva-artwork') || '{}');
            }
        },

        /**
         * @param {Object} data
         */
        setArtwork: function (data) {
            if(window.e383157Toggle){
                fxoStorage.set('canva-artwork',data);
            }else{
                window.localStorage.setItem(`canva-artwork`, JSON.stringify(data || {}));
            }
        },

        /**
         * @return {Array}
         */
        getCartItems: function () {
            return this.pod.getCartItems().filter(function (item) {
                if (_.has(item, 'externalProductInstance')) {
                    if (_.has(item.externalProductInstance, 'fxoProductInstance')) {
                        if (_.has(item.externalProductInstance.fxoProductInstance, 'productConfig')) {
                            if (_.has(item.externalProductInstance.fxoProductInstance.productConfig, 'designProduct')) {
                                return true;
                            }
                        }
                    } else if(_.has(item.externalProductInstance, 'productConfig')) {
                        if (_.has(item.externalProductInstance.productConfig, 'designProduct')) {
                            return true;
                        }
                    }
                }
                return false;
            });
        },

        /**
         * Returns the cart Items
         */
        getCartItemByDesignId: function (designId) {
            return this.getCartItems().find((item) => {
                    if (item.externalProductInstance && _.has(item.externalProductInstance, 'fxoProductInstance')) {
                        return item.externalProductInstance.fxoProductInstance.productConfig.designProduct.designId === designId
                    } else if(_.has(item.externalProductInstance, 'productConfig')) {
                        if (_.has(item.externalProductInstance.productConfig, 'designProduct')) {
                            return item.externalProductInstance.productConfig.designProduct.designId === designId
                        }
                    }
            });
        },

        /**
         * Returns the cart Item Id by DesignId
         */
        getCartItemIdByDesignId: function (designId) {
            const item = this.getCartItemByDesignId(designId);
            if (!_.isEmpty(item) && _.has(item, 'item_id')) {
                return item.item_id;
            }
            return null;
        },

        /**
         * Returns the cart item instance id
         *
         * @return {string, null}
         */
        getItemInstanceIdBy: function (designId) {
            const item = this.getCartItemByDesignId(designId);
            if (!_.isEmpty(item) && _.has(item.externalProductInstance, 'instanceId')) {
                if (_.isEmpty(item.externalProductInstance.instanceId) && _.has(item.externalProductInstance, 'fxoProductInstance')) {
                    if (_.has(item.externalProductInstance.fxoProductInstance, 'productConfig')) {
                        if (_.has(item.externalProductInstance.fxoProductInstance.productConfig, 'product')) {
                            if (_.has(item.externalProductInstance.fxoProductInstance.productConfig.product, 'instanceId')) {
                                if (!_.isEmpty(item.externalProductInstance.fxoProductInstance.productConfig.product.instanceId)) {
                                    return item.externalProductInstance.fxoProductInstance.productConfig.product.instanceId;
                                }
                            }
                        }
                    }
                } else if(_.has(item.externalProductInstance, 'productConfig')) {
                    if (_.has(item.externalProductInstance.productConfig, 'product')) {
                        if (_.has(item.externalProductInstance.productConfig.product, 'instanceId')) {
                            if (!_.isEmpty(item.externalProductInstance.productConfig.product.instanceId)) {
                                return item.externalProductInstance.productConfig.product.instanceId;
                            }
                        }
                    }
                }
                return item.externalProductInstance.instanceId;
            }
            return null;
        },

        /**
         * Returns the cart item instance id
         *
         * @return {string, null}
         */
        getItemProductConfig: function (designId) {
            const item = this.getCartItemByDesignId(designId);
            if (!_.isEmpty(item) && _.has(item.externalProductInstance, 'fxoProductInstance')) {
                if (_.has(item.externalProductInstance.fxoProductInstance, 'productConfig')) {
                    return item.externalProductInstance.fxoProductInstance.productConfig;
                }
            } else if(!_.isEmpty(item) && _.has(item.externalProductInstance, 'productConfig')) {
                return item.externalProductInstance.productConfig;
            }
            return null;
        },

        /**
         * @return {Object}
         */
        getProduct: function () {
            return {
                ...this.getArtwork(),
                is_editing: (!_.isEmpty(this.getCartItemByDesignId(this.getArtwork().designId))),
                instanceId: this.getItemInstanceIdBy(this.getArtwork().designId),
                productConfig: {
                    ...this.getItemProductConfig(this.getArtwork().designId),
                    cartItemId: this.getCartItemIdByDesignId(this.getArtwork().designId)
                }
            };
        },

        setDesignOptions: function (payload) {
            if(window.e383157Toggle){
                fxoStorage.set('canva-design-options',payload);
            }else{
                window.localStorage.setItem(`canva-design-options`, JSON.stringify(payload || {}));
            }
        },

        getDesignOptions: function () {
            if(window.e383157Toggle){
                return fxoStorage.get('canva-design-options');
            }else{
                return JSON.parse(window.localStorage.getItem('canva-design-options') || '{}');
            }
        },

        clearDesignOptions: function () {
            if(window.e383157Toggle){
                fxoStorage.delete('canva-design-options');
            }else{
                window.localStorage.removeItem('canva-design-options');
            }
          }
    };
});
