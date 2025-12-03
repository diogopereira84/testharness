/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Fedex_CustomerGroup/js/form/components/ui-select',
    'jquery',
    'fedex/storage'
], function (Select, $,fxoStorage) {
    'use strict';

    return Select.extend({
        defaults: {
            isFoundInOptionGroups: false
        },

        initialize: function (){
            this._super();
            let previousURL = document.referrer;
            let groupIndexUrl = 'customer/group/index';
            let newCatIds;
            if(window.e383157Toggle){
                newCatIds = fxoStorage.get("newCategoryIds");
            }else{
                newCatIds = JSON.parse(localStorage.getItem("newCategoryIds"));
            }
            let self = this;

            if (newCatIds !== null && newCatIds.length > 0) {
                newCatIds.forEach(function (value, index) {
                    let data = JSON.parse('{"value": "'+ value +'" , "is_active": "1"}');
                    self.toggleOptionSelected(data);
                });
            }
            return this;
        },

        hasChildList: function () {
            let categoryNumber = window.e383157Toggle ? fxoStorage.get("categoryId")
                : localStorage.getItem("categoryId");
            var categoryId = '' + categoryNumber + '';
            var isFoundInOptionGroups = false;
            var optgroups = this.cacheOptions.tree[0].optgroup;
            for (let i = 0; i < optgroups.length; i++) {
                var id = optgroups[i].value;
                $('#'+id).show();
            }
            if(categoryId !== null && categoryId !== "false") {
                var foundKey = Object.keys(optgroups).find(function(key) {
                    if(optgroups[key].value === categoryId) {
                        isFoundInOptionGroups = true;
                    }
                  });
                if(isFoundInOptionGroups === true) {
                    this.isFoundInOptionGroups = true;
                    for (let i = 0; i < optgroups.length; i++) {
                        if(optgroups[i].value !== categoryId) {
                            var id = optgroups[i].value;
                            $('#'+id).hide();
                        }
                        if(optgroups[i].value === categoryId) {
                            if (window.e383157Toggle) {
                                fxoStorage.set("optionLabel",optgroups[i].label);
                            } else {
                                localStorage.setItem("optionLabel", optgroups[i].label);
                            }
                        }
                    }
                } else {
                    for (let i = 0; i < optgroups.length; i++) {
                        var id = optgroups[i].value;
                        $('#'+id).show();
                    }
                    this.isFoundInOptionGroups = false;
                }
            }
            return _.find(this.options(), function (option) {
                return !!option[this.separator];
            }, this);
        },
        /**
         * Check options length and set to cache
         * if some options is added
         *
         * @param {Array} options - ui select options
         */
        checkOptionsList: function (options) {
            var searchOptionsCount =  0;
            var rootCategoryIds = [];
            var categoryId;
            var optionLabel;
            if(window.e383157Toggle){
                categoryId = '' + fxoStorage.get("categoryId") + '';
                optionLabel = '' + fxoStorage.get("optionLabel") + '';
            }else{
                categoryId = '' + localStorage.getItem("categoryId") + '';
                optionLabel = '' + localStorage.getItem("optionLabel") + '';
            }
            if(categoryId !== null && categoryId !== "false") {
                if(this.isFoundInOptionGroups === true) {
                    for (let i = 0; i < options.length; i++) {
                        if((window.categoryB2B != 'undefined' && options[i].label === window.categoryB2B) || options[i].label === optionLabel){
                            rootCategoryIds.push(options[i].value);
                        }
                    }
                    for (let i = 0; i < options.length; i++) {
                        if (options[i].path.indexOf(optionLabel) === -1 && rootCategoryIds.indexOf(options[i].value) === -1) {
                                options[i].is_active = false;
                        } else {
                            options[i].is_active = true;
                            searchOptionsCount++;
                        }
                    }
                    if(window.e383157Toggle){
                        fxoStorage.set("searchOptionsCount",searchOptionsCount);
                    }else{
                        localStorage.setItem("searchOptionsCount",searchOptionsCount);
                    }
                } else {
                    for (let i = 0; i < options.length; i++) {
                        options[i].is_active = true;
                    }
                    if(window.e383157Toggle){
                        fxoStorage.delete("searchOptionsCount");
                    }else{
                        localStorage.removeItem("searchOptionsCount");
                    }
                }
            } else {
                if(window.e383157Toggle){
                    fxoStorage.delete("searchOptionsCount");
                }else{
                    localStorage.removeItem("searchOptionsCount");
                }
            }
            if (options.length > this.cacheOptions.plain.length) {
                this.cacheOptions.plain = options;
                this.setCaption();
            }
        },
        /**
         * Set filtered items quantity
         *
         * @param {Object} data - option data
         */
        _setItemsQuantity: function (data) {
            var categoryId;
            if (window.e383157Toggle) {
                categoryId = '' + fxoStorage.get("categoryId") + '';
            } else {
                categoryId = '' + localStorage.getItem("categoryId") + '';
            }
            if(data === false && categoryId !== null && categoryId !== "false" && this.isFoundInOptionGroups == true) {
                    if(window.e383157Toggle){
                        fxoStorage.delete("searchOptionsCount");
                    }else{
                        localStorage.removeItem("searchOptionsCount");
                    }
                    var optgroups = this.cacheOptions.tree[0].optgroup;
                    for (let i = 0; i < optgroups.length; i++) {
                        var id = optgroups[i].value;
                        $('#'+id).show();
                    }
                    for (let i = 0; i < optgroups.length; i++) {
                        if(optgroups[i].value !== categoryId) {
                            var id = optgroups[i].value;
                            $('#'+id).hide();
                        }
                        if(optgroups[i].value === categoryId) {
                            if(window.e383157Toggle){
                                fxoStorage.set("optionLabel",optgroups[i].label);
                            }else{
                                localStorage.setItem("optionLabel",optgroups[i].label);
                            }
                        }
                    }
            }
            var searchOptionsCount;
            if (window.e383157Toggle) {
                searchOptionsCount = fxoStorage.get("searchOptionsCount");
            } else {
                searchOptionsCount = localStorage.getItem("searchOptionsCount");
            }
            let optionString = ' options';
            if (this.showFilteredQuantity) {
                data || parseInt(data, 10) === 0 ?
                    this.itemsQuantity(this.getItemsPlaceholder(data)) :
                    this.itemsQuantity('');
                if(searchOptionsCount && this.isFoundInOptionGroups == true){
                    this.itemsQuantity(searchOptionsCount+""+optionString);
                }
            }
        },
        /**
         * Parse data and set it to options.
         *
         * @param {Object} data - Response data object.
         * @returns {Object}
         */
        setParsed: function (data) {
            var option = this.parseData(data);

            if (data.error) {
                return this;
            }

            this.options([]);
            this.setOption(option);
            this.set('newOption', option);
        },

        /**
         * Normalize option object.
         *
         * @param {Object} data - Option object.
         * @returns {Object}
         */
        parseData: function (data) {
            return {
                'is_active': data.category['is_active'],
                level: data.category.level,
                value: data.category['entity_id'],
                label: data.category.name,
                parent: data.category.parent
            };
        }
    });
});
