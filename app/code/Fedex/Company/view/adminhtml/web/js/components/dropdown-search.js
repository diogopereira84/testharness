/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_B2b/js/form/element/ui-group',
], function (UiGroup) {
    'use strict';

    return UiGroup.extend({

        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();
        },
    });
});
