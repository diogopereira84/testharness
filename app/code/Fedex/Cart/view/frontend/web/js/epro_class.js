define([
    'jquery', 'domReady!'
],function($) {
    'use strict'

    return function (config, elm) {
        $(document).ready( function() {
            if(window.e383157Toggle){
                require([
                    'fedex/storage'
                ],function(fxoStorage) {
                    fxoStorage.set('pickup_enabled',config.isPickup);
                    fxoStorage.set('delivery_enabled',config.isDelivery);
                    fxoStorage.set('dunc_office_api_url',config.getDuncOfficeUrl);
                });
            }else{
                localStorage.setItem('pickup_enabled',config.isPickup);
                localStorage.setItem('delivery_enabled',config.isDelivery);
                localStorage.setItem('dunc_office_api_url',config.getDuncOfficeUrl);
            }
        });
    }
});
