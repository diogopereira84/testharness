/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery'
], function ($) {
    'use strict';

    let mixin = {
        options: {
            provider: 'change_admin_grid.change_admin_grid_data_source'
        },

        /**
         * Handles changes of 'params' object for change admin grid provider
         */
        onParamsChange: function () {
            if (this.name === this.options.provider) {
                if (!this.firstLoad) {
                    this.reload({refresh: true});
                } else {
                    this.triggerDataReload = true;
                }
            } else {
                this._super();
            }
        },
    };

    return function (target) {
        return target.extend(mixin);
    }
});
