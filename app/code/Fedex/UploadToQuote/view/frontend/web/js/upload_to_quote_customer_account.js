/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(["jquery"],
    function (
        $
    ) {
        "use strict";
        $(window).on('load', function () {
            let xmenUploadToQuote = typeof (window.checkout.xmen_upload_to_quote) != 'undefined' && window.checkout.xmen_upload_to_quote != null ? window.checkout.xmen_upload_to_quote : false;
            if (xmenUploadToQuote) {
                $(".my-quotes-epro-sidebar").css({'display' : 'block'});

                // If my-account-consistency is enabled
                // we need to add display block for my-quotes-epro-sidebar parent
                if ($(".account.my-account-nav-consistency").length) {
                    $(".my-quotes-epro-sidebar").parent().css({'display' : 'block'});
                }
            }
        });
    });
