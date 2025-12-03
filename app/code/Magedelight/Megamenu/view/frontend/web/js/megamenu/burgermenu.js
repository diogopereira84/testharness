/*global $ */
require(['jquery'], function($) {
    $(document).ready(function() {
        $('.md-menu-close-btn').on('click',function(){
           $('html').removeClass('nav-open');

        });

        /* Fix issue for Mobile when click on the menu it is close every time. */
        /*$('body').on("click", function(e) {
            if ($('html').hasClass('nav-open')){
                jQuery('html').removeClass('nav-open');
            }
        });*/
    });
});
