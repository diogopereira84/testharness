/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
require([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'fedex/storage'
], function ($, modal, fxoStorage) {
    let loginModalOptions = {
        type: 'popup',
        innerScroll: true,
    };
    let isBid = window.isBid;
    if (isBid) {
        $(window).on('load', function () {
	    delete window.isBid;
            $("#login-register-popup").modal(loginModalOptions).modal('openModal');

        });
    }
});
