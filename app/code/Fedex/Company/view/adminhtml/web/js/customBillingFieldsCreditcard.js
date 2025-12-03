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
            referencePrefix: "CC_Reference_",
            lastAddedCCValue: ko.observable('')
        },

        initialize: function () {
            this._super();
            this.addRow = this.addRow.bind(this);
            this.addRow();
        },

        addRow: function () {
            if (this.initialValue !== "") {
                this.lastAddedCCValue(this.value());
            }
            if (this.initialValue === "") {
                let newValue = this.referencePrefix + "1";

                const lastValue = this.lastAddedCCValue();
                if (lastValue) {
                    const lastNumber = parseInt(lastValue.split(this.referencePrefix)[1]) || 0;
                    newValue = this.referencePrefix + (lastNumber + 1);
                }

                this.value(newValue);
                this.lastAddedCCValue(newValue);
            }
        }
    });
});
