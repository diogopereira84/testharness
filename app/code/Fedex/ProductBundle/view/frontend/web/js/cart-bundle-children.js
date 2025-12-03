define(['jquery'], function ($) {
    'use strict';

    /**
     * Bundle children toggle component
     * @param {Object} config Configuration containing parentId and childrenSelector
     * @param {HTMLElement} element Toggle button element
     */
    return function bundleChildrenToggle(config, element) {
        const SHOW_TEXT = 'Show products';
        const HIDE_TEXT = 'Hide products';

        $(document).on('click', config.parentSelector, function (event) {
            event.preventDefault();
            event.stopPropagation();

            // Get fresh references to elements using selectors
            const $element = $(config.parentSelector);
            const $children = $(config.childrenSelector);
            const $childrenShowDetail = $(config.childrenShowDetailSelector);
            const $mobileChildrenTitle = $(config.mobileChildrenTitleSelector);
            const $lastchildrenseparator = $(config.lastChildrenSeparatorSelector);

            // Toggle aria state
            const isExpanded = $element.attr('aria-expanded') === 'true';
            const newExpandedState = !isExpanded;

            $element.attr('aria-expanded', String(newExpandedState));
            $element.toggleClass('icon-arrow');

            // Update text based on current visibility state
            const isVisible = $children.is(':visible');
            $element.text(isVisible ? SHOW_TEXT : HIDE_TEXT);

            // Toggle visibility
            if (isVisible) {
                $children.addClass('d-none');
                $lastchildrenseparator.addClass('d-none');

                if ($(window).width() < 768) {
                    $mobileChildrenTitle.addClass('d-none')
                }

                if ($(window).width() >= 768) {
                    $childrenShowDetail.addClass('d-none');
                }
            } else {
                $children.removeClass('d-none');
                $lastchildrenseparator.removeClass('d-none');

                if ($(window).width() < 768) {
                    $mobileChildrenTitle.removeClass('d-none')
                }

                if ($(window).width() >= 768) {
                    $childrenShowDetail.removeClass('d-none');
                }
            }
        })
    };
});
