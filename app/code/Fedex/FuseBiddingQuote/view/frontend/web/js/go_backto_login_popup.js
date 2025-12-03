/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require(
    [
        'jquery',
        'Magento_Ui/js/modal/modal',
        'mage/url',
        'Magento_Customer/js/customer-data',
        'loader'
    ],
function(
    $,
    modal,
    url
) {
    let options = {
        type: 'popup',
        innerScroll: true,
        modalClass: 'retail-go-back-to-login-modal-popup',
        clickableOverlay: false
    };
    
    /**
     * Open abandoning checkout modal popup
     */
    let loginErrorPopup = typeof (window.loginErrorPopup) !== "undefined" && window.loginErrorPopup !== null ? window.loginErrorPopup : false;
    let redirectUrl = typeof (window.redirectUrl) !== "undefined" && window.redirectUrl !== null ? window.redirectUrl : '/';
    if (loginErrorPopup) {
        $(window).on('load', function () {
            $("#retail-go-back-to-login-modal-popup").modal(options).modal("openModal");
        });
    }

    // function to handle logout on click of either go back to login button or cancel button
    function handleLogout(successRedirectUrl) {
        $('body').loader('show');
        window.location.href = successRedirectUrl;
    }

    $(document).on('click', '.btn-go-back-to-login', function(e) {
        e.preventDefault();
        handleLogout(redirectUrl);
    });

    $(document).on('click', '.fuse-bidding-quote .retail-go-back-to-login-modal-popup .modal-inner-wrap .action-close', function(e) {
        e.preventDefault();
        handleLogout('/');
    });
});
