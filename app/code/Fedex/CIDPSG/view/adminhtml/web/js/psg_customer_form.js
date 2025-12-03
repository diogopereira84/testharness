/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require(
    [
        'Magento_Ui/js/lib/validation/validator',
        'jquery',
        'mage/translate'
    ], function (validator, $) {
        /**
         * validation for client id field
         */
        validator.addRule(
            'validate-hyphen',
            function (value) {
                return !(/[^a-z^A-Z^0-9\.\-]/g.test(value));
            },
            $.mage.__('Client id can only contains hyphen (-), no whitespace and alphanumric values only.')
        )

        /**
         * Make it non editable if client is default
         */
        $(document).on('focus', "input[name='client_id']", function () {
            if ($(this).val() == 'default') {
                $(this).attr("readonly", "readonly");
            }
        });

        /**
         * Enable or disable max character field based on validation type when click on section title
         */
        $(document).on('click', ".customer-fields-container-collapse .fieldset-wrapper-title", function () {
            let interval = setInterval(function () {
                if ($("td.field-validate-type").length > 0) { 
                    $("td.field-validate-type").each(function() {
                        let element = $(this).find("select");
                        let selector = $(this).next("td").find("input");
                        validateField(element, selector, 'click')
                    });
                    clearInterval(interval);
                }
            }, 100);
        });

        /**
         * Enable or disable max character field based on validation type when when change validation type
         */
        $(document).on('change', "td.field-validate-type select", function () {
            let maxCharacterField = $(this).parents("td.field-validate-type").next("td").find("input");
            let element = $(this);
            validateField(element, maxCharacterField, 'change');
        });

        /**
         * Validate admin field
         */
        function validateField(element, maxCharacterField, event) {
            if (element.val() == 'telephone' || element.val() == 'fax') {
                maxCharacterField.val(10);
                maxCharacterField.attr("disabled", "disabled");
            } else if(element.val() == 'zipcode') {
                maxCharacterField.val(9);
                maxCharacterField.attr("disabled", "disabled");   
            } else if(element.val() == 'fedex_shipping_account' && event == 'change') {
                maxCharacterField.val(10);
                maxCharacterField.removeAttr("disabled");   
            } else if((element.val() == 'email' || element.val() == 'text') && event == 'change') {
                maxCharacterField.val(70);
                maxCharacterField.removeAttr("disabled");   
            } else if (element.val() == 'fedex_account') {
                maxCharacterField.val(24);
                maxCharacterField.attr("disabled", "disabled");
            } else {
                maxCharacterField.removeAttr("disabled");
            }
            maxCharacterField.change();
        }
    });
