define ([
    'jquery',
    'domReady'
], function ($, domReady, element) {

    return function(config, element) {

        const $element = $(element);
        var resizeTimer;

        $element.find("li.tab-header").each(function(){ 
            var $tabTitleList = $(this);
            var txtTab = $tabTitleList.find("span.tab-title").text(); 
            $tabTitleList.find("a.tab-title").attr("title", txtTab);
        });

        $element.find(".tabs-content > div[data-content-type='tab-item']").each(function(){ 
            var $tabContent = $(this); 
            var tabPanelId = $tabContent.attr("id");
            var btnShopHref = $tabContent.find("div.btn-shopnow > a").attr("href");
            var btnShopText = $tabContent.find("div.btn-shopnow span[data-element='link_text']").text();

            $tabContent.find("div.promo-content-block").attr("id", tabPanelId+'ABC');
            $tabContent.find('.shopnow-product-pricing-container .show-innerbox .show-toggle-link').attr("data-id", tabPanelId);
            $tabContent.find('.shopnow-product-pricing-container .shop-innerbox #btn-pricing-shopnow > a').attr("href", btnShopHref).text(btnShopText);
            
            if($tabContent.find("div.left-column-image").is(':empty')) {
                $tabContent.find("div.left-column-image").addClass("hide");
                $tabContent.find("div.pagebuilder-column").eq(1).css("margin", "0 auto");
            } else {
                var isBannerImages = $tabContent.find("div.left-column-image div.pagebuilder-banner-wrapper").attr("data-background-images");
                if ( isBannerImages == "{}" ) {
                    $tabContent.find("div.left-column-image").addClass("hide");
                    $tabContent.find("div.pagebuilder-column").eq(1).css("margin", "0 auto");
                }
            }

            if ( $tabContent.find("div.promo-content-block > div.promo-content-row").length > 7 ) {
                $tabContent.find("div.promo-content-block > div.promo-content-row:gt(6)").hide();
            } else {
                $tabContent.find(".shopnow-product-pricing-container .show-innerbox").hide();
            }
        });

        $element.find(".shopnow-product-pricing-container .show-innerbox .show-toggle-link").on("click", function(event){
            event.preventDefault();
            var $showToggle = $(this);
            var parentTabId = $showToggle.attr("data-id");
            var $showToggleElement = $element.find(".tabs-content > #"+parentTabId+" div.promo-content-block > div.promo-content-row:gt(6)");

            if($showToggle.text() == "SHOW MORE OPTIONS") { 
                if(parentTabId) {
                    $showToggleElement.show();
                    $showToggle.text("SHOW LESS OPTIONS"); 
                }
            } else {
                $showToggleElement.hide();
                $showToggle.text("SHOW MORE OPTIONS");
            }
        });
        
        if ( $( ".product-pricing-section-block ul.tabs-navigation li.tab-header" ).length > 4 ) {
            var $defaultVisibleTabs = $element.find("li.tab-header:gt(3)");

            $defaultVisibleTabs.hide();
            $element.find("li.tab-header:nth-child(4)").after("<li class='d-inline-block fs-16 text-center pointer mb-0' id='more-tabs-container'><span class='more-label down-arrow'>More</span></li>");
            $element.find("#more-tabs-container").append("<ul id='more-select-container' class='p-0 d-none'></ul>");
            $defaultVisibleTabs.each(function(){
                $element.find("#more-select-container").append("<li class='more-list-items mb-0 p-15'>" + $(this).html() + "</li>");
            });
        }

        $('.product-pricing-section-block').removeClass('hide');
 
        $element.find("#more-tabs-container").on("click", function(){
            var $dropdown = $(this);
            var $dropdownSelectContainer = $dropdown.find("#more-select-container");
            var $dropdownMoreLabel = $dropdown.find(".more-label");

            if ($dropdownSelectContainer.hasClass("d-none")) {
                $dropdownSelectContainer.removeClass('d-none').addClass('d-block');
                $dropdownMoreLabel.removeClass('down-arrow').addClass('up-arrow');
            } else {
                $dropdownSelectContainer.removeClass('d-block').addClass('d-none');
                $dropdownMoreLabel.removeClass('up-arrow').addClass('down-arrow');
            }
        });
        $element.find("#more-select-container > li").on("click", function(){
            var $option = $(this);
            var selOptHtml = $option.html(); 
            var lasVisTab = $element.find("li.tab-header:nth-child(4)").text(); 
            var lasVisTabHref = $element.find("li.tab-header:nth-child(4) > a.tab-title").attr('href'); 
            var aHref = $option.find("a.tab-title").attr('href'); 

            $element.find("li.tab-header:lt(4)").each(function(){
                var $listDeact = $(this);
                if ($listDeact.hasClass("ui-tabs-active")) {
                    $listDeact.removeClass('ui-tabs-active').removeClass('ui-state-active');
                }
            });
            $element.find("li.tab-header:nth-child(4)").html(selOptHtml).addClass('ui-tabs-active ui-state-active more-tabs-active');
            $option.find("span.tab-title").text(lasVisTab);
            $option.find("a.tab-title").attr( { title: lasVisTab, href: lasVisTabHref } );

            $element.find(".tabs-content div.ui-tabs-panel").each(function(){ 
                $(this).css("display", "none");
            });
            $element.find(".tabs-content " + aHref).css("display", "flex");
        });

        $element.find("li.tab-header:lt(4)").on("click", function(event){
            event.preventDefault();
            var $visTabHead = $(this);
            var $moreSelectContainer = $element.find("#more-select-container");

            if ($moreSelectContainer.hasClass("d-block")) {
                $moreSelectContainer.removeClass('d-block').addClass('d-none');
                $element.find(".more-label").removeClass('up-arrow').addClass('down-arrow');
            }
            $element.find("li.tab-header:lt(4)").each(function(){
                var $listOldActive = $(this); 
                if ($listOldActive.hasClass("more-tabs-active")) {
                    $listOldActive.removeClass('ui-tabs-active ui-state-active more-tabs-active');
                }
                $listOldActive.removeClass('ui-tabs-active ui-state-active');
            });
            $element.find(".tabs-content div.ui-tabs-panel").each(function(){
                $(this).css("display", "none");
            });
            var listClickedHref = $visTabHead.find("a.tab-title").attr('href');
            $visTabHead.addClass('ui-tabs-active ui-state-active');
            $element.find(".tabs-content " + listClickedHref).css("display", "flex");
        });

        // to ensure that code evaluates on page load
        domReady(function () { 
            if (window.matchMedia("(max-width: 767px)").matches) { // The viewport is less than 768 pixels wide
                mobileTabsDropdown();
            } else {
                DesktopTabsView();
            }
        });
        
        $(window).on('resize', function(e) {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.matchMedia('only screen and (max-width: 767px)').matches) {
                    mobileTabsDropdown();
                }  else {
                    DesktopTabsView();
                }            
            }, 500);
          
        });
        
        function mobileTabsDropdown() {
            var firstListItem = $element.find("li.tab-header:nth-child(1)").text(); 
            var $mobTabContainer = $("#mobile-tabs-container");

            $(".product-pricing-section-block ul.tabs-navigation").addClass("hide");

            if($("#mobile-tabs-container").length == 0) {
                $(".product-pricing-section-block ul.tabs-navigation").after("<div class='d-block fs-16' id='mobile-tabs-container'><span class='firstoption-label '>"+ firstListItem +"</span></div>");
                $("#mobile-tabs-container").after("<div class='mobile-select-outer'></div>");
                $(".mobile-select-outer").append("<ul id='mobile-select-container' class='d-none'></ul>");
                $(".product-pricing-section-block ul.tabs-navigation li.tab-header").each(function(){
                    $("#mobile-select-container").append("<li class='mobile-list-items mb-0 pb-5 pt-15 pl-15 pr-15'>" + $(this).html() + "</li>");
                });
            } else {
                if ($mobTabContainer.hasClass("d-none")) {
                    $mobTabContainer.removeClass('d-none').addClass('d-block');
                }
            }

            $("#mobile-tabs-container").on("click", function(event){
                event.stopImmediatePropagation();
                var $mobSelectContainer = $("#mobile-select-container");

                if ($mobSelectContainer.hasClass("d-none")) {
                    $mobSelectContainer.removeClass('d-none').addClass('d-block');
                } else {
                    $mobSelectContainer.removeClass('d-block').addClass('d-none');
                }
            });

            $("#mobile-select-container > li").on("click", function(event){
                event.preventDefault();
                var $option = $(this);
                var selOpt = $option.find("span.tab-title").text(); 
                var aHref = $option.find("a.tab-title").attr('href'); 
                var $mobileSelectContainer = $("#mobile-select-container");

                if ($mobileSelectContainer.hasClass("d-block")) {
                    $mobileSelectContainer.removeClass('d-block').addClass('d-none');
                }
                $(".firstoption-label").text(selOpt);
                $(".tabs-content div.ui-tabs-panel").each(function(){ 
                    $(this).css("display", "none");
                });
                $(".tabs-content " + aHref).css("display", "flex");
            });
        }

        function DesktopTabsView() {
            $mobileTabReset =$("#mobile-tabs-container");
            $mobileSelectReset = $("#mobile-select-container");
            
            $(".product-pricing-section-block ul.tabs-navigation").removeClass("hide");
            if ($mobileTabReset.hasClass("d-block")) {
                $mobileTabReset.removeClass('d-block').addClass('d-none');
            }
            if ($mobileSelectReset.hasClass("d-block")) {
                $mobileSelectReset.removeClass('d-block').addClass('d-none');
            }
        }
        
    };
    
});