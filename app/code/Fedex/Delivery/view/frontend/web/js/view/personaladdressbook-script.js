/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    "jquery",
    "fedex/storage",
    "mage/url",
    "Magento_Ui/js/modal/modal",
], function ($, fxoStorage, url, modal) {
    'use strict';
    window.alphabetSearched = 0;
    let requestUrl = url.build("personaladdressbook/index/addressbookpopup");
    $(document).ready(function () {  
        $(document).on('click','#personalAddressSearch',function() {
            var inputLength = $('#keyword').val().length;
            if(inputLength < 2) {
                $('.error-message').fadeIn();
            } else {
                $('.error-message').fadeOut();
            }
        });
    
        $(document).on('mouseenter', '.data-grid-th._sortable', function () {
            var $content = $(this).find('.data-grid-cell-content-name, .data-grid-cell-content-company, .data-grid-cell-content-address');
            if (!$content.hasClass('sorted')) {
                $content.attr('data-order', 'desc').addClass('sorted');
            }
        });
        
        // Mouseleave handler for sortable headers
        $(document).on('mouseleave', '.data-grid-th._sortable', function () {
            var $content = $(this).find('.data-grid-cell-content-name, .data-grid-cell-content-company, .data-grid-cell-content-address');
            if (!$content.hasClass('clicked-sort')) {
                $content.removeAttr('data-order').removeClass('sorted');
            }
        });
    
        $(document).on('click', '.data-grid-th._sortable .data-grid-cell-content-name, .data-grid-th._sortable .data-grid-cell-content-company, .data-grid-th._sortable .data-grid-cell-content-address', function() {
            $('.data-grid-cell-content-name, .data-grid-cell-content-company, .data-grid-cell-content-address').removeClass('clicked-sort');
            $(this).addClass('clicked-sort');
        });

        var lastAddressSortOption;
        // Bind click event to the column header (FULL NAME)
        $(document).on('click', '.data-grid-th._sortable .data-grid-cell-content-name, .data-grid-th._sortable .data-grid-cell-content-company, .data-grid-th._sortable .data-grid-cell-content-address',
         function () {
            var addressSortOption = $(this).data('th');
            var order = $(this).data('order');
            $('.data-grid-th._sortable .data-grid-cell-content-name, .data-grid-th._sortable .data-grid-cell-content-company, .data-grid-th._sortable .data-grid-cell-content-address').removeClass('sorted');
            if(addressSortOption != lastAddressSortOption) {
                $('.data-grid-th._sortable .data-grid-cell-content-name, .data-grid-th._sortable .data-grid-cell-content-company, .data-grid-th._sortable .data-grid-cell-content-address').data("order","asc");
                $('.data-grid-th._sortable .data-grid-cell-content-name, .data-grid-th._sortable .data-grid-cell-content-company, .data-grid-th._sortable .data-grid-cell-content-address').attr("data-order","asc");
            }
            if (order == 'desc') {
                $(this).data('order', "asc");
                $(this).attr('data-order', "asc");
            } else {
                $(this).data('order', "desc");
                $(this).attr('data-order', "desc");
            }
            $(this).addClass('sorted');
            lastAddressSortOption = addressSortOption;
            sortAddressbook(addressSortOption,order);
           
        });
    });

    function sortAddressbook(addressSortOption, currentOrder) {
        let requestUrl = url.build("personaladdressbook/index/sortaddressbook");
        var searchHtml = '';
        var postData = {
            addressSortOption: addressSortOption,
            order: currentOrder
        };

        $.ajax({
            url: requestUrl,
            type: "POST",
            data: postData,
            showLoader: false,
            dataType: 'json',
            success: function (response) {
                if (response.error_msg || response.errors) {
                    $(".err-msg .message").text("System error, Please try again.");
                    $(".err-msg").show();
                    $('html, body').animate({
                        scrollTop: $(".msg-container").offset().top
                    }, 1500);
                } else {
                    $(".err-msg").hide();
                    var responseData = response.data;
                    searchHtml = addressesResponseHtml(responseData);
                    
                    $('tbody.addressbookdatacheckout').html(searchHtml);
                    $('.current-page-total').text(paginationText);
                    $('.current-page-total').trigger("change");
                }
            }
        });
    }

    $(document).on('click', '.address-filter-section .search-address-alphabet', function () {
        $('.search-address-alphabet').removeClass('selected-alphabet');
        $(this).addClass('selected-alphabet');
        var alphabet = $(this).text();
        searchAddressbookbyAlphabet(alphabet, 0);
    });

    $(document).on('click', '.clear-address-search', function () {
        $('.search-address-alphabet').removeClass('selected-alphabet');
        $('.personal-address-search-field').val('');
        searchAddressbookbyAlphabet('', 1);
    });

    $(document).on('change', 'select.address-search-options', function () {
        var valueSelected = this.value;
        valueSelected = valueSelected.replace("personal_address_", "");
        valueSelected = valueSelected.replaceAll("_", " ");
        valueSelected = valueSelected.replace(/([A-Z])/g, " $1").trim();
        valueSelected = valueSelected.toLowerCase();
        $('input.personal-address-search-field')[0].placeholder = "Search by " + valueSelected;
    });

    $(document).on('keypress', '.personal-address-search-field', function (e) {
        if (e.which == 13) {
            $('#personalAddressSearch').trigger('click');
        }
    });

    $(document).on('click', '.custom_row_radio', function() {
        $('.custom_row_radio').not(this).prop('checked', false);
        // Select the current radio button
        $(this).prop('checked', true);
    });

    function searchAddressbookbyAlphabet(alphabet, clear) {
        var requestUrl = url.build("personaladdressbook/index/searchbyalphabet");
        var searchHtml = '';
        $.ajax({
                url: requestUrl,
                type: "POST",
                data: {alphabet:alphabet,clear:clear,alphabetSearched:window.alphabetSearched},
                showLoader: true,
                dataType: 'json',
                success: function (response) {
                    if (response.error_msg || response.errors) {
                        $(".succ-msg").hide();
                        $(".err-msg .message").text("System error, Please try again.");
                        $(".err-msg").show();
                        $('html, body').animate({
                            scrollTop: $(".msg-container").offset().top
                        }, 500);
                    } else {
                        var responseData = response.data;
                        window.alphabetSearched = 1;
                        if (typeof responseData !== 'undefined' && responseData.length) {
                            var startIndex = 1;
                            var endIndex = Math.min(10, responseData.length);
                            var paginationText = startIndex + '-' + endIndex + ' of ' + responseData.length;
                        }
                        if (typeof responseData !== 'undefined' && responseData.length) {
                            searchHtml = addressesResponseHtml(responseData);
                        }
                        $('tbody.addressbookdatacheckout').html(searchHtml);
                        $('.current-page-total').text(paginationText);
                        $('.current-page-total').trigger("change");
                    }
                }
            });
    }

    $(document).on('click', '#personalAddressSearch', function (e) {
        e.preventDefault();
        var addressSearchOption = $('#addressSearchOptions').val();
        var searchField = $('.personal-address-search-field').val();
        if (searchField) {
            searchAddressbook(searchField, addressSearchOption);
        }
    });

    function searchAddressbook(searchField, addressSearchOption) {
        let requestUrl = url.build("personaladdressbook/index/searchaddressbook");
        var searchHtml = '';
        var postData = {
            addressSearchOption: addressSearchOption,
            searchField: searchField
        };
        $.ajax({
            url: requestUrl,
            type: "POST",
            data: postData,
            showLoader: true,
            dataType: 'json',
            success: function (response) {
                if (response.error_msg || response.errors) {
                    $(".err-msg .message").text("System error, Please try again.");
                    $(".err-msg").show();
                    $('html, body').animate({
                        scrollTop: $(".msg-container").offset().top
                    }, 500);
                } else {
                    $(".err-msg").hide();
                    var responseData = response.data;
                    if (typeof responseData !== 'undefined' && responseData.length) {
                        var startIndex = 1;
                        var endIndex = Math.min(10, responseData.length);
                        var paginationText = startIndex + '-' + endIndex + ' of ' + responseData.length;
                    }
                    if (typeof responseData !== 'undefined' && responseData.length) {
                        searchHtml = addressesResponseHtml(responseData);
                    }
                    $('tbody.addressbookdatacheckout').html(searchHtml);
                    $('.current-page-total').text(paginationText);
                    $('.current-page-total').trigger("change");
                }
            }
        });
    }
    
    /**
     * Address Book Modal
     * @return boolean
     */
    function openPersonalAddressBookModal() {
        var options = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: 'Address Book',
            modalClass: 'add-address-book-popup',
            buttons: [{
                text: $.mage.__('CANCEL'),
                class: 'add-address-book-popup-cancel',
                click: function () {
                    this.closeModal();
                }
            },
            {
                text: $.mage.__('SELECT'),
                class: 'add-address-book-popup-select disabled',
                click: function () {
                    this.closeModal();
                }
            }
            ]
        };

        $.ajax({
            type: "post",
            url: requestUrl,
            showLoader: true,
            success: function (data) {
                $('.add-address-book-popup-info').html(data);
                $('.add-address-book-popup-select').addClass('disabled');
            }
        });
        var modalContent = $('.add-address-book-popup-info');
        if (modalContent.length) {
            modalContent.modal(options);
            modalContent.modal('openModal');
            $('.modal-inner-wrap').attr('id', 'modalInnerWrap');
            $('.modal-footer').attr('id', 'add-address-book-popup-footer');
            $("#personal").show();
            return true;
        } else {
            console.error('Modal content element not found.');
            return false;
        }
    }

    function openTab(event, tabName) {
        var tabcontent = $(".tabcontent");
        var tablinks = $(".tablinks");
        tabcontent.hide();
        tablinks.removeClass("active");
        $("#" + tabName).show();
        $(event.currentTarget).addClass("active");
    }

    function showToast(message) {
        $(".success-toast-msg p").text(message);
        var $toast = $('#personaladdressbook-toast');
        $toast.addClass('show');
        setTimeout(function () {
            $toast.addClass('fade-out');
            setTimeout(function () {
                $toast.remove();
            }, 3000);
        }, 4000);
    }

    function showWarning(message) {
        $("#personaladdressbook-warning-msg-p").text(message);
        var $warning = $('#personaladdressbook-warning');
        $warning.addClass('show');
        setTimeout(function () {
            $toast.addClass('fade-out');
            setTimeout(function () {
                $toast.remove();
            }, 3000);
        }, 4000);
    }

    //B-2263196 :: POD2.0: Update checkout shipping form from selected address from address book
    function setDataFromPersonalAddressBook(data) {
        let regionSelect = document.querySelector('select[name="region_id"]');
        for (let option of regionSelect.options) {
            if (option.getAttribute('data-title') === data.stateCode) {
                option.selected = true;
            }
        }
        let inputCity = document.querySelector('input[name="city"]');
        inputCity.value = data.city;
        $('input[name="city"]').trigger("change");
        let inputPincode = document.querySelector('input[name="postcode"]');
        inputPincode.value = data.pinCode;
        $('input[name="postcode"]').trigger("change");
        let inputCompany = document.querySelector('input[name="company"]');
        inputCompany.value = data.company;
        $('input[name="company"]').trigger("change");
        let inputPhoneNumber = document.querySelector('input[name="telephone"]');
        inputPhoneNumber.value = data.phoneNumber;
        $('input[name="phonenumber"]').trigger("change");
        let inputFirstName = document.querySelector('input[name="firstname"]');
        inputFirstName.value = data.firstName;
        $('input[name="firstname"]').trigger("change");
        let inputLastName = document.querySelector('input[name="lastname"]');
        inputLastName.value = data.lastName;
        $('input[name="lastname"]').trigger("change");
        let inputStreet1 = document.querySelector('input[name="street[0]"]');
        inputStreet1.value = data.street1;
        $('input[name="street[0]"]').trigger("change");
        //Need to add email from API response to the shipping form field
    }

    function appyPaginationOnAddressBookPopup(recordsPerPage, pageNo, arrow) {
        var totalPages = parseInt($('#totalPages').val(), 10);
        var totalRecords = $('#totalRecords').val();
        var pageLimit = 1;
        var paginationText = '';
        if (pageNo) {
            var startIndex = (pageNo - 1) * recordsPerPage + 1;
            var endIndex = Math.min(pageNo * recordsPerPage, totalRecords);
            paginationText = startIndex + '-' + endIndex + ' of ' + totalRecords;
        }
        if (arrow == 'next') {
            pageLimit = pageNo + 1;
            $('#currentPage').val(pageNo);
            $('#currentPage').trigger("change");
            $('.prev-page').prop('disabled', false);
            $('.prev-page').trigger("change");
            if (pageLimit > totalPages) {
                $('.next-page').prop('disabled', true);
                $('.next-page').trigger("change");
            } else {
                $('.next-page').prop('disabled', false);
                $('.next-page').trigger("change");
            }
        } else if(arrow == 'rows_change'){
            pageLimit = pageNo + 1;
            $('#currentPage').val(pageNo);
            $('#currentPage').trigger("change");
            $('.prev-page').prop('disabled', true);
            $('.prev-page').trigger("change");
            if (pageLimit > totalPages) {
                $('.next-page').prop('disabled', true);
                $('.next-page').trigger("change");
            } else {
                $('.next-page').prop('disabled', false);
                $('.next-page').trigger("change");
            }
        } else {
            pageLimit = pageNo - 1;
            $('#currentPage').val(pageNo);
            $('.next-page').prop('disabled', false);
            $('.next-page').trigger("change");
            if (pageLimit == 0) {
                $('.prev-page').prop('disabled', true);
                $('.prev-page').trigger("change");
            } else {
                $('.prev-page').prop('disabled', false);
                $('.prev-page').trigger("change");
            }
        }
        var requestUrl = url.build("personaladdressbook/index/addressbookpage");
        var searchHtml = '';
        $.ajax({
            url: requestUrl,
            type: "POST",
            data: { pageSize: recordsPerPage, currentPage: pageNo, setPageSize: true },
            showLoader: true,
            dataType: 'json',
            success: function (response) {
                if (response.error_msg || response.errors) {
                    $(".succ-msg").hide();
                    $(".err-msg .message").text("System error, Please try again.");
                    $(".err-msg").show();
                } else {
                    var responseData = response.data;
                    searchHtml = addressesResponseHtml(responseData);
                    
                    $('tbody.addressbookdatacheckout').html(searchHtml);
                    $('.current-page-total').text(paginationText);
                    $('.current-page-total').trigger("change");
                }
            }
        });
    }

    function resetTotalPages(resultsPerPage, totalRecords) {
        let totalPages = Math.ceil(totalRecords / resultsPerPage);
        $('#totalPages').val(totalPages);
        $('#totalPages').trigger("change");
        return totalPages;
    }
        
    /**
     * Handle response HTML
     * @param {*} responseData 
     */
    function addressesResponseHtml(responseData) {
        let responseHtml = '';
        if (typeof responseData !== 'undefined' && responseData.length) {
            responseData.forEach(function (item, index) {
                var contactID = typeof item.contactID !== 'undefined' ? item.contactID : 0;
                var firstName = typeof item.firstName !== 'undefined' ? item.firstName : '';
                var lastName = typeof item.lastName !== 'undefined' ? item.lastName : '';
                var fullName = lastName + ', ' + firstName;
                var companyName = typeof item.companyName !== 'undefined' ? item.companyName : '';
                let streetLine1 = typeof item.address.streetLines[0] !== 'undefined' ? item.address.streetLines[0] : '';
                let streetLine2 = typeof item.address.streetLines[1] !== 'undefined' ? item.address.streetLines[1] : '';
                var streetLines = streetLine1 + ' ' + streetLine2;
                
                let city = '';
                if (typeof item.city !== 'undefined') {
                    city = item.city;
                } else if (typeof item.address.city !== 'undefined') { 
                    city = item.address.city;
                }
                let stateOrProvinceCode = '';
                if (typeof item.stateOrProvinceCode !== 'undefined') {
                    stateOrProvinceCode = item.stateOrProvinceCode;
                } else if (typeof item.address.stateOrProvinceCode !== 'undefined') { 
                    stateOrProvinceCode = item.address.stateOrProvinceCode;
                }
                let postalCode = '';
                if (typeof item.postalCode !== 'undefined') {
                    postalCode = item.postalCode;
                } else if (typeof item.address.postalCode !== 'undefined') { 
                    postalCode = item.address.postalCode;
                }
                var address = streetLines + '</br>' + city + ',' + stateOrProvinceCode + '' + postalCode;
                responseHtml += '<tr class="data-row disabled"><td class="data-grid-checkbox-cell"><label class="data-grid-checkbox-cell-inner"><input class="admin__control-radio custom_row_radio" type="radio" data-action="select-row"  id="idscheck' + contactID + '" value="' + contactID + '"><input id="contactID" type="hidden" name="contactIDs[]" value="' + contactID + '"></label></td>';
                responseHtml += '<td class="long-text-field" data-th="FIRST NAME"><div class="data-grid-cell-content">' + fullName + '</div></td>';
                responseHtml += '<td class="long-text-field" data-th="COMPANY">' + companyName + '</td><td data-th="ADDRESS"><div class="data-grid-cell-content">' + address + '</div></td>';
            });
        } else {
            responseHtml += '<tr><td colspan="4">No Record Found.</td></tr>';
        }

        return responseHtml;
    }
    
    return {
        openPersonalAddressBookModal: openPersonalAddressBookModal,
        openTab: openTab,
        showToast: showToast,
        showWarning: showWarning,
        setDataFromPersonalAddressBook: setDataFromPersonalAddressBook,
        searchAddressbookbyAlphabet: searchAddressbookbyAlphabet,
        searchAddressbook: searchAddressbook,
        appyPaginationOnAddressBookPopup:appyPaginationOnAddressBookPopup,
        resetTotalPages: resetTotalPages,
        sortAddressbook: sortAddressbook
    }
})
