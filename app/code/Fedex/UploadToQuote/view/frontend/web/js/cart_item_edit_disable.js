/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require([
    'jquery'
], function ($) {
    /**
     * Disable the edit and quantity box for SI items
     */
    $(document).ready(function() {
        $(".si-item-non-editable").attr('disabled', 'disabled');
        $(".si-item-non-editable a.action-edit").attr("disabled", "true").css({'pointer-events':'none','color': '#aaaaaa'});
    });

    /**
     * To show Why can't I edit link in cart summary page
     */
    $(document).on('click', '.upload-to-quote-why-edit-button', function(e) {
        e.preventDefault();
        let isExpanded = $(this).attr('aria-expanded');
        if(isExpanded == "true") { 
            $(this).attr('aria-expanded', 'false');
        } else { 
            $(this).attr('aria-expanded', 'true');
        }
        $(this).toggleClass('icon-arrow');
        $(this).next('.upload-to-quote-edit-button-msg').slideToggle(200);
    });
})
