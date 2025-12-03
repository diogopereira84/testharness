define([
    'jquery',
    'underscore',
    'uiComponent',
    "uiRegistry",
    "ko",
    'fedex/storage',
    "mage/url",
    'AccessibleSelect',
    'Fedex_Delivery/js/model/toggles-and-settings',
    "shippingFormAdditionalScript",
    "Fedex_MarketplaceCheckout/js/mirakl/model/quote-helper",
    'Fedex_Delivery/js/view/shipping',
    'Fedex_Delivery/js/view/shipping-refactored',
    "Fedex_Recaptcha/js/reCaptcha",
], function ($, _, Component, registry, ko, fxoStorage, urlBuilder, AccessibleSelect, togglesAndSettings, shippingFormAdditionalScript, marketplaceQuoteHelper, shipping, shippingRefactored, reCaptcha) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Fedex_EnhancedProfile/view/checkout/shipping_step/fedex-shipping-account',
            hasLiftGate: '',
            selectionMissing: false
        },
        shippingAccountNumber: ko.observable(""),
        selectedShippingAccountNumberLabel: ko.observable(""),
        shippingAccountNumberPlaceHolder: ko.observable(""),
        isD207891Toggle: ko.observable(window?.checkoutConfig?.d207891_toggle),
        infoUrl: ko.observable(window.checkoutConfig.media_url + "/information.png"),
        isOpen: ko.observable(false),
        isCommercial: window.checkoutConfig?.is_commercial,
        defaultAccountNumberIndex: false,
        isMaskonAutopopulate: ko.observable(false),
        shouldShowShippingAccountDropdown: ko.observable(false),
        personalShippingAccounts: ko.observable([]),
        companyShippingAccount: ko.observable(null),
        defaultPersonalShippingAccount: ko.observable(null),

        toggleDropdown: function () {
            this.isOpen(!this.isOpen());
        },

        closeDropdown: function() {
            this.isOpen(false);
        },

        initialize: function () {
            var self = this;
            this._super();

            this._initializeAccessibilityHandlers();

            self.shouldShowShippingAccountDropdown(self.canShowShippingAccountDropdown());
            window.addEventListener('on_change_delivery_method', () => {
                self.shouldShowShippingAccountDropdown(self.canShowShippingAccountDropdown(fxoStorage.get('chosenDeliveryMethod')));
            });

            self.shippingAccountNumber.subscribe(function (newValue) {
                registry.async('checkout.steps.shipping-step.shippingAddress')(
                    function (shippingAddressComponent) {
                        if (shippingAddressComponent && ko.isObservable(shippingAddressComponent.shippingAccountNumber)) {
                            shippingAddressComponent.shippingAccountNumber(newValue);
                            shippingAddressComponent.isApplyShippingAccountNumber(newValue, newValue ? 'apply' : 'remove');
                        }
                    }
                );
            });

            const defaultAccountNumber = ko.computed(function () {
                const hasDefaultShippingAccount = self.companyShippingAccount() || self.defaultPersonalShippingAccount();
                const shouldShowShippingAccountDropdown = window.checkoutConfig?.tiger_B2027702_vendor_shipping_account_enable
                    ? self.shouldShowShippingAccountDropdown()
                    : true;

                if( hasDefaultShippingAccount && shouldShowShippingAccountDropdown) {
                    if(self.companyShippingAccount()) {
                        return self.companyShippingAccount();
                    } else {
                        return self.defaultPersonalShippingAccount();
                    }
                }

                return false;
            });

            defaultAccountNumber.subscribe(function (newValue) {
                if(newValue) {
                    self.selectAccount(newValue);
                    defaultAccountNumber.dispose();
                }
            });

            this.updateInitialValues(self);

            this.companyShippingAccount(window.checkoutConfig?.shipping_account_number || null);

            this._loadPersonalShippingAccounts();

            return this;
        },

        _initializeAccessibilityHandlers: function() {
            let accessibilityAdapter = {
                toggle: this.toggleDropdown.bind(this),
                close: this.closeDropdown.bind(this),
                isOpen: this.isOpen.bind(this),
                select: this.selectAccount.bind(this),
                focusFirst: this.focusFirstOption.bind(this),
                focusSelect: this.focusSelectElement.bind(this),
                navigate: this.navigateOptions.bind(this)
            };

            this.accessibilityKeydownHandlers = AccessibleSelect.createHandlers(accessibilityAdapter);
        },

        _loadPersonalShippingAccounts: function () {
            let self = this;
            const retailProfileSession = window.checkoutConfig?.retail_profile_session || false;
            if (!retailProfileSession?.output?.profile?.accounts?.length) {
                return [];
            }

            const personalShippingAccounts = retailProfileSession.output.profile.accounts.filter(function (item, index) {
                if(item.accountType === 'SHIPPING') {
                    item.shippingListLabel = 'FedEx Account <em> ending in ' + item.maskedAccountNumber + ' </em>';
                    item.index = index;
                    if(item.primary) {
                        self.defaultPersonalShippingAccount(item);
                    }
                    return true;
                }
                return false;
            });

            this.personalShippingAccounts(personalShippingAccounts);
        },

        isToggleEnabled: function (toggleName) {
            return togglesAndSettings[toggleName] !== undefined ? togglesAndSettings[toggleName] : false;
        },

        isLoggedIn: function () {
            return typeof (window.checkoutConfig.isCustomerLoggedIn) != 'undefined' && window.checkoutConfig.isCustomerLoggedIn != null ? window.checkoutConfig.isCustomerLoggedIn : false;
        },

        updateInitialValues: function (self) {
            shippingFormAdditionalScript.autoPopulateShippingAccountNumber(self);
        },

        customerHasAnyShippingAccount: function () {
            if (!this.isLoggedIn()) {
                return false;
            }

            return this.personalShippingAccounts().length > 0 || this.companyShippingAccount() !== null;
        },

        getCompanyShippingAccount: function () {
            return window.checkoutConfig?.shipping_account_number || null;
        },

        getCompanyAccountNumberLabel: function (fedExAccountNumber) {
            fedExAccountNumber = this.getFedexAccountNumberMasked(fedExAccountNumber);
            return 'FedEx Account <em> ending in ' + fedExAccountNumber + ' </em>';
        },

        getCompanyAccountClassificationLabel: function () {
            const companyName = window.checkoutConfig?.company_name || 'Site Name';
            return 'Saved Shipping Account Number (' + companyName + ')';
        },

        getFedexAccountNumberMasked: function (fedExAccountNumber) {
            fedExAccountNumber = fedExAccountNumber.slice(fedExAccountNumber.length - 4);
            return '*' + fedExAccountNumber;
        },

        normalizeAccountNumber: function (accountNumber) {
            return typeof accountNumber === 'object' ? accountNumber.accountNumber : accountNumber;
        },

        _persistAccountNumberInStorage: function(accountNumber) {
            if (window.e383157Toggle) {
                fxoStorage.set('shipping_account_number', accountNumber);
            } else {
                localStorage.setItem('shipping_account_number', accountNumber);
            }
        },

        // TODO: Rename this method to selectAccount once tiger_B2027702_vendor_shipping_account_enable toggle is removed
        selectAccountRefactored: async function(accountNumber) {
            let normalizedAccountNumber = this.normalizeAccountNumber(accountNumber);

            if (normalizedAccountNumber !== '' && !await this.isShippingAccountValid(normalizedAccountNumber)) {
                this.selectAccount('');
                return false;
            }
            if(normalizedAccountNumber === this.getCompanyShippingAccount()) {
                this.selectedShippingAccountNumberLabel(this.getCompanyAccountNumberLabel(normalizedAccountNumber));
            } else {
                this.selectedShippingAccountNumberLabel(accountNumber.shippingListLabel);
            }
            this.shippingAccountNumber(normalizedAccountNumber);

            this._persistAccountNumberInStorage(normalizedAccountNumber);

            this.isOpen(false);

            this.focusSelectElement();
        },

        selectAccount: async function (accountNumber) {
            if (window.checkoutConfig?.tiger_B2027702_vendor_shipping_account_enable) {
                return this.selectAccountRefactored(accountNumber);
            }

            if (accountNumber !== '' && !await this.isShippingAccountValid(accountNumber.accountNumber ? accountNumber.accountNumber : accountNumber)) {
                this.selectAccount('');
                return false;
            }
            if(accountNumber === this.getCompanyShippingAccount()) {
                this.selectedShippingAccountNumberLabel(this.getCompanyAccountNumberLabel(accountNumber));
            } else {
                this.selectedShippingAccountNumberLabel(accountNumber.shippingListLabel);
            }
            this.shippingAccountNumber(accountNumber.accountNumber ? accountNumber.accountNumber : accountNumber);
            this.isOpen(false);

            this.focusSelectElement();
        },

        handleSelectKeydown: function(data, event) {
            return this.accessibilityKeydownHandlers.selectKeydownHandler(data, event);
        },

        handleOptionKeydown: function(data, event) {
            return this.accessibilityKeydownHandlers.optionKeydownHandler(data, event);
        },

        focusFirstOption: function() {
            requestAnimationFrame(function() {
                $('.fxo-select-option:visible:first').not('[aria-disabled="true"]').focus();
            });
        },

        focusSelectElement: function() {
            requestAnimationFrame(function() {
                $('.fxo-select-field').focus();
            });
        },

        /**
         * @returns Bool
         */
        isFullMarketplaceQuote: function () {
            return marketplaceQuoteHelper.isFullMarketplaceQuote();
        },

        /**
         * @returns Bool
         */
        isMixedQuote: function () {
            return marketplaceQuoteHelper.isMixedQuote();
        },

        /**
         * @returns Bool
         */
        isAbleToUseShippingAccount: function () {
            return marketplaceQuoteHelper.isAbleToUseShippingAccount();
        },

        navigateOptions: function(currentElement, direction) {
            const $current = $(currentElement);
            const $options = $current.closest('.fxo-select').find('.fxo-select-option:visible').not('[aria-disabled="true"]');
            const currentIndex = $options.index($current);
            let nextIndex;

            if (direction === 'down') {
                nextIndex = currentIndex < $options.length - 1 ? currentIndex + 1 : 0;
            } else {
                nextIndex = currentIndex > 0 ? currentIndex - 1 : $options.length - 1;
            }

            $options.eq(nextIndex).focus();
        },

        isShippingAccountValid: async function (shippingAccountNumber) {
            if (!window.checkoutConfig.tigerteamE469373enabled || !shippingAccountNumber) {
                window.checkoutConfig.isValidShippingAccount = true;
                return true;
            }

            if (!window.checkoutConfig?.tiger_B2027702_vendor_shipping_account_enable) {
                // TODO - Remove all this code once tiger_B2027702_vendor_shipping_account_enable toggle is removed
                if (window.e383157Toggle) {
                    fxoStorage.set('shipping_account_number', shippingAccountNumber);
                } else {
                    localStorage.setItem('shipping_account_number', shippingAccountNumber);
                }
            }

            let errorMessage = '';
            let isValidAccount = false;
            try {
                const validationResult = await this.validateShippingAccountNumber(shippingAccountNumber);
                isValidAccount = !validationResult.error;

                if (isValidAccount) {
                    $("#fedExAccountNumber_validate").removeClass('error-icon').text('');
                    window.checkoutConfig.isValidShippingAccount= true;
                    return true;
                } else {
                    errorMessage = validationResult.error && validationResult.message === undefined
                        ? 'Please enter a valid shipping account number.'
                        : 'Unable to validate due to system error. Please try again.';
                }
            } catch (e) {
                errorMessage = 'Unable to validate due to system error. Please try again.';
            }

            window.checkoutConfig.isValidShippingAccount = isValidAccount;
            if (errorMessage) {
                $("#fedExAccountNumber_validate").addClass('error-icon').text(errorMessage);
            }

            return false;
        },

        validateShippingAccountNumber: async function (shippingAccountNumber) {

            let responseData = {};
            let fedexShippingAccountPayload = { fedexShippingAccountNumber: shippingAccountNumber };

            await reCaptcha.waitForCaptchaInitialized();
            await reCaptcha.addRecaptchaTokenToPayload(fedexShippingAccountPayload, 'checkout_shipping_account_validation');

            try {
                responseData = await $.ajax({
                    url: urlBuilder.build('accountvalidationapi/index/index'),
                    type: 'POST',
                    data: fedexShippingAccountPayload,
                    showLoader: true
                });
            } catch (error) {
                responseData.error = true;
            }
            return responseData;
        },

        /**
         * @returns Bool
         */
        canShowShippingAccountDropdown: function () {
            return this.isLoggedIn() && this.customerHasAnyShippingAccount() && this.isAbleToUseShippingAccount();
        },
    });
});
