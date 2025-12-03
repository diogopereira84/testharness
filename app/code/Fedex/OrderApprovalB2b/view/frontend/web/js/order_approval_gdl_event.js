/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery'
], function($) {
    'use strict';

    /**
     * Append GDL script in head
     *
     * @return bool
     */
    function appendGDLScript(orderSalePrice) {
        let gdlEventScript = null;
        if (orderSalePrice) {
            gdlEventScript = getGDLEvent62(orderSalePrice);
            $('head').append("<script>" + gdlEventScript + "</script>");
        }
    }

    /**
     * GDL Event 62
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

    /**
     * Get GDL Event config load
     *
     * @return string
     */
    function getGDLEventConfigLoad() {
        return "window.FDX.GDL.push([ 'config:load', [ 'fxo' ]]);";
    }

    return {
        appendGDLScript: appendGDLScript
    }
});
    
