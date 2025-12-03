<?php
/**
 * @category    Fedex
 * @package     Fedex_SubmitOrderSidebar
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\Registry;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order as OrderModel;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Fedex\SubmitOrderSidebar\Helper\SubmitOrderOptimizedHelper;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\SubmitOrderSidebar\Model\TransactionApi\RateQuoteAndTransactionApiHandler;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\DataObjectFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Fedex\CoreApi\Model\LogHelperApi;

class SubmitOrderApiTest extends TestCase
{
    /**
     * @var (\Fedex\GraphQl\Model\NewRelicHeaders & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $newRelicHeaders;

    /**
     * @var (\Fedex\CartGraphQl\Helper\LoggerHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $loggerHelperMock;
    protected $toggleConfigMock;
    protected $quote;
    protected $orderPaymentInterface;
    /**
     * @var (\Magento\Quote\Model\Quote\Address & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $addressMock;
    protected $addressInterfaceMock;
    protected $dataObjectFactory;
    protected $orderApprovalViewModel;
    protected $checkoutSessionMock;
    protected $submitOrderApiMock;
    public const ORDER_ID = 12;
    public const QUOTE_ID = 10;

    /**
     * @var SubmitOrderHelper|MockObject
     */
    protected SubmitOrderHelper|MockObject $submitOrderHelperMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected LoggerInterface|MockObject $loggerMock;

    /**
     * @var SubmitOrderOptimizedHelper|MockObject
     */
    protected SubmitOrderOptimizedHelper|MockObject $submitOrderOptimizedHelperMock;

    /**
     * @var OrderModel|MockObject
     */
    protected OrderModel|MockObject $orderModelMock;

    /**
     * @var Registry|MockObject
     */
    protected Registry|MockObject $registryMock;

    /**
     * @var RateQuoteAndTransactionApiHandler|MockObject
     */
    protected $apiHandlerMock;

    /**
     * @var RequestQueryValidator|MockObject
     */
    private RequestQueryValidator|MockObject $requestQueryValidatorMock;

    /**
     * @var InstoreConfig|MockObject
     */
    private InstoreConfig|MockObject $instoreConfigMock;

    /**
     * @var LogHelperApi|MockObject
     */
    private LogHelperApi|MockObject $logHelperApi;

    /**
     * @var string
     */
    protected $rateQuoteMockResponse = '{
        "transactionId":"d00b7d66-e169-4ad2-9d01-12c17ab604a2",
        "errors":[],
        "output":{
            "alerts":[],
            "rateQuote":{
                "currency":"USD",
                "rateQuoteDetails":[{
                    "grossAmount":45.45,
                    "totalDiscountAmount":12.25,
                    "netAmount":33.20,
                    "taxableAmount":33.20,
                    "taxAmount":1.88,
                    "totalAmount":35.08,
                    "estimatedVsActual":"ACTUAL",
                    "productLines":[{
                        "instanceId":"0",
                        "productId":"1463680545590",
                        "unitQuantity":50,
                        "priceable":true,
                        "unitOfMeasurement":"EACH",
                        "productRetailPrice":34.99,
                        "productDiscountAmount":12.25,
                        "productLinePrice":22.74,
                        "productLineDiscounts":[{
                            "type":"AR_CUSTOMERS",
                            "amount":12.25
                        }],
                        "productLineDetails":[{
                            "detailCode":"40005",
                            "priceRequired":false,
                            "priceOverridable":false,
                            "description":"Full Pg Clr Flyr 50",
                            "unitQuantity":1,
                            "quantity":1,
                            "detailPrice":22.74,
                            "detailDiscountPrice":12.25,
                            "detailUnitPrice":34.990000,
                            "detailDiscountedUnitPrice":12.25,
                            "detailDiscounts":[{
                                "type":"AR_CUSTOMERS",
                                "amount":12.2465
                            }],
                            "detailCategory":"PRINTING"
                        }],
                        "name":"Fast Order Flyer",
                        "userProductName":"Fast Order Flyer",
                        "type":"PRINT_ORDER"
                    }],
                    "deliveryLines":[
                        {
                            "recipientReference":"1",
                            "priceable":true,
                            "deliveryLinePrice":0,
                            "deliveryRetailPrice":0,
                            "deliveryLineType":"PACKING_AND_HANDLING",
                            "deliveryDiscountAmount":0
                        },
                        {
                            "recipientReference":"1",
                            "estimatedDeliveryLocalTime":"2021-09-17",
                            "estimatedShipDate":"2022-07-11",
                            "priceable":false,
                            "deliveryLinePrice":10.46,
                            "deliveryRetailPrice":10.46,
                            "deliveryLineType":"SHIPPING",
                            "deliveryDiscountAmount":0.0,
                            "recipientContact":{
                                "personName":{
                                    "firstName":"Nandhu",
                                    "lastName":"V Nair"
                                },
                                "company":{
                                    "name":"FXO"
                                },
                                "emailDetail":{
                                    "emailAddress":"nandhu.nair@igglobal.com"
                                },
                                "phoneNumberDetails":[{
                                    "phoneNumber":{
                                        "number":"8986776897"
                                    },
                                    "usage":"PRIMARY"
                                }]
                            },
                            "shipmentDetails":{
                                "address":{
                                    "streetLines":[
                                        "7900 Legacy Dr",
                                        null
                                    ],
                                    "city":"Plano",
                                    "stateOrProvinceCode":"75024",
                                    "postalCode":"75024",
                                    "countryCode":"US"
                                }
                            }
                        }
                    ],
                    "discounts":[{
                        "type":"AR_CUSTOMERS",
                        "amount":12.25
                    }],
                    "rateQuoteId":"eyJxdW90ZUl"
                }]
            }
        }
    }';

    protected $checkoutResponse = '{
        "transactionId": "0090062b-4066-4a1f-abd4-6b6e8759a7f1",
        "output": {
            "checkout": {
                "transactionHeader": {
                    "guid": "2c140ead-2974-4eef-ad44-ef47f7b93864",
                    "type": "SALE",
                    "requestDateTime": "2021-09-28 15:50:57",
                    "transactionDateTime": "2021-09-28T10:20:58Z",
                    "retailTransactionId": "JMDKN43E63F7121104X",
                    "fedExCartId": "797c4439-4ce2-45f6-9d86-88345669f5c5",
                    "rateQuoteId": "eyJxdW90ZUlkIjoiMzFhY2U1MTktNmNi"
                },
                "lineItems": [{
                    "type": "PRINT_PRODUCT",
                    "retailPrintOrderDetails": [{
                        "customerNotificationEnabled": false,
                        "orderContact": {
                            "contact": {
                                "personName": {
                                    "firstName": "Ravi Kant",
                                    "lastName": "Kumar"
                                },
                                "company": {
                                    "name": "FXO"
                                },
                                "emailDetail": {
                                    "emailAddress": "ravi5.kumar@infogain.com"
                                },
                                "phoneNumberDetails": [{
                                    "phoneNumber": {
                                        "number": "1234567890"
                                    },
                                    "usage": "PRIMARY"
                                }]
                            }
                        },
                        "responsibleCenterDetail": [{
                            "locationId": "DNEK",
                            "address": {
                                "streetLines": [
                                    "8290 State Highway 121"
                                ],
                                "city": "Frisco",
                                "stateOrProvinceCode": "TX",
                                "postalCode": "75034",
                                "countryCode": "US"
                            },
                            "emailDetail": {
                                "emailAddress": "BLANK@USI.NET"
                            },
                            "phoneNumberDetails": [{
                                "phoneNumber": {
                                    "number": "972.731.0997"
                                }
                            }]
                        }],
                        "productLines": [{
                            "instanceId": "0",
                            "productId": "1463680545590",
                            "unitQuantity": 50,
                            "unitOfMeasurement": "EACH",
                            "productRetailPrice": "11.09",
                            "productDiscountAmount": "0.0",
                            "productLinePrice": "11.09",
                            "productLineDetails": [{
                                "instanceId": "1",
                                "detailCode": "40005",
                                "description": "Full Pg Clr Flyr 50",
                                "priceRequired": false,
                                "priceOverridable": false,
                                "unitQuantity": 1,
                                "quantity": 1,
                                "detailPrice": "11.09",
                                "detailDiscountPrice": "0.0",
                                "detailUnitPrice": "11.093600",
                                "detailDiscountedUnitPrice": "11.093600"
                            }],
                            "name": "Fast Order Flyer",
                            "userProductName": "Flyers",
                            "type": "PRINT_PRODUCT",
                            "priceable": true
                        }],
                        "deliveryLines": [{
                            "deliveryLineId": "2810",
                            "recipientReference": "2810",
                            "deliveryLinePrice": "11.09",
                            "deliveryRetailPrice": "11.09",
                            "deliveryLineType": "PICKUP",
                            "deliveryDiscountAmount": "0.0",
                            "recipientContact": {
                                "personName": {
                                    "firstName": "Ravi Kant",
                                    "lastName": "Kumar"
                                },
                                "company": {
                                    "name": "FXO"
                                },
                                "emailDetail": {
                                    "emailAddress": "ravi5.kumar@infogain.com"
                                },
                                "phoneNumberDetails": [{
                                    "phoneNumber": {
                                        "number": "1234567890"
                                    },
                                    "usage": "PRIMARY"
                                }]
                            },
                            "pickupDetails": {
                                "locationName": "0798",
                                "requestedPickupLocalTime": "2021-09-28T15:00:00"
                            },
                            "productAssociation": [{
                                "productRef": "0",
                                "quantity": "50.0"
                            }],
                            "productTotals": {
                                "productDiscountAmount": "0.0",
                                "productNetAmount": "11.09",
                                "productTaxableAmount": "11.09",
                                "productTaxAmount": "0.91",
                                "productTotalAmount": "12.00"
                            }
                        }],
                        "orderTotalDiscountAmount": "0.0",
                        "orderGrossAmount": "11.09",
                        "orderNonTaxableAmount": "0.00",
                        "orderTaxExemptableAmount": "0.00",
                        "orderNetAmount": "11.09",
                        "orderTaxableAmount": "11.09",
                        "orderTaxAmount": "0.91",
                        "orderTotalAmount": "12.00",
                        "notificationRegistration": {
                            "webhook": {
                                "url": "https://staging3.office.fedex.com/"
                            }
                        },
                        "origin": {
                            "orderNumber": "2010197609025497",
                            "orderClient": "MAGENTO",
                            "apiCustomer": "l7e4acbdd6b7d341b0b59234bbdbd4e82e"
                        }
                    }]
                }],
                "contact": {
                    "personName": {
                        "firstName": "Ravi Kant",
                        "lastName": "Kumar"
                    },
                    "company": {
                        "name": "FXO"
                    },
                    "emailDetail": {
                        "emailAddress": "ravi5.kumar@infogain.com"
                    },
                    "phoneNumberDetails": [{
                        "phoneNumber": {
                            "number": "1234567890"
                        },
                        "usage": "PRIMARY"
                    }]
                },
                "tenders": [{
                    "id": "1",
                    "paymentType": "CREDIT_CARD",
                    "requestedAmount": "12.0",
                    "tenderedAmount": "12.00",
                    "balanceDueAmount": "0.00",
                    "creditCard": {
                        "type": "VISA",
                        "maskedAccountNumber": "411111xxxxxx1111",
                        "authResponse": "APPROVED",
                        "accountLast4Digits": "xxxxxxxxxxxx1111"
                    },
                    "currency": "USD"
                }],
                "transactionTotals": {
                    "currency": "USD",
                    "grossAmount": "11.09",
                    "totalDiscountAmount": "0.0",
                    "netAmount": "11.09",
                    "taxAmount": "0.91",
                    "totalAmount": "12.00"
                }
            }
        }
    }';

    protected $checkoutResponseArray = [
        0 => '{
            "transactionId": "0090062b-4066-4a1f-abd4-6b6e8759a7f1",
            "output": {
                "checkout": {
                    "transactionHeader": {
                        "guid": "2c140ead-2974-4eef-ad44-ef47f7b93864",
                        "type": "SALE",
                        "requestDateTime": "2021-09-28 15:50:57",
                        "transactionDateTime": "2021-09-28T10:20:58Z",
                        "retailTransactionId": "JMDKN43E63F7121104X",
                        "fedExCartId": "797c4439-4ce2-45f6-9d86-88345669f5c5",
                        "rateQuoteId": "eyJxdW90ZUlkIjoiMzFhY2U1MTktNmNi"
                    },
                    "lineItems": [{
                        "type": "PRINT_PRODUCT",
                        "retailPrintOrderDetails": [{
                            "customerNotificationEnabled": false,
                            "orderContact": {
                                "contact": {
                                    "personName": {
                                        "firstName": "Ravi Kant",
                                        "lastName": "Kumar"
                                    },
                                    "company": {
                                        "name": "FXO"
                                    },
                                    "emailDetail": {
                                        "emailAddress": "ravi5.kumar@infogain.com"
                                    },
                                    "phoneNumberDetails": [{
                                        "phoneNumber": {
                                            "number": "1234567890"
                                        },
                                        "usage": "PRIMARY"
                                    }]
                                }
                            },
                            "responsibleCenterDetail": [{
                                "locationId": "DNEK",
                                "address": {
                                    "streetLines": [
                                        "8290 State Highway 121"
                                    ],
                                    "city": "Frisco",
                                    "stateOrProvinceCode": "TX",
                                    "postalCode": "75034",
                                    "countryCode": "US"
                                },
                                "emailDetail": {
                                    "emailAddress": "BLANK@USI.NET"
                                },
                                "phoneNumberDetails": [{
                                    "phoneNumber": {
                                        "number": "972.731.0997"
                                    }
                                }]
                            }],
                            "productLines": [{
                                "instanceId": "0",
                                "productId": "1463680545590",
                                "unitQuantity": 50,
                                "unitOfMeasurement": "EACH",
                                "productRetailPrice": "11.09",
                                "productDiscountAmount": "0.0",
                                "productLinePrice": "11.09",
                                "productLineDetails": [{
                                    "instanceId": "1",
                                    "detailCode": "40005",
                                    "description": "Full Pg Clr Flyr 50",
                                    "priceRequired": false,
                                    "priceOverridable": false,
                                    "unitQuantity": 1,
                                    "quantity": 1,
                                    "detailPrice": "11.09",
                                    "detailDiscountPrice": "0.0",
                                    "detailUnitPrice": "11.093600",
                                    "detailDiscountedUnitPrice": "11.093600"
                                }],
                                "name": "Fast Order Flyer",
                                "userProductName": "Flyers",
                                "type": "PRINT_PRODUCT",
                                "priceable": true
                            }],
                            "deliveryLines": [{
                                "deliveryLineId": "2810",
                                "recipientReference": "2810",
                                "deliveryLinePrice": "11.09",
                                "deliveryRetailPrice": "11.09",
                                "deliveryLineType": "PICKUP",
                                "deliveryDiscountAmount": "0.0",
                                "recipientContact": {
                                    "personName": {
                                        "firstName": "Ravi Kant",
                                        "lastName": "Kumar"
                                    },
                                    "company": {
                                        "name": "FXO"
                                    },
                                    "emailDetail": {
                                        "emailAddress": "ravi5.kumar@infogain.com"
                                    },
                                    "phoneNumberDetails": [{
                                        "phoneNumber": {
                                            "number": "1234567890"
                                        },
                                        "usage": "PRIMARY"
                                    }]
                                },
                                "pickupDetails": {
                                    "locationName": "0798",
                                    "requestedPickupLocalTime": "2021-09-28T15:00:00"
                                },
                                "productAssociation": [{
                                    "productRef": "0",
                                    "quantity": "50.0"
                                }],
                                "productTotals": {
                                    "productDiscountAmount": "0.0",
                                    "productNetAmount": "11.09",
                                    "productTaxableAmount": "11.09",
                                    "productTaxAmount": "0.91",
                                    "productTotalAmount": "12.00"
                                }
                            }],
                            "orderTotalDiscountAmount": "0.0",
                            "orderGrossAmount": "11.09",
                            "orderNonTaxableAmount": "0.00",
                            "orderTaxExemptableAmount": "0.00",
                            "orderNetAmount": "11.09",
                            "orderTaxableAmount": "11.09",
                            "orderTaxAmount": "0.91",
                            "orderTotalAmount": "12.00",
                            "notificationRegistration": {
                                "webhook": {
                                    "url": "https://staging3.office.fedex.com/"
                                }
                            },
                            "origin": {
                                "orderNumber": "2010197609025497",
                                "orderClient": "MAGENTO",
                                "apiCustomer": "l7e4acbdd6b7d341b0b59234bbdbd4e82e"
                            }
                        }]
                    }],
                    "contact": {
                        "personName": {
                            "firstName": "Ravi Kant",
                            "lastName": "Kumar"
                        },
                        "company": {
                            "name": "FXO"
                        },
                        "emailDetail": {
                            "emailAddress": "ravi5.kumar@infogain.com"
                        },
                        "phoneNumberDetails": [{
                            "phoneNumber": {
                                "number": "1234567890"
                            },
                            "usage": "PRIMARY"
                        }]
                    },
                    "tenders": [{
                        "id": "1",
                        "paymentType": "CREDIT_CARD",
                        "requestedAmount": "12.0",
                        "tenderedAmount": "12.00",
                        "balanceDueAmount": "0.00",
                        "creditCard": {
                            "type": "VISA",
                            "maskedAccountNumber": "411111xxxxxx1111",
                            "authResponse": "APPROVED",
                            "accountLast4Digits": "xxxxxxxxxxxx1111"
                        },
                        "currency": "USD"
                    }],
                    "transactionTotals": {
                        "currency": "USD",
                        "grossAmount": "11.09",
                        "totalDiscountAmount": "0.0",
                        "netAmount": "11.09",
                        "taxAmount": "0.91",
                        "totalAmount": "12.00"
                    }
                }
            }
        }'
    ];

    protected $checkoutResponseWithAlertArray = [
        0 => '{
            "transactionId": "0090062b-4066-4a1f-abd4-6b6e8759a7f1",
            "errors": "error",
            "output": {
                "alerts": [test],
                "checkout": {
                    "transactionHeader": {
                        "guid": "2c140ead-2974-4eef-ad44-ef47f7b93864",
                        "type": "SALE",
                        "requestDateTime": "2021-09-28 15:50:57",
                        "transactionDateTime": "2021-09-28T10:20:58Z",
                        "retailTransactionId": "JMDKN43E63F7121104X",
                        "fedExCartId": "797c4439-4ce2-45f6-9d86-88345669f5c5",
                        "rateQuoteId": "eyJxdW90ZUlkIjoiMzFhY2U1MTktNmNi"
                    },
                    "lineItems": [{
                        "type": "PRINT_PRODUCT",
                        "retailPrintOrderDetails": [{
                            "customerNotificationEnabled": false,
                            "orderContact": {
                                "contact": {
                                    "personName": {
                                        "firstName": "Ravi Kant",
                                        "lastName": "Kumar"
                                    },
                                    "company": {
                                        "name": "FXO"
                                    },
                                    "emailDetail": {
                                        "emailAddress": "ravi5.kumar@infogain.com"
                                    },
                                    "phoneNumberDetails": [{
                                        "phoneNumber": {
                                            "number": "1234567890"
                                        },
                                        "usage": "PRIMARY"
                                    }]
                                }
                            },
                            "responsibleCenterDetail": [{
                                "locationId": "DNEK",
                                "address": {
                                    "streetLines": [
                                        "8290 State Highway 121"
                                    ],
                                    "city": "Frisco",
                                    "stateOrProvinceCode": "TX",
                                    "postalCode": "75034",
                                    "countryCode": "US"
                                },
                                "emailDetail": {
                                    "emailAddress": "BLANK@USI.NET"
                                },
                                "phoneNumberDetails": [{
                                    "phoneNumber": {
                                        "number": "972.731.0997"
                                    }
                                }]
                            }],
                            "productLines": [{
                                "instanceId": "0",
                                "productId": "1463680545590",
                                "unitQuantity": 50,
                                "unitOfMeasurement": "EACH",
                                "productRetailPrice": "11.09",
                                "productDiscountAmount": "0.0",
                                "productLinePrice": "11.09",
                                "productLineDetails": [{
                                    "instanceId": "1",
                                    "detailCode": "40005",
                                    "description": "Full Pg Clr Flyr 50",
                                    "priceRequired": false,
                                    "priceOverridable": false,
                                    "unitQuantity": 1,
                                    "quantity": 1,
                                    "detailPrice": "11.09",
                                    "detailDiscountPrice": "0.0",
                                    "detailUnitPrice": "11.093600",
                                    "detailDiscountedUnitPrice": "11.093600"
                                }],
                                "name": "Fast Order Flyer",
                                "userProductName": "Flyers",
                                "type": "PRINT_PRODUCT",
                                "priceable": true
                            }],
                            "deliveryLines": [{
                                "deliveryLineId": "2810",
                                "recipientReference": "2810",
                                "deliveryLinePrice": "11.09",
                                "deliveryRetailPrice": "11.09",
                                "deliveryLineType": "PICKUP",
                                "deliveryDiscountAmount": "0.0",
                                "recipientContact": {
                                    "personName": {
                                        "firstName": "Ravi Kant",
                                        "lastName": "Kumar"
                                    },
                                    "company": {
                                        "name": "FXO"
                                    },
                                    "emailDetail": {
                                        "emailAddress": "ravi5.kumar@infogain.com"
                                    },
                                    "phoneNumberDetails": [{
                                        "phoneNumber": {
                                            "number": "1234567890"
                                        },
                                        "usage": "PRIMARY"
                                    }]
                                },
                                "pickupDetails": {
                                    "locationName": "0798",
                                    "requestedPickupLocalTime": "2021-09-28T15:00:00"
                                },
                                "productAssociation": [{
                                    "productRef": "0",
                                    "quantity": "50.0"
                                }],
                                "productTotals": {
                                    "productDiscountAmount": "0.0",
                                    "productNetAmount": "11.09",
                                    "productTaxableAmount": "11.09",
                                    "productTaxAmount": "0.91",
                                    "productTotalAmount": "12.00"
                                }
                            }],
                            "orderTotalDiscountAmount": "0.0",
                            "orderGrossAmount": "11.09",
                            "orderNonTaxableAmount": "0.00",
                            "orderTaxExemptableAmount": "0.00",
                            "orderNetAmount": "11.09",
                            "orderTaxableAmount": "11.09",
                            "orderTaxAmount": "0.91",
                            "orderTotalAmount": "12.00",
                            "notificationRegistration": {
                                "webhook": {
                                    "url": "https://staging3.office.fedex.com/"
                                }
                            },
                            "origin": {
                                "orderNumber": "2010197609025497",
                                "orderClient": "MAGENTO",
                                "apiCustomer": "l7e4acbdd6b7d341b0b59234bbdbd4e82e"
                            }
                        }]
                    }],
                    "contact": {
                        "personName": {
                            "firstName": "Ravi Kant",
                            "lastName": "Kumar"
                        },
                        "company": {
                            "name": "FXO"
                        },
                        "emailDetail": {
                            "emailAddress": "ravi5.kumar@infogain.com"
                        },
                        "phoneNumberDetails": [{
                            "phoneNumber": {
                                "number": "1234567890"
                            },
                            "usage": "PRIMARY"
                        }]
                    },
                    "tenders": [{
                        "id": "1",
                        "paymentType": "CREDIT_CARD",
                        "requestedAmount": "12.0",
                        "tenderedAmount": "12.00",
                        "balanceDueAmount": "0.00",
                        "creditCard": {
                            "type": "VISA",
                            "maskedAccountNumber": "411111xxxxxx1111",
                            "authResponse": "APPROVED",
                            "accountLast4Digits": "xxxxxxxxxxxx1111"
                        },
                        "currency": "USD"
                    }],
                    "transactionTotals": {
                        "currency": "USD",
                        "grossAmount": "11.09",
                        "totalDiscountAmount": "0.0",
                        "netAmount": "11.09",
                        "taxAmount": "0.91",
                        "totalAmount": "12.00"
                    }
                }
            }
        }'
    ];

    protected $rateQuoteRequestArray = [
        'rateQuoteRequest' => [
            'sourceRetailLocationId' => null,
            'previousQuoteId' => null,
            'action' => 'SAVE_COMMIT',
            'retailPrintOrder' => [
                'fedExAccountNumber' => "",
                'origin' => [
                    'orderNumber' => "24823482348",
                    'orderClient' => 'MAGENTO',
                    'site' => "https://fedex.com",
                ],
                'orderContact' => [
                    'contact' => [
                        'contactId' => null,
                        'personName' => [
                            'firstName' => "Yogesh",
                            'lastName' => "Suryawanshi",
                        ],
                        'company' => [
                            'name' => 'FXO',
                        ],
                        'emailDetail' => [
                            'emailAddress' => "yogesh.suryawanshi@igglobal.com",
                        ],
                        'phoneNumberDetails' => [
                            0 => [
                                'phoneNumber' => [
                                    'number' => "956965656565",
                                    'extension' => null,
                                ],
                                'usage' => 'PRIMARY',
                            ],
                        ],
                    ],
                ],
                'customerNotificationEnabled' => false,
                'notificationRegistration' => [
                    'webhook' => [
                        'url' => "https://fedex.com",
                        'auth' => null,
                    ],
                ],
                'profileAccountId' => null,
                'expirationDays' => '30',
                'products' => "test",
                'recipients' => [
                    0 => [
                        'reference' => "8234982348",
                        'contact' => [
                            'contactId' => null,
                            'personName' => [
                                'firstName' => "Yogesh",
                                'lastName' => "Suryawanshi",
                            ],
                            'company' => [
                                'name' => 'FXO',
                            ],
                            'emailDetail' => [
                                'emailAddress' => "yogesh.suryawanshi",
                            ],
                            'phoneNumberDetails' => [
                                0 => [
                                    'phoneNumber' => [
                                        'number' => "963254254555",
                                        'extension' => null,
                                    ],
                                    'usage' => 'PRIMARY',
                                ],
                            ],
                        ],
                        'shipmentDelivery' => [
                            'address' => [
                                'streetLines' => "234",
                                'city' => "Plano",
                                'stateOrProvinceCode' => "Tx",
                                'postalCode' => "75024",
                                'countryCode' => 'US',
                                'addressClassification' => "Home",
                            ],
                            'holdUntilDate' => null,
                            'serviceType' => "Shipment",
                            'fedExAccountNumber' => "",
                            'deliveryInstructions' => null,
                            'poNumber' => null,
                        ],
                        'productAssociations' => "5545",
                    ],
                ],
            ],
            'coupons' => "MGT01",
            'teamMemberId' => null,
        ],
    ];

    protected $productLines = '{
        "instanceId": "0",
        "productId": "1463680545590",
        "unitQuantity": 50,
        "unitOfMeasurement": "EACH",
        "productRetailPrice": "11.09",
        "productDiscountAmount": "0.0",
        "productLinePrice": "11.09",
        "productLineDetails": [{
            "instanceId": "1",
            "detailCode": "40005",
            "description": "Full Pg Clr Flyr 50",
            "priceRequired": false,
            "priceOverridable": false,
            "unitQuantity": 1,
            "quantity": 1,
            "detailPrice": "11.09",
            "detailDiscountPrice": "0.0",
            "detailUnitPrice": "11.093600",
            "detailDiscountedUnitPrice": "11.093600"
        }],
        "name": "Fast Order Flyer",
        "userProductName": "Flyers",
        "type": "PRINT_PRODUCT",
        "priceable": true
    }';

    protected $searchTransactionResponse = '{
        "transactionId": "783d32ef-3c33-4ca3-a588-e57649325b72",
        "output": {
            "transactionsByIds": [{
                "type": "PRINT_ORDER_NUMBER",
                "value": "7868686969",
                "transactionExist": true,
                "transactionInfo": [
                    {
                        "id": "JMDKN43E63F7121104X",
                        "type": "SALE"
                    }
                ]
            }]
        }
    }';

    public const FJMP_RATE_QUOTE_ID = 'bmw123';
    public const GTN_NUMBER = '7868686969';
    public const RETAIL_TRANSACTION_ID = 'JMDKN43E63F7121104X';

    /**
     * Main set up method
     */
    public function setUp(): void
    {
        $this->newRelicHeaders = $this->createMock(NewRelicHeaders::class);
        $this->loggerHelperMock = $this->createMock(LoggerHelper::class);
        $this->logHelperApi = $this->createMock(LogHelperApi::class);
        $this->submitOrderHelperMock = $this->createMock(SubmitOrderHelper::class);
        $this->submitOrderOptimizedHelperMock = $this->createMock(SubmitOrderOptimizedHelper::class);
        $this->orderModelMock = $this->getMockBuilder(OrderModel::class)
            ->setMethods([
                'loadByIncrementId',
                'delete',
                'getId',
                'getQuoteId',
                'setStatus',
                'getStatus',
                'save',
                'getPayment',
                'hasInvoices',
                'hasShipments',
                'getItemsCollection'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->setMethods(['register', 'unregister'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->apiHandlerMock = $this->createMock(RateQuoteAndTransactionApiHandler::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods([
                'setIsTimeout',
                'getIsTimeout',
                'setIsActive',
                'save',
                'getData',
                'getId',
                'getGtn',
                'getShippingAddress',
                'getBillingFields',
                'getIsAlternatePickup',
                'getIsEproQuote'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderPaymentInterface = $this->getMockBuilder(OrderPaymentInterface::class)
            ->setMethods([
                'setFedexAccountNumber',
                'save',
                'setMethod',
                'setLastTransId',
                'setTransactionId',
                'setRetailTransactionId',
                'setProductLineDetails',
                'setSiteConfiguredPaymentUsed',
                'setCcLast4'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->addressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMockForAbstractClass();

        $this->addressInterfaceMock = $this->getMockBuilder(AddressInterface::class)
            ->setMethods([
                'setShippingMethod',
                'collectShippingRates',
                'setCollectShippingRates',
                'getShippingMethod',
                'getShippingDescription',
                'setShippingDescription',
                'getCustomerId'
            ])
            ->getMockForAbstractClass();

        $this->dataObjectFactory = $this->getMockBuilder(DataObjectFactory::class)
            ->setMethods(['create', 'getQuoteData', 'getPaymentData', 'getEncCCData',
                'getIsPickup', 'getShipmenttId', 'getEstimatePickupTime',
                'getUseSiteCreditCard', 'getOrderData', 'getQuoteId',
                'getOrderNumber',
                'getNumDiscountPrice', 'getShippingAccount', 'getRequestedAmount'
                , 'getCcToken', 'getNameOnCard', 'getExpirationMonth',
                'getExpirationYear', 'getNumTotal', 'getState',
                'getAccNo', 'getCondition', 'getPaymentMethod', 'getStateCode',
                'setDate', 'setFjmpRateQuoteId', 'setFname', 'setLname', 'setCompanyName',
                'setEmail', 'setPhNumber', 'setExtension', 'setNumDiscountPrice',
                'setShippingAccount', 'setRequestedAmount', 'setEncCCData',
                'setCcToken', 'setNameOnCard', 'setStreetAddress', 'setCity',
                'setShipperRegion', 'setStateCode', 'setZipCode', 'setAddressClassification',
                'setExpirationMonth', 'setExpirationYear', 'setPoReferenceId', 'setNumTotal',
                'setState', 'setAccNo', 'setCondition', 'setPaymentMethod', 'getRateQuoteResponse', 'getShipmentId',
                'getProductLinesDetails', 'getEproOrder', 'getSiteName', 'setSiteName'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderApprovalViewModel = $this->getMockBuilder(OrderApprovalViewModel::class)
            ->setMethods(['isOrderApprovalB2bEnabled'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->setMethods(['getProductionLocationId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->instoreConfigMock = $this->createMock(InstoreConfig::class);

        $this->submitOrderApiMock = (new ObjectManager($this))->getObject(
            SubmitOrderApi::class,
            [
                'submitOrderHelper' => $this->submitOrderHelperMock,
                'submitOrderOptimizedHelper' => $this->submitOrderOptimizedHelperMock,
                'logger' => $this->loggerMock,
                'orderModel' => $this->orderModelMock,
                'registryMock' => $this->registryMock,
                'apiHandler' => $this->apiHandlerMock,
                'toggleConfig' => $this->toggleConfigMock,
                'instoreConfig' => $this->instoreConfigMock,
                'orderApprovalViewModel' => $this->orderApprovalViewModel,
                'loggerHelperMock' => $this->loggerHelperMock,
                'newRelicHeaders' => $this->newRelicHeaders,
                'logHelperApi' =>  $this->logHelperApi
            ]
        );
    }

    public function testCallRateQuoteApiWithGraphQlFujitsuResponseException()
    {
        $rateQuoteResponseData = [
            "errors" => ['error']
        ];
        $this->dataObjectFactory->expects($this->any())->method('getOrderData')
            ->willReturn($this->rateQuoteRequestArray);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteData')
            ->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getId')->willReturn(self::QUOTE_ID);
        $this->quote->expects($this->any())->method('getGtn')->willReturn(self::GTN_NUMBER);
        $this->apiHandlerMock->expects($this->any())->method('callCurlPost')
            ->with(json_encode($this->rateQuoteRequestArray), 'rate')
            ->willReturn(json_encode($rateQuoteResponseData));

        $this->loggerMock->expects($this->any())->method('info');
        $this->instoreConfigMock->expects($this->any())
            ->method('isEnabledThrowExceptionOnGraphqlRequests')
            ->willReturn(true);

        $this->expectException(GraphQlFujitsuResponseException::class);

        $this->submitOrderApiMock->callRateQuoteApi($this->dataObjectFactory);
    }

    /**
     * Unset orderinprogress from session
     *
     * @return mixed
     */
    public function testUnsetOrderInProgress()
    {
        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('unsetOrderInProgress')->willReturn(null);
        $this->assertNull($this->submitOrderApiMock->unsetOrderInProgress());
    }

    /**
     * isAlternateContact
     *
     * @return mixed
     */
    public function testIsAlternateContact()
    {
        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('isAlternateContact')->willReturn(null);
        $this->assertNull($this->submitOrderApiMock->isAlternateContact());
    }

    /**
     * isAlternatePickupPerson
     *
     * @return mixed
     */
    public function testIsAlternatePickupPerson()
    {
        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('isAlternatePickupPerson')
            ->willReturn(null);
        $this->assertNull($this->submitOrderApiMock->isAlternatePickupPerson());
    }

    public function testCallFujitsuRateQuoteApiWithDuplicateOrder()
    {
        $this->dataObjectFactory->expects($this->any())->method('getOrderData')
            ->willReturn($this->rateQuoteRequestArray);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteId')->willReturn(self::QUOTE_ID);
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')->willReturn(self::GTN_NUMBER);
        $this->submitOrderHelperMock->expects($this->any())->method('isDuplicateOrder')->with(self::QUOTE_ID)
            ->willReturn(true);

        $this->assertEquals(
            ['error' => 2, 'msg' => 'Duplicate Order Number', 'response' => ''],
            $this->submitOrderApiMock->callFujitsuRateQuoteApi($this->dataObjectFactory)
        );
    }

    public function testCallFujitsuRateQuoteApiWithoutDuplicateOrder()
    {
        $this->dataObjectFactory->expects($this->any())->method('getOrderData')
            ->willReturn($this->rateQuoteRequestArray);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteId')->willReturn(self::QUOTE_ID);
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')->willReturn(self::GTN_NUMBER);
        $this->submitOrderHelperMock->expects($this->any())->method('isDuplicateOrder')->with(self::QUOTE_ID)
            ->willReturn(false);

        $this->apiHandlerMock->expects($this->any())->method('callCurlPost')
            ->with(json_encode($this->rateQuoteRequestArray), 'rate')
            ->willReturn($this->rateQuoteMockResponse);

        $this->apiHandlerMock->expects($this->any())->method('getCheckoutResponseData')
            ->with($this->dataObjectFactory, json_decode((string)$this->rateQuoteMockResponse, true))
            ->willReturn([]);

        $this->assertEquals([], $this->submitOrderApiMock->callFujitsuRateQuoteApi($this->dataObjectFactory));
    }

    public function testCallFujitsuRateQuoteApiWithEmptyResponse()
    {
        $this->dataObjectFactory->expects($this->any())->method('getOrderData')
            ->willReturn($this->rateQuoteRequestArray);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteId')->willReturn(self::QUOTE_ID);
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')->willReturn(self::GTN_NUMBER);
        $this->submitOrderHelperMock->expects($this->any())->method('isDuplicateOrder')->with(self::QUOTE_ID)
            ->willReturn(false);

        $this->apiHandlerMock->expects($this->any())->method('callCurlPost')
            ->with(json_encode($this->rateQuoteRequestArray), 'rate')
            ->willReturn('{}');

        $this->assertEquals(
            ['error' => 1, 'msg' => SubmitOrderApi::FAILURE, 'response' => ''],
            $this->submitOrderApiMock->callFujitsuRateQuoteApi($this->dataObjectFactory)
        );
    }

    public function testCallTransactionAPIRequest()
    {
        $this->dataObjectFactory->expects($this->any())->method('getQuoteData')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getData')
            ->withConsecutive(['fjmp_quote_id'])
            ->willReturnOnConsecutiveCalls(self::FJMP_RATE_QUOTE_ID);
        $this->dataObjectFactory->expects($this->any())->method('getRateQuoteResponse')
            ->willReturn($this->rateQuoteMockResponse);
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')->willReturn(self::GTN_NUMBER);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteId')->willReturn(self::QUOTE_ID);

        $this->apiHandlerMock->expects($this->any())->method('constructTransactionAPI')
            ->with($this->quote, self::FJMP_RATE_QUOTE_ID, $this->rateQuoteMockResponse, $this->dataObjectFactory)
            ->willReturn(['error' => 0, 'msg' => 'Success', 'response' => $this->checkoutResponse]);

        $this->apiHandlerMock->expects($this->any())->method('getTransactionIdAndProductLinesAttributes')
            ->willReturn([
                    'retailTransactionId' => self::RETAIL_TRANSACTION_ID,
                    'productLineDetailsAttributes' => json_decode($this->productLines, true)]);
        $this->dataObjectFactory->expects($this->any())->method('getPaymentData')->willReturn(
            (object)["paymentMethod" => "payment_method"]
        );
        $this->assertNotNull($this->submitOrderApiMock->callTransactionAPIRequest($this->dataObjectFactory));
    }

    public function testCallInStoreTransactionAPIRequest()
    {
        $this->dataObjectFactory->expects($this->any())->method('getQuoteData')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getData')
            ->withConsecutive(['fjmp_quote_id'])
            ->willReturnOnConsecutiveCalls(self::FJMP_RATE_QUOTE_ID);
        $this->dataObjectFactory->expects($this->any())->method('getRateQuoteResponse')
            ->willReturn($this->rateQuoteMockResponse);
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')->willReturn(self::GTN_NUMBER);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteId')->willReturn(self::QUOTE_ID);

        $this->apiHandlerMock->expects($this->any())->method('handleInstoreTransactionAPI')
            ->willReturn(['error' => 0, 'msg' => 'Success', 'response' => $this->checkoutResponse]);

        $this->apiHandlerMock->expects($this->any())->method('getTransactionIdAndProductLinesAttributes')
            ->willReturn([
                    'retailTransactionId' => self::RETAIL_TRANSACTION_ID,
                    'productLineDetailsAttributes' => json_decode($this->productLines, true)]);
        $this->dataObjectFactory->expects($this->any())->method('getPaymentData')->willReturn(
            (object)["paymentMethod" => "instore"]
        );
        $this->assertNotNull($this->submitOrderApiMock->callTransactionAPIRequest($this->dataObjectFactory));
    }

    public function testCallTransactionAPIRequestWithError()
    {
        $this->dataObjectFactory->expects($this->any())->method('getQuoteData')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getData')
            ->withConsecutive(['fjmp_quote_id'])
            ->willReturnOnConsecutiveCalls(self::FJMP_RATE_QUOTE_ID);
        $this->dataObjectFactory->expects($this->any())->method('getRateQuoteResponse')
            ->willReturn($this->rateQuoteMockResponse);
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')->willReturn(self::GTN_NUMBER);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteId')->willReturn(self::QUOTE_ID);

        $this->apiHandlerMock->expects($this->any())->method('constructTransactionAPI')
            ->with($this->quote, self::FJMP_RATE_QUOTE_ID, $this->rateQuoteMockResponse, $this->dataObjectFactory)
            ->willReturn(['error' => 1, 'msg' => 'Failure', 'response' => $this->checkoutResponse]);

        $this->apiHandlerMock->expects($this->any())->method('getTransactionIdAndProductLinesAttributes')
            ->willReturn([
                    'retailTransactionId' => self::RETAIL_TRANSACTION_ID,
                    'productLineDetailsAttributes' => json_decode($this->productLines, true)]);
        $this->dataObjectFactory->expects($this->any())->method('getPaymentData')->willReturn(
            (object)["paymentMethod" => "payment_method"]
        );
        $this->assertNotNull($this->submitOrderApiMock->callTransactionAPIRequest($this->dataObjectFactory));
    }

    public function testCallTransactionAPIRequestWithAlert()
    {
        $transactionResponseData = [
            "errors" => [
                [
                    "code" => "error",
                    "message" => "Transaction CXS API Failed"
                ]
            ]
        ];

        $this->dataObjectFactory->expects($this->any())->method('getQuoteData')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getData')
            ->withConsecutive(['fjmp_quote_id'])
            ->willReturnOnConsecutiveCalls(self::FJMP_RATE_QUOTE_ID);
        $this->dataObjectFactory->expects($this->any())->method('getRateQuoteResponse')
            ->willReturn($this->rateQuoteMockResponse);
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')->willReturn(self::GTN_NUMBER);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteId')->willReturn(self::QUOTE_ID);

        $this->apiHandlerMock->expects($this->any())->method('constructTransactionAPI')
            ->with($this->quote, self::FJMP_RATE_QUOTE_ID, $this->rateQuoteMockResponse, $this->dataObjectFactory)
            ->willReturn([
                'error' => 1, 'msg' => 'Failure',
                'response' => json_encode($transactionResponseData)
            ]);
        $this->dataObjectFactory->expects($this->any())->method('getPaymentData')->willReturn(
            (object)["paymentMethod" => "payment_method"]
        );
        $this->assertNotNull($this->submitOrderApiMock->callTransactionAPIRequest($this->dataObjectFactory));
    }

    public function testCallTransactionAPIRequestWithEmptyResponse()
    {
        $this->dataObjectFactory->expects($this->any())->method('getQuoteData')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getData')
            ->withConsecutive(['fjmp_quote_id'])
            ->willReturnOnConsecutiveCalls(self::FJMP_RATE_QUOTE_ID);
        $this->dataObjectFactory->expects($this->any())->method('getRateQuoteResponse')
            ->willReturn($this->rateQuoteMockResponse);
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')->willReturn(self::GTN_NUMBER);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteId')->willReturn(self::QUOTE_ID);

        $this->apiHandlerMock->expects($this->any())->method('constructTransactionAPI')
            ->with($this->quote, self::FJMP_RATE_QUOTE_ID, $this->rateQuoteMockResponse, $this->dataObjectFactory)
            ->willReturn([]);
        $this->dataObjectFactory->expects($this->any())->method('getPaymentData')->willReturn(
            (object)["paymentMethod" => "payment_method"]
        );
        $this->assertEquals(
            ['error' => 1, 'msg' => 'Error found no data', 'response' => ''],
            $this->submitOrderApiMock->callTransactionAPIRequest($this->dataObjectFactory)
        );
    }

    public function testCallRateQuoteApi()
    {
        $this->dataObjectFactory->expects($this->any())->method('getOrderData')
            ->willReturn($this->rateQuoteRequestArray);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteData')
            ->willReturn($this->quote);
        $this->dataObjectFactory->expects($this->any())->method('getEproOrder')
            ->willReturn(null);
        $this->quote->expects($this->any())->method('getId')->willReturn(self::QUOTE_ID);
        $this->quote->expects($this->any())->method('getGtn')->willReturn(self::GTN_NUMBER);
        $this->apiHandlerMock->expects($this->any())->method('callCurlPost')
            ->with(json_encode($this->rateQuoteRequestArray), 'rate')
            ->willReturn($this->rateQuoteMockResponse);
        $this->testValidateRateQuoteResponse();
        $this->assertNotNull($this->submitOrderApiMock->callRateQuoteApi($this->dataObjectFactory));
    }

    public function testCallRateQuoteApiWithErrors()
    {
        $rateQuoteResponseData = [
            "errors" => ['error']
        ];
        $this->dataObjectFactory->expects($this->any())->method('getOrderData')
            ->willReturn($this->rateQuoteRequestArray);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteData')
            ->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getId')->willReturn(self::QUOTE_ID);
        $this->quote->expects($this->any())->method('getGtn')->willReturn(self::GTN_NUMBER);
        $this->apiHandlerMock->expects($this->any())->method('callCurlPost')
            ->with(json_encode($this->rateQuoteRequestArray), 'rate')
            ->willReturn(json_encode($rateQuoteResponseData));

        $this->assertNotNull($this->submitOrderApiMock->callRateQuoteApi($this->dataObjectFactory));
    }

    public function testCallRateQuoteApiWithEmptyResponse()
    {
        $this->dataObjectFactory->expects($this->any())->method('getOrderData')
            ->willReturn($this->rateQuoteRequestArray);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteData')
            ->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getId')->willReturn(self::QUOTE_ID);
        $this->quote->expects($this->any())->method('getGtn')->willReturn(self::GTN_NUMBER);
        $this->apiHandlerMock->expects($this->any())->method('callCurlPost')
            ->with(json_encode($this->rateQuoteRequestArray), 'rate')
            ->willReturn('{}');

        $this->assertEquals(
            ['error' => 1, 'msg' => SubmitOrderApi::FAILURE, 'response' => ''],
            $this->submitOrderApiMock->callRateQuoteApi($this->dataObjectFactory)
        );
    }

    public function testValidateRateQuoteResponse()
    {
        $this->testGetRateQuoteId();
        $this->submitOrderHelperMock->expects($this->any())->method('getActiveQuote')
            ->willReturn($this->quote);

        $this->submitOrderHelperMock->expects($this->any())->method('isSetOrderId')
            ->willReturn(true);

        $this->assertNotNull(
            $this->submitOrderApiMock->validateRateQuoteResponse(
                $this->quote,
                $this->rateQuoteRequestArray
            )
        );
    }

    public function testValidateRateQuoteResponseWithoutReservedOrderId()
    {
        $this->testGetRateQuoteId();
        $this->submitOrderHelperMock->expects($this->any())->method('getActiveQuote')
            ->willReturn($this->quote);

        $this->submitOrderHelperMock->expects($this->any())->method('isSetOrderId')
            ->willReturn(false);

        $this->assertNotNull(
            $this->submitOrderApiMock->validateRateQuoteResponse(
                $this->quote,
                $this->rateQuoteRequestArray
            )
        );
    }

    public function testCreateOrderBeforePayment()
    {
        $paymentData = (object)[
            "fedexAccountNumber" => 653243286,
            "paymentMethod" => 'fedex',
            "poReferenceId" => '1234',
            "number" => '8599'
        ];

        $paymentInfo = [
            'shippingAccountNumber' => 140844063,
            'useSitePayment' => true,
            'fedexAccountNumber' => 653243286,
            'ccNumber' => '8599',
            'ccOwner' => null,
            'fedexPoNumber' => '1234',
            'paymentMethod' => 'fedex'
        ];
        $this->dataObjectFactory->expects($this->any())->method('getQuoteData')
            ->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getId')->willReturn(123);
        $this->submitOrderHelperMock->expects($this->any())->method('getPaymentParametersData')
            ->with($this->quote, $paymentData)
            ->willReturn($paymentInfo);

        $this->submitOrderHelperMock->expects($this->any())->method('setQuotePaymentInfo')
            ->with($this->quote, $paymentInfo)
            ->willReturn($this->quote);
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->apiHandlerMock->expects($this->any())->method('getCommercialCustomer')->willReturn(false);
        $this->apiHandlerMock->expects($this->any())->method('getCustomer')->willReturn(true);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterfaceMock);
        $this->addressInterfaceMock->expects($this->any())->method('setCollectShippingRates')->willReturnSelf();
        $this->addressInterfaceMock->expects($this->any())->method('collectShippingRates')->willReturnSelf();
        $this->addressInterfaceMock->expects($this->any())->method('getShippingMethod')->willReturn('TX_Flat');
        $this->addressInterfaceMock->expects($this->any())->method('setShippingMethod')->willReturn('TX_Flat');
        $this->submitOrderHelperMock->expects($this->any())->method('updateCustomerInformation')->with($this->quote)
            ->willReturn($this->quote);
        $this->submitOrderHelperMock->expects($this->any())->method('verifyQuoteIntegrity')->with($this->quote)
            ->willReturnSelf();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->submitOrderHelperMock->expects($this->any())->method('createOrderFromQuote')->with($this->quote)
            ->willReturn($this->orderModelMock);

        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('getCheckoutSessionQuote')
            ->willReturn($this->quote);
        $this->testUpdateQuoteStatusAndTimeoutFlag();
        $this->orderApprovalViewModel->expects($this->any())->method('isOrderApprovalB2bEnabled')
            ->willReturn(true);

        $rateQuoteMockResponseArray = [
            'output' => [
                'rateQuote' => [
                    'rateQuoteDetails' => [
                        0 => [
                            'estimatedVsActual' => 'Actual',
                            'productLines' => [
                                'test'
                            ],

                        ]
                    ]
                ]
            ]
        ];

        $this->dataObjectFactory->expects($this->any())->method('getRateQuoteResponse')
            ->willReturn($rateQuoteMockResponseArray);
        $this->orderModelMock->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->any())->method('setFedexAccountNumber')
            ->with(653243286)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setPoNumber')
            ->with(1234)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setCcOwner')
            ->with(null)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setCcLast4')
            ->with(8599)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setProductLineDetails')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setSiteConfiguredPaymentUsed')->with(1)
            ->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('save')->willReturnSelf();
        $this->orderModelMock->expects($this->any())->method('save')->willReturnSelf();

        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('producingAddress')
            ->willReturnSelf();

        $this->assertNotNull(
            $this->submitOrderApiMock->createOrderBeforePayment($paymentData, $this->dataObjectFactory)
        );
    }

    public function testCreateOrderBeforePaymentWithException()
    {
        $paymentData = (object)[
            "fedexAccountNumber" => 653243286,
            "paymentMethod" => 'fedex',
            "poReferenceId" => '1234',
            "number" => '8599'
        ];

        $paymentInfo = [
            'shippingAccountNumber' => 140844063,
            'useSitePayment' => true,
            'fedexAccountNumber' => 653243286,
            'ccNumber' => '8599',
            'ccOwner' => null,
            'fedexPoNumber' => '1234',
            'paymentMethod' => 'fedex'
        ];

        $this->dataObjectFactory->expects($this->any())->method('getQuoteData')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getId')->willReturn(123);
        $this->submitOrderHelperMock->expects($this->any())->method('getPaymentParametersData')
            ->with($this->quote, $paymentData)
            ->willReturn($paymentInfo);

        $this->submitOrderHelperMock->expects($this->any())->method('setQuotePaymentInfo')
            ->with($this->quote, $paymentInfo)
            ->willReturn($this->quote);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->apiHandlerMock->expects($this->any())->method('getCommercialCustomer')->willReturn(false);
        $this->apiHandlerMock->expects($this->any())->method('getCustomer')->willReturn(true);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterfaceMock);
        $this->addressInterfaceMock->expects($this->any())->method('setCollectShippingRates')->willReturnSelf();
        $this->addressInterfaceMock->expects($this->any())->method('collectShippingRates')->willReturnSelf();
        $this->addressInterfaceMock->expects($this->any())->method('getShippingMethod')->willReturn('TX_Flat');
        $this->addressInterfaceMock->expects($this->any())->method('setShippingMethod')->willReturn('TX_Flat');
        $this->submitOrderHelperMock->expects($this->any())->method('updateCustomerInformation')->with($this->quote)
            ->willReturn($this->quote);
        $this->submitOrderHelperMock->expects($this->any())->method('verifyQuoteIntegrity')->with($this->quote)
            ->willReturnSelf();
        $this->quote->expects($this->any())->method('save')->willThrowException(new Exception());

        $this->submitOrderHelperMock->expects($this->any())->method('createOrderFromQuote')->with($this->quote)
            ->willReturn($this->orderModelMock);

        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('getCheckoutSessionQuote')
            ->willReturn($this->quote);
        $this->testUpdateQuoteStatusAndTimeoutFlag();

        $rateQuoteMockResponseArray = [
            'output' => [
                'rateQuote' => [
                    'rateQuoteDetails' => [
                        0 => [
                            'estimatedVsActual' => 'Actual',
                            'productLines' => [
                                'test'
                            ],

                        ]
                    ]
                ]
            ]
        ];

        $this->dataObjectFactory->expects($this->any())->method('getRateQuoteResponse')
            ->willReturn($rateQuoteMockResponseArray);
        $this->orderModelMock->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->any())->method('setFedexAccountNumber')
            ->with(653243286)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setPoNumber')
            ->with(1234)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setCcOwner')
            ->with(null)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setCcLast4')
            ->with(8599)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setProductLineDetails')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setSiteConfiguredPaymentUsed')->with(1)
            ->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('save')->willReturnSelf();
        $this->orderModelMock->expects($this->any())->method('save')->willReturnSelf();

        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('producingAddress')
            ->willReturnSelf();

        $this->assertNotNull(
            $this->submitOrderApiMock->createOrderBeforePayment($paymentData, $this->dataObjectFactory)
        );
    }

    public function testCreateOrderBeforePaymentWithOrderSaveException()
    {
        $paymentData = (object)[
            "fedexAccountNumber" => 653243286,
            "paymentMethod" => 'fedex',
            "poReferenceId" => '1234',
            "number" => '8599'
        ];

        $paymentInfo = [
            'shippingAccountNumber' => 140844063,
            'useSitePayment' => true,
            'fedexAccountNumber' => 653243286,
            'ccNumber' => '8599',
            'ccOwner' => null,
            'fedexPoNumber' => '1234',
            'paymentMethod' => 'fedex'
        ];

        $this->dataObjectFactory->expects($this->any())->method('getQuoteData')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getId')->willReturn(123);
        $this->submitOrderHelperMock->expects($this->any())->method('getPaymentParametersData')
            ->with($this->quote, $paymentData)
            ->willReturn($paymentInfo);

        $this->submitOrderHelperMock->expects($this->any())->method('setQuotePaymentInfo')
            ->with($this->quote, $paymentInfo)
            ->willReturn($this->quote);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->apiHandlerMock->expects($this->any())->method('getCommercialCustomer')->willReturn(false);
        $this->apiHandlerMock->expects($this->any())->method('getCustomer')->willReturn(true);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterfaceMock);
        $this->addressInterfaceMock->expects($this->any())->method('setCollectShippingRates')->willReturnSelf();
        $this->addressInterfaceMock->expects($this->any())->method('collectShippingRates')->willReturnSelf();
        $this->addressInterfaceMock->expects($this->any())->method('getShippingMethod')->willReturn('TX_Flat');
        $this->addressInterfaceMock->expects($this->any())->method('setShippingMethod')->willReturn('TX_Flat');
        $this->submitOrderHelperMock->expects($this->any())->method('updateCustomerInformation')->with($this->quote)
            ->willReturn($this->quote);
        $this->submitOrderHelperMock->expects($this->any())->method('verifyQuoteIntegrity')->with($this->quote)
            ->willReturnSelf();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();

        $this->submitOrderHelperMock->expects($this->any())->method('createOrderFromQuote')->with($this->quote)
            ->willReturn($this->orderModelMock);

        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('getCheckoutSessionQuote')
            ->willReturn($this->quote);
        $this->testUpdateQuoteStatusAndTimeoutFlag();

        $rateQuoteMockResponseArray = [
            'output' => [
                'rateQuote' => [
                    'rateQuoteDetails' => [
                        0 => [
                            'estimatedVsActual' => 'Actual',
                            'productLines' => [
                                'test'
                            ],

                        ]
                    ]
                ]
            ]
        ];

        $this->dataObjectFactory->expects($this->any())->method('getRateQuoteResponse')
            ->willReturn($rateQuoteMockResponseArray);
        $this->orderModelMock->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->any())->method('setFedexAccountNumber')
            ->with(653243286)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setPoNumber')
            ->with(1234)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setCcOwner')
            ->with(null)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setCcLast4')
            ->with(8599)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setProductLineDetails')->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setSiteConfiguredPaymentUsed')->with(1)
            ->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('save')->willReturnSelf();
        $this->orderModelMock->expects($this->any())->method('save')->willThrowException(new Exception());

        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('producingAddress')
            ->willReturnSelf();

        $this->assertNotNull(
            $this->submitOrderApiMock->createOrderBeforePayment($paymentData, $this->dataObjectFactory)
        );
    }

    public function testUpdateOrderAfterPayment()
    {
        $this->dataObjectFactory->expects($this->any())->method('getQuoteData')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getData')
            ->withConsecutive(['fjmp_quote_id'])
            ->willReturnOnConsecutiveCalls(self::FJMP_RATE_QUOTE_ID);
        $this->dataObjectFactory->expects($this->any())->method('getRateQuoteResponse')
            ->willReturn($this->rateQuoteMockResponse);
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')->willReturn(self::GTN_NUMBER);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteId')->willReturn(self::QUOTE_ID);

        $this->apiHandlerMock->expects($this->any())->method('constructTransactionAPI')
            ->with($this->quote, self::FJMP_RATE_QUOTE_ID, $this->rateQuoteMockResponse, $this->dataObjectFactory)
            ->willReturn(['error' => 0, 'msg' => 'Success', 'response' => $this->checkoutResponse]);

        $this->apiHandlerMock->expects($this->any())->method('getTransactionIdAndProductLinesAttributes')
            ->willReturn([
                    'retailTransactionId' => self::RETAIL_TRANSACTION_ID,
                    'productLineDetailsAttributes' => json_decode($this->productLines, true)]);

        $this->submitOrderHelperMock->expects($this->any())->method('isSetOrderId')
            ->with($this->quote, self::GTN_NUMBER)
            ->willReturnSelf();

        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('getRetailTransactionId')
            ->willReturn(self::RETAIL_TRANSACTION_ID);
        $this->dataObjectFactory->expects($this->any())->method('getPaymentData')->willReturn(
            (object)["paymentMethod" => "payment_method"]
        );
        $this->testSaveOrderWithNewStatus();
        $this->assertNotNull(
            $this->submitOrderApiMock->updateOrderAfterPayment($this->dataObjectFactory, $this->orderModelMock)
        );
    }

    public function testUpdateOrderAfterPaymentWithTimeout()
    {
        $transactionResponseData = [
            "errors" => [
                [
                    "code" => "error",
                    "message" => "Transaction CXS API Failed"
                ]
            ]
        ];

        $this->dataObjectFactory->expects($this->any())->method('getQuoteData')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getData')
            ->withConsecutive(['fjmp_quote_id'])
            ->willReturnOnConsecutiveCalls(self::FJMP_RATE_QUOTE_ID);
        $this->dataObjectFactory->expects($this->any())->method('getRateQuoteResponse')
            ->willReturn($this->rateQuoteMockResponse);
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')->willReturn(self::GTN_NUMBER);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteId')->willReturn(self::QUOTE_ID);

        $this->apiHandlerMock->expects($this->any())->method('constructTransactionAPI')
            ->with($this->quote, self::FJMP_RATE_QUOTE_ID, $this->rateQuoteMockResponse, $this->dataObjectFactory)
            ->willReturn([
                'error' => 1, 'msg' => 'timeout',
                'response' => json_encode($transactionResponseData)
            ]);
        $this->dataObjectFactory->expects($this->any())->method('getPaymentData')->willReturn(
            (object)["paymentMethod" => "payment_method"]
        );
        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('getCheckoutSessionQuote')
            ->willReturn($this->quote);

        $this->testSetTimeoutFlag();

        $this->assertNotNull(
            $this->submitOrderApiMock->updateOrderAfterPayment($this->dataObjectFactory, $this->orderModelMock)
        );
    }

    public function testDeleteOrderWithPendingStatus()
    {
        $this->orderModelMock->expects($this->any())->method('loadByIncrementId')->with(self::GTN_NUMBER)
            ->willReturnSelf();
        $this->orderModelMock->expects($this->any())->method('getStatus')->willReturn("pending");
        $this->registryMock->expects($this->any())->method('register')->with('isSecureArea', 'true')
            ->willReturnSelf();
        $this->orderModelMock->expects($this->any())->method('delete')->willReturnSelf();
        $this->registryMock->expects($this->any())->method('unregister')->with('isSecureArea')->willReturnSelf();
        $this->assertNull($this->submitOrderApiMock->deleteOrderWithPendingStatus(self::GTN_NUMBER));
    }

    public function testDeleteOrderWithPendingStatusWithLocalizedException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->orderModelMock->expects($this->any())->method('loadByIncrementId')->with(self::GTN_NUMBER)
            ->willReturnSelf();
        $this->orderModelMock->expects($this->any())->method('getStatus')->willReturn("pending");
        $this->registryMock->expects($this->any())->method('register')->with('isSecureArea', 'true')
            ->willReturnSelf();
        $this->orderModelMock->expects($this->any())->method('delete')->willThrowException($exception);
        $this->registryMock->expects($this->any())->method('unregister')->with('isSecureArea')->willReturnSelf();
        $this->assertNull($this->submitOrderApiMock->deleteOrderWithPendingStatus(self::GTN_NUMBER));
    }

    public function testDeleteOrderWithPendingStatusWithException()
    {
        $this->orderModelMock->expects($this->any())->method('loadByIncrementId')->with(self::GTN_NUMBER)
            ->willReturnSelf();
        $this->orderModelMock->expects($this->any())->method('getStatus')->willReturn("pending");
        $this->registryMock->expects($this->any())->method('register')->with('isSecureArea', 'true')
            ->willReturnSelf();
        $this->orderModelMock->expects($this->any())->method('delete')->willThrowException(new Exception());
        $this->registryMock->expects($this->any())->method('unregister')->with('isSecureArea')->willReturnSelf();
        $this->assertNull($this->submitOrderApiMock->deleteOrderWithPendingStatus(self::GTN_NUMBER));
    }

    public function testSetTimeoutFlag()
    {
        $this->quote->expects($this->any())->method('setIsTimeout')->with(1)->willReturnSelf();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->assertNotNull(
            $this->submitOrderApiMock->setTimeoutFlag($this->quote)
        );
    }

    public function testSetTimeoutFlagWithException()
    {
        $this->quote->expects($this->any())->method('setIsTimeout')->willThrowException(new Exception());
        $this->assertNotNull($this->submitOrderApiMock->setTimeoutFlag($this->quote));
    }

    public function testGetTransactionAPIResponse()
    {
        $getTransactionResponse = [
            'error' => 0,
            'msg' => 'Success',
            'response' => ['output' => []],
            'rateQuoteResponse' => []
        ];

        $this->quote->expects($this->any())->method('getIsTimeout')->willReturn(1);

        $this->testGetRetailOrderTransactionId();
        $this->quote->expects($this->any())->method('getGtn')->willReturn(self::GTN_NUMBER);

        $this->apiHandlerMock->expects($this->any())->method('callCurlPost')
            ->willReturn($this->searchTransactionResponse);

        $this->quote->expects($this->any())->method('getGtn')->willReturn(null);

        $this->apiHandlerMock->expects($this->any())->method('getTransactionResponse')
            ->willReturn($getTransactionResponse);

        $this->orderModelMock->expects($this->any())->method('getItemsCollection')->willReturn([]);
        $this->orderModelMock->expects($this->any())->method('loadByIncrementId')->willReturnSelf();
        $this->testSaveOrderWithNewStatusWithNull();
        $this->assertNotNull(
            $this->submitOrderApiMock->getTransactionAPIResponse($this->quote, self::RETAIL_TRANSACTION_ID, true)
        );
    }

    public function testGetTransactionAPIResponseWithElse()
    {
        $getTransactionResponse = [
            'error' => 0,
            'msg' => 'Success',
            'response' => ['output' => []],
            'rateQuoteResponse' => []
        ];

        $this->quote->expects($this->any())->method('getIsTimeout')->willReturn(1);
        $this->quote->expects($this->any())->method('getGtn')->willReturn(self::GTN_NUMBER);
        $this->testGetRetailOrderTransactionId();
        $this->apiHandlerMock->expects($this->any())->method('callCurlPost')
            ->willReturn($this->searchTransactionResponse);

        $this->quote->expects($this->any())->method('getGtn')->willReturn(null);

        $this->apiHandlerMock->expects($this->any())->method('getTransactionResponse')
            ->willReturn($getTransactionResponse);

        $this->orderModelMock->expects($this->any())->method('getItemsCollection')->willReturn([]);
        $this->orderModelMock->expects($this->any())->method('loadByIncrementId')->willReturnSelf();
        $this->testSaveOrderWithNewStatusWithNull();
        $this->assertNotNull(
            $this->submitOrderApiMock->getTransactionAPIResponse($this->quote, self::RETAIL_TRANSACTION_ID, false)
        );
    }

    public function testGetTransactionAPIResponseWithoutTimeout()
    {
        $transactionApiResponse = [];
        $this->quote->expects($this->any())->method('getIsTimeout')->willReturn(false);
        $this->assertEquals(
            $transactionApiResponse,
            $this->submitOrderApiMock->getTransactionAPIResponse($this->quote, self::RETAIL_TRANSACTION_ID, true)
        );
    }

    public function testGetRetailOrderTransactionId()
    {
        $this->assertEquals(
            self::RETAIL_TRANSACTION_ID,
            $this->submitOrderApiMock->getRetailOrderTransactionId($this->quote, self::RETAIL_TRANSACTION_ID)
        );
    }

    public function testGetRetailOrderTransactionIdWithElseIf()
    {
        $this->submitOrderHelperMock->expects($this->any())->method('getRetailTransactionIdFromSession')
            ->willReturn(self::RETAIL_TRANSACTION_ID);

        $this->assertEquals(
            self::RETAIL_TRANSACTION_ID,
            $this->submitOrderApiMock->getRetailOrderTransactionId($this->quote, null)
        );
    }

    public function testGetRetailOrderTransactionIdWithElse()
    {
        $this->quote->expects($this->any())->method('getGtn')->willReturn(self::GTN_NUMBER);
        $this->submitOrderHelperMock->expects($this->any())->method('getRetailTransactionIdFromSession')
            ->willReturn(null);

        $this->assertEquals('', $this->submitOrderApiMock->getRetailOrderTransactionId($this->quote, null));
    }

    public function testGetRetailTransactionIdByGtnNumber()
    {
        $lookupsRequest = [
            "transactionsByIdsRequest" => [
                [
                    "type" => "PRINT_ORDER_NUMBER",
                    "value" => null
                ]
            ]
        ];

        $dataString = json_encode($lookupsRequest);

        $this->apiHandlerMock->expects($this->any())->method('callCurlPost')
            ->with($dataString, 'transaction_search')
            ->willReturn($this->searchTransactionResponse);
        $this->quote->expects($this->any())->method('getGtn')->willReturn(null);
        $this->assertNotNull(
            $this->submitOrderApiMock->getRetailTransactionIdByGtnNumber(null)
        );
    }

    public function testGetRetailTransactionIdByGtnNumberWitlNull()
    {
        $lookupsRequest = [
            "transactionsByIdsRequest" => [
                [
                    "type" => "PRINT_ORDER_NUMBER",
                    "value" => null
                ]
            ]
        ];

        $dataString = json_encode($lookupsRequest);
        $this->apiHandlerMock->expects($this->any())->method('callCurlPost')
            ->with($dataString, 'transaction_search')
            ->willReturn('');
        $this->quote->expects($this->any())->method('getGtn')->willReturn(null);
        $this->assertNotNull(
            $this->submitOrderApiMock->getRetailTransactionIdByGtnNumber(null)
        );
    }

    public function testSaveOrderWithNewStatus()
    {
        $orderId = 12345;
        $billingFields = '{"totalRecords":4,"items":[{"fieldName":"YOUR_REFERENCE","value":"123456789"},{"fieldName":"DEPARTMENT_NUMBER","value":"Test"},{"fieldName":"PURCHASE_ORDER_NUMBER","value":"Test"},{"fieldName":"SHIPPER ID","value":"Test"}]}';

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->quote->expects($this->any())->method('getBillingFields')->willReturn($billingFields);
        $this->orderModelMock->expects($this->any())->method('getQuoteId')->willReturn(self::QUOTE_ID);
        $this->orderModelMock->expects($this->any())->method('getId')->willReturn($orderId);
        $this->testUpdateQuoteStatusAndTimeoutFlagWithoutStatus();
        $this->orderModelMock->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->any())->method('setRetailTransactionId')
            ->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('save')->willReturnSelf();
        $this->orderModelMock->expects($this->any())->method('setStatus')->with('new')->willReturnSelf();
        $this->orderModelMock->expects($this->any())->method('save')->willReturnSelf();
        $this->submitOrderHelperMock->expects($this->any())->method('setCookie')
            ->with('quoteId', self::QUOTE_ID)->willReturnSelf();
        $this->submitOrderHelperMock->expects($this->any())->method('reorderInstanceSave')
            ->with($orderId)->willReturnSelf();
        $this->submitOrderHelperMock->expects($this->any())->method('cleanProductItemInstance')
            ->with(self::QUOTE_ID)->willReturnSelf();
        $this->submitOrderHelperMock->expects($this->any())->method('clearQuoteCheckoutSessionAndStorage')
            ->with(self::QUOTE_ID, $orderId)->willReturnSelf();

        $this->assertNull(
            $this->submitOrderApiMock->saveOrderWithNewStatus(
                $this->orderModelMock,
                self::RETAIL_TRANSACTION_ID,
                $this->quote
            )
        );
    }

    public function testSaveOrderWithNewStatusWithNull()
    {
        $orderId = 12345;
        $retailTransactionId = '';
        $this->orderModelMock->expects($this->any())->method('getQuoteId')->willReturn(self::QUOTE_ID);
        $this->orderModelMock->expects($this->any())->method('getId')->willReturn($orderId);
        $this->testUpdateQuoteStatusAndTimeoutFlagWithoutStatus();
        $this->orderModelMock->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->any())->method('setRetailTransactionId')
            ->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('save')->willReturnSelf();
        $this->orderModelMock->expects($this->any())->method('setStatus')->with('new')->willReturnSelf();
        $this->orderModelMock->expects($this->any())->method('save')->willReturnSelf();
        $this->submitOrderHelperMock->expects($this->any())->method('setCookie')
            ->with('quoteId', self::QUOTE_ID)->willReturnSelf();
        $this->submitOrderHelperMock->expects($this->any())->method('reorderInstanceSave')
            ->with($orderId)->willReturnSelf();
        $this->submitOrderHelperMock->expects($this->any())->method('cleanProductItemInstance')
            ->with(self::QUOTE_ID)->willReturnSelf();
        $this->submitOrderHelperMock->expects($this->any())->method('clearQuoteCheckoutSessionAndStorage')
            ->with(self::QUOTE_ID, $orderId)->willReturnSelf();

        $this->assertNull(
            $this->submitOrderApiMock->saveOrderWithNewStatus(
                $this->orderModelMock,
                $retailTransactionId,
                $this->quote
            )
        );
    }

    public function testSaveOrderWithNewStatusWithSaveOrderPaymentException()
    {
        $orderId = 12345;
        $this->orderModelMock->expects($this->any())->method('getQuoteId')->willReturn(self::QUOTE_ID);
        $this->orderModelMock->expects($this->any())->method('getId')->willReturn($orderId);
        $this->testUpdateQuoteStatusAndTimeoutFlagWithoutStatus();
        $this->orderModelMock->expects($this->any())->method('getPayment')->willThrowException(new Exception());

        $this->assertNull(
            $this->submitOrderApiMock->saveOrderWithNewStatus(
                $this->orderModelMock,
                self::RETAIL_TRANSACTION_ID,
                $this->quote
            )
        );
    }

    public function testSaveOrderWithNewStatusWithSaveOrderException()
    {
        $orderId = 12345;
        $this->orderModelMock->expects($this->any())->method('getQuoteId')->willReturn(self::QUOTE_ID);
        $this->orderModelMock->expects($this->any())->method('getId')->willReturn($orderId);
        $this->testUpdateQuoteStatusAndTimeoutFlagWithoutStatus();
        $this->orderModelMock->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->any())->method('setRetailTransactionId')
            ->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('save')->willReturnSelf();
        $this->orderModelMock->expects($this->any())->method('setStatus')->willThrowException(new Exception());

        $this->assertNull(
            $this->submitOrderApiMock->saveOrderWithNewStatus(
                $this->orderModelMock,
                self::RETAIL_TRANSACTION_ID,
                $this->quote
            )
        );
    }

    public function testUpdateOrderWithNewStatus()
    {
        $this->orderModelMock->expects($this->any())->method('getQuoteId')->willReturn(self::QUOTE_ID);
        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('getQuoteObject')
            ->willReturn($this->quote);
        $this->testGetRetailTransactionIdByGtnNumberWitlNull();
        $this->testSaveOrderWithNewStatus();
        $this->assertFalse($this->submitOrderApiMock->updateOrderWithNewStatus($this->orderModelMock));
    }

    public function testUpdateOrderWithNewStatusWithNull()
    {
        $this->orderModelMock->expects($this->any())->method('getQuoteId')->willReturn(self::QUOTE_ID);
        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('getQuoteObject')
            ->willReturn($this->quote);
        $this->testGetRetailTransactionIdByGtnNumber();
        $this->testSaveOrderWithNewStatus();
        $this->assertTrue($this->submitOrderApiMock->updateOrderWithNewStatus($this->orderModelMock));
    }

    public function testUpdateQuoteStatusAndTimeoutFlag()
    {
        $status = true;
        $isTimeout = 0;
        $this->quote->expects($this->any())->method('getId')->willReturn(123);
        $this->quote->expects($this->any())->method('setIsTimeout')->with($isTimeout)->willReturnSelf();
        $this->quote->expects($this->any())->method('setIsActive')->with($status)->willReturnSelf();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->submitOrderHelperMock->expects($this->any())->method('saveQuoteByRepository')
        ->with($this->quote)->willReturnSelf();
        $this->assertNull(
            $this->submitOrderApiMock->updateQuoteStatusAndTimeoutFlag($this->quote, $status, $isTimeout)
        );
    }

    public function testUpdateQuoteStatusAndTimeoutFlagWithoutStatus()
    {
        $status = false;
        $isTimeout = 0;
        $this->quote->expects($this->any())->method('getId')->willReturn(123);
        $this->quote->expects($this->any())->method('setIsTimeout')->with($isTimeout)->willReturnSelf();
        $this->quote->expects($this->any())->method('setIsActive')->with($status)->willReturnSelf();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->submitOrderHelperMock->expects($this->any())->method('saveQuoteByRepository')
        ->with($this->quote)->willReturnSelf();
        $this->assertNull(
            $this->submitOrderApiMock->updateQuoteStatusAndTimeoutFlag($this->quote, $status, $isTimeout)
        );
    }

    public function testUpdateQuoteStatusAndTimeoutFlagWithException()
    {
        $status = false;
        $isTimeout = 0;
        $this->quote->expects($this->any())->method('getId')->willReturn(123);
        $this->quote->expects($this->any())->method('setIsTimeout')->willThrowException(new Exception());
        $this->assertNull(
            $this->submitOrderApiMock->updateQuoteStatusAndTimeoutFlag($this->quote, $status, $isTimeout)
        );
    }

    /**
     * Finalize Order Test method in case both shipment and invoices are created
     */
    public function testFinalizeOrderWithShipmentAndInvoices()
    {
        $this->orderModelMock->expects($this->any())->method('hasInvoices')->willReturn(true);
        $this->orderModelMock->expects($this->any())->method('hasShipments')->willReturn(true);

        $this->assertFalse($this->submitOrderApiMock->finalizeOrder($this->orderModelMock));
    }

    /**
     * test method for finalize order
     */
    public function testFinalizeOrder()
    {
        $messageRequest = ['orderId' => static::ORDER_ID, 'counter' => 0];
        $this->orderModelMock->expects($this->any())->method('hasInvoices')->willReturn(true);
        $this->orderModelMock->expects($this->any())->method('hasShipments')->willReturn(false);
        $this->orderModelMock->expects($this->any())->method('getId')->willReturn(static::ORDER_ID);
        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('getQuoteObject')
            ->willReturn($this->quote);

        $this->submitOrderHelperMock->expects($this->any())->method('createShipment')
            ->with($this->quote, static::ORDER_ID)->willReturn(false);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->submitOrderHelperMock->expects($this->any())->method('pushOrderIdInQueueForShipmentCreation')
            ->with(json_encode($messageRequest))->willReturnSelf();

        $this->assertFalse($this->submitOrderApiMock->finalizeOrder($this->orderModelMock));
    }

    /**
     * test method for finalize order with shipment
     */
    public function testFinalizeOrderWithShipment()
    {
        $this->orderModelMock->expects($this->any())->method('hasInvoices')->willReturn(false);
        $this->orderModelMock->expects($this->any())->method('hasShipments')->willReturn(false);
        $this->orderModelMock->expects($this->any())->method('getId')->willReturn(static::ORDER_ID);
        $this->orderModelMock->expects($this->any())->method('getQuoteId')->willReturn(static::QUOTE_ID);
        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('getQuoteObject')
            ->willReturn($this->quote);

        $this->submitOrderHelperMock->expects($this->any())->method('createShipment')
            ->with($this->quote, static::ORDER_ID)->willReturn(false);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->submitOrderHelperMock->expects($this->any())->method('generateInvoice')
            ->with(static::ORDER_ID)->willReturnSelf();
        $this->submitOrderOptimizedHelperMock->expects($this->any())
            ->method('updateOrderProducingAddressDataAfterShipment')
            ->with($this->orderModelMock)
            ->willReturnSelf();

        $this->submitOrderHelperMock->expects($this->any())->method('reorderInstanceSave')
            ->with(static::ORDER_ID)->willReturnSelf();
        $this->submitOrderHelperMock->expects($this->any())->method('cleanProductItemInstance')->with(static::QUOTE_ID)
            ->willReturnSelf();

        $this->assertFalse($this->submitOrderApiMock->finalizeOrder($this->orderModelMock));
    }

    /**
     * test method for finalize order with exception
     */
    public function testFinalizeOrderWithException()
    {
        $this->orderModelMock->expects($this->any())->method('getQuoteId')->willReturn(static::QUOTE_ID);
        $this->orderModelMock->expects($this->any())->method('getId')->willReturn(static::ORDER_ID);
        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('getQuoteObject')
            ->willReturn($this->quote);

        $this->submitOrderHelperMock->expects($this->any())->method('createShipment')
            ->with($this->quote, static::ORDER_ID)->willReturnSelf();
        $this->submitOrderHelperMock->expects($this->any())->method('generateInvoice')
            ->with(static::ORDER_ID)->willReturnSelf();
        $this->submitOrderOptimizedHelperMock->expects($this->any())
            ->method('updateOrderProducingAddressDataAfterShipment')
            ->with($this->orderModelMock)
            ->willReturnSelf();
        $this->orderModelMock->expects($this->any())->method('setStatus')->with('new')->willReturnSelf();
        $this->orderModelMock->expects($this->any())->method('save')->willReturnSelf();
        $this->submitOrderHelperMock->expects($this->any())->method('reorderInstanceSave')
            ->with(static::ORDER_ID)->willReturnSelf();
        $this->submitOrderHelperMock->expects($this->any())->method('cleanProductItemInstance')
            ->willThrowException(new Exception());

        $this->assertNull($this->submitOrderApiMock->finalizeOrder($this->orderModelMock));
    }

    /**
     * getRateQuoteId
     */
    public function testGetRateQuoteId()
    {
        $this->submitOrderHelperMock->expects($this->any())->method('getRateQuoteId')->willReturn(null);
        $this->assertNull(
            $this->submitOrderApiMock->getRateQuoteId(json_decode($this->rateQuoteMockResponse, true))
        );
    }

    /**
     * ManageAlternateFlagsWithAlternateContact
     */
    public function testManageAlternateFlagsWithAlternateContact()
    {
        $this->submitOrderOptimizedHelperMock->expects($this->any())
            ->method('setAlternateContactFlag')
            ->with(true);

        $this->submitOrderApiMock->manageAlternateFlags(true, true);
    }

    /**
     * CallRateQuoteApiWithSave
     */
    public function testCallRateQuoteApiWithSave()
    {
        $rateQuoteResponseData = [
            "errors" => ['error']
        ];
        $this->dataObjectFactory->expects($this->any())->method('getOrderData')
            ->willReturn($this->rateQuoteRequestArray);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteData')
            ->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getId')->willReturn(self::QUOTE_ID);
        $this->quote->expects($this->any())->method('getGtn')->willReturn(self::GTN_NUMBER);
        $this->apiHandlerMock->expects($this->any())->method('callCurlPost')
            ->with(json_encode($this->rateQuoteRequestArray), 'rate')
            ->willReturn(json_encode($rateQuoteResponseData));

        $this->loggerMock->expects($this->any())->method('info');
        $this->instoreConfigMock->expects($this->any())
            ->method('isEnabledThrowExceptionOnGraphqlRequests')
            ->willReturn(true);
        $this->submitOrderApiMock->callRateQuoteApiWithSave($this->dataObjectFactory);
    }

    public function testCallRateQuoteApiWithSave1()
    {
        $this->dataObjectFactory->expects($this->any())->method('getOrderData')
            ->willReturn($this->rateQuoteRequestArray);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteData')
            ->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getId')->willReturn(self::QUOTE_ID);
        $this->quote->expects($this->any())->method('getGtn')->willReturn(self::GTN_NUMBER);
        $this->apiHandlerMock->expects($this->any())->method('callCurlPost')
            ->with(json_encode($this->rateQuoteRequestArray), 'rate')
            ->willReturn($this->rateQuoteMockResponse);
        $this->testValidateRateQuoteResponse();
        $this->assertNotNull($this->submitOrderApiMock->callRateQuoteApiWithSave($this->dataObjectFactory));
    }

    public function testCallRateQuoteApiWithEmptyResponseWithSave()
    {
        $this->dataObjectFactory->expects($this->any())->method('getOrderData')
            ->willReturn($this->rateQuoteRequestArray);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteData')
            ->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getId')->willReturn(self::QUOTE_ID);
        $this->quote->expects($this->any())->method('getGtn')->willReturn(self::GTN_NUMBER);
        $this->apiHandlerMock->expects($this->any())->method('callCurlPost')
            ->with(json_encode($this->rateQuoteRequestArray), 'rate')
            ->willReturn('{}');

        $this->assertEquals(
            ['error' => 1, 'msg' => SubmitOrderApi::FAILURE, 'response' => ''],
            $this->submitOrderApiMock->callRateQuoteApiWithSave($this->dataObjectFactory)
        );
    }

    public function testGetProductionLocationIdReturnsId()
    {
        $expectedLocationId = '1234';
        $this->checkoutSessionMock->method('getProductionLocationId')
            ->willReturn($expectedLocationId);
        $result = $this->submitOrderApiMock->getProductionLocationId();
        $this->assertNull($result);
    }

    public function testGetProductionLocationIdReturnsNull()
    {
        $this->checkoutSessionMock->method('getProductionLocationId')
            ->willReturn(null);
        $result = $this->submitOrderApiMock->getProductionLocationId();
        $this->assertNull($result);
    }

    /**
     * test method for getProductLinesDetails
     */
    public function testGetProductLinesDetails()
    {
        $rateQuoteMockResponseArray = [
            'output' => [
                'rateQuote' => [
                    'rateQuoteDetails' => [
                        0 => [
                            'productLines' => [
                                'test'
                            ],

                        ]
                    ]
                ]
            ]
        ];
        $productLines = ['test'];
        $this->submitOrderOptimizedHelperMock
            ->expects($this->any())
            ->method('getProductLinesDetails')
            ->with($rateQuoteMockResponseArray)
            ->willReturn($productLines);
        $result = $this->submitOrderApiMock
            ->getProductLinesDetails($rateQuoteMockResponseArray);
        $this->assertNotNull($result);
    }
}
