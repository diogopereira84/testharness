define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/alert'
], function ($, confirmation, alertModal) {
    'use strict';

    let selectedFile = null;

    /**
     * Handle file selection and validate CSV extension
     */
    window.uploadCsvHandler = function (file) {
        if (!file) {
            selectedFile = null;
            return;
        }

        const ext = file.name.split('.').pop().toLowerCase();
        if (ext !== 'csv') {
            alertModal({
                title: 'Invalid File',
                content: 'Only CSV files are allowed.'
            });
            $('input[type="file"]').val('');
            selectedFile = null;
            return;
        }

        selectedFile = file;
    };

    /**
     * Apply CSV file - uploads file and triggers backend logic
     */
    window.applyCsv = function () {
        if (!selectedFile) {
            alertModal({
                title: 'No File Selected',
                content: 'Please select a CSV file before applying.'
            });
            return;
        }

        confirmation({
            title: 'Confirm Apply',
            content: 'The key values from the file will be updated.  Do you want to continue with the update of the key values?',
            actions: {
                confirm: function () {
                    const formData = new FormData();
                    formData.append('toggle_file', selectedFile);
                    formData.append('form_key', window.formKey);

                    $.ajax({
                        url: window.applyUrl,
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        showLoader: true,
                        success: function (response) {
                            if (response.success) {
                                // Success message with applied count if available
                                let successMessage = response.message || 'Feature toggles applied successfully.';
                                if (response.appliedCount) {
                                    successMessage += ' Applied ' + response.appliedCount + ' toggle(s).';
                                }
                                
                                // Check if there are any invalid keys to display
                                if (response.warnings && response.invalidKeys && response.invalidKeys.length > 0) {
                                    // First show the success message
                                    alertModal({
                                        title: 'Applied with Warnings',
                                        content: successMessage
                                    });
                                    
                                    // Then show the warnings in a separate modal
                                    setTimeout(function() {
                                        let warningContent = '<p>The following keys were not found in the system:</p>';
                                        warningContent += '<ul>';
                                        response.invalidKeys.forEach(function(invalidKeyMsg) {
                                            warningContent += '<li>' + invalidKeyMsg + '</li>';
                                        });
                                        warningContent += '</ul>';
                                        warningContent += '<p>Please verify that the keys exist in the system.</p>';
                                        
                                        alertModal({
                                            title: 'Invalid Keys Found',
                                            content: warningContent
                                        });
                                    }, 500);
                                } else {
                                    // No invalid keys, just show success message
                                    alertModal({
                                        title: 'Success',
                                        content: successMessage
                                    });
                                }
                                
                                $('input[type="file"]').val('');
                                selectedFile = null;
                            } else {
                                alertModal({
                                    title: 'Apply Failed',
                                    content: response.message || 'Could not apply CSV.'
                                });
                            }
                        },
                        error: function () {
                            alertModal({
                                title: 'Error',
                                content: 'An error occurred. Please try again.'
                            });
                        }
                    });
                },
                cancel: function () {
                    // Action canceled by user
                }
            }
        });
    };

    /**
     * Download the current CSV file
     */
    window.downloadCsv = function () {
        let csvRowsData = [];

        $(window.toggleSelector || "tr[id*='row_environment_toggle']").each(function () {
           
            let keyAttr = $(this).find('select').attr('name') ?? '';
            if (keyAttr.length > 0) {
                let match = /groups\[environment_toggle]\[fields]\[(.*)]\[value]/.exec(keyAttr);
                if (match && match[1]) {
                    let key = match[1];
                    let value = $(this).find('select option:selected').text().trim();
                    if (/^(yes|no)$/i.test(value)) {
                        csvRowsData.push([key, value]);
                    }
                }
            }
        });

        if (csvRowsData.length === 0) {
            alertModal({
                title: 'No Data Found',
                content: 'No data found to export.'
            });
            return;
        }

        let csvRowsAndHeaders = [['Key', 'Value']].concat(csvRowsData);
        let csvString = csvRowsAndHeaders.map(e => e.join(",")).join("\n");

        let csvContent = "data:text/csv;charset=utf-8," + encodeURIComponent(csvString);

        let downloadElementName = 'feature_toggles_export';
        let timestamp = Date.now();
        let environment = window.location.host.split('.')[0];

        let link = document.createElement('a');
        link.setAttribute('href', csvContent);
        link.setAttribute('download', environment + '_' + downloadElementName + '_' + timestamp + '.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    /**
     * Remove the uploaded CSV file
     */
    window.removeCsv = function () {
        if (!selectedFile) {
            alertModal({
                title: 'No File Selected',
                content: 'Please select a CSV file before removing.'
            });
            return;
        }

        confirmation({
            title: 'Remove Selected CSV',
            content: 'Are you sure you want to remove the selected CSV file?',
            actions: {
                confirm: function () {
                    var fileInput = $('input[type="file"]')[0];
                    $(fileInput).val('');

                    selectedFile = null;

                    alertModal({
                        title: 'Removed',
                        content: 'Selected CSV file has been removed.'
                    });
                },
                cancel: function () {
                }
            }
        });
    };

});
