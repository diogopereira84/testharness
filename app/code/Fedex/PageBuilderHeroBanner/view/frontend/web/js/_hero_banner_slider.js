/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require(['jquery'], function($) {
    $(window).on('load', function () {
       $(document).on('keydown', function(e) {
            let keycode = (e.keyCode ? e.keyCode : e.key);
            if(keycode === 9) {
                $('.retail-home-banner-container .slick-track .slick-active').find('a').attr('tabindex',-1);
            }
        });
        // ADA issue for 320 breakpoint
        $('.mobile-pagebuilder-slide-button').on('click keypress',  function(e) {
            if (parseInt($(window).width()) >= 320 && parseInt($(window).width()) <= 767 ) {
                let url = $('.retail-home-banner-container .slick-track .slick-active').find('a').attr('href');
                let keycode = (e.keyCode ? e.keyCode : e.key);
                if((keycode === 32) || (keycode === 13)) {
                    window.location = url;
                }
                window.location = url;
            }
        });
    });

    let heroBannerDesktopMessageClass = ".hero-banner-desktop-message";
    let heroBannerLaptopMessageClass = ".hero-banner-laptop-message";
    let heroBannerTabletMessageClass = ".hero-banner-tablet-message";
    let heroBannerMobileMessageClass = ".hero-banner-mobile-message";

    /**
     * Set slider text based on current window width
     */
    $(window).on('load resize', function () {
        let windowWidth = $(window).width();
        $(heroBannerLaptopMessageClass).hide();
        $(heroBannerTabletMessageClass).hide();
        $(heroBannerMobileMessageClass).hide();
        $(".hero-banner-pagebuilder-content").show();
        $(".mobile-hero-banner-content").show();
        if(windowWidth >= 1440) {
            setDesktopSliderText();
        }else if(windowWidth >= 1200 && windowWidth < 1440) {
            setLaptopSliderText();
        }else if(windowWidth >= 768 && windowWidth < 1200) {
            setTabletSliderText();
        }else if(windowWidth < 768) {
            setMobileSliderText();
        }
    });

    /**
     * Set desktop slider text
     *
     * @return void
     */
    function setDesktopSliderText() {
        $(heroBannerDesktopMessageClass).show();
        $(heroBannerLaptopMessageClass).hide();
        $(heroBannerTabletMessageClass).hide();
        $(heroBannerMobileMessageClass).hide();
    }

    /**
     * Set laptop slider text
     *
     * @return void
     */
    function setLaptopSliderText() {
        $(heroBannerLaptopMessageClass).each(function() {
            let _this = this;
            if($(_this).text().trim() != '') {
                $(_this).show();
                $(_this).prev(heroBannerDesktopMessageClass).hide();
            } else {
                $(_this).prev(heroBannerDesktopMessageClass).show();
            }
        });
    }

    /**
     * Set tablet slider text
     *
     * @return void
     */
    function setTabletSliderText() {
        $(heroBannerTabletMessageClass).each(function() {
            let _this = this;
            if($(_this).text().trim() != '') {
                $(_this).show();
                $(_this).prev(heroBannerLaptopMessageClass).hide();
                $(_this).prev(heroBannerLaptopMessageClass).prev(heroBannerDesktopMessageClass).hide();
            } else if ($(_this).prev(heroBannerLaptopMessageClass).text().trim() != '') {
                $(_this).hide();
                $(_this).prev(heroBannerLaptopMessageClass).show();
                $(_this).prev(heroBannerLaptopMessageClass).prev(heroBannerDesktopMessageClass).hide();
            } else {
                $(_this).hide();
                $(_this).prev(heroBannerLaptopMessageClass).hide();
                $(_this).prev(heroBannerLaptopMessageClass).prev(heroBannerDesktopMessageClass).show();
            }
        });
    }

    /**
     * Set mobile slider text
     *
     * @return void
     */
    function setMobileSliderText() {
        $(heroBannerMobileMessageClass).each(function() {
            let _this = this;
            if($(_this).text().trim() != '') {
                $(_this).show();
                $(_this).prev(heroBannerTabletMessageClass).hide();
                $(_this).prev(heroBannerTabletMessageClass).prev(heroBannerLaptopMessageClass).hide();
                $(_this).prev(heroBannerTabletMessageClass).prev(heroBannerLaptopMessageClass).prev(heroBannerDesktopMessageClass).hide();
            } else if ($(_this).prev(heroBannerTabletMessageClass).text().trim() != '') {
                $(_this).hide();
                $(_this).prev(heroBannerTabletMessageClass).show();
                $(_this).prev(heroBannerTabletMessageClass).prev(heroBannerLaptopMessageClass).hide();
                $(_this).prev(heroBannerTabletMessageClass).prev(heroBannerLaptopMessageClass).prev(heroBannerDesktopMessageClass).hide();
            } else if ($(_this).prev(heroBannerTabletMessageClass).prev(heroBannerLaptopMessageClass).text().trim() != '') {
                $(_this).hide();
                $(_this).prev(heroBannerTabletMessageClass).hide();
                $(_this).prev(heroBannerTabletMessageClass).prev(heroBannerLaptopMessageClass).show();
                $(_this).prev(heroBannerTabletMessageClass).prev(heroBannerLaptopMessageClass).prev(heroBannerDesktopMessageClass).hide();
            } else {
                $(_this).hide();
                $(_this).prev(heroBannerTabletMessageClass).hide();
                $(_this).prev(heroBannerTabletMessageClass).prev(heroBannerLaptopMessageClass).hide();
                $(_this).prev(heroBannerTabletMessageClass).prev(heroBannerLaptopMessageClass).prev(heroBannerDesktopMessageClass).show();
            }
        });
    }
});
