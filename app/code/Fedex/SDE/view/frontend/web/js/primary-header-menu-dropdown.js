/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 require(['jquery'], function($){
    $(".category-tree .active > ul").addClass("left-sidebar-dropdown");
    $(document).ready( function() {
      let categoryTreeExpandElement = $('.category-tree .expend-class');
      let  leftSideBarElement = $('.left-sidebar-dropdown');

      $(document).on('click' , '.sde-customer-dropdown', function(e){
          $(".sde-customer-menu").toggle();
          e.stopPropagation();
      });
  
      $('.sde-customer-dropdown').on('keypress',function(e) {
        if(e.which == 13) {
          $(".sde-customer-menu").toggle();
          e.stopPropagation();
        }
      });
      
      $(document).on('click', function(){
          $(".sde-customer-menu").hide();
      });

      categoryTreeExpandElement.on('keypress',function(e) {
        if(e.which == 13) {
          categoryTreeExpandElement.parents('li').removeClass('active');
          leftSideBarElement.css("display","none");
        }
      });
      if(!categoryTreeExpandElement.parents('li').hasClass('active')) {
        categoryTreeExpandElement.on('keypress',function(e) {
          if(e.which == 13) {
            categoryTreeExpandElement.parents('li').addClass('active');
            leftSideBarElement.css("display","block");
          }
        });
      }

      /* B-1197886 - RT-ECVS - Validate all new components follow ADA compliance - CATALOG & UPLOAD screen*/
      $(document).ready(function(){ 
          setTimeout(() => {
            $('.cms-sde-home .nav-sections .navigation #ui-id-2').removeAttr("tabindex");  
            $('.cms-sde-home .nav-sections .navigation #ui-id-2 .ui-menu-item').prop("tabindex","0");   
            $('.cms-sde-home .nav-sections .navigation .ui-corner-all').removeAttr("aria-activedescendant"); 
            $('.cms-sde-home .nav-sections .navigation .ui-corner-all').removeAttr("role"); 
            $('.cms-sde-home .nav-sections .navigation #ui-id-2 .ui-menu-item').removeAttr("role"); 
            $('.cms-sde-home .page-header .header-top .retail-minicart .ui-widget-content').removeAttr('id');   
            $('.cms-sde-home .page-header .header-top .retail-minicart .ui-widget-content > div').removeAttr('id');  
            // B-1219179 - ADA Print Products Page
            $('.cms-sde-home .sidebar-main .category-tree .expend-class').prop("tabindex","0");     
          }, 4000);
      });
  
    });
  });
