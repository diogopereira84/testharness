/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 require([
    'jquery',
    'mage/url'
],function($, url) {
    let  current_page = $('.block-collapsible-nav-content li.current').html();
    $('.title.block-collapsible-nav-title strong').html(current_page);
    $('.block.block-collapsible-nav').show();
    $(document).on('load', function (e) {
        let  element = $('.sidebar.sidebar-main.order-approval-review-orders .block-collapsible-nav');
        if ((!element.is(e.target) && element.has(e.target).length === 0) && $(window).width() < 1200) {
            element.find('.title.block-collapsible-nav-title.active').trigger('click');
        }
    });

    /**
     * Trigger search on click
     */
    $('#search-quote-btn').click(function() {
        triggerSearch();
    });

     /**
      * Decline toast msg hide
      */
     $('#decline_err_msg_close').click(function() {
         $('.decline-toast-msg').hide();
     });

    /**
      * Success and error toast msg hide
      */
     $('.title.block-collapsible-nav-title').click(function() {
        $(".b2b-order-error-toast-msg, .b2b-order-success-toast-msg").hide();
    });
     
     /**
      * Trigger to close of success toast message when space and enter key is pressed
      */
     $("#decline_err_msg_close").on('keypress',$.proxy(function (evt) {
         evt = (evt) ? evt : window.event;
         let charCode = (evt.which) ? evt.which : evt.keyCode;
         if (charCode == 13 || charCode == 32) {
             $("#decline_err_msg_close").trigger('click');
             return false;
         }
     }, this));

    /**
     * Trigger search and Reset Search result on keypress
     */
    $(document).on('keypress', '#order-search-textbox, .search-clear-icon', function (event) {
        let keyCode = event.keyCode ? event.keyCode : event.which;
        if(keyCode  == 13) {
            triggerSearch();
        }
        if($(this).hasClass('search-clear-icon') && keyCode == 13){
            resetSearch();
        }
    });

    /**
     * Reset Search result
     */
    $('#reset-cross, .reset-btn').click(function() {
        resetSearch();
    });

    /**
     * Trigger sorting when click Order Date column
     */
    $('#sort-by-order-date').click(function() {
        let _this = this;
        $('body').trigger('processStart');
        let redirectUrl = $(_this).attr("date-href");
        location.href = redirectUrl;
    });

    /**
     * trigger search by order id
     */
    function triggerSearch() {
        $('body').trigger('processStart');
        let orderNumber = $("#order-search-textbox").val().trim();
        let reviewOrderHistoryUrl = url.build('orderb2b/revieworder/history/');
        location.href = reviewOrderHistoryUrl+"?search="+orderNumber;
    }

    /**
     * Reset search result
     */
    function resetSearch(){
        $('body').trigger('processStart');
        let reviewOrderHistoryUrl = url.build('orderb2b/revieworder/history/');
        window.location.href = reviewOrderHistoryUrl;
    }
});
