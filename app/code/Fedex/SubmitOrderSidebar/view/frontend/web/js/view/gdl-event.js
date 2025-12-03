/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 define([
    'jquery',
    'fedex/storage'
], function ($,fxoStorage) {
    'use strict';

    /**
     * Append GDL script in head
     *
     * @return bool
     */
    function appendGDLScript(orderSalePrice, orderNumber = null) {
        let gdlEventScript = null;
        if (orderSalePrice) {
            let isEpro = isEproSuccessPage();
            if (isEpro) {
                gdlEventScript = getGDLEvent62(orderSalePrice);
            } else {
                gdlEventScript = getGDLEvent38(orderSalePrice, orderNumber);
            }
            let isEventAdded;
            if(window.e383157Toggle){
                isEventAdded = fxoStorage.get("gdl-event-added");
            }else{
                isEventAdded = localStorage.getItem("gdl-event-added");
            }
            if (!isEventAdded) {
                $('head').append("<script>" + gdlEventScript + "</script>");
                if(window.e383157Toggle){
                    fxoStorage.set("gdl-event-added", true);
                }else{
                    localStorage.setItem("gdl-event-added", true);
                }
            }
        }
    }

    /**
     * Identify Epro and Success page
     *
     * @return bool
     */
    function isEproSuccessPage() {
        if (typeof (window.checkoutConfig) !== "undefined" && window.checkoutConfig !== null) {
            if (typeof (window.checkoutConfig.is_epro) !== "undefined" && window.checkoutConfig.is_epro !== null) {
                return window.checkoutConfig.is_epro;
            }
        }
        return false;
    }

    /**
     * Get GDL Event config load
     *
     * @return string
     */
    function getGDLEventConfigLoad() {
        return "window.FDX.GDL.push([ 'config:load', [ 'fxo' ]]);";
    }

    /**
     * GDL Event 38 and 14 for Retail and SDE
     *
     * @param {float} orderSalePrice
     * @param orderNumber
     * @return string
     */
    function getGDLEvent38(orderSalePrice, orderNumber = null) {
        let gdlEvent38 = null;
        gdlEvent38 = getGDLEventConfigLoad();
        gdlEvent38 = gdlEvent38 + "window.FDX.GDL.push(['event:publish', ['fxo', 'order-confirm-pol', {saleAmount: " + orderSalePrice + ", purchaseId: '" + orderNumber + "'}]]);";

        return gdlEvent38;
    }

    /**
     * GDL Event 62 for EPRO
     *
     * @param {float} orderSalePrice
     * @return bool
     */
     function getGDLEvent62(orderSalePrice) {
        let gdlEvent62 = null;
        gdlEvent62 = getGDLEventConfigLoad();
        gdlEvent62 = gdlEvent62 + "window.FDX.GDL.push(['event:publish', ['fxo', 'order-confirm-corporate', {saleAmount: " + orderSalePrice + ", purchaseId: 'EPRO-QUOTE'}]]);";

        return gdlEvent62;
    }

    return {
        appendGDLScript: appendGDLScript
    }
});
