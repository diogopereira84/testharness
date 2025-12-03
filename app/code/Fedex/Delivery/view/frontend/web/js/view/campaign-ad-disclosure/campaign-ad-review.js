define([
    'uiComponent',
    'ko',
    'jquery',
    'Fedex_Delivery/js/model/campaign-ad-disclosure',
    'uiRegistry',
    'fedex/storage',
    'Magento_Ui/js/lib/view/utils/dom-observer'
], function (Component, ko, $, disclosureModel, registry, fxoStorage, $do) {
    'use strict';
    return Component.extend({
        defaults: {
            template: 'Fedex_Delivery/campaign-ad-disclosure/campaign-ad-review' 
        },
        
        disclosureModel: disclosureModel,
        isCampaignAd: disclosureModel.isCampaignAd,
        greenCheckUrl: window.checkoutConfig.media_url + "/checkgreen.png",
        attentionIconUrl: window.checkoutConfig.xmen_order_approval_warning_icon,
        incompleteCheckmarkIconUrl: window.checkoutConfig.incomplete_checkmark_icon_url,
        campaingDetails: ko.observable({}),
        shouldDisplayQuestionnaireInReviewPage: disclosureModel.shouldDisplayQuestionnaireInReviewPage,
        isCampaignAdDisclosureComplete: disclosureModel.isCampaignAdDisclosureComplete,
        customerName: ko.observable(''),

        initialize: function () {
            const self = this;
            this._super();

            this.sectionIcon = ko.computed(() => {
                if (!this.isCampaignAdDisclosureComplete()) {
                    return this.incompleteCheckmarkIconUrl;
                }

                return this.greenCheckUrl;
            });

            this.getCustomerName();
            this.checkIsCampaignAd();

            this.disclosureModel.isCampaignAd.subscribe(() => {
                self.getCustomerName();
            });

            $do.get('#place-order-trigger', () => {
                self.handleSubmitCTA(self.isCampaignAdDisclosureComplete());
                
                if (!self.isCampaignAdDisclosureComplete()) {
                    self.openModal();
                }

                self.isCampaignAdDisclosureComplete.subscribe((newValue) => {
                    self.handleSubmitCTA(newValue);
                });
            });

            this.handleDisplaymentOnReload();
        },

        handleDisplaymentOnReload: function() {
            const shippingStateCode      = fxoStorage.get('stateOrProvinceCode');
            const pickupStateCode        = fxoStorage.get('pickupData')?.addressInformation?.pickup_location_state;
            const pickupShippingComboKey = fxoStorage.get('pickupShippingComboKey') === 'true';
            const chosenDeliveryMethod   = fxoStorage.get('chosenDeliveryMethod');

            if (this.shouldDisplayQuestionnaireInReviewPage()) {
                return;
            }

            const shouldCheckForBoth         = typeof pickupStateCode === 'string' && typeof shippingStateCode === 'string' && pickupShippingComboKey;
            const shouldCheckForPickupOnly   = typeof pickupStateCode === 'string' && chosenDeliveryMethod === 'pick-up' && !pickupShippingComboKey;
            const shouldCheckForShippingOnly = typeof shippingStateCode === 'string' && chosenDeliveryMethod === 'shipping' && !pickupShippingComboKey;

            if (shouldCheckForBoth) {
                disclosureModel.setShouldDisplayQuestionnaire(pickupStateCode, shippingStateCode);
            } else if (shouldCheckForPickupOnly) {
                disclosureModel.setShouldDisplayQuestionnaire(pickupStateCode);
            } else if (shouldCheckForShippingOnly) {
                disclosureModel.setShouldDisplayQuestionnaire(shippingStateCode);
            }
        },

        openModal: function () {
            registry.async('checkout.steps.shipping-step.campaign-ad-disclosure-modal')(function (modalComponent) {
                if (modalComponent && typeof modalComponent.openModal === 'function') {
                    modalComponent.openModal();
                }
            });

            return true;
        },

        blockCTA: function (unblock = false) {
            const $ctaButton = $('#place-order-trigger');
            
            if (unblock) {
                $ctaButton.removeClass('disabled');
                return;
            }

            $ctaButton.addClass('disabled');
        },

        checkIsCampaignAd: function () {
            const storageValue = fxoStorage.get('isCampaignAd');

            if (typeof storageValue === 'boolean') {
                disclosureModel.isCampaignAd(storageValue);
            }
        },

        getCustomerName: function() {
            const firstName = window.checkoutConfig.quoteData.customer_firstname || '';
            const lastName = window.checkoutConfig.quoteData.customer_lastname || '';

            this.customerName(`${firstName} ${lastName}`);
        },
        
        handleSubmitCTA: function(campaignAdComplete) {
            if (!campaignAdComplete) {
                this.blockCTA();
                return;
            }
            
            // Enable the Place Order button
            this.blockCTA(true);
        }
    });
});