define([
    'uiComponent',
    'ko',
    'jquery',
    'mage/translate',
    'Fedex_Delivery/js/model/toggles-and-settings',
    'Fedex_Delivery/js/model/campaign-ad-disclosure',
    'uiRegistry'
  ], function (Component, ko, $, $t, togglesAndSettings, disclosureModel, registry) {
    'use strict';
  
    return Component.extend({
        defaults: {
            template: 'Fedex_Delivery/campaign-ad-disclosure/campaign-ad-questionnaire'
        },
        disclosureModel: disclosureModel,
        selectedValue: ko.observable(),
        infoUrl: togglesAndSettings.infoUrl,   
        isCampaignAd: ko.observable(disclosureModel.isCampaignAd),
        shouldDisplayInlineEproQuestionnaire: disclosureModel.shouldDisplayInlineEproQuestionnaire,
        
        initialize: function () {
            this._super();
            return this;
        },

        openModal: function () {
            this.isCampaignAd(true);
            registry.async('checkout.steps.shipping-step.campaign-ad-disclosure-modal')(function (modalComponent) {
                if (modalComponent && typeof modalComponent.openModal === 'function') {
                    modalComponent.openModal();
                }
            });

            return true;
        }
    });
});