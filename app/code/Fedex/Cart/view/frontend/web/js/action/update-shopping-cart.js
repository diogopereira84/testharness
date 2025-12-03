define([
    'Magento_Ui/js/modal/alert',
    'jquery',
    'jquery-ui-modules/widget',
    'mage/validation'
], function (alert, $) {
    'use strict';

    var fedexUpdateShoppingCart = {

        /**
         * Form validation failed.
         */
        onError: function (response) {
            if (response['error_message']) {
                let alertIconImage = typeof (window.checkout) != 'undefined' && typeof (window.checkout.alert_icon_image) != 'undefined' && window.checkout.alert_icon_image != null ? window.checkout.alert_icon_image : '';
                let contentDetails = '<div class="invalid-quatity-popup-content"><h3 class="invalid_title">Invalid Quantity</h3><p class="invalid_description">The requested quantity exceeds the maximum quantity allowed in the shopping cart.</p></div>';
                alert({
                    title: '<img src="' + alertIconImage + '" class="invalid-alert-icon-img" aria-label="alert_image" />',
                    content: contentDetails,
                    modalClass: 'qty-error-popup',
                });
            } else {
                this.submitForm();
            }

        }
    };

    return function (targetWidget) {
        // Example how to extend a widget by mixin object
        $.widget('mage.updateShoppingCart', targetWidget, fedexUpdateShoppingCart); // the widget alias should be like for the target widget

        return $.mage.updateShoppingCart; //  the widget by parent alias should be returned
    };
});
