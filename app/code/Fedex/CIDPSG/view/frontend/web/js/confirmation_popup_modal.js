/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'jquery',
    'underscore',
    'Magento_Ui/js/modal/modal'
    ], function($){
        $.widget('mage.confirmationModal', {
            
            options: {
                confirmationModalSelector: '#cid-confirmation-modal-popup',
                modalClass: 'cid-confirmation-modal-popup'
            },

            /**
             * To open modal popup
             */
            openModal: function() {
                let self = this;
                let modalSel = this.options.confirmationModalSelector;
                $(modalSel).modal(this.getConfirmationModalOptions());
                $(modalSel).trigger('openModal');
                $(document).on('click', function(event) {
                    if(!$(event.target).closest('.cid-confirmation-modal-popup').length) {
                        self.closeConfirmationModal();
                    }
                })
            },

            /**
             * To get the confirmation modal popup opions
             */
            getConfirmationModalOptions: function() {
                let self = this;
                return {
                    type: 'popup',
                    responsive: true,
                    clickableOverlay: false,
                    modalClass: this.options.modalClass,
                    buttons: [],
                    closed: function () {
                        self.closeConfirmationModal();
                    }
                };
            },
            
            /**
             * To close the confirmation modal
             */
            closeConfirmationModal: function () {
                $(this.options.confirmationModalSelector).trigger('closeModal');
                window.location.href = window.BASE_URL;
            },

        });
        
    return $.mage.confirmationModal;
});
