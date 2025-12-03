/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    "jquery"
], function ($) {
    'use strict';

    /**
     * Close success or error message when click on close icon
     */
      $(document).on('click', '.img-cross-icon', function () {
        $(".expiryitem-alertbox").remove();
    });

    /**
     * Trigger expiry and expired item message  when enter or space key is pressed
     */
    $(document).on('keypress', '.img-cross-icon', function (e) {
        let keycode = (e.keyCode ? e.keyCode : e.which);
        if (keycode  == 13 || keycode  == 32) {
            e.preventDefault();
            $(".img-cross-icon").trigger('click');
        }
    });

    /**
     * Close success or error message when click on close icon
     */
    $(document).on('click', '.img-close-icon', function () {
        $(".expireditem-error-box").remove();
    });

    /**
     * Trigger expiry and expired item message  when enter or space key is pressed
     */
    $(document).on('keypress', '.img-close-icon', function (e) {
        let keycode = (e.keyCode ? e.keyCode : e.which);
        if (keycode  == 13 || keycode  == 32) {
            e.preventDefault();
            $(".img-close-icon").trigger('click');
        }
    });
});
