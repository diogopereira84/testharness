require(['jquery', 'slick'], function($) {
  $(window).on('load', function(){
    $('.widget-promo-banner-container figure').each(function() {
      if ($(window).width() <= 767) {
        if($(this).find(".promo-mobilecontent").css("display") === "none"){
          $(this).addClass("hide");
          $(".widget-promo-banner-container .hide").remove();
        }
      } else {
        if($(this).find(".promo-desktopcontent").css("display") === "none"){
          $(this).addClass("hide");
          $(".widget-promo-banner-container .hide").remove();
        }
      }
    });
  });
  $( document ).ready(function() {
    let resizeTimer;
    const $productHighlightCarousels = $('.product-highlight-design-2 .pagebuilder-column-line');
    function setProductHighlightCarousel() {
      if (window.matchMedia("only screen and (max-width: 767px)").matches) {
        if (!$productHighlightCarousels.hasClass('slick-initialized')) {
          $productHighlightCarousels.slick({
            dots: true,
            infinite: true,
            variableWidth: false,
            loops: false,
            slidesToShow: 2,
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
      } else {
        if ($productHighlightCarousels.hasClass('slick-initialized')) {
          $productHighlightCarousels.slick('unslick');
        }
      }
    }
    setProductHighlightCarousel();
    $(window).on('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            setProductHighlightCarousel();
        }, 200);
    });

    $('.widget-promo-banner-container figure').each(function() {
      if ($(window).width() <= 767) {
        if($(this).find(".promo-mobilecontent").css("display") === "none"){
          $(this).addClass("hide");
          $(".widget-promo-banner-container .hide").remove();
        }
      } else {
        if($(this).find(".promo-desktopcontent").css("display") === "none"){
          $(this).addClass("hide");
          $(".widget-promo-banner-container .hide").remove();
        }
      }
    });

    const $promoBannerCarousel = $('.header-slick-slider');
    $promoBannerCarousel.slick({
      dots: false,
      infinite: true,
      speed: 300,
      slidesToShow: 1,
      adaptiveHeight: true
    });
    $promoBannerCarousel.find('.slick-prev').attr('aria-label', 'View Previous promo');
    $promoBannerCarousel.find('.slick-next').attr('aria-label', 'View Next promo');
    $('img[data-element=desktop_image]').attr("alt", "price-tag");

    $('.slick-slider').each(function () {
        const $carousel = $(this);
        // Initial run to disable links in inactive slides
        updateSlideLinks($carousel);
        // On slide change, re-run
        $carousel.on('afterChange', function () {
            updateSlideLinks($carousel);
        });
    });

    function updateSlideLinks($carousel) {
      $carousel.find('.slick-slide').not('.slick-cloned').each(function () {
        const $slide = $(this);
        const $links = $slide.find('a');

        if ($slide.hasClass('slick-active')) {
          // Enable links in active slides
          $links.removeAttr('disabled');
          $links.attr('tabindex', '0'); // optional
        } else {
          // Disable links in inactive slides
          $links.attr('disabled', 'disabled');
          $links.attr('tabindex', '-1');
        }
      });
    }

  });

  let promoBaseUrl = $('#promoBaseUrl').val();
  $(".widget-promo-banner-container .promo-coupon-label").each(function() {
    let couponCode = $(this).next().html();
    let isCartPage = '';
    isCartPage = typeof(window.location.href) !== "undefined" && 
      window.location.href !== null && window.location.href.includes('checkout/cart') ? '&isCartPage=true' : '';
    
    let promoUrl = promoBaseUrl + '?code=' + couponCode;
    promoUrl = promoBaseUrl + '?code=' + couponCode + isCartPage;
    let html = $(this).html();
      let couponClass = $(this).next().attr('class');
      let couponClassExist = couponClass.includes("placeholder-html-code promo-associated-coupon"); 
      if (couponClassExist){
            let dataAnalytics = $(this).next().attr('data-analytics');
            $(this).next().removeAttr('data-analytics');
            let dataAnalyticsTag = "data-analytics";
            if (dataAnalytics != undefined){
                dataAnalyticsTag = 'data-analytics= "'+ dataAnalytics +'"';
            }
            $(this).html('<a class="coupon-link pagebuilder-data-analytics" tabindex="-1" href="' + promoUrl + '"'+dataAnalyticsTag+'>' + html + '</a>');
      }
  });
});
