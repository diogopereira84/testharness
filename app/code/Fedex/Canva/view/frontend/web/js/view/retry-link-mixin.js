define([
    'jquery',
    'Fedex_Canva/js/view/canva',
], function ($, canva) {
    'use strict';

    const reTryLinkWidgetMixin = {
        options: {
            isOnProductPage: $('body').hasClass('catalog-product-view'),
            isOnCanvaPage: $('body').hasClass('canva-index-index'),
            isFromCanvaFlow: $.cookie("canva_error_profile_popup"),
            canvaLogin: '#canva-login-modal',
        },

        /**
         * Check if is on canva flow, then open Canva Login Modal instead of default one
         *
         * @returns {Element, Boolean}
         */
        _retryLogin: function () {
            if ((this.options.isOnProductPage && this.options.isFromCanvaFlow) || (this.options.isOnCanvaPage && this.options.isFromCanvaFlow)) {
                const url = this.options.sso().login_page_url;
                const param = this.options.sso().login_page_query_parameter;
                window.CANVA_LOGIN_REDIRECT_URL(`${canva.getURL()}`);
                window.location = `${canva.getURL()}`;
                // $(this.options.canvaLogin).modal('openModal');
                return false;
            } else {
                return this._super();
            }
        }
    };

    return function (reTryLinkWidget) {
        $.widget('mage.SSORetryLink', reTryLinkWidget, reTryLinkWidgetMixin);
        return $.mage.SSORetryLink;
    };
});
