/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/grid/paging/paging',
    'jquery'
], function (gridPaging, $) {
    'use strict';

    return gridPaging.extend({
        defaults: {
            template: 'Magento_B2b/grid/paging/paging',
            sizesConfig: {
                template: 'Magento_B2b/grid/paging/sizes'
            }
        },

        /**
         * @return {Number}
         */
        getFirstNum: function () {
            return this.pageSize * (this.current - 1) + 1;
        },

        /**
         * @return {*}
         */
        getLastNum: function () {
            if (this.isLast()) {
                return this.totalRecords;
            }

            return this.pageSize * this.current;
        },

        /**
         * @return {Array}
         */
        getPages: function () {
            var pagesList = [],
                i;

            for (i = 1; i <= this.pages; i++) {
                pagesList.push(i);
            }

            return pagesList;
        },
        
        /**
         * B-1096684 | Display text 'Showing page' in the pagination bar of My Quotes page
         * @return {Boolean}
         */
        ifEproSession: function () {
			if ($('div.epro-order-history').length) {
				return true;
			}
			return false;
        },
        
        /**
         * B-1096684 | Display text 'Showing page' in the pagination bar of My Quotes page
         * @return {String}
         */
        getCurrentPage: function () {
            return this.current;
        },
        
       /**
         * B-1096684 | Display text 'Showing page' in the pagination bar of My Quotes page
         * @return {String}
         */
        getLastPage: function () {
            return this.pages;
        }
    });
});
