/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'mage/template'
], function (CheckboxElement, $) {
    'use strict';

    return CheckboxElement.extend({
        defaults: {
            elementTmpl: 'Fedex_Company/form/element/input'
        },

        /**
         * @returns {Element}
         */
        initialize: function () {
            return this._super()
                .initStateConfig();
        },

        /**
         * @returns {Element}
         */
        initStateConfig: function () {
            var stores;
            if (this.source) {
                stores = this.source.get(this.parentScope);
                if (this.inputName == 'general[new_store_id]') {
                    this.selectedValue = stores['new_store_name'];
                    this.fieldDepend(this.selectedValue);
                } else {
                    this.selectedValue = stores['new_store_view_name'];
                }

            }

            return this;
        },
        onUpdate: function (value) {
            if (this.inputName == 'general[new_store_id]') {
                this.fieldDepend(value);
            }
            return this._super();
        },
        /**
         * Update field dependency
         *
         * @param {String} value
         */
        fieldDepend: function (value) {
            setTimeout(function () {
                if (value) {
                    $('.new_store_view_id').show();
                }
                else {
                    $('.new_store_view_id').hide();
                }
                return this;
            }, 400);
            return this;
        }
    });
});
