/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
require(['jquery', 'Magento_Ui/js/modal/modal'],function($, modal) {
    $('.cart-warning-popup-main .cart-warning-btn-text a').on('click', function(event){
        $('.cart-warning-popup-main').modal('closeModal');
    });
}); 
