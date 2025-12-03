define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'previewImg',
    'fedex/storage'
], function($, customerData, previewImg, fxoStorage) {
    'use strict';

    return function (config, elm) {

        if (window.hawks_d218499_customer_section_load_session_evict_unnecessary_calls_toggle) {
            var customerDataSession = customerData.get('session');
            if (!Object.values(customerDataSession())[0]) {
                customerData.reload(['session']);
            }
        }

        customerData.reload(['customer','cart','messages'], true);

        var exeCount = true;

        $(window).on('resize', function() {
            exeCount = true;
            if ($(window).width() < 1024 && exeCount) {
                subMenuDropDown($);
                exeCount = false;
            } else {
                $("ul.md-top-menu-items").find("ul").css( "display", "");
                $("div.block-new-products-names").find(".block-content").css( "display", "");
            }
        });

        $(window).on('load', function() {
            if (exeCount) {
                subMenuDropDown($);
                exeCount = false;
            }
        });

        function subMenuDropDown($) {
            $('.md-top-menu-items div[data-content-type=column]').each(function(){
                if($(this).find('.block-title').length == 0) {
                    $(this).find('.block-content').show();
                }
            });
            $('.megamenu-primary-menu .menu-container.horizontal-menu .block-title').each(function(){
                $(this).off('click');
                $(this).on('click', function() {
                    if ($(window).width() < 1024 ) {
                        let blocktitle = $(this).parent().next().find('.block-title');
                        if (!blocktitle.length) {
                            $(this).parent().next().find('.block-content').slideToggle();
                        }
                        $(this).next().slideToggle();
                    }
                    if ($(window).width() < 1024 ) {
                        selector = $(this).parent().next().next();
                        triggerer = $(this).parent().next().next();
                        for (i = 1; i < $(this).parent().nextAll().length; i++) {
                            if(!selector.find('.block-title').length){
                                triggerer.find('.block-content').slideToggle();
                                selector = selector.next();
                                triggerer = triggerer.next();
                            }
                        }
                    }
                });
            });
        }

        $(document).ready(function() {
            var element = '.commercial-store-home .page-main .page-title-wrapper #page-title-heading > span';
            var brdElement = '.commercial-store-home .page-wrapper .breadcrumbs > ul li:nth-child(2)';
            /* B-1625415 */
            var brdNewElement = '.commercial-store-home .page-wrapper .breadcrumbs > ul li:nth-child(2)  > a';

            if ($(brdNewElement).text() === 'Browse Catalog') {
                $(brdNewElement).text("Shared Catalog");
            }

            if ($(element).text() === 'Browse Catalog') {
                $(element).text("Shared Catalog");
            }

            if($(brdElement).text().replace(/[\t\n]+/g,' ').trim() === 'Browse Catalog' ){
                $(brdElement).text('Shared Catalog');
            }
            //B-1569527
            var leftnavfirstElement = '.catalog-mvp-customer-admin .columns .sidebar-main .category-tree > ul >li:nth-child(1) >p >a';
            var leftnavsecoundElement = '.catalog-mvp-customer-admin .columns .sidebar-main .category-tree > ul >li:nth-child(2) >p >a';

            if ($(leftnavfirstElement).text() === "Print Products") {
                $(".catalog-mvp-customer-admin .columns .sidebar-main .category-tree > ul >li:nth-child(1)").addClass("print_products_left_nav").removeClass("shared_catalog_left_nav");
            }
            else {
                $(".catalog-mvp-customer-admin .columns .sidebar-main .category-tree > ul >li:nth-child(1)").addClass("shared_catalog_left_nav").removeClass("print_products_left_nav");
            }
            if ($(leftnavsecoundElement).text() === "Shared Catalog") {
                $(".catalog-mvp-customer-admin .columns .sidebar-main .category-tree > ul >li:nth-child(2)").addClass("shared_catalog_left_nav").removeClass("print_products_left_nav");
            }
            else {
                $(".catalog-mvp-customer-admin .columns .sidebar-main .category-tree > ul >li:nth-child(2)").addClass("print_products_left_nav").removeClass("shared_catalog_left_nav");
            }
            $(".catalog-mvp-customer-admin .columns .sidebar-main .category-tree > ul >li").prepend("<div class='left-nav-parent-icon'></div>");
        });

        $(document).ready(function() {
            let successUrl;
            if(window.e383157Toggle){
                successUrl = fxoStorage.get("successUrl");
            }else{
                successUrl = localStorage.getItem("successUrl");
            }
            if (successUrl) {
                if (successUrl != window.location.href && !window.location.pathname.includes('nuance.html')) {
                    if(window.e383157Toggle){
                        var podData = fxoStorage.get('pod-data');
                        fxoStorage.clearAll();
                        fxoStorage.set('pod-data',podData);
                    }else{
                        localStorage.clear();
                    }
                }
            }
        });
    }
});
