define([
    "jquery",
    "jquery/ui",
    'mage/validation'
], function ($) {
    "use strict";

    //creating jquery widget
    $.widget('cid_custom_validation.js', {
        _create: function () {
            this._bind();
        },

        /**
         * Event binding, will monitor change, keyup and paste events.
         * @private
         */
        _bind: function () {
            this._on(this.element, {
                'change': this.validateField,
                'keyup': this.validateField,
                'keypress': this.validateField,
                'paste': this.validateField,
                'click': this.validateField,
                'focusout': this.validateField,
                'focusin': this.validateField,
            });
        },
        validateField: function () {
            $.validator.validateSingleElement(this.element);
        },
    });
    return $.cid_custom_validation.js;
});
