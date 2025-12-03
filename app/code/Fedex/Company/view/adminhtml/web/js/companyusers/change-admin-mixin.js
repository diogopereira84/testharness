/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery'
], function ($) {
    'use strict';

    let mixin = {
        defaults: {
            gridProvider: 'name = change_admin_grid.change_admin_grid_data_source, ns = change_admin_grid',
            modules: {
                gridProvider: '${ $.gridProvider }'
            },
            changeAdminScope: 'data.company_admin.email'
        },

        /**
         * Update the form data source and check to see if change admin grid should be updated
         */
        updateSource: function () {
                if (this.dataScope === this.changeAdminScope) {
                    let currentAdminGrid = this.gridProvider()?.get('params.savedAdminId');
                    let currentAdminForm = this.companyAdmin.customer;
                    if (currentAdminGrid !== currentAdminForm) {
                        this.gridProvider()?.set('params.savedAdminId', currentAdminForm);
                    }
                }
            if (this.companyAdmin.gender === '' || this.companyAdmin.gender == null || this.companyAdmin.gender == undefined) {
                this.companyAdmin.gender = window.genderDefaultOption;
            }
            this._super();
        },
    };

    return function (target) {
        return target.extend(mixin);
    }
});
