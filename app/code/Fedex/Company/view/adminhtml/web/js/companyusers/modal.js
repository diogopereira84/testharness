/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'Magento_Ui/js/modal/modal-component',
    'Magento_Ui/js/lib/spinner'
], function ($, _, Modal, loader) {
    'use strict';

    return Modal.extend({
        defaults: {
            gridProvider: 'name = change_admin_grid.change_admin_grid_data_source, ns = change_admin_grid',
            pagingComponent: 'name = change_admin_grid.change_admin_grid.listing_top.listing_paging, ns = change_admin_grid',
            searchComponent: 'name = change_admin_grid.change_admin_grid.listing_top.fulltext, ns = change_admin_grid',
            modules: {
                saveAdminProvider: '${ $.saveAdminProvider }',
                gridProvider: '${ $.gridProvider }',
                pagingComponent: '${ $.pagingComponent }',
                searchComponent: '${ $.searchComponent }',
            },
        },

        /**
         * Modal cancel handler for change admin grid
         */
        actionCancel: function () {
            this._resetGrid();
            this._super();
        },

        /**
         * Change current selected user to admin on Save Admin button click
         */
        changeAdminActive: function () {
            let savedAdminId = this.gridProvider().get('params.adminId');
            if (savedAdminId) {
                loader.get(this.saveAdminProvider().formName).show();
                this.gridProvider().set('params.savedAdminId', savedAdminId);
                let adminEmail = this.gridProvider().get('params.adminEmail');
                this.getSavedAdminData(adminEmail);
            }
            this._resetGrid();
            this.closeModal();
        },

        /**
         * Get new admin data via email
         *
         * @param {string} adminEmail 
         */
        getSavedAdminData: function(adminEmail) {
            this.saveAdminProvider().clearValidationParams();
            this.saveAdminProvider().value(adminEmail);

            //jscs:disable requireCamelCaseOrUpperCaseIdentifiers
            let data = {
                email: adminEmail,
                companyId: this.saveAdminProvider().companyId,
                website_id: this.saveAdminProvider().source.get('data.company_admin.website_id')
            };
            //jscs:enable requireCamelCaseOrUpperCaseIdentifiers

            $.ajax({
                url: this.saveAdminProvider().getCustomerDataUrl,
                type: 'get',
                data: data,
                dataType: 'json',
                context: this,
                async: true
            }).done(this.onGetSavedAdminData);
        },

        /**
         * Verify new admin data and update fieldset source.
         *
         * @param {Object} data
         */
        onGetSavedAdminData: function(data) {
            this.saveAdminProvider().companyAdmin = this.saveAdminProvider()._normalizeAdminData(data);

            if (_.isObject(data)) {
                _.extend(this.saveAdminProvider().validationParams, data);
            }
            loader.get(this.saveAdminProvider().formName).hide();

            if (data.error || !this.saveAdminProvider().isValid()) {
                return false;
            }

            this.saveAdminProvider().updateSource();
        },

        /**
         * Resets Change Company Admin grid to default state
         */
        _resetGrid: function() {
            this.gridProvider().set('params.oldAdminId', '');
            this.gridProvider().set('params.adminId', '');
            this.gridProvider().set('params.adminEmail', '');
            this.gridProvider().set('params.sorting', {});
            this.pagingComponent().setPage(1);
            this.pagingComponent().pageSize = 20;
            this.searchComponent().clear();
            this.searchComponent().inputValue = '';
        }
    });
});
