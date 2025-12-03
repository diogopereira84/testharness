require(['jquery', 'slick'], function($) {
    $(window).on('load resize', function () {
      var isMobile = window.matchMedia("only screen and (max-width: 767px)").matches;
      if(isMobile) {
        //Exclude Product items in the Shop by Block from getting rendered as Slick Carousels.
        $('.shop-by-type .block-products-list .product-items').removeAttr("id");
        var elementExist = true;
        if($(".block-products-list #simple-product-grid").length == 0) {
          elementExist = false;
        }

        if (elementExist) {
          $('.block-products-list #simple-product-grid:not(.slick-initialized)').slick({
              dots: true,
              infinite: true,
              variableWidth: false,
              slidesToShow: 2,
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
      }
    });
});
