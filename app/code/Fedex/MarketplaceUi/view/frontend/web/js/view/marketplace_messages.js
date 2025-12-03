/**
 * This file works together with the manage_toast_messages.js
 * Copyright © Fedex. All rights reserved.
 */

define([
    'jquery',
    'ko',
    'uiComponent',
    'marketplace-delivery-toast-messages',
    'fedex/storage',
    'Fedex_Delivery/js/model/toggles-and-settings'
], function ($, ko, Component, deliveryToastMessages, fxoStorage, togglesAndSettings) {
    'use strict';

    let self;

    return Component.extend({
        marketplaceMessages: ko.observableArray(),

        initialize: function() {

            self = this;
            let marketplaceMessages = this.marketplaceMessages();
            let messages;

            window.addEventListener('toast_messages', () => {

                if (window.e383157Toggle) {
                    messages = fxoStorage.get('toast_messages') ?? [];
                } else {
                    messages = localStorage.getItem('toast_messages');
                    messages = typeof messages === 'string' && messages !== ''
                        ? JSON.parse(messages)
                        : [];
                }
                if (messages.length > 6) {
                    messages = messages.slice(1, messages.length);
                    this.marketplaceMessages(messages);
                    if (window.e383157Toggle) {
                        fxoStorage.set(
                            'toast_messages',
                            this.marketplaceMessages()
                        );
                    } else {
                        localStorage.setItem(
                            'toast_messages',
                            JSON.stringify(this.marketplaceMessages())
                        );
                    }
                }
                this.marketplaceMessages(messages);

                deliveryToastMessages.removePreviousDeliveryMethodMessage();
            });
        },

        removeMessage: function(message, removeAfter5Seconds = true) {
            let timeoutDuration = 0;

            if (removeAfter5Seconds) {
                timeoutDuration = 5000;
            }

            setTimeout(() => {
                self.marketplaceMessages.remove(message);

                localStorage.setItem(
                    'toast_messages',
                    JSON.stringify(self.marketplaceMessages())
                );
            }, timeoutDuration);
        }
    });
})
