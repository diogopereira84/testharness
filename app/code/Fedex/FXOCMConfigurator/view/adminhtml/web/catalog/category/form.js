/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/modal/alert'
], function ($, alert) {
    'use strict';

    // code added to limit category name of 100 characater
    let waitForCategoryFormLoad = setInterval(function () {
        if (jQuery(".character_limit_hundred  input[name='name']").length) {
            var maxLength = 100; // Change this to your desired character limit
            var categoryNameField = jQuery(".character_limit_hundred input[name='name']");
            // Add input event listener to enforce character limit
            categoryNameField.on('input', function () {
                var value = jQuery(this).val();
                if (value.length > maxLength) {
                    jQuery(this).val(value.substr(0, maxLength));
                }
            });
            clearInterval(waitForCategoryFormLoad);
        }
    }, 3000);

    return function (config) {
        var categoryForm = {
            options: {
                categoryIdSelector: 'input[name="id"]',
                categoryPathSelector: 'input[name="path"]',
                categoryParentSelector: 'input[name="parent"]',
                categoryLevelSelector: 'input[name="level"]',
                refreshUrl: config.refreshUrl
            },

            /**
             * Sending ajax to server to refresh field 'path'
             * @protected
             */
            refreshPath: function () {
                if (!$(this.options.categoryIdSelector)) {
                    return false;
                }
                $.ajax({
                    url: this.options.refreshUrl,
                    method: 'GET',
                    showLoader: true
                }).done(this._refreshPathSuccess.bind(this));
            },

            /**
             * Refresh field 'path' on ajax success
             * @param {Object} data
             * @private
             */
            _refreshPathSuccess: function (data) {
                if (data.error) {
                    alert({
                        content: data.message
                    });
                } else {
                    $(this.options.categoryIdSelector).val(data.id).trigger('change');
                    $(this.options.categoryPathSelector).val(data.path).trigger('change');
                    $(this.options.categoryParentSelector).val(data.parentId).trigger('change');
                    $(this.options.categoryLevelSelector).val(data.level).trigger('change');
                }
            }
        };

        $('body').on('categoryMove.tree', $.proxy(categoryForm.refreshPath.bind(categoryForm), this));
    };
});
