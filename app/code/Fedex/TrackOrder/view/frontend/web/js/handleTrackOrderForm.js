require(['jquery', 'mage/url', 'ko'], function ($, urlBuilder, ko) {
    $(document).ready(function () {
        var deleteRowButtonImageUrl = $('.row').data('remove-button-image-url');
        var legacyOrderTrackUrl = $('.row').data('legacy-track-order-url');

        //function to handle track more orders button functionality
        var inputRow = 1;

        function toggleTrackButton() {
            $('.order_submit_button_dynamic').each(function () {
                var input = $(this).closest('tr').find('.dynamic_track_order_input');
                if (input.val().trim() === '' || !/^\d+$/.test(input.val())) {
                    $(this).prop('disabled', true);
                } else {
                    $(this).prop('disabled', false);
                }
            });
            $('.order_input_field').each(function () {
                if (this.value.trim() === '' || !/^\d+$/.test(this.value)) {
                    $('#order_submit_button_small').prop('disabled', true);
                } else {
                    $('#order_submit_button_small').prop('disabled', false);
                }
            });
        }
        toggleTrackButton();
        $(document).on('input', '.dynamic_track_order_input, .order_input_field', function() {
            var $this = $(this);
            $this.val($this.val().replace(/[^0-9]/g, ''));
            if ($this.val().length > 16) {
                $this.val($this.val().substring(0, 16));
            }
            toggleTrackButton();
        });
        $('#track_more_orders').on('click', function () {
            if (inputRow < 4) {
                inputRow++;
                if (!$('.dynamic_order_input_rows')[0]) {
                    var dynamicOrderNewDiv = $('<div/>').addClass('dynamic_order_input_rows').css('padding-bottom', '10px');
                    $("#order_input_row").after(dynamicOrderNewDiv);
                }
                $('.dynamic_order_input_rows').append('<tr id="row' + inputRow + '" class="order_input_dynamic_row"><td class="dynamic_remove_button"><img name="remove" class="remove_button" id="' + inputRow + '" src="' + deleteRowButtonImageUrl + '"></td><td class="dynamic_order_input"><input type="text" class="dynamic_track_order_input order_input_field" name="track_order_input[]" id="dynamic_track_order_input' + inputRow + '" placeholder="Search by order number" required pattern="\d+" maxlength="16"></td><td class="dynamic_order_submit_button form_submit_button"><button type="submit" class="order_submit_button_dynamic" id="order_submit_button_dynamic" disabled>TRACK</button></td></tr>');
                $('#row' + (inputRow - 1) + ' .dynamic_order_submit_button').hide();
                toggleTrackButton();

                if (inputRow > 1) {
                    $('.order_submit_button').hide();
                }

                if (inputRow == 4) {
                    $('#track_more_orders').hide();
                }
            }
        });

        //function to handle input row delete button functionality
        $(document).on('click', '.remove_button', function () {
            inputRow--;
            var button_id = $(this).attr("id");
            $('#row' + button_id + '').remove();

            $('#row' + (inputRow) + ' .dynamic_order_submit_button').show();

            toggleTrackButton();
            if (inputRow < 4) {
                $('#track_more_orders').show();
            }

            if (inputRow == 1 && $(window).width() > 480) {
                $('.order_submit_button').show();
            }
        });

        $(window).resize(function () {
            if (inputRow == 1 && $(window).width() > 480) {
                $('.order_submit_button').show();
            } else {
                $('.order_submit_button').hide();
            }
        });

        //function to handle track button functionality
        //array to store processed order id's
        var processedOrderIds = [];

        $(document).on('click', '.form_submit_button', function () {
            var errorInputStyle = {
                'border': '1px solid red',
            };

            var errorMessageStyle = {
                'color': 'red',
                'margin-top': '1rem',
                'font-size': '1rem',
                'font-family': 'FedEx Sans Regular'
            };

            var userInputOrders = [];

            $('input[name^="track_order_input"]').each(function (e) {
                if (this.value != '') {
                    userInputOrders.push(this.value);
                }
            });

            var occurrenceMap = [];
            var duplicateValues = [];

            for (var i = 0; i < userInputOrders.length; i++) {
                var order = userInputOrders[i];

                if (order === undefined || order.trim() === '') {
                    continue;
                }

                if (occurrenceMap[order] || processedOrderIds.includes(order)) {
                    if (!duplicateValues.includes(order)) {
                        duplicateValues.push(order);
                    }
                } else {
                    occurrenceMap[order] = true;
                }
            }

            if (duplicateValues.length != 0) {
                $('.order_input_field').css({
                    'border': '',
                    'color': ''
                });
                $('.error-message').remove();

                for (var j = 0; j < duplicateValues.length; j++) {
                    var fieldToHighlight = duplicateValues[j];
                    $('.order_input_field').filter(function () {
                        return $(this).val() === fieldToHighlight;
                    }).css(errorInputStyle);

                    var errorMessage = 'This Order Number has already been entered. Please use a different Order Number.';
                    $('<div>').addClass('error-message').text(errorMessage).css(errorMessageStyle).insertAfter($('.order_input_field').filter(function () {
                        return $(this).val() === fieldToHighlight;
                    }));
                }
            } else {
                // Show the loader before processing
                $('body').trigger('processStart');
                //check for already processed orders from url gtns orders and input fields
                var urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('gtns')) {
                    var completeSearchOrder = [];
                    if (processedOrderIds.length === 0) {
                        var gtnsValues = urlParams.getAll('gtns');
                        if (gtnsValues) {
                            gtnsValues.forEach(function (value) {
                                var splitValue = value.split(',');
                                processedOrderIds = processedOrderIds.concat(splitValue);
                            });
                        }
                    }
                    var unique2 = userInputOrders.filter((o) => processedOrderIds.indexOf(o) === -1);
                    completeSearchOrder = unique2;
                } else {
                    //ignore already searched input values
                    var completeSearchOrder = [];
                    if (processedOrderIds.length === 0) {
                        completeSearchOrder = userInputOrders;
                    } else {
                        var unique2 = userInputOrders.filter((o) => processedOrderIds.indexOf(o) === -1);
                        completeSearchOrder = unique2;
                    }
                }

                //ajax logic to get order details
                if (completeSearchOrder.length != 0) {
                    var customLink = urlBuilder.build('track/home/search');
                    $.ajax({
                        url: customLink,
                        data: { inputValues: completeSearchOrder },
                        type: 'POST',
                        dataType: 'json',
                        showLoader: true,
                        cache: false,
                        success: function (response) {
                            $('.order_input_field').css({
                                'border': '',
                                'color': ''
                            });
                            $('.error-message').remove();

                            if (response && response.output != '') {
                                //checking already processed values
                                var extractedOrderIDs = extractOrderIds(response.output);

                                for (var i = 0; i < extractedOrderIDs.length; i++) {
                                    processedOrderIds.push(extractedOrderIDs[i]);
                                }

                                $("#order_json_data").append(response.output);
                                $(".fedex-product-bundle-children-init").trigger('contentUpdated');
                                $('#gtnsAnotherOrderHeading').hide();

                                var trackAnotherOrderHeading = '<h2 class="track_another_order">Track Another Order</h2>';
                                if ($('.track_another_order').length == 0) {
                                    $("#order_input_row").before(trackAnotherOrderHeading);
                                }

                                $("#track_order_control, #gtns_track_order_control").css('margin-top', '8rem');

                                $('#track_more_orders').text('+Track multiple orders');
                                $('#track_more_orders').attr('name', 'track_multiple_orders');
                                $('#track_order_form')[0].reset();
                                resetForm();
                                toggleTrackButton();
                            }

                            // Hide the loader after processing
                            $('body').trigger('processStop');
                        },
                        error: function (error) {
                            $("#order_json_data").append('<div>System Error!</div>');
                            $('#track_order_form').hide();
                            $('#track_more_orders').hide();
                            // Hide the loader in case of error
                            $('body').trigger('processStop');
                        }
                    });
                } else {
                    $('#track_order_form')[0].reset();
                    $('.order_input_field').css({
                        'border': '',
                        'color': ''
                    });
                    $('.error-message').remove();
                }
            }

            return false;
        });

        //function to extract orders from response json
        function extractOrderIds(response) {
            var div = document.createElement('div');
            div.innerHTML = response;

            var orderIDs = [];
            var orderIdElements = div.getElementsByClassName('orderId');

            for (var i = 0; i < orderIdElements.length; i++) {
                var orderId = orderIdElements[i].querySelector('strong').textContent;
                orderIDs.push(orderId);
            }
            return orderIDs;
        }

        // Function to reset the form
        function resetForm() {
            $('input[name^="track_order_input"]').each(function () {
                $(this).val('');
            });
            $('.order_input_field').css({
                'border': '',
                'color': ''
            });
            $('.error-message').remove();
        }

        //show or hide order details section during resize of screen
        $(window).resize(function () {
            if ($(window).width() > 480) {
                $('.orderItemDetailsContentSmall').hide();
                $('.orderDetailsSectionSmall').hide();
            } else if ($(window).width() <= 480) {
                $('.orderItemDetailsContentSmall').show();
            }
        });
    });
});
