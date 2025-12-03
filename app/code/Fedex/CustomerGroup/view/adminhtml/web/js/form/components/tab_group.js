/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'underscore',
    'Magento_Ui/js/lib/collapsible',
    'jquery',
    'fedex/storage'
], function (_, Collapsible, $,fxoStorage) {
    'use strict';

    return Collapsible.extend({
        defaults: {
            listens: {
                '${ $.provider }:data.validate': 'onValidate'
            },
            collapsible: false,
            isFirstFlag: true,
            opened: true
        },

        /**
         * Invokes initElement method of parent class, calls 'initActivation' method
         * passing element to it.
         * @param {Object} elem
         * @returns {Object} - reference to instance
         */
        initElement: function (elem) {
            this._super()
                .initActivation(elem);

            return this;
        },

        /**
         * Activates element if one is first or if one has 'active' propert
         * set to true.
         *
         * @param  {Object} elem
         * @returns {Object} - reference to instance
         */
        initActivation: function (elem) {
            var elems   = this.elems(),
                isFirst = !elems.indexOf(elem);
                // B-1819244 - selecting folder level permission tab after creataion of new category
                var isCatalogPermissionActive
                if(window.e383157Toggle){
                    isCatalogPermissionActive = fxoStorage.get("isCatalogPermissionActive");
                }else{
                    isCatalogPermissionActive = localStorage.getItem("isCatalogPermissionActive");
                }
                if(isCatalogPermissionActive && isCatalogPermissionActive === "true") {
                    if(elem.index === 'customergroup_general') {
                        isFirst = false;
                        this.isFirstFlag = false;
                    }
                    if(window.e383157Toggle){
                        fxoStorage.set("isCatalogPermissionActive", false);
                    }else{
                        localStorage.setItem("isCatalogPermissionActive", false);
                    }
                }
                if(!this.isFirstFlag && elem.index === 'customergroup_catalog_permission') {
                    isFirst = true;
                    this.isFirstFlag = true;
                }
            if (isFirst || elem.active()) {
                elem.activate();
            }

            return this;
        },

        /**
         * Delegates 'validate' method on element, then reads 'invalid' property
         * of params storage, and if defined, activates element, sets
         * 'allValid' property of instance to false and sets invalid's
         * 'focused' property to true.
         *
         * @param {Object} elem
         */
        validate: function (elem) {
            var result  = elem.delegate('validate'),
                invalid;

            invalid = _.find(result, function (item) {
                return typeof item !== 'undefined' && !item.valid;
            });

            if (invalid) {
                let groupName;
                if (window.e383157Toggle) {
                    groupName = fxoStorage.get("groupName") ? fxoStorage.get("groupName") : '';
                } else {
                    groupName = localStorage.getItem("groupName") ? localStorage.getItem("groupName") : '';
                }
                if (groupName != '' && invalid.target.index == 'customer_group_code') {
                    elem.activate();
                    $({to:0}).animate({to:1}, 800, function() {
                        $('#save').trigger('click');
                    });
                } else {
                    elem.activate();
                    invalid.target.focused(true);
                }
            }

            return invalid;
        },

        /**
         * Sets 'allValid' property of instance to true, then calls 'validate' method
         * of instance for each element.
         */
        onValidate: function () {
            this.elems.sortBy(function (elem) {
                return !elem.active();
            }).some(this.validate, this);
        }
    });
});
