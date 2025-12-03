/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/template',
    'jquery-ui-modules/widget',
    'mage/translate'
], function ($, mageTemplate) {
    'use strict';

    var loaderAjaxMixin = {
        /**
         * @param {jQuery.Event} e
         * @param {Object} jqxhr
         * @param {Object} settings
         * @private
         */
        _onAjaxSend: function (e, jqxhr, settings) {
            var ctx;

            $(this.options.defaultContainer)
                .addClass(this.options.loadingClass)
                .attr({
                    'aria-busy': true
                });

            if (settings && settings.showLoader) {
                ctx = this._getJqueryObj(settings.loaderContext);
                ctx.trigger('processStart');
            }
        }
    };

    return function (targetWidget) {
        $.widget('mage.loaderAjax', targetWidget.loaderAjax, loaderAjaxMixin);
        return {
            loader: $.mage.loader,
            loaderAjax: $.mage.loaderAjax
        }
    };
});
