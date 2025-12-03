require([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'fedex/storage',
    'mage/calendar',
    'uiRegistry',
    'Fedex_CatalogMvp/js/configure-popup/calendar',
    'Fedex_CatalogMvp/js/configure-popup/tags',
    'Fedex_CatalogMvp/js/configure-popup/catalog-handler',
    'Fedex_CatalogMvp/js/configure-popup/folder-handler'
], function ($, modal, fxoStorage, Calendar, uiRegistry, calendarModule, tagsModule, catalogHandler, folderHandler) {
    'use strict';

    window.isEditFlow = window.isEditFlow || false;
    window.tags = window.tags || [];

    var CatalogPopup = {
        init: function () {
            this.initModals();
            this.bindProductGridClick();
            this.bindDescriptionCharCount();
            this.bindSettingsValidation();
            this.bindEditFlow();
            
            // Initialize child modules
            catalogHandler.init(fxoStorage);
            folderHandler.init(fxoStorage, modal);
            calendarModule.init();
            tagsModule.init();
        },

        // -------------------- Initialize Modals --------------------
        initModals: function () {
            try {
                this.settingsModal = modal({
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    modalClass: 'setting-configure-popup',
                    title: $.mage.__('Settings'),
                    buttons: [{
                        text: $.mage.__('Close'),
                        class: 'action-secondary',
                        click: function () { this.closeModal(); }
                    }]
                }, $('#configure-popup'));

            } catch (error) {
                console.error('Error initializing modals:', error);
            }
        },

        // -------------------- Product Grid Click --------------------
        bindProductGridClick: function () {
            const self = this;
            $(document).on('click', '.custom-pop-up-product-grid .product-item', function (e) {
                if (!isE475721ToggleEnabled) {
                    return; // Exit if the toggle is disabled
                }
                window.isEditFlow = false;
                e.preventDefault();
                e.stopPropagation();

                try {
                    let $el = $(this),
                        productName = $el.data('index-name'),
                        productSku = $el.attr('data-index-id'),
                        productId = $el.attr('data-index-engineid');

                    $('#configure-popup')
                        .data('selected-sku', productSku)
                        .data('selected-name', productName)
                        .data('selected-id', productId);

                    const $nameWrapper = $('#configure-popup .shared-catalog-admin-setting-name');
                    $nameWrapper.find('#shared-catalog-admin-setting-name').val(productName);
                    $nameWrapper.hide();
                    self.prefillModalData();
                    $('#configure-popup').modal('openModal');
                    $('#custom-model-popup').modal('closeModal');

                } catch (err) {
                    console.error('Error handling product click:', err);
                }
            });
        },

        // -------------------- Helper: Flatten Category Options --------------------
        flattenCategoryOptions: function(options) {
            if (!Array.isArray(options)) return [];

            const self = this;
            return options.reduce(function(flattened, opt) {
                if (opt.value) {
                    flattened.push({ value: opt.value, label: opt.label });
                }
                if (Array.isArray(opt.optgroup)) {
                    return flattened.concat(self.flattenCategoryOptions(opt.optgroup));
                }
                return flattened;
            }, []);
        },

        // -------------------- Prefill Modal Data (Reusable) --------------------
        prefillModalData: function () {
            const contentElement = $('div[data-index="content"]');
            contentElement.find('.fieldset-wrapper-title[data-state-collapsible="closed"]').trigger('click');
            const tagsElement = $('div[data-index="search-engine-optimization"]');
            tagsElement.find('.fieldset-wrapper-title[data-state-collapsible="closed"]').trigger('click');
            const sharedCatalogElement = $('div[data-index="shared_catalog"]');
            sharedCatalogElement.find('.fieldset-wrapper-title[data-state-collapsible="closed"]').trigger('click');
            const productData = {
                productName: $("input[name='product[name]']").val() || '',
                startDate: $("input[name='product[start_date_pod]']").val() || '',
                endDate: $("input[name='product[end_date_pod]']").val() || '',
                description: $("textarea[name='product[catalog_description]']").val() || '',
                tags: $("input[name='product[related_keywords]']").val() || '',
            };
            let catalogId = '';
            let catalogName = '';
            const sharedCatalogComponent = uiRegistry.get('product_form.product_form.shared_catalog.shared_catalog');
            if (sharedCatalogComponent && sharedCatalogComponent.value().length) {
                catalogId = sharedCatalogComponent.value()[0];
                let options = typeof sharedCatalogComponent.options === 'function' ? sharedCatalogComponent.options() : [];
                let option = options.find(opt => String(opt.value) === String(catalogId));
                catalogName = option ? option.label : '';
            }
            if (catalogId) {
                fxoStorage.set('selectedSharedCatalogId', catalogId);
                $(document).trigger('fetchDiscountNumber');
            }

            let categoryId = '';
            let categoryName = '';
            const categoryComponent = uiRegistry.get('product_form.product_form.product-details.container_category_ids.category_ids');

            if (categoryComponent && typeof categoryComponent.value === 'function' && categoryComponent.value().length) {
                categoryId = categoryComponent.value()[0];

                let categoryOptions = typeof categoryComponent.options === 'function' ? categoryComponent.options() : [];
                let flatOptions = this.flattenCategoryOptions(categoryOptions);
                let matchedOption = flatOptions.find(function(opt) {
                    return String(opt.value) === String(categoryId);
                });
                categoryName = matchedOption ? matchedOption.label : '';

                if (categoryId) {
                    fxoStorage.set('selectedCategoryId', categoryId);
                    if (categoryName) {
                        fxoStorage.set('selectedCategoryName', categoryName);
                    }
                }
            }

            // Prefill fields in settings modal
            $('#shared-catalog-input').val(catalogName).attr('data-id', catalogId).trigger('input');
            if (productData.productName) {
                $('#shared-catalog-admin-setting-name').val(productData.productName);
            }
            $('#start-date').val(productData.startDate);
            if (productData.endDate) {
                $('#end-date').val(productData.endDate).prop('disabled', false);
                $('#no-end-date').prop('checked', false).trigger('change');
            } else {
                $('#end-date').val('').prop('disabled', true);
                $('#no-end-date').prop('checked', true).trigger('change');
            }
            if (productData.description) {
                $('#description').val(productData.description).trigger('input');
            }
            window.tags = [];
            if (productData.tags) {
                window.tags = productData.tags.split(',').map(function(tag) {
                    return tag.trim();
                }).filter(function(tag) {
                    return tag.length > 0;
                });
            }
            if (typeof window.renderTags === 'function') {
                window.renderTags();
            }
        },

        // -------------------- Description Character Count --------------------
        bindDescriptionCharCount: function () {
            const $textarea = $('#description, textarea.char-length-validation');
            const $caption = $('#description-caption');
            let MAX = 500;

            if ($textarea.length) {
                const attrMax = parseInt($textarea.first().attr('maxlength'), 10);
                if (!isNaN(attrMax) && attrMax > 0) {
                    MAX = attrMax;
                }
            }

            function updateCounter($input) {
                if (!$input || !$input.length) return;
                let val = $input.val() || '';
                if (val.length > MAX) {
                    $input.val(val.substring(0, MAX));
                    val = $input.val();
                }
                const remaining = MAX - val.length;
                if ($caption.length) {
                    $caption.text(remaining + ' characters left');
                }
            }
            $textarea.each(function () { updateCounter($(this)); });
            $textarea.off('input.charCount').on('input.charCount', function () {
                updateCounter($(this));
            });
            $(document).on('openModal', '#configure-popup', function () {
                $textarea.each(function () { updateCounter($(this)); });
            });

            $(document).on('click', '.mvp-catalog-edit-button', function () {
                setTimeout(function () {
                    $textarea.each(function () { updateCounter($(this)); });
                }, 50);
            });
        },

        // -------------------- Settings Validation --------------------
        bindSettingsValidation: function () {
            function validate() {
                var name = $('#shared-catalog-admin-setting-name').val().trim(),
                    catalog = $('#shared-catalog-input').val().trim(),
                    startDate = $('#start-date').val().trim(),
                    endDateDisabled = $('#end-date').prop('disabled'),
                    endDate = endDateDisabled || $('#end-date').val().trim().length > 0;

                $('.continue-setting').prop('disabled', !(name && catalog && startDate && endDate));
            }

            $(document).on('input change', '#shared-catalog-admin-setting-name, #shared-catalog-input, #start-date, #end-date', validate);
            $(document).on('change', '#no-end-date', function () { setTimeout(validate, 10); });
            $(document).on('openModal', '#configure-popup', validate);
            $(document).on('click', '.cancel-setting', function() {
                $('#configure-popup').modal('closeModal');
                if (window.isEditFlow === true) {
                    window.isEditFlow = false;
                    return;
                }
                $('#custom-model-popup').modal('openModal');
            });
            $(validate);
        },

        // -------------------- Edit Configure Flow --------------------
        bindEditFlow: function () {
            const self = this;
            $(document).on('click', ".mvp-catalog-edit-button", function(){
                if (!isE475721ToggleEnabled) {
                    return; // Exit if the toggle is disabled
                }
                window.isEditFlow = true;
                const $nameWrapper = $('#configure-popup .shared-catalog-admin-setting-name');
                $nameWrapper.show();
                self.prefillModalData();
                $('#configure-popup').modal('openModal');
            });
        }
    };

    $(function () { CatalogPopup.init(); });
});