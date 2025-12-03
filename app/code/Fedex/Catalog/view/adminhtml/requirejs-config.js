var config = {
    map: {
        '*': {
            categoryCatalogRenderSelection: 'Fedex_Catalog/js/category-catalog-render-selection'
        }
    },
    config: {
        mixins: {
            'mage/validation': {
                'Fedex_Catalog/js/validator/json': true
            },
            'Magento_Ui/js/form/form': {
                'Fedex_Catalog/js/form/form-mixin': true
            }
        }
    }
};
