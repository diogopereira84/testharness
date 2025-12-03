/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/form/components/insert-form',
    'fedex/storage'
], function (Insert,fxoStorage) {
    'use strict';

    return Insert.extend({
        defaults: {
            listens: {
                responseData: 'onResponse'
            },
            modules: {
                form: '${ $.formProvider }',
                categoryModal: '${ $.categoryModalProvider }'
            }
        },

        /**
         * Close modal, reload customer address listing and save customer address
         *
         * @param {Object} responseData
         */
        onResponse: function (responseData) {
            var newCatIds;
            if(window.e383157Toggle){
                newCatIds = fxoStorage.get('newCategoryIds') ?? []
            }else{
                newCatIds = (
                    localStorage.getItem("newCategoryIds") !== 'null' &&
                    typeof localStorage.getItem("newCategoryIds") != 'undefined'
                ) ? JSON.parse(localStorage.getItem("newCategoryIds")) : [];
            }
            if (!responseData.error) {
                let newCatId = typeof responseData.data.new_category_id !== 'undefined' ? responseData.data.new_category_id : null;
                newCatIds.push(newCatId);
                if (window.e383157Toggle) {
                    fxoStorage.set("newCategoryIds", newCatIds);
                } else {
                    localStorage.setItem("newCategoryIds", JSON.stringify(newCatIds));
                }
                this.categoryModal().closeModal();
                if(window.e383157Toggle){
                    fxoStorage.set("isCatalogPermissionActive", true);
                }else{
                    localStorage.setItem("isCatalogPermissionActive", true);
                }
                location.reload(true);
            }
        }
    });
});
