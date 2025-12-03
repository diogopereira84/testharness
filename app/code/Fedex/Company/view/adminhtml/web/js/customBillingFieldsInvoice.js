/**
 * @api
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'ko'
], function (Element, $, ko) {
    'use strict';

    return Element.extend({
        defaults: {
            referencePrefix: "IA_Reference_",
            lastAddedInvoiceValue: ko.observable('')
        },

        initialize: function () {
            this._super();
            this.addRow = this.addRow.bind(this);
            this.addRow();
        },

        addRow: function () {
            if (this.initialValue !== "") {
                this.lastAddedInvoiceValue(this.value());
            }

            if (this.initialValue === "") {
                let newValue = this.referencePrefix + "1";

                const lastValue = this.lastAddedInvoiceValue();
                if (lastValue) {
                    const lastNumber = parseInt(lastValue.split(this.referencePrefix)[1]) || 0;
                    newValue = this.referencePrefix + (lastNumber + 1);
                }

                this.value(newValue);
                this.lastAddedInvoiceValue(newValue);
            }
        }
    });
});
