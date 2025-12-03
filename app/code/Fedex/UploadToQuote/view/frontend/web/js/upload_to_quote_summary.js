/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require([
    'jquery',
    'mage/mage'
], function ($) {
    
    /**
     * Trigger sidebar sticky on window resize
     */
     $(window).on('resize', function() {
        triggerSidebarStick();
    });

    
    /**
     * Close sidebar print instruction box on click of close icon
     */
    $(".sidebar-print-instruction-container .sidebar-print-instruction-inner-container .close-icon").on('click', function() {
        $(".sidebar-print-instruction-container").remove();
        triggerSidebarStick();
    });

    /**
     * Sidebar sticky when print instruction appear on sidebar
     */
    $('.cart-summary').mage('sticky', {
        container: '#maincontent',
        spacingTop: -($(".sidebar-print-instruction-container").height() + 38)
    });

    /**
     * Trigger sidebar sticky
     * 
     * @return void
     */
    function triggerSidebarStick() {
        let sidebarPrintContainerHeight = 0;
        if ($(".sidebar-print-instruction-container").length > 0) {
            sidebarPrintContainerHeight = -($(".sidebar-print-instruction-container").height() + 38);
        }
        $('.cart-summary').mage('sticky', {
            container: '#maincontent',
            spacingTop: sidebarPrintContainerHeight
        });
    }

    /**
     * ADA for preview msg close icon
     */
     $(document).on('keypress', '.sidebar-print-instruction-inner-container .close-icon', function (e) {
        let keycode = (e.keyCode ? e.keyCode : e.which);
        if(keycode  == 13 || keycode  == 32){
            e.preventDefault();
            $(this).click();
        }
    });

    /**
     * ADA issue for cart page
     */
    $('.upload-to-quote .pagebuilder-mobile-hidden').attr('tabindex',0);

});
