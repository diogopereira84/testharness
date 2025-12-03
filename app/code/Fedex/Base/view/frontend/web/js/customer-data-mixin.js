/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'mage/utils/wrapper',
    'jquery/jquery-storageapi',
    window.d198297_toggle ? 'Fedex_Base/js/localstorage-compress' : 'domReady!'
], function ($, wrapper, storage, tmp) {
    'use strict';

    if (
        window.d198297_toggle &&
        typeof(tmp) == 'undefined'
    ){
        require(['Fedex_Base/js/localstorage-compress'], function(localStorageCompress) {
            localStorageCompress.initialize()
        });
    }

    let mixin = {
        clearAll: function () {
            let storage = $.initNamespaceStorage('mage-cache-storage').localStorage;
            storage.removeAll();
        }
    };
    return function (target) {
        return wrapper.extend(target, mixin);
    };
});
