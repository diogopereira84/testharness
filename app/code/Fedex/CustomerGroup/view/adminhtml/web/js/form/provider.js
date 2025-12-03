/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/form/provider',
    'jquery',
    'fedex/storage'
], function (Provider,$,fxoStorage) {
    'use strict';

    return Provider.extend({

    	/**
         * Saves currently available data.
         *
         * @param {Object} [options] - Addtitional request options.
         * @returns {Provider} Chainable.
         */
        save: function (options) {
            $("input[name='customergroup_general[code]']").trigger('change');
            if(window.e383157Toggle){
                fxoStorage.set("groupName", '');
                fxoStorage.set("parentGroupID", null);
                fxoStorage.set("newCategoryIds", null);
            }else{
                localStorage.setItem("groupName", '');
                localStorage.setItem("parentGroupID", null);
                localStorage.setItem("newCategoryIds", null);
            }
            var data = this.get('data');
            this.client.save(data, options);
            return this;
        }

	});
});
