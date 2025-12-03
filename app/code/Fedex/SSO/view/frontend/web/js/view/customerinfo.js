/**
* Copyright © Fedex, Inc. All rights reserved.
* See COPYING.txt for license details.
*/

define([
    'jquery',
    'uiComponent',
    'mage/url'
], function ($, Component, url) {
    'use strict';
    
    return Component.extend({
        
        /** @inheritdoc */
        initialize: function () { 
            this._super();
            var customShppingInfo =  url.build('fcl/customer/customershppinginfo');
            jQuery(".loading-mask").show();
            setTimeout(function () {
                $.ajax({
                    type: "POST",
                    enctype: "multipart/form-data",
                    url: customShppingInfo,
                    data: [],
                    processData: false,
                    contentType: false,
                    cache: false
                }).done(function (data) {
                    $("#defaule_shipping_address").html(data);
                    $('.hide-firstname, .hide-lastname, .hide-email').css('display', 'none');
                });

                var customContactInfo =  url.build('fcl/customer/customercontactinfo');
                $.ajax({
                    type: "POST",
                    enctype: "multipart/form-data",
                    url: customContactInfo,
                    data: [],
                    processData: false,
                    contentType: false,
                    cache: false,
                    showLoader: true
                }).done(function (data) {
                    if (data) {
                        $(".box-information .box-content").html(data);
                    }
                });
            }, 2000);
        }
    });
});
