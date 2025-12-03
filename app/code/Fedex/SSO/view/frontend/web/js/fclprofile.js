/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
require(["jquery", "mage/cookies"], function ($) {
    $(document).on( "click", ".right-top-header-links .fcl-login .links-container", function() {
        $(".fcl-login-error-popup").hide();
    });
    $('.login-error-popup-error-close').on('click', function () {
        $(".fcl-login-error-popup").hide();
    });
    $('#retry-login').on('click', function () {
        $(".fcl-login-error-popup").hide();
    });
});
