define([
        'jquery',
        'underscore',
        'fedex/storage',
        'domReady!'
    ], function($, _, fxoStorage) {
        $(document).ready( function() {
            let orderConfirmationResponse;
            if(window.e383157Toggle){
                orderConfirmationResponse = fxoStorage.get('orderTransactionData');
                if(typeof orderConfirmationResponse === 'string'){
                    orderConfirmationResponse = JSON.parse(orderConfirmationResponse);
                }
            }else{
                orderConfirmationResponse = JSON.parse(JSON.parse(localStorage.getItem('orderTransactionData')));
            }
            if (typeof orderConfirmationResponse === 'object') {
                (function(config){
                    (function (info) {
                        info.PageView = function () {
                            const firstName = _.get(
                                orderConfirmationResponse,
                                ['output', 'checkout', 'contact', 'personName', 'firstName'],
                                ''
                            );
                            const lastName = _.get(
                                orderConfirmationResponse,
                                ['output', 'checkout', 'contact', 'personName', 'lastName'],
                                ''
                            );
                            return {
                                userData: {
                                    customerName: firstName + ' ' + lastName,
                                    orderGTN: _.get(
                                        orderConfirmationResponse,
                                        [
                                            'output',
                                            'checkout',
                                            'lineItems',
                                            '0',
                                            'retailPrintOrderDetails',
                                            '0',
                                            'origin',
                                            'orderNumber'
                                        ],
                                        ''
                                    ),
                                    totalAmount: _.get(
                                        orderConfirmationResponse,
                                        ['output', 'checkout', 'transactionTotals', 'totalAmount'],
                                        ''
                                    ),
                                }
                            }
                        }
                    })(config.userEventInfo || (config.userEventInfo = {}))
                })(window["adrum-config"] || (window["adrum-config"] = {}));
            }
        });
    }
);
