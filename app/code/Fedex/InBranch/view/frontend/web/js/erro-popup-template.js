require([
    'jquery',
    'inBranchWarning',
    'Magento_Customer/js/customer-data',
    'mage/cookies',
    'domReady'
], function ($, inBranchWarning, customerData) {
         if (!(window.checkout?.is_retail || window.checkoutConfig?.isRetailCustomer)) {
            customerData.reload(['inBranchdata'], true);
        }
        let isInBranchProductExist;
        isInBranchProductExist = $.mage.cookies.get('isInBranchProductExist');
        if (isInBranchProductExist !== undefined && isInBranchProductExist == 1) {
            inBranchWarning.inBranchWarningPopup();
            $.cookie('isInBranchProductExist', '1', {expires: -1, path: '/'});
        }
        $(document).on("mvp_add_to_cart_end", function () {
            isInBranchProductExist = $.mage.cookies.get('isInBranchProductExist');
            if (isInBranchProductExist !== undefined && isInBranchProductExist == 1) {
                inBranchWarning.inBranchWarningPopup();
                /**
                 * For some reason Magento/JQuery cookie manager could not delete this one.
                 * Deleting it the hard way.
                 * @TODO investigate why?
                 */
                document.cookie = 'isInBranchProductExist=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;';
            }
        });
    }
);
