define([
    'jquery',
    "Magento_Customer/js/customer-data",
    "Fedex_Delivery/js/model/toggles-and-settings",
], function ($, customerData, togglesAndSettings) {
    'use strict';

    return  {
        getShippingAmount: function(deliveryLine, shippingAmount) {
            let deliveryLinePrice = 0;
                let deliveryRetailPrice = deliveryLine.deliveryRetailPrice;
                if (typeof deliveryRetailPrice == 'string') {
                    deliveryLinePrice = parseFloat(deliveryRetailPrice.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                } else {
                    deliveryLinePrice = parseFloat(deliveryRetailPrice);
                }

            shippingAmount = deliveryLinePrice;
            return shippingAmount;
        },

        getGrossAmount: function(productLine, grossAmount) {
            let productLinePrice = 0;
                let productRetailPrice = productLine.productRetailPrice;
                if (typeof productRetailPrice == 'string') {
                    productLinePrice = parseFloat(productRetailPrice.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                } else {
                    productLinePrice = parseFloat(productRetailPrice);
                }
            grossAmount = productLinePrice;
            return grossAmount;
        },

        getTotalDiscountAmount: function(rateDetail, totalDiscountAmount, promoDiscountPrice, accountDiscountPrice, volumeDiscountPrice, bundleDiscountPrice, discountResult) {
            if(window.checkout.mazegeek_b2352379_discount_breakdown === true || window.checkoutConfig.mazegeek_b2352379_discount_breakdown === true) {
                totalDiscountAmount = rateDetail.totalDiscountAmount;
                (rateDetail?.productLines || []).forEach((discount) => {
                    const instanceId = discount.instanceId;
                    discount = discount.productLineDiscounts?.[0];
                    if (typeof discount !== 'undefined') {
                        if (discount.type == 'AR_CUSTOMERS' || discount.type == 'CORPORATE') {
                            let accountDiscountAmount = discount.amount;
                            if (typeof accountDiscountAmount == 'string') {
                                accountDiscountPrice += parseFloat(accountDiscountAmount.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                            } else {
                                accountDiscountPrice += parseFloat(accountDiscountAmount);
                            }
                        }
                        if (discount.type == 'QUANTITY') {
                            if(togglesAndSettings.isToggleEnabled('tiger_e468338') && this.isBundleChildren(instanceId)){
                                let bundleDiscountAmount = discount.amount;
                                if (typeof bundleDiscountAmount == 'string') {
                                    bundleDiscountPrice += parseFloat(bundleDiscountAmount.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                                } else {
                                    bundleDiscountPrice += parseFloat(bundleDiscountAmount);
                                }
                            }
                            else {
                                let volumeDiscountAmount = discount.amount;
                                if (typeof volumeDiscountAmount == 'string') {
                                    volumeDiscountPrice += parseFloat(volumeDiscountAmount.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                                } else {
                                    volumeDiscountPrice += parseFloat(volumeDiscountAmount);
                                }
                            }
                        }
                        if (discount.type == 'COUPON') {
                            let promoDiscountAmount = discount.amount;
                            if (typeof promoDiscountAmount == 'string') {
                                promoDiscountPrice += parseFloat(promoDiscountAmount.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                            } else {
                                promoDiscountPrice += parseFloat(promoDiscountAmount);
                            }
                        }
                    }
                });
            } else {
                rateDetail.discounts.forEach((discount) => {
                    let totalDiscountPrice = 0;
                    if (typeof discount.amount !== 'undefined') {
                        let totalDiscountAmountMain = discount.amount;
                        if (typeof totalDiscountAmountMain == 'string') {
                            totalDiscountPrice = parseFloat(totalDiscountAmountMain.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                        } else {
                            totalDiscountPrice = parseFloat(totalDiscountAmountMain);
                        }
                        totalDiscountAmount += totalDiscountPrice;
                        if (discount.type == 'AR_CUSTOMERS' || discount.type == 'CORPORATE') {
                            let accountDiscountAmount = discount.amount;
                            if (typeof accountDiscountAmount == 'string') {
                                accountDiscountPrice += parseFloat(accountDiscountAmount.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                            } else {
                                accountDiscountPrice += parseFloat(accountDiscountAmount);
                            }
                        }
                        if (discount.type == 'QUANTITY') {
                            let volumeDiscountAmount = discount.amount;
                            if (typeof volumeDiscountAmount == 'string') {
                                volumeDiscountPrice += parseFloat(volumeDiscountAmount.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                            } else {
                                volumeDiscountPrice += parseFloat(volumeDiscountAmount);
                            }
                        }
                        if (discount.type == 'COUPON') {
                            let promoDiscountAmount = discount.amount;
                            if (typeof promoDiscountAmount == 'string') {
                                promoDiscountPrice += parseFloat(promoDiscountAmount.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                            } else {
                                promoDiscountPrice += parseFloat(promoDiscountAmount);
                            }
                        }
                    }
                });
            }
            discountResult['totalDiscountAmount'] = totalDiscountAmount;
            discountResult['promoDiscountAmount'] = promoDiscountPrice;
            discountResult['accountDiscountAmount'] = accountDiscountPrice;
            discountResult['volumeDiscountAmount'] = volumeDiscountPrice;
            discountResult['bundleDiscountAmount'] = bundleDiscountPrice;
            return discountResult;
        },
        isBundleChildren: function (instanceId) {
            const cartData = customerData.get('cart')();

            if (!cartData || !cartData.items || !instanceId) {
                return false;
            }

            return cartData.items.some(item =>
                item.product_type === 'bundle' &&
                item.childrenItemsIds &&
                item.childrenItemsIds.includes(instanceId)
            );
        },
        getTotalNetAmount: function (rateDetail, totalNetAmount, estimatedShippingTotal,netAmountResult) {
            let rateDetailTotalAmount = 0;
                rateDetailTotalAmount = rateDetail.totalAmount;
                if (typeof rateDetailTotalAmount == 'string') {
                    rateDetailTotalAmount = parseFloat(rateDetailTotalAmount.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                } else {
                    rateDetailTotalAmount = parseFloat(rateDetailTotalAmount);
                }

            if (typeof rateDetail.estimatedVsActual != "undefined" && rateDetail.estimatedVsActual == 'ESTIMATED') {
                estimatedShippingTotal = rateDetailTotalAmount;
            } else {
                totalNetAmount += rateDetailTotalAmount;
            }
            netAmountResult['totalNetAmount'] = totalNetAmount;
            netAmountResult['estimatedShippingTotal'] = estimatedShippingTotal;
            return netAmountResult;
        },

        getTotalAmount: function (rateDetail, totalNetAmount) {
                if (typeof rateDetail.estimatedVsActual != "undefined" && rateDetail.estimatedVsActual == 'ACTUAL') {
                    let totalAmount = rateDetail.totalAmount;
                    if (typeof totalAmount == 'string') {
                        totalNetAmount += parseFloat(totalAmount.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                    } else {
                        totalNetAmount += parseFloat(totalAmount);
                    }
                }
            return totalNetAmount;
        }
    };
});
