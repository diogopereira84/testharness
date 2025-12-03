/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 require(['jquery'], function($){
    $(document).ready( function() {
          $(document).on('click' , '.commercial-customer-dropdown', function(e){
          $(".commercial-customer-menu").toggle();
          e.stopPropagation();
      });

      /* B-1294466-My Profile : Remove the preferences and Fix My accounts  Account */
      $('.cms-sde-home .sidebar.sidebar-main ul.nav.items li').each(function() {
        if ($(this).find('a').length > 0 && $(this).find('a').attr('href').includes('customer/account/preferences') && !$('body').hasClass('selfreg-store-fcl')) {
            $(this).find('a').remove();
        }
      });

      $('.cms-sde-home .nav-sections .navigation .ui-corner-all .level0 .level-top').each(function(){
        $(this).on('keydown',function(e) {
            if(e.keyCode == 40) {
                $('.cms-sde-home .nav-sections .navigation .level0 .submenu').toggle();
                e.stopPropagation();
            }
        });
      });

      $('.commercial-customer-dropdown').on('keypress',function(e) {
        if(e.which == 13) {
          $(".commercial-customer-menu").toggle();
          e.stopPropagation();
        }
      });
      /* D-96973 RT-ECVS-Secondary Hearder  defect in Tablet view for epro not able to click on print product and browse catalog*/
        $('.commercial-epro-store #ui-id-2 .category-item a').on('click', function(){
          if (parseInt($(window).width()) > 751 && parseInt($(window).width()) < 800 ) {
          window.location.href = $(this).data("href");
          return;
          }
        });

      $(document).on('click', function(){
          $(".commercial-customer-menu").hide();

          /** D-143262 - JS Error: JQMIGRATE: JQuery.fn.click() event shorthand is deprecated */
          $('.toast-msg-container').css('display', 'none');
      });

            /* B-1197886 - RT-ECVS - Validate all new components follow ADA compliance - CATALOG & UPLOAD screen*/
      $(document).ready(function(){
          setTimeout(() => {
            if (!$(".commercial-epro-store").hasClass("catalog-mvp-customer-admin")) {
              $('.commercial-store-home .nav-sections .navigation #ui-id-2').removeAttr("tabindex");
              $('.commercial-store-home .nav-sections .navigation #ui-id-2 .ui-menu-item').prop("tabindex","0");
              $('.commercial-store-home .nav-sections .navigation #ui-id-2 .ui-menu-item').removeAttr("role");
            }
            $('.commercial-store-home .nav-sections .navigation .ui-corner-all').removeAttr("aria-activedescendant");
            $('.commercial-store-home .nav-sections .navigation .ui-corner-all').removeAttr("role");
            $('.commercial-store-home .page-header .header-top .retail-minicart .ui-widget-content').removeAttr('id');
            $('.commercial-store-home .page-header .header-top .retail-minicart .ui-widget-content > div').removeAttr('id');
          }, 4000);
      });
      /* D-104271 - ADA Issues fix for SDE */
        $(document).ready(function(){
          setTimeout(() => {
            // D-115251 - Issue with ADA in Minicart
            $('.cms-sde-home .header-top .retail-minicart .minicart-wrapper .ui-dialog.mage-dropdown-dialog').attr("title","ui-content");
            $('.cms-sde-home .nav-sections .navigation .ui-corner-all .level0').removeAttr("tabindex");
            $('.cms-sde-home .nav-sections .navigation .ui-corner-all .level0 .level-top').removeAttr("tabindex");
            $('.cms-sde-home .nav-sections .navigation .ui-corner-all .level0 .level-top').prop("tabindex","0");
            $('.cms-sde-home .nav-sections .navigation .level0 .submenu >li').removeAttr("tabindex");
            $('.cms-sde-home .nav-sections .navigation .level0 .submenu >li >a').removeAttr("tabindex");
            $('.cms-sde-home .nav-sections .navigation #ui-id-2').removeAttr("role");
            $('.cms-sde-home .nav-sections .navigation #ui-id-2').removeAttr("aria-activedescendant");
            $('.cms-sde-home .nav-sections .navigation #ui-id-3').removeAttr("role");
            $('.cms-sde-home .nav-sections .navigation #ui-id-2 .ui-menu-item').removeAttr("role");
            $('.cms-sde-home .ui-corner-all').attr("aria-label", 'minicart');
          }, 4000);
      });
      /* B-1405455-Fix ADA issues for self-reg header */
      $(document).ready(function(){
        setTimeout(() => {
          $('.commercial-epro-store .nav-sections .navigation .ui-corner-all .level0').removeAttr("tabindex");
          $('.commercial-epro-store .nav-sections .navigation .ui-corner-all .level0 .level-top').removeAttr("tabindex").prop("tabindex","0");
          $('.commercial-epro-store .nav-sections .navigation .level0 .submenu >li').removeAttr("tabindex");
          $('.commercial-epro-store .nav-sections .navigation .level0 .submenu >li >a').removeAttr("tabindex");
          if (!$(".commercial-epro-store").hasClass("catalog-mvp-customer-admin"))
          {
              $('.commercial-epro-store .nav-sections .navigation #ui-id-2').removeAttr("role");
              $('.commercial-epro-store .nav-sections .navigation #ui-id-2').removeAttr("aria-activedescendant");
              $('.commercial-epro-store .nav-sections .navigation #ui-id-2 .ui-menu-item').removeAttr("role");
          }

          // D-160816 - Remove negative tabindex from header top-level links
          var $levelTopMenu = $('.commercial-store-home .nav-sections .navigation a.level-top');

          if($levelTopMenu.length) {
              $levelTopMenu.removeAttr("role");
              $levelTopMenu.removeAttr("tabindex");
              $levelTopMenu.find('.ui-menu-item').removeAttr("role");
          }
          $('.commercial-epro-store .nav-sections .navigation #ui-id-3').removeAttr("role");
          $('.commercial-epro-store .nav-sections .navigation #ui-id-7').removeAttr("role");
          $('.commercial-epro-store .nav-sections .navigation #ui-id-2 .ui-menu-item #ui-id-13').removeAttr("role");
          $('.commercial-epro-store .nav-sections .section-items .header .customer-welcome .customer-menu .header a').removeAttr("id");
	        $('.commercial-epro-store .ui-corner-all').attr("aria-label", 'minicart');
          $('.commercial-store-home .nav-sections .section-items').attr("aria-busy", 'true');
          $('.commercial-store-home .nav-sections .section-items .nav-sections-item-content .navigation ul.ui-widget-content').attr("aria-busy", 'true');
          $('.commercial-store-home .nav-sections .section-items .nav-sections-item-content .navigation ul.ui-widget-content li.ui-menu-item').attr("role", 'presentation');
        }, 1000);
    });

      $(document).ready(function(){
        $(document).ajaxStop(function() {
            $('.company-users-index .nav-sections .navigation #ui-id-2').attr("role","list");
            $('.company-users-index .nav-sections .navigation .level-top .ui-menu-item-wrapper').removeAttr("aria-haspopup");
            $('.company-users-index .nav-sections .navigation .level-top .ui-menu-item-wrapper').removeAttr("role");
            $('.company-users-index .page-header .header-top .retail-minicart .ui-dialog .block-minicart .block-content').removeAttr('id');
            $('.company-users-index .nav-sections .navigation .ui-corner-all .level0').removeAttr("tabindex");
            $('.company-users-index .nav-sections .navigation .ui-corner-all .level0 .level-top').removeAttr("tabindex");
            $('.company-users-index .nav-sections .navigation .ui-corner-all .level0 .level-top').prop("tabindex","0");
            $('.company-users-index .nav-sections .navigation .level0 .submenu >li').removeAttr("tabindex");
            $('.company-users-index .nav-sections .navigation .level0 .submenu >li >a').removeAttr("tabindex");
            $('.company-users-index .nav-sections .navigation #ui-id-2').removeAttr("role");
            $('.company-users-index .nav-sections .navigation #ui-id-3').removeAttr("role");
            $('.company-users-index .nav-sections .navigation #ui-id-2 .ui-menu-item').removeAttr("role");

            if($(window).width() < 639){
                if(!$( "body" ).hasClass( "update_roles_and_permission" )){
                  $('.data-grid.data.table tr').find('th:last-child, td:last-child').attr('data-th','');
                }
                $('.data-grid.data.table tr').find('td:eq(2) > div').css('margin-left','58px');
                $('.data-grid.data.table tr').find('td:eq(3) > div').css('margin-left','45px');
          }else if($(window).width() > 639){
            $('.data-grid.data.table tr').find('td:eq(2) > div').css('margin-left','');
            $('.data-grid.data.table tr').find('td:eq(3) > div').css('margin-left','');
          }
        });

        myInterval = setInterval(function(){
          if($( "body" ).hasClass( "company-users-index" )){
          $('.company-users-index .nav-sections .navigation .ui-widget-content').removeAttr("aria-activedescendant");
        }
        }, 1000);

        setTimeout(() => {
          clearInterval(myInterval);
        }, 30000);

        $(window).on('resize', function(){
          if($(window).width() < 639){
            if(!$( "body" ).hasClass( "update_roles_and_permission" )){
              $('.data-grid.data.table tr').find('th:last-child, td:last-child').attr('data-th','');
            }
            $('.data-grid.data.table tr').find('td:eq(2) > div').css('margin-left','58px');
            $('.data-grid.data.table tr').find('td:eq(3) > div').css('margin-left','45px');
        } else if($(window).width() > 639){
          $('.data-grid.data.table tr').find('td:eq(2) > div').css('margin-left','');
          $('.data-grid.data.table tr').find('td:eq(3) > div').css('margin-left','');
        }
        });
      });

      responsiveDeviceCustomerProfile();
      $(window).on('resize', function(){
        responsiveDeviceCustomerProfile();
      });
    });

    function responsiveDeviceCustomerProfile() {
      if($(window).width() < 1024 ){
        var element;
        if($( ".header-nav-pannel" ).children('div').hasClass('right-top-header-links')){
          element = $( ".header-nav-pannel" ).html();
        }
        if(!$("body").hasClass('catalog-mvp-break-points') && !$('body').hasClass('cms-sde-home')){
           $('.header.content .right-top-header-links').remove();
        }
        if ($('body').hasClass('cms-sde-home')) {
            $(element).insertAfter( $( "#toggle-menu" ));
        } else {
          if(!$("body").hasClass('catalog-mvp-break-points')){
          $('.header-nav-pannel .right-top-header-links').removeClass('right-top-header-links');
          }
          setTimeout(function() {
            if($("body").hasClass('catalog-mvp-break-points') && !$('body').hasClass('cms-sde-home')){
                 if(!$(".header.content .right-top-header-links.ajax-updated .commercial-right-header-links").length){
                     element = $('.header-nav-pannel .right-top-header-links').detach();
                     $(element).insertAfter( $( "#toggle-menu" ));
                }} else {
                  $(element).insertAfter( $( "#toggle-menu" ));
                }
          }, 10000);
        }
        $('.commercial-store-home .header .right-top-header-links .header .sde-my-order').removeClass('log-out');
      } else{
          $('.header.content .right-top-header-links').remove();
          if (!$('body').hasClass('cms-sde-home')) {
            $('.header-nav-pannel').children('div').addClass('right-top-header-links');
          }
          $('.commercial-order-history.hrborder.sde-my-order').addClass('log-out');
      }
      return true;
    }
    //B-1420377-Implement header dropdown menu items for mobile view
    $(document).ready( function() {
      $(".commercial-store-home .nav-sections .navigation .level0 .submenu .category-item.parent >a").append("<i class='mega-menu-icon-mobile'></i>");
      $('.commercial-store-home .nav-sections .navigation .level0 .submenu .level1.parent a i.mega-menu-icon-mobile').on('click', function(event){
        var link = $(this);
        $('.commercial-store-home .nav-sections .navigation .level0 i.mega-menu-icon-mobile').not($(this).closest('li.level1').find('.mega-menu-icon-mobile')).removeClass('active');
        if(link.hasClass('active')){
          link.removeClass("active");
          var closest_li = link.closest('li');
          closest_li.removeClass("link-active");
          var closest_ul = closest_li.children('ul');
          closest_ul.slideUp(700);
        }
        else {
          link.addClass("active");
          var closest_li = link.closest('li');
          closest_li.addClass('link-active');
        }
      });
      $('.commercial-store-home .nav-sections .navigation .level0 >  a.level-top').on('click', function(event){
        var link = $(this);
        var closest_li = link.closest('li');
          var closest_ul = closest_li.children('ul');
          closest_ul.slideUp(100);
         if (link.hasClass('ui-state-active')) {
          link.removeClass("ui-state-active");
          $('.commercial-store-home .nav-sections .navigation .level0 i.mega-menu-icon-mobile').removeClass('active');
         }
      });
  });

  $(document).ready(function(){
    setTimeout(() => {
      $('.commercial-epro-store .page-wrapper  header.page-header').attr('aria-label', 'head-main');
      $('.commercial-epro-store .page-wrapper .footer-container').attr('aria-label', 'footer-main');
    }, 1000);
});
});
