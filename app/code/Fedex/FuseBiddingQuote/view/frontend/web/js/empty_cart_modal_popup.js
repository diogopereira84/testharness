/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function ($, modal) {
    'use strict';

    /**
     * empty cart modal options
     */
    let emptyCartModalOptions = {
        type: 'popup',
        innerScroll: true,
        modalClass: 'retail-empty-cart-modal-popup',
        buttons: []
    };

    return emptyCartModalOptions;
});
