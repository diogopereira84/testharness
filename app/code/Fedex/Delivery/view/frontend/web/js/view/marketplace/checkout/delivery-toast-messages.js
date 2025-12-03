define([
    "jquery",
    "ko",
    "mage/translate",
    "fedex/storage"
], function ($, ko, $t,fxoStorage) {
    'use strict'

    return {

        /**
         * This function uses the values from the admin:
         * ---
         * Stores -> Configuration -> Fedex internal configurations ->
         * Settings -> Marketplace Toast Configuration
         *
         * @param Void
         * @return String
         */
        getDeliveryMethodMessageObj: function () {

            let checkoutConfig = window.checkoutConfig;

            let message = {
                'type': 'success',
                'category': 'delivery_method_changed',
                'title': checkoutConfig.toastTitle,
                'text': '',
                'removeAfter5Seconds': false,
                'change2shipping': checkoutConfig.toastShippingContent,
                'change2pickup': checkoutConfig.toastPickupContent
            };

            if ($(window).width() < 768) {
                // Handling the mobile position
                $('.toast-messages').prependTo('#checkoutSteps');
            }

            return message;
        },

        removePreviousDeliveryMethodMessage: function () {
            let toastMessagesArray;
            if (window.e383157Toggle) {
                toastMessagesArray = fxoStorage.get("toast_messages");
            } else {
                toastMessagesArray = localStorage.getItem("toast_messages");
            }
            if (typeof toastMessagesArray !== 'object') {
                toastMessagesArray = typeof toastMessagesArray === 'string' && toastMessagesArray !== ""
                    ? JSON.parse(toastMessagesArray)
                    : []
            }
            if (!Object.is(toastMessagesArray, null)) {
                toastMessagesArray = toastMessagesArray.filter((message) => message.category !== 'delivery_method_changed');
                if (window.e383157Toggle) {
                    fxoStorage.set('toast_messages', toastMessagesArray);
                } else {
                    localStorage.setItem('toast_messages', toastMessagesArray);
                }
            }

        }
    }
});
