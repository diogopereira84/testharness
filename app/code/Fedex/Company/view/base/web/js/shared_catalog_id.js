define([
    'underscore',
    'Magento_Ui/js/form/element/select',
    'jquery',
    'domReady!'
], function (_, select, $) {
    'use strict';
    $(window).on('change',function(){
        let isAllowSharedCatalog = $("input[name='catalog_document[allow_shared_catalog]']").val();
        if(isAllowSharedCatalog == 1) {
            $('div[data-index="shared_catalog_id"]').addClass('_required');
        } else {
            $('div[data-index="shared_catalog_id"]').removeClass('_required');
        }
        $(document).on('click',function(){
            if(isAllowSharedCatalog == 1) {
                $('div[data-index="shared_catalog_id"]').addClass('_required');
            } else {
                $('div[data-index="shared_catalog_id"]').removeClass('_required');
            }
                
        });
      });
    
    return select;
});