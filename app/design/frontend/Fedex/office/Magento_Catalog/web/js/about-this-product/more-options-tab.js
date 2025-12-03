define(["jquery", "slick", 'domReady!'], function ($) {
    "use strict";
    $('.product-options-section .product-options-category strong[id]').removeAttr('id');
    var isMobile = window.matchMedia("only screen and (max-width: 1199px)").matches;
        if (!isMobile) {
            $(".inner-content-wrapper.product-options-section").each(
                function () {
                    let $optionsCategory = $(this).find(
                        ".product-options-category"
                    );
                    if ($optionsCategory.length > 1) {
                        $optionsCategory.each(function () {
                            let $optionsRow = $(this).find(
                            ".pagebuilder-column-group"
                            );
                        let total = $optionsRow.length;
                            if (total >= 2) {
                                $optionsRow.not(":first").hide();
                                let $button = $(
                                    "<button class='options-button d-flex flex-start weight-600 border-0 no-underline'>SEE MORE OPTIONS</button>"
                                );
                                $button.data("category", this);
                                $(this).append($button);
                            }
                        });
                    }
                }
            );
            $(document).on("click", ".options-button", function () {
                let $category = $(this).data("category");
                let $optionsRow = $($category).find(
                    ".pagebuilder-column-group:hidden"
                );
                $optionsRow.show();
                $(this).remove();
            });
        }

        if (isMobile) {
            $(document).on("click", '.detail-tabs-head.product-options', function() {

                if($(".product-options-category").hasClass("slick-initialized")) {
                    return;
                }

                $(".pagebuilder-column.product-options-column").unwrap().unwrap();

                $(".pagebuilder-column.product-options-column").each(function() {
                    if($(this).children().length === 0) {
                        $(this).remove();
                    }
                });

                const $element = $(".product-options-category");

                $element.slick({
                    fade: $element.data("fade"),
                    dots: false,
                    nextArrow:
                        '<button class="slick-next" aria-label="Next" type="button">Next</button>',
                    slidesToShow: 4,
                    slidesToScroll: 1,
                    responsive: [
                        {
                            breakpoint: 1024,
                            settings: {
                                slidesToShow: 3,
                                slidesToScroll: 1,
                            },
                        },
                        {
                            breakpoint: 600,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 1,
                            },
                        },
                        {
                            breakpoint: 480,
                            settings: {
                                slidesToShow: 1,
                                slidesToScroll: 1,
                            },
                        },
                    ],
                });4
            });
        }
    });
