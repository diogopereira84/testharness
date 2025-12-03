define([
    'jquery',
    'uiComponent',
    'ko',
    'Fedex_Delivery/js/model/toggles-and-settings',
    'fedex/storage',
    "Fedex_MarketplaceCheckout/js/mirakl/model/quote-helper",
], function ($, Component, ko, togglesAndSettings, fxoStorage, quoteHelper) {
    'use strict';

    let isCustomerAcknowledgementThirdPartyEnabled = togglesAndSettings.isCustomerAcknowledgementThirdPartyEnabled;

    return Component.extend({
        defaults: {
            template: 'Fedex_MarketplaceCheckout/checkout/shipping-account-acknowledgement'
        },

        addedShippingAccountFromList: ko.observable(false),
        displayShippingAccountAcknowledgementError: ko.observable(false),
        hasShippingAccountManual: ko.observable(false),
        shippingAccountAcknowledgementErrorMessage: ko.observable(window?.checkoutConfig?.shipping_account_acknowledgement_error_message || ''),
        shippingAccountAcknowledgementMessage: ko.observable(window?.checkoutConfig?.shipping_account_acknowledgement_message || ''),
        shippingAccountCheckboxValue: ko.observable(false),
        infoErrorIconUrl: window?.checkoutConfig?.error_icon_url || '',

        initialize: function () {
            var self = this;
            this._super();

            // Reset value in case page reloads.
            fxoStorage.delete('shippingAccountCheckboxValue');

            window.addEventListener('fedexShippingAccountNumberChanged', (event) => {
                this.addedShippingAccountFromList(event.detail);
                this.shippingAccountCheckboxValue(false);
            });

            window.addEventListener('fedexShippingAccountAddedManually', (event) => {
                this.hasShippingAccountManual(event.detail?.length > 0);
            });

            window.addEventListener('displayShippingAccountAcknowledgementError', (event) => {
                this.displayShippingAccountAcknowledgementError(event.detail);
            });

            window.addEventListener('on_change_delivery_method', () => {
                const isPickup = (fxoStorage.get('chosenDeliveryMethod') || '').toLowerCase() !== 'shipping';
                this.displayShippingAccountAcknowledgementError(false);
                if (!quoteHelper.isAbleToUseShippingAccount() || isPickup) {
                    this.displayShippingAccountDisclaimerFor3P(false);
                }

                if (quoteHelper.isAbleToUseShippingAccount() && this.hasShippingAccountSet()) {
                    this.displayShippingAccountDisclaimerFor3P(true);
                }
            });

            window.addEventListener('resetShippingAccountAcknowledgementData', () => {
                this.resetValues();
            });

            this.shippingAccountCheckboxValue.subscribe((checked) => {
                fxoStorage.set('shippingAccountCheckboxValue', checked);

                if (checked) {
                    this.displayShippingAccountAcknowledgementError(false);
                    return;
                }

                if ($('div.checkout-shipping-method').css('display') !== 'none' && this.hasShippingAccountSet()) {
                    this.displayShippingAccountAcknowledgementError(true);
                    return;
                }

                window.dispatchEvent(new CustomEvent('resetShippingMethodsSection'));
            });

            this.displayShippingAccountAcknowledgementError.subscribe((displayError) => {
                if (!displayError) {
                    return;
                }

                window.dispatchEvent(new CustomEvent('displayShippingAccountAcknowledgementError', { detail: true }));
                const acknowledgementError = document.querySelector('#shipping_account_number_acknowledgement .error-warning');

                if (acknowledgementError) {
                    acknowledgementError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            this.hasShippingAccountSet = ko.computed(() => {
                window.dispatchEvent(new CustomEvent('resetShippingMethodsSection'));
                return this.addedShippingAccountFromList() || this.hasShippingAccountManual();
            });

            this.displayShippingAccountCheckbox = ko.computed(() => {
                const hasMktWithShippingAccount = quoteHelper.checkIfSomeSellerIsUsingShippingAccount();
                const shouldDisplayCheckbox = isCustomerAcknowledgementThirdPartyEnabled && hasMktWithShippingAccount && this.hasShippingAccountSet();

                if (this.hasShippingAccountSet() && quoteHelper.isAbleToUseShippingAccount()) {
                    this.displayShippingAccountDisclaimerFor3P(true);
                } else {
                    this.displayShippingAccountDisclaimerFor3P(false);
                }

                if (!shouldDisplayCheckbox) {
                    fxoStorage.set('displayShippingAccountCheckbox', false);
                    fxoStorage.delete('shippingAccountCheckboxValue');
                    return false;
                }

                this.shippingAccountCheckboxValue(false);
                fxoStorage.set('shippingAccountCheckboxValue', false);
                fxoStorage.set('displayShippingAccountCheckbox', true);

                return true;
            });

            return this;
        },

        displayShippingAccountDisclaimerFor3P: function (display) {
            window.dispatchEvent(new CustomEvent('displayShippingAccountDisclaimerFor3P', { detail: display }));
        },

        resetValues: function() {
            this.displayShippingAccountAcknowledgementError(false);
            this.shippingAccountCheckboxValue(false);
            fxoStorage.delete('shippingAccountCheckboxValue');
            fxoStorage.delete('displayShippingAccountCheckbox');
        }
    });
});
