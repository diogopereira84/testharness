define([
    "Magento_Checkout/js/model/quote"
], function (
    quote
) {
    'use strict';
    return {
        isOnlyThreeProductAvailable: function () {
            const quoteItems = quote.getItems();
            let firstPartyCounter = 0;
            let thirdPartyCounter = 0;
            quoteItems.forEach(function (item, index) {
                if ( Boolean(item.isMarketplaceProduct) ) {
                    thirdPartyCounter++
                }
                else {
                    firstPartyCounter++
                }
            });
            if(firstPartyCounter === 0 && thirdPartyCounter > 0) {
                return true;
            }
            return false;
        }
    }
});

