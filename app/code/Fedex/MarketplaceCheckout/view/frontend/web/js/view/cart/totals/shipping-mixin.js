define([
    'jquery',
    'Fedex_MarketplaceCheckout/js/mirakl/model/quote-helper'
], function ($, quoteHelper) {
    'use strict';

    return function(Component) {
        return Component.extend({
            hasThirdPartyItem: function() {
                return quoteHelper.isMixedQuote() || quoteHelper.isFullMarketplaceQuote();
            }
        });
    }
});
