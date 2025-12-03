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
    'mage/translate',
    'Magento_Ui/js/form/element/select',
    'jquery'
], function (ko, _, utils, $t, Select, $) {
    'use strict';

    /**
     * Parses incoming options, considers options with undefined value property
     *     as caption
     *
     * @param  {Array} nodes
     * @return {Object}
     */
    function parseOptions(nodes, captionValue) {
        var caption,
            value;

        nodes = _.map(nodes, function (node) {
            value = node.value;

            if (value === null || value === captionValue) {
                if (_.isUndefined(caption)) {
                    caption = node.label;
                }
            } else {
                return node;
            }
        });

        return {
            options: _.compact(nodes),
            caption: _.isString(caption) ? caption : false
        };
    }

    return Select.extend({
        defaults: {
            size: 5,
            elementTmpl: 'ui/form/element/multiselect-custom',
            listens: {
                value: 'setDifferedFromDefault setPrepareToSendData',
                defaultOption: 'setDifferedFromSelectedDefault'
            },
            orderedValues: [],
            isDifferedFromSelectedDefault: false,
            selectedDefault: '',
            defaultLabelTitle: $t('Default Option'),
        },

        /**
         * Calls 'initObservable' of parent, initializes 'options' and 'initialOptions'
         *     properties, calls 'setOptions' passing options to it
         *
         * @returns {Object} Chainable.
         */
        initObservable: function () {
            this._super();

            this.initialOptions = this.options;

            this.observe('options caption optionsSelected defaultOption isDifferedFromSelectedDefault')
                .setOptions(this.options());

            return this;
        },

        /**
         * @inheritdoc
         */
        setInitialValue: function () {
            this._super();

            this.initialValue = utils.copy(this.initialValue);

            return this;
        },

        /**
         * @inheritdoc
         */
        normalizeData: function (value) {
            if (utils.isEmpty(value)) {
                value = [];
            }

            return _.isString(value) ? value.split(',') : value;
        },

        /**
         * Sets the prepared data to dataSource
         * by path, where key is component link to dataSource with
         * suffix "-prepared-for-send"
         *
         * @param {Array} data - current component value
         */
        setPrepareToSendData: function (data) {
            if (_.isUndefined(data) || !data.length) {
                data = '';
            }

            this.source.set(this.dataScope + '-prepared-for-send', data);
        },

        /**
         * @inheritdoc
         */
        getInitialValue: function () {
            var values = [
                    this.normalizeData(this.source.get(this.dataScope)),
                    this.normalizeData(this.default)
                ],
                value;

            values.some(function (v) {
                return _.isArray(v) && (value = utils.copy(v)) && !_.isEmpty(v);
            });

            let itemId = '';
            value.toArray().forEach(function(item){
                if(item.indexOf('default-') !== -1) {
                    itemId = item.replace("default-", "");
                }
            });

            if (itemId) {
                this.defaultOption(itemId);
            }

            this.setOptionsSelected(this.options(), value);
            return value;
        },

        /**
         * @inheritdoc
         */
        hasChanged: function () {
            var value = this.value(),
                initial = this.initialValue;

            if(!utils.equalArrays(value, this.orderedValues)) {
                value = this.handleDefaultValue(value);
                this.setOptionsSelected(this.options(), value);
                this.value(value);
            }

            return !utils.equalArrays(value, initial);
        },

        /**
         * Keep defaultOption at the end of the array when he is in selected options of the multiselect element
         * @param value
         * @returns {*}
         */
        handleDefaultValue: function (value) {
            var self = this;

            value = this.dealWithDefaultOption(value, this.defaultOption())

            self.orderedValues = value;

            return self.orderedValues;
        },

        /**
         * Refresh the defaultOption select element whenever the multiselect changes
         * @param data
         * @param selectedOptions
         * @returns {*}
         */
        setOptionsSelected: function (data, selectedOptions) {
            var captionValue = this.captionValue || '',
                result = parseOptions(data, captionValue),
                defaultOption = this.defaultOption();

            result = this.removeUnselectedOptions(result.options, selectedOptions);

            this.optionsSelected(result);
            this.defaultOption(defaultOption);

            return this;
        },

        removeUnselectedOptions: function (indexedOptions, selectedOptions) {
            Object.entries(indexedOptions).forEach(([key, obj]) => {
                if(!selectedOptions.includes(obj.value)){
                    delete indexedOptions[key];
                }
            });

            return _.compact(indexedOptions);
        },

        setDifferedFromSelectedDefault: function () {
            var defaultOption = typeof this.defaultOption() != 'undefined' && this.defaultOption() !== null ? this.defaultOption() : '',
                defaultValue = typeof this.selectedDefault != 'undefined' && this.selectedDefault !== null ? this.selectedDefault : '';

            var value = this.dealWithDefaultOption(this.value().toArray(), defaultOption)
            this.orderedValues = value;
            this.value(value);

            this.isDifferedFromSelectedDefault(defaultOption !== defaultValue);
        },

        dealWithDefaultOption: function (value, defaultOption) {

            value.forEach(function (item, index) {
                if(item.indexOf('default-') !== -1)
                    value.splice(index, 1)
            })

            if (value.includes(defaultOption))
                value.push('default-' + defaultOption);

            return value;
        },

        /**
         * @inheritdoc
         */
        reset: function () {
            this.value(utils.copy(this.initialValue));
            this.error(false);

            return this;
        },

        /**
         * @inheritdoc
         */
        clear: function () {
            this.value([]);
            this.error(false);

            return this;
        }
    });
});
