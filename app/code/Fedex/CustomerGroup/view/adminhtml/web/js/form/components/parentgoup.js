/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Fedex_CustomerGroup/js/form/components/ui-select-parent',
    'jquery',
    'Magento_Ui/js/lib/view/utils/dom-observer',
    'fedex/storage'

], function (Select,$,$dom,fxoStorage) {
    'use strict';

    return Select.extend({

        initialize: function (){
            this._super();
            if(this.initialValue !== '') {
                if(window.e383157Toggle){
                    fxoStorage.set("parentGroupID", this.initialValue);
                }else{
                    localStorage.setItem("parentGroupID", this.initialValue);
                }
                var ajaxUrl = this.category_url;
                this.getCategoryId(ajaxUrl);
                var categoryId;
                if(window.e383157Toggle){
                    categoryId = fxoStorage.get("categoryId");
                }else{
                    categoryId = localStorage.getItem("categoryId");
                }
            }

            var self = this;
            $dom.get('input[name="customergroup_general[code]"]', function () {
                let currentUrl = window.location.href;
                let groupNewUrl = 'customer/group/new';
                let newCategoryId;
                let parentID;
                let groupName;
                if (window.e383157Toggle) {
                    newCategoryId = fxoStorage.get("newCategoryIds");
                    parentID = fxoStorage.get("parentGroupID") ? fxoStorage.get("parentGroupID") : null;
                    groupName = fxoStorage.get("groupName") ? fxoStorage.get("groupName") : '';
                } else {
                    newCategoryId = localStorage.getItem("newCategoryIds");
                    parentID = localStorage.getItem("parentGroupID") ? localStorage.getItem("parentGroupID") : null;
                    groupName = localStorage.getItem("groupName") ? localStorage.getItem("groupName") : '';
                }
                let data = JSON.parse('{"value": "'+ parentID +'" , "is_active": "1"}');
                if (currentUrl.indexOf(groupNewUrl) !== -1 && parentID != 'null' && newCategoryId != 'null') {
                    $("input[name='customergroup_general[code]']").val(groupName);
                    $("input[name='customergroup_general[code]']").trigger('change');
                    self.toggleOptionSelected(data);
                }
                $("input[name='customergroup_general[code]']").change(function(){
                    let groupName = $(this).val();
                    if(window.e383157Toggle){
                        fxoStorage.set("groupName", groupName);
                    }else{
                        localStorage.setItem("groupName", groupName);
                    }
                });
            });

            return this;
        },

        /**
         * On value change handler.
         *
         * @param {String} value
         */
        onUpdate: function (value) {
            if(!Array.isArray(value)) {
                let groupName = typeof $("input[name='customergroup_general[code]']").val() == 'undefined' ? '' : $("input[name='customergroup_general[code]']").val();
                if (window.e383157Toggle) {
                    fxoStorage.set("groupName", groupName);
                    fxoStorage.set("parentGroupID", value);
                    fxoStorage.set("isSelectedParentGroupID", true);
                } else {
                    localStorage.setItem("groupName", groupName);
                    localStorage.setItem("parentGroupID", value);
                    localStorage.setItem("isSelectedParentGroupID", true);
                }
                var ajaxUrl = this.category_url;
                this.getCategoryId(ajaxUrl);
            }
        },

        /**
         * Get Category ID for selected Parent group
         *
         * @param {String} url
         */
        getCategoryId: function (url) {
            this.loading(true);
            var parentID;
            if (window.e383157Toggle) {
                parentID = fxoStorage.get("parentGroupID") ? fxoStorage.get("parentGroupID") : null;
            } else {
                parentID = localStorage.getItem("parentGroupID") ? localStorage.getItem("parentGroupID") : null;
            }
            if (parentID) {
                $.ajax({
                    url: url,
                    method: "post",
                    dataType: "json",
                    data: {form_key: window.FORM_KEY, parent_id: parentID},
                    success: function (data) {
                        if (window.e383157Toggle) {
                            fxoStorage.set("categoryId", data.categoryId);
                        } else {
                            localStorage.setItem("categoryId", data.categoryId);
                        }
                    },
                    error: $.proxy(this.error, this),
                    complete: $.proxy(this.complete, this)
                });
            } else {
                if (window.e383157Toggle) {
                    fxoStorage.set("categoryId", null);
                } else {
                    localStorage.setItem("categoryId", null);
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
