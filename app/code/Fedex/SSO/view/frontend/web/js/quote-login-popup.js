/**
 * Copyright FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/url'
], function ($, url) {
    'use strict';

    return function (config) {
        var form = $('#quote-login-form');
        
        if (form.length) {
            form.on('submit', function (e) {
                e.preventDefault();
                var clickedButton = $(document.activeElement);
                var action = clickedButton.val() || 'login';
                
                // Update the hidden action field
                form.find('#quote-login-action').val(action);
                
                // Submit the form
                form[0].submit();
            });
        }
    };
});
