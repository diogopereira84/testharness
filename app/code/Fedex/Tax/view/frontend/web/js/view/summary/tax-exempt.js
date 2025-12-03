/*
 * @category Fedex
 * @package Fedex _ModuleName
 * @copyright (c) 2021.
 * @author Iago Lima <ilima@mcfadyen.com>
 */

define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'jquery',
    'ko',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function (Component, $, ko, modal) {
    'use strict';

    let taxModalTitleDefault = 'Tax exemption';
    let taxModalBodyDefault = 'FedEx Office manages tax exempt status by way of a FedEx Office account. To apply your tax exempt status to your online transaction, make sure to enter your FedEx Office account number during the checkout process.';
    let taxModalPrimaryCTADefault = '<a class="btn-faq mb-26 no-underline" href="https://getrewards.fedex.com/us/en/office/printpreferred.html" target="_blank">CREATE ACCOUNT</a>';
    let taxModalSecondaryCTADefault = '<a class="cl-steel-blue fedex-bold lh-19 ls-1" href="https://www.fedex.com/en-us/printing/customersupport/print-online.html" target="_blank">READ FAQS</a>';
    let taxModalFooter = 'If you have a FedEx Office account and sales tax is being charged when it should not be charged, contact <a class="link" href="mailto: ams.taxattributes@fedex.com">ams.taxattributes@fedex.com</a>';

    return Component.extend({
        defaults: {
            template: 'Fedex_Tax/summary/tax-exempt',
            taxModalTitle: ko.observable(taxModalTitleDefault),
            taxModalBody: ko.observable(taxModalBodyDefault),
            taxModalPrimaryCTA: ko.observable(taxModalPrimaryCTADefault),
            taxModalSecondaryCTA: ko.observable(taxModalSecondaryCTADefault),
            taxModalFooter: ko.observable(taxModalFooter),
            modal: '',
            options: {
                trigger: '[data-trigger=trigger-taxexempt]',
                modalClass: 'tax-exempt-model',
                buttons: []
            }
        },

        /**
         * Initializes modal instance.
         */
        initModal: function () {
            $('.tax-exempt-model').attr('aria-label','Tax Exempt');

            if (this.isTaxExemptModalAdminData()) {
                let taxModalCTAs = this.getTaxModalPrimaryCTA() + '<br />' + this.getTaxModalSecondaryCTA();
                $(taxModalCTAs).insertAfter('.tax-subtitle');
            }
        },

        userkeydown: function (data, event) {
            if (event.keyCode == 13 ) {
                $(".link.pointer").trigger('click');
            }
        },

        isCustomerLoggedIn: function () {
            return !window.isCustomerLoggedIn;
        },

        isTaxExemptModalAdminData: function () {
            return window.checkoutConfig.is_tax_exempt_modal_admin_data;
        },

        getTaxModalTitle: function() {
            if (window.checkoutConfig.tax_exempt_modal_title) {
                this.taxModalTitle = ko.observable(window.checkoutConfig.tax_exempt_modal_title);
            }
            return this.taxModalTitle();
        },

        getTaxModalBody: function() {
            if (window.checkoutConfig.tax_exempt_modal_body) {
                this.taxModalBody = ko.observable(window.checkoutConfig.tax_exempt_modal_body);
            }
            return this.taxModalBody();
        },

        getTaxModalPrimaryCTA: function() {
            if (window.checkoutConfig.tax_exempt_modal_primary_cta) {
                let taxPrimaryCTAElement = window.checkoutConfig.tax_exempt_modal_primary_cta;
                taxPrimaryCTAElement = taxPrimaryCTAElement.replace('tax-modal-config', 'btn-faq mb-26 no-underline');
                this.taxModalPrimaryCTA = ko.observable(taxPrimaryCTAElement);
            }
            return this.taxModalPrimaryCTA();
        },

        getTaxModalSecondaryCTA: function() {
            if (window.checkoutConfig.tax_exempt_modal_secondary_cta) {
                let taxSecondaryCTAElement = window.checkoutConfig.tax_exempt_modal_secondary_cta;
                taxSecondaryCTAElement = taxSecondaryCTAElement.replace('tax-modal-config', 'cl-steel-blue fedex-bold lh-19 ls-1');
                this.taxModalSecondaryCTA = ko.observable(taxSecondaryCTAElement);
            }
            return this.taxModalSecondaryCTA();
        },

        getTaxModalFooter: function() {
            if (window.checkoutConfig.tax_exempt_modal_footer) {
                let taxFooterElement = window.checkoutConfig.tax_exempt_modal_footer;
                taxFooterElement = taxFooterElement.replace('tax-modal-config', 'link');
                this.taxModalFooter = ko.observable(taxFooterElement);
            }
            return this.taxModalFooter();
        }
    });
});
