/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/lib/view/utils/dom-observer',
    'uiRegistry'
], function ($, alert, $dom, uiRegistry) {
    'use strict';

    /**
     * init
     * 
     * @returns void
     */
    function init(categoryRequestUrl) {

        $(".update-category").on("click", function () {
			let categoryId = $('.category_ids').val();
			if (categoryId == '') {
				alert({
					title: $.mage.__('Alert'),
					content: $.mage.__('Please select a category to process.'),
					actions: {
						always: function () { }
					}
				});
            } else {
				processRequest(categoryRequestUrl, categoryId);
			}
        });
    }

    // Read Data Request
    function processRequest(categoryRequestUrl, categoryId) {
        $.ajax({
            showLoader: true,
            url: categoryRequestUrl + '?form_key=' + window.FORM_KEY + '&isAjax=true',
            type: 'POST',
            data: {category_id: categoryId},
            dataType: 'json',
            async: true,
            success: function (data) {     
                alert({
                    title: $.mage.__('Success'),
                    content: $.mage.__('Category update has been queued successfully. It would take few minutes to process updates.'),
                    actions: {
                        always: function () { }
                    }
                });
            }
        });
    }

    return {
        init: init
    }
});
