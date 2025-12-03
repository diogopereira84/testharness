define([
    'jquery',
    'Magento_Ui/js/lib/view/utils/dom-observer',
    'uiRegistry',
], function ($, $dom, uiRegistry) {
    'use strict';

    var categorySharedCatalogsUrl = '';

    /**
     * Initialize
     * 
     * @returns void
     */
    function init(requestUrl) {
        // Initialized Url
        categorySharedCatalogsUrl = requestUrl;
        
        $dom.get('div[data-index="category_ids"] div.admin__action-multiselect-text', function (elem) {
            if (isProductAttributeSetPrintOnDemand()) {
                $(document).on('click', elem, function() {
                    let sharedCatalogElement = $('div[data-index="shared_catalog"]');
                    sharedCatalogElement.find('.fieldset-wrapper-title[data-state-collapsible="closed"]').trigger('click');

                    // when selecting category by searching
                    $dom.get('label.admin__action-multiselect-label', function (elems) {
                        $(elems).off('click').on('click', function() {
                            if ($(this).children('span').text() !== 'B2B Root Category') {
                                let categoryId = $(this).attr('data') === undefined ? $(this).find('span').eq(0).attr('data') : $(this).attr('data');
                                if (typeof categoryId != 'undefined') {
                                    getCategorySharedCatalogs(categoryId, $(this));
                                }
                            }
                        });
                    });
                });

            }
        });
    }

    function checkUncheckedSharedCatalog(sharedCatId, isSharedCatRemove) {
        const sharedCatalogComponent = uiRegistry.get('product_form.product_form.shared_catalog.shared_catalog');

        if (!sharedCatalogComponent) {
            return;
        }

        let sharedCatalogIds = [];
        const currentValue = sharedCatalogComponent.value();

        if (Array.isArray(currentValue)) {
            sharedCatalogIds = uniqBySharedCatalogIds(currentValue, JSON.stringify);
        } else if (typeof currentValue === 'number') {
            sharedCatalogIds = [currentValue];
        }

        const id = parseInt(sharedCatId);

        if (isSharedCatRemove) {
            const idx = sharedCatalogIds.indexOf(id);
            if (idx !== -1) {
                sharedCatalogIds.splice(idx, 1);
                sharedCatalogComponent.setSelected(sharedCatId.toString());
            }
        } else {
            if (!sharedCatalogIds.includes(id)) {
                sharedCatalogComponent.setSelected(sharedCatId.toString());
                sharedCatalogIds.push(id);
            }
        }

        const allOptions = sharedCatalogComponent.options || [];
        const selectedItems = allOptions.filter(opt =>
            sharedCatalogIds.includes(parseInt(opt.value))
        );

        selectedItems.sort((a, b) => a.label.localeCompare(b.label));
        const sortedIds = selectedItems.map(opt => parseInt(opt.value));

        sharedCatalogComponent.options.sort((a, b) => a.label.localeCompare(b.label));

        sharedCatalogComponent.setValue([]);
        sharedCatalogComponent.setValue(sortedIds);

        if (typeof sharedCatalogComponent.setOptions === 'function') {
            sharedCatalogComponent.setOptions(sharedCatalogComponent.options);
        }
    }


    /**
     * Remove duplicate ids
     * @param {*} a 
     * @param {*} key 
     * @returns 
     */
    function uniqBySharedCatalogIds(a, key) {
        var index = [];
        return a.filter(function (item) {
            var k = key(item);
            return index.indexOf(k) >= 0 ? false : index.push(k);
        });
    }

    /**
     * Get Category Shared catalogs
     * 
     * @param {number} categoryID - The ID of the category
     * @returns void
     */
    function getCategorySharedCatalogs(categoryId, browseCategorySelector) { // first time categoryId 89 element false
        $.ajax({
            url: `${categorySharedCatalogsUrl}?form_key=${window.FORM_KEY}&category_id=${categoryId}`,
            type: 'POST',
            dataType: 'json',
            data: {},
            showLoader: true,
            success: function (response) {
                let sharedCatId = typeof response.shared_catalog_id != undefined && response.shared_catalog_id != null ? response.shared_catalog_id: 0;
                
                // for add select shared catalog and add shared-catalog-id attribute in parent label
                if (sharedCatId != 0) {
                    browseCategorySelector.attr('shared-catalog-id', response.shared_catalog_id);
                    checkUncheckedSharedCatalog(response.shared_catalog_id, false);
                }
            },
            error: function (xhr, status, error) {
                // Handle the error response
                console.error(xhr.responseText);
            },
        });
    }

    /**
     * Checks if the current product's attribute set corresponds to the "Print On Demand" option.
     *
     * @returns {boolean} - True if the attribute set is "Print On Demand", otherwise false.
     */
    function isProductAttributeSetPrintOnDemand() {
        const productAttrSetSelector = uiRegistry.get('product_form.product_form.product-details.attribute_set_id');

        // Selected product attribute id if not string convert in string
        let selectedAttributeId = typeof productAttrSetSelector.value() == "string" ? productAttrSetSelector.value() : productAttrSetSelector.value().toString();
        
        let isPrintOnDemand = productAttrSetSelector.cacheOptions.plain.some(option => 
            option.label === 'PrintOnDemand' && option.value === selectedAttributeId
        );

        return isPrintOnDemand;
    }

    return {
        init: init
    };
});
