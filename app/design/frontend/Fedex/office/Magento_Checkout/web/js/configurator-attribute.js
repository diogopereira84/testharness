define(['jquery'], function ($) {
    'use strict';

    // Cache selectors and use event delegation
    $(document).on('click', '.show-detail', function (e) {
        e.preventDefault();

        const $button = $(this);
        const itemId = $button.attr('data-item-id');
        const $details = $('.checkout-cart-index #configurater-product-attr-' + itemId);
        const $label = $('.checkout-cart-index #show-detail-' + itemId);

        // Toggle aria state
        const isExpanded = $button.attr('aria-expanded') === 'true';
        $button.attr('aria-expanded', (!isExpanded).toString());

        // Update text based on visibility
        const isVisible = $details.is(':visible');
        $label.text(isVisible ? 'Show details' : 'Hide details');

        // Toggle classes and visibility
        $button.toggleClass('icon-arrow');
        $details.slideToggle(200);
    });
});
