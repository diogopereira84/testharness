/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'underscore',
    'Magento_Ui/js/modal/modal'
    ], function($, customerData, _){
        $.widget('mage.cartExpiryModal', {
            
            options: {
                isLoggedIn: 0,
                expiredModalSelector: '#expired-modal-popup',
                expiryModalSelector: '#expiry-modal-popup',
                modalClass: 'expired-modal-popup',
                expiredCloseButtonSelector: '.close-popup',
                expiryCloseButtonSelector: '.close-expiry-popup'
            },

            isOpen: false,

            /**
             * Widget initialization
             */
            _create: function() {
                this.bindEvent();
                if (this.options.isLoggedIn) {
                    this.init();
                } else {
                    this.removeCloseFlag();
                }
            },

            /**
             * initialization process
             */
            init: function() {
                let self = this;
                this.customerCart = customerData.get('cart');
                this.customerCart.subscribe(function() {
                    self.openModal();
                });
            },

            /**
             * To open modal popup
             */
            openModal: function() {
                if (this.isOpen == false && this.hasCartExpiredItem() && this.hasExpiredModalClosed() == false) {
                    $(this.options.expiredModalSelector).modal(this.getExpiredModalOptions());
                    $(this.options.expiredModalSelector).trigger('openModal');
                    this.isOpen = true;
                    this.closeExpiredModal();
                } else if (this.isOpen == false && this.hasCartExpiryItem() && this.hasExpiredModalClosed() == false) {
                    $(this.options.expiryModalSelector).modal(this.getExpiredModalOptions());
                    $(this.options.expiryModalSelector).trigger('openModal');
                    this.isOpen = true
                    this.closeExpiredModal();
                    
                }
            },

            /**
             * To check cart has expired item
             * @return bool
             */
            hasCartExpiredItem: function () {
                let hasExpiredItem = false;
                let items = !_.isUndefined(this.customerCart().items) ? this.customerCart().items : [];
                _(items).each(function(item, i) {
                    if (!_.isUndefined(item.is_expired) && item.is_expired == true) {
                        hasExpiredItem = true;
                    }
                });
                
                return hasExpiredItem;  
            },

            /**
             * To check cart has expiry item
             * @return bool
             */
             hasCartExpiryItem: function () {
                let hasExpiryItem = false;
                let items = !_.isUndefined(this.customerCart().items) ? this.customerCart().items : [];
                _(items).each(function(item, i) {
                    if (!_.isUndefined(item.is_expiry) && item.is_expiry == true) {
                        hasExpiryItem = true;
                    }
                });
                return hasExpiryItem;  
            },

            /**
             * To get the expired modal popup opions
             */
            getExpiredModalOptions: function() {
                let self =this;
                return {
                    type: 'popup',
                    responsive: true,
                    clickableOverlay: false,
                    modalClass: this.options.modalClass,
                    buttons: [],
                    closed: function () {
                        self.closeExpiredModal();
                    }
                };
            },

            /**
             * To set the cookie for modal
             */
            closeExpiredModal: function () {
                $.cookie('expired_expiry_modal_closed', 1, {path: '/', secure: true, domain: '.fedex.com'});
            },

            /**
             * To remove the cookie for modal
             */
            removeCloseFlag: function () {
                $.removeCookie('expired_expiry_modal_closed', {path: '/', secure: true, domain: '.fedex.com'});
            },

            /**
             * To check cookie exists for modal
             */
            hasExpiredModalClosed: function () {
                return !_.isUndefined($.cookie('expired_expiry_modal_closed')) ? true : false;
            },

            /**
             * To bind the event on html element
             */
            bindEvent: function(){
                let self = this;
                $(document.body).on('click', this.options.expiredCloseButtonSelector ,function() {
                    self.closeExpiredModalPopup();
                });
                
                $(document.body).on('click', this.options.expiryCloseButtonSelector ,function() {
                    self.closeExpiryModalPopup();
                });
                
                $(document).on('loginSuccess', function () {
                    self.init();
                })
            },

            /**
             * To close the expired modal
             */
            closeExpiredModalPopup: function () {
                $(this.options.expiredModalSelector).trigger('closeModal');
            },

            /**
             * To close the expiry modal
             */
             closeExpiryModalPopup: function () {
                $(this.options.expiryModalSelector).trigger('closeModal');
            }
        });
        
    return $.mage.cartExpiryModal;
});
