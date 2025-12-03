define([
    'jquery',
    'uiComponent',
    'ko',
    'Fedex_MarketplaceCheckout/js/mirakl/model/quote-helper',
    'Fedex_ExpressCheckout/js/fcl-profile-session',
    'checkout-common',
    'fedex/storage'
],
    function (
        $,
        Component,
        ko,
        quoteHelper,
        profileSessionBuilder,
        checkoutCommon,
        fxoStorage
    ) {
        'use strict';

        let self;

        return Component.extend({

            FEDEX_SELLER_NAME: 'FedEx Office',

            isPickupAvailable: window.checkoutConfig.is_pickup,

            isOutSourced: window.checkoutConfig.is_out_sourced,

            isDeliveryAvailable: window.checkoutConfig.is_delivery,

            isSelfregCustomer: window.checkoutConfig.is_selfreg_customer,

            shouldSelfregDisplayDiscount3pOnly: window.checkoutConfig.tiger_display_selfreg_cart_fxo_discount_3P_only,

            chosenDeliveryMethod: window.e383157Toggle ? fxoStorage.get('chosenDeliveryMethod') : localStorage.getItem('chosenDeliveryMethod'),

            isChosenDeliveryMethodManuallySet:  (window.e383157Toggle ? fxoStorage.get('chosenDeliveryMethodManuallySet')
                : localStorage.getItem('chosenDeliveryMethodManuallySet')) === 'true' ,

            preferredDeliveryMethod: window.e383157Toggle ? fxoStorage.get('preferredDeliveryMethod') : localStorage.getItem('preferredDeliveryMethod'),
            promoCodeMessageEnabledToggle: window.checkoutConfig.promoCodeMessageEnabledToggle,
            allSellersInfo: ko.observable([]),

            initialize: function (config) {
                this._super();
                self = this;

                this.allSellersInfo(this.getAllSellersInfo());

                this.handlePromoMessagesVisiblity(config.combinedWarningMessageEnable);

                this.handleInitialDeliveryMethod();
                // Trigger an update to the cart items and display the expired messages
                window.addEventListener('check_disabled_products', function() {
                    self.allSellersInfo(self.getAllSellersInfo());
                });
            },

            isPreferredDeliveryMethod(method = '') {
                if (!this.isPickupAvailable && method === 'pick-up') {
                    return '';
                }

                if (method === this.chosenDeliveryMethod) {
                    return 'selected';
                }

                return '';
            },

            handlePromoMessages: function (promoCodeElementClass, content, couponCode) {
                if (content !== '') {
                    let messageContent = $(`.block.discount.${promoCodeElementClass} .message-content`);
                    let message = $(`.block.discount.${promoCodeElementClass}`);
                    let closeButton = $(`.block.discount.${promoCodeElementClass} .close-icon button`);

                    messageContent.text(content);
                    message.show();

                    closeButton.on('click', function () {
                        message.hide();
                    })
                }
            },

            isPickupVisible: function (seller) {
                return seller.name == this.FEDEX_SELLER_NAME && this.isPickupAvailable && !this.isOutSourced;
            },

            getAllSellersInfo: function () {
                const { quoteItemData } = window.checkoutConfig;

                let sellersInCart = quoteItemData.map((item) => {
                    const DEFAULT_RETURN = {name: this.FEDEX_SELLER_NAME, tooltip: ''};

                    let name = '';
                    let tooltip = '';
                    let isMarketplaceProduct = false;

                    if (item.additional_data !== null) {
                        JSON.parse(item.additional_data);
                    }

                    if (!isMarketplaceProduct && (!item.mirakl_offer_id || !item.offer_id)) return DEFAULT_RETURN;
                    if (!item.seller_name && !item.tooltip) return DEFAULT_RETURN;

                    name = item.seller_name;
                    tooltip = item.tooltip;

                    // Save the seller name and tooltip in the storage
                    if (window.e383157Toggle) {
                        fxoStorage.set(`tooltip-${name}`, tooltip);
                    } else {
                        localStorage.setItem(`tooltip-${name}`, tooltip);
                    }

                    return {name, tooltip};
                })
                    .sort((a, b) => {
                        if (a.name == this.FEDEX_SELLER_NAME) return -1;
                        return 1;
                    });

                return this.filterDuplicateSellers(sellersInCart);
            },

            filterDuplicateSellers: function (sellers) {
                return sellers.filter((seller, index, array) =>
                    index === array.findIndex((item) => (
                        item.name === seller.name // Check if seller already exists inside the array
                    ))
                );
            },

            compareItemSellerName: function (data) {
                let { quoteItemData } = window.checkoutConfig;
                let itemSellerName = quoteItemData.find(item => item.item_id == data.id);

                if (itemSellerName.seller_name) {
                    itemSellerName = itemSellerName.seller_name;
                } else {
                    itemSellerName = this.FEDEX_SELLER_NAME;
                }


                if (itemSellerName == data.sellerName) return true;

                return false;
            },

            setDeliveryMethod: function (deliveryMethod) {
                if(window.e383157Toggle){
                    fxoStorage.set('chosenDeliveryMethod', deliveryMethod);
                }else{
                    localStorage.setItem('chosenDeliveryMethod', deliveryMethod);
                }
                window.dispatchEvent(new Event('on_change_delivery_method'));

                const delivery = (deliveryMethod === 'pick-up') ? 'pickup' : deliveryMethod;
                self.handleSelected(`${delivery}Label`);
            },

            handleInitialDeliveryMethod: function () {
                const self = this;

                this.sanitizeLocalStorage();

                let customerHasDeliveryMethod = profileSessionBuilder.getPreferredDeliveryMethod();
                let preferredDeliveryMethod;

                if (customerHasDeliveryMethod && customerHasDeliveryMethod.delivery_method) {
                    preferredDeliveryMethod = customerHasDeliveryMethod.delivery_method;
                    preferredDeliveryMethod = preferredDeliveryMethod === 'DELIVERY' ? 'shipping' : 'pick-up';
                    if(window.e383157Toggle){
                        fxoStorage.set('preferredDeliveryMethod',preferredDeliveryMethod);
                    }else{
                        localStorage.setItem('preferredDeliveryMethod',preferredDeliveryMethod);
                    }
                    this.preferredDeliveryMethod = preferredDeliveryMethod;

                    if(!this.isChosenDeliveryMethodManuallySet) {
                        this.chosenDeliveryMethod = preferredDeliveryMethod;
                        this.setDeliveryMethod(preferredDeliveryMethod);
                    }
                }

                if (!this.isDeliveryAvailable) {
                    this.setDeliveryMethod('pick-up');
                    return;
                }

                if (!this.isPickupAvailable) {
                    this.setDeliveryMethod('shipping');
                    return;
                }

                if (preferredDeliveryMethod && !this.chosenDeliveryMethod) {
                    this.setDeliveryMethod(preferredDeliveryMethod);
                }

                else if (
                    this.chosenDeliveryMethod === 'shipping'
                    || this.chosenDeliveryMethod === 'pick-up' && quoteHelper.isFullMarketplaceQuote()
                    || !this.chosenDeliveryMethod && quoteHelper.isFullMarketplaceQuote()
                ) {
                    this.setDeliveryMethod('shipping');
                }

                else if (
                    this.chosenDeliveryMethod === 'pick-up'
                    || !this.chosenDeliveryMethod && quoteHelper.isMixedQuote()
                    || !this.chosenDeliveryMethod && !quoteHelper.isFullMarketplaceQuote()
                ) {
                    this.setDeliveryMethod('pick-up');
                }
            },

            handleSelected: function (target) {
                const alternateMethod = target === 'pickupLabel' ? 'shippingLabel' : 'pickupLabel';

                const selectedShipping = {
                    pickupLabel: document.querySelector('.in-storepickup.method'),
                    shippingLabel: document.querySelector('.shipping.method')
                }

                const { parentNode, classList } = selectedShipping[target];

                if (parentNode.classList.contains('marketplace-seller')) {
                    return;
                }

                selectedShipping[alternateMethod].classList.remove('selected');

                classList.add('selected');

                window.dispatchEvent(new Event('on_change_delivery_method'));
            },

            setPickupDeliveryMethod: function (data, event) {
                if(window.e383157Toggle){
                    fxoStorage.set('chosenDeliveryMethod', 'pick-up');
                    if (event) {
                        fxoStorage.set('chosenDeliveryMethodManuallySet', true);
                    }
                }else{
                    localStorage.setItem('chosenDeliveryMethod', 'pick-up');
                    if (event) {
                        localStorage.setItem('chosenDeliveryMethodManuallySet', true);
                    }
                }

                window.dispatchEvent(new Event('on_change_delivery_method'));

                self.handleSelected('pickupLabel');
            },

            setShippingDeliveryMethod: function (data, event) {
                if (event?.target?.parentNode?.classList?.contains('marketplace-seller')) {
                    return;
                }
                if (window.e383157Toggle) {
                    fxoStorage.set('chosenDeliveryMethod', 'shipping');
                    if (event) {
                        fxoStorage.set('chosenDeliveryMethodManuallySet', true);
                    }
                } else {
                    localStorage.setItem('chosenDeliveryMethod', 'shipping');
                    if (event) {
                        localStorage.setItem('chosenDeliveryMethodManuallySet', true);
                    }
                }
                window.dispatchEvent(new Event('on_change_delivery_method'));

                self.handleSelected('shippingLabel');
            },

            /**
             * In case the customer initializes a new quote it will sanitize the localStorage and set the correct delivery method.
             */
            sanitizeLocalStorage: function () {
                const quoteId = window.checkoutConfig.quoteId;
                let localStorageQuoteId;
                if(window.e383157Toggle){
                    localStorageQuoteId = fxoStorage.get('quoteId') || '';
                }else{
                    localStorageQuoteId = localStorage.getItem('quoteId') || '';
                }
                if (quoteId !== localStorageQuoteId) {
                    if(window.e383157Toggle){
                        fxoStorage.delete('preferredDeliveryMethod');
                        fxoStorage.delete('quoteId');
                        fxoStorage.delete('selectedShippingMethods');
                        fxoStorage.delete('shipping_account_number');
                        fxoStorage.delete('quoteId', quoteId);
                    }else{
                        localStorage.removeItem('preferredDeliveryMethod');
                        localStorage.removeItem('quoteId');
                        localStorage.removeItem('selectedShippingMethods');
                        localStorage.removeItem('shipping_account_number');
                        localStorage.setItem('quoteId', quoteId);
                    }
                }
            },

            isProductDisabled: function (productInstanceId) {
                productInstanceId = productInstanceId || "";
                const disabledProducts = window.checkout.disabledProducts || [];

                if ( disabledProducts.includes(productInstanceId.toString()) ) {
                    return true;
                }

                return false;
            },

            handlePromoMessagesVisiblity: function(combinedWarningMessageEnable = false) {

                const cfg = window.checkoutConfig;
                const couponCode = cfg.totalsData.coupon_code || '';
                const promoCodeMessage = cfg.promoCodeMessage || '';
                const combinedPromoCodeMessage = cfg.promo_code_combined_discount_message || '';
                const showPromoCodeCombinedDiscountMessage = cfg.show_promo_code_combined_discount_message || '';
                let fedexAccountNumber = cfg.fedex_account_number ? cfg.fedex_account_number : cfg.fedex_account_number_discount;
                    fedexAccountNumber = fedexAccountNumber || '';

                // Promo Code	    | Payment Account	| Non combinable message	| Marketplace discount disclaimer
                // ---------------------------------------------------------------------------------------
                // Yes	            | No	            | No	                    | Yes
                // No	            | Yes	            | No	                    | Yes
                // Yes	            | Yes	            | No	                    | Yes
                // Yes	            | Not Combinable	| Yes	                    | Yes
                // Not Combinable	| Yes	            | Yes	                    | Yes

                // Marketplace discount disclaimer should be visible at all times if promoCodeMessageEnabledToggle is enabled
                function handlePromoMessagesIfApplicable() {
                    // 1. Has coupon Code or Fedex Account Number with a mixed quote = Show discount message
                    if ((couponCode.length || fedexAccountNumber.length) && quoteHelper.isMixedQuote()) {
                        this.handlePromoMessages('discount-message', promoCodeMessage);
                    }

                    // 2. Has a coupon Code, Fedex Account Number and receives the combine warning message flag = Show combined discount message
                    if ((combinedWarningMessageEnable && showPromoCodeCombinedDiscountMessage) &&
                        (couponCode.length || fedexAccountNumber.length)) {
                        this.handlePromoMessages('combined-discount-message', combinedPromoCodeMessage);
                    }
                }

                if (this.promoCodeMessageEnabledToggle) {
                    handlePromoMessagesIfApplicable.call(this);
                }
            }
        });
    })
