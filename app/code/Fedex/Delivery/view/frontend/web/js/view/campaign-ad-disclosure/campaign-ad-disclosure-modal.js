define([
    'uiComponent',
    'ko',
    'jquery',
    'mage/translate',
    'Fedex_Delivery/js/model/toggles-and-settings',
    'Fedex_Delivery/js/model/campaign-ad-disclosure',
    'Fedex_Delivery/js/view/google-places-api',
    'Magento_Ui/js/modal/modal',
    'jquery-ui-modules/datepicker',
    'uiRegistry',
    'underscore',
    'mage/validation',
], function (Component, ko, $, $t, togglesAndSettings, disclosureModel, googlePlacesApi, _modal, _datepicker, registry, _) {
    'use strict';
    return Component.extend({
        defaults: {
            template: 'Fedex_Delivery/campaign-ad-disclosure/campaign-ad-disclosure-modal',
            disclosureModel: disclosureModel,
            temporaryFormData: ko.observable({
                candidatePacBallotIssue: ko.observable(''),
                electionDate: ko.observable(''),
                sponsoringCommittee: ko.observable(''),
                addressLine1: ko.observable(''),
                addressLine2: ko.observable(''),
                city: ko.observable(''),
                state: ko.observable(''),
                zipCode: ko.observable(''),
            }),
            shouldDisplayQuestionnaireInReviewPage: disclosureModel.shouldDisplayQuestionnaireInReviewPage,
            infoUrl: togglesAndSettings.infoUrl,
            regionOptions: ko.observable({}),
            modalOpened: ko.observable(false),
            suggestedAddresses: ko.observableArray([]),


            // Temporary form data
            imports: {
                regionOptions: '${ $.parentName }.shippingAddress.shipping-address-fieldset.region_id:indexedOptions',
            }
        },
    
        initialize: function () {
            this.isSaveDisabled = ko.observable(true);
            this._super();
            
            $(document).off('click.cadModal');
            $(document).on('click.cadModal', '.campaign-ad-disclosure-modal', (e) => {
                let $target = $(e.target);
                let insideResults = $target.closest('.google-maps-results').length > 0;
                if (!insideResults) {
                    this.suggestedAddresses([]);
                }
            });

            this.currentRegionOptions = ko.computed(() => {
                return Object.keys(this.regionOptions()).map(key => this.regionOptions()[key]);
            }, this);

            this.isFormValid = ko.computed(() => {
                if (this.disclosureModel.isCampaignAd() === false || this.modalOpened() === false) {
                    return true;
                }

                if(this.temporaryFormData().candidatePacBallotIssue() 
                    && this.temporaryFormData().electionDate() 
                    && this.temporaryFormData().addressLine1() 
                    && this.temporaryFormData().city() 
                    && this.temporaryFormData().state() 
                    && this.temporaryFormData().zipCode().length >= 5) {
                        // Run form validation
                        return $('#campaign-ad-disclosure-form').validation('isValid');
                }
                return false;
            }, this);

            return this;
        },

        startShippingAddressHydration: function () {
            if(this.disclosureModel.dataLoadedFromStorage()) {
                return;
            }
            registry.async('checkoutProvider')((provider) => {
                const addr = provider.get('shippingAddress');
                if (addr) {
                    this.hydrateFromAddress(addr);
                }
                provider.on('shippingAddress', (newAddr) => {
                    if (newAddr) {
                        this.hydrateFromAddress(newAddr);
                    }
                });
            });
        },

        hydrateFromAddress: function (addr) {
            if (!addr || typeof addr !== 'object') {
                return;
            }
            this.temporaryFormData().addressLine1(addr.street && addr.street[0] || '');
            this.temporaryFormData().addressLine2(addr.street && addr.street[1] || '');
            this.temporaryFormData().city(addr.city || '');
            this.temporaryFormData().state(addr.region_id || '');
            this.temporaryFormData().zipCode(addr.postcode || '');
        },

        initModal: function () {
            $("#campaign-ad-disclosure-modal").modal({
                type: 'popup',
                modalClass: 'campaign-ad-disclosure-modal fedex-ddt-modal no-icon',
                trigger: '[data-trigger=campaign-ad-disclosure-modal]',
                title: $t('Political Campaign Disclosure'),
                buttons: [],
            });
        },

        initDatePicker: function () {
            let self = this;
            let el = $( "#cad-election-date" ).datepicker({
                showButtonPanel: true,
                closeText: "Clear",
                onSelect: function(dateText) {
                    self.temporaryFormData().electionDate(dateText);
                    $('#cad-election-date').val(dateText);
                    $('#cad-election-date').valid();
                },
            });
            let widget = el.datepicker("widget");
            widget.on("click", ".ui-datepicker-close", () => {
                self.temporaryFormData().electionDate('');
                $('#cad-election-date').val('');
            })
        },

        openModal: function () {
            this.temporaryFormData({
                candidatePacBallotIssue: ko.observable(this.disclosureModel.candidatePacBallotIssue()),
                electionDate: ko.observable(this.disclosureModel.electionDate()),
                sponsoringCommittee: ko.observable(this.disclosureModel.sponsoringCommittee()),
                addressLine1: ko.observable(this.disclosureModel.addressLine1()),
                addressLine2: ko.observable(this.disclosureModel.addressLine2()),
                city: ko.observable(this.disclosureModel.city()),
                state: ko.observable(this.disclosureModel.state()),
                zipCode: ko.observable(this.disclosureModel.zipCode())
            });

            this.suggestedAddresses([]);
            this.startShippingAddressHydration();

            $('#campaign-ad-disclosure-modal').modal('openModal');
            this.modalOpened(true);
        },

        closeModal: function () {
            $('#campaign-ad-disclosure-modal').modal('closeModal');
            this.modalOpened(false);
        },
        
        saveFormData: function () {
            let formData = {
                isCampaignAd: this.disclosureModel.isCampaignAd(),
                ...(this.disclosureModel.isCampaignAd() && {
                    candidatePacBallotIssue: this.temporaryFormData().candidatePacBallotIssue(),
                    electionDate: this.temporaryFormData().electionDate(),
                    sponsoringCommittee: this.temporaryFormData().sponsoringCommittee(),
                    addressLine1: this.temporaryFormData().addressLine1(),
                    addressLine2: this.temporaryFormData().addressLine2(),
                    city: this.temporaryFormData().city(),
                    state: this.temporaryFormData().state(),
                    zipCode: this.temporaryFormData().zipCode()
                })
            };

            this.disclosureModel.saveFormData(formData);
            this.closeModal();
        },

        handleAddressInputEvent: function (_data, event) {
            if (event.keyCode === 13) {
                this.suggestedAddresses([]);
                return true;
            }

            const value = this.temporaryFormData().addressLine1();
            if (value && value.length >= 2) {
                this.debouncedAutocomplete();
            } else {
                this.suggestedAddresses([]);
            }

            return true;
        },

        debouncedAutocomplete: _.debounce(function () {
            const value = this.temporaryFormData().addressLine1();
            if (!value || value.length < 2) {
                this.suggestedAddresses([]);
                return;
            }

            googlePlacesApi.loadAutocomplete(value)
                .then(predictions => {
                    const highlighted = (predictions || []).map(prediction => ({
                        ...prediction,
                        description: googlePlacesApi.hightlightResult(value, prediction.description)
                    }));
                    this.suggestedAddresses(highlighted);
                })
                .catch(() => {
                    this.suggestedAddresses([]);
                });
        }, 300),

        handleAddressSuggestionSelect: function (prediction, event) {
            event.preventDefault();
            event.stopPropagation();
            const description = prediction && prediction.description ? prediction.description : '';
            if (!description) {
                this.suggestedAddresses([]);
                return false;
            }
            googlePlacesApi.handleGooglePlacesGeocoding(description)
                .then(geoData => {
                    this.setCampaignAddressFromComponents(geoData.address_components);
                    this.suggestedAddresses([]);
                })
                .catch(() => {
                    // Silently fail
                });
            return false;
        },

        validateFieldOnBlur: function (_vm, event) {
            $(event.target).valid();
        },

        setCampaignAddressFromComponents: function (components) {
            const fields = googlePlacesApi.normalizeAddressComponents(components || []);
            if (fields.addressLine1) {
                this.temporaryFormData().addressLine1(fields.addressLine1);
            }
            if (fields.city) {
                this.temporaryFormData().city(fields.city);
            }
            if (fields.stateCode) {
                const opts = this.currentRegionOptions();
                const matched = Array.isArray(opts)
                    ? opts.find(o => o && (o.label === fields.stateCode || o.title === fields.stateCode))
                    : null;
                this.temporaryFormData().state(matched?.value || null);
            }
            if (fields.postalCode) {
                this.temporaryFormData().zipCode(fields.postalCode);
            }
        },
    });
});