define(['jquery', 'ko', 'pubsub', 'uiComponent', 'mage/url', 'ajaxUtils', 'domReady!'], function ($, ko, pubsub, Component, urlBuilder, ajaxUtils) {
    'use strict';
    return Component.extend({
        shippingEstimates: ko.observableArray([]),
        responseLength: ko.observable(0),
        displayProgressFlag: ko.observable(false),

        /* Initialize method get called when we mount this component */
        initialize: function (config) {  
            /* Component is extended from Magento UI Component, this._super() will call the parent component init */
            this._super();
            ko.postbox.subscribe("resetRate", () => {
                this.responseLength(0);
            });
        },

        /* Invoke the API and get the shipping estimate */
        getShippingEstimate: function () {
            this._clearErrorBlocks();

            if(this.validateShippingEstimate()) {
                this.displayProgressFlag(true);
                var updateBtn = $('.btn-update-location');
                updateBtn.find('.btn-text').addClass('hide');
                updateBtn.find('.inline-loader').removeClass('hide');
                updateBtn.prop('disabled', true ).removeClass('hide');
                $('#geolocate').addClass('link-disable');
                var region = $('#shipping-region').val();
                var zipcode = $('#shipping-zipcode').val();
                const productsPayload = window.productsPayload;

                const params = {
                    stateOrProvinceCode: region,
                    postalCode: zipcode,
                    products: JSON.stringify(productsPayload.products),
                    validateContent: productsPayload.validateContent
                };

                ajaxUtils.post('/estimate-shipping', {}, params, false, 'json', this.onShippingEstimateSuccess.bind(this));
            }
        },
  
        /* Callback method to recevice the API response */ 
        onShippingEstimateSuccess: function (result) {
            const responseData = result.response.data; // On error, returns: ''
            const responseError = result.response.hasError; // On error, returns: true

            this.shippingEstimates(responseData);
            this.responseLength(responseData.length);

            if (responseError) {
                const ERROR_MESSAGE_ID = 'api-response'; // It's rendered as #error-api-response by generateValidationBlock()
                const MESSAGE_DELAY = 250; // Just to let the user know a new error message is being displayed

                const fetchErrorMessage = $(`#error-${ERROR_MESSAGE_ID}`);

                if (fetchErrorMessage.length) {
                    fetchErrorMessage.remove();
                }

                setTimeout(
                    () => this.generateValidationBlock('api-response', 'We were not able to estimate shipping due to an internal error.'),
                    MESSAGE_DELAY
                );
            }

            const updateBtn = $('.btn-update-location');

            updateBtn.find('.inline-loader').addClass('hide');
            updateBtn.find('.btn-text').removeClass('hide');
            $('#geolocate').removeClass('link-disable');
            this.displayProgressFlag(false);
        },
        validateShippingEstimate: function() {
            var isValid = true;
            var formControls = $('.estimate-form .form-control');
            for(var i = 0; i < formControls.length; i++) {
                var controlId = formControls[i].id;
                var errorBlock = $('#error-' + controlId);
                var patternMatch = true;
                if(formControls[i].pattern) {
                    var regexPattern = new RegExp(formControls[i].pattern);
                    patternMatch = regexPattern.test(formControls[i].value);
                }
                if(!formControls[i].value || !patternMatch) {
                    isValid = false;
                    if(errorBlock.length < 1) {
                        var errorMsg = formControls[i].getAttribute('data-error');
                        this.generateValidationBlock(controlId,errorMsg);
                    }
                } else if (formControls[i].value && patternMatch && errorBlock) {
                    $(errorBlock).remove();
                }
            }
            return isValid;
        },
        generateValidationBlock: function(controlId, errorMsg) {
            var validationTemplate = $('.estimate-form .error-template').clone();
            $(validationTemplate).removeClass('error-template hide');
            $(validationTemplate).attr('id', 'error-' + controlId);
            $(validationTemplate).find('.message-text').text(errorMsg);
            $('.estimate-form .error-block').append(validationTemplate);
        },
        _clearErrorBlocks: function () {
            $('#shipping-estimate-modal [id*="error-"]').remove();
        }
    });
});