define([
    "jquery",
    "mage/template",
    "Fedex_HttpRequestTimeout/js/modal",
    'Magento_Ui/js/modal/confirm',
    'mage/translate',
], function ($, mageTemplate, overlay, confirmation) {
    return function (config) {
        let updateEntryModel,
            createEntryModelOptions = {
                title: $.mage.__('Create Entry'),
                content: function () {
                    return mageTemplate('#fedex-http-request-timeout-entry-template');
                },
                action: function () {
                    updateEntry();
                },
            },
            updateEntryModelOptions = {
                title: $.mage.__('Edit Entry'),
                content: function () {
                    return mageTemplate('#fedex-http-request-timeout-entry-template');
                },
                action: function () {
                    updateEntry();
                }
            };

        initEntries();

        function initEntries() {

            processEntries(config.startingEntries);

            $('body').on('click', '#fedex_http_request_timeout_create_entry_button', function () {

                overlay(createEntryModelOptions)
                updateEntryModel = modal;

                $('#fedex-http-request-timeout-entry-form #entry_key').val('');
                $('#fedex-http-request-timeout-entry-form #entry').val('');

                if ($('#fedex_http_request_timeout_list_inherit').is(':checked')) {
                    $('#fedex_http_request_timeout_list_inherit').click();
                    disableEntriesInput();
                }
            });

            $('body').on('click', '#entries-list .entry-edit', function () {

                overlay(updateEntryModelOptions)
                updateEntryModel = modal;
                let entryKey = $(this).data('entry-key');
                let entry = $(this).data('entry');

                $('#fedex-http-request-timeout-entry-form #entry_key').val(entryKey);
                $('#fedex-http-request-timeout-entry-form #entry').val(entry);
            });

            $('body').on('click', '#entries-list .entry-remove', function () {
                let entry = $(this).data('entry');
                let entryKey = $(this).data('entry-key');
                confirmation({
                    title: $.mage.__('Warning!'),
                    content: $.mage.__('Confirm that you want to delete: %1').replace('%1', entry),
                    actions: {
                        confirm: function() {
                            removeEntry(entryKey);
                        },
                    }
                });
            });

            $('body').on('click', '#fedex_http_request_timeout_create_entry_button_inherit', function () {
                let entriesList = $('#fedex_http_request_timeout_list_inherit')
                if ($(this).is(':checked') !== entriesList.is(':checked')) {
                    entriesList.click();
                    disableEntriesInput();
                }
            });

            $('body').on('click', '#fedex_http_request_timeout_list_inherit', function () {
                disableEntriesInput();
            });
        }

        function updateEntry() {
            let form = $('#fedex-http-request-timeout-entry-form');
            form.validate({})
            if (!form.valid()) {
                return;
            }

            let data = form.serialize();
            $.ajax({
                type: "POST",
                url: config.saveEntryUrl,
                data: data,
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        processEntries(JSON.parse(response.entries_value));
                        updateEntryModel.modal('closeModal');
                    } else {
                        alert(response.error);
                    }
                }
            });
        }

        function removeEntry(entryKey) {
            $.ajax({
                type: "POST",
                url: config.removeEntryUrl,
                data: {
                    entry_key: entryKey,
                },
                dataType: 'JSON',
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        processEntries(JSON.parse(response.entries_value));
                    } else {
                        alert(response.error);
                    }
                }
            });
        }

        function processEntries(entries) {
            let disableButtons = $('#fedex_http_request_timeout_list_inherit').is(':checked');
            $('#entries-list').html('');
            $.each(entries, function (index, entry) {
                let html =
                    `<tr>
                        <td>
                            <input value='${index},${entry.timeout}' type='text' class='input-text disabled' disabled='disabled'/>
                        </td>
                        <td class='col-actions'>
                            <button class='action-delete entry-edit' ${disableButtons?"disabled='disabled'":""} title='Edit Entry' type='button' id='edit-entry_${index}' data-entry-key='${index}' data-entry='${index},${entry.timeout}'></button>
                            <button class='action-delete entry-remove' ${disableButtons?"disabled='disabled'":""} title='Remove Entry' type='button' id='remove-entry_${index}' data-entry-key='${index}' data-entry='${index},${entry.timeout}'></button>
                    </td>`;
                $('#entries-list').append(html);
            });
        }

        function disableEntriesInput() {
            $('#entries-list').find('input').attr('disabled', true);
        }
    }
});
