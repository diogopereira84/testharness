define([
    'mage/translate'
], function ($t) {
    'use strict';

    return {
        ERROR_CODES: {
            SESSION_EXPIRED: 'SESSION_EXPIRED',
            HOLD_DATE_INVALID: 'HOLDUNTILDATE.EARLIER.THAN.ORDERREADYDATE',
            HOLD_DATE_INVALID_ALT: 'HOLD_UNTIL_DATE_EARLIER_THAN_ORDER_READY_DATE',
            NETWORK_ERROR: 'NETWORK_ERROR',
            NO_LOCATIONS: 'NO_LOCATIONS',
            INVALID_RESPONSE: 'INVALID_RESPONSE'
        },

        ERROR_MESSAGES: {
            NO_LOCATIONS: $t(window.checkoutConfig?.pickup_search_error_description),
            DEFAULT: $t("System ErrorSystem error, Please try again.")
        }
    };
});