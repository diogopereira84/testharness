/*
 * @category Fedex
 * @package Fedex_ProductEngine
 * @copyright Fedex (c) 2021.
 * @author Iago Lima <ilima@mcfadyen.com>
 */

define([
    "jquery",
    'peProductController',
    'fedexRateApi',
    'mageUtils',
    'mage/template',
    'jquery-ui-modules/widget',
    'ko',
    "mage/translate"

], function ($, productController, fedexRateApi, utils, mageTemplate, ko) {
    'use strict';

    $.widget('mage.productengineattributes', {
        options: {
            peUrl: '',
            productController: null,
            inputSelectedSelection: '',
            serializedProductInstance: '',
            inChoiceIds: [],
            errorMessage: $.mage.__('We\'re sorry, pricing is currently unavailable. Please try again later.'),
            errorTemplate: mageTemplate('#product-engine-error-messages-template'),
            ph: {
                productId: $('#product-id').val(),
                controlId: null,
                version: null,
                choiceIds: [],
                presetId: ($( '#preset-id' ).val() ? $( '#preset-id' ).val() : null),
                defaultContent: true,
                validateProductConfig: true,
                contentHints: null
            }
        },
        _create: function () {
            var self = this;
            self.options.productController = new productController.ProductController(self.options.peUrl);

            var inputQtyField = $('input.qty-field'),
                inputHiddenQtyField = $('#quantity');
            inputQtyField.on('keypress', function(event) {
                var qtyCode = (event.which) ? event.which : event.keyCode;
                if (qtyCode < 48 || qtyCode > 57) {
                    return false;
                }
            });

            inputQtyField.on('input', function(event) {
                var qtyFieldValue = $(this).val(),
                    inputQty = parseInt(qtyFieldValue),
                    minQty = $(this).data('min-value'),
                    maxQty = $(this).data('max-value'),
                    errorFlag = false;
                if(qtyFieldValue.length) {
                    if(isNaN(inputQty) || (inputQty < minQty)) {
                        $(this).val(minQty);
                        errorFlag = true;
                    } else if (inputQty > maxQty) {
                        $(this).val(maxQty);
                        errorFlag = true;
                    }
                }
                if(errorFlag) {
                    $('#qty-error-text').removeClass('hide');
                } else {
                    $('#qty-error-text').addClass('hide');
                }
            });

            inputQtyField.on('focusout', function(event) {
                var qtyFieldValue = $(this).val(),
                    inputQty = parseInt(qtyFieldValue),
                    minQty = $(this).data('min-value'),
                    selectedQty = inputHiddenQtyField;
                if(!qtyFieldValue.length) {
                    inputQty = minQty;
                    $(this).val(minQty);
                }
                if(selectedQty.val() != inputQty) {
                    selectedQty.val(inputQty);
                }
                self.callProductRateApi();

                    $('#qty-error-text').addClass('hide');

            });

            if(inputQtyField.val() !== inputHiddenQtyField.val()) {
                inputHiddenQtyField.val(inputQtyField.val());
            }

            $(self.options.inputSelectedSelection).on('change', function() {
                self.callProductRateApi();
            });
        },

        callProductRateApi: function() {
            var self = this;
            var inChoiceIdsOld = [];
            Object.assign(inChoiceIdsOld, self.options.inChoiceIds);
            var qtyOld = self.options.ph.qty;
            $( self.options.inputSelectedSelection ).each(function(index, elm) {
                if(elm.id === 'quantity') {
                    self.options.ph.qty = $(elm).val();
                    self.options.productController.setProductQty(self.options.ph.qty);
                } else {
                    self.options.inChoiceIds[index] = $(elm).val();
                }
            });

            if((qtyOld === self.options.ph.qty && utils.equalArrays(self.options.inChoiceIds, inChoiceIdsOld))){
                return true;
            }

            self.options.productController.selectProductByHint(self.options.ph, () => {
                self.options.serializedProductInstance = self.getSerializedProduct();
                window.serializedProductInstance = self.options.serializedProductInstance;
                if (window.contentAssociation) {
                    const isDyeSubProduct = document.getElementById('isDyeSubProduct').value === '1';
                    if(window.tiger_E_478196_dye_sub_pod_2_updates && isDyeSubProduct) {
                        const vendorTemplate = self.options.productController.getVendorTemplate('CUSTOMERS_CANVAS', self.options.inChoiceIds);
                        self.options.serializedProductInstance.vendorTemplate = {
                            templateId: vendorTemplate.vendorTemplateId,
                            productId: vendorTemplate.vendorProductId,
                            productVersion: vendorTemplate.vendorProductVersion
                        };
                    }
                    window.contentAssociation(self.options.serializedProductInstance);
                }
                fedexRateApi.callRateApiAjax(self.options.serializedProductInstance).then(
                    function (result) {
                        result = JSON.parse(result);
                        fedexRateApi.applyPriceBox(result);
                    }, function (reason) {
                        fedexRateApi.applyPriceBox(null);
                        console.log(reason);
                    }
                );

            }, (response) => {
                console.info(response);
            });
        },

        initProductEngine: async function(useFedexAccount = false) {
            var self = this;
            $( self.options.inputSelectedSelection ).each(function(index, elm) {
                if(elm.id === 'quantity') {
                    self.options.ph.qty = $(elm).val() ? $(elm).val() : $(elm).siblings().text();
                } else {
                    self.options.inChoiceIds[index] = $(elm).val()
                }
            });

            self.options.ph.choiceIds = self.options.inChoiceIds;

            this.options.productController.selectProductByHint(this.options.ph, () => {
                this.options.serializedProductInstance = this.getSerializedProduct();
                window.serializedProductInstance = this.options.serializedProductInstance;
                if (window.contentAssociation) {
                    const isDyeSubProduct = document.getElementById('isDyeSubProduct').value === '1';
                    if(window.tiger_E_478196_dye_sub_pod_2_updates && isDyeSubProduct) {
                        const vendorTemplate = self.options.productController.getVendorTemplate('CUSTOMERS_CANVAS', self.options.inChoiceIds);
                        this.options.serializedProductInstance.vendorTemplate = {
                            templateId: vendorTemplate.vendorTemplateId,
                            productId: vendorTemplate.vendorProductId,
                            productVersion: vendorTemplate.vendorProductVersion
                        };
                    }
                    window.contentAssociation(this.options.serializedProductInstance);
                }
                fedexRateApi.callRateApiAjax(this.options.serializedProductInstance).then(
                    function (result) {
                        result = JSON.parse(result);
                        fedexRateApi.applyPriceBox(result);
                    }, function (reason) {
                        fedexRateApi.applyPriceBox(null);
                        console.info(reason);
                    }
                );

            }, (response) => {
                fedexRateApi.applyPriceBox(null);
                $('#config-accordion').after(this.options.errorTemplate({message: this.options.errorMessage}));
                console.info(response);
            });
        },

        getSerializedProduct: function() {
            return this.options.productController.serializeSelectedProduct();
        },

        getQuantitySets: async function (productKey) {
            var self = this;
            const errorCallback = (errorData) => {
                $('.product-engine-qty label').css({opacity: 1.0, visibility: "visible"}).animate({opacity: 1}, 500);
                console.log('get Product Quntity Sets Error for '+errorData)
            };

            self.options.productController = new productController.ProductController(self.options.peUrl);
            const productEngineQtypromise = new Promise(function(resolve) {
                var _this = self.options.productController;
                var quantitySet = [];
                _this.resolveProductVersion(productKey.id, productKey.version, function (productVersion) {
                    _this.fetchProductDataById(productKey.id, productVersion, null, function (productData) {
                        var property = _this.getPropertyByName('PRODUCT_QTY_SET', productData.product.properties);
                        if (property != null) {
                            var boundIt = property.getBounds().iterator();
                            while (boundIt.hasNext()) {
                                var inBound = boundIt.next();
                                var allowedValueIt = inBound.allowedValues.iterator();
                                while (allowedValueIt.hasNext()) {
                                    quantitySet.push(allowedValueIt.next().name);
                                }
                            }
                        }
                        resolve(quantitySet);
                    }, errorCallback);
                });
            });
            return productEngineQtypromise;
        }
    });

    return $.mage.productengineattributes;
});
