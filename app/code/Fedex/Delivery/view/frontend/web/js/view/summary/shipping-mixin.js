define([
   'jquery',
   'ko',
   'Magento_Checkout/js/view/summary/abstract-total',
   'Magento_Checkout/js/model/quote',
   'Fedex_MarketplaceCheckout/js/mirakl/model/quote-helper',
   'fedex/storage'
], function ($, ko, Component, quote, quoteHelper,fxoStorage) {
    'use strict';
    return function (shipping) {
        return shipping.extend({
            showShippingTotal: ko.observable(),

            isInCartSummary: false,

            isInCheckoutSummary: false,

            value: ko.observable('TBD'),

            initialize: function (config) {
                this._super();

                this.showShippingTotal(this.handleShippingTotalVisibility());

                const self = this;

                window.addEventListener('on_change_delivery_method', function () {
                    let chosenDeliveryMethod;
                    if(window.e383157Toggle){
                        chosenDeliveryMethod = fxoStorage.get('chosenDeliveryMethod');
                    }else{
                        chosenDeliveryMethod = localStorage.getItem('chosenDeliveryMethod');
                    }
                    self.showShippingTotal(self.handleShippingTotalVisibility());
                    self.chosenDeliveryMethod = chosenDeliveryMethod;
                    self.getValue();
                });

                window.addEventListener('shipping_method', () => {
                    let selectedShippingMethodsStorage;
                    if(window.e383157Toggle){
                        selectedShippingMethodsStorage = fxoStorage.get('selectedShippingMethods');
                    }else{
                        selectedShippingMethodsStorage = localStorage.getItem('selectedShippingMethods');
                    }
                    self.selectedShippingMethods = selectedShippingMethodsStorage;
                    if(self.allShippingMethodsSelected()) {
                        self.getValue();
                    }
                })

                this.isInCartSummary     = config.isInCartSummary;
                this.isInCheckoutSummary = config.isInCheckoutSummary;
            },

            getValue: function () {
                var price;

                if (!this.isCalculated()) {
                    return this.notCalculatedMessage;
                }
                //var price =  this.totals().shipping_amount; //comment this line

                var shippingMethod = quote.shippingMethod(); //add these both line
                var price =  shippingMethod.amount; // update data on change of the shipping method
                var tax = $(".totals-tax .price").text().trim();
                if(tax == 'TBD'){
                    $(".opc-block-summary .table-totals .totals.shipping.excl").hide();
                }else{
                    $(".opc-block-summary .table-totals .totals.shipping.excl").show();
                }

                let marketplaceShippingPrice;
                if(window.e383157Toggle){
                    marketplaceShippingPrice = fxoStorage.get("marketplaceShippingPrice");
                }else{
                    marketplaceShippingPrice  = localStorage.getItem('marketplaceShippingPrice');
                }

                if(this.enableShippingFreeText()){
                    marketplaceShippingPrice = marketplaceShippingPrice ? +marketplaceShippingPrice : 0.00;
                } else {
                    marketplaceShippingPrice = typeof marketplaceShippingPrice === 'string' && marketplaceShippingPrice !== ''
                    ? +marketplaceShippingPrice
                    : 0.00;
                }

                price = marketplaceShippingPrice;
                $(".opc-block-summary .table-totals .totals.shipping.excl").show();

                const formattedPrice = this.getFormattedPrice(price);
                const zeroCostText = this.enableShippingFreeText() ? 'FREE' : 'TBD';
                price = price === 0.00 ? zeroCostText : formattedPrice;

                this.value(price);

                return price;
            },

            allShippingMethodsSelected: function () {
                const methods = $('.table-checkout-shipping-method').find('tbody');
                let isAllOptionsSelected = true;
                for( let i = 0; i < methods.length; i++) {
                    const currentDiv = $(methods[i]);
                    const anyChecked = currentDiv.find('input[type="radio"]:checked').length > 0;
                    if (!anyChecked) {
                        isAllOptionsSelected = false;
                    }
                }
                return isAllOptionsSelected;
            },

            handleShippingTotalVisibility: function () {
                const isFullMarketplaceQuote = quoteHelper.isFullMarketplaceQuote();

                const isMixedQuote = quoteHelper.isMixedQuote();

                let chosenDeliveryMethod;
                if(window.e383157Toggle){
                    chosenDeliveryMethod = fxoStorage.get('chosenDeliveryMethod');
                }else{
                    chosenDeliveryMethod = localStorage.getItem('chosenDeliveryMethod');
                }

                if (isFullMarketplaceQuote || isMixedQuote) {
                    return true;
                }

                if (chosenDeliveryMethod === 'shipping') {
                    return true;
                }

                return false;
            },

            /**
             * Is Essendant toggle enabled
             *
             * @return {string}
             */
            isEssendantEnabled: function () {
                return !!window.checkoutConfig?.tiger_enable_essendant;
            },

            isCBBEnabled: function () {
                return !!window.checkoutConfig?.tiger_enable_cbb
            },

            enableShippingFreeText: function () {
                return this.isCBBEnabled() || this.isEssendantEnabled();
            }
        });
    }});
