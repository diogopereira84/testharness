/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'underscore',
    'uiCollection',
    'jquery'
], function (_, Collection,$) {
    'use strict';

	$(document).ready(function() {
		var findStreet = setInterval(function(){ 
			if($('input[name="street[0]"]').length > 0){
				$('div[name="shippingAddress.custom_attributes.ext"]').hide();
				$('input[name="street[0]"]').val('n/a');
				$('.shipto-telephone input').val('0000000000');
				$('.shipto-city input').val('n/a');
				$('input[name="street[0]"]').trigger('change');
				$('input[name="street[0]"]').trigger('change');
				
				$('.shipto-telephone input').trigger('change');
				$('.shipto-telephone input').trigger('change');
				
				$('.shipto-city input').trigger('change');
				$('.shipto-city input').trigger('change');
				//$('input[name="street[0]"]').trigger('click');
				clearInterval(findStreet);
			}
		}, 1000);
	});
    return Collection.extend({
        defaults: {
            visible: true,
            label: '',
            showLabel: true,
            required: false,
            template: 'ui/group/group',
            fieldTemplate: 'ui/form/field',
            breakLine: true,
            validateWholeGroup: false,
            additionalClasses: {}
        },

        /**
         * Extends this with defaults and config.
         * Then calls initObservable, iniListenes and extractData methods.
         */
        initialize: function () {
            this._super()
                ._setClasses();

            return this;
        },

        /**
         * Calls initObservable of parent class.
         * Defines observable properties of instance.
         *
         * @return {Object} - reference to instance
         */
        initObservable: function () {
            this._super()
                .observe('visible')
                .observe({
                    required: !!+this.required
                });

            return this;
        },

        /**
         * Extends 'additionalClasses' object.
         *
         * @returns {Group} Chainable.
         */
        _setClasses: function () {
            var additional = this.additionalClasses,
                classes;

            if (_.isString(additional)) {
                additional = this.additionalClasses.split(' ');
                classes = this.additionalClasses = {};

                additional.forEach(function (name) {
                    classes[name] = true;
                }, this);
            }

            _.extend(this.additionalClasses, {
                'admin__control-grouped': !this.breakLine,
                'admin__control-fields': this.breakLine,
                required:   this.required,
                _error:     this.error,
                _disabled:  this.disabled
            });

            return this;
        },

        /**
         * Defines if group has only one element.
         * @return {Boolean}
         */
        isSingle: function () {
            return this.elems.getLength() === 1;
        },

        /**
         * Defines if group has multiple elements.
         * @return {Boolean}
         */
        isMultiple: function () {
            return this.elems.getLength() > 1;
        },

        /**
         * Returns an array of child components previews.
         *
         * @returns {Array}
         */
        getPreview: function () {
            return this.elems.map('getPreview');
        }
    });
});
