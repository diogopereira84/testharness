define([
    'jquery',
    'Magento_Catalog/js/product/view/product-ids-resolver',
    'peProductController',
    'Magento_Ui/js/model/messageList',
    "fedex/storage",
    'mage/translate',
    'prototype'
], function ($, idsResolver, productController, messageList, fxoStorage, $t) {
    'use strict';

    var catalogAddToCartWidgetMixin = {
        /** @inheritdoc */
        _create: function () {
            this._super();
            if (this.options.submitOnLoad) {
                this.ajaxSubmit($(this.element));
            }
        },

        // Update processProductsSequentially to return qtyMapping
        processProductsSequentially: async function (productIds, productsIdsSkus, productEngineUrl) {
            if (!Object.keys(productIds).length || !productEngineUrl) {
                console.warn('processProductsSequentially: Invalid input parameters');
                return { products: [], qtyMapping: {} };
            }

            const serializedProductsInstances = [];
            const qtyMapping = {};

            try {
                for (const productId of Object.keys(productIds)) {
                    const integrationReference = productsIdsSkus[productId];
                    const productInstance = await this.processProduct(productId, productIds[productId], integrationReference, productEngineUrl);
                    if (productInstance) {
                        serializedProductsInstances.push(productInstance);
                        if (integrationReference && productInstance.qty !== undefined) {
                            qtyMapping[integrationReference] = productInstance.qty;
                        }
                    }
                }

                return { products: serializedProductsInstances, qtyMapping };
            } catch (error) {
                return { products: serializedProductsInstances, qtyMapping };
            }
        },

        /**
         * Processes a single product by its ID and returns a serialized product instance.
         * @param productId
         * @param choiceIds
         * @param integrationReference
         * @param productEngineUrl
         * @returns {Promise<unknown>}
         */
        processProduct: function (productId, choiceIds, integrationReference, productEngineUrl) {
            return new Promise((resolve) => {
                const productHint = this.createProductHint(productId, choiceIds);
                const productControllerObj = new productController.ProductController(productEngineUrl);

                try {
                    productControllerObj.selectProductByHint(productHint, () => {
                        try {
                            const productInstance = productControllerObj.serializeSelectedProduct();
                            if (productInstance && integrationReference) {
                                productInstance.integratorProductReference = integrationReference;
                            }
                            const quantitySet = [];
                            if (productControllerObj.getPropertyByName('PRODUCT_QTY_SET', productControllerObj.product.properties) != null) {
                                const boundIt = productControllerObj.getPropertyByName('PRODUCT_QTY_SET', productControllerObj.product.properties).getBounds().iterator();
                                while (boundIt.hasNext()) {
                                    const inBound = boundIt.next();
                                    const allowedValueIt = inBound.allowedValues.iterator();
                                    while (allowedValueIt.hasNext()) {
                                        quantitySet.push(allowedValueIt.next().name);
                                    }
                                }
                            }
                            productInstance.quantityChoices = quantitySet;
                            resolve(productInstance);
                        } catch (serializationError) {
                            resolve(null);
                        }
                    });
                } catch (error) {
                    resolve(null);
                }
            });
        },

        /**
         * Creates a product hint object for the product controller.
         * @param productId
         * @param choiceIds
         * @returns {{productId, controlId: null, version: null, choiceIds: *[], presetId: null, defaultContent: boolean, validateProductConfig: boolean, contentHints: null}}
         */
        createProductHint: function (productId, choiceIds) {
            return {
                productId: productId,
                controlId: window?.checkout?.is_retail ? 4: 2,
                version: null,
                choiceIds: choiceIds,
                presetId: null,
                defaultContent: true,
                validateProductConfig: true,
                contentHints: null
            };
        },

        /**
         * Default AJAX submit method from Magento's catalog-add-to-cart.js.
         * @param form
         * @param formData
         * @param self
         * @param productIds
         * @param productInfo
         */
        defaultAjaxSubmit(form, formData, self, productIds, productInfo) {
            $.ajax({
                url: form.prop('action'),
                data: formData,
                type: 'post',
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,

                /** @inheritdoc */
                beforeSend: function () {
                    if (self.isLoaderEnabled()) {
                        $('body').trigger(self.options.processStart);
                    }
                },

                /** @inheritdoc */
                success: function (res) {
                    var eventData, parameters;

                    $(document).trigger('ajax:addToCart', {
                        'sku': form.data().productSku,
                        'productIds': productIds,
                        'productInfo': productInfo,
                        'form': form,
                        'response': res
                    });

                    if(res.success && res.message.toLowerCase().includes('bundle')) {
                        fxoStorage.set('showBundleInstructionModalOnCart', true);
                    }

                    if (self.isLoaderEnabled()) {
                        $('body').trigger(self.options.processStop);
                    }

                    if (res.backUrl) {
                        eventData = {
                            'form': form,
                            'redirectParameters': []
                        };
                        // trigger global event, so other modules will be able add parameters to redirect url
                        $('body').trigger('catalogCategoryAddToCartRedirect', eventData);

                        if (eventData.redirectParameters.length > 0 &&
                            window.location.href.split(/[?#]/)[0] === res.backUrl
                        ) {
                            parameters = res.backUrl.split('#');
                            parameters.push(eventData.redirectParameters.join('&'));
                            res.backUrl = parameters.join('#');
                        }

                        self._redirect(res.backUrl);

                        return;
                    }

                    if (res.messages) {
                        $(self.options.messagesSelector).html(res.messages);
                    }

                    if (res.minicart) {
                        $(self.options.minicartSelector).replaceWith(res.minicart);
                        $(self.options.minicartSelector).trigger('contentUpdated');
                    }

                    if (res.product && res.product.statusText) {
                        $(self.options.productStatusSelector)
                            .removeClass('available')
                            .addClass('unavailable')
                            .find('span')
                            .html(res.product.statusText);
                    }
                    self.enableAddToCartButton(form);
                },

                /** @inheritdoc */
                error: function (res) {
                    if (self.isLoaderEnabled()) {
                        $('body').trigger(self.options.processStop);
                    }

                    messageList.addErrorMessage({ message: $t(res.message) });

                    $(document).trigger('ajax:addToCart:error', {
                        'sku': form.data().productSku,
                        'productIds': productIds,
                        'productInfo': productInfo,
                        'form': form,
                        'response': res
                    });
                },

                /** @inheritdoc */
                complete: function (res) {
                    if (res.state() === 'rejected') {
                        location.reload();
                    }
                }
            });
        },

        /**
         * Submit the form via AJAX
         *
         * @param {jQuery} form - The form to be submitted
         * @return {void}
         */
        ajaxSubmit: function (form) {
            var self = this,
                productIds = idsResolver(form),
                productInfo = self.options.productInfoResolver(form),
                attributesElement = form.find('#bundle-product-attributes');

            $(self.options.minicartSelector).trigger('contentLoading');
            self.disableAddToCartButton(form);

            // Handle regular product submission
            if (attributesElement.length === 0) {
                this._submitStandardProduct(form, productIds, productInfo);
                return;
            }

            // Handle bundle product with attributes
            this._submitBundleProduct(form, attributesElement, productIds, productInfo);
        },

        /**
         * Submit standard product form
         *
         * @private
         * @param {jQuery} form - The form to be submitted
         * @param {Array} productIds - Product IDs
         * @param {Object} productInfo - Product information
         */
        _submitStandardProduct: function (form, productIds, productInfo) {
            const formData = new FormData(form[0]);
            this.defaultAjaxSubmit(form, formData, this, productIds, productInfo);
        },

        /**
         * Submit bundle product with attributes
         *
         * @private
         * @param {jQuery} form - The form to be submitted
         * @param {jQuery} attributesElement - Bundle attributes element
         * @param {Array} productIds - Product IDs
         * @param {Object} productInfo - Product information
         */
        _submitBundleProduct: function (form, attributesElement, productIds, productInfo) {
            const self = this,
                childProductsIdsSkus = attributesElement.data('product-ids-skus') || [],
                childProductsIds = attributesElement.data('product-ids') || [],
                productEngineUrl = attributesElement.data('product-engine-url') || '',
                bundleAddToCartUrl = attributesElement.data('bundle-add-to-cart-url') || '';

            if (self.isLoaderEnabled()) {
                $('body').trigger(self.options.processStart);
            }
            this.processProductsSequentially(childProductsIds, childProductsIdsSkus, productEngineUrl)
                .then(result => {
                    form.prop('action', bundleAddToCartUrl);
                    const formData = new FormData(form[0]);
                    formData.set('productsData', JSON.stringify(result.products || {}));
                    formData.set('productsQtyData', JSON.stringify(result.qtyMapping || {}));
                    self.defaultAjaxSubmit(form, formData, self, productIds, productInfo);
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
    };

    return function (targetWidget) {
        // Example how to extend a widget by mixin object
        $.widget('mage.catalogAddToCart', targetWidget, catalogAddToCartWidgetMixin); // the widget alias should be like for the target widget

        return $.mage.catalogAddToCart; //  the widget by parent alias should be returned
    };
});
