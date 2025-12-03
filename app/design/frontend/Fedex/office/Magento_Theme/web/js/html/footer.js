define([
    'jquery',
    'domReady!'
], function($) {
    'use strict';

    return function (config, elm) {
        window.mediaJsImgpath = config.mediaJsImgpath;
        window.viewJsImgpath = config.viewJsImgpath;
        window.alertImgPath = config.alertImgPath;
        window.configuratorUrl = config.configuratorUrl;
        window.siteName = config.siteName;
        window.authToken = config.authToken;

        require(['jquery'], function($) {
            $(document).ajaxComplete(function() {
                if(!window.location.href.includes("/cart") && window.location.href.includes("/checkout") && (!$('#payment .error-title').text())) {
                    $('#payment').find('.checkout-error').addClass('api-error-hide');
                }
            });
            $(window).on('resize', function(){
                setTimeout(function () {
                    if ($(window).width() < 800) {
                        $('.checkout-index-index .opc-sidebar.opc-summary-wrapper').removeClass('custom-slide');
                    } else {
                        $('.checkout-index-index .opc-sidebar.opc-summary-wrapper').addClass('custom-slide');
                    }
                }, 1000);
            });
        });
    }
});
