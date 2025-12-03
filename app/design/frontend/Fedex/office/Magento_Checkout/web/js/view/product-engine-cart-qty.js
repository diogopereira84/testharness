define([
    'jquery',
    'uiComponent',
    'productEngineAttributes'
],function($, Component, peAttributes){
    return Component.extend({

        initialize: function() {
            this._super();
            _this = this;
            var item_id     = _this.itemId;
            var id          = _this.id;//1538443253090;
            var version     = _this.version;//1;
            var item_sku    = _this.itemSku;
            var item_qty    = _this.itemQty;
            var product_engine_url    = _this.productEngineURL;
            var item_row_id = '#cart-'+_this.itemId+'-qty';
            var productEngineQty;
            var productKey = {id : id, version: version};
            let peAttributesInstance = peAttributes({
                peUrl: product_engine_url
            });

            productEngineQty =  peAttributesInstance.getQuantitySets(productKey);

            productEngineQty.then(function(result){
                if (result.length) {
                    var optionHtml = '';
                    $.each(result, function(index, value) {
                        if (item_qty == value) {
                            optionHtml+= '<option value="'+value+'" selected>'+value+'</option>';
                        } else {
                            optionHtml+= '<option value="'+value+'">'+value+'</option>';
                        }
                    });

                    var qty_select_option = '<select id="cart-'+item_id+'-qty" name="cart['+item_id+'][qty]" data-cart-item-id="'+item_sku+'" class="input-text qty product-engine-qty" data-role="cart-item-qty">'+optionHtml+'</select>';
                    $(item_row_id).replaceWith(qty_select_option);
                }
                $(item_row_id).parent().css({opacity: 1.0, visibility: "visible"}).animate({opacity: 1}, 500);
            });
        }
    });
});