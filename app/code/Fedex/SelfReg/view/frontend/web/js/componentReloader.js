define([
    "uiRegistry"
], function (registry) {
    'use strict';
    return {
        reloadUIComponent: function (gridName, value) {
            if (gridName) {
                var params = [];
                var target = registry.get(gridName);
                if (target && typeof target === 'object') {
                    target.set('params.search', value);
                }
            }
        },
        reloadFilterUIComponent: function (gridName, value, permissionFilter = null) {
            if (gridName) {
                var params = [];
                var target = registry.get(gridName);
                if (target && typeof target === 'object') {
                    if (window.checkout.user_roles_permission) {
                        if (value) {
                            permissionFilter['status'] = value;
                        }
                        target.set('params.filter', permissionFilter);
                    } else {
                        target.set('params.filter', value);
                    }
                }
            }
        }
    };
});
