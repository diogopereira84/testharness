/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* B-1027092 */

define(['Magento_Ui/js/grid/columns/column'], function (Column) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'Magento_NegotiableQuote/quote/grid/cells/text_with_title'
        },

        /**
         * Returns truncated name
         *
         * @returns {String}
         */
        getShortName: function (name, maxLength) {
            return name;
        }
    });
});
