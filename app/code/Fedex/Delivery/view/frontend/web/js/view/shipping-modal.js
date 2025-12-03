define([
    'jquery',
    'fedex/storage'
], function($,fxoStorage) {
    'use strict';

    /**
     * open auto retail ship and pick popup on checkout page after login and proceed  to checkout
     */
    return  {
        openShipPickPopup: function (scop) {
            var self = scop;
            if (!window.checkoutConfig.is_commercial) {
                let isAutoPopup;
                if(window.e383157Toggle){
                    isAutoPopup = (typeof (fxoStorage.get('autopopup')) !== 'undefined')
                        && (fxoStorage.get('autopopup') !== null);
                }else{
                    isAutoPopup = (typeof (localStorage.getItem('autopopup')) !== 'undefined')
                        && (localStorage.getItem('autopopup') !== null);
                }
                if (isAutoPopup) {
                    var options2 = {
                        type: 'popup',
                        modalClass: 'pickup-shipup-model',
                        buttons: [{
                            text: $.mage.__('Continue'),
                            class: 'mymodal1',
                            click: function () {
                                this.closeModal();
                            }
                        }]
                    };
                    $('#pickup-shipup-popup-modal').modal(options2).modal('openModal');
                    $('.pickup-shipup-model').attr('aria-label','Pickup/Delivery');
                    if(window.e383157Toggle){
                        fxoStorage.delete('autopopup');
                    }else{
                        localStorage.removeItem('autopopup');
                    }
                    $('.anchor-pickup, .anchor-ship').on('click', function(e) {
                        e.preventDefault();
                        if ($(this).hasClass('anchor-pickup')) {
                            self.onclickTriggerShip();
                            $('#pickup-shipup-popup-modal').modal(options2).modal('closeModal');
                        }
                        if ($(this).hasClass('anchor-ship')) {
                            var pickupLabel = null;
                            if (typeof(pickupLabel = $('.opc-progress-bar-item._active > span:first-child').html()) !== 'undefined' && pickupLabel == 'Delivery method') {
                                $('.opc-progress-bar-item._active > span:first-child').html('Delivery method');
                            }
                            self.onclickTriggerPickup();
                            $('#pickup-shipup-popup-modal').modal(options2).modal('closeModal');
                        }
                        return false;
                    });
                }
            }
        }
    }
});
