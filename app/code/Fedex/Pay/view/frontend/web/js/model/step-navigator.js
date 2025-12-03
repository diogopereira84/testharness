/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'ko',
    'shippingFormAdditionalScript'
], function ($, ko, shippingFormAdditionalScript) {
    'use strict';

    let steps = ko.observableArray();
    let isSdeStore = shippingFormAdditionalScript.isSdeStore();
    let isSelfregCustomer = window.checkoutConfig.is_selfreg_customer;

    return {
        steps: steps,
        stepCodes: [],
        validCodes: [],

        /**
         * @return {Boolean}
         */
        handleHash: function () {
            let hashString = window.location.hash.replace('#', ''),
                isRequestedStepVisible;

            if (hashString === '') {
                return false;
            }

            if ($.inArray(hashString, this.validCodes) === -1) {
                window.location.href = window.checkoutConfig.pageNotFoundUrl;

                return false;
            }

            isRequestedStepVisible = steps.sort(this.sortItems).some(function (element) {
                return (element.code == hashString || element.alias == hashString) && element.isVisible(); //eslint-disable-line
            });

            //if requested step is visible, then we don't need to load step data from server
            if (isRequestedStepVisible) {
                return false;
            }

            steps().sort(this.sortItems).forEach(function (element) {
                if (element.code == hashString || element.alias == hashString) { //eslint-disable-line eqeqeq
                    element.navigate(element);
                } else {
                    element.isVisible(false);
                }
            });

            return false;
        },

        /**
         * @param {String} code
         * @param {*} alias
         * @param {*} title
         * @param {Function} isVisible
         * @param {*} navigate
         * @param {*} sortOrder
         */
        registerStep: function (code, alias, title, isVisible, navigate, sortOrder) {
            let hash, active;

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
            active = this.getActiveItemIndex();
            steps.each(function (elem, index) {
                if (active !== index) {
                    elem.isVisible(false);
                }
            });
            this.stepCodes.push(code);
            hash = window.location.hash.replace('#', '');

            if (hash != '' && hash != code) { //eslint-disable-line eqeqeq
                //Force hiding of not active step
                isVisible(false);
            }
        },

        /**
         * @param {Object} itemOne
         * @param {Object} itemTwo
         * @return {Number}
         */
        sortItems: function (itemOne, itemTwo) {
            return itemOne.sortOrder > itemTwo.sortOrder ? 1 : -1;
        },

        /**
         * @return {Number}
         */
        getActiveItemIndex: function () {
            let activeIndex = 0;

            steps().sort(this.sortItems).some(function (element, index) {
                if (element.isVisible()) {
                    activeIndex = index;

                    return true;
                }

                return false;
            });

            return activeIndex;
        },

        /**
         * @param {*} code
         * @return {Boolean}
         */
        isProcessed: function (code) {
            let activeItemIndex = this.getActiveItemIndex(),
                sortedItems = steps().sort(this.sortItems),
                requestedItemIndex = -1;

            sortedItems.forEach(function (element, index) {
                if (element.code == code) { //eslint-disable-line eqeqeq
                    requestedItemIndex = index;
                }
            });

            return activeItemIndex > requestedItemIndex;
        },

        /**
         * @param {*} code
         * @param {*} scrollToElementId
         */
        navigateTo: function (code, scrollToElementId) {
            let sortedItems = steps().sort(this.sortItems),
                bodyElem = $('body');
            scrollToElementId = scrollToElementId || null;

            if (!this.isProcessed(code)) {
                return;
            }
            if (code=="payment") {
                $(".opc-block-summary .table-totals .incl .isnot_review_page").hide();
                $(".opc-block-summary .table-totals .incl .is_review_page").show();
            } else {
                $(".opc-block-summary .table-totals .incl .isnot_review_page").show();
                $(".opc-block-summary .table-totals .incl .is_review_page").hide();
            }
            sortedItems.forEach(function (element) {
                if (element.code == code) { //eslint-disable-line eqeqeq
                    element.isVisible(true);
                    bodyElem.animate({
                        scrollTop: 0
                    }, 0, function () {
                        // D-92464 : SDE : Issue when navigating back with checkout breadcrumb and Edit button
                        if (isSdeStore === true || isSelfregCustomer) {
                            window.location = window.checkoutConfig.checkoutUrl + '#' + code;
                        } else {
                            let checkoutUrl = window.checkoutConfig.checkoutUrl;
                            window.location = checkoutUrl.replace(/\/+$/, '') + '#' + code;
                        }
                        console.log("Navigated to modified");
                        $('.error-container').addClass('api-error-hide');

                        let selectedShippingId;
                        if(window.e383157Toggle){
                            selectedShippingId = localStorage.getItem("selectedRadioShipping");
                        }else{
                            selectedShippingId = localStorage.getItem("selectedRadioShipping");
                        }

                        if (selectedShippingId) {
                            $('#' + selectedShippingId).parent(".row").find(".radio").prop("checked", true);
                        }
                    });

                    if (scrollToElementId && $('#' + scrollToElementId).length) {
                        bodyElem.animate({
                            scrollTop: $('#' + scrollToElementId).offset().top
                        }, 0);
                    }
                } else {
                    element.isVisible(false);
                }
            });
            this.closeNonCombinibleMessages();
        },

        /**
         * Sets window location hash.
         *
         * @param {String} hash
         */
        setHash: function (hash) {
            window.location.hash = hash;
        },

        /**
         * Next step.
         */
        next: function () {
            let activeIndex = 0,
                code,
                element,
                navigate;

            steps().sort(this.sortItems).forEach(function (element, index) {
                if (element.isVisible()) {
                    element.isVisible(false);
                    activeIndex = index;
                }
            });

            if (steps().length > activeIndex + 1) {
                element = steps()[activeIndex + 1];
                code = element.code;
                navigate = element.navigate;
                steps()[activeIndex + 1].isVisible(true);
                this.setHash(code);
                document.body.scrollTop = document.documentElement.scrollTop = 0;
                this.closeNonCombinibleMessages();

                // Call navigate function of the next step
                // This is needed for the steps that have navigate function
                // and need to perform some action every time the step is visible.
                if (navigate) {
                    navigate(element);
                }
            }
        },

        closeNonCombinibleMessages: function () {
            window.dispatchEvent(new Event('closePromoCodeDiscount'));
            window.dispatchEvent(new Event('closeNonCombinableDiscount'));
            window.dispatchEvent(new Event('closeMarketplaceDisclaimer'));
        }
    };
});
