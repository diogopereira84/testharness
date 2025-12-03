define([
    'uiComponent',
    'ko',
    'uiRegistry',
    'Fedex_Delivery/js/model/campaign-ad-disclosure'
], function (Component, ko, registry, disclosureModel) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Fedex_Delivery/campaign-ad-disclosure/campaign-ad-address-box',
            disclosureModel: disclosureModel,
            regionOptions: ko.observable({}),
            imports: {
                regionOptions: '${ $.parentName }.shipping-address-fieldset.region_id:indexedOptions'
            }
        },

        initialize() {
            this._super();

            this.currentRegionOptions = ko.computed(() => {
                return Object.keys(this.regionOptions()).map(key => this.regionOptions()[key]);
            }, this);

            this.stateLabel = ko.computed(() => {
                const opts = this.currentRegionOptions() || {};
                const matched = Array.isArray(opts)
                    ? opts.find(o => o.value === this.disclosureModel.state())
                    : null;
                return matched?.label || matched?.title || this.disclosureModel.state();
            });

            this.hasAddress = ko.computed(() => !!(
                this.disclosureModel.addressLine1() &&
                this.disclosureModel.city() &&
                this.disclosureModel.state() &&
                this.disclosureModel.zipCode()
            ));

            this.isVisible = ko.computed(() => (
                this.disclosureModel.shouldDisplayInlineEproQuestionnaire() &&
                this.hasAddress() &&
                this.disclosureModel.isCampaignAd()
            ));

            this.addressLines = ko.computed(() => {
                const addressLines = [];
                const addressLine = [
                    this.disclosureModel.addressLine1(),
                    this.disclosureModel.addressLine2()
                ].filter(Boolean).join(' ');
                if (addressLine) addressLines.push(addressLine);

                const city = this.disclosureModel.city();
                const state = this.stateLabel();
                const zip = this.disclosureModel.zipCode();

                addressLines.push(`${city}, ${state} ${zip}`);
                return addressLines;
            });

            return this;
        },

        openModal() {
            registry.async('checkout.steps.shipping-step.campaign-ad-disclosure-modal')(function (modalComponent) {
                if (modalComponent && typeof modalComponent.openModal === 'function') {
                    modalComponent.openModal();
                }
            });
        }
    });
});
