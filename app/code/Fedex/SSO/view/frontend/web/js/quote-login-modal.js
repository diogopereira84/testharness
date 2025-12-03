define([
    'jquery',
], function($){
    
    return function(config) {
        const options = {
            target: '#quote-login-popup',
            type: 'popup',
            ...config
        }

        $("#quote-login-popup").modal({
            type: "popup",      
            modalClass: 'fedex-ddt-modal',
            icon: "https://staging2.office.fedex.com/media/wysiwyg/Warning_Icon_Outline.png",
            buttons: [],
            closed: function () {
            }
        }).modal('openModal')
    }
});