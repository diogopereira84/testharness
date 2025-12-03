/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    "jquery",
    "dateRangePicker",
    'mage/url',
    'fedex/storage'
], function ($,dateRangePicker, url,fxoStorage) {
    "use strict";
    $(window).on('load', function () {
        $(".filter-btn").on('click', function() {
            $(".quote-history-filter").toggle('slow');
        });
        let queryParam = window.location.search;
        if (queryParam.match("dataset=2") !=null && queryParam.match("dataset=2")) {
            $(".order-history").addClass("tab-highlighter");
            $(".in-progress").removeClass("tab-highlighter");
        }
        $('.history-link, .inprogress-link').click(function() {
            if (!$(this).hasClass("tab-highlighter") && ($(this).hasClass("history-link") || $(this).hasClass("inprogress-link"))) {
                $('body').trigger('processStart');
            }
            if (!$(this).hasClass("tab-highlighter") && $(this).hasClass("inprogress-link")) {
                $(".in-progress").addClass("tab-highlighter");
                $(".order-history").removeClass("tab-highlighter");
            } else if (!$(this).hasClass("tab-highlighter") && $(this).hasClass("history-link")) {
                $(".order-history").addClass("tab-highlighter");
                $(".in-progress").removeClass("tab-highlighter");
            }
        });
    });

    let urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search') && urlParams.get('search')) {
        $('#search-quote-input').val(window.e383157Toggle
            ? fxoStorage.get('searchString')
            : localStorage.getItem('searchString'));
    } else {
        $('#search-quote-input').val('');
    }

    /**
     * Trigger search on click
     */
    $('#search-quote-btn').click(function(e){
        triggerSearch();
    });

    /**
     * Trigger search and Reset Search result on keypress
     */
    $(document).on('keypress', '#search-quote-input, .search-clear-icon', function (event) {
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
    $('#reset-cross, .reset-btn').click(function(e){
        resetSearch();
    });

    /**
     * To sort by columns
     */
    $('.sort-by-column').click(function(e){
        let orderBy = $(this).attr('orderby');
		let sortBy = $(this).attr('sortby');
        triggerSort(orderBy, sortBy);
    });

    /**
     * trigger sort by given column
     */
     function triggerSort(orderBy, sortBy) {
        $('body').trigger('processStart');
        let dataSet =  $('#data_set').val();
        let historyUrl = url.build('uploadtoquote/index/quotehistory/?');
        if (dataSet) {
            historyUrl = historyUrl+"dataset="+dataSet+"&";
        }
        historyUrl = historyUrl+"sortby="+sortBy+"&orderby="+orderBy
        window.location.href = historyUrl;
     }

    /**
     * trigger search by quote id
     */
    function triggerSearch() {
       let isValid = validateSearchInput();
       let searchQueryVal = $('#search-quote-input').val();
        if (isValid) {
            $('body').trigger('processStart');
            let searchQuery = '?search=' + searchQueryVal;
            if (window.location.href.indexOf("dataset") > -1) {
                searchQuery = '?dataset=2&search=' + searchQueryVal;
            }
            if (window.e383157Toggle) {
                fxoStorage.set('searchString', searchQueryVal);
            } else {
                localStorage.setItem('searchString', searchQueryVal);
            }
            let searchUrl = window.location.href.split('?')[0] + searchQuery;
            window.location.href = searchUrl;
        }
    }

    /**
     * Validate search input
     */
    $(document).on('keyup', '#search-quote-input', function () {
        validateSearchInput();
    });

    /**
     * Reset search result
     */
    function resetSearch(){
        if (window.e383157Toggle) {
            fxoStorage.set('searchString', '');
        } else {
            localStorage.setItem('searchString', '');
        }
        $('body').trigger('processStart');
        window.location.href = window.location.href.split('?')[0];
    }

    /**
     * To check if the input character length is valid or not
     */
    function validateSearchInput() {
        let searchInput = $('#search-quote-input');
        let searchQueryVal = $('#search-quote-input').val();
        let errorMessageDiv = $('.error-message');
        if (searchQueryVal.length < 1) {
            searchInput.next(".error").html('<span class="fedex-icon-error"></span>');
            searchInput.addClass("error-text");
            return false;
        } else {
            searchInput.next(".error").html("");
            searchInput.removeClass("error-text");
            errorMessageDiv.text("");
            return true;
        }
    }

    /**
     * ADA issue for left nav bar for quote history page
     */
    $('.uploadtoquote-index-quotehistory .nav.item.current').attr('tabindex', '0');

});
