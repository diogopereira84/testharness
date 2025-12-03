define(['jquery', 'jquery/jquery-storageapi'], function ($) {
    'use strict';
    let storage = {
        /**
         * Initialize class
         *
         * @return Chainable.
         */
        initialize: function () {
            this.namespace = 'fedex-cache-storage';
            this.initLocalStorage();

            return this;
        },
        /**
         * Initialize localStorage
         *
         * @return Chainable.
         */
        initLocalStorage: function () {
            this.localStorage = $.initNamespaceStorage(this.namespace).localStorage;

            return this;
        },
        /**
         * Retrieve value from local storage
         * @param {String} key
         */
        get: function (key) {
            return this.localStorage.get(key)
        },

        /**
         * Save value from local storage.
         * @param {String} key
         * @param value
         */
        set: function (key, value) {
            this.localStorage.set(key, value)
        },

        /**
         * Delete key from local storage.
         * @param {String} key
         */
        delete: function (key) {
            this.localStorage.remove(key)
        },

        /**
         * Clear all key/values from the namespace
         */
        clearAll: function () {
            this.localStorage.removeAll();
        }
    };
    return storage.initialize();
});
