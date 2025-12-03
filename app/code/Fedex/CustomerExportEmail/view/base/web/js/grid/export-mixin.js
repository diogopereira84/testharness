/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'uiElement'
], function ($, _, Element) {
    'use strict';

    let mixin = {
        defaults: {
            template: 'Fedex_CustomerExportEmail/grid/exportButton',
            selectProvider: 'ns = ${ $.ns }, index = ids',
            checked: '',
            additionalParams: [],
            modules: {
                selections: '${ $.selectProvider }'
            }
        },

        /**
         * Check Customer Admin Page and Toggle Enabled
         * @return {Boolean}
         */
        isCustomerPageToggleEnabled: function () {
            let currentUrl = window.location.href;
            let customerUrl = 'customer/index/index';
            
            if (currentUrl.indexOf(customerUrl) !== -1){
                return true;
            } else {
                return false;
            }
        },

        /**
         * exclude Excel XML option
         * @param  {String} optionvalue
         * @return {Boolean}    
         */
        excludeXML: function(optionvalue) {
            if (optionvalue != 'xml') {
                return true;
            }

            return false;
        },

        /**
         * Redirect to built option url.
         */
        applyOption: function () {
            let option = this.getActiveOption();

            let url = this.buildOptionUrl(option);

            if (this.isCustomerPageToggleEnabled()) {
                url = this.buildOptionUrl(option) + '&customerexporttoggle=true&excluded=false';
            }

            location.href = url;

        }
    };

    return function (target) {
        return target.extend(mixin);
    }
});
