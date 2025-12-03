/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/lib/view/utils/dom-observer',
    'uiRegistry'
], function ($, alert, $dom, uiRegistry) {
    'use strict';

    /**
     * init
     * 
     * @returns void
     */
    function init(userPreferenceImportRequestUrl) {

        $(".user-preference-browse-btn").on('click', function () {
            $(".user-preference-browse-file").val('');
            $(".user-preference-browse-file").trigger('click');
        });
        $(".user-preference-browse-file").on("change", function () {
            if (typeof $(this)[0].files[0] !== 'undefined' && $(this)[0].files[0].name && $(this)[0].files[0].name.includes('.csv')) {
                $(".user-preference-input").val($(this)[0].files[0].name);
                $(".user-preference-confirm-btn").show();
            } else {
                handleAlert('wrongExt');
            }
        });
        $(".user-preference-confirm-btn").on('click', function () {
            let browsefileSelector = $('.user-preference-browse-file');
            let compUrlExt = $('input[name="general[company_url_extention]"]').val();
            if (typeof browsefileSelector[0].files[0] !== 'undefined' && browsefileSelector[0].files[0].name && browsefileSelector[0].files[0].name.includes('.csv')) {
                if (compUrlExt) {
                    importDataRequest(userPreferenceImportRequestUrl, browsefileSelector);
                } else {
                    handleAlert('newComp');
                }
            } else {
                handleAlert('emptyFile');
            }
        });
    }

    /**
     * Perform an AJAX request to import user preference fields data
     *
     * @param {string} importRequestUrl - The URL for the import request.
     * @param {string} browsefileSelector - The selector for the file input field.
     */
    function importDataRequest(importRequestUrl, browsefileSelector) {
        // Create a FormData object and append the selected file
        let formData = new FormData();
        formData.append("file", $(browsefileSelector)[0].files[0]);

        // Company Url Extension getting
        let companyUrlExt = $('input[name="general[company_url_extention]"]').val();

        // Show loading mask
        $('.loading-mask').attr('style', '');
        $('body').loader('show');

        // Make an AJAX request for catalog migration
        $.ajax({
            url: `${importRequestUrl}?form_key=${window.FORM_KEY}&isAjax=true&ext_url=${companyUrlExt}`,
            type: 'POST',
            data: formData,
            async: true,
            success: function (data) {
                // Handle the response data
                handleResponse(data);
                $(".user-preference-input").val('');
            },
            error: function (xhr, status, error) {
                // Log the error and handle it
                console.error(error);
                handleError();
            },
            complete: function () {
                // Hide loading mask on completion
                $('body').loader('hide');
            },
            cache: false,
            contentType: false,
            processData: false,
        });
    }

    /**
     * Handle the response data from the AJAX request.
     *
     * @param {object} data - The response data.
     */
    function handleResponse(data) {
        try {
            let title = data.status ? 'Success' : 'Failed';

            // Show an alert with the appropriate title and content
            alert({
                title: $.mage.__(title),
                content: $.mage.__(data.message),
                actions: {
                    always: function () {}
                }
            });
        } catch (err) {
            // Log any exceptions and handle errors
            console.log(err.message);
            handleError();
        }
    }
    /**
     * Handle the response data from the AJAX request.
     *
     * @param {object} data - The response data.
     */
    function handleAlert(errorType) {
        let invalidMsg = "Please upload valid CSV file.";
        let alertMsg = errorType == "newComp" ? 'User Preference Fields import only applicable for existing company.': invalidMsg;
        alertMsg = errorType == "emptyFile" ? invalidMsg : alertMsg;
        
        $('body').trigger('processStop');
        $(".user-preference-input").val('');
        $(this).val();
        alert({
            title: $.mage.__('Alert'),
            content: $.mage.__(alertMsg),
            actions: {
                always: function() {}
            }
        });
    }
    /**
     * Handle errors by stopping the process, clearing input values, and resetting the file input.
     */
    function handleError() {
        $('body').loader('hide');
        $(".file-name-input").val('');
        $('.browse-file').val('');
    }

    return {
        init: init
    }
});
