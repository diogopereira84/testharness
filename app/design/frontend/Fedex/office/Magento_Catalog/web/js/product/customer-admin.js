define(['jquery','jquery/ui','Magento_Ui/js/modal/modal','fedex/storage','inBranchWarning', "mage/url"],
    function($,ui,modal,fxoStorage,inBranchWarning, urlBuilder){
    'use strict';

    return function(config, elm) {
        var nonStandardReplaceFileConfig = config.nonStandardReplaceFileConfig;
        let originalCategoryTree;
        /* B-2182041 js start here.. */
        var $mobileButton = $('.product-item-details-mobile');
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
                            let isD217161Enabled = config.isD217161Enabled;
                            if (!isD217161Enabled) {
                                let catalogExpiryToggle = config.isCatalogExpiryNotificationToggle;
                                if (catalogExpiryToggle) {
                                    if (!response.renewLink) {
                                        $(".action_kebab_right_item_renew").parent('.menu-item').hide();
                                    } else {
                                        $(".action_kebab_right_item_renew").parent('.menu-item').show();
                                    }
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
        /* B-2182041 js end here */
        /*B-1611718*/

        function showPanel() {
            if (screen.width > 767) {
              jQuery('body').removeClass('page-layout-2columns-left');
            }
            jQuery('body').addClass('page-layout-3columns');

            jQuery( ".panel-top-actions" ).on( "click", function() {
                jQuery(".dropdown-menu").show();
            });

            jQuery(document).on( "mouseup", function() {
                jQuery(".dropdown-menu").hide();
            });
            jQuery(".panel-container").addClass("active");

            /* D-138269 */
            if(screen.width >= 1280 && screen.width <= 1440) {
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
            jQuery('body').addClass('page-layout-2columns-left');
            jQuery('body').removeClass('page-layout-3columns');

            jQuery(".panel-container").removeClass("active");

            /* D-138269 */
            if(screen.width >= 1280 && screen.width <= 1440) {
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
                Object.entries(obj.contentAssociations)
                    .forEach(([key, value]) => {
                        Object.entries(value).forEach(([k, v]) => {
                            if(k == "fileName"){
                                printPropertiesTab.push(k);
                                printPropertiesContent.push(v);
                            }
                        });
                    });
                if(printingCost) {
                    const formatter = new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: 'USD',
                    });
                    printPropertiesTab.push("Printing Cost");
                    printPropertiesContent.push(formatter.format(printingCost));
                }


                printPropertiesTab.forEach((key, idx) => printProperties[key] = printPropertiesContent[idx]);

                return printProperties;
            }
        }

        /* get Print Properties Html */

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

        /* get Move Modal Category Search */

        function mvpMoveSearch(){
            jQuery('.commercial-store-home .toggle-icon-level-0').on('click', function(event){
                var link = jQuery(this);
                var closest_li = link.closest('li');
                var closest_ul = closest_li.children('ul');
                var open_li = closest_ul.children('li');
                open_li.slideToggle("slow");
                if (event.target.className === 'disclosere-icon-closed level-0 display') {
                    jQuery(this).find('.disclosere-icon-closed').hide().removeClass('display');
                    jQuery(this).find('.disclosere-icon-open').show().addClass('display');
                } else{
                    jQuery(this).find('.disclosere-icon-closed').show().addClass('display');
                    jQuery(this).find('.disclosere-icon-open').hide().removeClass('display');
                }
            });
            if(!jQuery("body").hasClass('catalog-mvp-break-points'))
            {
                jQuery('.commercial-store-home .toggle-icon-level-0').on('click', function(event){
                    jQuery('.mvp-catalog-move-popup-category-tree ul li').each(function(index){
                        jQuery(this).attr('id', '');
                    });
                    jQuery('.mvp-catalog-move-popup-category-tree>ul>li').each(function(index){
                        jQuery(this).attr('id', '');
                        jQuery(this).css('display', 'block');
                    });
                    setTimeout(function() {
                        jQuery('.mvp-catalog-move-popup-category-tree ul li:not([style*="display: none"])').each(function(index){
                            jQuery(this).attr('id', index + 1);
                            jQuery('.mvp-catalog-move-popup-category-tree ul li').each(function(index){
                                if (jQuery(this).attr('id') > 10){
                                    jQuery(this).hide();
                                }
                            });
                        });}, 1000);
                });
                jQuery('.commercial-store-home .toggle-icon-level-1').on('click', function(event){
                    jQuery('.mvp-catalog-move-popup-category-tree ul li').each(function(index){
                        jQuery(this).attr('id', '');
                    });
                    jQuery('.mvp-catalog-move-popup-category-tree>ul>li').each(function(index){
                        jQuery(this).attr('id', '');
                        jQuery(this).css('display', 'block');
                    });
                    setTimeout(function() {
                        jQuery('.mvp-catalog-move-popup-category-tree ul li:not([style*="display: none"])').each(function(index){
                            jQuery(this).attr('id', index + 1);
                            jQuery('.mvp-catalog-move-popup-category-tree ul li').each(function(index){
                                if (jQuery(this).attr('id') > 10){
                                    jQuery(this).hide();
                                }
                            });
                        });}, 1000);
                });  
            }
            jQuery('.cancel-btn-move-popup').click(function() {
                jQuery('button.action-close').trigger('click');
            });
            jQuery('.commercial-store-home .toggle-icon-level-1').on('click', function(event){
                var link = jQuery(this);
                var closest_li = link.closest('li');
                var closest_ul = closest_li.children('ul');
                var open_li = closest_ul.children('li');
                open_li.slideToggle("slow");
                if (event.target.className === 'disclosere-icon-closed level-1 display') {
                    jQuery(this).find('.disclosere-icon-closed').hide().removeClass('display');
                    jQuery(this).find('.disclosere-icon-open').show().addClass('display');
                } else{
                    jQuery(this).find('.disclosere-icon-closed').show().addClass('display');
                    jQuery(this).find('.disclosere-icon-open').hide().removeClass('display');
                }
            });
            jQuery('.commercial-store-home .toggle-icon-level-all').on('click', function(event){
                var link = jQuery(this);
                var closest_li = link.closest('li');
                var closest_ul = closest_li.children('ul');
                var open_li = closest_ul.children('li');
                open_li.slideToggle("slow");
                if (event.target.className === 'disclosere-icon-closed level-all display') {
                    jQuery(this).find('.disclosere-icon-closed').hide().removeClass('display');
                    jQuery(this).find('.disclosere-icon-open').show().addClass('display');
                } else{
                    jQuery(this).find('.disclosere-icon-closed').show().addClass('display');
                    jQuery(this).find('.disclosere-icon-open').hide().removeClass('display');
                }
            });
            if(!jQuery("body").hasClass('catalog-mvp-break-points'))
            {
                jQuery('.mvp-catalog-move-popup-category-tree ul li:not([style*="display: none"])').each(function(index){
                    jQuery(this).attr('id', index + 1);
                    if (jQuery(this).attr('id') > 10){
                        jQuery(this).hide();
                    }
                });
            }
            
            jQuery(".sub-cat-div-name").on('click',function(event){
                var currentSubCatId = config.getCurrentSubCategoryId;
                var currentSubCatName = config.getCurrentSubCategoryName;
                if(jQuery(this).hasClass('shared-catalog-lable')){
                    var sharedDivid = jQuery(this).attr('id');
                    sharedDivid = sharedDivid.replace('move-','');
                    if(sharedDivid != currentSubCatId)
                    {
                        var catName = "Shared Catalog";
                        var catTrim = jQuery.trim(catName);
                        jQuery(".selected").removeClass("selected");
                        jQuery(this).addClass('selected');
                        jQuery("#move-"+currentSubCatId).removeClass("shared-catalog-label-active");
                        jQuery("#mvp-move-popup-folder-location-input").val(catTrim);
                        jQuery('.move-action').removeAttr("disabled");
                        jQuery(".move-action").addClass("active");
                    }
                }else{
                    var subCatDiv = jQuery(this).closest('.sub-cat-div');
                    var subCatDivid = jQuery(subCatDiv).attr('id');
                    subCatDivid = subCatDivid.replace('move-','');
                    if(currentSubCatId != subCatDivid)
                    {
                        if(jQuery(".move-action").hasClass('single-cat-move')){
                            var mainCatDivId = jQuery(".move-action").attr('id');
                            if(subCatDivid != mainCatDivId)
                            {
                                var catName = jQuery(this).text();
                                var catTrim = jQuery.trim(catName);
                                if(subCatDiv.hasClass('selected')) {
                                    jQuery(".selected").removeClass("selected");
                                    jQuery("#mvp-move-popup-folder-location-input").val(currentSubCatName);
                                    jQuery("#move-"+currentSubCatId).addClass("shared-catalog-label-active");
                                    jQuery('.move-action').prop('disabled', true);
                                    jQuery(".move-action").removeClass("active");
                                }else{
                                    jQuery(".selected").removeClass("selected");
                                    subCatDiv.addClass('selected');
                                    jQuery("#move-"+currentSubCatId).removeClass("shared-catalog-label-active");
                                    jQuery("#mvp-move-popup-folder-location-input").val(catTrim);
                                    jQuery('.move-action').removeAttr("disabled");
                                    jQuery(".move-action").addClass("active");
                                }
                            }
                        }else{
                            var catName = jQuery(this).text();
                            var catTrim = jQuery.trim(catName);
                            if(subCatDiv.hasClass('selected')) {
                                jQuery(".selected").removeClass("selected");
                                jQuery("#mvp-move-popup-folder-location-input").val(currentSubCatName);
                                jQuery("#move-"+currentSubCatId).addClass("shared-catalog-label-active");
                                jQuery('.move-action').prop('disabled', true);
                                jQuery(".move-action").removeClass("active");
                            }else{
                                jQuery(".selected").removeClass("selected");
                                subCatDiv.addClass('selected');
                                jQuery("#move-"+currentSubCatId).removeClass("shared-catalog-label-active");
                                jQuery("#mvp-move-popup-folder-location-input").val(catTrim);
                                jQuery('.move-action').removeAttr("disabled");
                                jQuery(".move-action").addClass("active");
                            }
                        }
                    }
                }
            });
            jQuery('.move-action').click(function() {
                if(jQuery(this).hasClass('bulk-move-action')){
                    var arr = [];
                    jQuery('.product-item-checkbox.list-checkbox').each(function () {
                        var self = jQuery(this);
                        if (self.is(':checked')) {
                            arr.push(self.attr("name"));
                        }
                    });
                    var catId = jQuery(".selected").attr('id');
                    catId = catId.replace('move-','');
                    var url = config.bulkMoveProductUrl;
                    jQuery.ajax({
                        type: "post",
                        url: url,
                        dataType: 'JSON',
                        showLoader: true,
                        data: { id: arr, cat_id: catId},
                        success: function(response) {
                            if(response.status) {
                                $(document).trigger("toast_message_fired",response.message);
                                jQuery('button.action-close').trigger('click');
                                hidePanel();
                                location.replace(response.url);
                            }
                        }
                    });
                }else if(jQuery(this).hasClass('single-cat-move')){
                    var pid = jQuery(this).attr('id');
                    var catId = jQuery(".selected").attr('id');
                    catId = catId.replace('move-','');
                    var url = config.moveFolderUrl;
                    jQuery.ajax({
                        type: "post",
                        url: url,
                        showLoader: true,
                        data: { id: pid, cat_id: catId},
                        success: function(response) {
                            if(response.status) {
                                $(document).trigger("toast_message_fired",response.message);
                                jQuery('button.action-close').trigger('click');
                                hidePanel();
                                location.replace(response.url);
                            }
                        }
                    });
                }else{
                    var pid = jQuery(this).attr('id');
                    var catId = jQuery(".selected").attr('id');
                    catId = catId.replace('move-','');
                    var url = config.moveProductUrl;
                    jQuery.ajax({
                        type: "post",
                        url: url,
                        showLoader: true,
                        data: { id: pid, cat_id: catId},
                        success: function(response) {
                            if(response.status) {
                                $(document).trigger("toast_message_fired",response.message);
                                jQuery('button.action-close').trigger('click');
                                hidePanel();
                                location.replace(response.url);
                            }
                        }
                    });
                }
            });
        }

        /* Open Mvp Toggle Icon */
        function openMvpTogleIcon(currentSubCatId){
            var currentCatPath = JSON.parse(JSON.stringify(config.getCurrentCategoryPath));
            jQuery.each( currentCatPath, function( i, val ) {
                if(currentSubCatId != val)
                {
                    if(jQuery("#toggle-"+val).hasClass('opened') == false)
                    {
                        jQuery("#toggle-"+val).click();
                        jQuery("#toggle-"+val).addClass('opened');

                    }
                }
            });

        }

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


        if(window.e383157Toggle){
            fxoStorage.set("configurator_json",'');
            fxoStorage.set("configurator_customizedata","");
        }else{
            window.localStorage.setItem("configurator_json",'');
            window.localStorage.setItem("configurator_customizedata","");
        }
        $(document).ready(function() {
            $('.loading-mask-mvp').hide();
            if(window.e383157Toggle){
                fxoStorage.set("cancelurl", window.location.href);
                $('.edit-product').on('click', function() {
                    fxoStorage.set("cancelurl", window.location.href);
                });
            }else{
                localStorage.setItem("cancelurl", window.location.href);
                $('.edit-product').on('click', function() {
                    localStorage.setItem("cancelurl", window.location.href);
                });
            }
            var isCustomerAdmin = $("ol.products.list").hasClass("customer-admin");
            if(isCustomerAdmin){
               $("body").addClass("customer-admin");
            }
            $(".product-item-checkbox").on('click', function(){
                $(".selected-number").addClass("show");
                if ($(".check-box-check-all").is(":checked")) {
                    $('.check-box-check-all').attr('checked', false);
                }
                if ($('.product-item-info').find('.product-item-checkbox.list-checkbox').is(":checked")) {
                    $('.product-item-info').find('.review-message').css('margin-left', '-25px');
                } else {
                    $('.product-item-info').find('.review-message').css('margin-left', '0px');
                }

            });
                /* B-1646916-Delete-pop-up-modal */
                var options = {
                    type: 'popup',
                    responsive: true,
                    modalClass: 'mvp-delete-modal',
                    innerScroll: true,
                    buttons: [],
                    close: [],
                };

                $(".product-item-info .menu-item-label.Delete").on('click',function(event){
                    var pid = $(this).closest('.product-item-info').find('.product-item-checkbox').attr('name');
                    $(".delete-action"). attr('id','delete-'+pid);
                    $('#custom-model-delete-popup').modal(options, $('#custom-model-delete-popup')).modal('openModal');
                });
                $(".right-pannel .menu-item-label.Delete").on('click',function(event){
                    $('#custom-model-delete-popup').modal(options, $('#custom-model-delete-popup')).modal('openModal');
                });

                $('.action_kebab_folder_delete a').click(function() {
                    var folderId = $(this).attr("data-folder-id");
                    $(".kebab-delete-action").attr('id', 'delete-'+folderId);
                    $('#custom-model-delete-popup').modal(options, $('#custom-model-delete-popup')).modal('openModal');
                    $("#custom-model-delete-popup action")
                    $(".kebab-delete-action").show();
                    $(".delete-action").hide();
                    $(".bulk-delete-action").hide();
                });

                /* B-1651686-Create move to modal to move the folder and catalog item */
                var moveoptions = {
                    type: 'popup',
                    responsive: true,
                    modalClass: 'mvp-move-modal',
                    innerScroll: true,
                    buttons: [],
                    close: [],
                };
                $(".menu-item-label.Move").on('click',function(event){
                    var currentSubCatId = config.getCurrentSubCategoryId;
                    var currentSubCatName = config.getCurrentSubCategoryName;
                    if($(this).hasClass('cat-move')){
                        var pid = $(this).closest('.product-item-info').find('.category-item-checkbox').attr('value');
                        $(".move-action"). attr('id', pid);
                        $('.move-action').prop('disabled', true);
                        $(".move-action").removeClass("bulk-move-action");
                        $("#move-"+currentSubCatId).addClass("shared-catalog-label-active");
                        openMvpTogleIcon(currentSubCatId);
                        $("#mvp-move-popup-folder-location-input").val(currentSubCatName);
                        $(".move-action").addClass("single-cat-move");
                        $(".move-action").removeClass("active");
                        $(".selected").removeClass("selected");
                        $('#custom-model-move-popup').modal(moveoptions, $('#custom-model-move-popup')).modal('openModal');
                    }else if($(this).hasClass('action_kebab_right_item_move')){
                        var selectedProductId;
                        $('.product-item-checkbox:checked').each(function() {
                            selectedProductId = $(this).attr('name');
                        });
                        $(".move-action"). attr('id', selectedProductId);
                        $('.move-action').prop('disabled', true);
                        $(".move-action").removeClass("bulk-move-action");
                        $("#move-"+currentSubCatId).addClass("shared-catalog-label-active");
                        openMvpTogleIcon(currentSubCatId);
                        $("#mvp-move-popup-folder-location-input").val(currentSubCatName);
                        $(".move-action").removeClass("single-cat-move");
                        $(".move-action").removeClass("active");
                        $(".selected").removeClass("selected");
                        $('#custom-model-move-popup').modal(moveoptions, $('#custom-model-move-popup')).modal('openModal');
                    }else{
                        var pid = $(this).closest('.product-item-info').find('.product-item-checkbox').attr('name');
                        $(".move-action"). attr('id', pid);
                        $('.move-action').prop('disabled', true);
                        $(".move-action").removeClass("bulk-move-action");
                        $("#move-"+currentSubCatId).addClass("shared-catalog-label-active");
                        openMvpTogleIcon(currentSubCatId);
                        $("#mvp-move-popup-folder-location-input").val(currentSubCatName);
                        $(".move-action").removeClass("single-cat-move");
                        $(".move-action").removeClass("active");
                        $(".selected").removeClass("selected");
                        $('#custom-model-move-popup').modal(moveoptions, $('#custom-model-move-popup')).modal('openModal');
                    }
                });

                $(".bulk-move-to").on('click',function(event){
                    var currentSubCatId = config.getCurrentSubCategoryId;
                    var currentSubCatName = config.getCurrentSubCategoryName;
                    $('.move-action').prop('disabled', true);
                    $(".move-action").removeClass("active");
                    $(".move-action").removeClass("single-cat-move");
                    $(".move-action").addClass("bulk-move-action");
                    $("#move-"+currentSubCatId).addClass("shared-catalog-label-active");
                    openMvpTogleIcon(currentSubCatId);
                    $(".selected").removeClass("selected");
                    $("#mvp-move-popup-folder-location-input").val(currentSubCatName);
                    $('#custom-model-move-popup').modal(moveoptions, $('#custom-model-move-popup')).modal('openModal');
                });

                $(".sub-cat-div-name").on('click',function(event){
                    var currentSubCatId = config.getCurrentSubCategoryId;
                    var currentSubCatName = config.getCurrentSubCategoryName;
                    if($(this).hasClass('shared-catalog-lable')){
                        var sharedDivid = $(this).attr('id');
                        sharedDivid = sharedDivid.replace('move-','');
                        if(sharedDivid != currentSubCatId)
                        {
                            var catName = "Shared Catalog";
                            var catTrim = $.trim(catName);
                            if($(this).hasClass('selected')) {
                                $(".selected").removeClass("selected");
                                $("#mvp-move-popup-folder-location-input").val(currentSubCatName);
                                $("#move-"+currentSubCatId).addClass("shared-catalog-label-active");
                                $('.move-action').prop('disabled', true);
                                $(".move-action").removeClass("active");
                            }else{
                                $(".selected").removeClass("selected");
                                $(this).addClass('selected');
                                $("#move-"+currentSubCatId).removeClass("shared-catalog-label-active");
                                $("#mvp-move-popup-folder-location-input").val(catTrim);
                                $('.move-action').removeAttr("disabled");
                                $(".move-action").addClass("active");
                            }
                        }
                    }else{
                        var subCatDiv = $(this).closest('.sub-cat-div');
                        var subCatDivid = $(subCatDiv).attr('id');
                        subCatDivid = subCatDivid.replace('move-','');
                        if(currentSubCatId != subCatDivid)
                        {
                            if($(".move-action").hasClass('single-cat-move')){
                                var mainCatDivId = $(".move-action").attr('id');
                                if(subCatDivid != mainCatDivId)
                                {
                                    var catName = $(this).text();
                                    var catTrim = $.trim(catName);
                                    if(subCatDiv.hasClass('selected')) {
                                        $(".selected").removeClass("selected");
                                        $("#mvp-move-popup-folder-location-input").val(currentSubCatName);
                                        $("#move-"+currentSubCatId).addClass("shared-catalog-label-active");
                                        $('.move-action').prop('disabled', true);
                                        $(".move-action").removeClass("active");
                                    }else{
                                        $(".selected").removeClass("selected");
                                        subCatDiv.addClass('selected');
                                        $("#move-"+currentSubCatId).removeClass("shared-catalog-label-active");
                                        $("#mvp-move-popup-folder-location-input").val(catTrim);
                                        $('.move-action').removeAttr("disabled");
                                        $(".move-action").addClass("active");
                                    }
                                }
                            }else{
                                var catName = $(this).text();
                                var catTrim = $.trim(catName);
                                if(subCatDiv.hasClass('selected')) {
                                    $(".selected").removeClass("selected");
                                    $("#mvp-move-popup-folder-location-input").val(currentSubCatName);
                                    $("#move-"+currentSubCatId).addClass("shared-catalog-label-active");
                                    $('.move-action').prop('disabled', true);
                                    $(".move-action").removeClass("active");
                                }else{
                                    $(".selected").removeClass("selected");
                                    subCatDiv.addClass('selected');
                                    $("#move-"+currentSubCatId).removeClass("shared-catalog-label-active");
                                    $("#mvp-move-popup-folder-location-input").val(catTrim);
                                    $('.move-action').removeAttr("disabled");
                                    $(".move-action").addClass("active");
                                }
                            }
                        }

                    }
                });

                $('.move-action').click(function() {
                    if($(this).hasClass('bulk-move-action'))
                    {
                        var arr = [];
                        $('.product-item-checkbox.list-checkbox').each(function () {
                            var self = $(this);
                            if (self.is(':checked')) {
                                arr.push(self.attr("name"));
                            }
                        });
                        var catId = $(".selected").attr('id');
                        catId = catId.replace('move-','');
                        var url = config.bulkMoveProductUrl;
                        $.ajax({
                            type: "post",
                            url: url,
                            dataType: 'JSON',
                            showLoader: true,
                            data: { id: arr, cat_id: catId},
                            success: function(response) {
                                if(response.status) {
                                    $(document).trigger("toast_message_fired",response.message);
                                    $('button.action-close').trigger('click');
                                    hidePanel();
                                    location.replace(response.url);
                                }
                            }
                        });
                    }else if($(this).hasClass('single-cat-move')){
                        var pid = $(this).attr('id');
                        var catId = $(".selected").attr('id');
                        catId = catId.replace('move-','');
                        var url = config.moveFolderUrl;
                        $.ajax({
                            type: "post",
                            url: url,
                            showLoader: true,
                            data: { id: pid, cat_id: catId},
                            success: function(response) {
                                if(response.status) {
                                    $(document).trigger("toast_message_fired",response.message);
                                    $('button.action-close').trigger('click');
                                    hidePanel();
                                    location.replace(response.url);
                                }
                            }
                        });
                    }else{
                        var pid = $(this).attr('id');
                        var catId = $(".selected").attr('id');
                        catId = catId.replace('move-','');
                        var url = config.moveProductUrl;
                        $.ajax({
                            type: "post",
                            url: url,
                            showLoader: true,
                            data: { id: pid, cat_id: catId},
                            success: function(response) {
                                if(response.status) {
                                    $(document).trigger("toast_message_fired",response.message);
                                    $('button.action-close').trigger('click');
                                    hidePanel();
                                    location.replace(response.url);
                                }
                            }
                        });
                    }
                });

                $('.commercial-store-home .toggle-icon-level-0').on('click', function(event){
                    var link = $(this);
                    var closest_li = link.closest('li');
                    var closest_ul = closest_li.children('ul');
                    var open_li = closest_ul.children('li');
                    open_li.slideToggle("slow");
                    var ul_level_2 = open_li.children('ul');
                    var li_level_2 = ul_level_2.children('li');
                    li_level_2.slideUp("slow");
                    $(this).find('.disclosere-icon-closed .level-1').show().removeClass('display');
                    $(this).find('.disclosere-icon-open .level-1').hide().addClass('display');
                    if (event.target.className === 'disclosere-icon-closed level-0 display') {
                        $(this).find('.disclosere-icon-closed').hide().removeClass('display');
                        $(this).find('.disclosere-icon-open').show().addClass('display');
                    } else{
                        $(this).find('.disclosere-icon-closed').show().addClass('display');
                        $(this).find('.disclosere-icon-open').hide().removeClass('display');
                    }
                });
                if(!$("body").hasClass('catalog-mvp-break-points'))
                {
                    $('.commercial-store-home .toggle-icon-level-0').on('click', function(event){
                        $('.mvp-catalog-move-popup-category-tree ul li').each(function(index){
                            $(this).attr('id', '');
                        });
                        $('.mvp-catalog-move-popup-category-tree>ul>li').each(function(index){
                            $(this).attr('id', '');
                            $(this).css('display', 'block');
                        });
                        setTimeout(function() {
                            $('.mvp-catalog-move-popup-category-tree ul li:not([style*="display: none"])').each(function(index){
                                $(this).attr('id', index + 1);
                                $('.mvp-catalog-move-popup-category-tree ul li').each(function(index){
                                    if ($(this).attr('id') > 10){
                                        $(this).hide();
                                    }
                                });
                            });}, 1000);
                    });
                    $('.commercial-store-home .toggle-icon-level-1').on('click', function(event){
                        $('.mvp-catalog-move-popup-category-tree ul li').each(function(index){
                            $(this).attr('id', '');
                        });
                        $('.mvp-catalog-move-popup-category-tree>ul>li').each(function(index){
                            $(this).attr('id', '');
                            $(this).css('display', 'block');
                        });
                        setTimeout(function() {
                            $('.mvp-catalog-move-popup-category-tree ul li:not([style*="display: none"])').each(function(index){
                                $(this).attr('id', index + 1);
                                $('.mvp-catalog-move-popup-category-tree ul li').each(function(index){
                                    if ($(this).attr('id') > 10){
                                        $(this).hide();
                                    }
                                });
                            });}, 1000);
                    });
                }
                
                $('.cancel-btn-move-popup').click(function() {
                    jQuery('button.action-close').trigger('click');
                });
                $('.commercial-store-home .toggle-icon-level-1').on('click', function(event){
                    var link = $(this);
                    var closest_li = link.closest('li');
                    var closest_ul = closest_li.children('ul');
                    var open_li = closest_ul.children('li');
                    open_li.slideToggle("slow");
                    if (event.target.className === 'disclosere-icon-closed level-1 display') {
                        $(this).find('.disclosere-icon-closed').hide().removeClass('display');
                        $(this).find('.disclosere-icon-open').show().addClass('display');
                    } else{
                        $(this).find('.disclosere-icon-closed').show().addClass('display');
                        $(this).find('.disclosere-icon-open').hide().removeClass('display');
                    }
                });
                $('.commercial-store-home .toggle-icon-level-all').on('click', function(event){
                    var link = $(this);
                    var closest_li = link.closest('li');
                    var closest_ul = closest_li.children('ul');
                    var open_li = closest_ul.children('li');
                    open_li.slideToggle("slow");
                    if (event.target.className === 'disclosere-icon-closed level-all display') {
                        $(this).find('.disclosere-icon-closed').hide().removeClass('display');
                        $(this).find('.disclosere-icon-open').show().addClass('display');
                    } else{
                        $(this).find('.disclosere-icon-closed').show().addClass('display');
                        $(this).find('.disclosere-icon-open').hide().removeClass('display');
                    }
                });
                if(!$("body").hasClass('catalog-mvp-break-points'))
                {
                    $('.mvp-catalog-move-popup-category-tree ul li:not([style*="display: none"])').each(function(index){
                        $(this).attr('id', index + 1);
                        if ($(this).attr('id') > 10){
                            $(this).hide();
                        }
                    });
                }
                
                /* B-1694113-Implement search folder functionality in Move To modal */
                $(".mvp-move-search-input-field .mvp-move-search-icon").on('click',function(event){
                    var search_category_by_name = $('#mvp-move-search-input').val();
                    var searchurl = config.searchcategorybynameUrl;
                    var currentCategoryId = config.getCurrentCategoryId;
                    $.ajax({
                        url: searchurl,
                        type: 'post',
                        data: {
                            search_category_by_name: search_category_by_name,
                            currentCategoryId: currentCategoryId
                        },
                        showLoader: true,
                        success: function (data){
                            if (data != ''){
                                $('.category-tree-level-0').html(data);
                                mvpMoveSearch();
                                $(".selected").removeClass("selected");
                                $(".shared-catalog-label-active").removeClass("shared-catalog-label-active");
                                $('.move-action').prop('disabled', true);
                                $(".move-action").removeClass("active");
                            }
                        }
                    });
                });

                var $checkboxes = $('.product-item-checkbox.list-checkbox');
                var $categoryCheckboxes = $('.category-item-checkbox.list-checkbox');
                var $listCheckBox = $('.list-checkbox');

                $listCheckBox.change(function(){
                    var countCheckedCheckboxes = $checkboxes.filter(':checked').length;
                    var checkboxes = $checkboxes.filter(':checked').length;
                    var categoryCheckboxCount = $categoryCheckboxes.filter(':checked').length;
                    var allcheckbox = $listCheckBox.filter(':checked').length;
                    var isCustomizableCheckboxSelected = $checkboxes.filter('.customizable:checked').length > 0;
                    var isPendingReviewCheckboxSelected = $checkboxes.filter('.isPendingReview:checked').length > 0;
                    var isCustomDocEnabled = config.isCustomDocEnabledJson;
                    let totalCount;
                    if(window.e383157Toggle){
                        totalCount = fxoStorage.get("totalCount");
                    }else{
                        totalCount = localStorage.getItem("totalCount");
                    }
                    if ((allcheckbox-1) < totalCount) {
                        $('.check-box-check-all').prop('checked', false);
                        $('.item-count').text(allcheckbox-1);
                        $('.item-count').val(allcheckbox-1);
                    }
                    if(allcheckbox==1 && categoryCheckboxCount==1){
                        $(".selected-number").removeClass("show");
                        return;
                    }
                    if(isCustomDocEnabled && isCustomizableCheckboxSelected){
                        $(".bulk-add-to-cart").hide();
                        $(".bulk-move-to").show();
                    } else if (checkboxes && categoryCheckboxCount == 0) {
                        if (isPendingReviewCheckboxSelected) {
                            $(".bulk-add-to-cart").hide();
                        } else {
                            $(".bulk-add-to-cart").show();
                        }
                        $(".bulk-move-to").show();
                    } else {
                        $(".bulk-add-to-cart").hide();
                        $(".bulk-move-to").hide();
                    }
                    if (allcheckbox) {
                        if ($(".check-box-check-all").is(":checked") == false) {
                            $(".selected-number").addClass("show");
                            let totalCount;
                            if(window.e383157Toggle){
                                totalCount = fxoStorage.get("totalCount");
                            }else{
                                totalCount = localStorage.getItem("totalCount");
                            }
                            if ((allcheckbox-1) < totalCount) {
                                $('.check-box-check-all').prop('checked', false);
                                $('.item-count').text(allcheckbox-1);
                                $('.item-count').val(allcheckbox-1);
                                if(window.e383157Toggle){
                                    fxoStorage.set('totalCount', '');
                                }else{
                                    localStorage.setItem("totalCount",'');
                                }
                            } else {
                                $('.item-count').text(allcheckbox);
                                $('.item-count').val(allcheckbox);
                            }
                        }
                        if (allcheckbox < 2 && screen.width < 768) {
                            $(".selected-number").hide();
                        } else if (screen.width < 768) {
                            $(".selected-number").show();
                        }
                        /*B-1611718*/
                        if(allcheckbox < 2){
                            /*B-1651604*/
                            if ($checkboxes.is(":checked") && $(window).width() >= 768) {
                                $(window).scrollTop(0);
                                var itemId = $checkboxes.filter(':checked').attr("id").split("_");
                                var url = config.rightpanelUrl;
                                var isCustomDocEnabled = config.isCustomDocEnabled;
                                $(".delete-action"). attr('id', parseInt(itemId[1]));
                                $(".action_kebab_right_item_settings").html('');
                                $(".action_kebab_right_item_request").html('');
                                $.ajax({
                                    url: url,
                                    type: 'get',
                                    dataType: 'JSON',
                                    data: {
                                        item_id: parseInt(itemId[1])
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
                                        let isD217161Enabled = config.isD217161Enabled;
                                        if (!isD217161Enabled) {
                                            let catalogExpiryToggle = config.isCatalogExpiryNotificationToggle;
                                            if (catalogExpiryToggle) {
                                                if (!response.renewLink) {
                                                    $(".action_kebab_right_item_renew").parent('.menu-item').hide();
                                                } else {
                                                    $(".action_kebab_right_item_renew").parent('.menu-item').show();
                                                }
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
                            var targetDiv = $('.right-pannel');
                            targetDiv.show();
                            showPanel();
                        }
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

            // check all functionality
            $(".check-box-check-all").on('click', function(){
                if ($(".check-box-check-all").is(":checked")) {
                    $(".selected-number").addClass("show");
                } else {
                    $(".selected-number").removeClass("show");
                }
                $checkboxes.parent().parent()[this.checked ? "addClass" : "removeClass"]("checked");
                $categoryCheckboxes.parent().parent()[this.checked ? "addClass" : "removeClass"]("checked");
                var countCheckedCheckboxes = $checkboxes.filter(':checked').length;
                var catgeoyCheckboxCount = $categoryCheckboxes.filter(':checked').length;
                var totalCount = countCheckedCheckboxes + catgeoyCheckboxCount;
                if(window.e383157Toggle){
                    fxoStorage.set('totalCount', totalCount);
                }else{
                    localStorage.setItem("totalCount",totalCount);
                }
                if (totalCount) {
                    $('.item-count').text(totalCount);
                    $('.item-count').val(totalCount);
                }
            });

            let bodyCls = $('body').attr('class'),
            isSharedCatalog = "browse-catalog";

            if(bodyCls.indexOf(isSharedCatalog) !== -1){
                $('body').addClass('catalog-mvp-shared-catalog');
            }

            $(".bulk-delete").on('click',function(event){
                $('#custom-model-delete-popup').modal(options, $('#custom-model-delete-popup')).modal('openModal');
                $("#custom-model-delete-popup action")
                $(".bulk-delete-action").show();
                $(".delete-action").hide();
                $(".kebab-delete-action").hide();

                var categoryID = [];
                $('.sub-category.checked').each(function() {
                   let catid = $(this).attr('id')
                    catid = catid.replace('sub-cat-','');
                    categoryID.push(catid);
                });
                if(window.e383157Toggle){
                    fxoStorage.set("categoryID",categoryID);
                }else{
                    localStorage.setItem("categoryID",categoryID);
                }
                var productID = [];
                $('.category-product.checked').each(function() {
                   let pid = $(this).attr('id');
                    pid = pid.replace('product-list-','');
                    productID.push(pid);
                });
                if (window.e383157Toggle) {
                    fxoStorage.set("productID", productID);
                } else {
                    localStorage.setItem("productID", productID);
                }
            });
        });

        $(document).on("change", ".product-item-checkbox.list-checkbox", function () {
            $(this).parent().parent()[this.checked ? "addClass" : "removeClass"]("checked");
            let productIds = [];
            jQuery('.list-checkbox.product-item-checkbox:checked').each(function() {
                productIds.push(jQuery(this).attr('name'));
            });
            productIds = productIds.join(',');
            fxoStorage.set('productID', productIds);
     });

        $(document).on("change", ".category-item-checkbox.list-checkbox", function () {
            $(this).parent().parent()[this.checked ? "addClass" : "removeClass"]("checked");
            let categoryIds = [];
            jQuery('.list-checkbox.category-item-checkbox:checked').each(function() {
                categoryIds.push(jQuery(this).attr('value'));
            });
            categoryIds = categoryIds.join(',');
            fxoStorage.set('categoryID', categoryIds);
        });

        $(".cancel-all").on('click', function(){
            $(".selected-number").removeClass("show");
            $(".product-item-checkbox.list-checkbox").prop("checked", false);
            $(".product-item-checkbox.list-checkbox").parent().parent().removeClass("checked");
            $(".category-item-checkbox.list-checkbox").prop("checked", false);
            $(".category-item-checkbox.list-checkbox").parent().parent().removeClass("checked");
            $(".check-box-check-all").prop("checked", false);

            /*B-1611718*/
            hidePanel();
        });

        /* Bulk add to cart */

        $(".right-section .bulk-add-to-cart").on('click', function(event){
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
                           if(response.isInBranchProductExist == true)
                            {
                                inBranchWarning.inBranchWarningPopup();
                            }else {
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
                },
                complete: function () {
                    $(document).trigger("mvp_add_to_cart_end");
                }
            });
        });

        $(".right-section-action-kebab-mobile .bulk-add-to-cart").on('click', function(event){
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
                           if(response.isInBranchProductExist == true)
                            {
                                inBranchWarning.inBranchWarningPopup();
                            }else {
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
                },
                complete: function () {
                    $(document).trigger("mvp_add_to_cart_end");
                }
            });
        });

        // Category publish toggle update category
        $('.sub-category .published input[type="checkbox"]').change(function() {
            var catid = $(this).attr('id');
            catid = catid.replace('cat-','');
            var currentStatus = $(this).prop('checked');
            currentStatus = (currentStatus) ? "1" : "0";

            var url = config.updatecategoryUrl;
            $.ajax({
                type: "post",
                url: url,
                showLoader: true,
                data: { id: catid, status: currentStatus },
                success: function(response) {
                }
            });
        });

        $('.category-product .published input[type="checkbox"]').change(function() {
            var pid = $(this).attr('id');
            pid = pid.replace('product-','');
            var url = config.updateproductUrl;
            $.ajax({
                type: "post",
                url: url,
                showLoader: true,
                data: { id: pid},
                success: function(response) {
                }
            });
        });
        $(".kebab .kebab-image" ).on("keypress", function(e) {
            if (e.keyCode == 13) {
                e.stopPropagation();
                $(this).parent().next().show();
                var clickedItem = $($(this).parent().next());
                var otherItems = $(".kebab-options").not(clickedItem);
                otherItems.hide();
            }
        } );
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

        $('.delete-action').click(function() {
            var pid = $(this).attr('id');
            pid = pid.replace('delete-','');
            var url = config.deleteproductUrl;
            var cuurentUrl = config.cuurentUrl;
            $.ajax({
                type: "post",
                url: url,
                showLoader: true,
                data: { id: pid},
                success: function(response) {
                    if(response.delete) {
                        $(document).trigger("toast_message_fired",response.message);
                        $('button.action-close').trigger('click');
                        $("#product-item-info_" + pid).parent().hide();
                        hidePanel();
                        $(".selected-number.show").hide();
                        location.replace(cuurentUrl);
                    }
                }
            });
        });
        $('.cancel-btn-delete').click(function() {
            jQuery('button.action-close').trigger('click');
        });

        $(".action-kebab-add-to-cart").on('click', function(event){
            var selectedProductId = $(this).attr("data-item-id");
            var url = config.addtocartsingleUrl;
            var qty = 0;
            $.ajax({
                type: "post",
                url: url,
                showLoader: true,
                data: { id: selectedProductId, qty:qty },
                success: function(response) {
                    if (response) {
                            if(response.isInBranchProductExist == true)
                            {
                                inBranchWarning.inBranchWarningPopup();
                            }else{
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
                    }
                },
                complete: function () {
                    $(document).trigger("mvp_add_to_cart_end");
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

        /* Customize Url with Qty */

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

        $('.bulk-delete-action').click(function() {
            var url = config.bulkdeleteUrl;
            var currentUrl = config.cuurentUrl;
            $.ajax({
                type: "post",
                url: url,
                showLoader: true,
                data: {
                    cid:window.e383157Toggle ? fxoStorage.get('categoryID') : localStorage.getItem("categoryID"),
                    pid:window.e383157Toggle ? fxoStorage.get('productID') : localStorage.getItem("productID")
                },
                success: function(response) {
                    if(response.delete) {
                        $(document).trigger("toast_message_fired",response.message);
                        location.replace(currentUrl);
                    }
                }
            });
        });

        $(".kebab-delete-action").click(function() {
            var cid = $(this).attr('id');
            $("kebab-delete-action").hide();
            cid = cid.replace('delete-','');
            var url = config.deletecategoryUrl;
            var currentUrl = config.cuurentUrl;
            $.ajax({
                type: "post",
                url: url,
                showLoader: true,
                data: {
                    cid:cid
                },
                success: function(response) {
                    if(response.delete) {
                        $(document).trigger("toast_message_fired",response.message);
                        location.replace(currentUrl);
                    }
                }
            });
        });

        $(".menu-item-label.Duplicate a").click(function() {
            var selectedProductId = $(this).attr("data-item-id");
            var url = config.duplicateproductUrl;
            $.ajax({
                type: "post",
                url: url,
                showLoader: true,
                data: {
                    pid:selectedProductId,
                    viewMode: config.viewMode
                },
                success: function(response) {
                    if(window.b2034092Toggle) {
                        if(response.duplicate) {
                            $(document).trigger("toast_message_fired",response.message);
                        }
                    }
                    if(response.html) {
                        $(response.html).insertBefore(".category-product:first");
                        $(".duplicate .rename-editable").focus();
                    }
                }
            });
        });

        $(".action_kebab_right_item_duplicate").click(function() {
            var selectedProductId;
            $('.product-item-checkbox:checked').each(function() {
                selectedProductId = $(this).attr('name');
            });
            var url = config.duplicateproductUrl;
            $.ajax({
                type: "post",
                url: url,
                showLoader: true,
                data: {
                    pid:selectedProductId,
                    viewMode: config.viewMode
                },
                success: function(response) {
                    if(response.html) {
                        $(response.html).insertBefore(".category-product:first");
                        $(".duplicate .rename-editable").focus();
                    }
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
            $('.kebab-image').attr('tabindex',0);
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
        // Request change button implementation
        let requestOptions = {
            type: 'popup',
            responsive: true,
            innerScroll: false,
            clickableOverlay: false,
            modalClass: 'mvp-change-request-modal',
            title: '',
            buttons: []
        };
        let settingModalPopupOptions = {
            type: 'popup',
            responsive: true,
            modalClass: 'shared-catalog-setting-modal',
            innerScroll: true,
            buttons: [],
            close: [],
        };
        // Renew popup start
        let renewOptions = {
            type: 'popup',
            responsive: true,
            innerScroll: false,
            clickableOverlay: false,
            modalClass: 'mvp-change-request-modal renew-mvp-change-request-modal',
            title: '',
            buttons: []
        };
        $(document).on('click', 'div.action_kebab_right_item_renew' , function() {
            $("#modal-content-renew").attr('prodId', $(this).find('a').attr('prodid'));
            $('#modal-content-renew').modal(
                renewOptions,
                $('#modal-content-renew')).modal('openModal');
        });
        $(document).on('click', '.renew-cancel' , function() {
            $('#modal-content-renew').modal(
                renewOptions,
                $('#modal-content-renew')).modal('closeModal');
        });

        $(document).on('click', '#renew-popup-container' , function() {
            var selectedProductId;
            if ($('.product-item-checkbox').is(":checked")) {
                $('.product-item-checkbox:checked').each(function() {
                    selectedProductId = $(this).attr('name');
                });
            } else {
                selectedProductId = $("#modal-content-renew").attr('prodId');
            }
            let url = config.UpdateCatalogExpiryDateUrl;
            jQuery.ajax({
            dataType: 'json',
            type: "POST",
            url: url,
            showLoader: true,
            data: { id: selectedProductId},
                success: function(data) {
                    if(data.status == 'success') {
                    $('#modal-content-renew').modal('closeModal');
                    $('.renew-success-msg').html('');
                    $('.renew-success-msg').append('<p>Catalog item has been renewed.</p>');
                    $('.renew-success').show();
                    }
                }
            });
        });

        $('.renew-close-icon').on('click', function () {
            $('.renew-success').hide();
        });

        // Renew model popup end
        $(document).on('click', '#change-request-product' , function() {
           
            let userWorkSpaceData = $(this).find("a").attr('userWorkSpace');
            let listItems = '';
            let filesCount = 0;
            if (config.nonStandardReplaceFileToggle && typeof (userWorkSpaceData) !== 'undefined' && userWorkSpaceData !== null && userWorkSpaceData !== '') {
                let documentImageSrc = $('.document-icon-src').attr('data-src');
                let userWorkSpaceObject = JSON.parse(userWorkSpaceData);
                userWorkSpaceObject.forEach(file => {
                    filesCount++;
                    listItems += `
                        <li class="file-upload-li" id="document-file-id-${file.id}" data-id="${file.id}" data-name="${file.name}" data-size="${file.size}" data-date="${file.uploadDateTime}"><img src="${documentImageSrc}" class="file-icon" aria-describedby="File Icon" alt="File Icon" /><span class="file-name">${file.name}</span><span class="remove-file" id="${file.id}" tabindex="0">REMOVE</span></li>`;
                });
                $(".file-upload-container").show();
            }

            $('#modal-content-change-request').modal(requestOptions, $('#modal-content-change-request')).modal('openModal');
            $('#modal-content-change-request').show();
            $('#print_instruction').attr('prodid', $(this).find("a").attr('prodid'));
            $('.product_name').text($(this).find("a").attr('prodname'));
            $('#print_instruction').val('');
            if (config.nonStandardReplaceFileToggle && config.nonStandardToggle) {
                let nonStandardReplaceFileText = nonStandardReplaceFileConfig['replace_file_text'];
                let fileMaxLimit = nonStandardReplaceFileConfig['replace_file_max_limit'];
                $('.replace_file_text').text(nonStandardReplaceFileText);
                $(".file-upload-container .file-upload-ul").html(listItems);
                $(".file-upload-container .filesCount").html(filesCount);

                if (filesCount == fileMaxLimit) {
                    $(".replace_file_cta").text('');
                } else if (filesCount > 0 && filesCount < fileMaxLimit) {
                    $(".replace_file_cta").text("ADD NEW FILE");
                } else {
                    $(".replace_file_cta").text("REPLACE FILE");
                } 
            } else {
                $('.replace_file').hide();
            }

            $('#char_left').text('400 characters left').css('color','#3333');
        });

        $(document).on('click', '.replace_file_cta', function (e) {
            if (e.type === "click") {
                $("#replace_file_upload").val('');
                $('#replace_file_upload').trigger('click');
            }
        });

        $(document).on('click keypress', '.remove-file', function (e) {
            if (e.type === "click" || (e.type === "keypress" && (e.which === 13 || e.which === 32))) {
                let documentId = $(this).attr("id");
                $("#document-file-id-"+documentId).remove();
                $("#replace_file_upload").val('');
                let fileCount = $(".file-upload-container .filesCount").html();
                $(".file-upload-container .filesCount").html(fileCount-1);
                let fileMaxLimit = nonStandardReplaceFileConfig['replace_file_max_limit'];
                if ($(".file-upload-li").length == 0) {
                    $(".file-upload-container").hide();
                    $(".replace_file_cta").text("REPLACE FILE");
                } else if ($(".file-upload-li").length > 0 && $(".file-upload-li").length < fileMaxLimit) {
                    $(".replace_file_cta").text("ADD NEW FILE");
                }
            }
        });

        $(document).on('click keypress', '.validation-error .cross-icon', function (e) {
            if (e.type === "click" || (e.type === "keypress" && (e.which === 13 || e.which === 32))) {
                $(this).parent(".validation-error").hide();
            }
        });
        $(document).on('click keypress', '.validation-error-file-length .cross-icon', function (e) {
            if (e.type === "click" || (e.type === "keypress" && (e.which === 13 || e.which === 32))) {
                $(this).parent(".validation-error-file-length").hide();
            }
        });
        $(document).on('click keypress', '.validation-error-file-size .cross-icon', function (e) {
            if (e.type === "click" || (e.type === "keypress" && (e.which === 13 || e.which === 32))) {
                $(this).parent(".validation-error-file-size").hide();
            }
        });
        $(document).on('click keypress', '.validation-error-file-type .cross-icon', function (e) {
            if (e.type === "click" || (e.type === "keypress" && (e.which === 13 || e.which === 32))) {
                $(this).parent(".validation-error-file-type").hide();
            }
        });

        // Handle File upload with new document API from Change request modal
        $('#replace_file_upload').on('change', function() {
            //console.log($('#replace_file_upload').prop('files'));
            let productSku = $("#print_instruction").attr("prodid");
            let fileMaxLimitMsg = nonStandardReplaceFileConfig['replace_file_max_limit_msg'];
            let fileMaxLimit = nonStandardReplaceFileConfig['replace_file_max_limit'];
            let fileNameLengthMsg = nonStandardReplaceFileConfig['replace_file_name_length_msg'];
            let fileNameLength = nonStandardReplaceFileConfig['replace_file_name_length'];
            let fileSupportedTypes = nonStandardReplaceFileConfig['replace_file_supported_types'];
            let fileMaxSizeMsg = nonStandardReplaceFileConfig['replace_file_max_size_msg'];
            let fileMaxSize = nonStandardReplaceFileConfig['replace_file_max_size'];
            let fileLimitErrorMessage = '';
            let fileNameLengthErrorMessage = '';
            let fileTypeErrorMessage = '';
            let fileMaxSizeErrorMessage = '';
            let totalFileUploadCount = 0;
            let replaceFilesObj = document.getElementById("replace_file_upload");
            let documentImageSrc = $('.document-icon-src').attr('data-src');
            let existingUploadedFileCount = 0;
            $(".file-upload-li").each(function() {
                existingUploadedFileCount = existingUploadedFileCount+1;
            });
            
            let extArr = fileSupportedTypes.split(","); 
            let trimmedExtArr = extArr.map(ext => ext.trim());

            if (replaceFilesObj.files.length > 0) {
                let uploadFileCount = replaceFilesObj.files.length;
                totalFileUploadCount = uploadFileCount + existingUploadedFileCount;
                if (totalFileUploadCount > fileMaxLimit) {
                    // Show message here of max files
                    fileLimitErrorMessage = fileMaxLimitMsg+ " "+fileMaxLimit+".";
                    totalFileUploadCount = existingUploadedFileCount;
                } else {
                    if ('files' in replaceFilesObj) {
                        for (let count = 0; count < replaceFilesObj.files.length; count++) {
                            // reset previous file error messages
                            let fileNameLengthError = false;
                            let fileMaxSizeError = false;
                            let fileTypeError = false;
                            var file = replaceFilesObj.files[count];

                            let fileName = file.name;
                            let fileNameArr = fileName.split(".");                            
                            
                            if ('name' in file) {
                                // Exclude extension while calculating length
                                let fileNameArrWithoutExt = fileNameArr.slice(0, -1);
                                let fileNameWithoutExt = fileNameArrWithoutExt.join("");
                                if (fileNameWithoutExt.length > fileNameLength ) {
                                    fileNameLengthErrorMessage = fileNameLengthMsg+ " "+fileNameLength+".";
                                    fileNameLengthError = true;
                                }
                            }
                            if ('size' in file) {
                                //Convert in bytes so use formula to calculate in MB > 150 MB
                                let uploadedFileSizeInMB = (file.size / 1048576).toFixed(2); // (Bytes / 1024 * 1024)
                                if (uploadedFileSizeInMB > fileMaxSize) {
                                    fileMaxSizeErrorMessage = fileMaxSizeMsg+" "+fileMaxSize + "MB.";
                                    fileMaxSizeError = true;
                                }
                            }
                            if ('type' in file) {
                                let fileExtensionType = fileNameArr.slice(-1);
                                let fileExtension = fileExtensionType.join("");
      
                                if (!trimmedExtArr.includes(fileExtension.trim())) {
                                    fileTypeErrorMessage = '<span style="color:#E3002D;font-family: FedEx Sans bold;font-size: 14px;font-weight: 700;">Your file type is not supported </span> <br/>';
                                    fileTypeErrorMessage += file.name + ' is not supported. Please make sure <br/>that your file uses an accepted format listed below. Click "View <br/>Details" to view all accepted file types.<br/>';
                                    fileTypeErrorMessage += '<span class="file-extensions">'+extArr.slice(0, 11)+'</span>';
                                    fileTypeErrorMessage += '<br/><br/><a data-extensions="'+extArr+'" class="display-ext-types" href="javascript:void(0);" style="color:#0078B4;font-family: FedEx Sans Regular;font-size: 14px;font-weight: 700;">VIEW DETAILS</a>';
                                    fileTypeError = true;
                                }
                            }

                            let documentApiUrl = urlBuilder.build("fedexcatalog/product/newdocumentapi");
                            var formData = new FormData();
                            formData.append('filepath', file);
                            formData.append('sku', productSku);
                            if (!fileNameLengthError && !fileMaxSizeError && !fileTypeError) {
                                //Ajax call to integrate API Here.
                                jQuery.ajax({
                                    url: documentApiUrl,
                                    type: "POST",
                                    data: formData,
                                    dataType: "json",
                                    showLoader: true,
                                    contentType: false,
                                    enctype: 'multipart/form-data',
                                    processData: false,
                                    success: function(data) {
                                        // ToDO: check for undefined output first and then document name
                                        if (typeof (data.output) !== 'undefined' && data.output !== null && typeof (data.output.document) !== 'undefined' && data.output.document !== null) {
                                            $(".file-upload-container").show();
                                            
                                            let documentDate = new Date(data.output.document.currentDateTime).toISOString()
                                            $(".file-upload-container .file-upload-ul").append(
                                                '<li class="file-upload-li temp" id="document-file-id-'+data.output.document.documentId+'" data-id="'+ data.output.document.documentId +'" data-name="'+ data.output.document.documentName +'" data-size="'+ data.output.document.documentSize +'" data-date="'+ documentDate +'"><img src="'+documentImageSrc+'" class="file-icon" aria-describedby="File Icon" alt="File Icon" /><span class="file-name">'+ data.output.document.documentName +'</span><span class="remove-file" id="'+ data.output.document.documentId +'" tabindex="0">REMOVE</span></li>');
                                            
                                            if (totalFileUploadCount < fileMaxLimit) {
                                                $(".replace_file_cta").text("ADD NEW FILE");
                                            } else {
                                                $(".replace_file_cta").text("");
                                            }
                                        }
                                    }
                                });
                            } else {
                                totalFileUploadCount--;
                            }
                        }
                    }
                } // Else closing
            } // If close

            $('.file-upload-container').each( function() {
                $('.validation-error').hide();
                $(".validation-error-file-length").hide();
                $(".validation-error-file-size").hide();
                $(".validation-error-file-type").hide();
                $(".validation-error .error-msg-container .error-text-msg").html('');
                $(".validation-error-file-length .error-msg-container .error-text-msg").html('');
                $(".validation-error-file-size .error-msg-container .error-text-msg").html('');
                $(".validation-error-file-type .error-msg-container .error-text-msg").html('');
            });      
        
            if (fileLimitErrorMessage) {
                $(".file-upload-container").show();
                $(".file-upload-container .validation-error").show();
                $(".file-upload-container .validation-error .error-msg-container .error-text-msg").append(fileLimitErrorMessage);
            }
            if (fileNameLengthErrorMessage) {   
                $(".file-upload-container").show();
                $(".file-upload-container .validation-error-file-length").show();
                $(".file-upload-container .validation-error-file-length .error-msg-container .error-text-msg").append(fileNameLengthErrorMessage);
            }
            if (fileMaxSizeErrorMessage) {
                $(".file-upload-container").show();
                $(".file-upload-container .validation-error-file-size").show();
                $(".file-upload-container .validation-error-file-size .error-msg-container .error-text-msg").append(fileMaxSizeErrorMessage);
            }
            if (fileTypeErrorMessage) {
                $(".file-upload-container").show();
                $(".file-upload-container .validation-error-file-type").show();
                $(".file-upload-container .validation-error-file-type .error-msg-container .error-text-msg").append(fileTypeErrorMessage);
            }
            $(".file-upload-container .filesCount").html(totalFileUploadCount);
        });

        $(document).on('click', '.display-ext-types' , function(e) {
            $('.file-extensions').html($(this).attr('data-extensions'));
            $(this).hide();
        });

        let max = 400;
        $(document).on('keyup', '#print_instruction' , function(e) {
            let len = $(this).val().length;
            if (len > 0) {
                $(".request-change").prop('disabled', false);
            } else {
                $(".request-change").prop('disabled', true);
            }
            if (len >= max) {
                $('#char_left').text('you have reached the limit').css('color','red');
                e.preventDefault();
                $(this).val($(this).val().substring(0, max));
            } else {
                var ch = max - len;
                $('#char_left').text(ch + ' characters left').css('color','#3333');
            }
        });
        
        $(document).on('click', '.modal-popup.mvp-change-request-modal .action-close', function(event) {
            let $liTempCount = 0;
            let $liCount = 0;
            $(".file-upload-container .validation-error .error-msg-container .error-text-msg").html('');
            $(".validation-error").hide();
            $(".validation-error-file-length").hide();
            $(".validation-error-file-size").hide();
            $(".validation-error-file-type").hide();
            $(".file-upload-li").each(function() {
                $liCount = $liCount+1;
            });
            $(".file-upload-li.temp").each(function() {
                $liTempCount = $liTempCount+1;
                $(".file-upload-li.temp").remove();
            });

            if ($liTempCount == $liCount) {
                $(".file-upload-container").hide();
                $(".replace_file_cta").text("REPLACE FILE");
            }
            $(".request-change").prop('disabled', true);
        });

        $(document).on('click', '.request-change-cancel', function(event){
            event.stopPropagation()
            let $liTempCount = 0;
            let $liCount = 0;
            $(".file-upload-container .validation-error .error-msg-container .error-text-msg").html('');
            $(".validation-error").hide();
            $(".validation-error-file-length").hide();
            $(".validation-error-file-size").hide();
            $(".validation-error-file-type").hide();
            $(".file-upload-li").each(function() {
                $liCount = $liCount+1;
            });
            $(".file-upload-li.temp").each(function() {
                $liTempCount = $liTempCount+1;
                $(".file-upload-li.temp").remove();
            });

            if ($liTempCount == $liCount) {
                $(".file-upload-container").hide();
                $(".replace_file_cta").text("REPLACE FILE");
            }
            $(".request-change").prop('disabled', true);
            $('#modal-content-change-request').modal(requestOptions, $('#modal-content-change-request')).modal('closeModal');
        });

        // Setting model popup start
        $('.settings_sku').html('');
        $(document).on('click', '#change-settings-product, .action_kebab_right_item_settings', function(event){
            let url = config.changeSettingsUrl;
            jQuery.ajax({
                type: "post",
                url: url,
                showLoader: true,
                data: { sku: $(this).find("a").attr('prodid')},
                success: function(response) {
                    if(response.status) {
                        $('.settings_sku').html(response.output);
                        $('#setting-formp-popup').modal(
                            settingModalPopupOptions, $('#setting-formp-popup')
                        ).modal('openModal');
                    }
                }
            });
        });

        // Setting model popup end
         // Setting model popup end
         $(document).on('click keypress', '.request-change', function (e) {
            if (e.type === "click" || (e.type === "keypress" && (e.which === 13 || e.which === 32))) {
                let id = $('#print_instruction').attr("prodid");
                let specialInstruction = $('#print_instruction').val();
                if (!$.trim(specialInstruction).length) {
                    $(".print_error").text('This is required').css('color','red').fadeIn(3000);
                    return false;
                }
                var files = [];
                $(".file-upload-li").each(function() {
                    var file = {
                        name: $(this).data("name"),
                        id: $(this).data("id"),
                        size: $(this).data("size"),
                        uploadDateTime: $(this).data("date")
                    };

                    files.push(file);
                });
                let url = config.changeRequestUrl;
                jQuery.ajax({
                    type: "post",
                    url: url,
                    showLoader: true,
                    data: { id: id, specialInstruction: specialInstruction, userWorkSpace:files},
                    success: function(response) {
                        if (response.status) {
                            $('#modal-content-change-request').modal(requestOptions,
                            $('#modal-content-change-request')).modal('closeModal');
                            location.reload();
                        } else {
                            $(".print_error").text(response.message).css('color','red').fadeOut(3000);
                        }
                    }
                });
            }
        });

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
            if (jQuery('.products.wrapper').hasClass('products-grid')) {
                    var targetDiv = $('.right-pannel');
                    if (targetDiv.length) {
                            targetDiv.hide();
                            $('body').addClass('page-layout-2columns-left');
                            $('body').removeClass('page-layout-3columns');
                            $('body').removeClass('flyout-modal-mobile');
                    }
             }
        });
        $('.product-item-details-mobile').on('click', function () {
            var targetDiv = $('.right-pannel');
            if (targetDiv.length) {
                $('body').addClass('flyout-modal-mobile');
                targetDiv.show();
            }
        });
        /* B-2182041 flyout end open/close icon */
        /* B-2182032 flyout tablet */
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
    }
});
