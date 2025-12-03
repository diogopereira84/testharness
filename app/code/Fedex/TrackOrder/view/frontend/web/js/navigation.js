/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 
require(["jquery"],function($) {
    let current_page = $('.block-collapsible-nav-content li:contains("Track My Print Order")');
    current_page.addClass('current');
    $('.title.block-collapsible-nav-title strong').html(current_page.html());
    $('.block.block-collapsible-nav').show();
    $(document).on('click', function (e) {
        let element = $('.sidebar.sidebar-main .block-collapsible-nav');
        if ((!element.is(e.target) && element.has(e.target).length === 0) && $(window).width() < 1200) {
            element.find('.title.block-collapsible-nav-title.active').trigger('click');
        }
    });
});
