/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            importCompanyDataHandler: 'Fedex_Company/js/importcompanydata/import-comapany-data-handler',
            migrateLagecyCatalogDataHandler: 'Fedex_Company/js/lagecycatalog/migrate-lagecy-catalog-data-handler',
            userPreferenceFieldsDataHandler: 'Fedex_Company/js/userpreference/user-preference-fields-data-handler'
        }
    },
    config: {
        mixins: {
            'Magento_Ui/js/dynamic-rows/dynamic-rows': {
                'Fedex_Company/js/dynamic-rows/dynamic-rows-mixin': true
            },
            'Magento_Ui/js/grid/provider': {
                'Fedex_Company/js/companyusers/companyusers-mixin': true
            },
            'Magento_Company/js/edit/add-user': {
                'Fedex_Company/js/companyusers/change-admin-mixin': true
            }
        }
    }
};
