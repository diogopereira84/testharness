var config = {
    map: {
        '*': {
            'peProductController': 'Fedex_ProductEngine/js/product-controller',
            'peProductTranslator': 'Fedex_ProductEngine/js/product-translator',
            'peProductDetailsController': 'Fedex_ProductEngine/js/product-details-controller',
            'peProductMenuController': 'Fedex_ProductEngine/js/product-menu-controller',
            'productEngineAttributes': 'Fedex_ProductEngine/js/product/view/productEngineAttributes',
            'peProductProductionController': 'Fedex_ProductEngine/js/product-production-controller',
        }
    },
    paths: {
        'peProductEngine': 'Fedex_ProductEngine/js/productEngine'
    },
    config: {
        mixins: {
            'Magento_Catalog/js/catalog-add-to-cart': {
                'Fedex_ProductEngine/js/product/view/bundle/catalogAddToCartBundleAttributes': true
            }
        }
    }
};
