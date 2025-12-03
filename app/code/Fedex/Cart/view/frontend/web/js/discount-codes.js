/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define(['jquery','fedex/storage', 'jquery-ui-modules/widget'], function ($,fxoStorage) {
    'use strict';
     $(".coupon").trigger('processStop');
     var modalWidgetMixin = {
        options: {
        },

        /**
         *
         * @returns {Element}
         */
        _create: function () {
            this.couponCode = $(this.options.couponCodeSelector);
            this.removeCoupon = $(this.options.removeCouponSelector);
             $(this.options.applyButton).on('click', $.proxy(function () {
                this.couponCode.attr('data-validate', '{required:true}');
                if ($('#coupon_code').val() == ''){
                    $('#coupon-code-error').css('display', 'block');
                    setTimeout(function() {
                        $('#coupon-code-error').fadeOut('slow');
                    }, 2000);
                    return false;
                }

                this.removeCoupon.attr('value', '0');
                $('.action.apply.primary.promo-code').css('color', '#fff');
                $('#apply-promo-loader').show();
                $('#apply-account-discount-loader').show();
                $(".coupon").trigger('processStart');
                $(this.element).validation().submit();
            }, this));

            // Storing the Coupon Code on LocalStorage for CJ

            if(window.e383157Toggle){
                if ($('#coupon_code').val() != '') {
                    fxoStorage.set('coupon_code', $('#coupon_code').val());
                } else {
                    fxoStorage.set('coupon_code', '');
                }
            }else{
                if ($('#coupon_code').val() != '') {
                    localStorage.setItem('coupon_code', $('#coupon_code').val());
                } else {
                    localStorage.setItem('coupon_code', '');
                }
            }

            $(this.options.cancelButton).on('click', $.proxy(function () {
                this.couponCode.removeAttr('data-validate');
                this.removeCoupon.attr('value', '1');
                $(".coupon").trigger('processStart');
                this.element.submit();
            }, this));
        }
    };

    return function (targetWidget) {

        $.widget('mage.discountCode', targetWidget, modalWidgetMixin);

        return $.mage.discountCode;
    };
});
