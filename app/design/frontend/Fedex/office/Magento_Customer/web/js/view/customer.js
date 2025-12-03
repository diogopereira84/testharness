/**
* Copyright © Magento, Inc. All rights reserved.
* See COPYING.txt for license details.
*/

define([
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data'
], function ($, Component, customerData) {
    'use strict';

    return Component.extend({
        /** @inheritdoc */
        initialize: function () {
            this._super();

            this.customer = customerData.get('customer');
        },
         /** @inheritdoc */
        closeReorderAlert: function () {
            $('.reorder-close-icon').trigger('click');
        }
    });
});
