/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 
require(["jquery", "domReady"],function($, domReady) {

    domReady(function() {
        let  current_page = $('.block-collapsible-nav-content li strong').html();
        if(!current_page) {
            current_page = $('.block-collapsible-nav-content li.current').text();
        }
        $('.title.block-collapsible-nav-title strong').html(current_page);
        $('.block.block-collapsible-nav').show();
        $(document).on('click', function (e) {
            let  element = $('.sidebar.sidebar-main .block-collapsible-nav');
            if ((!element.is(e.target) && element.has(e.target).length === 0) && $(window).width() < 1200) {
                element.find('.title.block-collapsible-nav-title.active').trigger('click');
            }
        });
    });
});
