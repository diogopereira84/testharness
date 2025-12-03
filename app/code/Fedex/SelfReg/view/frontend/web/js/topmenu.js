define(['jquery'], function($){
    'use strict'

    return function (config, elm) {
        var topmenuurl = config.topmenuUrl;
        var isLoaderRemovedEnabled = config.isLoaderRemovedEnabled;
        let showLoader = false;

        $(document).ready(function() {
            if (!isLoaderRemovedEnabled) {
                $('.loading-mask-ajax-menu').show();
                showLoader = true;
            }

            $.ajax({
                url: topmenuurl,
                type: 'get',
                showLoader: showLoader,
                success: function (data){
                    if (data != ''){
                        let htmlObject = $(data).insertBefore(".btn-toggle-search");
                        htmlObject.trigger('contentUpdated');
                        $('.navigation').addClass("ajax-updated");
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
                        }else{
                            if($(".navigation").hasClass("ajax-updated")) {
                                $('.loading-mask-ajax-menu').hide();
                            }
                        }

                        if(windowsize < 1024 && (!$("body").hasClass('catalog-mvp-break-points') || $("body").hasClass('cms-sde-home'))) {
                            $('#retail_cart_mobile').insertAfter("nav");
                        }
                    
                    }
                }
            });
        });
    }
});
