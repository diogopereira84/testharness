/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define(
    [
        'jquery',
        'fedex/storage'
    ], function (
        $,fxoStorage
    ) {
        'use strict';

        return function (target) {
            return target.extend({
                /**
                 * Check current delivery method is shipping or not
                 *
                 * @return Boolean
                 */
                isShip: function () {
                    let isShipKey = true;
                    if(window.e383157Toggle){
                        if (fxoStorage.get("shipkey") == 'false') {
                            isShipKey = false;
                        }
                    }else{
                        if (localStorage.getItem("shipkey") == 'false') {
                            isShipKey = false;
                        }
                    }
                    return isShipKey;
                }
            });
        }
    }
);
