    define(['jquery','jquery/ui','Magento_Ui/js/modal/modal','fedex/storage','inBranchWarning'], 
    function($,ui,modal,fxoStorage,inBranchWarning){
    'use strict';

    return function(config, elm) {
        
         /* B-2182041 js start here.. */
         var $mobileButton = $('.product-item-details-mobile-normal'); 
         if ($mobileButton.length) {
             $mobileButton.each(function() {
                 $(this).on('click', function() {
                     $(window).scrollTop(0);
                     var url = config.rightpanelUrl;
                     var itemId = $(this).attr('id').split('_')[1];
                     var isCustomDocEnabled = config.isCustomDocEnabled;
                     $("#product-item-checkbox_"+itemId).prop("checked", true);
                     $(".delete-action"). attr('id', itemId);
                     $(".action_kebab_right_item_settings").html('');
                     $(".action_kebab_right_item_request").html('');
                     $.ajax({
                         url: url,
                         type: 'get',
                         dataType: 'JSON',
                         data: {
                             item_id: itemId
                         },
                         showLoader: true,
                         success: function (response){
                             var itemTitle = response.name?response.name: "Catalog Item";
                             var itemStatus = (response.published == 1)?"Published":"Unpublished";
                             var itemThumbnail = response.small_image_url;
                             var printProperties = {};
                             var printingCost = response.price ? response.price: null;
                             var itemDesc = response.catalog_description?response.catalog_description: false;
                             var itemMetaKeyword = response.related_keywords?response.related_keywords: false;
                             var productQty = (response.quantity_and_stock_status.qty)
                                 ?response.quantity_and_stock_status.qty:1;
                             var isCustomizeProduct = response.customizable;
                             var isProductPendingReview = response.is_pending_review ? response.is_pending_review: null;
                             var isCatalogUpdates = config.isCatalogUpdatesJson;
                             if(response.external_prod != null
                                 && Object.keys(response.external_prod).length > 2){
                                 var obj = JSON.parse(response.external_prod);

                                 productQty = (obj.qty)?obj.qty:1;

                                 if(obj.name !== "Legacy Catalog"
                                     && obj.instanceId !== 0)
                                 {
                                     productQty = (obj.qty)
                                         ?obj.qty:1;
                                     printProperties = getPrintProperties(response.external_prod, printingCost);
                                 }
                             }
                             let catalogExpiryToggle = config.isCatalogExpiryNotificationToggle;
                             if (catalogExpiryToggle) {
                                 if (!response.renewLink) {
                                     $(".action_kebab_right_item_renew").parent('.menu-item').hide();
                                 } else {
                                     $(".action_kebab_right_item_renew").parent('.menu-item').show();
                                 }
                             }
                             $(".panel-top-actions .kebab-hidden svg path").show();
                             let nonStandardToggle = config.nonStandardToggle;
                             if (nonStandardToggle && response.is_pending_review == '1') {
                                 $(".panel-top-actions .kebab-hidden svg path").hide();
                                 $("#accordion .panel-top .kebab-hidden").css('pointer-events','none');
                             } else {
                                 $("#accordion .panel-top .kebab-hidden").css('pointer-events','auto');
                             }
                             if (nonStandardToggle && response.settingsLink == true) {
                                 $(".action_kebab_right_item_settings").html('<a prodid="'+response.sku+'" prodname="'+response.name+'" class="change-settings-product" href="javascript:void(0);">'+config.settingsText+'</a>');
                             }
                             if (nonStandardToggle && response.requestChange == true) {
                                 $(".action_kebab_right_item_edit").hide();
                                 $(".action_kebab_right_item_request").html('<a prodid="'+response.sku+'" prodname="'+response.name+'" class="change-request-product" id="change-request-product" href="javascript:void(0);">'+config.requestChangeText+'</a>');
                                 $(".action_kebab_right_item_request").appendTo($(".action_kebab_right_item_edit").closest('.menu-item'));
                             }
                             $('.item-title').text(itemTitle);
                             $('.item-status').text(itemStatus);
                             if (isProductPendingReview == 1) {
                                 $(".right-pannel .content-wrapper-print-properties").addClass("isPendingReview");
                             } else {
                                 $(".right-pannel .content-wrapper-print-properties").removeClass("isPendingReview");
                             }
                             if(isCustomizeProduct == 1 && isCustomDocEnabled == 1) {
                                 $('.cart-chip-label').text('Customize');
                                 $('.item-custom-option').text('Customizable Document');
                                 $('.cart-chip-wrapper').addClass('action-customize-product');
                             } else {
                                 $('.cart-chip-label').text('Add to cart');
                                 $('.item-custom-option').text('');
                                 $('.cart-chip-wrapper').removeClass('action-customize-product');
                             }

                             //hiding the Buttons if status pending review
                             if (config.nonStandardToggle){
                                 if (response.is_pending_review == 1) {
                                     $('.panel-content').hide();
                                 }
                             }
                             $('.gallery-media img').attr('src', itemThumbnail);
                             $('.gallery-media img').attr('datasku', response.sku);
                             var propertiesDivEl = $('.print-properties');

                             var propertiesEl = '';
                             if(Object.keys(printProperties).length !== 0){
                                 propertiesEl = propertiesHtml(printProperties);
                                 propertiesDivEl.html(propertiesEl);

                             }else{
                                 propertiesEl = propertiesHtml(printProperties);
                                 propertiesDivEl.html(propertiesEl);
                             }

                             $('.content-desc').html(itemDesc);

                             if(itemDesc){
                                 $('.panel-tab.description').show();
                                 $('.panel-content.description').show();
                             }else{

                                 $('.panel-tab.description').show();
                                 $('.panel-content.description').hide();
                                 $('.panel-tab.description').removeClass('active');
                             }

                             var chipsEl = '';
                             if(itemMetaKeyword){
                                 var tags = itemMetaKeyword.split(',');

                                 $.each(tags, function( index, tag ) {
                                     chipsEl += '<div class="chips">'
                                         +'<div class="tag-button">'
                                         +'<div class="tag-label">'
                                         +  tag
                                         + '</div>'
                                         +'</div></div>';
                                 });
                             }

                             $('.tags-wrapper').html(chipsEl);

                             if (isCatalogUpdates) {
                                 var lastModifiedContent = '<div class="last-modified-content"'+
                                     '<p><span class="label">User</span> <span class="user">' + response.modified_by + '</span></p>' +
                                     '<p><span class="label">Date</span> <span class="date">' + response.last_modified_date + '</span></p>' +
                                     '<p><span class="label">Time</span> <span class="time">' + response.last_modified_time + '</span></p>' +
                                     '</div>';
                                 $('.last-modified-wrapper').html(lastModifiedContent);
                             }

                             // Allowed qty for catalogMvp
                             var fixedQtyHandlerToggle = config.fixedQtyHandlerToggle;

                             if (fixedQtyHandlerToggle) {
                                 var productQtyArray = '';
                                 if (response.external_prod != null
                                     && Object.keys(response.external_prod).length > 2) {
                                     var obj = JSON.parse(response.external_prod);
                                     productQtyArray = obj.quantityChoices ? obj.quantityChoices : 0;
                                 }

                                 if (productQtyArray.length > 1) {
                                     var options = '';
                                     jQuery.each( productQtyArray, function( key, value ) {
                                         var isSelected = (value == productQty)? 'selected="selected"': '';
                                         options += '<option value="'+value+'" '
                                             + isSelected + '>'+value+'</option>';
                                     });
                                     jQuery('.qty-box-value').hide();
                                     jQuery('.qty-dropbox-value').html(options);
                                     jQuery('.qty-dropbox-value').select2({minimumResultsForSearch: -1});
                             } else if (productQty > 1) {
                                 var options = '';
                                 for (let i = 1; i <= 5; i++) {
                                     var result = i * productQty;
                                     var isSelected = (result == productQty)? 'selected="selected"': '';
                                         options += '<option value="'+result+'" '
                                                     + isSelected + '>'+result+'</option>';
                                 }

                                 jQuery('.qty-box-value').hide();
                                 jQuery('.qty-dropbox-value').html(options);
                                 jQuery('.qty-dropbox-value').select2({minimumResultsForSearch: -1});

                             } else {
                                 jQuery('.qty-dropbox-value').next(".select2-container").hide();
                                 jQuery('.qty-dropbox-value').hide();
                                 jQuery('.qty-box-value').show();
                                 $('.qty-box-value').val(productQty);
                             }
                         }
                         else {
                             if(productQty > 1){
                                 var options = '';
                                 for(let i = 1; i <= 5; i++) {
                                 var result = i * productQty;
                                     var isSelected = (result == productQty)? 'selected="selected"': '';
                                     options += '<option value="'+result+'" '
                                     + isSelected + '>'+result+'</option>';
                                 }

                                 jQuery('.qty-box-value').hide();
                                 jQuery('.qty-dropbox-value').html(options);
                                 jQuery('.qty-dropbox-value').select2({minimumResultsForSearch: -1});

                             }else{
                                 jQuery('.qty-dropbox-value').next(".select2-container").hide();
                                 jQuery('.qty-dropbox-value').hide();
                                 jQuery('.qty-box-value').show();
                                 $('.qty-box-value').val(productQty);
                             }
                         }
                     }
                 });
                 showPanel();
                 });
             });
        }
        /* B-2182041 flyout start open/close icon */
        $('.flyout-close-button-mobile').on('click', function () {
            if ($(window).width() < 768) {
                $(".product-item-checkbox.list-checkbox").prop("checked", false);
                $(".selected-number").removeClass("show");
            }
            var targetDiv = $('.right-pannel');
            if (targetDiv.length) {
                if (targetDiv.is(':visible')) {
                    targetDiv.hide(); 
                    $('body').addClass('page-layout-2columns-left');
                    $('body').removeClass('page-layout-3columns');
                    $('body').removeClass('flyout-modal-mobile');
                } else {
                    targetDiv.show();
                }
            }
        });
        $('.product-item-details-mobile-normal').on('click', function () {
            var targetDiv = $('.right-pannel');
            if (targetDiv.length) {
                $('body').addClass('flyout-modal-mobile');
                targetDiv.show();
            }
        });
        /* B-2182041 js end here */
        /*B-1611718*/
        function showPanel() {
            /*D-137050*/
            if (screen.width > 767) {
             jQuery('body').removeClass('page-layout-2columns-left');
            }
            jQuery('body').addClass('page-layout-3columns catalog-mvp-customer-view');

            jQuery( ".panel-top-actions" ).on( "click", function() {
                jQuery(".dropdown-menu").show();
            });

            jQuery( ".dropdown-menu" ).on( "mouseleave", function() {
                jQuery(this).hide();
            });

            jQuery(".panel-container").addClass("active");

            if(screen.width >= 1280 && screen.width <= 1440) {
                /* D-138269 */
                jQuery(".commercial-toolbar-bottom") . css("width", "135%");

            }
            setTimeout(function() {
                var mainContentEl = jQuery('#maincontent');
                var bootomToolbarEl = jQuery('.commercial-toolbar-bottom');
                var rightPanelEl = jQuery('#accordion');
                var mainContentElHeight = mainContentEl.height();
                var rightPanelElHeight = rightPanelEl.height();
                var columnMainEl = jQuery('.column.main');
                var columnMainElHeight = columnMainEl.height()- 60;
                var fraction = 50;
                if(columnMainElHeight < rightPanelElHeight){
                    var newRightPanelHeight = rightPanelElHeight + fraction;
                if (!$("body").hasClass("catalog-mvp-break-points") || screen.width >1023) {
                        mainContentEl.height(newRightPanelHeight);
                }   
                    var toolbarTop = newRightPanelHeight - columnMainElHeight;
                    bootomToolbarEl.css('top',toolbarTop);
                    bootomToolbarEl.css('position','relative');
                    bootomToolbarEl.css('margin-top','50px');
                }
            }, 500);
        }
        /*B-1611718*/
        function hidePanel() {
            /*D-137050*/
            jQuery('body').addClass('page-layout-2columns-left');
            jQuery('body').removeClass('page-layout-3columns catalog-mvp-customer-view');

            jQuery(".panel-container").removeClass("active");
            if(screen.width >= 1280 && screen.width <= 1440) {
                /* D-138269 */
                jQuery(".commercial-toolbar-bottom") . css("width", "100%");
            }else{
                /* D-138269 */
                jQuery(".commercial-toolbar-bottom") . css("width", "100%");
            }

            var mainContentEl = jQuery('#maincontent');
            var bootomToolbarEl = jQuery('.commercial-toolbar-bottom');
            var columnMainEl = jQuery('.column.main');
            var columnMainElHeight = columnMainEl.height();
            if (!$("body").hasClass("catalog-mvp-break-points") || screen.width >1023) {
                mainContentEl.height(columnMainElHeight);
            }
            bootomToolbarEl.css('position','inherit');
            bootomToolbarEl.css('margin-top','unset');
        }

        /* get Print Properties */

        function getPrintProperties(externalProd, printingCost = null){
            var json = externalProd.replace('/\\/g', '');
            const obj = JSON.parse(json);
            const printProperties = {};
            var printPropertiesTab = [];
            var printPropertiesContent = [];

            if(!obj){
                return false;
            }else{
                Object.entries(obj.features).forEach(([key, value]) => {
                    printPropertiesTab.push(value.name);
                    if(value.name != 'Orientation' ){
                        printPropertiesContent.push(value.choice.name );
                    }else{
                        var valueTransform = value.choice.properties[0].value;
                        var finalValue = valueTransform.split(' ').map(function(word, index){
                            return word.charAt(0) + word.slice(1).toLowerCase();
                        }).join('-');
                        printPropertiesContent.push(finalValue);
                    }
                });


                if(printingCost) {
                    const formatter = new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: 'USD',
                    });
                    printPropertiesTab.push("Printing Cost");
                    printPropertiesContent.push(formatter.format(printingCost));
                }
                Object.entries(obj.contentAssociations)
                .forEach(([key, value]) => {
                    Object.entries(value).forEach(([k, v]) => {
                        if(k == "fileName"){
                            printPropertiesTab.push(k);
                            printPropertiesContent.push(v);
                        }
                    });
                });

                printPropertiesTab.forEach((key, idx) => printProperties[key] = printPropertiesContent[idx]);

                return printProperties;
            }

        }

        /* get Properties Html */

        function propertiesHtml(properties){
            var printPropertiesContent = '';
            if(properties){
                for (const [key, value] of Object.entries(properties)) {
                    if(key !== 'fileName' && value !== "None"){
                        printPropertiesContent += '<div class="property-wrapper">'
                            + '<div class="property-label">' + key + '</div>'
                            + '<div class="property">' + value + '</div>'
                        + '</div>';
                    }
                }
            }else{
                printPropertiesContent = properties;
            }
            return printPropertiesContent;
        }

    // Pattern Fly Truncation Default Values

        function patternFlyTruncationCatalog(
            text,
            minimumCharacters = config.minChar,
            firstPartNumChars = config.ellipsisStartChar,
            lastPartNumChars = config.ellipsisEndChar
        ) {

            let isTruncated = false;
            if (text?.length > minimumCharacters) {
                isTruncated = true;
                return [`${text.slice(0, firstPartNumChars)}...${text.slice(-lastPartNumChars)}`, isTruncated];
            }

            return [text, isTruncated];
        }

        require(['jquery','mage/url'], function($){
        
            if (config.catalogEllipsisEnable == "1" && config.char100Enable == "1" ) {
            $('.product-items .product-item-name a').each(function() {
                    $(this).attr('aria-label', $(this).text().trim());
                    const [truncatedText, isTruncated] = patternFlyTruncationCatalog($(this).text().trim());

                    if (isTruncated) {
                        $(this).attr("title",jQuery(this).attr("alt"));
                        $(this).html(truncatedText);
                        $(this).addClass("break-all")
                    }
            });
        }

            $(document).ready(function() {
                $('.loading-mask-mvp').hide();
                var isCustomerView = $("ol.products.list").hasClass("customer-view");
                if(isCustomerView){
                    $("body").addClass("customer-view");
                }

                $(".product-item-checkbox").on('click', function(){
                    $(".selected-number").addClass("show");
                    if ($(".check-box-check-all").is(":checked")) {
                        $('.check-box-check-all').attr('checked', false);
                    }
                });

                var $checkboxes = $('.product-item-checkbox.list-checkbox');
                $checkboxes.change(function(){
                    var countCheckedCheckboxes = $checkboxes.filter(':checked').length;
                    var isCustomizableCheckboxSelected = $checkboxes.filter('.customizable:checked').length;
                    var isCustomDocEnabled = config.isCustomDocEnabledJson;
                    if(isCustomDocEnabled && isCustomizableCheckboxSelected > 0){
                        $(".user-bulk-add-to-cart").hide();
                    }else {
                        $(".user-bulk-add-to-cart").show();
                    }
                    if (countCheckedCheckboxes) {
                        $('.item-count') . text(countCheckedCheckboxes);
                        $('.item-count') . val(countCheckedCheckboxes);
                        if (countCheckedCheckboxes < 2 && screen.width < 768) {
                            $(".selected-number").hide();
                        } else if (screen.width < 768) {
                            $(".selected-number").show();
                        }
                        /*B-1611718*/
                        if(countCheckedCheckboxes < 2 && $(window).width() >= 768){
                            $(window).scrollTop(0);
                            /*B-1651604*/
                            var itemId = $checkboxes.filter(':checked').attr("id").split("_");
                            var url = config.rightpanelUrl;
                            var isCustomDocEnabled = config.isCustomDocEnabled;
                            $.ajax({
                                url: url,
                                type: 'get',
                                dataType: 'JSON',
                                data: {
                                    item_id: parseInt(itemId[1])
                                },
                                showLoader: true,
                                success: function (response){
                                    if (response.previewLinkDisplay == 'false' && jQuery('.menu-item-label:contains("Preview")').length) {
                                        jQuery('.menu-item-label:contains("Preview")').closest('.menu-item').remove();
                                    }
                                    var itemTitle = response.name?response.name: "Catalog Item";
                                    var itemStatus = (response.published == 1)?"Published":"Unpublished";
                                    var itemThumbnail = response.small_image_url;
                                    var printProperties = {};
                                    var printingCost = response.price ? response.price: null;
                                    var itemDesc = response.catalog_description?response.catalog_description: false;
                                    var itemMetaKeyword = response.related_keywords?response.related_keywords: false;
                                    var productQty = (response.quantity_and_stock_status.qty)
                                    ?response.quantity_and_stock_status.qty:1;
                                    var isCustomizeProduct = response.customizable;

                                    if(response.external_prod != null
                                    && Object.keys(response.external_prod).length > 2){
                                        var obj = JSON.parse(response.external_prod);

                                        productQty = (obj.qty)?obj.qty:1;

                                        if(obj.name !== "Legacy Catalog"
                                        && obj.instanceId !== 0)
                                        {
                                            productQty = (obj.qty)
                                            ?obj.qty:1;
                                            printProperties = getPrintProperties(response.external_prod, printingCost);
                                        }
                                    }

                                    $('.item-title').text(itemTitle);
                                    $('.item-status').text(itemStatus);
                                    if(isCustomizeProduct == 1 && isCustomDocEnabled == 1) {
                                        $('.cart-chip-label').text('Customize');
                                        $('.item-custom-option').text('Customizable Document');
                                        $('.cart-chip-wrapper').addClass('action-customize-product');
                                    } else {
                                        $('.cart-chip-label').text('Add to cart');
                                        $('.item-custom-option').text('');
                                        $('.cart-chip-wrapper').removeClass('action-customize-product');
                                    }
                                    $('.gallery-media img').attr('src', itemThumbnail);
                                    $('.gallery-media img').attr('datasku', response.sku);
                                    var propertiesDivEl = $('.print-properties');

                                    var propertiesEl = '';
                                    if(Object.keys(printProperties).length !== 0){
                                        propertiesEl = propertiesHtml(printProperties);
                                        propertiesDivEl.html(propertiesEl);

                                    }else{
                                        propertiesEl = propertiesHtml(printProperties);
                                        propertiesDivEl.html(propertiesEl);
                                    }

                                    $('.content-desc').html(itemDesc);

                                    if(itemDesc){
                                            $('.panel-tab.description').show();
                                            $('.panel-content.description').show();
                                    }else{

                                        $('.panel-tab.description').show();
                                        $('.panel-content.description').hide();
                                        $('.panel-tab.description').removeClass('active');
                                    }

                                    var chipsEl = '';
                                    if(itemMetaKeyword){
                                        var tags = itemMetaKeyword.split(',');

                                        $.each(tags, function( index, tag ) {
                                            chipsEl += '<div class="chips">'
                                            +'<div class="tag-button">'
                                                +'<div class="tag-label">'
                                                +  tag
                                                + '</div>'
                                            +'</div></div>';
                                        });
                                    }
                                    $('.tags-wrapper').html(chipsEl);

                                    // Allowed qty for catalogMvp
                                    var fixedQtyHandlerToggle = config.fixedQtyHandlerToggle;

                                    if (fixedQtyHandlerToggle) {
                                        var productQtyArray = '';
                                        if (response.external_prod != null
                                            && Object.keys(response.external_prod).length > 2) {
                                            var obj = JSON.parse(response.external_prod);
                                            productQtyArray = obj.quantityChoices ? obj.quantityChoices : 0;
                                        }

                                        if (productQtyArray.length > 1) {
                                            var options = '';
                                                jQuery.each( productQtyArray, function( key, value ) {
                                                    var isSelected = (value == productQty)? 'selected="selected"': '';
                                                    options += '<option value="'+value+'" '
                                                    + isSelected + '>'+value+'</option>';
                                                });
                                                jQuery('.qty-box-value').hide();
                                                jQuery('.qty-dropbox-value').html(options);
                                                jQuery('.qty-dropbox-value').select2({minimumResultsForSearch: -1});
                                        } else if (productQty > 1) {
                                            var options = '';
                                            for (let i = 1; i <= 5; i++) {
                                                var result = i * productQty;
                                                var isSelected = (result == productQty)? 'selected="selected"': '';
                                                    options += '<option value="'+result+'" '
                                                                + isSelected + '>'+result+'</option>';
                                            }

                                            jQuery('.qty-box-value').hide();
                                            jQuery('.qty-dropbox-value').html(options);

                                            jQuery('.qty-dropbox-value').select2({minimumResultsForSearch: -1});

                                        } else {
                                            jQuery('.qty-dropbox-value').next(".select2-container").hide();
                                            jQuery('.qty-dropbox-value').hide();
                                            jQuery('.qty-box-value').show();
                                            $('.qty-box-value').val(productQty);
                                        }
                                    }
                                    else {
                                        if(productQty > 1){
                                            var options = '';
                                            for(let i = 1; i <= 5; i++) {
                                            var result = i * productQty;
                                                var isSelected = (result == productQty)? 'selected="selected"': '';
                                                options += '<option value="'+result+'" '
                                                + isSelected + '>'+result+'</option>';
                                            }

                                            jQuery('.qty-box-value').hide();
                                            jQuery('.qty-dropbox-value').html(options);

                                            jQuery('.qty-dropbox-value').select2({minimumResultsForSearch: -1});

                                        }else{
                                            jQuery('.qty-dropbox-value').next(".select2-container").hide();
                                            jQuery('.qty-dropbox-value').hide();
                                            jQuery('.qty-box-value').show();
                                            $('.qty-box-value').val(productQty);
                                        }
                                    }
                                }
                            });
                            var targetDiv = $('.right-pannel');
                            targetDiv.show();
                            /*B-1611718*/
                            showPanel();
                        }else{
                            /*B-1611718*/
                            hidePanel();
                            $('.shared-catalog-cta-container, .mvp-breadcrumb-label').removeClass('flyout-modal-tablet');
                            $('.toolbar-sorter').removeClass('flyout-modal-toolbar');                
                        }
                    } else {
                        $(".selected-number").removeClass("show");

                        /*B-1611718*/
                        hidePanel();
                        $('.shared-catalog-cta-container, .mvp-breadcrumb-label').removeClass('flyout-modal-tablet');
                        $('.toolbar-sorter').removeClass('flyout-modal-toolbar');            
                    }
                });

                /* Bulk Operations */

                $(".check-box-check-all").on('click', function(){
                    if ($(".check-box-check-all").is(":checked")) {
                        $(".selected-number").addClass("show");
                    } else {
                        $(".selected-number").removeClass("show");
                    }
                    $checkboxes.parent().parent().parent().parent().parent().parent()[this.checked ? "addClass" : "removeClass"]("checked");
                    var countCheckedCheckboxes = $checkboxes.filter(':checked').length;
                    if (countCheckedCheckboxes) {
                        $('.item-count') . text(countCheckedCheckboxes);
                        $('.item-count') . val(countCheckedCheckboxes);
                    }
                });

                let bodyCls = $('body').attr('class'),
                isSharedCatalog = "browse-catalog";

                if(bodyCls.indexOf(isSharedCatalog) !== -1){
                    $('body').addClass('catalog-mvp-shared-catalog');
                }
            });

            $(document).on("change", ".product-item-checkbox.list-checkbox", function () {
                $(this).parent().parent().parent().parent().parent().parent()[this.checked ? "addClass" : "removeClass"]("checked");
            });

            /* Cancel Bulk Operations */

            $(".cancel-all").on('click', function(){
                $(".selected-number").removeClass("show");
                $(".product-item-checkbox.list-checkbox").prop("checked", false);
                $(".product-item-checkbox.list-checkbox").parent().parent().parent().parent().parent().parent().removeClass("checked");
                $(".check-box-check-all").prop("checked", false);

                /*B-1611718*/
                hidePanel();
            });

            /* Open Right Panel */

            $(".right-section button").on('click', function(event){
                var arr = [];
                $('.product-item-checkbox.list-checkbox').each(function () {
                    var self = $(this);
                    if (self.is(':checked')) {
                        arr.push(self.attr("name"));
                    }
                });
                var url = config.addtocartUrl;
                $.ajax({
                    type: "post",
                    url: url,
                    showLoader: true,
                    data: { id: arr },
                    success: function(response) {
                        if (response) {
                            if (config.addToCartRedirectStop == '1') {
                                $(".cancel-all").trigger("click");
                                $("#add-to-cart-toast-message").show();
                                $("#add-to-cart-toast-message .success-toast-msg p").text(response + ' item has been added to your cart.');
                                $('html, body').animate({
                                    scrollTop: $(".header-top").offset().top
                                }, 500);
                            } else {
                                $(location).prop('href', config.cartUrl);
                            }
                        }
                    }
                });
            });

            $(".right-section-action-kebab-mobile .user-bulk-add-to-cart").on('click', function(event){
                var arr = [];
                $('.product-item-checkbox.list-checkbox').each(function () {
                    var self = $(this);
                    if (self.is(':checked')) {
                        arr.push(self.attr("name"));
                    }
                });
                var url = config.addtocartUrl;
                $.ajax({
                    type: "post",
                    url: url,
                    showLoader: true,
                    data: { id: arr },
                    success: function(response) {
                        if (response) {
                            if (config.addToCartRedirectStop == '1') {
                                $(".cancel-all").trigger("click");
                                $("#add-to-cart-toast-message").show();
                                $("#add-to-cart-toast-message .success-toast-msg p").text(response + ' item has been added to your cart.');
                                $('html, body').animate({
                                    scrollTop: $(".header-top").offset().top
                                }, 500);
                            } else {
                                $(location).prop('href', config.cartUrl);
                            }
                        }
                    }
                });
            });

            /* Customize Product Url */

            function customizeProduct(sku) {
                if (sku == null) {
                    $('.product-item-checkbox.list-checkbox').each(function () {
                        var self = $(this);
                        if (self.is(':checked')) {
                            sku = self.attr("data-item-sku");
                        }
                    });
                }
                if (sku) {
                    var customizeUrl = config.configutorUrl + '?sku=' + sku + '&configurationType=customize';
                    window.location.href = customizeUrl;
                }
            }

            /* Customize Product with Qty */

            function customizeProductWithQty(sku,qty) {
                if (sku == null) {
                    $('.product-item-checkbox.list-checkbox').each(function () {
                        var self = $(this);
                        if (self.is(':checked')) {
                            sku = self.attr("data-item-sku");
                        }
                    });
                }
                if (sku) {
                    var customizeUrl = config.configutorUrl + '?sku=' + sku + '&configurationType=customize&qty=' + qty ;
                    window.location.href = customizeUrl;
                }
            }

            $(document).on("click",".action-customize-product",function() {
                let sku = $(this).attr("data-item-sku");
                if($(".quantity-wrapper").is(':visible')) {
                    if($('.qty-dropbox-value').is(':visible')) {
                        $(".qty-box-value").val($(".qty-dropbox-value").val());
                    }
                    var qty = $(".qty-box-value").val();
                    customizeProductWithQty(sku,qty);
                }else{
                    customizeProduct(sku);
                }
            });

            $(".cart-chip-wrapper").on('click', function(event){
                if ($(".cart-chip-wrapper").hasClass('action-customize-product')) {
                    return;
                }
                if($('.qty-dropbox-value').is(':visible')) {
                    $(".qty-box-value").val($(".qty-dropbox-value").val());
                }
                var id = 0;
                $('.product-item-checkbox.list-checkbox').each(function () {
                    var self = $(this);
                    if (self.is(':checked')) {
                        id =self.attr("name");
                    }
                });
                var url = config.addtocartsingleUrl;
                var qyt = $(".qty-box-value").val();
                $.ajax({
                    type: "post",
                    url: url,
                    showLoader: true,
                    data: { id: id, qty:qyt },
                    success: function(response) {
                        if (response) {
                            if (config.addToCartRedirectStop == '1') {
                                $("#add-to-cart-toast-message").show();
                                $("#add-to-cart-toast-message .success-toast-msg p").text('1 Item has been added to your cart.');
                                $('html, body').animate({
                                    scrollTop: $(".header-top").offset().top
                                }, 500);
                            } else {
                                $(location).prop('href', config.cartUrl);
                            }
                        }
                    },
                    complete: function () {
                        $(document).trigger("mvp_add_to_cart_end");
                    }
                });
            });

            $(".panel-tab").click(function() {
                setTimeout(function() {
                    var mainContentEl = jQuery('#maincontent');
                    var bootomToolbarEl = jQuery('.commercial-toolbar-bottom');
                    var rightPanelEl = jQuery('#accordion');
                    var mainContentElHeight = mainContentEl.height();
                    var rightPanelElHeight = rightPanelEl.height();
                    var columnMainEl = jQuery('.column.main');
                    var columnMainElHeight = columnMainEl.height()- 60;
                    var fraction = 50;
                    if(columnMainElHeight < rightPanelElHeight){
                        var newRightPanelHeight = rightPanelElHeight + fraction;
                    if (!$("body").hasClass("catalog-mvp-break-points") || screen.width >1023) {
                        mainContentEl.height(newRightPanelHeight);
                    }
                        var toolbarTop = newRightPanelHeight - columnMainElHeight;
                        bootomToolbarEl.css('top',toolbarTop);
                        bootomToolbarEl.css('position','relative');
                        bootomToolbarEl.css('margin-top','50px');
                    }
                    else {
                        bootomToolbarEl.css('top','0px');
                    }
                }, 50);
            });
            $(document).ajaxStop(function() {
                setTimeout(function() {
                var mainContentEl = jQuery('#maincontent');
                var bootomToolbarEl = jQuery('.commercial-toolbar-bottom');
                var rightPanelEl = jQuery('#accordion');
                var mainContentElHeight = mainContentEl.height();
                var rightPanelElHeight = rightPanelEl.height();
                var columnMainEl = jQuery('.column.main');
                var columnMainElHeight = columnMainEl.height()- 60;
                var fraction = 50;
                if(columnMainElHeight < rightPanelElHeight){
                    var newRightPanelHeight = rightPanelElHeight + fraction;
                if (!$("body").hasClass("catalog-mvp-break-points") || screen.width >1023) {
                    mainContentEl.height(newRightPanelHeight);
                    }
                    var toolbarTop = newRightPanelHeight - columnMainElHeight;
                    bootomToolbarEl.css('top',toolbarTop);
                    bootomToolbarEl.css('position','relative');
                    bootomToolbarEl.css('margin-top','50px');
                }
                else {
                    bootomToolbarEl.css('top','0px');
                }
            }, 100);
            });
        });

        $('.product-item-checkbox').on('click', function () {
            $('.shared-catalog-cta-container, .mvp-breadcrumb-label').addClass('flyout-modal-tablet');
            $('.toolbar-sorter').addClass('flyout-modal-toolbar');
        });

        $('.flyout-close-button-tablet').on('click', function () {
            $(".product-item-checkbox.list-checkbox").prop("checked", false);
            $(".selected-number").removeClass("show");
            $(".product-item-checkbox.list-checkbox").parent().parent().removeClass("checked");
            $(".category-item-checkbox.list-checkbox").parent().parent().removeClass("checked");
            $('.shared-catalog-cta-container, .mvp-breadcrumb-label').removeClass('flyout-modal-tablet');
            $('.toolbar-sorter').removeClass('flyout-modal-toolbar');
        });

        //On click of kebab show options
        $(".kebab").click(function(e) {
            e.stopPropagation();
            $(this).next().show();
            var clickedItem = $($(this).next());
            var otherItems = $(".kebab-options").not(clickedItem);
            otherItems.hide();
        });

        $("body").click(function() {
            $(".kebab-options").hide();
        });

    }
    
});
