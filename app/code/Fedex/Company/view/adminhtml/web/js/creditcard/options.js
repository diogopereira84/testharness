/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * B-1205796 : API integration for CC details and Billing details in Magento Admin
 */
define([
    'Magento_Ui/js/form/element/checkbox-set',
    'uiRegistry',
    'jquery',
    'Fedex_Company/js/creditcard/creditcard-handler'
], function (Checkbox, registry, $, CreditCardHandler) {
    'use strict';

    $(document).on("click", '.open-cc-form', function (e) {
        let modal = registry.get('company_form.company_form.credit_card_modal');
        modal.openModal();
    });

    return Checkbox.extend({

        /**
         * Initialize element
         */
        initConfig: function () {
            this.showHideFields('hide');
            this._super();
        },

        /**
         * On element update handler
         */
        onUpdate: function () {
            let modal = registry.get('company_form.company_form.credit_card_modal');
            if (this.value() == 'new_credit_card') {
                CreditCardHandler.addViewCreditCardLink();
                this.showHideFields('show');
                modal.openModal();
            } else {
                this.showHideFields('hide');
            }

            this._super();
        },

        /**
         * Show or hide fields
         *
         * @param {*} type 
         */
        showHideFields: function (type) {
            CreditCardHandler.showHideFields(type);
        }
    });
});
