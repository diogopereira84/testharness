/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/lib/view/utils/dom-observer',
    'uiRegistry'
], function ($, alert, $dom, uiRegistry) {
    'use strict';

    /**
     * init
     * 
     * @returns void
     */
    function init(importRequestUrl) {

        $(".browse-btn").on('click', function () {
            $(".browse-file").val('');
            $(".browse-file").trigger('click');
        });
        $(".browse-file").on("change", function () {
            if (typeof $(this)[0].files[0] !== 'undefined' && $(this)[0].files[0].name && $(this)[0].files[0].name.includes('.csv')) {
                let browsefileSelector = this;
                readDataRequest(importRequestUrl, browsefileSelector);
                $(".file-name-input").val($(this)[0].files[0].name);
            } else {
                $('body').trigger('processStop');
                $(".file-name-input").val('');
                $(this).val();
                alert({
                    title: $.mage.__('Alert'),
                    content: $.mage.__('Invalid file, please ensure file is in correct format and  saved as a CSV.'),
                    actions: {
                        always: function () { }
                    }
                });
            }
        });
    }

    // Read Data Request
    function readDataRequest(importRequestUrl, browsefileSelector) {

        let formData = new FormData();
        formData.append("file", $(browsefileSelector)[0].files[0]);
        $('.loading-mask').attr('style', '');
        $.ajax({
            url: importRequestUrl + '?form_key=' + window.FORM_KEY + '&isAjax=true',
            type: 'POST',
            data: formData,
            async: true,
            success: function (data) {
                $('div[data-index="fxo_web_analytics"] > .fieldset-wrapper-title[data-state-collapsible="closed"]').trigger('click').trigger('click');
                $('div[data-index="catalog_document"] > .fieldset-wrapper-title[data-state-collapsible="closed"]').trigger('click').trigger('click');
                $('div[data-index="email_notf_options"] > .fieldset-wrapper-title[data-state-collapsible="closed"]').trigger('click').trigger('click');
                $('div[data-index="cxml_notification"] > .fieldset-wrapper-title[data-state-collapsible="closed"]').trigger('click').trigger('click');
                $('div[data-index="upload_to_quote"] > .fieldset-wrapper-title[data-state-collapsible="closed"]').trigger('click').trigger('click');
                $('div[data-index="notification_banner_config"] > .fieldset-wrapper-title[data-state-collapsible="closed"]').trigger('click').trigger('click');
                $('div[data-index="is_catalog_mvp_enabled"] > .fieldset-wrapper-title[data-state-collapsible="closed"]').trigger('click').trigger('click');
                $('div[data-index="information"] > .fieldset-wrapper-title[data-state-collapsible="closed"]').trigger('click').trigger('click');
                $('div[data-index="settings"] > .fieldset-wrapper-title[data-state-collapsible="closed"]').trigger('click').trigger('click');
                $('div[data-index="authentication_rule"] > .fieldset-wrapper-title').trigger('click').trigger('click');
                $('div[data-index="production_location"] > .fieldset-wrapper-title').trigger('click').trigger('click');
                $('div[data-index="shipping_options"] > .fieldset-wrapper-title').trigger('click').trigger('click');
                $('div[data-index="company_admin"] > .fieldset-wrapper-title[data-state-collapsible="closed"]').trigger('click').trigger('click');
                $('div[data-index="company_payment_methods"] > .fieldset-wrapper-title[data-state-collapsible="closed"]').trigger('click').trigger('click');
                $('div[data-index="company_credit"] > .fieldset-wrapper-title[data-state-collapsible="closed"]').trigger('click').trigger('click');
                $('div[data-index="address"] > .fieldset-wrapper-title[data-state-collapsible="closed"]').trigger('click').trigger('click');

                try {
                    data.forEach(function (item, index, arr) {
                        let tabData = JSON.parse(item);
                        if (tabData) {
                            if (index == 0) {
                                prePopulateGeneralTabData(tabData);
                            }
                            if (index == 2) {
                                prePopulatedCxmlNotificationMessageTabData(tabData);
                            }
                            if (index == 3) {
                                prePopulatedCatalogDocumentUserSettingsTabData(tabData);
                            }
                            if (index == 4) {
                                prePopulatedWebAnalyticsTabData(tabData);
                            }
                            if (index == 5) {
                                prePopulatedEmailNotificationOptionsTabData(tabData);
                            }
                            if (index == 6) {
                                prePopulatedUploadToQuoteTabData(tabData);
                            }
                            if (index == 7) {
                                prePopulateAuthenticationRuleTabData(tabData);
                            }
                            if (index == 8) {
                                prePopulatedOrderSetting(tabData);
                            }
                            if (index == 9) {
                                prePopulatedPaymentMethodTabData(tabData);
                            }
                            if (index == 10) {
                                prePopulatedNotificationBannerTabData(tabData);
                            }
                            if (index == 11) {
                                prePopulatedDeliveryOptionsTabData(tabData);
                            }
                            if (index == 12) {
                                prePopulatedAccountInformationTabData(tabData);
                            }
                            if (index == 13) {
                                prePopulatedLegalAddressTabData(tabData);
                            }
                            if (index == 14) {
                                prePopulatedCompanyAdminTabData(tabData);
                            }
                            if (index == 15) {
                                prePopulatedCompanyCreditTabData(tabData);
                            }
                            if (index == 16) {
                                prePopulatedAdvanceSettingsTabData(tabData);
                            }
                            if (index == 17) {
                                prePopulatedMvpCatalogTabData(tabData);
                            }
                        }
                    });

                    alert({
                        title: $.mage.__('Success'),
                        content: $.mage.__('Site Successfully imported. Payment information is not transferred over, please review the Payment Methods section to update all payment information.'),
                        actions: {
                            always: function () { }
                        }
                    });
                } catch (err) {
                    alert({
                        title: $.mage.__('Alert'),
                        content: $.mage.__('Invalid file, please ensure file is in correct format and  saved as a CSV.'),
                        actions: {
                            always: function () { }
                        }
                    });
                    console.log(err.message);
                    $('body').trigger('processStop');
                    $(".file-name-input").val('');
                    $('.browse-file').val();
                }

                $('body').loader('hide');
            },
            cache: false,
            contentType: false,
            processData: false,
        });
    }

    // General Tab Data Prepopulated
    function prePopulateGeneralTabData(tabData) {
        if (typeof tabData.company_name !== 'undefined' && tabData.company_name) {
            $('input[name="general[company_name]"]').val(tabData.company_name).trigger('change');
        }
        if (typeof tabData.status !== 'undefined' && tabData.status) {
            $('select[name="general[status]"] > option').each(function () {
                if ($(this).text() == tabData.status) {
                    $(this).attr('selected', 'selected');
                }
            });
        }
        if (typeof tabData.company_email !== 'undefined' && tabData.company_email) {
            $('input[name="general[company_email]"]').val(tabData.company_email).trigger('change');
        }
        if (typeof tabData.sales_representative !== 'undefined' && tabData.sales_representative) {
            $('select[name="general[sales_representative_id]"] > option').each(function () {
                if ($(this).text() == tabData.sales_representative) {
                    $(this).attr('selected', 'selected');
                }
            });
        }
        if (typeof tabData.url_extention !== 'undefined' && tabData.url_extention) {
            $('input[name="general[company_url_extention]"]').val(tabData.url_extention).trigger('change');
        }
        if (typeof tabData.sensitive_data_enabled !== 'undefined' && tabData.sensitive_data_enabled) {
            if ($('input[name="general[is_sensitive_data_enabled]"]').val() !== tabData.sensitive_data_enabled) {
                $('input[name="general[is_sensitive_data_enabled]"]').next('label').trigger('click');
            }
        }
    }

    // Cxml Notification Message Tab Data Prepopulated
    function prePopulatedCxmlNotificationMessageTabData(tabData) {
        if (typeof tabData.order_complete_confirm !== 'undefined' && tabData.order_complete_confirm) {
            if ($('input[name="cxml_notification[order_complete_confirm]"]').val() !== tabData.order_complete_confirm) {
                $('input[name="cxml_notification[order_complete_confirm]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.shipping_notification_or_delivery_options !== 'undefined' && tabData.shipping_notification_or_delivery_options) {
            if ($('input[name="cxml_notification[shipnotf_delivery]"]').val() !== tabData.shipping_notification_or_delivery_options) {
                $('input[name="cxml_notification[shipnotf_delivery]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.order_cancel_customer !== 'undefined' && tabData.order_cancel_customer) {
            if ($('input[name="cxml_notification[order_cancel_customer]"]').val() !== tabData.order_cancel_customer) {
                $('input[name="cxml_notification[order_cancel_customer]"]').next('label').trigger('click');
            }
        }
    }

    // Catalog Document User Settings Tab Data Prepopulated
    function prePopulatedCatalogDocumentUserSettingsTabData(tabData) {
        if (typeof tabData.reorder !== 'undefined' && tabData.reorder) {
            if ($('input[name="catalog_document[is_reorder_enabled]"]').val() !== tabData.reorder) {
                $('input[name="catalog_document[is_reorder_enabled]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.allow_own_document !== 'undefined' && tabData.allow_own_document) {
            if ($('input[name="catalog_document[allow_own_document]"]').val() !== tabData.allow_own_document) {
                $('input[name="catalog_document[allow_own_document]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.allow_shared_catalog !== 'undefined' && tabData.allow_shared_catalog) {
            if ($('input[name="catalog_document[allow_shared_catalog]"]').val() !== tabData.allow_shared_catalog) {
                $('input[name="catalog_document[allow_shared_catalog]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.allow_upload_to_quote !== 'undefined' && tabData.allow_upload_to_quote) {
            if ($('input[name="catalog_document[allow_upload_to_quote]"]').val() !== tabData.allow_upload_to_quote) {
                $('input[name="catalog_document[allow_upload_to_quote]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.box_cloud_drive_integration_option !== 'undefined' && tabData.box_cloud_drive_integration_option) {
            if ($('input[name="catalog_document[box_enabled]"]').val() !== tabData.box_cloud_drive_integration_option) {
                $('input[name="catalog_document[box_enabled]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.dropbox_cloud_drive_integration_option !== 'undefined' && tabData.dropbox_cloud_drive_integration_option) {
            if ($('input[name="catalog_document[dropbox_enabled]"]').val() !== tabData.dropbox_cloud_drive_integration_option) {
                $('input[name="catalog_document[dropbox_enabled]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.google_cloud_drive_integration_option !== 'undefined' && tabData.google_cloud_drive_integration_option) {
            if ($('input[name="catalog_document[google_enabled]"]').val() !== tabData.google_cloud_drive_integration_option) {
                $('input[name="catalog_document[google_enabled]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.microsoft_cloud_drive_integration_option !== 'undefined' && tabData.microsoft_cloud_drive_integration_option) {
            if ($('input[name="catalog_document[microsoft_enabled]"]').val() !== tabData.microsoft_cloud_drive_integration_option) {
                $('input[name="catalog_document[microsoft_enabled]"]').next('label').trigger('click');
            }
        }
    }

    // FXO Web Analytics Tab Data Prepopulated
    function prePopulatedWebAnalyticsTabData(tabData) {
        if (typeof tabData.content_square !== 'undefined' && tabData.content_square) {
            if ($('input[name="fxo_web_analytics[content_square]"]').val() !== tabData.content_square) {
                $('input[name="fxo_web_analytics[content_square]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.adobe_analytics !== 'undefined' && tabData.adobe_analytics) {
            if ($('input[name="fxo_web_analytics[adobe_analytics]"]').val() !== tabData.adobe_analytics) {
                $('input[name="fxo_web_analytics[adobe_analytics]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.app_dynamics !== 'undefined' && tabData.app_dynamics) {
            if ($('input[name="fxo_web_analytics[app_dynamics]"]').val() !== tabData.app_dynamics) {
                $('input[name="fxo_web_analytics[app_dynamics]"]').next('label').trigger('click');
            }
        }

        if (typeof tabData.forsta !== 'undefined' && tabData.forsta) {
            if ($('input[name="fxo_web_analytics[forsta]"]').val() !== tabData.forsta) {
                $('input[name="fxo_web_analytics[forsta]"]').next('label').trigger('click');
            }
        }
    }

    // Emal Notification Tab Data Prepopulated
    function prePopulatedEmailNotificationOptionsTabData(tabData) {
        if (typeof tabData.is_quote_request !== 'undefined' && tabData.is_quote_request) {
            if ($('input[name="email_notf_options[is_quote_request]"]').val() !== tabData.is_quote_request) {
                $('input[name="email_notf_options[is_quote_request]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.is_expiring_order_email !== 'undefined' && tabData.is_expiring_order_email) {
            if ($('input[name="email_notf_options[is_expiring_order]"]').val() !== tabData.is_expiring_order_email) {
                $('input[name="email_notf_options[is_expiring_order]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.is_expired_order_email !== 'undefined' && tabData.is_expired_order_email) {
            if ($('input[name="email_notf_options[is_expired_order]"]').val() !== tabData.is_expired_order_email) {
                $('input[name="email_notf_options[is_expired_order]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.is_order_reject_email !== 'undefined' && tabData.is_order_reject_email) {
            if ($('input[name="email_notf_options[is_order_reject]"]').val() !== tabData.is_order_reject_email) {
                $('input[name="email_notf_options[is_order_reject]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.is_success_email_enable !== 'undefined' && tabData.is_success_email_enable) {
            if ($('input[name="email_notf_options[is_success_email_enable]"]').val() !== tabData.is_success_email_enable) {
                $('input[name="email_notf_options[is_success_email_enable]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.bcc_comma_seperated_email !== 'undefined' && tabData.bcc_comma_seperated_email) {
            $('input[name="email_notf_options[bcc_comma_seperated_email]"]').val(tabData.bcc_comma_seperated_email).trigger('change')
        }
    }

    // Upload to quote
    function prePopulatedUploadToQuoteTabData(tabData) {
        if (typeof tabData.allow_upload_to_quote !== 'undefined' && tabData.allow_upload_to_quote) {
            if ($('input[name="upload_to_quote[allow_upload_to_quote]"]').val() !== tabData.allow_upload_to_quote) {
                $('input[name="upload_to_quote[allow_upload_to_quote]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.enable_next_step_content_to_display !== 'undefined' && tabData.enable_next_step_content_to_display) {
            if ($('input[name="upload_to_quote[allow_next_step_content]"]').val() !== tabData.enable_next_step_content_to_display) {
                $('input[name="upload_to_quote[allow_next_step_content]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.upload_to_quote_next_step_content !== 'undefined' && tabData.upload_to_quote_next_step_content) {
            $('textarea[name="upload_to_quote_next_step_content"]').val(tabData.upload_to_quote_next_step_content).trigger('change');
        }
    }

    // Notificatoin Banner Tab Data prepopulated
    function prePopulatedNotificationBannerTabData(tabData) {
        if (typeof tabData.is_banner_enable !== 'undefined' && tabData.is_banner_enable) {
            if ($('input[name="notification_banner_config[is_banner_enable]"]').val() !== tabData.is_banner_enable) {
                $('input[name="notification_banner_config[is_banner_enable]"]').next('label').trigger('click');
            }
        }

        if (typeof tabData.banner_title !== 'undefined' && tabData.banner_title) {
            $('input[name="notification_banner_config[banner_title]"]').val(tabData.banner_title).trigger('change');
        }
        if (typeof tabData.iconography !== 'undefined' && tabData.iconography) {
            $('select[name="notification_banner_config[iconography]"] > option').each(function () {
                let iconongraphy = tabData.iconography[0].toUpperCase() + tabData.iconography.slice(1);
                if ($(this).text() == iconongraphy) {
                    $(this).attr('selected', 'selected').trigger('change');
                }
            });
        }
        if (typeof tabData.description !== 'undefined' && tabData.description) {
            $('textarea#company_form_description').val(tabData.description).trigger('change');
        }
        $('div#buttonscompany_form_description > #togglecompany_form_description').trigger('click').trigger('click');
        if (typeof tabData.cta_text !== 'undefined' && tabData.cta_text) {
            $('input[name="notification_banner_config[cta_text]"]').val(tabData.cta_text).trigger('change');
        }
        if (typeof tabData.cta_link !== 'undefined' && tabData.cta_link) {
            $('input[name="notification_banner_config[cta_link]"]').val(tabData.cta_link).trigger('change');
        }

        if (typeof tabData.link_open_in_new_tab !== 'undefined' && tabData.link_open_in_new_tab) {
            if ($('input[name="notification_banner_config[link_open_in_new_tab]"]').val() !== tabData.allow_upload_to_quote) {
                $('input[name="notification_banner_config[link_open_in_new_tab]"]').next('label').trigger('click');
            }
        }
    }

    //Mvp Catalog Enabled
    function prePopulatedMvpCatalogTabData(tabData) {
        if (typeof tabData.is_catalog_mvp_enabled !== 'undefined' && tabData.is_catalog_mvp_enabled) {
            if ($('input[name="is_mvp_catalog_enabled[is_catalog_mvp_enabled]"]').val() !== tabData.is_catalog_mvp_enabled) {
                $('input[name="is_mvp_catalog_enabled[is_catalog_mvp_enabled]"]').next('label').trigger('click');
            }
        }
    }

    // Account Information Prepopulated
    function prePopulatedAccountInformationTabData(tabData) {
        if (typeof tabData.legal_name !== 'undefined' && (tabData.legal_name || tabData.legal_name == null)) {
            $('input[name="information[legal_name]"]').val(tabData.legal_name).trigger('change');
        }
        if (typeof tabData.vat_tax_id !== 'undefined' && (tabData.vat_tax_id || tabData.vat_tax_id == null)) {
            $('input[name="information[vat_tax_id]"]').val(tabData.vat_tax_id).trigger('change');
        }

        if (typeof tabData.reseller_id !== 'undefined' && (tabData.reseller_id || tabData.reseller_id == null)) {
            $('input[name="information[reseller_id]"]').val(tabData.reseller_id).trigger('change');
        }
        if (typeof tabData.comment !== 'undefined' && (tabData.comment || tabData.comment == null)) {
            $('textarea[name="information[comment]"]').val(tabData.comment).trigger('change');
        }
    }

    // Advance Settings Data Prepopulated
    function prePopulatedAdvanceSettingsTabData(tabData) {
        if (typeof tabData.customer_group !== 'undefined' && tabData.customer_group) {
            if ($('div[data-index="settings"] > .fieldset-wrapper-title').height() < 80) {
                $('div[data-index="settings"] > .fieldset-wrapper-title').trigger('click');
            }
            $dom.get('div[name="settings[customer_group_id]"] .admin__action-multiselect.action-select', function () {
                $('div[name="settings[customer_group_id]"]').find('.admin__action-multiselect.action-select').trigger('click');
                $('div[name="settings[customer_group_id]"]').children('.action-select-list').children('ul.admin__action-multiselect-menu-inner._root').each(function () {
                    if (
                        $(this).children('li').children('div').children('label').children('span').text() == tabData.customer_group
                    ) {
                        $(this).children('li').trigger('click');
                        if ($('.confirm-customer-group-change._show .action-accept').is(':visible')) {
                            $('.confirm-customer-group-change._show .action-accept').trigger('click');
                        }
                    }
                });

            });
        }

        if (typeof tabData.allow_quotes !== 'undefined' && tabData.allow_quotes) {
            let isQuoteEnabled = $('input[name="settings[is_quote_enabled]"]').val();
            if (typeof $('input[name="settings[is_quote_enabled]"]').val() == 'string') {
                isQuoteEnabled = false;
            }
            if (isQuoteEnabled !== tabData.allow_quotes) {
                $('input[name="settings[is_quote_enabled]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.enable_purchase_orders !== 'undefined' && tabData.enable_purchase_orders) {
            if ($('input[name="settings[extension_attributes][is_purchase_order_enabled]"]').val() !== tabData.enable_purchase_orders) {
                $('input[name="settings[extension_attributes][is_purchase_order_enabled]"]').next('label').trigger('click');
            }
        }
    }

    //Company Credit Data Prepopulated
    function prePopulatedCompanyCreditTabData(tabData) {
        if (typeof tabData.credit_limit !== 'undefined' && tabData.credit_limit) {
            $('input[name="company_credit[credit_limit]"]').val(tabData.credit_limit).trigger('change');
        }
        setTimeout(() => {
            if (typeof tabData.allow_to_exceed_credit_limit !== 'undefined') {
                let exceedLimit = tabData.allow_to_exceed_credit_limit == '0' || tabData.allow_to_exceed_credit_limit == '' ? 'false' : 'true';
                if ($('input[name="company_credit[exceed_limit]"]').val() !== exceedLimit) {
                    $('input[name="company_credit[exceed_limit]"]').next('label').trigger('click');
                }
            }
        }, 500);
        if (typeof tabData.credit_currency !== 'undefined' && tabData.credit_currency) {
            $('select[name="company_credit[currency_code]"] > option').each(function () {
                if ($(this).text() == tabData.credit_currency) {
                    $(this).attr('selected', 'selected');
                }
            });
        }
    }

    //Payment Data Prepopulated
    function prePopulatedPaymentMethodTabData(tabData) {
        if (typeof tabData.applicable_payment_method !== 'undefined' && tabData.applicable_payment_method) {

            $dom.get('input[value="fedexaccountnumber"][class="admin__control-checkbox"]', function (elem) {
                if (tabData.applicable_payment_method.includes($('input[value="fedexaccountnumber"][class="admin__control-checkbox"]').val())) {
                    if (!$('input[value="fedexaccountnumber"][class="admin__control-checkbox"]').is(':checked')) {
                        $('input[value="fedexaccountnumber"][class="admin__control-checkbox"]').trigger('click');
                        popuplatedFedexAccountOption(tabData);
                    } else {
                        popuplatedFedexAccountOption(tabData);
                    }
                } else {
                    if ($('input[value="fedexaccountnumber"][class="admin__control-checkbox"]').is(':checked')) {
                        $('input[value="fedexaccountnumber"][class="admin__control-checkbox"]').trigger('click');
                    }
                }
                if (tabData.default_payment_method.includes('fedexaccountnumber')) {
                    $('input[name="ko_unique_1"]').trigger('click');
                } else {
                    $('input[name="ko_unique_2"]').trigger('click');
                }

                if (tabData.applicable_payment_method.includes($('input[value="creditcard"][class="admin__control-checkbox"]').val())) {
                    if (!$('input[value="creditcard"][class="admin__control-checkbox"]').is(':checked')) {
                        $('input[value="creditcard"][class="admin__control-checkbox"]').trigger('click');
                        prePopulatedCreditCardOption(tabData);
                    }
                } else {
                    if ($('input[value="creditcard"][class="admin__control-checkbox"]').is(':checked')) {
                        $('input[value="creditcard"][class="admin__control-checkbox"]').trigger('click');
                    }
                }
            });

            if (tabData.fedex_shipping_reference_field !== 'undefined' && tabData.fedex_shipping_reference_field.length) {
                popuplateShippingReferenceData(tabData);
            }
            if (tabData.custom_billing_fields_invoiced_account !== 'undefined' && tabData.custom_billing_fields_invoiced_account.length) {
                handleBillingInvoiceData(tabData);
            }
            if (tabData.custom_billing_fields_credit_card !== 'undefined' && tabData.custom_billing_fields_credit_card.length) {
                popuplateBillingCardData(tabData);
            }
        }
    }

    function prePopulatedCreditCardOption(tabData) {
        if (tabData.credit_card_options.includes('sitecreditcard')) {
            $('input[value="sitecreditcard"]').trigger('click');
        } else {
            $('input[value="new_credit_card"]').prop('checked', true);
            $('input[value="sitecreditcard"]').prop('checked', false);
        }
    }

    //Shipping Reference Data Prepopulted
    function popuplateShippingReferenceData(tabData) {
        setTimeout(() => {
            if (
                typeof tabData.fedex_shipping_reference_field !== 'undefined' &&
                typeof tabData.fedex_shipping_reference_field == 'object' &&
                Object.keys(tabData.fedex_shipping_reference_field).length
            ) {
                let customFieldData = tabData.fedex_shipping_reference_field;
                let length = customFieldData.length;
                let fieldName = 'custom_billing_shipping';
                let customFieldDataPath = 'company_form.company_form.company_payment_methods.custom_billing_shipping.custom_billing_shipping';  
                let count = 0;

                customFieldsDataManagement(customFieldDataPath, fieldName, length, customFieldData, count);
            }
        }, 800);
    }

    //Billing Invoiced Data Prepopulated
    function handleBillingInvoiceData(tabData) {
        setTimeout(() => {
            if (
                typeof tabData.custom_billing_fields_invoiced_account !== 'undefined' &&
                typeof tabData.custom_billing_fields_invoiced_account == 'object' &&
                Object.keys(tabData.custom_billing_fields_invoiced_account).length
            ) {
                let customFieldData = tabData.custom_billing_fields_invoiced_account;
                let length = customFieldData.length;
                let fieldName = 'custom_billing_invoiced';
                let customBillingInvoicePath = 'company_form.company_form.company_payment_methods.custom_billing_invoiced.custom_billing_invoiced';  
                let count = 0;

                customFieldsDataManagement(customBillingInvoicePath, fieldName, length, customFieldData, count);
            }
        }, 2000);
    }

    //Billing Card Data Prepopulated
    function popuplateBillingCardData(tabData) {

        setTimeout(() => {
            if (
                typeof tabData.custom_billing_fields_credit_card !== 'undefined' &&
                typeof tabData.custom_billing_fields_credit_card == 'object' &&
                Object.keys(tabData.custom_billing_fields_credit_card).length
            ) {
                let customFieldData = tabData.custom_billing_fields_credit_card;
                let length = customFieldData.length;
                let fieldName = 'custom_billing_credit_card';
                let customCcFieldDataPath = 'company_form.company_form.company_payment_methods.custom_billing_credit_card.custom_billing_credit_card';  
                let count = 0;
                customFieldsDataManagement(customCcFieldDataPath, fieldName, length, customFieldData, count);
            }
        }, 2000);
    }

    /** 
     * Custom Fields Data render Management 
     */
    function customFieldsDataManagement(customBillingInvoicePath, fieldName, length, customFieldData, count) {
        
        setTimeout(function() {
            if (count < length) {
                if (
                    fieldName == 'custom_billing_shipping' &&
                    typeof(uiRegistry.get('company_form.company_form.company_payment_methods.'+fieldName+'.'+fieldName).source.data.custom_billing_shipping.length) !== 'undefined' &&
                    uiRegistry.get('company_form.company_form.company_payment_methods.'+fieldName+'.'+fieldName).source.data.custom_billing_shipping.length < length
                ) {
                    uiRegistry.get(customBillingInvoicePath).processingAddChild();
                } else if (
                    fieldName == 'custom_billing_invoiced' &&
                    typeof(uiRegistry.get('company_form.company_form.company_payment_methods.'+fieldName+'.'+fieldName).source.data.custom_billing_invoiced.length) !== 'undefined' &&
                    uiRegistry.get('company_form.company_form.company_payment_methods.'+fieldName+'.'+fieldName).source.data.custom_billing_invoiced.length < length
                ) {
                    uiRegistry.get(customBillingInvoicePath).processingAddChild();
                } else if (fieldName == 'custom_billing_credit_card' &&
                    typeof(uiRegistry.get('company_form.company_form.company_payment_methods.'+fieldName+'.'+fieldName).source.data.custom_billing_credit_card.length) !== 'undefined' &&
                    uiRegistry.get('company_form.company_form.company_payment_methods.'+fieldName+'.'+fieldName).source.data.custom_billing_credit_card.length < length
                ) {
                    uiRegistry.get(customBillingInvoicePath).processingAddChild();
                }               
                
                setTimeout(function() {
                    let index = count - 1;
                    let customFieldPath = customBillingInvoicePath + '.' + index;
                    uiRegistry.get(customFieldPath + '.field_label').value(customFieldData[index].field_label);
                    uiRegistry.get(customFieldPath + '.default').value(customFieldData[index].default);
                    uiRegistry.get(customFieldPath + '.visible').value(customFieldData[index].visible);
                    uiRegistry.get(customFieldPath + '.editable').value(customFieldData[index].editable);
                    uiRegistry.get(customFieldPath + '.required').value(customFieldData[index].required);
                    uiRegistry.get(customFieldPath + '.mask').value(customFieldData[index].mask);
                    uiRegistry.get(customFieldPath + '.custom_mask').value(customFieldData[index].custom_mask);
                    uiRegistry.get(customFieldPath + '.error_message').value(customFieldData[index].error_message);
                }, 2000);
                count++;
                customFieldsDataManagement(customBillingInvoicePath, fieldName, length, customFieldData, count);
            } else {
                return false;
            }
        }, 1000);
    }

    //Fedex account Option Data Prepopulated
    function popuplatedFedexAccountOption(tabData) {
        if (tabData.fedex_account_options.includes($('input[value="legacyaccountnumber"][class="admin__control-radio"]').val())) {
            $('input[value="legacyaccountnumber"').trigger('click');
        }
        if (tabData.fedex_account_options.includes($('input[value="custom_fedex_account"][class="admin__control-radio"]').val())) {
            $('input[value="custom_fedex_account"][class="admin__control-radio"]').trigger('click');
            $dom.get('input[name="company_payment_methods[fxo_account_number]"]', function () {
                if (typeof tabData.fxo_account_number !== 'undefined' && tabData.fxo_account_number) {
                    $('input[name="company_payment_methods[fxo_account_number]"]').val(tabData.fxo_account_number).trigger('change');
                }
            });

            $dom.get('input[name="company_payment_methods[fxo_account_number_editable]"]', function () {
                if (
                    (typeof tabData.fxo_account_number_editable !== 'undefined' && tabData.fxo_account_number_editable) &&
                    (!$('input[name="company_payment_methods[fxo_account_number_editable]"]').is(':checked'))
                ) {
                    $('input[name="company_payment_methods[fxo_account_number_editable]"]').trigger('click');
                }
            });
            $dom.get('input[name="company_payment_methods[fxo_shipping_account_number]"]', function () {
                if (typeof tabData.shipping_account_number !== 'undefined' && tabData.shipping_account_number) {
                    $('input[name="company_payment_methods[fxo_shipping_account_number]"]').val(tabData.shipping_account_number).trigger('change');
                }
            });

            $dom.get('input[name="company_payment_methods[shipping_account_number_editable]"]', function () {
                if (
                    (typeof tabData.shipping_account_number_editable !== 'undefined' && tabData.shipping_account_number_editable) &&
                    (!$('input[name="company_payment_methods[shipping_account_number_editable]"]').is(':checked'))
                ) {
                    $('input[name="company_payment_methods[shipping_account_number_editable]"]').trigger('click');
                }
            });
            $dom.get('input[name="company_payment_methods[fxo_discount_account_number]"]', function () {
                if (typeof tabData.discount_account_number !== 'undefined' && tabData.discount_account_number) {
                    $('input[name="company_payment_methods[fxo_discount_account_number]"]').val(tabData.discount_account_number).trigger('change');
                }
            });
            $dom.get('input[name="company_payment_methods[discount_account_number_editable]"]', function () {
                if (
                    (typeof tabData.discount_account_number_editable !== 'undefined' && tabData.discount_account_number_editable) &&
                    (!$('input[name="company_payment_methods[discount_account_number_editable]"]').is(':checked'))
                ) {
                    $('input[name="company_payment_methods[discount_account_number_editable]"]').trigger('click');
                }
            });
        }
    }

    // Order Setting Tab Data Prepopulated
    function prePopulatedOrderSetting(tabData) {
        if (typeof tabData.order_notes !== 'undefined' && tabData.order_notes) {
            setTimeout(() => {
                $('textarea[name="production_location[order_notes]"]').val(tabData.order_notes);
            }, 1500);
        }
        if (typeof tabData.terms_and_conditions !== 'undefined' && tabData.terms_and_conditions) {
            if ($('input[name="production_location[terms_and_conditions]"]').val() !== tabData.terms_and_conditions) {
                $('input[name="production_location[terms_and_conditions]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.is_promo_discount_enabled !== 'undefined' && tabData.is_promo_discount_enabled) {
            if ($('input[name="production_location[is_promo_discount_enabled]"]').val() !== tabData.is_promo_discount_enabled) {
                $('input[name="production_location[is_promo_discount_enabled]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.is_account_discount_enabled !== 'undefined' && tabData.is_account_discount_enabled) {
            if ($('input[name="production_location[is_account_discount_enabled]"]').val() !== tabData.is_account_discount_enabled) {
                $('input[name="production_location[is_account_discount_enabled]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.epro_new_platform_order_creation !== 'undefined' && tabData.epro_new_platform_order_creation) {
            if ($('input[name="production_location[epro_new_platform_order_creation]"]').val() !== tabData.epro_new_platform_order_creation) {
                $('input[name="production_location[epro_new_platform_order_creation]"]').next('label').trigger('click');
            }
        }
    }

    // Delivery Options Tab Data Prepopulated
    function prePopulatedDeliveryOptionsTabData(tabData) {
        if (typeof tabData.shipment !== 'undefined' && tabData.shipment) {
            if ($('input[name="shipping_options[is_delivery]"]').val() !== tabData.shipment) {
                $('input[name="shipping_options[is_delivery]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.allowed_shipping_options !== 'undefined' && tabData.allowed_shipping_options) {
            setTimeout(() => {
                var selectElements = document.querySelectorAll('select[name="shipping_options[allowed_delivery_options]"]');
                if (selectElements.length > 0) {
                    for (var i = 0; i < selectElements.length; i++) {
                        var selectElement = selectElements[i];
                        var options = selectElement.options;
                        for (var j = 0; j < options.length; j++) {
                            var option = options[j];
                            var optionValue = option.value;
                            if (tabData.allowed_shipping_options.includes(optionValue)) {
                                option.selected = true;
                            } else {
                                option.selected = false;
                            }
                        }
                    }
                }
            }, 1500);
        }
        if (typeof tabData.recipient_address_from_po !== 'undefined' && tabData.recipient_address_from_po) {
            if ($('input[name="shipping_options[recipient_address_from_po]"]').val() !== tabData.recipient_address_from_po) {
                $('input[name="shipping_options[recipient_address_from_po]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.store_pickup !== 'undefined' && tabData.store_pickup) {
            if ($('input[name="shipping_options[is_pickup]"]').val() !== tabData.store_pickup) {
                $('input[name="shipping_options[is_pickup]"]').next('label').trigger('click');
            }
        }
        if (typeof tabData.hc_toggle !== 'undefined' && tabData.hc_toggle) {
            if ($('input[name="shipping_options[hc_toggle]"]').val() !== tabData.hc_toggle) {
                $('input[name="shipping_options[hc_toggle]"]').next('label').trigger('click');
            }
        }

    }

    // Company Admin Tab Data Prepopulated
    function prePopulatedCompanyAdminTabData(tabData) {
        if (typeof tabData.contact_number !== 'undefined' && tabData.contact_number) {
            $('input[name="company_admin[contact_number]"]').val(tabData.contact_number);
        }
        if (typeof tabData.ext !== 'undefined' && tabData.ext) {
            $('input[name="company_admin[contact_ext]"]').val(tabData.ext);
        }
        if (typeof tabData.customer_status !== 'undefined' && tabData.customer_status) {
            $('select[name="company_admin[customer_status]"] > option').each(function () {
                if ($(this).text() == tabData.customer_status) {
                    $(this).attr('selected', 'selected');
                }
            });
        }
        if (typeof tabData.website !== 'undefined' && tabData.website) {
            $('select[name="company_admin[website_id]"] > option').each(function () {
                if ($(this).text() == tabData.website) {
                    $(this).attr('selected', 'selected');
                }
            });
        }
        if (typeof tabData.email !== 'undefined' && tabData.email) {
            setTimeout(() => {
                $('input[name="company_admin[email]"]').each(function (index) {
                    if (index == 0) {
                        $(this).val(tabData.email).trigger('change');
                    }
                });
            }, 1500);
        }
        if (typeof tabData.first_name !== 'undefined' && tabData.first_name) {
            $('input[name="company_admin[firstname]"]').val(tabData.first_name).trigger('change');
        }
        if (typeof tabData.last_name !== 'undefined' && tabData.last_name) {
            $('input[name="company_admin[lastname]"]').val(tabData.last_name).trigger('change');
        }
        if (typeof tabData.fcl_profile_contact_number !== 'undefined' && tabData.fcl_profile_contact_number) {
            $('input[name="company_admin[fcl_profile_contact_number]"]').val(tabData.fcl_profile_contact_number).trigger('change');
        }
        if (typeof tabData.secondary_email !== 'undefined' && tabData.secondary_email) {
            $('input[name="company_admin[secondary_email]"]').val(tabData.secondary_email).trigger('change');
        }
    }

    // Authentication Rule Tab Data Prepopulated
    function prePopulateAuthenticationRuleTabData(tabData) {
        if (typeof tabData.storefront_login_method_option !== 'undefined' &&
            tabData.storefront_login_method_option) {
            var selectedValue = tabData.storefront_login_method_option;
            var selectElement = $('select[name="authentication_rule[storefront_login_method]"]');
            selectElement.val(selectedValue).change();
            selectElement.change(function () {
                var selectedOptionValue = selectElement.val();
                if (selectedOptionValue === 'commercial_store_sso' || selectedOptionValue === 'commercial_store_sso_with_fcl') {
                    // Show fields for SSO
                    $('input[name="authentication_rule[sso_login_url]"]').show();
                    $('input[name="authentication_rule[sso_logout_url]"]').show();
                    $('input[name="authentication_rule[sso_idp]"]').show();
                    // Hide fields for commercial_store_epro
                    $('input[name="authentication_rule[domain_name]"]').hide();
                    $('input[name="authentication_rule[network_id]"]').hide();
                    $('input[name="authentication_rule[site_name]"]').hide();
                    $('.auth_rule.extrinsic').show();
                    // Set values for SSO fields
                    $('input[name="authentication_rule[sso_login_url]"]').val(tabData.sso_login_url).trigger('change');
                    $('input[name="authentication_rule[sso_logout_url]"]').val(tabData.sso_logout_url).trigger('change');
                    $('input[name="authentication_rule[sso_idp]"]').val(tabData.sso_idp).trigger('change');
                    $('input[name="authentication_rule[sso_group]"]').val(tabData.sso_group).trigger('change');
                } else if (selectedOptionValue === 'commercial_store_epro') {
                    // Show fields for commercial_store_epro
                    $('input[name="authentication_rule[domain_name]"]').show();
                    $('input[name="authentication_rule[network_id]"]').show();
                    $('input[name="authentication_rule[site_name]"]').show();
                    // Hide fields for SSO
                    $('input[name="authentication_rule[sso_login_url]').hide();
                    $('input[name="authentication_rule[sso_logout_url]"]').hide();
                    $('input[name="authentication_rule[sso_idp]"]').hide();
                    $('input[name="authentication_rule[sso_group]"]').hide();
                    // Set values for commercial_store_epro fields
                    $('input[name="authentication_rule[domain_name]"]').val(tabData.domain_name).trigger('change');
                    $('input[name="authentication_rule[network_id]"]').val(tabData.domain_id).trigger('change');
                    $('input[name="authentication_rule[site_name]"]').val(tabData.site_name).trigger('change');
                } else if (selectedOptionValue === 'commercial_store_wlgn') {
                    // Show and prepopulate the "self_reg_login_method" select field for FCL
                    var selfRegLoginMethodSelect = $('select[name="authentication_rule[self_reg_login_method]"]');
                    var selfRegUserVerificationMessage = $('select[name="authentication_rule[email_verification_user_message]"]');
                    selfRegLoginMethodSelect.show();
                    selfRegUserVerificationMessage.hide();
                    selfRegLoginMethodSelect.val(tabData.self_reg_data.self_reg_login_method).trigger('change');
                    if (typeof tabData.self_reg_data !== 'undefined' && tabData.self_reg_data) {
                        setTimeout(() => {
                            var selfRegLoginMethod = tabData.self_reg_data.self_reg_login_method;
                            if (selfRegLoginMethod === 'domain_registration') {
                                var domainsTextarea = $('textarea[name="authentication_rule[domains]"]');
                                domainsTextarea.show().val(tabData.self_reg_data.domains).trigger('change');
                                var domainsFieldContainer = domainsTextarea.closest('.admin__field');
                                domainsFieldContainer.show();
                                if (typeof tabData.self_reg_data.error_message !== 'undefined' && tabData.self_reg_data.error_message) {
                                    setTimeout(() => {
                                        $('textarea[name="authentication_rule[error_message]"]').val(tabData.self_reg_data.error_message).trigger('change');
                                    }, 1500)
                                }
                            } else if (selfRegLoginMethod === 'admin_approval') {
                                if (typeof tabData.self_reg_data.error_message !== 'undefined' && tabData.self_reg_data.error_message) {
                                    setTimeout(() => {
                                        $('textarea[name="authentication_rule[error_message]"]').val(tabData.self_reg_data.error_message).trigger('change');
                                    }, 1500)
                                }
                            } else {
                                $('textarea[name="authentication_rule[error_message]"]').hide();
                            }
                        }, 1500);
                    }
                } else {
                    $('input[name="authentication_rule[sso_login_url]"]').hide();
                    $('input[name="authentication_rule[sso_logout_url]"]').hide();
                    $('input[name="authentication_rule[sso_idp]"]').hide();
                    $('input[name="authentication_rule[sso_group]"]').hide();
                    $('input[name="authentication_rule[domain_name]"]').hide();
                    $('input[name="authentication_rule[network_id]"]').hide();
                    $('input[name="authentication_rule[site_name]"]').hide();
                }
                $('select[name="authentication_rule[acceptance_option]"]').val(tabData.acceptance_option).trigger('change');
                if (tabData.acceptance_option === 'extrinsic') {
                    $('.auth_rule.extrinsic').show();
                    var extrinsicTable = $('#modulename_tbl_data tbody');
                    extrinsicTable.empty();
                    extrinsicTable.append('<tr class="header"><th class="_required">Extrinsic Authentication Code</th><th></th></tr>');
                    for (var i = 0; i < tabData.rule_data.length; i++) {
                        var ruleCode = tabData.rule_data[i].rule_code;
                        var newRow = $('<tr class="data-row"></tr>');
                        newRow.append('<td class="dynElemRow _required"><input data-form-part="company_form" name="authentication_rule[rule_code_e][' + i + ']" value="' + ruleCode + '" class="input-text required-option admin__control-text dynrule"></td>');
                        newRow.append('<td class="col-delete data-grid-actions-cell"><button onclick="javascript:jQuery(this).parent().parent().remove();" class="action- scalable delete delete-option action-delete" type="button" title="Remove"><span>Remove</span></button></td>');
                        extrinsicTable.append(newRow);
                    }
                } else {
                    $('.auth_rule.extrinsic').hide();
                }
                if (tabData.acceptance_option === 'contact') {
                    $('.auth_rule.contact').show();
                } else {
                    $('.auth_rule.contact').hide();
                }
                if (tabData.acceptance_option === 'contact' || tabData.acceptance_option === 'both') {
                    $('.auth_rule.contact').show();
                } else {
                    $('.auth_rule.contact').hide();
                }
                if (tabData.acceptance_option === 'both') {
                    $('.auth_rule.extrinsic').show();
                }
                if ((tabData.acceptance_option === 'extrinsic' || tabData.acceptance_option === 'both') && tabData.rule_data) {
                    var extrinsicTable = $('#modulename_tbl_data tbody');
                    extrinsicTable.empty();
                    extrinsicTable.append('<tr class="header"><th class="_required">Extrinsic Authentication Code</th><th></th></tr>');
                    var ruleData = tabData.rule_data.filter(function (rule) {
                        return rule.rule_type === 'extrinsic';
                    });
                    for (var i = 0; i < ruleData.length; i++) {
                        var ruleCode = ruleData[i].rule_code;
                        var newRow = $('<tr class="data-row"></tr>');
                        newRow.append('<td class="dynElemRow _required"><input data-form-part="company_form" name="authentication_rule[rule_code_e][' + i + ']" value="' + ruleCode + '" class="input-text required-option admin__control-text dynrule"></td>');
                        newRow.append('<td class="col-delete data-grid-actions-cell"><button onclick="javascript:jQuery(this).parent().parent().remove();" class="action- scalable delete delete-option action-delete" type="button" title="Remove"><span>Remove</span></button></td>');
                        extrinsicTable.append(newRow);
                    }
                }
            });
            selectElement.change();
        }
    }

    //Legal Address Tab Data Prepopulated
    function prePopulatedLegalAddressTabData(tabData) {
        setTimeout(() => {
            if (typeof tabData.street[0] !== 'undefined' && tabData.street[0]) {
                $('input[name="address[street][0]"]').val(tabData.street[0]).trigger('change');
            }
            if (typeof tabData.street[1] !== 'undefined' && tabData.street[1]) {
                $('input[name="address[street][1]"]').val(tabData.street[1]).trigger('change');
            }
        }, 1000);

        if (typeof tabData.city !== 'undefined' && tabData.city) {
            $('input[name="address[city]"]').val(tabData.city).trigger('change');
        }

        $('select[name="address[country_id]"] > option').each(function () {
            if ($(this).text() == tabData.country_id) {
                $(this).attr('selected', true).trigger('change');
            }
        });

        setTimeout(() => {
            $('select[name="address[region_id]"] > option').each(function () {
                if ($(this).text() == tabData.state_or_province) {
                    $(this).attr('selected', true).trigger('change');
                }
            });
        }, 1000);

        if (typeof tabData.postcode !== 'undefined' && tabData.postcode) {
            $('input[name="address[postcode]"]').val(tabData.postcode).trigger('change');
        }
        if (typeof tabData.telephone !== 'undefined' && tabData.telephone) {
            $('input[name="address[telephone]"]').val(tabData.telephone).trigger('change');
        }
    }

    return {
        init: init
    }
});
