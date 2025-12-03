define([
    'jquery',
    'mage/url',
    'Magento_Customer/js/customer-data',
    'Fedex_Canva/js/model/canva',
    'loader'
],function($, url, customerData, canvaModel) {
    'use strict'
    var button = $('.canva_home_button');
    button.each(function(index, elm){
        var elmAnchor = $(elm).find('a');
        if (!window.techTitans_d203652) {
            if(elmAnchor.attr('href') !== '#') {
                elmAnchor.attr('href', '#');
            }
        }
        if(elmAnchor.attr('target')) {
            elmAnchor.removeAttr('target');
        }
    });
    button.on('click', function(event){
        event.preventDefault();
        $('body').loader('show');
        sessionStorage.setItem('canva-from-megamenu', true);
        sessionStorage.setItem('back-url', location.href);
        canvaModel.resetProcess(canvaModel.process.LISTING, null, null, null, '/canva');
        window.location = url.build('canva');
    });
});
