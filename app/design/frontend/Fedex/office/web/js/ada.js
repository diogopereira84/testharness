/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(["jquery"], function ($) {
    'use strict';
    $(document).ready(function() {
        if($(".top-container").length > 0){
            $(".top-container").attr("role", "region");
        }
        if($('body').hasClass('selfreg-landing-index') == false && $('body').hasClass('selfreg-login-fail') == false){
            $('.wlgn-enable-page-header').attr("role", "banner");
        }
        $('#maincontent').attr("role", "main");

        // ADA for Megamenu starts
        $(".nav-sections").on("keydown", ".md-top-menu-items > .custom_mega_menu", function(e) {
            switch (e.which) {
                case 13: // enter KEY
                    $('.custom_mega_menu.ada-activemenu').removeClass('ada-activemenu');
                    if($(this).children('ul.column1').length) {
                        $(this).addClass('ada-activemenu');
                    } else {
                        $(this).children('a').get(0).trigger("click");
                    }
                    break;

                case 27: // escape KEY
                    $('.custom_mega_menu.ada-activemenu').removeClass('ada-activemenu');
                    $(this).trigger('focus');
                    break;

                case 9: // tab KEY
                    if (e.target.className === "product-item-link") {
                        $('.custom_mega_menu.ada-activemenu').removeClass('ada-activemenu');
                    }
                    break;

                case 38: // up KEY
                    e.preventDefault();
                    var targetClassName = e.target.className,
                        upClosestLi = $(e.currentTarget.ownerDocument.activeElement).closest('li.product-item').prev().find("a"),
                        upClosestTitle = $(e.currentTarget.ownerDocument.activeElement).closest('div.block-new-products-names').find("div.block-title"),
                        upClosestColumn = $(e.currentTarget.ownerDocument.activeElement).closest('div.pagebuilder-column'),
                        upClosestPrevColumn = upClosestColumn.prev(),
                        upClosestFirstColumn = upClosestColumn.first();
                    if (targetClassName == "product-item-link") {
                        if (upClosestLi.length) {
                            upClosestLi.trigger('focus');
                        } else {
                            if (upClosestTitle.length) {
                                upClosestTitle.trigger('focus');
                            } else {
                                $(e.currentTarget.ownerDocument.activeElement).closest('div.block-new-products-names').prev().find("li.product-item").last().find("a").first().trigger('focus');
                            }
                        }
                    } else if (targetClassName == "block-title") {
                        if (upClosestPrevColumn.find("li.product-item").length) {
                            upClosestPrevColumn.find("li.product-item").last().find("a").trigger('focus');
                        } else {
                            upClosestPrevColumn.find("div.block-title").trigger('focus');
                        }
                    } else {
                        upClosestFirstColumn.find("li.product-item").first().find("a").first().trigger('focus');
                    }
                    break;

                case 37: // left KEY
                    e.stopPropagation();
                    var targetClassName = e.target.className,
                        leftClosestColumn = $(e.currentTarget.ownerDocument.activeElement).closest('div.pagebuilder-column'),
                        leftClosestPrevTitle = leftClosestColumn.prev().find("div.block-title"),
                        leftClosestFirstColumn = leftClosestColumn.parent().find('div.pagebuilder-column').first();
                    if($(this).children('ul.column1').is(":visible")) {
                        if (targetClassName == "product-item-link" || targetClassName == "block-title") {
                            if (leftClosestPrevTitle.length) {
                                leftClosestPrevTitle.trigger('focus');
                            } else {
                                leftClosestColumn.prev().find("div.block-content").first().find("a").first().trigger('focus');
                            }
                        } else if (targetClassName == "find-out-more-link") { // last to first column transition
                            if (leftClosestFirstColumn.find("div.block-title").length) {
                                leftClosestFirstColumn.find("div.block-title").trigger('focus');
                            } else {
                                leftClosestFirstColumn.find("li.product-item").first().find("a").first().trigger('focus');
                            }
                        } else {
                            // less possible
                            leftClosestFirstColumn.find("li.product-item").first().find("a").first().trigger('focus');
                        }
                    }
                    break;

                case 40: // down KEY
                    e.preventDefault();
                    var targetClassName = e.target.className;
                    if (targetClassName === "product-item-link") {
                        var downClosestLi = $(e.target).closest('.product-item').next().find("a.product-item-link");
                        if (downClosestLi.length) {
                            downClosestLi.trigger('focus');
                        } else {
                            var nextBlockLink = $(e.target).closest('.block-new-products-names').next().find("a.product-item-link");
                            if(nextBlockLink.length) {
                                nextBlockLink.first().trigger('focus');
                            } else {
                                var downClosestNextColumn = $(e.target).closest('.pagebuilder-column').next();
                                if(downClosestNextColumn.length) {
                                    var downClosestNextColumnTitle = downClosestNextColumn.find(".block-title");
                                    if (downClosestNextColumnTitle.length) {
                                        downClosestNextColumnTitle.trigger('focus');
                                    } else {
                                        downClosestNextColumn.find("a.product-item-link").first().trigger('focus');
                                    }
                                }
                            }
                        }
                    } else if(targetClassName === "block-title") {
                        $(e.target).parent().find("a.product-item-link").first().trigger('focus');
                    } else {
                        var downFirstColumn = $(this).find(".megaStaticBlock .pagebuilder-column").first(),
                            downFirstColumnTitle = downFirstColumn.find(".block-title");
                        if (downFirstColumnTitle.length) {
                            downFirstColumnTitle.trigger('focus');
                        } else {
                            downFirstColumn.find("a.product-item-link").first().trigger('focus');
                        }
                    }
                    break;

                case 39: // right KEY
                    e.stopPropagation();
                    var rightClosestColumn = $(e.target).closest('.pagebuilder-column').next();
                    if (rightClosestColumn.length && rightClosestColumn.find(".block-new-products-names").length) {
                        var rightClosestColumnTitle = rightClosestColumn.find(".block-title");
                        if (rightClosestColumnTitle.length) {
                            rightClosestColumnTitle.trigger('focus');
                        } else {
                            rightClosestColumn.find("a.product-item-link").first().trigger('focus');
                        }
                    } else {
                        rightClosestColumn.parent().find('.pagebuilder-column').last().find(".featured-image-container a").trigger('focus');
                    }
                    break;
            }
        });

        $('.md-top-menu-items > .custom_mega_menu').on('mouseover', function() {
            $('.custom_mega_menu.ada-activemenu').removeClass('ada-activemenu');
        });
    });
});
