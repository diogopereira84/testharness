/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'Magento_Checkout/js/view/summary/abstract-total'
], function (viewModel) {
    'use strict';

    return viewModel.extend({
        defaults: {
            displayArea: 'after_details',
            template: 'Magento_Checkout/summary/item/details/subtotal'
        },

        /**
         * @param {Object} quoteItem
         * @return {*|String}
         */
        getValue: function (quoteItem) {
            let isQuoteIsPriceable = typeof (window.checkoutConfig.is_quote_price_is_dashable) != 'undefined' && window.checkoutConfig.is_quote_price_is_dashable != null ? window.checkoutConfig.is_quote_price_is_dashable : false;

            let isExplorersD198644FixToggleEnable = typeof (window.checkoutConfig.explorers_d_198644_fix) != 'undefined' && window.checkoutConfig.explorers_d_198644_fix != null ? window.checkoutConfig.explorers_d_198644_fix : false;

            if (isQuoteIsPriceable && quoteItem['row_total'] == 0) {
                return '$--.--';
            } else {
                let quoteItemPrice = quoteItem['row_total'];
                if (isExplorersD198644FixToggleEnable && typeof quoteItemPrice === 'string' && quoteItemPrice !== '') {
                    quoteItemPrice = quoteItemPrice.replace(/,/g , '');
                }

                return this.getFormattedPrice(quoteItemPrice);
            }
        }
    });
});
