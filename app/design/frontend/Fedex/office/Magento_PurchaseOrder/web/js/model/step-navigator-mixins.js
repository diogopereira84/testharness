/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'ko',
    'mage/translate'
], function ($, ko, $t) {
    'use strict';

    return function (target) {
        var steps = target.steps;

        /**
         * @param {String} code
         * @param {*} alias
         * @param {*} title
         * @param {Function} isVisible
         * @param {*} navigate
         * @param {*} sortOrder
         */
        target.registerStep = function (code, alias, title, isVisible, navigate, sortOrder) {
            var hash;

            if (window.checkoutConfig.isPurchaseOrderEnabled === true && code === 'payment') {
                title = $t('Submit Order');
            }

            if ($.inArray(code, this.validCodes) !== -1) {
                throw new DOMException('Step code [' + code + '] already registered in step navigator');
            }

            if (alias != null) {
                if ($.inArray(alias, this.validCodes) !== -1) {
                    throw new DOMException('Step code [' + alias + '] already registered in step navigator');
                }

                this.validCodes.push(alias);
            }

            this.validCodes.push(code);

            steps.push({
                code: code,
                alias: alias != null ? alias : code,
                title: title,
                isVisible: isVisible,
                navigate: navigate,
                sortOrder: sortOrder
            });

            this.stepCodes.push(code);

            hash = window.location.hash.replace('#', '');

            if (hash.length && hash !== code) {
                // Hide inactive step
                isVisible(false);
            }
        };

        return target;
    };
});
