/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'Magento_Ui/js/grid/columns/actions'
], function (_, Actions) {
    'use strict';

    return Actions.extend({
        defaults: {
            bodyTmpl: 'Fedex_Company/companyusers/grid/cells/actions'
        },

        /**
         * Callback after click on element.
         *
         * @public
         */
        applyAction: function (parent) {
            parent.source().set('params.oldAdminId', '');
            parent.source().set('params.adminId', '');
            parent.source().set('params.adminEmail', '');

            switch (this.type) {
                case 'remove-admin':
                    parent.source().set('params.oldAdminId', this.recordId);
                    break;

                case 'make-admin':
                    let adminIndex = _.findIndex(parent.actions, (actionObj) => {
                        return actionObj.removeAdmin;
                    });

                    if (adminIndex !== -1) {
                        let oldAdminId = parent.rows[adminIndex].entity_id;
                        parent.source().set('params.oldAdminId', oldAdminId);
                    }

                    parent.source().set('params.adminId', this.recordId);
                    parent.source().set('params.adminEmail', parent.rows[this.rowIndex].email);
                    break;

                default:
                    return true;
            }

            return true;
        }
    });
});
