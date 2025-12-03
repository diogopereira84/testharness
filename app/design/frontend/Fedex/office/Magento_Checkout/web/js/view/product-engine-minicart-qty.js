define([
    'ko',
    'jquery',
    'uiComponent',
    'underscore',
    'product'
],function(ko, $, Component, _, productCartAction){
    return Component.extend({
        tiger_team_D_186617_unable_to_delete_1p3p_product: window.tiger_team_D_186617_unable_to_delete_1p3p_product,
        tiger_D217169: window.tiger_D217169,
        editItem: function (instanceId, designId = false) {
            productCartAction.editItem(instanceId, designId)()()()
        },

        /**
         * Get Pre-Configured Qty
         *
         * @param {Int} id
         * @param {Int} version
         * @param {Int} item_id
         * @param {Int} item_qty
         * @param {Int} product_sku
         * @param {String} product_engine_url
         * @param {Array} qtyData
         *
         * @returns string
         */
        getProductEngineQtyOption: function (id, version, item_id, item_qty, product_sku, product_engine_url, qtyData) {

            var qty_text_id = '#cart-item-'+item_id+'-qty';

            var windowWidth = $(window).width();

            if(windowWidth < 760){
                qty_text_id = '.cart-item-'+item_id+'-qty';
            }

                if (qtyData.length) {
                    var optionHtml = '';
                    $.each(qtyData, function(index, value) {
                        if (item_qty == value) {
                            optionHtml+= '<option value="'+value+'" selected>'+value+'</option>';
                        } else {
                            optionHtml+= '<option value="'+value+'">'+value+'</option>';
                        }
                    });

                var qty_select_option = "<select data-bind='attr: {id: 'cart-item-'+item_id+'-qty','data-cart-item': item_id, 'data-item-qty': qty, 'data-cart-item-id': product_sku}, value: qty, class='item-qty cart-item-qty' id='cart-item-"+item_id+"-qty' data-cart-item='"+item_id+"' data-item-qty='"+item_qty+"' data-cart-item-id='"+product_sku+"'>"+optionHtml+"</select>";
                $(qty_text_id).replaceWith(qty_select_option);
                var parentElement = $(qty_text_id).closest('.disabled-qty-wrp');
                if (parentElement.length) {
                    $('#cart-item-' + item_id + '-qty').prop('disabled', true);
                }
            }
        },

        alternativeEditItem: function (fxoProductInstance, product = null, item_id = null) {
            var designId = false;
            if (_.has(fxoProductInstance, 'fxoProductInstance')) {
                if (_.has(fxoProductInstance.fxoProductInstance, 'productConfig')) {
                    if (_.has(fxoProductInstance.fxoProductInstance.productConfig, 'designProduct')) {
                        designId = fxoProductInstance.fxoProductInstance.productConfig.designProduct.designId;
                    }
                }
            } else if(_.has(fxoProductInstance, 'productConfig')) {
                if (_.has(fxoProductInstance.productConfig, 'designProduct')) {
                    designId = fxoProductInstance.productConfig.designProduct.designId;
                }
            }
            if(product.is_customize !== undefined && product.is_customize  == "1" && product.product_sku !== undefined){
                let siteName = window.siteName;
                let authToken = window.authToken;
                if(siteName != "") {
                    productCartAction.editItem(fxoProductInstance.instanceId, designId, product, product.product_sku, product.is_customize)(authToken)(siteName)();
                } else {
                    productCartAction.editItem(item_id, designId, product, product.product_sku, product.is_customize)(authToken)(siteName)();
                }

            } else {
                productCartAction.editItem(fxoProductInstance.instanceId, designId, product)()()();
            }

        },
    });
});
