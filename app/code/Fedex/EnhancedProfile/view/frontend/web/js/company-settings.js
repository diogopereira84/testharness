/**
* Copyright © Fedex, Inc. All rights reserved.
* See COPYING.txt for license details.
*/

define([
    'jquery',
    "mage/url",
    "fedex/storage",
    "Magento_Ui/js/modal/confirm"
], function ($, urlBuilder, fxoStorage, confirm) {
    'use strict';

    return function (config) {
        window.explorersCompanySettingsCustomerAdmin = config.explorersCompanySettingsCustomerAdmin;
        let defaultSiteLogo = config.defaultSiteLogo;
        let companyLogo = config.companyLogo;
        let infoIconImage = config.infoIconImage;
        let warningIconImage = config.warningIconImage;
        let ctaText = config.ctaText;
        let ctaLink = config.ctaLink;
        let resizeTimer;
        let accordionListItemsArray = {};
        let isResizeFirst = true;
        let linkHyperText = 'https://';
        if (companyLogo) {
            defaultSiteLogo = companyLogo;
        }
        $('#expand-all').click(function() {
            if ($('#enable-shipping-toggle').prop('checked') == true) {
                $('.ep-shipping-options').show();
            } else {
                $('.ep-shipping-options').hide();
            }
            if ($('#enable-store-pickup-toggle').prop('checked') == true) {
                $('.ep-allow-hotel-convention').show();
            } else {
                $('.ep-allow-hotel-convention').hide();
            }
        });
        $('.company-settings-accordion .company-settings-details .accordion-li > a').click(function() {

            if ($('#enable-shipping-toggle').prop('checked') == true) {
                $('.ep-shipping-options').show();
            } else {
                $('.ep-shipping-options').hide();
            }
            if ($('#enable-store-pickup-toggle').prop('checked') == true) {
                $('.ep-allow-hotel-convention').show();
            } else {
                $('.ep-allow-hotel-convention').hide();
            }

            if ($('#notificationbanner-toggle').prop('checked') == true) {
                if (ctaText != '' && ctaLink != '') {
                    $('.preview-button').text(ctaText);
                    // D-199639 Check the hypertext in link
                    if (!ctaLink.includes(linkHyperText)) {
                        ctaLink = linkHyperText + '' + ctaLink;
                    }
                    $('.preview-button').attr('href', ctaLink);
                    $('.preview-button').trigger("change");
                }
            } else if ($('#notificationbanner-toggle').prop('checked') == false) {
                $(".preview-banner-section").hide();
                $(".form-group").hide();
                $(".ep-notificationbanner").hide();
                $(".update-preview").hide();
                $(".preview-banner-label").hide();
            }
            if ($('#link-newtab-toggle').prop('checked') == true) {
                $('.preview-button').attr('target','_blank');
            } else {
                $('.preview-button').attr('target','');
            }
            var $accordion = $(this);
            var checkElement = $(this).next();

            $accordion.find('.accordion-li').removeClass('active');
            $accordion.closest('.accordion-li').addClass('active');

            if ((checkElement.is('.accordion-ul')) && (checkElement.is(':visible'))) {
                $accordion.closest('.accordion-li').removeClass('active');
                checkElement.slideUp('normal');
                $accordion.find('.down-icon').show();
                $accordion.find('.up-icon').hide();
            }

            if ((checkElement.is('.accordion-ul')) && (!checkElement.is(':visible'))) {
                $accordion.find('.accordion-ul ul:visible').slideUp('normal');
                checkElement.slideDown('normal');
                $accordion.find('.down-icon').hide();
                $accordion.find('.up-icon').show();
            }

            if (checkElement.is('.accordion-ul')) {
                $('.collapse-all').hide();
                $('.ëxpand-all').show();
                $('.accordion-li.active').each(function() {
                    $('.collapse-all').show();
                    $('.ëxpand-all').hide();
                });
                return false;
            } else {
                return true;
            }
        });

        $('#upload-site-logo-btn').click(function() {
            $('#upload-site-logo').trigger("click");
        });

        $('#upload-site-logo').change(function() {
            $('.max-size-allow-site-logo-warning').hide();
            $('.allow-logo-type-warning').hide();
            let files = $('#upload-site-logo').prop('files');
            let filesArr = Array.prototype.slice.call(files);
            let fileType = filesArr[0].type;
            const validImageTypes = ['image/jpg', 'image/jpeg', 'image/png'];

            if (filesArr[0].size > 2097152) {
                $(".max-size-allow-site-logo-warning").show().delay(5000).fadeOut(500);
                //$('.max-size-allow-site-logo-warning').show();
                //The code below is to prevent any change when the file is bigger than the max size (2097152 or 2MB)
                $('#upload-site-logo').val('');
                $('#revert-site-logo-btn').addClass('disable');
            } else if ($.inArray(fileType, validImageTypes) < 0) {
                $('.allow-logo-type-warning').show().delay(5000).fadeOut(500);
                $('#upload-site-logo').val('');
                $('#revert-site-logo-btn').addClass('disable');
            } else {
                $('#revert-site-logo-btn').removeClass('disable');
                $('#site-logo-img-preview').attr('src', URL.createObjectURL(filesArr[0]));
            }
        });

        /* Ada for credit card, account page & company settings */
        $(document).ready(function() {
            $(".column.main .info-icon span img").each(function() {
                $(this).attr('tabindex', '0');
            });
            /* start ADA Fixes for company settings */
            $('.hotel-label').on('click',function(event){
                event.preventDefault();
                event.stopPropagation();
                return false;
            });
            $('.hotel-checkbox-tooltip').on('click',function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
            /* end ADA Fixes for company settings */
        });

        $('.ëxpand-all').keypress(function (e) {
            var key = e.which;
            if(key == 13) {
               $('.ëxpand-all').click();
            }
        });

        $('.collapse-all').keypress(function (e) {
            var key = e.which;
            if(key == 13) {
               $('.collapse-all').click();
            }
        });

        $('input:checkbox').keypress(function (e) {
            var key = e.which;
            if(key == 13) {
               $(this).click();
            }
        });

        $('.company-settings-form').on('keypress', '#upload-site-logo-btn, .update-preview, #save-changes-btn, #revert-site-logo-btn', function(e) {
            var key = e.which;
            if (key == 13) {
               $(this).click();
            }
        });

        $("input:checkbox").focus(function(){
            $(this).siblings(".custom-slider").css("box-shadow", '0 0 3px 1px #00699d');
        });

        $("input:checkbox").blur(function(){
            $(this).siblings(".custom-slider").css("box-shadow", 'unset');
        });

        $('#company-settings-form').on('keyup keypress', function(e) {
            var keyCode = e.keyCode || e.which;
            if (keyCode === 13) {
              e.preventDefault();
              return false;
            }
        });
        function scrollToBannerSection() {
            $('html, body').animate({
                scrollTop: $(".preview-banner-label").offset().top
            }, 500);
        }
        function validateNotificationBannerSection() {
            $('input[type=text]').each(function(){
                if ($(this).val() == '' && ($(this).attr('id') != 'linkAddress' && $(this).attr('id') != 'linkDisplay')) {
                    $(this).css('border-color', 'red');
                    scrollToBannerSection();
                } else {
                    $(this).css('border-color', '');
                }
            });
            if ($('#bannerTitle').val() != '') {
                $('.preview-banner-title').text($('#bannerTitle').val());
                $('.banner-title-warning').html('This text will be used for the banner title.').css('color','#333333');
            } else {
                scrollToBannerSection();
                $('.banner-title-warning').html('<span style="font-size: 16px;width: 24px; height:24px;">x </span>This is required field.').css('color', 'red');
            }
            let descriptionValue = $('#description').val();
            if (descriptionValue == '') {
                scrollToBannerSection();
                $('#description').css('border-color', 'red');
                $('.banner-description-warning').html('This is required field.').css('color', 'red');
            } else {
                $('#description').css('border-color', '');
                $('.preview-banner-description').text(descriptionValue);
                $('.banner-description-warning').css('color','#333333');
            }
            let bannerType = $('#bannerType').val();
            if ($('#bannerType').val() == '' || $('#bannerType').val() == 0) {
                scrollToBannerSection();
                $('#bannerType').css('border-color', 'red');
                $('.banner-type-warning').html('<span style="font-size: 16px;width: 24px; height:24px;">x </span>This is required field.').css('color','red');
            } else {
                $('#bannerType').css('border-color', '');
                if (bannerType == 'information') {
                    $('.preview-banner-section').removeClass('warning');
                    $('.preview-banner-icon').attr('src', infoIconImage);
                } else {
                    $('.preview-banner-section').addClass('warning');
                    $('.preview-banner-icon').attr('src', warningIconImage);
                }
                $('.banner-type-warning').html('This will indicate the visual type of banner.').css('color','#333333');
            }
            if ($('#linkAddress').val() != '') {
                // D-199639 Check the hypertext in link
                let ctaLink = $('#linkAddress').val();
                if (!ctaLink.includes(linkHyperText)) {
                    ctaLink = linkHyperText + '' + ctaLink;
                }
                $('.preview-button').attr('href', ctaLink);
            }
            if ($('#link-newtab-toggle').prop('checked') == true) {
                $('.preview-button').attr('target','_blank');
            } else {
                $('.preview-button').attr('target','');
            }
            if ($('#linkDisplay').val() != '') {
                $('.preview-button').show();
                $('.preview-button').text($('#linkDisplay').val());
            } else {
                $('.preview-button').text('BUTTON').hide();
            }
        }
        /* End Ada for credit card & account page */
        $('#company-settings-form').on('submit', function(e){
            e.preventDefault();
            let imageSrc = $('#site-logo-img-preview').attr('src');
            if (imageSrc.toLocaleLowerCase().indexOf("sitelogo")!=-1) {
                $(".upload-site-logo-warning").show().css('color','red').delay(5000).fadeOut(500);
                $('.site-logo').addClass('active');
                $('.site-logo-settings').show();
                return false;
            }
            var formData = new FormData(this);
            var file_obj = document.getElementById("upload-site-logo");
            formData.append('filepath', file_obj.files[0]);
            formData.delete('company_logo');
            let requestUrl = urlBuilder.build("customer/account/savecompanysettings");
            $.ajax({
                url: requestUrl,
                type: "POST",
                data: formData,
                dataType: "json",
                showLoader: true,
                contentType: false,
                enctype: 'multipart/form-data',
                processData: false,
                success: function (data) {
                    if (data.error) {
                        if (data.msg != 'validation') {
                            $(".succ-msg").hide();
                            $(".err-msg").show();
                            $('html, body').animate({
                                scrollTop: $(".msg-container").offset().top
                            }, 1000);
                        }
                        $('.notification-banners').addClass('active');
                        $('.notification-banner-settings').show();
                        validateNotificationBannerSection();
                        return false;
                    } else {
                        e.preventDefault();
                        $(".succ-msg span.message").text(data.msg);
                        $(".succ-msg").show();
                        $(".err-msg").hide();
                        $('html, body').animate({
                            scrollTop: $(".msg-container").offset().top
                        }, 500, function(){
                            let comapnySettingRedirectUrl = $('#companySettingRedirectUrl').text();
                            if (comapnySettingRedirectUrl !== '' || comapnySettingRedirectUrl !== null) {
                                setTimeout(function() {
                                    $(".company-setting-level-warning-popup .modal-inner-wrap .action-close").trigger("click");
                                    window.location.href = comapnySettingRedirectUrl;
                                }, 100);
                            } else {
                                setTimeout(function() {
                                    location.reload();
                                }, 100);
                            }
                        });
                    }
                }
            });
        });

        $('#revert-site-logo-btn').click(function() {
            $('#upload-site-logo').val('');
            $('#revert-site-logo-btn').addClass('disable');
            $('#site-logo-img-preview').attr('src', defaultSiteLogo);
        });

        $('.ëxpand-all').click(function() {
            $('.accordion-li').addClass('active');
            $('.accordion-ul').show();
            $('.collapse-all').show();
            $('.ëxpand-all').hide();
            $('.down-icon').hide();
            $('.up-icon').show();
            $('.collapse-all').focus();
        });

        $('.collapse-all').click(function() {
            $('.accordion-li').removeClass('active');
            $('.accordion-ul').hide();
            $('.collapse-all').hide();
            $('.ëxpand-all').show();
            $('.down-icon').show();
            $('.up-icon').hide();
            $('.ëxpand-all').focus();
        });

        $(function () {
            setCheckboxSelectLabels();
            $(document).on('click', function (event) {
                let targetClassName = event.target.className;
                if ((event.target.id !== 'shipping-options' && event.target.id !== '')
                    || (targetClassName.match('checkBox val') == null && targetClassName.match('shipping-option-checkbox-checked') == null && targetClassName.match('shipping-option-level') == null && targetClassName !== '')) {
                    $('.shipping-options-container .checkboxes').hide();
                    $('.shipping-options .arrow').removeClass('up');
                }
                if (targetClassName == 'inner-wrap') {
                    $('.shipping-options-container .checkboxes').show();
                    $('.shipping-options .arrow').addClass('up');
                }
            });
            $('.toggle-next').click(function(event) {
                event.stopPropagation();
                $(this).next('.checkboxes').slideToggle(400);
                $('.shipping-options .arrow').toggleClass('up');
            });
            $('.toggle-next').on('keypress',function(e) {
                if(e.which == 13) {
                    $('.toggle-next').trigger('click');
                }
            });
            $('.shipping-option-row .shipping-option-checkbox-checked').on('keypress',function(e) {
                if(e.which == 13) {
                    $(this).siblings('.checkBox.val').trigger('click');
                    $(this).siblings('.checkBox.val').focus();
                }
            });
            $('.shipping-option-row .checkBox.val').on('keypress',function(e) {
                if(e.which == 13) {
                    $(this).siblings('.shipping-option-checkbox-checked').focus();
                }
            });
            $('.checkBox, .shipping-option-checkbox-checked').change(function() {
                let checkboxId = $(this).attr('data-id');
                if ($('.checkbox-icon-id-'+checkboxId).css('display') == 'none' && $('.checkbox-checked-icon-id-'+checkboxId).css('display') == 'block') {
                    $('.checkbox-checked-icon-id-'+checkboxId).removeClass('show');
                    $('.checkbox-checked-icon-id-'+checkboxId).prop('checked', true);
                    $('.checkbox-icon-id-'+checkboxId).removeClass('hidden');
                } else if ($('.checkbox-icon-id-'+checkboxId).css('display') == 'block' && $('.checkbox-checked-icon-id-'+checkboxId).css('display') == 'none') {
                    $('.checkbox-icon-id-'+checkboxId).addClass('hidden');
                    $('.checkbox-checked-icon-id-'+checkboxId).prop('checked', false);
                    $('.checkbox-checked-icon-id-'+checkboxId).addClass('show');
                }
                setCheckboxSelectLabels();
            });

            $('#shippingOptionVal').change(function() {
                if( $('#shippingOptionVal').prop('checked') == true){
                    $('.defaultVal').prop("checked", false);
                    $('.defaultVal').trigger("change");
                }
            });

            $('#enable-shipping-toggle').change(function() {
                if ($(this).is(':checked')) {
                    $('.ep-shipping-options').show();
                } else {
                    $('.ep-shipping-options').hide();
                }
            });
            $('#enable-store-pickup-toggle').change(function() {
                if ($(this).is(':checked')) {
                    $('.ep-allow-hotel-convention').show();
                } else {
                    $('.ep-allow-hotel-convention').hide();
                }
            });
            $('.hotel-checkbox').change(function() {
                if ($(this).is(':checked')) {
                    $('.hotel-checkbox').addClass('hidden');
                    $('.checkbox-checked').addClass('show');
                } else {
                    $('.hotel-checkbox').removeClass('hidden');
                    $('.checkbox-checked').removeClass('show');
                }
            });
            $('.checkbox-checked').on('keypress',function(e) {
                if(e.which == 13) {
                    $('.hotel-checkbox').trigger('click');
                    if ($(this).is(':checked')) {
                        $('.checkbox-checked').focus()
                    } else {
                        $('.hotel-checkbox').focus()
                    }
                }
            });
            $('.hotel-checkbox').on('keypress',function(e) {
                if(e.which == 13) {
                    $('.checkbox-checked').focus()
                }
            });
            /* start add warning popup for site level payment settings */
            $('#company-settings-form').find(':input').each(function(index, value) {
                fxoStorage.set("isCompanySettingEditable", false);
            });

            $('#company-settings-form').on('change paste', ':input', function(e) {
                fxoStorage.set("isCompanySettingEditable", true);
            });

            $(document).on('keydown keypress', '.company-setting-level-action-secondary', function (e) {
                if (e.which === 9) { // Tab key
                    $('.company-setting-level-action-secondary').css('box-shadow', '');
                }
            });

            $(document).on('click', function (event) {
                if($(event.target).hasClass('action-close')) {
                    $(".block-minicart").find("#top-cart-btn-checkout").attr("disabled",false);
                }
                let isCompanySettingEditable = fxoStorage.get("isCompanySettingEditable");
                var container = $("#company-settings-form");
                if(event.target.tagName.toLowerCase() !== 'a') {
                    if (!$(event.target).is('#save-changes-btn') || !$(event.target).is('.company-setting-level-action-primary')) {
                        $('#companySettingRedirectUrl').text(event.target.href);
                    }
                }
                let menuTags = event.target.parentNode;
                let showWarningModalPopupFlag = false;
                if (menuTags.tagName == 'A' ||
                    event.target.tagName == 'A' ||
                    $(event.target).hasClass('checkout')
                ) {
                    showWarningModalPopupFlag = true;
                }
                if (menuTags.tagName == 'A' && menuTags.className != 'preview-button') {
                    $('#companySettingRedirectUrl').text(menuTags.target.href);
                }
                // D-199639 Avoiding redirect after preview link click
                if (event.target.tagName == 'A' && event.target.className != 'preview-button' && event.target.className != 'notification-banner-preview-button') {
                    $('#companySettingRedirectUrl').text(event.target.href);
                }
                if (showWarningModalPopupFlag === true && $('.modal-popup.company-setting-level-warning-popup._show').length == 0) {
                    if (isCompanySettingEditable
                        && !container.is(event.target)
                        && container.has(event.target).length === 0
                        && event.target.className !== 'company-setting-level-action-primary'
                        && event.target.className !== 'company-setting-level-action-secondary'
                        && event.target.className !== 'action-close'
                    ) {
                        event.preventDefault();
                        let alertIconImage = typeof(window.checkout.alert_icon_image) != 'undefined' && window.checkout.alert_icon_image != null ? window.checkout.alert_icon_image : '';
                        let contentDetails ='<div class="comapny-setting-level-warning-popup-content"><h3 class="comapny-setting-level-warning-title">Want to save your changes?</h3><p class="comapny-setting-level-warning-description">The changes you have made have not been saved, and will be lost.</p></div>';

                        confirm({
                            buttons: [
                                {
                                    text: $.mage.__("DON'T SAVE"),
                                    'class': 'company-setting-level-action-secondary',
                                    click: function () {
                                        fxoStorage.set("isCompanySettingEditable", false);
                                        $("#company-settings-form").trigger("reset");
                                        $(".company-setting-level-warning-popup .modal-inner-wrap .action-close").trigger("click");
                                        let urlRedirect =  $('#companySettingRedirectUrl').text();
                                        if (urlRedirect !== '' && urlRedirect !== null) {
                                            window.location.replace(urlRedirect);
                                            $('#companySettingRedirectUrl').text('');
                                        } else {
                                            $(event.target)[0].click();
                                        }
                                    }
                                },
                                {
                                    text: $.mage.__('SAVE'),
                                    'class': 'company-setting-level-action-primary',
                                    click: function () {
                                        // need to store action in local storage for redirect
                                        $(".company-setting-level-warning-popup .modal-inner-wrap .action-close").trigger("click");
                                        $('#companySettingRedirectUrl').text(event.target.href);
                                        $("#save-changes-btn").trigger("click");
                                    }
                                }
                            ],
                            modalClass: 'company-setting-level-warning-popup',
                            title: '<img src="'+alertIconImage+'" class="comapny-setting-level-warning-icon-img" aria-label="delete_image" />',
                            content: contentDetails,
                            focus: '.company-setting-level-action-secondary'
                        });
                        $(".company-setting-level-warning-popup .company-setting-level-action-secondary, .company-setting-level-warning-popup .company-setting-level-action-primary, .company-setting-level-warning-popup .action-close").each(function() {
                            $(this).attr('tabindex', '0');
                        });
                        if (event.screenX === 0 && event.screenY === 0) {
                            $(".company-setting-level-action-secondary").css('box-shadow', '0 0 3px 1px #00699d');
                        }
                    }
                }
            });
            // end code
        });

        function setCheckboxSelectLabels(elem) {
            var wrappers = $('.wrapper');
            $.each(wrappers, function(key, wrapper) {
                var checkboxes = $(wrapper).find('.checkBox');
                var prevText = '';
                var btnText = '';
                var button = $(wrapper).find('button');
                $.each( checkboxes, function( i, checkbox ) {
                    if ( $(checkbox).prop('checked') == true) {
                        var text = $(checkbox).next().html();
                        btnText = prevText + text;
                        prevText = btnText + ', ';
                    }
                });
                var numberOfChecked = $(wrapper).find('input.val:checkbox:checked').length;
                if (numberOfChecked >= 2) {
                    btnText = btnText.substring(0, 30) + '...';
                }
                $(button).html(btnText + ' <i class="arrow down up"></i>');
            });
        }

        $('#notificationbanner-toggle').click(function() {
            if ($(this).is(":checked")) {
                $(".preview-banner-section").show();
                $(".form-group").show();
                $(".ep-notificationbanner").show();
                $(".update-preview").show();
                $(".preview-banner-label").show();
            } else {
                $(".preview-banner-section").hide();
                $(".form-group").hide();
                $(".ep-notificationbanner").hide();
                $(".update-preview").hide();
                $(".preview-banner-label").hide();
            };
        });
        let max = 400;
        $(document).on('keyup', '#description' , function() {
            let len = $(this).val().length;
            if (len >= max) {
                $('.banner-description-warning').text('you have reached the limit').css('color','red');
                $(this).val($(this).val().substring(0, max));
            } else {
                var ch = max - len;
                $('.banner-description-warning').text(ch + ' characters left').css('color','#333333');
            }
        });
        $(".update-preview").click(function() {
            validateNotificationBannerSection();
        });

        // Accordion title change on specific breakpoint
        $(window).resize(function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(breakpointChange(), 100);
        });

        function breakpointChange() {
            var listItems = $(".company-settings-details li");
            var accordionTitle = '';
            var j = 1;
            if (window.matchMedia('(max-width: 767px)').matches) {
                listItems.each(function (id, li) {
                    var accordionListItemSpan = $(li).find('.accordion-title');
                    var isSiteLogo = $(li).hasClass('site-logo');
                    if (!isSiteLogo) {
                        var spanText = accordionListItemSpan.text();
                        accordionTitle = spanText.trim();
                        if (isResizeFirst) {
                            accordionListItemsArray[j] = accordionTitle;
                            j++;
                        }
                        accordionTitle = accordionTitle.substring(0, 16) + '...';
                        $(accordionListItemSpan).text(accordionTitle);
                    }
                });
                isResizeFirst = false;
            } else {
                j = 1;
                listItems.each(function (id, li) {
                    var accordionListItemSpan = $(li).find('.accordion-title');
                    var isSiteLogo = $(li).hasClass('site-logo');
                    if (!isSiteLogo) {
                        $(accordionListItemSpan).text(accordionListItemsArray[j]);
                        j++;
                    }
                });
            }
        }
        $(window).resize();
    }
});
