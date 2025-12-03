define(['jquery'], function ($) {
    'use strict';

    return function (config, elm) {
        window.animation_time = config.animationTime;

        var nav = $('.horizontal-menu');
        if (nav.length) {
            var stickyHeaderTop = nav.offset().top;
            $(window).on('scroll', function () {
                if ($(window).width() >= 768) {
                    if ($(window).scrollTop() > stickyHeaderTop) {

                        if ($('.horizontal-menu .stickymenu').hasClass('vertical-right')) {
                            var outerWidth = $('.section-items.nav-sections-items').width();
                            var innerWidth = $('.menu-container.horizontal-menu').width();
                            var rightMargin = ((outerWidth - innerWidth) / 2) + 'px';
                            $('.horizontal-menu .stickymenu').css({position: 'fixed', top: '0px', right: rightMargin});
                        } else {
                            $('.horizontal-menu .stickymenu').css({position: 'fixed', top: '0px'});
                        }

                        $('.stickyalias').css('display', 'block');
                    } else {
                        $('.horizontal-menu .stickymenu').css({position: 'static', top: '0px'});
                        $('.stickyalias').css('display', 'none');
                    }
                }
            });

            $('.section-item-content .menu-container.horizontal-menu .menu > ul li.dropdown, .section-item-content .menu-container.horizontal-menu .menu > ul > li').each(function (e) {
                $(this).children('a').after('<span class="plus"></span>');
                // Added custom CSS class
                $(this).addClass('custom_mega_menu');
            });

            $('.section-item-content .menu-container.horizontal-menu .menu > ul li.external_link').each(function (e) {
                // For external links
                $(this).find('a').attr('target', '_blank');
            });

            $('.section-item-content .menu-container.horizontal-menu .menu > ul li.dropdown span.plus').on('click', function (e) {
                $(this).siblings('a').toggleClass('active');
                $(this).toggleClass('active').siblings('ul').slideToggle('500');
            });

            // Homepage - category header in mega menu should be clickable on mobile
            $('.nav-sections .section-item-content .menu-container.horizontal-menu .menu > ul li.dropdown a')
                .on('click', function (e) {
                    if (parseInt($(window).width()) >= 320 && parseInt($(window).width()) <= 1023 ) {
                        $(this).next('span.plus').trigger('click');
                    }
                });

            $('.nav-sections .section-item-content .megaStaticBlock .block-new-products-names')
                .on('click', function (e) {
                    if (parseInt($(window).width()) >= 320 && parseInt($(window).width()) < 1024 ) {
                        if ($(this).find('span.active').length > 0 ) {
                            $(this).find('span.plus').removeClass('active');
                        } else {
                            $(this).find('span.plus').addClass('active');
                        }
                    }
                });

            $('.nav-sections .section-item-content .megaStaticBlock .block-new-products-names .block-title span.plus')
                .on('click', function (e) {
                    if (parseInt($(window).width()) >= 320 && parseInt($(window).width()) < 1024 ) {
                        if ($(this).parent().find('span.active').length > 0 ) {
                            $(this).removeClass('active');
                        } else {
                            $(this).addClass('active');
                        }
                    }
                });
            $(window).on('load resize', function () {
                $('.megamenu-improvement-feature .menu-dropdown-icon .pagebuilder-column').each(function(){
                    let outerElem = $(this).find('.block-new-products-names:first');
                    var blockTitle = $(outerElem).find('.block-title');
                    if ($(window).width() >= 1024) {
                        if(!blockTitle.length){
                            $(outerElem).find('.block-content').addClass('block-content-customize');
                        }
                    } else {
                        $(outerElem).find('.block-content').removeClass('block-content-customize');
                    }
                });
            });
        }
    }
});
