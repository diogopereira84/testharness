/*global $ */
require(['jquery'], function($) {
    $(document).ready(function() {

        $('.menu > ul > li:has( > ul)').addClass('menu-dropdown-icon');
        //Checks if li has sub (ul) and adds class for toggle icon - just an UI


        $('.menu > ul > li > ul:not(:has(ul))').addClass('normal-sub');
        //Checks if drodown menu's li elements have anothere level (ul), if not the dropdown is shown as regular dropdown, not a mega menu (thanks Luka Kladaric)

        $(".menu > ul").before("<a href=\"#\" class=\"menu-mobile\">Navigation</a>");

        //Adds menu-mobile class (for mobile toggle menu) before the normal menu
        //Mobile menu is hidden if width is more then 959px, but normal menu is displayed
        //Normal menu is hidden if width is below 959px, and jquery adds mobile menu
        //Done this way so it can be used with wordpress without any trouble
        $(".menu > ul > li").on({
            mouseenter: function (e) {
                if ($(window).width() > 767) {
                    var duration = '0.3s';
                    if (window.animation_time) {
                        duration = window.animation_time + 's';
                    }
                    $(this).children("ul").stop(true, false).css({
                        'animation-duration': duration
                    });
                    e.preventDefault();
                }
            },
            mouseleave: function (e) {
                if ($(window).width() > 767) {
                    e.preventDefault();
                }
            }
        });

        $(".menu-vertical-items").on({
            mouseenter: function (e) {
                $('.menu-vertical-items').removeClass('active');
                $('.vertical-subcate-content').removeClass('active');
                $(this).addClass('active');
                $('#' + $(this).data('toggle')).addClass('active');
            }
        });
        //If width is more than 943px dropdowns are displayed on hover

        //If width is less or equal to 943px dropdowns are displayed on click (thanks Aman Jain from stackoverflow)
        $(".menu-mobile").on("click", function(e) {
            $(".menu > ul").toggleClass('show-on-mobile');
            e.preventDefault();
        });
        //when clicked on mobile-menu, normal menu is shown as a list, classic rwd menu story (thanks mwl from stackoverflow)

        /* menu toggle for mobile menu */
        var menuToogle = function() {

            /* Restrict to call code when burger menu is enable */
            var menuLength = document.getElementsByClassName('menu-container').length;
            if($("html").hasClass("md-burger-menu") == false && (menuLength !== 0)) {
                if ($('html').hasClass('nav-open')) {
                    $('html').removeClass('nav-open');
                    setTimeout(function() {
                        $('html').removeClass('nav-before-open');
                    }, 300);
                } else {
                    $('html').addClass('nav-before-open');
                    setTimeout(function() {
                        $('html').addClass('nav-open');
                    }, 42);
                }
            }

        }
        $(document).on("click", ".action.nav-toggle", menuToogle);

        /* Apply has active to parents */
        $('.nav-sections-item-content li.active').each(function() {
            $(this).parents('li').addClass('has-active');
            $(this).addClass('has-active');
        });
        if ($(window).width() >= 768) {
            $('.has-active').parents('.vertical-subcate-content').addClass('active');
            $('.vertical-menu-left li[data-toggle="' + $('.has-active').parents('.vertical-subcate-content').attr('id') + '"]').addClass('active');
            if ($('.menu-vertical-items.active').length >= 1) {
                $('.menu-vertical-items.active').each(function() {
                    $('#' + $(this).data('toggle')).addClass('active');
                });
            }
            if ($('.menu-vertical-wrapper').find('.active').length <= 0) {
                $('.menu-vertical-wrapper').each(function() {
                    $(this).find('.menu-vertical-items:first-child').addClass('active');
                    $('#' + $(this).find('.menu-vertical-items:first-child').data('toggle')).addClass('active');
                });
            }
        }
        /* Apply has active to parents */

        if ($(window).width() <= 767) {
            $('.col-menu-3.vertical-menu-left .menu-vertical-items').each(function() {
                var childDivId = $(this).data('toggle');
                $(this).append($('#' + childDivId).html());
                $('.menu-vertical-items .menu-vertical-child').hide();
            });
        }
    });
});
