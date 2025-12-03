define([
    'jquery',
    'slick',
    'Magento_Ui/js/lib/view/utils/dom-observer'
], function($, slick, $do) {
    return function(config, element) {
        const SLICK_UPDATE_DEBOUNCE_TIME = 250;
        const $element = $(element);
        // Apply slick component
        $do.get('.pagebuilder-column-group', function(elem) {
            let pageBuilderColumnLine = $element.find('.pagebuilder-column-line');
            let sliderElement = null;
            if (pageBuilderColumnLine.length > 0) {
                sliderElement = 'pagebuilder-column-line';
            } else {
                sliderElement = 'pagebuilder-column-group';
            }

            $element.find('.'+sliderElement).not('.slick-initialized').slick({
                dots: false,
                arrows: true,
                infinite: false,
                variableWidth: false,
                loops: false,
                slidesToShow: 3,
                responsive: [
                    {
                        breakpoint: 1200,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 1,
                            dots: true
                        }
                    }
                ]
            });
        });

        // Reposition slick arrow and dots relative to the column image (default is relative to entire column)
        function updateSlickItemsPosition () {
            const slickImageHeight = $element.find('figure').height();
            const newButtonHeight = slickImageHeight / 2;
            const newDotsHeight = slickImageHeight + 10;
    
            $element.find('button.slick-prev').css('top', newButtonHeight);
            $element.find('button.slick-next').css('top', newButtonHeight);
            $element.find('.slick-dots').css('top', newDotsHeight);
        }

        function debounce(func, timeout = 300){
            let timer;
            return (...args) => {
              clearTimeout(timer);
              timer = setTimeout(() => { func.apply(this, args); }, timeout);
            };
        }

        $(window).on('resize', debounce(updateSlickItemsPosition, SLICK_UPDATE_DEBOUNCE_TIME));

        updateSlickItemsPosition();
    };
});
