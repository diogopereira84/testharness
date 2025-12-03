/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
var config = {
    map: {
        '*': {
            'Magento_Catalog/js/options':'Fedex_ProductEngine/js/options',
            'peProductController': 'Fedex_ProductEngine/js/product-engine/product-controller',
            'peProductTranslator': 'Fedex_ProductEngine/js/product-engine/product-translator',
            'peProductDetailsController': 'Fedex_ProductEngine/js/product-engine/product-details-controller',
            'peProductMenuController': 'Fedex_ProductEngine/js/product-engine/product-menu-controller',
            'productEngineAttributes': 'Fedex_ProductEngine/js/product-engine/product/view/productEngineAttributes',
            'peProductProductionController': 'Fedex_ProductEngine/js/product-engine/product-production-controller',
        }
    },
    paths: {
        'peProductEngine': 'Fedex_ProductEngine/js/product-engine/productEngine'
    }
};
