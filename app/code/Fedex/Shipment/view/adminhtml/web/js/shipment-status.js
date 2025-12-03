/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
require(["jquery"], function ($) {
    $(window).on('load',function () {
	let shipmentValue = $("input[name='value']").val();
	let shipmentKey = $("input[name='key']").val();
	if(shipmentValue) {
            $("input[name='value']").attr("readonly","readonly");
	}
	if(shipmentKey) {
	    $("input[name='key']").attr("readonly","readonly");
	}
    });
});
