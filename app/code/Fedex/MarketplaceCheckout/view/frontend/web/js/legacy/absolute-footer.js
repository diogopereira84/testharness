require([
        'jquery',
        'Magento_Customer/js/customer-data',
        'fedex/storage'
    ], function($, customerData, fxoStorage) {

    customerData.reload(['customer','cart','messages'], true);

    var exeCount = true;
    $(window).on('resize', function() {
        exeCount = true;
        if ($(window).width() < 1024 && exeCount) {
            subMenuDropDown($);
            exeCount = false;
        }
    });
    $(window).on('load', function() {
        if (exeCount) {
            subMenuDropDown($);
            exeCount = false;
        }
    });

    function subMenuDropDown($) {
        $('.md-top-menu-items div[data-content-type=column]').each(function(){
            if($(this).find('.block-title').length == 0) {
                $(this).find('.block-content').show();
            }
        });
        $('.megamenu-primary-menu .menu-container.horizontal-menu .block-title').each(function(){
            $(this).off('click');
            $(this).on('click', function() {
                var blocktitle = $(this).parent().next().find('.block-title');
                if (!blocktitle.length) {
                    $(this).parent().next().find('.block-content').slideToggle();
                }
                $(this).next().slideToggle();
            });
        });
    }

    $(document).ready(function() {
        let successUrl;
        if(window.e383157Toggle){
            successUrl = fxoStorage.get("successUrl");
        }else{
            successUrl = localStorage.getItem("successUrl");
        }
        if (successUrl) {
            if (successUrl != window.location.href && !window.location.pathname.includes('nuance.html')) {
                if(window.e383157Toggle){
                    fxoStorage.clearAll();
                }else{
                    localStorage.clear();
                }
            }
        }
    });
});
