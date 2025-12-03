/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Fedex_FXOCMConfigurator/js/configuratorscript' // adjust the path as necessary
], function($,configuratorFunction) {
    'use strict';

    return function() {
        $("#iframe_container").css("display", "none");

        //remove iframe once model is closed
        $('#iframe_popup').on('modalclosed', function() {
            $("#iframe_container").css("display", "none");
            $(".modal-inner-wrap").css("width", "1044px");
            jQuery('#iframe_container iframe').remove();
        });

        // Open FxoCm configurator model on click of preview link
        $(document).on('click', '[id^="preview_link_"]', function() {
            var itemSku = $(this).data('index-id');
            var itemId = $(this).data('index');
            var itemConfig = window.itemConfigs[itemId];
            let explorerD190723fix = typeof (window.checkout.d_190723_fix) != 'undefined' && window.checkout.d_190723_fix != null ? window.checkout.d_190723_fix : false;
            if (!itemConfig) {
                console.error('Configuration not found for item:', itemSku);
                console.error('Configuration not found for item id :', itemId);
                return;
            }

            $("#iframe_container").css("display", "block");
            var modalOptions = {
                type: 'popup',
                innerScroll: true,                
                buttons: [],
                modalClass: 'preview-model-class'
            };

            // Open modal popup
            $('#iframe_popup').modal(modalOptions, $('#iframe_popup')).modal('openModal');
            $(".preview-model-class .modal-inner-wrap").css("width", "100%");
            $(".preview-model-class .modal-inner-wrap").css("height", "100%");
            $(".loading-mask").show();

            // Call configurator js with specific config for the item
            configuratorFunction(itemConfig);
            if (explorerD190723fix) {
                setTimeout(() => {
                    $(".loading-mask").hide();
                }, 5000);  
            }
            
        });
        
        $(document).ready(function() {
            jQuery('.preview_btn').on('keypress', function(event) {
                if (event.which === 13) { 
                   jQuery('[id^="preview_link_"]:first').focus();
                }
            });
        });
        
        $(document).on('click', '.preview_btn', function () {
            $('[id^="preview_link_"]:first').focus().css('box-shadow', '0px 0px 3px #006bb4');
        });

        $(document).on('click', '.btn-decline-deny', function () {
            let explorerEproUploadToQuote = typeof (window.checkout.is_u2q_toggle_enabled) != 'undefined' && window.checkout.is_u2q_toggle_enabled != null ? window.checkout.is_u2q_toggle_enabled : false;
            if (explorerEproUploadToQuote) {
                $('.preview_btn').hide();
            }
        });
    };
});

