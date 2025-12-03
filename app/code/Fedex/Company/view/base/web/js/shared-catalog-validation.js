require(
    [
        'Magento_Ui/js/lib/validation/validator',
        'jquery',
        'mage/translate'
], function(validator, $){
        validator.addRule(
            'shared-catalog-validation',
            function (value) {
                let sharedCatalogToggle = $("input[name='catalog_document[allow_shared_catalog]']").val();
                let isValid = true;
                if(sharedCatalogToggle == 1 && value == 0) {
                    isValid = false;
                }
                return isValid;
            },
            $.mage.__('This is a required field.')
        );
});