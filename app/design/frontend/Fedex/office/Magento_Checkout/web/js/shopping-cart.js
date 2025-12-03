/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'jquery-ui-modules/widget',
    'mage/translate'
], function ($, confirm) {
    'use strict';

    $.widget('mage.shoppingCart', {
        /** @inheritdoc */
        _create: function () {
            var items, i, reload, cartSummaryBlock;

            $(this.options.emptyCartButton).on('click', $.proxy(function () {
                this._confirmClearCart();
            }, this));
            items = $.find('[data-role="cart-item-qty"]');

            for (i = 0; i < items.length; i++) {
                $(items[i]).on('keypress', $.proxy(function (event) { //eslint-disable-line no-loop-func
                    var keyCode = event.keyCode ? event.keyCode : event.which;

                    if (keyCode == 13) { //eslint-disable-line eqeqeq
                        $(this.options.emptyCartButton).attr('name', 'update_cart_action_temp');
                        $(this.options.updateCartActionContainer)
                            .attr('name', 'update_cart_action').attr('value', 'update_qty');

                    }
                }, this));
            }
            $(this.options.continueShoppingButton).on('click', $.proxy(function () {
                location.href = this.options.continueShoppingUrl;
            }, this));

            $(document).on('ajax:removeFromCart', $.proxy(function () {
                reload = true;
                $('div.block.block-minicart').on('dropdowndialogclose', $.proxy(function () {
                    if (reload === true) {
                        location.reload();
                        reload = false;
                    }
                    $('div.block.block-minicart').off('dropdowndialogclose');
                }));
            }, this));
            $(document).on('ajax:updateItemQty', $.proxy(function () {
                reload = true;
                $('div.block.block-minicart').on('dropdowndialogclose', $.proxy(function () {
                    if (reload === true) {
                        location.reload();
                        reload = false;
                    }
                    $('div.block.block-minicart').off('dropdowndialogclose');
                }));
            }, this));
            cartSummaryBlock = $('.checkout-cart-index .cart-summary');
            if(cartSummaryBlock.length) {
                cartSummaryBlock.attr({
                    'role': 'region',
                    'aria-label': 'Cart Summary'
                });
            }
        },

        /**
         * Display confirmation modal for clearing the cart
         * @private
         */
        _confirmClearCart: function () {
            var self = this;
            let isDeletePopupModelToggleEnabled = typeof (window.checkout) != 'undefined' && typeof (window.checkout.explorers_delete_cart_items_confirmation_modal) != 'undefined' && window.checkout.explorers_delete_cart_items_confirmation_modal != null ? window.checkout.explorers_delete_cart_items_confirmation_modal : false;
            if (isDeletePopupModelToggleEnabled) {
                let alertIconImage = typeof (window.checkout) != 'undefined' && typeof (window.checkout.alert_icon_image) != 'undefined' && window.checkout.alert_icon_image != null ? window.checkout.alert_icon_image : '';
                let contentDetails = '<div class="delete-popup-content"><h3 class="delete-item-name">Clear your cart?</h3><p class="delete-item-description">This action cannot be reversed.</p></div>';
                confirm({
                    buttons: [{
                        text: $.mage.__('CANCEL'),
                        'class': 'clear-action-secondary clear-action-dismiss',
                    }, {
                        text: $.mage.__('CLEAR CART'),
                        'class': 'clear-action-primary clear-action-accept',
                        click: function (event) {
                            self.clearCart();
                        }
                    }],
                    modalClass: 'confirm-clear-cart update-delete-popup',
                    title: '<img src="' + alertIconImage + '" class="delete-alert-icon-img" aria-label="delete_image" />',
                    content: contentDetails,
                });
                $('.modal-inner-wrap .action-close').trigger('focus');
            } else {
                confirm({
                    buttons: [{
                        text: $.mage.__('CANCEL'),
                        'class': 'clear-action-secondary clear-action-dismiss',
                    }, {
                        text: $.mage.__('YES'),
                        'class': 'clear-action-primary clear-action-accept',
                        click: function (event) {
                            self.clearCart();
                        }
                    }],
                    modalClass: 'confirm-clear-cart',
                    title: $.mage.__('Clear Shopping Cart'),
                    content: $.mage.__('Are you sure you want to remove all items from your shopping cart?'),
                });
                $('.confirm-clear-cart .action-close').trigger('focus');
            }
        },

        /**
         * Prepares the form and submit to clear the cart
         * @public
         */
        clearCart: function () {
            $(this.options.emptyCartButton).attr('name', 'update_cart_action_temp');
            $(this.options.updateCartActionContainer)
                .attr('name', 'update_cart_action').attr('value', 'empty_cart');

            if ($(this.options.emptyCartButton).parents('form').length > 0) {
                $(this.options.emptyCartButton).parents('form').submit();
            }
        }
    });

    return $.mage.shoppingCart;
});
