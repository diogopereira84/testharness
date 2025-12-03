/**
 * @api
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'jquery'
], function (Element, $) {
    'use strict';
    return Element.extend({
        getRemoteValue: function (ui,e) {
            this.value(e.target.value); // update value
            delete window.isValidLocation; // reset validation
            let locationId = this.value();
            let element = this;
            if (locationId.length === 4) {
                $.when(
                    $.ajax({
                        url: window.locationServiceUrl,
                        async: true,
                        showLoader: true,
                        data: {
                            'locationId': locationId
                        },
                        type: 'POST',
                        dataType: 'json'
                    })
                ).then(function (result, textStatus, jqXHR) {
                    if (result.Id && result.Id.length > 0) {
                        window.isValidLocation = result.Id;
                    } else {
                        window.isValidLocation = false;
                    }
                    element.validate();
                });
            } else {
                element.validate();
            }
        }
    });
});
