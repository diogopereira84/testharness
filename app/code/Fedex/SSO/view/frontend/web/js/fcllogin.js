/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
require(["jquery", 'domReady!'], function ($) {
    $(document).on('click' , '.nav-profile', function(e){
        $(".fcl-login-mobile-toggle").toggle();
        e.stopPropagation();
    });

    $(document).on('click', function(){
        $(".fcl-login-mobile-toggle").hide();
    });

    $(window).on('resize', function(){
        if ($(window).width() > 1024) {
            $(".fcl-login-mobile-toggle").hide();
        }
    });
});
