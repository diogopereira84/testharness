define(['jquery', 'underscore', 'slick'], function ($, _) {
    'use strict';

    /**
     * Initializes a slick carousel for the bundle recommendation section.
     * @param {Object} config - Configuration object for the carousel.
     * @param {HTMLElement} element - The DOM element to apply the carousel to.
     */
    return function (config, element) {
        let $element = $(element);

        /**
         * Prevent each slick slider from being initialized more than once which could throw an error.
         */
        if ($element.hasClass('slick-initialized')) {
            $element.slick('unslick');
        }

        $element.slick({
            autoplay: false,
            autoplaySpeed: 0,
            fade: false,
            infinite: false,
            slidesToShow: 3,
            slidesToScroll: 1,
            arrows: true,
            dots: false,
            responsive: [
                {
                    breakpoint: 769,
                    settings: {
                        slidesToShow: 2,
                    }
                },
                {
                    breakpoint: 376,
                    settings: {
                        slidesToShow: 1,
                    }
                }
            ]
        });

        /**
         * Use ResizeObserver to detect changes in the size of the carousel element and adjust the layout accordingly.
         * Fallback to window resize event if ResizeObserver is not supported.
         */
        let lazyLayout = _.debounce(() => {
            $element.slick('setPosition');
        }, 300);

        const resizeObserver = new ResizeObserver(lazyLayout);

        resizeObserver.observe(element);

        if (!window.ResizeObserver) {
            console.warn('ResizeObserver not supported, using window resize fallback');
            $(window).resize(lazyLayout);
        }
    };
});
