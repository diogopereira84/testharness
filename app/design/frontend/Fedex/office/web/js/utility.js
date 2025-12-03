define([
    'jquery', 
    'slick',
    'Fedex_Delivery/js/model/toggles-and-settings'
], function ($, slick, togglesAndSettings) {
    'use strict';

    // Pattern Fly Truncation Default Values
    const DEFAULT_MINIMUM_CHARACTERS = window.LiveSearchParameters?.ellipsisTotalCharacters || 56;
    const DEFAULT_FIRST_PART_NUM_CHARS = window.LiveSearchParameters?.ellipsisStartCharacters || 36;
    const DEFAULT_LAST_PART_NUM_CHARS = window.LiveSearchParameters?.ellipsisEndCharacters || 20;

    return {
        /* Access the user location (lat and long) using HTML5 geo-location detection
        */
        getPosition: function () {
            return new Promise(function(resolve, reject) {
                navigator.geolocation.getCurrentPosition(resolve, reject);
            });
        },

        /* Reverse geo-coding using Google Maps API
        * @coordinates: location coordinates (lat and long)
        */
        reverseGeocode: function (coordinates) {
            var geocoder = new google.maps.Geocoder();
            var latlng = new google.maps.LatLng(coordinates.coords.latitude, coordinates.coords.longitude);
            return new Promise(function(resolve, reject) {
                geocoder.geocode({'latLng': latlng}, function(results, status) {
                    if (status == google.maps.GeocoderStatus.OK && results[0]) {
                        var geocoded_data = {};
                        var address_components= results[0].address_components;
                        for (var i=0; i<address_components.length; i++) {
                            if (address_components[i].types[0] == "administrative_area_level_1") {
                                geocoded_data.state = address_components[i];
                            }
                            if (address_components[i].types[0] == "postal_code") {
                                geocoded_data.zipcode = address_components[i];
                            }
                        }
                        resolve(geocoded_data);
                    } else {
                        reject(new Error('Cannot find the location'));
                    }
                });
            });
        },

        /* Render the Related and Recently Viewed carousels in PDP
        * @element: element selector to convert to carousel
        */
        renderPdpCarousel: function (element) {
          var $element = $(element);
          let isOptimizingProductCards = togglesAndSettings.tigerE424573OptimizingProductCards;

          if ($element.children().length) {
              let slickConfig = isOptimizingProductCards ? {
                  infinite: false,
                  slidesToShow: 4,
                  slidesToScroll: 1,
                  responsive: [
                      {
                          breakpoint: 1200,
                          settings: {
                              slidesToShow: 2,
                              slidesToScroll: 1,
                              arrows: false,
                              dots: true
                          }
                      },
                      {
                          breakpoint: 768,
                          settings: {
                              slidesToShow: 1,
                              slidesToScroll: 1,
                              arrows: false,
                              dots: true
                          }
                      }
                  ]
              } : {
                  infinite: false,
                  slidesToShow: 4,
                  slidesToScroll: 1,
                  responsive: [
                      {
                          breakpoint: 1440,
                          settings: {
                              slidesToShow: 3,
                              slidesToScroll: 1
                          }
                      },
                      {
                          breakpoint: 768,
                          settings: {
                              slidesToShow: 1,
                              slidesToScroll: 1,
                              arrows: true,
                              dots: false
                          }
                      },
                      {
                          breakpoint: 640,
                          settings: {
                              slidesToShow: 1,
                              slidesToScroll: 1,
                              arrows: true,
                              dots: false
                          }
                      }
                  ]
              };

              $element.slick(slickConfig);
          }
      },

        /**
         * Truncate the text and add ellipsis
         * @param {string} text - The text to truncate
         * @param {number} minimumCharacters - The minimum characters to truncate
         * @param {number} firstPartNumChars - The number of characters to show in the first part
         * @param {number} lastPartNumChars - The number of characters to show in the last part
         * @returns {array} - The truncated text and a boolean indicating if the text was truncated
         */

        patternFlyTruncation: function(
            text,
            minimumCharacters = DEFAULT_MINIMUM_CHARACTERS,
            firstPartNumChars = DEFAULT_FIRST_PART_NUM_CHARS,
            lastPartNumChars = DEFAULT_LAST_PART_NUM_CHARS
        ) {
            let isTruncated = false;
            if (text?.length > minimumCharacters) {
                isTruncated = true;
                return [`${text.slice(0, firstPartNumChars)}...${text.slice(-lastPartNumChars)}`, isTruncated];
            }

            return [text, isTruncated];
        },

        addLinkWrapperForProductFigures: function(selector) {
            $(selector).each(function() {
                let figureUrl = $(this).find('> a').attr('href');
                $(this).find('> a').replaceWith(function() {
                    return $('<div>', {
                        class: 'item-img-wrapper',
                        html: $(this).find('img').map(function() {
                            return $(this).prop('outerHTML');
                        }).get().join('')
                    });
                });

                $(this).wrap(
                    '<a ' +
                        'href="' + figureUrl + '"' + 
                        'class="all-unset product-item-wrapper" ' +
                    '</a>'
                );
            });
        }
    }
});
