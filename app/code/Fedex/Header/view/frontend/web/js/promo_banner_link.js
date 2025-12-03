define([
    'jquery'
], function ($) {
    'use strict'

    return function (config, elm) {
        $(document).ready(function() {
            $(document).on('click', '.promo-banner-container', function() {
                var promoBannerUrl = config.promoBannerUrl;
                var promoBannerIsNewTab = config.promoBannerIsNewTab;
                if(promoBannerUrl) {
                    if(promoBannerIsNewTab==1) {
                        window.open(promoBannerUrl, '_blank');
                    } else {
                        window.open(promoBannerUrl, '_self');
                    }
                }
            });
        });
    }
});
