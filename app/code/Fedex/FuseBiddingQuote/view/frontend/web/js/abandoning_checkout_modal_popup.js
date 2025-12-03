/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require(
    [
        'jquery',
        'Magento_Ui/js/modal/modal',
        "fedex/storage"
    ],
function(
    $,
    modal,
    fxoStorage
) {
    let options = {
        type: 'popup',
        innerScroll: true,
        modalClass: 'retail-abandoning-checkout-modal-popup'
    };
    let isFusebidToggleEnabled = typeof (window.checkoutConfig.is_fusebid_toggle_enabled) !== "undefined" && window.checkoutConfig.is_fusebid_toggle_enabled !== null ? window.checkoutConfig.is_fusebid_toggle_enabled : false;
    
    /**
     * Open abandoning checkout modal popup
     */
    $('#fxgLogoCheckout').on('click', function(e) {
        let qouteLocationDetails;
        if (window.e383157Toggle) {
            qouteLocationDetails = fxoStorage.get('qouteLocationDetails') ? JSON.parse(fxoStorage.get('qouteLocationDetails')) : null;
        }
        else {
            qouteLocationDetails = localStorage.getItem('qouteLocationDetails') ? JSON.parse(localStorage.getItem('qouteLocationDetails')) : null;
        }

        if(qouteLocationDetails !== null && isFusebidToggleEnabled) {
            e.preventDefault();
            $("#retail-abandoning-checkout-modal-popup").css('display','block');
            $("#retail-abandoning-checkout-modal-popup").modal(options).modal("openModal");
        }
    });

    // Close chekout button click in popup
    $('.btn-close-checkout').on('click', function(e) {
        e.preventDefault();
        window.location.href = '/';
    });
});