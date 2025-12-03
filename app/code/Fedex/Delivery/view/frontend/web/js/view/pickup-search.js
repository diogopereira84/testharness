define([
    'jquery',
], function($) {
    'use strict';

    /**
     * open auto retail ship and pick popup on checkout page after login and proceed  to checkout
     */
    return  {
        showMoreLocations: function () {
            
            let element = $('.show-more-locations');
            element.off('click');
            element.on('click', function () {
                $('.pickup-location-container.hide-location').each(function (index) {
                    if (index < 5) {
                        $(this).removeClass('hide-location');
                        $('.pickup-location-item-container').scrollTop($('.pickup-location-item-container').height());
                        return true;
                    } 
                    return false; 
                });
            });

            let elementShowMoreLocations = $('.pickup-search-selector');
            elementShowMoreLocations.off('keypress');
            elementShowMoreLocations.on('keypress', function (e) {
                if (e.key == " " ||
                  e.code == "Space" ||      
                  e.keyCode == 32  || e.which == 13    
              ) {
                   e.preventDefault();
                    $(this).trigger('click');
                }
            });

            let elementSelectPickupLocations = $('.custom-radio-btn');
            elementSelectPickupLocations.off('keypress');
            elementSelectPickupLocations.on('keypress', function (e) {
                    if (e.key == " " ||
                    e.code == "Space" ||      
                    e.keyCode == 32  || e.which == 13    
                ) {
                    e.preventDefault();
                        $(this).trigger('click');
                    }
            });
        },

        /**
         * Open & close radius dropdown
         *
         * @returns void
         */
         openCloseRadiusDropdown: function (event) {
            $(event).next().slideToggle(100);
            $(document).on("click", function (e) {
                let containerElement = $('.pickup-search-selector');
                if (!containerElement.is(e.target) && containerElement.has(e.target).length === 0) {
                    $(event).next().slideUp(100);
                }
            });
        },

        /**
         * Select radius option
         *
         * @returns void
         */
         selectRadiusOption: function (event) {
            $(event).parent().parent().slideToggle(100);
            $('select.pickup-search-radius').val($('select.pickup-search-radius option').eq($(event).index()).prop('value')).trigger('change').trigger('click');
            $('span.selected-miles').text($(event).text());
        }
    }
});
