/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'ko',
    'underscore',
    'mageUtils',
    'jquery',
    'uiRegistry',
    'Magento_Ui/js/form/element/abstract',
    'Magento_Ui/js/modal/alert',
    'uiLayout'
], function (ko, _, utils, $, registry, Abstract, magentoAlert) {
    'use strict';
    var self;
    ko.bindingHandlers.sortableItem = {
        init: function (element, valueAccessor) {
            var options = valueAccessor();
            $(element).data("sortItem", options.item);
        }
    };
    ko.bindingHandlers.sortableList = {
        init: function (element, valueAccessor, allBindings, viewModel, bindingContext) {
            var list = valueAccessor();
            $(element).data("sortList", valueAccessor());
            $(element).data("viewModel", bindingContext.$root);
            $(element).sortable({
                update: function (event, ui) {
                    var item = ui.item.data("sortItem");
                    var newPosition = ui.item.index();
                    self.options.remove(item);
                    self.options.splice(newPosition, 0, item);
                    list().forEach(function (v, i) {
                        v.sort_order = i;
                        if (v.is_default) {
                            self.defaultOption(v.id)
                        }
                    }.bind(this));
                    ui.item.remove();
                    self.value(JSON.stringify(list()));
                }
            });
        }
    };

    return Abstract.extend({
        defaults: {
            customName: '${ $.parentName }.${ $.index }_input',
            caption: '',
            defaultOption: 'option_0',
            magentoAlert: magentoAlert,
            options: []
        },

        /**
         * Calls 'initObservable' of parent, initializes 'options' and 'initialOptions'
         *     properties, calls 'setOptions' passing options to it
         *
         * @returns {Object} Chainable.
         */
        initObservable: function () {
            this._super();
            this.observe('options');
            this.observe('defaultOption');
            this.options.subscribe(function (value) {
                this.value(JSON.stringify(value))
            }.bind(this))
            return this;
        },
        changeOnRadioButton: function (element) {
            self.options().map(function (i) {
                i.is_default = false
            })
            self.options()[self.options().indexOf(element)].is_default = true
            self.defaultOption(element.id);
            self.value(JSON.stringify(self.options()))
        },
        changeInformation: function (element) {
            self.value(JSON.stringify(self.options()))
        },
        initialize: function () {
            this._super();
            self = this;
            return this;
        },
        validate: function () {
            var isValid = this._super();
            var hasCanvaEnabled = jQuery('input[name="product[has_canva_design]"]:checked').length;

            if (hasCanvaEnabled && this.options().length <= 0) {
                isValid.valid = false
                var message = "Please fill at least one canva size to be used as default or disable 'Has Canva Design'"
                this.error(message);
                this.error.valueHasMutated();
                this.bubble('error', message);
                this.source.set('params.invalid', true);
            }
            return isValid;
        },
        getInitialValue: function () {
            if (this.source.get(this.dataScope)) {
                this.options(JSON.parse(this.source.get(this.dataScope) || '[]'))
                this.options().map(function (i) {
                    if (i.is_default) {
                        this.defaultOption(i.id);
                    }
                }.bind(this))
            }
            return JSON.stringify(this.options());
        },
        guid: function () {
            let s4 = function () {
                return Math.floor((1 + Math.random()) * 0x10000)
                    .toString(16)
                    .substring(1);
            }
            return s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4();
        },
        addNewOption: function () {
            this.options.push({
                id: 'option_' + this.guid(),
                sort_order: this.options().length,
                product_mapping_id: '',
                display_width: '',
                display_height: '',
                orientation: '',
                is_default: false
            });
            var hasDefault = self.options().filter(function (i) {
                return i.is_default == true;
            })
            if (hasDefault.length <= 0) {
                if (self.options().length > 0) {
                    self.options()[0].is_default = true;
                    self.defaultOption(self.options()[0].id);
                    self.value(JSON.stringify(self.options()))
                }
            }
        },
        removeOption: function (option) {
            self.options.remove(option);
            var hasDefault = self.options().filter(function (i) {
                return i.is_default == true;
            })
            if (hasDefault.length <= 0) {
                if (self.options().length > 0) {
                    self.options()[0].is_default = true;
                    self.defaultOption(self.options()[0].id);
                    self.value(JSON.stringify(self.options()))
                }
            }
        }
    });
});
