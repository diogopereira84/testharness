/**
* Copyright © Fedex, Inc. All rights reserved.
* See COPYING.txt for license details.
*/

define([
    'jquery',
    'uiComponent',
    'mage/url',
    'mage/storage',
    'Magento_Ui/js/modal/modal',
    'fedex/storage',
    'jquery/ui',
    'domReady!'
], function ($, Component, urlBuilder, storage, modal, fxoStorage) {
    'use strict';
    let click = 0;
    return Component.extend({

        /** @inheritdoc */
        initialize: function () {
            this._super();
            var self = this;

            $(document).ready(function() {
                // Move folder drag and drop execution code
                if ($('.category-product-count-is-zero').val()) {
                    $(".product-items.customer-admin").css('display', 'none');
                    $(".product-items.customer-view").css('display', 'none');
                    $(".breadcrumbs mvp-breadcrumb-label").css('display', 'block');
                    $(".breadcrum-container-no-count .breadcrumbs").removeClass("mvp-breadcrumb-label");
                    $('.shared-catalog-cta-container').css('margin-top','50px');
                }
                self.moveFolderToFolder();

                $(".catalog-item-move" ).draggable({
                    revert: "invalid",
                    containment: "document",
                    helper: "clone",
                    cursor: "move"
                });

                if ($('#product-items-zero').length) {
                    if(!$('body').hasClass('customer-admin')){
                        $('body').addClass('customer-admin');
                    }
                }

                $(".droppable" ).droppable({
                    accept: ".catalog-item-move",
                    revert: true,
                    greedy: true,
                    tolerance: "pointer",
                    drop:function (event,ui) {
                        let categoryId = $(this).attr('id');
                        let productSku = $(ui.draggable).attr('id');
                        let url = urlBuilder.build('catalogmvp/index/productAssignedToCategory');

                        $.ajax({
                            type: "post",
                            url: url,
                            showLoader: true,
			    isAjax: true,
                            data: { category_id: categoryId, product_sku: productSku },
                            success: function(response) {
                                if (response.status) {
                                    location.reload(true);
                                }
                            }
                        });
                    }
                });
            });
        },

        /**
         * Open add new item mvp product popup
         */
        openAddItemPopup: function(config) {
            let options = {
                type: 'popup',
                responsive: true,
                modalClass: 'add-mvp-item-popup',
                innerScroll: true,
                buttons: []
            };
            let category = [];
            $('.sub-category .category-item-checkbox:checked').each(function(i)
            {
                category.push($(this).val());
            });
            category.push(config.currentCategoryId);
            if(window.e383157Toggle){
                fxoStorage.set("cancelurl", window.location.href);
                fxoStorage.set("categoryIds", category);
            }else{
                localStorage.setItem("cancelurl", window.location.href);
                localStorage.setItem("categoryIds", JSON.stringify(category));
            }
            $('#add-item-product-view-modal').modal(options, $('#add-item-product-view-modal')).modal('openModal');
        },

        /**
         * GetProduct using ajax request
         * @param {*} data
         * @param {*} event
         */
        getProducts: function(data, event) {
            if($('body').hasClass('catalog-mvp-break-points') && $(window).width() < 767.5 && $(event.target).attr('class') != 'catalogmvp-setproduct-arrow'){
                return;
            }
            let id = $(event.currentTarget).attr('id');
            id = id.replace('category-','');
            id = id.replace('products-','');
            $(event.currentTarget).addClass("active");
            $(".add-mvp-item-popup li.custom-pop-up-category-link").
            not(event.currentTarget).removeClass("active");
            let url = $(event.currentTarget).attr('data');
            $.ajax({
                type: "post",
                url: url,
                showLoader: true,
                isAjax: true,
                data: { id: id },
                success: function(response) {
                    if (response == ''){
                        $('.add-mvp-item-popup .products.list.items.product-items').html(
                            '<div class="message message-error">No product found for your selection.</div>'
                        );
                    } else {
                        $('.add-mvp-item-popup .products.list.items.product-items').html(response);
                    }
                }
            });
        },

        /**
         * Open setting modal
         */
        openSettingModal: function () {
            let options = {
                type: 'popup',
                responsive: true,
                modalClass: 'shared-catalog-setting-modal',
                innerScroll: true,
                buttons: [],
                close: [],
            };

            $('#setting-formp-popup').modal(options, $('#setting-formp-popup')).modal('openModal');
        },

        /**
         * Create new folder on click of new folder button
         */
        newFolder: function(config) {
            const date = new Date();
            $(".product-items.customer-admin").css('display','block');
            $(".mvp-catalog-product-an-category").css('display','none');

            let day = date.getDate();
            let month = date.getMonth() + 1;
            let year = date.getFullYear();

            let currentDate = `${month}/${day}/${year}`;

            if (click > 0) {
                var foldername = "Untitled Folder "+click;
            } else {
                var foldername = "Untitled Folder";
            }
            click++;

            if (!$('.item.product.product-item.heading').is(':visible')) {
                var newFolder = '<li class="item product product-item sub-category new-folder-custom"><div class="product-item-info"><input class="category-item-checkbox list-checkbox" type="checkbox"><div class="kebab"><div class="kebab-image category-grid"></div></div><div class="kebab-options"><div class="dropdown-menu-list"><div class="menu-item"><div class="menu-item-label action_kebab_folder_delete"><a href="#">Delete</a></div><div class="menu-item-label Move cat-move"><a href="#">Move</a></div><div class="menu-item-label action_kebab_folder_rename"><a href="#">Rename</a></div></div></div></div><span class="product-image-container"><span class="product-image-wrapper" style="padding-bottom:125%"><div class="sub-categorty-img"></div></span></span><div class="product-name-list"><span class="sub-category name product-item-name"><input type="text" id="newFolder" name="newfolder" value="'+foldername+'"></span></div><div class="modified-info"><span>'+currentDate+'</span></div></div></li>';
            } else {
                var newFolder = '<li class="item product product-item sub-category new-folder-custom newrow"> <div class="product-item-info"> <!-- Added check for drag frop for list view --> <div class="drag-drop"> <div class="row"> <figure class="odd"></figure> <figure class="even"></figure> </div> <div class="row"> <figure class="odd"></figure> <figure class="even"></figure> </div> <div class="row"> <figure class="odd"></figure> <figure class="even"></figure> </div> <div class="row"> <figure class="odd"></figure> <figure class="even"></figure> </div> </div> <input class="category-item-checkbox list-checkbox" type="checkbox" value="" aria-label="newcategory"> <span class="product-image-container"> <span class="product-image-wrapper" style="padding-bottom: 125%;"> <div class="sub-categorty-img"></div> </span> </span> <div class="product-name-list"> <span class="sub-category name product-item-name"> <input type="text" id="newFolder" name="newfolder" value="'+foldername+'"> </span> </div> <div class="modified-info"> <span>'+currentDate+'</span> </div> <div class="product details product-item-details empty small-screen-empty"> </div> <div class="published small-screen-empty"> <label class="switch"> <input type="checkbox" checked=""> <span class="custom-slider round"></span> <span class="labels" data-on="ON" data-off="OFF"></span> </label> </div> <div class="kebab  small-screen-empty"><div class="kebab-image category-list"></div></div> <div class="kebab-options"> <div class="dropdown-menu-list"> <div class="menu-item"> <div class="menu-item-label"> <a href="#">Add to Cart</a> </div> <div class="menu-item-label"> <a href="#">Edit</a> </div> <div class="menu-item-label"> <a href="#">Delete</a> </div> <div class="menu-item-label"> <a href="#">Duplicate</a> </div> <div class="menu-item-label"> <a href="#">Remove</a> </div> <div class="menu-item-label"> <a href="#">Rename</a> </div> </div> </div> </div></div> </li>';
            }

            $(".item.product.product-item.heading").after(newFolder);
            $("#newFolder").select();

            // insert the value when clicked outside
            $("#newFolder").focusout(function(){
                var name = $(this).val();
                if(name==''){
                    $(this).focus();
                    return;
                }
                $(this).after("<a href = '#'>"+name+"</a>");
                $(this).remove();
                var url = urlBuilder.build('catalogmvp/index/createcategory');
                $.ajax({
                    type: "post",
                    url: url,
                    showLoader: true,
                    isAjax: true,
                    data: { name: name, id:config.currentCategoryId },
                    success: function(response) {
                        if (response) {
                            location.reload(true);
                        }
                    }
                });
            });

            // insert the value when enter is pressed
            $('#newFolder').keypress(function(event){
                var keycode = (event.keyCode ? event.keyCode : event.which);
                if(keycode == '13'){
                    var name = $(this).val();
                    if(name==''){
                       $(this).focus();
                       return;
                    }
                    $(this).after("<a href = '#'>"+name+"</a>");
                    $(this).remove();
                    var url = urlBuilder.build('catalogmvp/index/createcategory');
                    $.ajax({
                        type: "post",
                        url: url,
                        showLoader: true,
                        isAjax: true,
                        data: { name: name, id:config.currentCategoryId },
                        success: function(response) {
                            if (response) {
                                location.reload(true);
                            }
                        }
                    });
                }
            });
        },

        /**
         * Move folder to folder (drag and drop)
         */
        moveFolderToFolder: function () {
            $(".catalog-folder-move" ).draggable({
                revert: "invalid",
                containment: "document",
                helper: "clone",
                cursor: "move",
                zIndex: 100
            });

            $("li.sub-category > div.product-item-info" ).droppable({
                accept: ".catalog-folder-move",
                revert: true,
                greedy: true,
                tolerance: "pointer",
                drop:function (event,ui) {
                    let parentCategoryId = $(this).parents('li.sub-category').attr('id');
                    let categoryId = $(ui.draggable).parents('li.sub-category').attr('id');
                    let url = urlBuilder.build('catalogmvp/index/categoryAssignedToCategory');
                    if (parentCategoryId != categoryId ) {
                        $.ajax({
                            type: "post",
                            url: url,
                            showLoader: true,
                            isAjax: true,
                            data: { parent_category_id: parentCategoryId, category_id: categoryId },
                            success: function(response) {
                                if (response.status) {
                                    location.reload(true);
                                }
                            }
                        });
                    }
                }
            });
        }
    });
});
