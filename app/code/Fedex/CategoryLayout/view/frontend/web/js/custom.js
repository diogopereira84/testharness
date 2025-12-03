require(['jquery', 'utils', 'slick'], function ($, jsutils) {
    $(".most-popular-tabs li.tab-header").each(function () {
        $(this).on('click', function () {
            let mostPopularSliderTabId = $(this).attr("aria-controls");
            let pageBuilderColumnLine = $("#" +mostPopularSliderTabId +" div.pagebuilder-column-line");

            let sliderElement = null;
            if (pageBuilderColumnLine.length > 0) {
                sliderElement = ' div.pagebuilder-column-line';
            } else {
                sliderElement = ' div.pagebuilder-column-group';
            }

            let isTab = window.matchMedia("only screen and (min-width: 768px) and (max-width: 1023px)").matches;
            let columcounts = $("#" +mostPopularSliderTabId + sliderElement +":first > .pagebuilder-column").length;

            // Do not apply slick if product cards are optimized
            if(window.tigerE424573OptimizingProductCards) {
                return;
            }

            if (columcounts > 4 || isTab) {
                $("#" +mostPopularSliderTabId + sliderElement +":first").not(".slick-initialized").slick({
                    dots: false,
                    infinite: true,
                    speed: 300,
                    slidesToShow: 4,
                    slidesToScroll: 1,
                    autoplay: false,
                    nav: true,
                    responsive: [
                        {
                            breakpoint: 1024,
                            settings: {
                                slidesToShow: 4,
                                slidesToScroll: 1,
                            },
                        },
                        {
                            breakpoint: 1023,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 1,
                            },
                        },
                        {
                            breakpoint: 768,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 1
                            }
                        },
                        {
                            breakpoint: 767,
                            settings: {
                                slidesToShow: 1,
                                slidesToScroll: 1,
                                dots: true,
                                nav: false,
                                speed: 300,
                                infinite: true,
                                autoplay: false,
                                init: function () {
                                    $("#" +mostPopularSliderTabId + sliderElement +":first").find(".slick-initialized").slick({ settings: "unslick" });
                                }
                            }
                        },
                        {
                            breakpoint: 600,
                            settings: {
                                slidesToShow: 1,
                                slidesToScroll: 1,
                                dots: true,
                                init: function () {
                                    $("#" +mostPopularSliderTabId + sliderElement +":first").find(".slick-initialized").slick({ settings: "unslick" });
                                },
                            },
                        },
                    ],
                });
            } else {
                $("#" +mostPopularSliderTabId + sliderElement +":first").not(".slick-initialized").slick({
                    dots: true,
                    infinite: true,
                    loops: false,
                    slidesToShow: 1,
                    responsive: [
                        {
                            breakpoint: 3500,
                            settings: "unslick",
                        },
                        {
                            breakpoint: 767,
                            settings: {
                                slidesToShow: 1,
                                slidesToScroll: 1,
                                dots: true,
                                nav: false,
                                speed: 300,
                                infinite: true,
                                autoplay: false,
                                init: function () {
                                    $("#" +mostPopularSliderTabId + sliderElement +":first").find(".slick-initialized").slick({ settings: "unslick" });
                                },
                            },
                        },
                    ],
                });
            }
        });
    });

    $(window).on("resize", function () {
        let isMobile = window.matchMedia("only screen and (max-width: 767px)").matches;
        if (!isMobile) {
            $(".printing-solutions-container .pagebuilder-column").addClass("resize-slider");
            $(".article-block .pagebuilder-column").addClass("resize-slider");
        } else {
            $(".printing-solutions-container .pagebuilder-column").removeClass("resize-slider");
            $(".article-block .pagebuilder-column").removeClass("resize-slider");
        }
    });
    function tabDropdown(isMobile) {
        if (isMobile) {
            let selected_value = null;
            $(".category-most-popular-section ul.tabs-navigation").hide();
            $(".category-most-popular-section .most-popular-tabs").prepend(
                "<div class='most-active-content' id='most-active-content'></div>"
            );
            selected_value = $(".category-most-popular-section li.tab-header:first-child a .tab-title").text();
            $(".category-most-popular-section .most-active-content").text(selected_value);

            $(".category-most-popular-section li.tab-header").on("click", function () {
                let currentTabId = $(this).attr("aria-controls");
                $(this).parent().next('.tabs-content').find('.ui-widget-content').height(0);
                $("#" +currentTabId).height('auto');
                let selectval = $(".category-most-popular-section li.tab-header.ui-tabs-active.ui-state-active a .tab-title").text();
                $(".category-most-popular-section .most-active-content").text("");
                $(".category-most-popular-section .most-active-content").text(selectval);
                $(".category-most-popular-section ul.tabs-navigation").hide();
            });

            $(".category-most-popular-section").on( "click", "#most-active-content", function() {
                $(".category-most-popular-section").find("ul.tabs-navigation").toggle();
            });
        } else {
            $(".category-most-popular-section li.tab-header").on("click", function () {
                $(".category-most-popular-section .most-popular-tabs ul.tabs-navigation").show();
            });
            $(".category-most-popular-section .most-popular-tabs ul.tabs-navigation").show();
            $(".category-most-popular-section #most-active-content").remove();
        }

    }
    let isMobile = window.matchMedia("only screen and (max-width: 767px)").matches;
    tabDropdown(isMobile);
    sliderBlock(isMobile);

    function sliderBlock(isMobile) {
        if (isMobile) {
            const productHighlightDOM = $('.printing-solutions-container');
            let pageBuilderColumnDOM = productHighlightDOM.find('.pagebuilder-column-line');
            if (pageBuilderColumnDOM.length < 1) {
                pageBuilderColumnDOM = productHighlightDOM.find('.pagebuilder-column-group');
            }
            pageBuilderColumnDOM.not('.slick-initialized').slick({
                dots: true,
                infinite: true,
                variableWidth: false,
                loops: false,
                slidesToShow: 3,
                responsive: [
                    {
                        breakpoint: 3500,
                        settings: "unslick"
                    },
                    {
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: 600,
                        settings: {
                            slidesToShow: 1,
                            slidesToScroll: 1
                        }
                    }
                ]
            });
            $(".article-block .pagebuilder-column-line").not('.slick-initialized').slick({
                dots: true,
                infinite: true,
                variableWidth: false,
                loops: false,
                slidesToShow: 3,
                responsive: [
                    {
                        breakpoint: 3500,
                        settings: "unslick"
                    },
                    {
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 1
                        }
                    },
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
        $(".subcategory-container .pagebuilder-column-line").not('.slick-initialized').slick({
            dots: false,
            infinite: true,
            variableWidth: false,
            loops: false,
            slidesToShow: 6,
            responsive: [
                {
                    breakpoint: 1440,
                    settings: {
                        slidesToShow: 5,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 1200,
                    settings: {
                        slidesToShow: 4,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1
                    }
                },
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

    /* Redirect to particular section of FAQ page */
    $(window).on("load",function () {
            if (!window.location.href.includes("faq.html")) return

        $(".faq-category-section ."+window.location.href.split("#")[1]).trigger("click");
    });
    /* End redirect to particular section of FAQ page */

    $(document).ready(function(){
        if(window.tigerE424573OptimizingProductCards) {
            $('.image-slider-block figcaption').each(function() {
                const [truncatedText, isTruncated] = jsutils.patternFlyTruncation($(this).text().trim());
                if (isTruncated) {
                    $(this).html(truncatedText).addClass("break-all");
                }
            });
        }

        jsutils.addLinkWrapperForProductFigures('.with-prod-ctlg-standard.page-layout-allprint-products-full-width .pagebuilder-column figure');

        jsutils.renderPdpCarousel(".image-slider-block .pagebuilder-column-line");
        /* FAQ template expend tab changes */
        $(".faq-content-section .tabs-content h2").each(function() {
            $(this).append('<span class="carrot"> <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="22" height="8" viewBox="0 0 22 8"> <desc>  Created with Sketch.</desc> <g fill="none"> <g style="stroke-width:2;stroke:#333333"> <polyline transform="translate(-1042 -1047)translate(120 541)translate(0 481)translate(931.827869 29.967213)rotate(90)translate(-931.827869 -29.967213)translate(927.827869 21.467213)translate(4 8.915368)scale(-1 -1)rotate(-90)translate(-4 -8.915368)" points="-4 5 4.1 12.8 12 5.1"></polyline> </g> </g> </svg> </span>');
            $(this).on('click', function() {
                $(this).toggleClass("active");
                $(this).next('[data-content-type="text"]').slideToggle();
            });
        });
        /* End FAQ template expend tab changes */

        /* Faq Left Side Navigator Start  */
        $('.faq-template-full-width .faq-category-section').find('h2').first().addClass('active');
        $('.faq-template-full-width .faq-category-section h2').on('click', function () {
            $('.faq-template-full-width .faq-category-section h2').removeClass('active');

            let currentClassName = '.faq-template-full-width .faq-content-section ' + '.' + $(this).attr('class');

            $(this).addClass('active');
            $('.faq-template-full-width .faq-content-section .tab-align-left').removeClass('faq-content-scroll-top');
            $(currentClassName).addClass('faq-content-scroll-top');

            let scrollClassName = '.faq-template-full-width .faq-content-section .faq-content-scroll-top';
            let scrollFocusClassName = scrollClassName + ' a';
            $(scrollFocusClassName).trigger('focus');

            $('html, body').animate({
                scrollTop: $(scrollClassName).offset().top
            }, 1500);
        });
        /* Faq Left Side Navigator End */

        /* ADA  complaint from 1200px to 1400px */
        $(".faq-content-section .tabs-content h2").each(function() {
            let html = $(this).html();
            $(this).html('<button type="button">' + html +'</button>');
        });

        $(".faq-category-section h2").each(function() {
            let html = $(this).html();
            $(this).html('<button type="button">' + html +'</button>');
        });
        /* END ADA  complaint from 1200px to 1400px */
    });

});
