define([
    'jquery',
    'fedex/storage'
], function ($, fxoStorage) {
    'use strict';

    return {

        /**
         * Check if the url is valid
         * @param {string} url
         * @returns {string|null}
         */
        sanitizeUrl: function (url) {
            if (!url) {
                console.error('Missing URL');
                return '';
            }

            try {
                const parsedUrl = new URL(url);
                const allowedProtocols = ['http:', 'https:'];
                if (!allowedProtocols.includes(parsedUrl.protocol)) {
                    throw new Error('Invalid protocol');
                }
                return parsedUrl.href;
            } catch (error) {
                console.error('Invalid URL:', error);
                return null;
            }
        },

        /**
         * Randomizes a string
         * @param {number} len
         * @param {string} arr
         * @returns {string}
         */
        randomize: function (len, arr) {
            let ans = '';
            for (let i = len; i > 0; i--) {
                ans += arr[Math.floor(Math.random() * arr.length)];
            }
            return ans;
        },

        /**
         * Generates Verifier
         * @returns {string}
         */
        getVerifier: function () {
            const DEFAULT_VERIFIER_LENGTH = 20;
            const CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            return this.randomize(DEFAULT_VERIFIER_LENGTH, CHARACTERS);
        },

        /**
         * Generates challenger
         * @param {string} verifier
         * @returns {Promise<string>}
         */
        getChallenger: async function (verifier) {
            const msgUint8 = new TextEncoder().encode(verifier);
            const hashBuffer = await window.crypto.subtle.digest("SHA-256", msgUint8);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            const hashBase64 = btoa(String.fromCharCode(...hashArray));
            return hashBase64;
        },

        /**
         * Makes ajax request
         * @param {string} url
         * @param {Object} payload
         * @returns {Promise}
         */
        post: function (url, payload, contentType) {
            contentType = contentType || 'application/json';

            return $.ajax({
                dataType: "json",
                contentType: contentType,
                method: "POST",
                url: url,
                data: payload
            });
        },

        /**
         * Redirects to URL
         * @param {Object} response
         * @param {string} code_verifier
         */
        redirect: function (response, code_verifier) {
            const auth_code = response['auth_code'];
            const payload = { auth_code, code_verifier };
            const url = this.sanitizeUrl(response['url']);

            this.post(url, JSON.stringify(payload)).done((response) => {
                if (response.location) {
                    location.href = response.location;
                }
            }).fail((jqXHR, textStatus, errorThrown) => {
                console.error("AJAX request failed:", textStatus, errorThrown);
            });
        },

        /**
         * Punchout
         * @param {string} code_verifier
         * @param {string} code_challenge
         * @param {Object} config
         */
        punchout: function (code_verifier, code_challenge, config) {
            const verifierKey = 'code_verifier';
            const challengeKey = 'code_challenge';

            fxoStorage.set(verifierKey, code_verifier);
            fxoStorage.set(challengeKey, code_challenge);

            const url = `${config.form_action}?t=${Date.now()}`;
            const contentType = 'application/x-www-form-urlencoded; charset=UTF-8';
            const payload = { code_challenge, ...config.payload };

            this.post(url, payload, contentType).done((response) => {
                if (Array.isArray(response) && response.length === 0) {
                    location.href = config.error_url;
                } else {
                    fxoStorage.delete(verifierKey);
                    fxoStorage.delete(challengeKey);
                    this.redirect(response, code_verifier);
                }
            }).fail((jqXHR, textStatus, errorThrown) => {
                console.error("AJAX request failed:", textStatus, errorThrown);
                location.href = config.error_url;
            });
        }
    };
});
