/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 define([
    'jquery',
    'Magento_PageBuilder/js/events',
    'jquery-ui-modules/tabs',
    'slick',
], function ($, events) {
    'use strict';

    return function (config, element) {
        let $element = $(element);

        // Ignore stage builder preview tabs
        if ($element.is('.pagebuilder-tabs')) {
            return;
        }

        $(document).on('keydown','.tabs-navigation li.tab-header',function() {
            $(".tabs-navigation li.tab-header").each(function (i) {$(this).attr('tabindex', 0);});
            $(".tabs-content .pagebuilder-button-link").attr('tabindex', 0);
        });
        $(document).on('click','.tabs-navigation li.tab-header',function() {
            $(".tabs-navigation li.tab-header").each(function (i) {$(this).attr('tabindex', 0);});
            $(".tabs-content .pagebuilder-button-link").attr('tabindex', 0);
        });

        // Disambiguate between the mage/tabs component which is loaded randomly depending on requirejs order.
        $.ui.tabs({
            active: $element.data('activeTab') || 0,
            create:

                /**
                 * Adjust the margin bottom of the navigation to correctly display the active tab
                 */
                function () {
                    let i = 1;
                    $element.find("li.tab-header").each(function(){
                        $(".tabs-navigation li.tab-header").each(function (i) {$(this).attr('tabindex', 0);});
                        $(".pagebuilder-column-group .pagebuilder-column .play-overlay a.play-icon").attr('aria-label', 'Video');
                        if(i == 1) {
                            let firstTabId = $(this).attr("aria-controls");
                            $("#" +firstTabId).height('auto');
                        }
                        if ($(this).attr('role') == 'tablist' || $(this).attr('role') == 'presentation') {
                            let $tab = $(this);
                            $tab.attr('role','tab');
                            $tab.find('a.ui-tabs-anchor').removeAttr('role');
                        }
                        i++;
                    });

                    let borderWidth = parseInt($element.find('.tabs-content').css('borderWidth').toString(), 10);

                    $element.find('.tabs-navigation').css('marginBottom', -borderWidth);
                    $element.find('.tabs-navigation li:not(:first-child)').css('marginLeft', -borderWidth);

                    // Start custom implementation
                    let isMobile = window.matchMedia("only screen and (max-width: 767px)").matches;

                    // Biding this customization to punchout and home page
                    const atHomePage = $element.parents('body.cms-index-index').length
                    const atPunchoutPage = $element.parents('.tabbed-display').length

                    if (isMobile && ( atPunchoutPage || atHomePage ) ) {
                        // Override tabs navigation by a custom element which displays the selected tab
                        $element.find("ul.tabs-navigation").hide();
                        $element.prepend("<div class='most-active-content' id='most-active-content'></div>");
                        let selected_value = $element.find("li.tab-header:first-child a .tab-title").text();
                        $element.find(".most-active-content").text(selected_value);

                        $element.find("li.tab-header").on( "click", function() {
                            let currentTabId = $(this).attr("aria-controls");
                            $(this).parent().next('.tabs-content').find('.ui-widget-content').height(0);
                            $("#" +currentTabId).height('auto');

                            let selectval = $element.find("li.tab-header.ui-tabs-active.ui-state-active a .tab-title").text();
                            $element.find(".most-active-content").text("");
                            $element.find(".most-active-content").text(selectval);
                            $element.find("ul.tabs-navigation").hide();
                        });

                        $element.on( "click", "#most-active-content", function() {
                            $element.find("ul.tabs-navigation").toggle();
                        });

                        let pageBuilderColumnLine = $element.find(".pagebuilder-column-line");

                        let sliderElement = null;
                        if (pageBuilderColumnLine.length > 0) {
                            sliderElement = 'div.pagebuilder-column-line';
                        } else {
                            sliderElement = 'div.pagebuilder-column-group';
                        }

                        $element.find(sliderElement).not('.slick-initialized').slick({
                            dots: true,
                            infinite: true,
                            variableWidth: false,
                            loops: false,
                            slidesToShow: 1,
                            nav: false,
                            responsive: [
                                {
                                    breakpoint: 600,
                                    settings: {
                                        slidesToShow: 1,
                                        slidesToScroll: 1
                                    }
                                }
                            ]
                        });
                    }
                    // End custom implementation
                },
            activate:

                /**
                 * Trigger redraw event since new content is being displayed
                 */
                function () {
                    events.trigger('contentType:redrawAfter', {
                        element: element
                    });
                    console.log("activate")
                }
        }, element);
    };
});
