require(['jquery', 'utils', 'slick'], function ($, jsutils) {
    $(window).on('load resize', function () {
        var isMobile = window.matchMedia("only screen and (max-width: 767px)").matches;
        mobileHomeBanner(isMobile);
        if (isMobile) {
            $(".article-block .pagebuilder-column").removeClass("resize-slider");
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
        } else {
            $(".article-block .pagebuilder-column").addClass("resize-slider");
        }
    });
    $(window).on('resize', function () {
        var isMobile = window.matchMedia("only screen and (max-width: 767px)").matches;
        tabDropdown(isMobile);
    });
    function tabDropdown(isMobile) {
        if (isMobile) {
            $(".most-popular-section ul.tabs-navigation").hide();
            $(".most-popular-section #most-active-content").remove();
            $(".most-popular-section #most-active-content-resize").remove();
            $(".most-popular-section .most-popular-tabs").prepend("<div class='most-active-content' id='most-active-content-resize'></div>");
            var selected_value = null;
            if ($(".most-popular-section li.tab-header.ui-tabs-active.ui-state-active a .tab-title").length > 0) {
                selected_value = $(".most-popular-section li.tab-header.ui-tabs-active.ui-state-active a .tab-title").text();
            } else {
                selected_value = $(".most-popular-section li.tab-header:first-child a .tab-title").text();
            }
            $(".most-popular-section #most-active-content-resize").text(selected_value);
            $(".most-popular-section li.tab-header").on("click", function () {
                var selectval = $(".most-popular-section li.tab-header.ui-tabs-active.ui-state-active a .tab-title").text();
                $(".most-popular-section #most-active-content-resize").text("");
                $(".most-popular-section #most-active-content-resize").text(selectval);
                $(".most-popular-section ul.tabs-navigation").hide();
            });
            $(".most-popular-section").off("click").on("click", "#most-active-content-resize", function () {
                $(".most-popular-section ul.tabs-navigation").toggle();
            });
        } else {
            $(".most-popular-section ul.tabs-navigation").show();
            $(".most-popular-section #most-active-content-resize").remove();
        }
    }

    function mobileHomeBanner(isMobile) {
        if (isMobile) {
            var homeBannerMessageHeight = $('body.is-fcl-global .retail-home-banner-container .retail-home-banner-section .pagebuilder-poster-content .message').innerHeight();
            var homeBannerImageHeight = $('.retail-home-banner-container .pagebuilder-banner-wrapper').innerHeight();
            var homeBannerContHeight = parseInt(homeBannerImageHeight) + parseInt(homeBannerMessageHeight);

            $('body.is-fcl-global .retail-home-banner-container .retail-home-banner-section').css("height", homeBannerContHeight + 'px');
        } else {
            $('body.is-fcl-global .retail-home-banner-container .retail-home-banner-section').css("height", 'auto');
        }  
    }
    // B-1582216 - Most popular block price style
    $(document).ready(function() {
        $('.most-popular-products [data-element="description"]').html(function(index, html) {

          if(window.checkout.tiger_display_unit_cost_3p_1p_products_toggle) {
            let newhtml = html.replace(/(\$[0-9]+\.[0-9]{2})/, '<span class="description-price fedex-bold">$1</span>');

            // Make sure if we dont find the each text, we insert it at the end of the html
            // This will only be applicable when a price per unit is displayed
            if (newhtml.includes('class="description-price fedex-box"') && !html.includes("each")) {
              newhtml += " each";
            }

            return newhtml;
          }

          return html.replace(/(\$[0-9]+\.[0-9]{2})/, '<span class="description-price" style="font-family: FedEx Sans Regular;">$1</span>');
        });
        
        if(window.tigerE424573OptimizingProductCards) {
            $('.with-prod-ctlg-standard .most-popular-tabs .product-item-link, .most-popular-tabs figcaption').each(function() {
                const [truncatedText, isTruncated] = jsutils.patternFlyTruncation($(this).text().trim());
                if (isTruncated) {
                    $(this).html(truncatedText).addClass("break-all");
                }
            });

            // Most popular block remove empty columns
            $('.with-prod-ctlg-standard .most-popular-tabs .pagebuilder-column-group .pagebuilder-column-line .pagebuilder-column').each(function(){
                if($(this).find('img').length === 0) {
                    $(this).remove();
                }
            });
        }
    });

    $('.cms-index-index').addClass('cms-retail-home-page');

    $(window).on('load', function() {
        /*
         * Wrap all images in the Homepage "Most Popular" PageBuilder section with a
         * clickable button. This ensures that the entire card—title, description, 
         * and image—is clickable, in accordance with acceptance criteria number 4 
         * from E-424573. 
        **/
        jsutils.addLinkWrapperForProductFigures('.with-prod-ctlg-standard #retail-home-most-popular-product-block figure');
    });
});
