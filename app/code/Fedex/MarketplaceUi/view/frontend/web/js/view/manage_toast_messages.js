/**
 * This file works together with the marketplace_messages.js
 * Copyright © Fedex. All rights reserved.
 */

define([
    'jquery',
    'ko',
    'fedex/storage'
], function ($, ko, fxoStorage) {
    'use strict';

    return {
        addMessage: function (message, sanitizeFirst = false, removeAfter5Seconds = true) {
            let toastMessagesArray;
            if (window.e383157Toggle) {
                toastMessagesArray = fxoStorage.get("toast_messages");
                if (!toastMessagesArray) {
                    fxoStorage.set('toast_messages', []);
                }
            } else {
                toastMessagesArray = localStorage.getItem("toast_messages");
                if (!toastMessagesArray) {
                    localStorage.setItem(
                        'toast_messages',
                        JSON.stringify([])
                    );
                }
            }
            let messagesStorage;
            if (window.e383157Toggle) {
                messagesStorage = fxoStorage.get('toast_messages');
            }else{
                messagesStorage = JSON.parse(localStorage.getItem('toast_messages'));
            }
            let messageObj = this.convertToObject(message);
            // This is ideal for ephemeral messages.
            // It make sures that the message is gonna be dispatched,
            // by sanitizing it from the localstorage.
            if (sanitizeFirst) {
                for (let i = 0; i < messagesStorage.length; i++) {
                    if (messagesStorage[i].category === messageObj.category) {
                        messagesStorage.splice(i, 1);
                    }
                }
            }
            
            if(window.checkout?.tiger_d_213919_marketplace_seller_downtime_message_fix) {
                messageObj.removeAfter5Seconds = removeAfter5Seconds;
            }
            
            messagesStorage.push(messageObj);
            if (window.e383157Toggle) {
                fxoStorage.set(
                    'toast_messages',
                    messagesStorage
                );
            } else {
                localStorage.setItem(
                    'toast_messages',
                    JSON.stringify(messagesStorage)
                );
            }
            $(window).ready(function () {
                window.dispatchEvent(new Event('toast_messages'));
            });
        },

        /**
         * This input refers to the object from localStorage.
         * It converts the JSON string to an object.
         */
        convertToObject: function (input) {
            let doc = new DOMParser().parseFromString(input, "text/html");
            return JSON.parse(doc.documentElement.textContent);
        }
    };
})
