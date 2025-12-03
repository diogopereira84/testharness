/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/ui-select',
    "Magento_Ui/js/modal/modal",
    "iframe",
    "Magento_Ui/js/lib/view/utils/dom-observer"
], function (Select,modal,iframe, dom) {
    'use strict';
    var counter = 0;
    var firstLoadAttributeSet = "";
    return Select.extend({
        defaults: {
            listens: {
                'value': 'changeFormSubmitUrl'
            },
            modules: {
                formProvider: '${ $.provider }'
            }
        },

        // B-1556308: Function to genrate uuid for SKU
        genrateUUID(value) {
            var d = new Date().getTime();//Timestamp
            var d2 = ((typeof performance !== 'undefined') && performance.now && (performance.now()*1000)) || 0;//Time in microseconds since page-load or 0 if unsupported
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                var r = Math.random() * 16;//random number between 0 and 16
                if(d > 0){//Use timestamp until depleted
                    r = (d + r)%16 | 0;
                    d = Math.floor(d/16);
                } else {//Use microseconds since page-load if supported
                    r = (d2 + r)%16 | 0;
                    d2 = Math.floor(d2/16);
                }
                return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
            });
        },

        // B-1556308: Function for SKU, Price and external product readonly and uuid for sku for attribute set print on demand
        readonlySections : function(value) {
            let uuid = this.genrateUUID(value);
            let waitForProductFormLoad = setInterval(function () {
                if(jQuery("textarea[name='product[external_prod]']").is(":visible") && jQuery('div[data-index="attribute_set_id"] .admin__action-multiselect-text').length) {
                    let selctedOption = jQuery('div[data-index="attribute_set_id"] .admin__action-multiselect-text').html();

                    if(counter == 1) {
                        firstLoadAttributeSet = selctedOption;
                    }

                    if(selctedOption == "PrintOnDemand") {
                        if (jQuery("input[name='product[pod2_0_editable]']").val() == 1) {
                            jQuery('div[data-index="attribute_set_id"] .admin__action-multiselect-text').click(false);
                        }

                        if (jQuery("input[name='product[sku]']").val() == '' ||
                        window.location.toString().includes("catalog/product/new")) {
                            jQuery("input[name='product[sku]']").val(uuid).change();
                        }
                        if (jQuery("input[name='product[price]']").val() == '') {
                            jQuery("input[name='product[price]']").val('0.01').change();
                        }
                        jQuery("input[name='product[sku]']").attr('readonly', true);
                        jQuery("input[name='product[price]']").attr('readonly', true);
                        jQuery("textarea[name='product[external_prod]']").attr('readonly', true);
                        jQuery("input[name='product[is_pending_review]']").attr('disabled', true);
                        jQuery('[data-index="pod2_0_editable"]').css('display','block');
                        if(!(window.location.href.indexOf("catalog/product/edit") > -1 )){
                            jQuery("input[name='product[pod2_0_editable]']").click();
                        }
                        //Disable send to customer radio button when sent_to_customer is 1
                        if (jQuery("input[name='product[sent_to_customer]']").val() == 1) {
                            jQuery("input[name='product[sent_to_customer]']").attr('disabled', true);
                            jQuery('[data-index="sent_to_customer"]').addClass('_disabled');
                        }
                        jQuery("input[name='product[pod2_0_editable]']").attr('disabled', true);
                        dom.get('div[data-index="category_ids"] label.admin__action-multiselect-label', function (elem) {
                            jQuery("div[data-index='category_ids'] label.admin__action-multiselect-label:contains('(Unpublished)')").each(function() {
                                jQuery(this).html(function(_, html) {
                                   return html.replace(/(Unpublished)/g, '<b>$1</b>');
                                });
                            });

                            jQuery('div[data-index="category_ids"] span.admin__action-multiselect-crumb span:contains("(Unpublished)")').each(function() {
                                jQuery(this).html(function(_, html) {
                                   return html.replace(/(Unpublished)/g, '<b>$1</b>');
                                });
                            });
                        });
                    } else {
                        if (!jQuery("input[name='product[sku]']").val()) {
                            jQuery("input[name='product[sku]']").val('');
                        }
                        if (!jQuery("input[name='product[price]']").val()) {
                            jQuery("input[name='product[price]']").val('');
                        }
                        jQuery("input[name='product[sku]']").attr('readonly', false);
                        jQuery("input[name='product[price]']").attr('readonly', false);
                        jQuery("textarea[name='product[external_prod]']").attr('readonly', false);
                        jQuery("input[name='product[is_pending_review]']").attr('disabled', true);
                        jQuery('[data-index="pod2_0_editable"]').css('display', 'none');
                    }
                    clearInterval(waitForProductFormLoad);
                }
            }, 3000);
        },

        // B-1556307: Function for Tax Class, Quantity, Weight, Country of Manufacture, Enable RMA, Is Customizable, Has Canva Design and Admin User ID should be hidden for attribute set print on demand
        togglePodAttributes: function (value) {
            let waitForProductFormLoad = setInterval(function () {
                jQuery('div[data-index="product_updated_date"]').hide();
                jQuery('div[data-index="product_created_date"]').hide();
                jQuery('div[data-index="product_attribute_sets_id"]').hide();
                if(jQuery(".page-footer").is(":visible") && jQuery('div[data-index="attribute_set_id"] .admin__action-multiselect-text').length) {

                    let podAttributes = [
                        "tax_class_id",
                        "quantity_and_stock_status_qty",
                        "container_weight",
                        "container_is_returnable",
                        "customizable",
                        "admin_user_id",
                        "is_new"
                    ];

                    let selctedOption = jQuery('div[data-index="attribute_set_id"] .admin__action-multiselect-text').html();

                    if(selctedOption == "PrintOnDemand") {
                        if (jQuery('body').hasClass('mazegeeks_download_catalog_items')) {
                            let removeItem = "customizable";
                            podAttributes = jQuery.grep(podAttributes, function(value) {
                                return value != removeItem;
                            });
                        }

                        podAttributes.forEach(function(podAttribute) {
                            if(podAttribute === "tax_class_id"
                                || podAttribute === "customizable"
                                || podAttribute === "has_canva_design"
                                || podAttribute === "admin_user_id"
                                || podAttribute === "country_of_manufacture"
                                || podAttribute === "is_new"
                            ){
                                let container = 'div[data-index="'+podAttribute+'"]';
                                if(jQuery(container).length > 0) {
                                    jQuery(container).hide();
                                }
                            }else {
                                let container = 'fieldset[data-index="'+podAttribute+'"]';
                                if(jQuery(container).length > 0) {
                                    jQuery(container).hide();
                                }
                            }
                        });
                    } else {
                        jQuery("input[name='product[customizable]']").prop("checked", false).change();
                        jQuery('[data-index="pod2_0_editable"]').css('display', 'none');
                        jQuery('[data-index="catalog_description"]').css('display', 'none');
                        podAttributes.forEach(function(podAttribute) {
                            if (podAttribute === "tax_class_id"
                                || podAttribute === "customizable"
                                || podAttribute === "has_canva_design"
                                || podAttribute === "admin_user_id"
                                || podAttribute === "country_of_manufacture"
                            ) {
                                let container = 'div[data-index="'+podAttribute+'"]';
                                if (jQuery(container).length > 0) {
                                    jQuery(container).show();
                                }
                            } else {
                                let container = 'fieldset[data-index="'+podAttribute+'"]';
                                if (jQuery(container).length > 0) {
                                    jQuery(container).show();
                                }
                            }
                        });
                    }
                    clearInterval(waitForProductFormLoad);
                }
            }, 3000);
        },

        // B-1556309: Added new fields for Print on Demand attribute
        addNewFieldsForpod : function() {
            let self = this;
            let waitForProductFormLoad = setInterval(function () {
                if(jQuery("textarea[name='product[external_prod]']").is(":visible") && jQuery('div[data-index="attribute_set_id"] .admin__action-multiselect-text').length) {
                    let selctedOption = jQuery('div[data-index="attribute_set_id"] .admin__action-multiselect-text').html();
                    if (selctedOption == "PrintOnDemand") {
                        if (jQuery('.configure-product-pod').length === 0 && jQuery("input[name='product[pod2_0_editable]']").val() == "1") {
                            jQuery('div[data-index="attribute_set_id"]')
                            .after("<div class='admin__field configure-product-pod'><div class='admin__field-label configure-product-pod-lable'><label><span>Configure Product</span></label></div><div class='admin__field-control'><button style='width: 170px;height: 45px;font-size: 18px;font-family: 'Open Sans';line-height: 24px;letter-spacing: 1.1875px;text-transform:uppercase;'id='configure-product' title='Configure Product' class='action-default primary configure-button'><span>CONFIGURE</span></button></div></div>");
                        }
                        jQuery('div[data-index="start_date_pod"]').show();
                        jQuery('div[data-index="end_date_pod"]').show();
                        var options = {
                            type: 'popup',
                            responsive: true,
                            innerScroll: true,
                            buttons: []
                        };
                        var popup = modal(options, $('#custom-model-popup'));
                        jQuery(".configure-button").on('click', function(event){
                            event.preventDefault();
                            if (jQuery('body').hasClass('catalog_mvp_custom_docs')) {
                                jQuery(".modal-inner-wrap").css("width", "1000px");
                            }
                            jQuery('#custom-model-popup').modal(options, $('#custom-model-popup')).modal('openModal');
                        });

                        if (jQuery('body').hasClass('mazegeeks_download_catalog_items')) {
                            self.downloadFieldForNonConfProd();
                        }

                        if (jQuery('body').hasClass('catalog_mvp_custom_docs')) {
                            self.customDocFieldForNonConfProd();
                        }

                        // Send to customer field for tooltips added
                        self.addTooltipsForSendToCustomer();
                    } else {
                        if (jQuery('.configure-product-pod').length !== 0) {
                            jQuery(".configure-product-pod").remove();
                            jQuery(".download-field").remove();
                            jQuery(".custom-doc-field").remove();
                        }
                        jQuery('div[data-index="start_date_pod"]').hide();
                        jQuery('div[data-index="end_date_pod"]').hide();
                    }
                    clearInterval(waitForProductFormLoad);
                }
            }, 3000);
        },

        // Download field for non configured product
        downloadFieldForNonConfProd : function() {
            if (jQuery("input[name='product[pod2_0_editable]']").val() != "1") {
                return;
            }
            const downloadTooltip = '<svg class="download-field-tooltip" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none"><rect opacity="0.01" width="32" height="32" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M16 26.6667C10.109 26.6667 5.33337 21.8911 5.33337 16C5.33337 10.109 10.109 5.33337 16 5.33337C21.8911 5.33337 26.6667 10.109 26.6667 16C26.6667 21.8911 21.8911 26.6667 16 26.6667ZM16 25.3334C21.1547 25.3334 25.3334 21.1547 25.3334 16C25.3334 10.8454 21.1547 6.66671 16 6.66671C10.8454 6.66671 6.66671 10.8454 6.66671 16C6.66671 21.1547 10.8454 25.3334 16 25.3334ZM18.3334 20.6667H16.3345L16.3334 20.6667L16.3322 20.6667H14.3334C13.9652 20.6667 13.6667 20.3682 13.6667 20C13.6667 19.6319 13.9652 19.3334 14.3334 19.3334H15.6667V15H15C14.6319 15 14.3334 14.7016 14.3334 14.3334C14.3334 13.9652 14.6319 13.6667 15 13.6667H16.3334C16.7016 13.6667 17 13.9652 17 14.3334V19.3334H18.3334C18.7016 19.3334 19 19.6319 19 20C19 20.3682 18.7016 20.6667 18.3334 20.6667ZM15 11C15 10.45 15.446 10 16 10C16.55 10 17 10.45 17 11C17 11.554 16.55 12 16 12C15.446 12 15 11.554 15 11Z" fill="#333333"/></svg>';;
            const downloadIconGrey = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="none"><rect opacity="0.01" width="48" height="48" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M29.0391 22.4819L32.2331 22.5C33.067 22.5048 33.4801 23.5144 32.8887 24.1024L24.661 32.282C24.2945 32.6463 23.7024 32.6455 23.337 32.2801L15.1339 24.0758C14.5434 23.4851 14.9617 22.4754 15.7969 22.4754H18.9609V10.5234C18.9609 10.0057 19.3807 9.58594 19.8984 9.58594H28.1016C28.6193 9.58594 29.0391 10.0057 29.0391 10.5234V22.4819ZM18.06 24.3504L24.002 30.2933L29.9679 24.3622L28.0962 24.3515C27.5806 24.3486 27.1641 23.9298 27.1641 23.4141V11.4609H20.8359V23.4129C20.8359 23.9307 20.4162 24.3504 19.8984 24.3504H18.06ZM11 32V37H37V32C37 31.4477 37.4477 31 38 31C38.5523 31 39 31.4477 39 32V38C39 38.5523 38.5523 39 38 39H10C9.44772 39 9 38.5523 9 38V32C9 31.4477 9.44772 31 10 31C10.5523 31 11 31.4477 11 32Z" fill="#8E8E8E"/></svg>';
            jQuery('div[data-index="name"]').after("<div class='admin__field download-field download-field-non-configured'><div class='admin__field-label configure-product-pod-lable'><label style='margin-right: 18px;'><span>Download File(s)</span></label><div class='tooltip-content' style='display: none;'>Download file(s) capability is not enabled because there is no file configured.</div><div class='download-tool-tip'>"+downloadTooltip+"</div></div><div class='admin__field-control'>"+downloadIconGrey+"</div></div>");
            jQuery(document).on('mouseenter', '.download-field-tooltip, .tooltip-content', function () {
                jQuery('.tooltip-content').show();

            }).on('mouseleave', '.download-field-tooltip, .tooltip-content', function () {
                jQuery('.tooltip-content').hide();
            });
        },

        // Download field for configured product
        downloadFieldForConfProd : function() {
            if(jQuery("input[name='product[pod2_0_editable]']").val() != "1")
            {
                return;
            }
            const downloadIconBlue='<svg class="download-configure-img" style="cursor: pointer;"xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="none"><rect opacity="0.01" width="48" height="48" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M29.0391 22.4819L32.2331 22.5C33.067 22.5048 33.4801 23.5144 32.8887 24.1024L24.661 32.282C24.2945 32.6463 23.7024 32.6455 23.337 32.2801L15.1339 24.0758C14.5434 23.4851 14.9617 22.4754 15.7969 22.4754H18.9609V10.5234C18.9609 10.0057 19.3807 9.58594 19.8984 9.58594H28.1016C28.6193 9.58594 29.0391 10.0057 29.0391 10.5234V22.4819ZM18.06 24.3504L24.002 30.2933L29.9679 24.3622L28.0962 24.3515C27.5806 24.3486 27.1641 23.9298 27.1641 23.4141V11.4609H20.8359V23.4129C20.8359 23.9307 20.4162 24.3504 19.8984 24.3504H18.06ZM11 32V37H37V32C37 31.4477 37.4477 31 38 31C38.5523 31 39 31.4477 39 32V38C39 38.5523 38.5523 39 38 39H10C9.44772 39 9 38.5523 9 38V32C9 31.4477 9.44772 31 10 31C10.5523 31 11 31.4477 11 32Z" fill="#007AB7"/></svg>';
            jQuery('div[data-index="name"]').after("<div class='admin__field download-field download-field-configured'><div class='admin__field-label configure-product-pod-lable'><label><span>Download File(s)</span></label></div><div class='admin__field-control'>"+downloadIconBlue+"</div></div>");

        },

        // Custom Doc field for non configured product
        customDocFieldForNonConfProd : function() {
            let self = this;
            const tooltipConst = '<svg class="custom-doc-field-tooltip" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none"><rect opacity="0.01" width="32" height="32" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M15.9999 26.6666C10.1089 26.6666 5.33325 21.891 5.33325 15.9999C5.33325 10.1089 10.1089 5.33325 15.9999 5.33325C21.891 5.33325 26.6666 10.1089 26.6666 15.9999C26.6666 21.891 21.891 26.6666 15.9999 26.6666ZM15.9999 25.3333C21.1546 25.3333 25.3333 21.1546 25.3333 15.9999C25.3333 10.8453 21.1546 6.66658 15.9999 6.66658C10.8453 6.66658 6.66658 10.8453 6.66658 15.9999C6.66658 21.1546 10.8453 25.3333 15.9999 25.3333ZM18.3333 20.6666H16.3344L16.3333 20.6666L16.3321 20.6666H14.3333C13.9651 20.6666 13.6666 20.3681 13.6666 19.9999C13.6666 19.6317 13.9651 19.3333 14.3333 19.3333H15.6666V14.9999H14.9999C14.6317 14.9999 14.3333 14.7014 14.3333 14.3333C14.3333 13.9651 14.6317 13.6666 14.9999 13.6666H16.3333C16.7014 13.6666 16.9999 13.9651 16.9999 14.3333V19.3333H18.3333C18.7014 19.3333 18.9999 19.6317 18.9999 19.9999C18.9999 20.3681 18.7014 20.6666 18.3333 20.6666ZM14.9999 10.9999C14.9999 10.4499 15.4459 9.99992 15.9999 9.99992C16.5499 9.99992 16.9999 10.4499 16.9999 10.9999C16.9999 11.5539 16.5499 11.9999 15.9999 11.9999C15.4459 11.9999 14.9999 11.5539 14.9999 10.9999Z" fill="#4D148C"/></svg>';
            let appendElement;
            if(jQuery('.configure-product-pod').length > 0) {
                appendElement = jQuery('.configure-product-pod');
            } else {
                appendElement = jQuery('.edit-product-pod');
            }
            appendElement.after("<div class='admin__field custom-doc-field custom-doc-field-non-configured'><div class='admin__field-label customize-product-label configure-product-pod-lable'><label><span>Customize<br/>Product</span></label><div class='custom-doc-tooltip-content' style='display: none;'>The uploaded file has fields that can be <br/>customized.</div><div class='svg-tool-tip'>"+tooltipConst+"</div></div><div class='admin__field-control'><button type='button' class='customize'>SET UP</div></div>");

            self.customDocTooltip();
        },

        // Customer timezone for pod products
        customerTimezoneForProd : function() {
            let selctedOption = jQuery('div[data-index="attribute_set_id"] .admin__action-multiselect-text').html();
            if(selctedOption == "PrintOnDemand") {
                let customertimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                jQuery("input[name='extraconfiguratorvalue[customertimezone]']").val(customertimezone);
                jQuery("input[name='extraconfiguratorvalue[customertimezone]']").trigger('change');
                let productId = jQuery("input[name='extraconfiguratorvalue[entity_id]']").val();
                let timezoneUrl = window.customerTimeZoneUrl;
                jQuery.ajax({
                    type: "post",
                    url: timezoneUrl,
                    data: {
                        custimezone:customertimezone,
                        productId:productId
                    },
                    success: function(response) {
                        if (response.success == 'true') {
                            jQuery("input[name='product[start_date_pod]']").val(response.startpsttime);
                            jQuery("input[name='product[start_date_pod]']").trigger('change');
                            jQuery("input[name='extraconfiguratorvalue[custom_start_date]']").val(response.startpsttime);
                            jQuery("input[name='extraconfiguratorvalue[custom_start_date]']").trigger('change');
                            if (response.endpstTime) {
                                jQuery("input[name='product[end_date_pod]']").val(response.endpstTime);
                                jQuery("input[name='product[end_date_pod]']").trigger('change');
                                jQuery("input[name='extraconfiguratorvalue[custom_end_date]']").val(response.endpstTime);
                                jQuery("input[name='extraconfiguratorvalue[custom_end_date]']").trigger('change');
                            }
                        }
                    }
                });
            }

            jQuery("input[name='product[start_date_pod]']").change(function() {
                let startDate = jQuery("input[name='product[start_date_pod]']").val();
                jQuery("input[name='extraconfiguratorvalue[custom_start_date]']").val(startDate);
                jQuery("input[name='extraconfiguratorvalue[custom_start_date]']").trigger('change');
            });

            jQuery("input[name='product[end_date_pod]']").change(function() {
                let endDate = jQuery("input[name='product[end_date_pod]']").val();
                jQuery("input[name='extraconfiguratorvalue[custom_end_date]']").val(endDate);
                jQuery("input[name='extraconfiguratorvalue[custom_end_date]']").trigger('change');
            });
        },

        // Custom Doc field for configured product
        customDocFieldForConfProd : function() {
            let self = this;
            const tooltipConst = '<svg class="custom-doc-field-tooltip" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none"><rect opacity="0.01" width="32" height="32" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M15.9999 26.6666C10.1089 26.6666 5.33325 21.891 5.33325 15.9999C5.33325 10.1089 10.1089 5.33325 15.9999 5.33325C21.891 5.33325 26.6666 10.1089 26.6666 15.9999C26.6666 21.891 21.891 26.6666 15.9999 26.6666ZM15.9999 25.3333C21.1546 25.3333 25.3333 21.1546 25.3333 15.9999C25.3333 10.8453 21.1546 6.66658 15.9999 6.66658C10.8453 6.66658 6.66658 10.8453 6.66658 15.9999C6.66658 21.1546 10.8453 25.3333 15.9999 25.3333ZM18.3333 20.6666H16.3344L16.3333 20.6666L16.3321 20.6666H14.3333C13.9651 20.6666 13.6666 20.3681 13.6666 19.9999C13.6666 19.6317 13.9651 19.3333 14.3333 19.3333H15.6666V14.9999H14.9999C14.6317 14.9999 14.3333 14.7014 14.3333 14.3333C14.3333 13.9651 14.6317 13.6666 14.9999 13.6666H16.3333C16.7014 13.6666 16.9999 13.9651 16.9999 14.3333V19.3333H18.3333C18.7014 19.3333 18.9999 19.6317 18.9999 19.9999C18.9999 20.3681 18.7014 20.6666 18.3333 20.6666ZM14.9999 10.9999C14.9999 10.4499 15.4459 9.99992 15.9999 9.99992C16.5499 9.99992 16.9999 10.4499 16.9999 10.9999C16.9999 11.5539 16.5499 11.9999 15.9999 11.9999C15.4459 11.9999 14.9999 11.5539 14.9999 10.9999Z" fill="#4D148C"/></svg>';
            jQuery('.edit-product-pod').after("<div class='admin__field custom-doc-field custom-doc-field-configured'><div class='admin__field-label customize-product-label configure-product-pod-lable'><label><span>Customize<br/>Product</span></label><div class='custom-doc-tooltip-content' style='display: none;'>The uploaded file has fields that can be <br/>customized.</div><div class='svg-tool-tip'>"+tooltipConst+"</div></div><div class='admin__field-control'><button type='button' class='customize' id='setup_customize_doc' style='float: left;'>SET UP</div></div>");

            self.customDocTooltip();

            let customizeFields = jQuery("textarea[name='product[customization_fields]']").val();
            if (customizeFields !== "") {
                let customizeDocument = false;
                let arr = JSON.parse(customizeFields);
                jQuery.each(arr,function(key,value){
                    if (value.documentId !== undefined && value.formFields !== undefined) {
                        jQuery.each(value.formFields,function(key,value){
                            if (value.label !== undefined) {
                                customizeDocument = true;
                                return false;
                            }
                        });
                    }
                });

                if (customizeDocument) {
                    jQuery('<div class="setupcompleted" id="setupcompleted" style="float: left; margin-left: 14px; margin-top: 2px;"><div class="text" style="font-weight: 700; float: left; margin-top: 14%;">Completed</div><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30" fill="none" style="margin-top: 8px; float: right;"><rect opacity="0.01" width="30" height="30" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M12.5505 18.2827L22.9195 9.06575C23.4355 8.6071 24.2256 8.65358 24.6843 9.16956C25.1429 9.68554 25.0964 10.4756 24.5805 10.9343L13.3305 20.9343C12.8358 21.374 12.0841 21.3519 11.6161 20.8839L5.36612 14.6339C4.87796 14.1457 4.87796 13.3543 5.36612 12.8661C5.85427 12.378 6.64573 12.378 7.13388 12.8661L12.5505 18.2827Z" fill="#008A00"/></svg></div>').insertAfter('.custom-doc-field-configured button');
                } else {
                    jQuery("#setupcompleted").remove();
                }
            }
        },

        // Custom Doc field tooltip
        customDocTooltip : function() {
            jQuery(document).on('mouseenter', '.custom-doc-field-tooltip, .custom-doc-tooltip-content', function () {
                jQuery('.custom-doc-tooltip-content').show();
            }).on('mouseleave', '.custom-doc-field-tooltip, .custom-doc-tooltip-content', function () {
                jQuery('.custom-doc-tooltip-content').hide();
            });
        },

        // B-1604876 : RT-ECVS-Display edit button in admin to edit a product
        addEditButton : function() {
            let self = this;
            let waitForProductFormLoad = setInterval(function () {
                if(jQuery('div[data-index="attribute_set_id"] .admin__action-multiselect-text').length && jQuery("textarea[name='product[external_prod]']").is(":visible") &&  jQuery("input[name='product[pod2_0_editable]']").val() == 1) {
                    let selctedOption = jQuery('div[data-index="attribute_set_id"] .admin__action-multiselect-text').html(),
                        isConfigured = jQuery.trim(jQuery("textarea[name='product[external_prod]']").val());

                    if(selctedOption == "PrintOnDemand"
                        && isConfigured != ""
                    ) {
                        /*B-1642066*/
                        let externalProd = JSON.parse(isConfigured);
                        if (jQuery('.edit-product-pod').length === 0) {
                            jQuery('div[data-index="attribute_set_id"]')
                                .after(
                                    "<div class='admin__field edit-product-pod'>" +
                                    "<div class='admin__field-label edit-product-pod-lable'>" +
                                    "<label>" +
                                    "<span>Configure Product</span>" +
                                    "</label>" +
                                    "</div>" +
                                    "<div class='admin__field-control'>" +
                                    "<button id='edit-product' " +
                                    "title='Edit Product' " +
                                    "class='action-default primary edit-button mvp-catalog-edit-button'>" +
                                    "<span>EDIT</span>" +
                                    "</button>" +
                                    "</div>" +
                                    "</div>"
                                );

                            if (jQuery('body').hasClass('mazegeeks_download_catalog_items')) {
                                self.downloadFieldForConfProd();
                            }


                            if (jQuery('body').hasClass('catalog_mvp_custom_docs')) {
                                jQuery(".custom-doc-field-non-configured").remove();
                                let isCustomizeFields = jQuery.trim(jQuery("textarea[name='product[customization_fields]']").val());
                                if(isCustomizeFields != "" && isCustomizeFields != "[]") {
                                    self.customDocFieldForConfProd();
                                } else {
                                    self.customDocFieldForNonConfProd();
                                }
                            }
                            self.customerTimezoneForProd();
                        }

                        if (jQuery('.configure-product-pod').length !== 0) {
                            jQuery(".configure-product-pod").remove();
                            jQuery(".download-field-non-configured").remove();
                            //jQuery(".custom-doc-field-non-configured").remove();
                        }
                    } else {
                        if (jQuery('.edit-product-pod').length !== 0) {
                            jQuery(".edit-product-pod").remove();
                            jQuery(".download-field-configured").remove();
                            //jQuery(".custom-doc-field-configured").remove();
                        }
                    }
                    clearInterval(waitForProductFormLoad);
                }
            }, 3000);
        },

	    hideExtraSections : function(value) {
            let waitForProductFormLoad = setInterval(function () {
                if(jQuery(".page-footer").is(":visible") &&
                jQuery('div[data-index="attribute_set_id"] .admin__action-multiselect-text').length) {
                    const imageSectionClick = setTimeout(function(){
                        jQuery('div[data-index="gallery"]').trigger('click');
                    }, 2000);

                   let hideSections = ['configurable','related','custom_options','websites','downloadable','review','salable_quantity'];

                    let selctedOption = jQuery('div[data-index="attribute_set_id"] .admin__action-multiselect-text').html();

                    jQuery('div[data-index="gallery"]').on('click',function(){
                        if(selctedOption == "PrintOnDemand") {
                            if(jQuery(".add-video-button-container").length) {
                                jQuery(".add-video-button-container").hide();
                            }
                        } else {
                            if(jQuery(".add-video-button-container").length) {
                                jQuery(".add-video-button-container").show();
                            }
                        }
                    });

                    jQuery('div[data-index="search-engine-optimization"]').on('click',function(){
                        if(selctedOption == "PrintOnDemand") {
                            if(jQuery('div[data-index="preview_url"]').length) {
                                jQuery('div[data-index="preview_url"]').hide();
                                jQuery('div[data-index="preview_description"]').hide();
                                jQuery('div[data-index="meta_keyword"]').hide();
                                jQuery('div[data-index="canonical_url_type"]').hide();
                                jQuery('div[data-index="preview_title"]').hide();
                                jQuery('fieldset[data-index="container_url_key"]').hide();
                                jQuery('div[data-index="url_key"]').hide();
                            }
                        } else {
                            if(jQuery('div[data-index="preview_url"]').length) {
                                jQuery('div[data-index="preview_url"]').show();
                                jQuery('div[data-index="preview_description"]').show();
                                jQuery('div[data-index="canonical_url_type"]').show();
                                jQuery('div[data-index="preview_title"]').show();
                                jQuery('fieldset[data-index="container_url_key"]').show();
                                jQuery('div[data-index="url_key"]').show();
                            }
                        }
                    });

                    jQuery('div[data-index="content"]').on('click',function(){
                        if(selctedOption == "PrintOnDemand") {
                            const contentClick = setTimeout(function(){
                                jQuery("div[data-index='description']").hide();
                            }, 1000);
                        } else {
                            const contentClick = setTimeout(function(){
                                jQuery("div[data-index='description']").show();
                            }, 1000);
                        }
                    });


                    if(selctedOption == "PrintOnDemand") {
                        hideSections.forEach(function(hideElement) {
                            let container = 'div[data-index="'+hideElement+'"]';
                            if(jQuery(container).length > 0) {
                                jQuery(container).hide();
                            }
                        });

                    } else {
                        hideSections.forEach(function(hideElement) {
                            let container = 'div[data-index="'+hideElement+'"]';
                            if(jQuery(container).length > 0) {
                                jQuery(container).show();
                            }
                        });
                    }
                    clearInterval(waitForProductFormLoad);
                }
            }, 3000);
        },

        // ProductName Limit to 100 character
        productNameHundredCharacter : function() {
            let waitForProductFormLoad = setInterval(function () {
                if(jQuery('.character_limit_hundred div[data-index="attribute_set_id"] .admin__action-multiselect-text').length) {
                    var maxLength = 100; // Change this to your desired character limit
                    var productNameField = jQuery("input[name='product[name]']");
                    // Add input event listener to enforce character limit
                    productNameField.on('input', function() {
                        var value = jQuery(this).val();
                        if (value.length > maxLength) {
                            jQuery(this).val(value.substr(0, maxLength));
                        }
                    });
                    clearInterval(waitForProductFormLoad);
                }
            }, 3000);
        },

        /**
         * Change set parameter in save and validate urls of form
         *
         * @param {String|Number} value
         */
        changeFormSubmitUrl: function (value) {
            var pattern = /(set\/)(\d)*?\//,
                change = '$1' + value + '/';

            this.formProvider().client.urls.save = this.formProvider().client.urls.save.replace(pattern, change);
            this.formProvider().client.urls.beforeSave = this.formProvider().client.urls.beforeSave.replace(
                pattern,
                change
            );

            if (window.location.toString().includes("catalog/product/new")) {
                var attributeSetId = value;
                var currentUrl = window.location.href;
                var updatedUrl = currentUrl.replace(/\/set\/\d+/, '/set/' + attributeSetId);
                window.history.replaceState(null, '', updatedUrl);
                counter++;
                if (counter >= 2) {
                    location.reload(true);
                }
            }
            if (window.location.toString().includes("catalog/product/edit")) {
                counter++;
                if (counter >= 2 && firstLoadAttributeSet == "PrintOnDemand") {
                    location.reload(true);
                }
            }

	        this.hideExtraSections(value);

            // B-1556308: Call function for SKU, Price and external product readonly and uuid for sku for attribute set print on demand
            this.readonlySections(value);

            // B-1556307: Call function for Tax Class, Quantity, Weight, Country of Manufacture, Enable RMA, Is Customizable, Has Canva Design and Admin User ID should be hidden for attribute set print on demand
            this.togglePodAttributes(value);

            // B-1556309: Added new fields for Print on Demand attribute
            this.addNewFieldsForpod();

            //B-1604876 : RT-ECVS-Display edit button in admin to edit a product
            this.addEditButton();

            // B-2011432: POD2.0: Product name while creating setting limit to 100
            this.productNameHundredCharacter();
        },

        // Send to customer field for tooltips added
        addTooltipsForSendToCustomer : function() {
            let addTooltipsDiv = 'div[data-index="sent_to_customer"]';
            const sendToCustomerTooltip = '<svg class="sent-to-customer-field-tooltip" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none"><rect opacity="0.01" width="32" height="32" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M16 26.6667C10.109 26.6667 5.33337 21.8911 5.33337 16C5.33337 10.109 10.109 5.33337 16 5.33337C21.8911 5.33337 26.6667 10.109 26.6667 16C26.6667 21.8911 21.8911 26.6667 16 26.6667ZM16 25.3334C21.1547 25.3334 25.3334 21.1547 25.3334 16C25.3334 10.8454 21.1547 6.66671 16 6.66671C10.8454 6.66671 6.66671 10.8454 6.66671 16C6.66671 21.1547 10.8454 25.3334 16 25.3334ZM18.3334 20.6667H16.3345L16.3334 20.6667L16.3322 20.6667H14.3334C13.9652 20.6667 13.6667 20.3682 13.6667 20C13.6667 19.6319 13.9652 19.3334 14.3334 19.3334H15.6667V15H15C14.6319 15 14.3334 14.7016 14.3334 14.3334C14.3334 13.9652 14.6319 13.6667 15 13.6667H16.3334C16.7016 13.6667 17 13.9652 17 14.3334V19.3334H18.3334C18.7016 19.3334 19 19.6319 19 20C19 20.3682 18.7016 20.6667 18.3334 20.6667ZM15 11C15 10.45 15.446 10 16 10C16.55 10 17 10.45 17 11C17 11.554 16.55 12 16 12C15.446 12 15 11.554 15 11Z" fill="#333333"/></svg>';;
            jQuery(addTooltipsDiv).find('.admin__field-label > label').after("<div class='sent-to-customer-tooltips' style='display: inline-flex;margin-left: 10px;'><div class='tooltip-content' style='display: none;bottom: 40px; text-align: left; width: 430px; height: 65px;'><span style='line-height: 24px;'>Enabling this toggle will add the priced catalog item to the Shared Catalog(taking it out of Pending Review status).</span></div>"+sendToCustomerTooltip+"</div>");
            jQuery(addTooltipsDiv).find('.admin__field-label').css({"display":"flex","justify-content":"right"});
            jQuery(document).on('mouseenter', '.sent-to-customer-field-tooltip, .tooltip-content', function () {
                jQuery('.tooltip-content').show();
            }).on('mouseleave', '.sent-to-customer-field-tooltip, .tooltip-content', function () {
                jQuery('.tooltip-content').hide();
            });
        },
    });
});



