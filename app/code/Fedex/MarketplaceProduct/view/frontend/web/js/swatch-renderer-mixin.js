define([
    'jquery',
    'tabSpecifications',
    'jquery-ui-modules/widget',
], function ($, tabSpecifications) {
    'use strict';

    return function (SwatchRenderer) {
        /**
         * Render tooltips by attributes (only to up).
         * Required element attributes:
         *  - data-option-type (integer, 0-3)
         *  - data-option-label (string)
         *  - data-option-tooltip-thumb
         *  - data-option-tooltip-value
         *  - data-thumb-width
         *  - data-thumb-height
         */
        $.widget('mage.SwatchRendererTooltip', {
            options: {
                delay: 200,                             //how much ms before tooltip to show
                tooltipClass: 'swatch-option-tooltip'  //configurable, but remember about css
            },

            /**
             * @private
             */
            _init: function () {
                var $widget = this,
                    $this = this.element,
                    $element = $('.' + $widget.options.tooltipClass),
                    timer,
                    $corner;

                if (!$element.length) {
                    $element = $('<div class="' + $widget.options.tooltipClass + '"><div class="title fedex-light fs-16 lh-24"></div><div class="corner"></div></div>');
                    $('body').append($element);
                }
                $corner = $element.find('.corner');

                $this.on('mouseenter', function () {
                    if ($this.hasClass('disabled')) {
                        timer = setTimeout(
                            function () {
                                var leftOpt = null, leftCorner = 0, left, $window;
                                $element.find('.title').text('This option is unavailable based on one or more of your selections');

                                leftOpt = $this.offset().left;
                                left = leftOpt + $this.width() / 2 - $element.width() / 2;
                                $window = $(window);

                                // the numbers (5 and 5) is magick constants for offset from left or right page
                                if (left < 0) {
                                    left = 5;
                                } else if (left + $element.width() > $window.width()) {
                                    left = $window.width() - $element.width() - 5;
                                }

                                // the numbers (6, 4 and 3) are magick constants for offset tooltip
                                leftCorner = 0;
                                if ($element.width() < $this.width()) {
                                    leftCorner = $element.width() / 2 - 3;
                                } else {
                                    leftCorner = (leftOpt > left ? leftOpt - left : left - leftOpt) + ($this.width() / 2) + 4;
                                }
                                $corner.css({
                                    left: leftCorner
                                });
                                //CSS method call is split into two in order to address an esge case
                                $element.css({ left: left })
                                    .css({ top: $this.offset().top - $element.height() - $corner.height() - 35 })
                                    .show();
                            },
                            $widget.options.delay
                        );
                    }
                });

                $this.on('mouseleave', function () {
                    $element.hide();
                    clearTimeout(timer);
                });

                $(document).on('tap', function () {
                    $element.hide();
                    clearTimeout(timer);
                });

                $this.on('tap', function (event) {
                    event.stopPropagation();
                });
            }
        });

        $.widget('mage.SwatchRenderer', SwatchRenderer, {

            options: {
                classes: {
                    configurableBody: 'page-product-configurable',
                }
            },

            /**
             * @inheritDoc
             */
            _init: function () {
                this._super();

                var self = this;

                this.specificationsModule = tabSpecifications();

                if ($('body').hasClass(this.options.classes.configurableBody)
                    && this.options.jsonConfig !== ''
                    && window.offerData
                ) {

                    $(document).on('change', '#product-options-wrapper .swatch-input', function (event) {
                        self.updateQuantityConstraintsFromSelection();
                    });

                    this._preselectFirstChild();
                }

                this.specificationsModule.init();

                this._setSwatchColorCssVariables();
            },

            /**
             * Finds an offer in the provided data that matches all target attributes
             * @param {Object} data - The offer data to search through
             * @param {Object} targetAttributes - The attributes to match against
             * @returns {Object|null} - The matching offer or null if not found
             */
            findOfferByAttributes: function (data, targetAttributes) {
                // Iterate through all offers in the data
                for (const [key, offer] of Object.entries(data)) {
                    if (!offer['min-qty'] || !offer['max-qty']){
                        continue; // Skip offers without quantity constraints
                    }
                    // Check if all target attributes match the offer's attributes
                    const attributesMatch = Object.entries(targetAttributes).every(([attrKey, attrValue]) => {
                        return offer.attributes[attrKey] === attrValue;
                    });

                    if (attributesMatch) {
                        return {
                            'min-qty': offer['min-qty'],
                            'max-qty': offer['max-qty'],
                            'offer-id': offer['offer-id'], // Including offer-id for reference
                            'final-price': offer['final-price'] // Including price for reference
                        };
                    }
                }

                // Return null if no matching offer is found
                return null;
            },

            /**
             * Updates the quantity input min/max constraints based on the currently selected options
             */
            updateQuantityConstraintsFromSelection: function() {
                const $swatchAttributes = $('#product-options-wrapper .swatch-attribute');
                let selectedOptions = {};

                // Collect all selected attribute options
                $swatchAttributes.each(function () {
                    const attributeId = $(this).attr('data-attribute-id');
                    const optionSelected = $(this).attr('data-option-selected').toString();

                    if (optionSelected) {
                        selectedOptions[attributeId] = optionSelected;
                    }
                });

                // Find matching offer from offer data
                const foundOffer = this.findOfferByAttributes(window.offerData, selectedOptions);

                // Apply quantity constraints if an offer was found
                if (foundOffer) {
                    const $qtyInput = $('#qty');
                    $qtyInput.attr('min', foundOffer['min-qty']);
                    $qtyInput.attr('max', foundOffer['max-qty']);
                    // dispatch new event
                    const event = new CustomEvent('qty_updated', {
                        detail: { min: foundOffer['min-qty'], max: foundOffer['max-qty'] } // optional data
                    });
                    document.getElementById('qty').dispatchEvent(event);
                }
            },

            /**
             * Set CSS custom properties for all color swatches
             * @private
             */
            _setSwatchColorCssVariables: function () {
                this.element.find('.swatch-attribute.color .swatch-option.color').each(function () {
                    var $swatch = $(this);
                    var backgroundColor = $swatch.data('option-tooltip-value');

                    if (backgroundColor) {
                        $swatch.get(0).style.setProperty('--swatch-color', backgroundColor);
                    }
                });
            },

            /**
             * Preselect options
             * @private
             */
            _preselectFirstChild() {
                var options = this.options.classes;

                if (!options) {
                    return;
                }

                var swatches = this.element.find('.' + options.attributeClass + ' .' + options.attributeOptionsWrapper);
                $.each(swatches, function (index, swatch) {
                    var swatchOption = $(swatch).find('.' + options.optionClass).first();
                    if (swatchOption.length && !swatchOption.hasClass('selected')) {
                        swatchOption.trigger('click');
                    }
                });
            },

            /**
             * @private
             */
            _create: function () {
                var options = this.options,
                    gallery = $('[data-gallery-role=gallery-placeholder]', '.column.main'),
                    productData = this._determineProductData(),
                    $main = productData.isInProductView ?
                        this.element.parents('.column.main') :
                        this.element.parents('.product-item-info');

                if (productData.isInProductView) {
                    gallery.data('gallery') ?
                        this._onGalleryLoaded(gallery) :
                        gallery.on('gallery:loaded', this._onGalleryLoaded.bind(this, gallery));
                } else {
                    options.mediaGalleryInitial = [{
                        'img': $main.find('.product-image-photo').attr('src')
                    }];
                }

                this.productForm = this.element.parents(this.options.selectorProductTile).find('form:first');
                this.inProductList = this.productForm.length > 0;
            },
            /**
             * Render swatch options by part of config
             *
             * @param {Object} config
             * @param {String} controlId
             * @returns {String}
             * @private
             */
            _RenderSwatchOptions: function (config, controlId) {
                let optionConfig = this.options.jsonSwatchConfig[config.id],
                    optionClass = this.options.classes.optionClass,
                    sizeConfig = this.options.jsonSwatchImageSizeConfig,
                    moreLimit = parseInt(this.options.numberToShow, 10),
                    moreClass = this.options.classes.moreButton,
                    moreText = this.options.moreButtonText,
                    countAttributes = 0,
                    html = '';

                if (!this.options.jsonSwatchConfig.hasOwnProperty(config.id)) {
                    return '';
                }

                $.each(config.options, function (index) {
                    var id,
                        type,
                        value,
                        thumb,
                        label,
                        width,
                        height,
                        attr;

                    if (!optionConfig.hasOwnProperty(this.id)) {
                        return '';
                    }

                    // Add more button
                    if (moreLimit === countAttributes++) {
                        html += '<a href="#" class="' + moreClass + '"><span>' + moreText + '</span></a>';
                    }

                    id = this.id;
                    type = parseInt(optionConfig[id].type, 10);
                    value = optionConfig[id].hasOwnProperty('value') ?
                        $('<i></i>').text(optionConfig[id].value).html() : '';
                    thumb = optionConfig[id].hasOwnProperty('thumb') ? optionConfig[id].thumb : '';
                    width = _.has(sizeConfig, 'swatchThumb') ? sizeConfig.swatchThumb.width : 110;
                    height = _.has(sizeConfig, 'swatchThumb') ? sizeConfig.swatchThumb.height : 90;
                    label = this.label ? $('<i></i>').text(this.label).html() : '';
                    attr =
                        ' id="' + controlId + '-item-' + id + '"' +
                        ' index="' + index + '"' +
                        ' aria-checked="false"' +
                        ' aria-describedby="' + controlId + '"' +
                        ' tabindex="0"' +
                        ' data-option-type="' + type + '"' +
                        ' data-option-id="' + id + '"' +
                        ' data-option-label="' + label + '"' +
                        ' aria-label="' + label + '"' +
                        ' role="option"' +
                        ' data-thumb-width="' + width + '"' +
                        ' data-thumb-height="' + height + '"';

                    attr += thumb !== '' ? ' data-option-tooltip-thumb="' + thumb + '"' : '';
                    attr += value !== '' ? ' data-option-tooltip-value="' + value + '"' : '';

                    if (!this.hasOwnProperty('products') || this.products.length <= 0) {
                        attr += ' data-option-empty="true"';
                    }

                    if (type === 0) {
                        // Text
                        html += '<div class="' + optionClass + ' text" ' + attr + '>' + (value ? value : label) +
                            '</div>';
                    } else if (type === 1) {
                        // Color
                        html += '<div class="' + optionClass + ' color" ' + attr +
                            ' style="background: ' + value +
                            ' no-repeat center; background-size: initial;">' + '' +
                            '</div>';
                    } else if (type === 2) {
                        // Image
                        html += '<div class="' + optionClass + ' image" ' + attr +
                            ' style=" --bg-image: url(' + value + ');">' + '' +
                            '</div>';
                    } else if (type === 3) {
                        // Clear
                        html += '<div class="' + optionClass + '" ' + attr + '></div>';
                    } else {
                        // Default
                        html += '<div class="' + optionClass + '" ' + attr + '>' + label + '</div>';
                    }
                });

                return html;
            },

            /**
             * Event for swatch options
             *
             * @param {Object} $this
             * @param {Object} $widget
             * @private
             */
            _OnClick: function ($this, $widget) {
                if ($this.hasClass('disabled')) {
		            return;
	            }

	            const $parent = $this.parents('.' + $widget.options.classes.attributeClass),
		            $wrapper = $this.parents('.' + $widget.options.classes.attributeOptionsWrapper),
		            $label = $parent.find('.' + $widget.options.classes.attributeSelectedOptionLabelClass),
		            attributeId = $parent.data('attribute-id'),
		            checkAdditionalData = JSON.parse(this.options.jsonSwatchConfig[attributeId]['additional_data']),
		            $priceBox = $widget.element.parents($widget.options.selectorProduct).find(this.options.selectorProductPrice);

	            let $input = $parent.find('.' + $widget.options.classes.attributeInput);

	            // Use product form input if rendering on product list
	            if ($widget.inProductList) {
		            $input = $widget.productForm.find('.' + $widget.options.classes.attributeInput + '[name="super_attribute[' + attributeId + ']"]');
	            }

	            // Handle selection toggle
	            if (!$this.hasClass('selected')) {
		            $parent.attr('data-option-selected', $this.data('option-id')).find('.selected').removeClass('selected');
		            $label.text($this.data('option-label'));
		            $input.val($this.data('option-id'));
		            $input.attr('data-attr-name', this._getAttributeCodeById(attributeId));
		            $this.addClass('selected');
		            $widget._toggleCheckedAttributes($this, $wrapper);
	            }

	            $widget._Rebuild();

                // Auto-select valid combination if the first or second swatch group was clicked
                const attributeIdStr = String(attributeId),
                    firstAttributeIdStr = String($widget.options.jsonConfig.attributes[0].id),
                    secondAttributeIdStr = $widget.options?.jsonConfig?.attributes?.[1]?.id
                        ? String($widget.options.jsonConfig.attributes[1].id)
                        : null;
                let attributeAutoSelect = [firstAttributeIdStr];

                if (secondAttributeIdStr != null) {
                    attributeAutoSelect.push(secondAttributeIdStr);
                }

                if (_.contains(attributeAutoSelect, attributeIdStr)) {
		            const selectedOptionId = $this.data('option-id');
		            const attributes = $widget.options.jsonConfig.attributes;
		            const selectedMap = {};
		            selectedMap[attributeIdStr] = selectedOptionId;

		            // Get products associated with the selected first attribute option
		            const startingProductIds = $widget.optionsMap[attributeIdStr]?.[selectedOptionId]?.products || [];

		            // Recursive function to build a valid combination
		            function findCombination(index, selection, currentProductIds) {
			            if (index >= attributes.length) {
				            return selection;
			            }

			            const attr = attributes[index];
			            const attrId = String(attr.id);

			            // Skip already selected attributes
			            if (selection.hasOwnProperty(attrId)) {
				            return findCombination(index + 1, selection, currentProductIds);
			            }

			            const options = attr.options;
			            for (let i = 0; i < options.length; i++) {
				            const opt = options[i];
				            const optProductIds = $widget.optionsMap[attrId]?.[opt.id]?.products || [];
				            const intersected = _.intersection(currentProductIds, optProductIds);

				            if (intersected.length > 0) {
					            const newSelection = Object.assign({}, selection);
					            newSelection[attrId] = opt.id;

					            const result = findCombination(index + 1, newSelection, intersected);
					            if (result) {
						            return result;
					            }
				            }
			            }
			            return null; // No valid combination found
		            }

		            const validSelection = findCombination(0, selectedMap, startingProductIds);

		            // Trigger swatch clicks for remaining attributes
		            if (validSelection) {
			            Object.entries(validSelection).forEach(function ([attrId, optionId]) {
				            if (String(attrId) === attributeIdStr) return;
				            const $targetSwatch = $widget.element.find('.swatch-attribute[data-attribute-id="' + attrId + '"]').find('.swatch-option[data-option-id="' + optionId + '"]');
				            if ($targetSwatch.length && !$targetSwatch.hasClass('selected')) {
					            $targetSwatch.trigger('click');
				            }
			            });
		            }
	            }

	            // Update price
	            if ($priceBox.is(':data(mage-priceBox)')) {
		            $widget._UpdatePrice();
	            }

	            // Update MSRP and media
	            $(document).trigger('updateMsrpPriceBlock', [this._getSelectedOptionPriceIndex(), $widget.options.jsonConfig.optionPrices, $priceBox]);

	            if (parseInt(checkAdditionalData['update_product_preview_image'], 10) === 1) {
		            $widget._loadMedia();
	            }

	            $input.trigger('change');

                /* ----------------------------------------------------
                | Product Specifications Tab.
                | Populates the tab with the specifications
                | of the selected variation.
                |------------------------------------------------------ */
                let productVariationsSku = this.options.jsonConfig.sku;

                // Return the SKU of the clicked product
                const clickedProductId = $widget.getProductId();
                const clickedProductSku = productVariationsSku[clickedProductId];

                this.specificationsModule.update(clickedProductSku);

                if($('.product-unavailability-message').hasClass('d_228743')){
                    this.checkProductAvailability(clickedProductSku);
                }
            },

            checkProductAvailability(clickedProductSku) {
                if (window.offerData[clickedProductSku] === undefined) {
                    $('.product-unavailability-message').addClass('d-flex');
                    $("#qty-box, #price-box-2, #add-to-cart-button").hide();
                    $(".product-info-main hr").last().hide();
                    return;
                }

                $('.product-unavailability-message').removeClass('d-flex');
                $("#qty-box, #price-box-2, #add-to-cart-button").show();
                $(".product-info-main hr").last().show();
            },

            /**
             * Rebuild container
             *
             * @private
             */
            _Rebuild: function () {
                const $widget = this,
                    controls = $widget.element.find('.' + $widget.options.classes.attributeClass + '[data-attribute-id]'),
                    selected = controls.filter('[data-option-selected]'),
                    firstAttrId = Number($widget.options.jsonConfig.attributes[0].id),
                    secondAttrId = $widget.options?.jsonConfig?.attributes?.[1]?.id
                        ? Number($widget.options.jsonConfig.attributes[1].id)
                        : null;

                $widget._Rewind(controls);

                if (selected.length <= 0) {
                    return;
                }

                controls.each(function () {
                    const $attributeContainer = $(this),
                        attributeId = $attributeContainer.data('attribute-id'),
                        matchingProducts = $widget._CalcProducts(attributeId);

                    //Don't disable first attribute
                    if (attributeId === firstAttrId) {
                        return;
                    }

                    //Don't disable second attribute if first attribute has only one option
                    if (secondAttrId && Object.keys($widget.optionsMap[firstAttrId]).length === 1 && attributeId === secondAttrId) {
                        return;
                    }

                    $attributeContainer.find('[data-option-id]').each(function () {
                        const $optionElement = $(this),
                            optionId = $optionElement.data('option-id');

                        if (!$widget.optionsMap.hasOwnProperty(attributeId) ||
                            !$widget.optionsMap[attributeId].hasOwnProperty(optionId) ||
                            $optionElement.hasClass('selected') ||
                            $optionElement.is(':selected')) {
                            return;
                        }

                        if (_.intersection(matchingProducts, $widget.optionsMap[attributeId][optionId].products).length <= 0) {
                            $optionElement.attr('disabled', true).addClass('disabled');
                        }
                    });
                });
            }
        });

        return $.mage.SwatchRenderer;
    };
});
