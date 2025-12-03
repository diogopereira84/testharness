define([
    "jquery",
    "mage/template",
    "mage/translate",
    "domReady!"
], function($, mageTemplate) {
    'use strict'

    return function (config, elm) {

        config.minQty = parseInt(config.minQty);
        config.maxQty = parseInt(config.maxQty);
        var minus = document.querySelector(".btn-subtract"),
            add = document.querySelector(".btn-add"),
            quantityNumber = document.querySelector(".item-quantity"),
            currentValue = config.minQty,
            qtyErrorText = document.getElementById("qty-error-text"),
            template = mageTemplate('#price-per-qty-template');

        let toggleButtonState = function(currentValue) {
            minus.disabled = currentValue <= config.minQty || config.isUnavailable;
            add.disabled = currentValue >= config.maxQty || config.isUnavailable;
        };

        toggleButtonState(currentValue);
        updatedHiddenFields(currentValue);
        renderPricePerQty(currentValue);

        minus.addEventListener("click", function () {
            currentValue = parseInt(quantityNumber.value) - 1;
            if (currentValue >= config.minQty) {
                quantityNumber.value = currentValue;
                renderPricePerQty(currentValue);
                updatedHiddenFields(currentValue);
            } else {
                qtyErrorText.classList.remove('hide');
            }
            toggleButtonState(currentValue);
        });

        add.addEventListener("click", function () {
            currentValue = parseInt(quantityNumber.value) + 1;
            if (currentValue <= config.maxQty) {
                quantityNumber.value = currentValue;
                renderPricePerQty(currentValue);
                updatedHiddenFields(currentValue);
            } else {
                qtyErrorText.classList.remove('hide');
            }
            toggleButtonState(currentValue);
        });

        function checkQty(event) {
            const value = parseInt(event.target.value);

            if ((value >= config.minQty && value <= config.maxQty) || isNaN(value)) {
                qtyErrorText.classList.add('hide');
                return;
            }

            qtyErrorText.classList.remove('hide');

            // Choose between the min and max values
            this.value = Math.max(config.minQty, Math.min(value, config.maxQty));
            toggleButtonState(this.value);
        }

        quantityNumber.addEventListener('keyup', function(event) {
            checkQty.call(this, event);
        });

        document.getElementById('qty').addEventListener('qty_updated', function (event) {
            const min = parseInt(event.detail.min);
            const max = parseInt(event.detail.max);

            if (config.minQty !== min || config.maxQty !== max) {
                config.minQty = min;
                config.maxQty = max;
                qtyErrorText.textContent = $.mage.__('Accepted values are between %1 and %2')
                    .replace('%1', min)
                    .replace('%2', max);
                checkQty.call(this, event);
            }
        });

        ['change', 'blur'].forEach(function(event) {
            quantityNumber.addEventListener(event, function () {
                var value = parseInt(this.value ? this.value : 0);
                if (value < config.minQty) {
                    this.value = config.minQty;
                } else if (value > config.maxQty) {
                    this.value = config.maxQty;
                }
                qtyErrorText.classList.add('hide');
                renderPricePerQty(this.value);
                updatedHiddenFields(this.value);
                toggleButtonState(this.value);
            });
        });

        $('.product-options-wrapper .swatch-opt').on('click', '.swatch-option', function() {
            if($(this).hasClass('disabled')) {
                return;
            }
            const selectedOption = $(this).data('option-id');
            $(this).parents('.swatch-attribute').attr('data-option-selected', selectedOption);
            const selectedSwatches = document.querySelectorAll('.product-options-wrapper .swatch-attribute');
            const selectedAttributes = Array.from(selectedSwatches).reduce((accumulatorObj, swatchItem) => {
                const attributeId = swatchItem.getAttribute('data-attribute-id');
                const selectedOption = swatchItem.getAttribute('data-option-selected');
                accumulatorObj[attributeId] = selectedOption;
                return accumulatorObj;
            }, {});
            const selectedOffer = getSelectedOffer(window.offerData, selectedAttributes);
            if(selectedOffer) {
                const unitOfferPrice = parseFloat(selectedOffer.skuOfferData['final-price']).toFixed(2);
                const quantity = parseInt($('#hidden-product-qty').val());
                $('#hidden-offer-price').val(unitOfferPrice);
                $('#price-box-2 .price').text(`$${unitOfferPrice}`);
                renderPricePerQty(quantity);
            }
        });

        $('.file-upload-container #add-to-cart-button').click(function () {
            const addtocartForm = $(this).closest('form');
            if(config.minQty && config.maxQty) {
                const quantity = parseInt(addtocartForm.find('#hidden-product-qty').val());
                if (quantity < config.minQty || quantity > config.maxQty) {
                    alert(`Please enter a quantity between ${config.minQty} and ${config.maxQty}`);
                    return;
                }
            }
            if(window.offerData) {
                const selectedSwatches = document.querySelectorAll('.product-options-wrapper .swatch-attribute');
                const selectedAttributes = Array.from(selectedSwatches).reduce((accumulatorObj, swatchItem) => {
                    const attributeId = swatchItem.getAttribute('data-attribute-id');
                    const selectedOption = swatchItem.getAttribute('data-option-selected');
                    accumulatorObj[attributeId] = selectedOption;
                    return accumulatorObj;
                }, {});
                const selectedOffer = getSelectedOffer(window.offerData, selectedAttributes);
                const formattedOfferPayLoad = Object.entries(selectedOffer.skuOfferData.attributes).map(([key, value]) => `${key}=>${value}`).join(',');
                addtocartForm.find('#hidden-product-offer-id').val(selectedOffer.skuOfferData['offer-id']);
                addtocartForm.find('#hidden-product-super_attribute').val(`[${formattedOfferPayLoad}]`);
            }
            addtocartForm.submit();
        });

        function getSelectedOffer(offerData, selectedAttributes) {
            for (let [skuId, skuOfferData] of Object.entries(offerData)) {
                let isMatchingOffer = true;
                for (let [attributeId, selectedOption] of Object.entries(selectedAttributes)) {
                    if (skuOfferData.attributes[attributeId] !== selectedOption) {
                        isMatchingOffer = false;
                        break;
                    }
                }
                if (isMatchingOffer) {
                    return { skuId, skuOfferData: skuOfferData };
                }
            }
            return null;
        }

        function renderPricePerQty(qty) {
            if (qty == 1) {
                if (document.contains(document.getElementById("dynamic-qty-value"))) {
                    document.getElementById("dynamic-qty-value").remove();
                }
            } else {
                const unitOfferPrice = parseFloat($('#hidden-offer-price').val());
                const valuePerQty = unitOfferPrice ? unitOfferPrice * qty : config.basePrice * qty;
                const newField = template({
                    data: {
                        qty: qty,
                        value_per_qty: '$' + valuePerQty.toFixed(2)
                    }
                });
                if (document.contains(document.getElementById("dynamic-qty-value"))) {
                    document.getElementById("dynamic-qty-value").remove();
                }
                $('#price-box-2 div.price-area .prices').append(newField);
            }
        }

        function updatedHiddenFields(qty) {
            if ($('#hidden-product-qty').length) {
                $('#hidden-product-qty').val(qty);
            }
        }
    }
});
