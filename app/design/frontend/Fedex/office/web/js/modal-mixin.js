define([
    'jquery',
    'jquery-ui-modules/widget',
    'jquery-ui-modules/core'
], function ($) {
 
    return function (widget) {
        let uploadToQuoteConfigValues = false;
        let checkoutQuotePriceisDashable = false;
        let infoIcon = "";
        let closeIcon = "";
        if (typeof window.checkoutConfig !== "undefined" && window.checkoutConfig !== null) {
            if (typeof window.checkoutConfig.upload_to_quote_config_values !== "undefined" && window.checkoutConfig.upload_to_quote_config_values !== null) {
              uploadToQuoteConfigValues = window.checkoutConfig.upload_to_quote_config_values;
            }
            if (typeof window.checkoutConfig.is_quote_price_is_dashable !== "undefined" && window.checkoutConfig.is_quote_price_is_dashable !== null) {
                checkoutQuotePriceisDashable = window.checkoutConfig.is_quote_price_is_dashable;
            }
            infoIcon = typeof (window.checkoutConfig !=="undefined") && typeof(window.checkoutConfig !==null) ? window.checkoutConfig.info_icon_url :"";
    
            closeIcon = typeof (window.checkoutConfig !=="undefined") && typeof(window.checkoutConfig !==null) ? window.checkoutConfig.cross_icon_url :"";
        }
        
        $.widget('mage.modal', widget, {
            options: {
                infoIconUrl: infoIcon,
                closeIconUrl: closeIcon,
                isUploadToQuote: JSON.parse(uploadToQuoteConfigValues),
                isCheckoutQuotePriceDashable: checkoutQuotePriceisDashable,
            },
            closeModal: function () {
                var that = this;
                if(this.options.isOpen) {
                    this._removeKeyListener();
                    this.options.isOpen = false;
                    this.modal.one(this.options.transitionEvent, function () {
                        that._close();
                    });
                    this.modal.removeClass(this.options.modalVisibleClass);
                    if (!this.options.transitionEvent) {
                        that._close();
                    }
                }
                return this.element;
            }
        });
        return $.mage.modal;
    };
});