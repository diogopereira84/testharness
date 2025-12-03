define(['jquery'], function($){
    'use strict';

    return function(config, elm) {
        $(document).ready(function(){
            let isCommercial = typeof (window.checkout.is_commercial) != 'undefined' && window.checkout.is_commercial != null ? window.checkout.is_commercial : false;

            if (isCommercial) {
                $('.header-nav-pannel .right-top-header-links').prepend('<div class="commercial-right-header-links"><div class="commercial-login-msg"><div class="commercial-customer-dropdown customerdropdown"></div></div></div>');

                $.ajax({
                    data: null,
                    url: config.ajaxLoginUrl,
                    method: 'POST',
                    success: function(result){
                        $(".right-top-header-links .commercial-right-header-links").remove();

                        let htmlObject = $('.header-nav-pannel .right-top-header-links').prepend(result);
                        $('.right-top-header-links').addClass("ajax-updated");
                        var windowsize = $(window).width();
                        if(windowsize > 1023) {
                            if ($("body").hasClass("catalog-category-view") == true && $(".sidebar").hasClass("sidebar-main")) {
                                if($(".right-top-header-links").hasClass("ajax-updated") && $(".sidebar-main").hasClass("ajax-updated") && $(".navigation").hasClass("ajax-updated")) {
                                    $('.loading-mask-ajax-menu').hide();
                                }
                            }else{
                                if($(".right-top-header-links").hasClass("ajax-updated") &&  $(".navigation").hasClass("ajax-updated")) {
                                    $('.loading-mask-ajax-menu').hide();
                                }
                            }
                        }else {
                            if($(".navigation").hasClass("ajax-updated")) {
                                $('.loading-mask-ajax-menu').hide();
                            }
                        }

                        let customerName = htmlObject.find('.commercial-login-msg .logged-in').attr("data-text");
                        htmlObject.find('.commercial-login-msg .logged-in').text(customerName);
                    }
                });
            }
        });
    }
});
