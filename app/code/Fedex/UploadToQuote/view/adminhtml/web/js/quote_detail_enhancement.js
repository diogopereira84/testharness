/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require([
    'jquery'
], function ($) {
    if (window.isEnhancementToggleEnabled) {
        $(document).on('click', '.instruction-header', function() {
            var target = $(this).closest('.instruction-item').find('.instruction-details');
            if (target.hasClass('collapse')) {
                target.removeClass('collapse').addClass('show');
            } else {
                target.removeClass('show').addClass('collapse');
            }
            $(this).find('.circle-arrow').toggleClass('expanded');
        });
    } else {
        $('#grid_tab_new_special_instructions').closest('li').hide();
        $('#specialInstructions').hide();
    }

})
