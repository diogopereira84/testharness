define([
    'jquery',
    'underscore',
    'uiComponent',
    "uiRegistry",
    'Magento_Checkout/js/model/quote',
    'fedex/storage'
], function ($, _, Component, registry, quote, fxoStorage) {
    'use strict';

    const storageKey = 'shipping-freight';
    const useNewLocalStorage = window.e383157Toggle;

    return Component.extend({
        defaults: {
            template: 'Fedex_MarketplaceCheckout/checkout/shipping-freight',
            hasLiftGate: '',
            selectionMissing: false
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super().initStorage().initSubscribe();

            this.source = registry.get('checkoutProvider');

            this.validation();

            return this;
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super().observe(['hasLiftGate', 'selectionMissing']);

            return this;
        },

        /**
         * Initialize hasLiftGate value
         */
        initStorage: function () {
            const hasLiftGate = this.getStorage();
            this.hasLiftGate(hasLiftGate);

            return this;
        },
        /**
         * Subscribe to hasLiftGate changes
         */
        initSubscribe: function () {
            this.hasLiftGate.subscribe(function (value) {
                if (value) {
                    this.selectionMissing(false);
                    this.setStorage(value);
                    this.hideShippingMethods();
                }
            }, this);

            return this;
        },

        /**
         * Check if hasLiftGate is selected
         */
        validation: function () {
            this.source.on('shippingAddress.data.validate', function () {
                if (_.isNull(this.hasLiftGate())) {
                    this.source.set("params.invalid", true);
                    this.selectionMissing(true);
                }
            }.bind(this));
        },

        /**
         *
         * @param value
         */
        setStorage: function (value) {
            if (useNewLocalStorage) {
                fxoStorage.set(storageKey, value);
            } else {
                localStorage.setItem(storageKey, value);
            }
        },

        /**
         *
         * @returns {*|string}
         */
        getStorage: function () {
            return useNewLocalStorage ? fxoStorage.get(storageKey) : localStorage.getItem(storageKey);
        },

        /**
         * Hide shipping methods by triggering a change event on the address form fields.
         */
        hideShippingMethods: function () {
            $("#shipping-new-address-form .address-field .form-input").change();
        },
    });
});
