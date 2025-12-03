/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'fedex/storage',
    'domReady!'
], function ($, _,fxoStorage){

    /**
     * Checks if current logged in user is FCL or not
     */
    let checkoutConfig = typeof (window.checkoutConfig) !== "undefined" && window.checkoutConfig !== null ? window.checkoutConfig : false;
    let isFclCustomer = null;
    if (checkoutConfig) {
        isFclCustomer =  typeof (window.checkoutConfig.is_fcl_customer) !== "undefined" && window.checkoutConfig.is_fcl_customer !== null ? window.checkoutConfig.is_fcl_customer : false;
    }
    let maskedAccountNumber = null;

     /**
      * Click on shipping account list dropdown
      */
    $(document).on("click", '.shipping-account-list', function (e) {
        toggleShippingAccountList();
    });

    /**
     * Trigger custom dropdown when enter or space key is pressed
     */
    $(document).on('keypress', '.shipping-account-list', function (e) {
        let keycode = (e.keyCode ? e.keyCode : e.which);
        if(keycode  == 13 || keycode  == 32){
            e.preventDefault();
            toggleShippingAccountList();
        }
    });

    function toggleShippingAccountList(){
        if (isFclCustomer && $(".shipping_account_number.child-box").css('display') == 'none') { 
            $('.shipping-account-list .custom-select').toggleClass("open");
        }
    }

    return {
        /**
         * Prepare Shipping Account List HTML for FCL
         *
         * @return html
         */
        generateShippingAccountListHtml: function () {
            let self = this;
            let retailProfileSession = typeof (window.checkoutConfig.retail_profile_session) !== "undefined" && window.checkoutConfig.retail_profile_session !== null ? window.checkoutConfig.retail_profile_session : false;
            let shippingAccountList = null;
            if (retailProfileSession) {
                let profile = typeof (retailProfileSession.output.profile) !== "undefined" &&
                retailProfileSession.output.profile !== null ? retailProfileSession.output.profile : false;
                if (profile) {
                    shippingAccountList = typeof (profile.accounts) !== "undefined" &&
                    profile.accounts !== null ? profile.accounts : false;
                    let shippingDropdown = '';
                    let primaryShippingAccount = null;
                    let fedexShippingAccountNumber;
                    if (window.e383157Toggle) {
                        fedexShippingAccountNumber = fxoStorage.get('fedexShippingAccountNumber');
                    } else {
                        fedexShippingAccountNumber = localStorage.getItem('fedexShippingAccountNumber');
                    }
                    let isShowManul = (fedexShippingAccountNumber && fedexShippingAccountNumber != 'null') ? true : false;
                    if (shippingAccountList.length > 0) {
                        shippingAccountList.forEach(function(item) {
                            if (item.accountType == 'SHIPPING') {
                                shippingDropdown += '<span class="custom-option" data-value="'+item.accountNumber+'" tabindex="0">'+item.accountNumber+'</span>';
                                if (item.primary) {
                                    primaryShippingAccount = item.accountNumber;
                                    maskedAccountNumber = item.maskedAccountNumber;
                                }
                                if (fedexShippingAccountNumber == item.accountNumber) {
                                    isShowManul = false;
                                    maskedAccountNumber = item.maskedAccountNumber;
                                }
                            }
                        });
                    }
                    if (shippingDropdown != '') {
                        shippingDropdown = '<div class="shipping-account-list-container"><div id="shipping-account-list" class="shipping-account-list"><div class="custom-select"><div class="custom-select__trigger" tabindex="0"><span>Manually enter a FedEx shipping account number</span><div class="arrow"></div></div><div class="custom-options">'+shippingDropdown+'<span class="custom-option " data-value="manual" tabindex="0">Manually enter a FedEx shipping account number</span></div></div></div></div><div class="clearfix"></div>';
                            $('.early-shipping-account-number .fedex_account_number-box').before(shippingDropdown);
                            //Condition to fix defect D-180537
                            let containers = $('.container').find('.shipping-account-list-container');
                            if (containers.length > 1) {
                                containers.slice(1).remove();
                            }
                            let clearFixcontainers = $('.container').find('.shipping-account-list-container');
                            if (clearFixcontainers.length > 1) {
                                clearFixcontainers.slice(1).remove();
                            }
                            $(".fedex_account_number-box.child-box .fedex_account_number_label, .fedex_account_number-box.child-box .fedex_account_number").addClass("shipping-account-hidden");
                            $('.shipping-account-list-container').addClass('shipping-account-hidden-parent');
                            $('.early-shipping-account-number .fedex_account_number-box').addClass('manual-shipping-account-add');
                            $('.early-shipping-account-number .shipping_account_number').addClass('auto-shipping-account-remove');
                            let defaultShippingAccountNumber =  (fedexShippingAccountNumber && fedexShippingAccountNumber != 'null') ? fedexShippingAccountNumber : primaryShippingAccount;
                            if (defaultShippingAccountNumber !== null && defaultShippingAccountNumber != '') {
                                this.applyShippingAccountNumber(defaultShippingAccountNumber, null, isShowManul);
                                $('.custom-select__trigger > span').html(maskedAccountNumber);
                            } else {
                                this.applyShippingAccountNumber('manual');
                                if (fedexShippingAccountNumber !== null) {
                                    self.applyShippingAccountNumber(fedexShippingAccountNumber,1);
                                }
                            }
                            for (const option of document.querySelectorAll(".custom-option")) {
                                ["click", "keypress"].forEach(ev=>{
                                    option.addEventListener(ev, function(e){
                                        if (!this.classList.contains("selected")) {
                                            this.parentNode.querySelector(".custom-option.selected").classList.remove("selected");
                                            this.classList.add("selected");
                                            this.closest(".custom-select").querySelector(".custom-select__trigger span").textContent = this.textContent;
                                            self.applyShippingAccountNumber(this.getAttribute("data-value"));
                                        }
                                    });
                                });
                            }
                            window.addEventListener("click", function(e) {
                                for (const select of document.querySelectorAll(".custom-select")) {
                                    if (!select.contains(e.target)) {
                                        select.classList.remove("open");
                                    }
                                }
                            });
                    }
                }
            }
            return shippingAccountList;
        },

        /**
         * Apply Shipping Account Number based on dropdown select
         */
        applyShippingAccountNumber: function (accountNumber,flag=null, isShowManul=null) {
            if (flag == 1 && accountNumber == undefined) {
                return;
            }
            let defaultAccountNumberText = "";
            if(typeof accountNumber!== "undefined" && accountNumber != "manual") {
                defaultAccountNumberText = "*" + accountNumber.substr(-4);
            } else {
                defaultAccountNumberText = $("#shipping-account-list .custom-option[data-value=" + accountNumber + "]").html();
            }

            $(".custom-select__trigger > span").html(defaultAccountNumberText);
            if (flag == 1) {
                if (accountNumber.length > 8) {
                    $(".fedex_account_number-field").val(accountNumber);
                    $(".fedex_account_number-field").trigger('focus');
                    $(".fedex_account_number-field").blur();
                    $("#addFedExAccountNumberButton").trigger('click');
                }
            } else {
                if (accountNumber == "manual") {
			        $('.custom-select__trigger > span').html('Manually enter a FedEx shipping account number');
                    $('.checkout-shipping-method').hide();
                    $('.fedex_account_number-box').show();
                    $("#fedExAccountNumber").val('');
                    $("#fedExAccountNumber_validate").html('');
                    $(".fedex_account_number-box.child-box .fedex_account_number_label, .fedex_account_number-box.child-box .fedex_account_number").removeClass("shipping-account-hidden");
                    $('.early-shipping-account-number .shipping_account_number').addClass('manual-shipping-account-remove');
                    $('.early-shipping-account-number .shipping_ref_container').addClass('manual-shipping-ref-remove');
                    $('.early-shipping-account-number .field-error').addClass('auto-validate');
                    $('.shipping-account-list-container').removeClass('shipping-account-hidden-parent');
                    $(".opc-shipping-account-number .container").css("flex-wrap", "wrap");
                    $('.shipping-account-list .custom-option').removeClass('selected');
                    $('.shipping-account-list .custom-option[data-value="manual"]').addClass('selected');
                    if ($(".create_quote").length > 0) {
                        $(".create_quote").prop("disabled", false);
                    }
                    if ($('button.create_quote_review_order').length > 0) {
                        $('button.create_quote_review_order').prop("disabled", false);
                    }
                    window.dispatchEvent(new CustomEvent('fedexShippingAccountNumberChanged', { detail: false }));
                } else {
                    $(".fedex_account_number-field").removeAttr("disabled");
                    $("#fedExAccountNumber_validate").show();
                    $(".fedex_account_number-box.child-box .fedex_account_number_label, .fedex_account_number-box.child-box .fedex_account_number").addClass("shipping-account-hidden");
                    $('.shipping-account-list-container').addClass('shipping-account-hidden-parent');
                    $('.early-shipping-account-number .shipping_account_number').removeClass('manual-shipping-account-remove');
                    $('.early-shipping-account-number .shipping_ref_container').removeClass('manual-shipping-ref-remove');
                    $('.early-shipping-account-number .field-error').removeClass('auto-validate');
                    //Condition Modified For the defect D-123177
                    if (accountNumber && accountNumber.length > 8) {
                        $(".fedex_account_number-field").val(accountNumber);
                        $(".fedex_account_number_remove").css("display", "inline");
                        if ($(".auto-shipping-account-remove").css("display") == "none") {
                            $(".auto-shipping-account-remove").css("display", "");
                        }
                    }
                    $("#addFedExAccountNumberButton").trigger('click');
                    $('.shipping-account-list .custom-option[data-value="'+accountNumber+'"]').addClass('selected');
                    $(".opc-shipping-account-number .container").css("flex-wrap", "wrap");
                    $('.shipping-account-list .custom-option[data-value="manual"]').removeClass('selected');
                    setTimeout(function(){$(".shipping-account-list .custom-select").removeClass('open')},100);
                    window.dispatchEvent(new CustomEvent('fedexShippingAccountNumberChanged', {detail: true}));
                    if (isShowManul) {
                        $('.custom-select__trigger > span').html('Manually enter a FedEx shipping account number');
                        $(".fedex_account_number-field").attr("disabled", "disabled");
                        $(".fedex_account_number-box.child-box .fedex_account_number_label, .fedex_account_number-box.child-box .fedex_account_number").removeClass("shipping-account-hidden");
                        $('.fedex_account_number-box').show();
                    	$('.early-shipping-account-number .shipping_account_number').addClass('manual-shipping-account-remove');
                        $('.early-shipping-account-number .shipping_ref_container').addClass('manual-shipping-ref-remove');
                        $('.early-shipping-account-number .field-error').addClass('auto-validate');
                        $('.shipping-account-list-container').removeClass('shipping-account-hidden-parent');
                        $('.shipping-account-list .custom-option[data-value="manual"]').addClass('selected');
                    }
                }
            }

        }
    };
});
