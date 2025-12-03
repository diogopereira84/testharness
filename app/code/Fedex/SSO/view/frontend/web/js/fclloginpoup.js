/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* Call profile service to update customer address */
function profileUpdate(e) {
    require(["jquery", 'mage/url'], function ($, url) {
        var updateProfile =  url.build('fcl/index/updateaccount');
        var redirectUrl = $(".customer-menu .my-profile a").length ? $(".customer-menu .my-profile a").attr('data-url') : url.build('customer/account');
        $.ajax({
            type: "POST",
            enctype: "multipart/form-data",
            url: updateProfile,
            data: [],
            processData: false,
            contentType: false,
            cache: false,
            showLoader: true
        }).done(function (response) {
            window.location.href = redirectUrl;
        });
    });
}

require(["jquery"], function ($) {
    $(document).on("click", ".customerdropdown", function() {
        $(".wlgn-login-container").toggleClass("active");
    });

    $(document).on('click', function(e) {
        var dropDownContainer = $(".customerdropdown");
        var headerContainer = $(".header");

        if ( !dropDownContainer.is(e.target)
            && dropDownContainer.has(e.target).length === 0
            && !headerContainer.is(e.target)
            && headerContainer.has(e.target).length === 0 ) {
            $(".wlgn-login-container").removeClass("active");
        }
    });

    /*Close my profile dropdown after click on minicart*/
    $('a.action.showcart').on('click', function() {
        $('.links-container.wlgn-login-container').removeClass('active');
    });
});

require(["jquery"], function ($) {
        $(".showcart").on('click', function() {
            $(".fcl-login-mobile-toggle").hide();
        });
    });
