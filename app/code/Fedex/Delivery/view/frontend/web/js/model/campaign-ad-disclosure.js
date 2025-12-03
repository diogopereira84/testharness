define([
    'ko',
    'jquery',
    'fedex/storage'
  ], function (ko, $, fxoStorage) {
    'use strict';

    const model = {
        FORM_FIELDS: [
            'isCampaignAd',
            'candidatePacBallotIssue',
            'electionDate',
            'sponsoringCommittee',
            'addressLine1',
            'addressLine2',
            'city',
            'state',
            'zipCode'
        ],

        storageKey: 'politicalDisclosure',

        // Display Inline Questionnaire in Shipping or Pickup flows
        shouldDisplayInlineEproQuestionnaire: ko.observable(false),

        // Display Questionnaire in Order Review Page 
        shouldDisplayQuestionnaireInReviewPage: ko.observable(false),

        isCampaignAd: ko.observable(null),

        dataLoadedFromStorage: ko.observable(false),

        candidatePacBallotIssue: ko.observable(''),
        electionDate: ko.observable(''),
        sponsoringCommittee: ko.observable(''),
        addressLine1: ko.observable(''),
        addressLine2: ko.observable(''),
        city: ko.observable(''),
        state: ko.observable(''),
        zipCode: ko.observable(''),

        isCampaingAdDisclosureToggleEnable: Array.isArray(window.checkoutConfig?.political_disclosure?.enabledStates) 
            ? window.checkoutConfig?.political_disclosure?.enabledStates.length > 0
            : false,

        /*
        * Set shouldDisplayQuestionnaireInReviewPage and shouldDisplayInlineEproQuestionnaire based on the region code
        * @param {string} primaryRegionCode - The primary region code
        * @param {string} secondaryRegionCode - The secondary region code, its only used in a mixed cart - pickup + shipping
        */
        setShouldDisplayQuestionnaire: function (primaryRegionCode, secondaryRegionCode = null) {
            if(!Array.isArray(window.checkoutConfig?.political_disclosure?.enabledStates) ) {
                this.shouldDisplayInlineEproQuestionnaire(false);
                this.shouldDisplayQuestionnaireInReviewPage(false);
                return;
            }

            let includesState = window.checkoutConfig.political_disclosure.enabledStates.includes(primaryRegionCode);

            if (secondaryRegionCode) {
                const includesSecondaryState = window.checkoutConfig.political_disclosure.enabledStates.includes(secondaryRegionCode);
                includesState = includesState || includesSecondaryState;
            }

            if(!includesState) {
                this.clearAllData();
            }

            window.checkoutConfig.is_epro ?
                this.shouldDisplayInlineEproQuestionnaire(includesState) :
                this.shouldDisplayQuestionnaireInReviewPage(includesState);
        },

        clearAllData: function () {
            this.clearStorage();
            this.isCampaignAd(null);
            this.candidatePacBallotIssue('');
            this.electionDate('');
            this.sponsoringCommittee('');
            this.addressLine1('');
            this.addressLine2('');
            this.city('');
            this.state('');
            this.zipCode('');
            this.dataLoadedFromStorage(false);
        },

        shouldSendPayloadOnSubmit: function () {
            return this.isCampaignAd() && (this.shouldDisplayInlineEproQuestionnaire() || this.shouldDisplayQuestionnaireInReviewPage());
        },

        loadFromStorage: function () {
            let storedData = fxoStorage.get(this.storageKey);
            if (!storedData || typeof storedData !== 'object') {
                this.dataLoadedFromStorage(false);
                return null;
            }

            this.applyPayload(storedData);

            this.dataLoadedFromStorage(true);
            return this;
        },

        applyPayload: function (payload) {
            if (!payload || typeof payload !== 'object') return this;

            for (const key of this.FORM_FIELDS) {
              if (Object.prototype.hasOwnProperty.call(payload, key) && typeof this[key] === 'function') {
                this[key](payload[key]);
              }
            }
            return this;
        },

        saveFormData: function (payload) {
            if(!payload || typeof payload !== 'object') {
                return this;
            }

            this.applyPayload(payload);
        
            fxoStorage.set(this.storageKey, {
                isCampaignAd: this.isCampaignAd(),
                candidatePacBallotIssue: this.candidatePacBallotIssue(),
                electionDate: this.electionDate(),
                sponsoringCommittee: this.sponsoringCommittee(),
                addressLine1: this.addressLine1(),
                addressLine2: this.addressLine2(),
                city: this.city(),
                state: this.state(),
                zipCode: this.zipCode()
            });

            this.dataLoadedFromStorage(true);

            return this;
        },

        clearStorage: function () {
            fxoStorage.set(this.storageKey, null);
            return this;
        },
    };

    // Auto-hydrate from storage on module load
    model.loadFromStorage();

    model.isCampaignAdDisclosureComplete = ko.computed(() => {
        // If questionnaire is not displayed, form is not required
        if (!model.shouldDisplayInlineEproQuestionnaire() && !model.shouldDisplayQuestionnaireInReviewPage()) {
            return true;
        }

        // If isCampaignAd is false, form is not required
        if (model.isCampaignAd() === false) {
            return true;
        }

        // If isCampaignAd is true or null, check if all required fields are filled
        return model.candidatePacBallotIssue().trim().length > 0 &&
                model.electionDate().trim().length > 0 &&
                model.addressLine1().trim().length > 0 &&
                model.city().trim().length > 0 &&
                model.state().trim().length > 0 &&
                model.zipCode().trim().length > 0;
    }, this);

    model.isCampaignAd.subscribe((newValue) => {
        fxoStorage.set('isCampaignAd', newValue);
    })

    return model;
  });