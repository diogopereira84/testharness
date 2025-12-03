define(["jquery", "Magento_Ui/js/modal/modal"],function($, modal) {
    'use strict'

    return function (config, elm) {
        var options = {
            type: 'popup',
            responsive: true,
            title: 'Attention',
            modalClass: 'fedex-cart-modal-box',
            outerClickHandler: function(){
                this.openModal();
            } ,
            buttons: [{
                text: $.mage.__('Continue to Checkout'),
                class: 'continue-to-checkout-button action-primary',
                click: function (e) {
                    this.closeModal();
                    location.href = config.cartUrl;
                }
            }]
        };
        var popup = modal(options, $('#fedex-cart-modal-box'));
    }
});
