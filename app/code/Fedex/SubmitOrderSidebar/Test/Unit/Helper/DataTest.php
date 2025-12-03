<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SubmitOrderSidebar\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Checkout\Model\Cart;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\Quote\Payment;
use Magento\Quote\Model\Quote\PaymentFactory;
use Magento\Sales\Model\Shipment;
use Magento\Sales\Model\Order\Shipment\Item as ShipmentItem;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\TransactionFactory;
use Fedex\Shipment\Model\ProducingAddressFactory;
use Magento\Sales\Model\Order;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\SubmitOrderSidebar\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Quote\Model\QuoteFactory;
use Fedex\OptimizeProductinstance\Helper\OptimizeItemInstanceHelper;
use Fedex\ReorderInstance\Helper\ReorderInstanceHelper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option as QuoteItemOption;
use Magento\Framework\App\RequestInterface;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Magento\Framework\App\ResourceConnection;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\Shipment\Helper\StatusOption as ShipmentHelper;
use Fedex\Base\Helper\Auth;
use Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel;
use Fedex\Cart\ViewModel\CartSummary;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
/**
 * Data Test Case
 */
class DataTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $orderPaymentInterface;
    protected $orderCollectionFactoryMock;
    protected $orderCollectionMock;
    protected $payment;
    /**
     * @var (\Magento\Sales\Api\Data\OrderInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderInterface;
    protected $cart;
    protected $quote;
    protected $item;
    protected $quoteItem;
    protected $option;
    protected $shipment;
    protected $shipmentItem;
    protected $invoiceMock;
    protected $toggleConfigMock;
    protected $deliveryHelper;
    protected $addressInterface;
    protected $eventManager;
    /**
     * @var (\Magento\Framework\Exception\LocalizedException & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $localizedException;
    protected $quoteFactory;
    protected $optimizeItemInstanceHelper;
    protected $reorderInstanceHelper;
    protected $resourceConnection;
    protected $attributeRepositoryInterface;
    protected $timezone;
    protected $orderApprovalViewModel;
    public const QUOTE_ID = 238;
    public const FEDEX_ACCOUNT_NUMBER = "12345678";
    public const SHIPPING_METHOD_NAME = "fedex overnight";

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    protected $quoteRepository;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    protected $orderRepository;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    protected CartRepositoryInterface|MockObject $quoteRepositoryMock;

    /**
     * @var BuilderInterface|MockObject
     */
    protected $transactionBuilder;

    /**
     * @var Order|MockObject
     */
    protected $convertOrder;

    /**
     * @var QuoteManagement|MockObject
     */
    protected $quoteManagement;

    /**
     * @var OrderService|MockObject
     */
    protected $orderService;

    /**
     * @var PaymentFactory|MockObject
     */
    protected $paymentFactory;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var InvoiceService|MockObject
     */
    protected $invoiceService;

    /**
     * @var TransactionFactory|MockObject
     */
    protected $transactionFactory;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $producingAddress;

    /**
     * @var ModelOrder|MockObject
     */
    protected $order;

    /**
     * @var Data|MockObject
     */
    protected $helperData;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    protected $customerRepositoryMock;

    /**
     * @var Session|MockObject
     */
    protected $customerSessionMock;

    /**
     * @var CookieManagerInterface|MockObject
     */
    protected $cookieManagerMock;

    /**
     * @var CookieMetadataFactory|MockObject
     */
    protected $cookieMetadataFactoryMock;

    /**
     * @var CheckoutSession|MockObject
     */
    protected $checkoutSessionMock;

    /**
     * @var SsoConfiguration|MockObject
     */
    protected $ssoConfiguration;

    /**
     * @var SdeHelper|MockObject
     */
    protected $sdeHelper;

    /**
     * @var RequestInterface|MockObject
     */
    private RequestInterface|MockObject $requestMock;

    /**
     * @var RequestQueryValidator|MockObject
     */
    private RequestQueryValidator|MockObject $requestQueryValidatorMock;

    /**
     * @var ShipmentHelper|MockObject
     */
    private ShipmentHelper|MockObject $shipmentHelper;

    /**
     * @var CartSummary|MockObject
     */
    private $cartSummaryMock;

    /**
     * @var DataTest|MockObject
     */
    protected $output = [
        'output' => [
            'rateQuote' => [
                'currency' => 'USD',
                'rateQuoteDetails' => [
                    0 => [
                        'productLines' => [
                            0 => [
                                'instanceId' => 0,
                                'productId' => '1508784838900',
                                'retailPrice' => '$0.09',
                                'discountAmount' => '$0.90',
                                'unitQuantity' => 1,
                                'linePrice' => '$0.490',
                                'priceable' => 1,
                                'productLineDetails' => [
                                    0 => [
                                        'detailCode' => '0173',
                                        'description' => 'Single Sided Color',
                                        'detailCategory' => 'PRINTING',
                                        'unitQuantity' => 1,
                                        'unitOfMeasurement' => 'EACH',
                                        'detailPrice' => '$0.493',
                                        'detailDiscountPrice' => '$0.000',
                                        'detailUnitPrice' => '$0.4900',
                                        'detailDiscountedUnitPrice' => '$0.002',
                                    ],
                                ],
                                'productRetailPrice' => 0.49,
                                'productDiscountAmount' => '0.00',
                                'productLinePrice' => '0.49',
                                'editable' => '',
                            ],
                        ],
                        'grossAmount' => '$0.499',
                        'discounts' => [
                            0 => [
                                'type' => "AR_CUSTOMERS",
                                'amount' => '$2.43'
                            ]
                        ],
                        'totalDiscountAmount' => '$0.00',
                        'netAmount' => '$0.494',
                        'taxableAmount' => '$0.495',
                        'taxAmount' => '$0.00',
                        'totalAmount' => '$0.49',
                        'estimatedVsActual' => 'ACTUAL',
                    ],
                ],

            ]
        ],
    ];

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

    /**
     * @var string
     */
    protected $rateQuoteMockErrorResponse = '{
        "transactionId": "54986d88-1836-4ab2-84a9-83c9282dad46",
        "errors": [{
            "code": "SHIPMENTDELIVERY.NOT.AVAILABLE",
            "message": "Shipping service is not available, Please try again."
        }]
    }';

    /**
     * @var string
     */
    protected $newRateQuoteMockResponse = '{
        "transactionId": "02db13cd-1364-4829-be2c-bde1ba5d5f90",
        "errors": [],
        "output": {
            "alerts": [],
            "rateQuote": {
                "orderTotal": 35.08,
                "currency": "USD",
                "rateQuoteDetails": [{
                    "grossAmount": 10.46,
                    "totalDiscountAmount": 0,
                    "netAmount": 10.46,
                    "taxableAmount": 0,
                    "taxAmount": 0,
                    "totalAmount": 10.46,
                    "estimatedVsActual": "ESTIMATED",
                    "deliveryLines": [{
                        "recipientReference": "1",
                        "estimatedDeliveryLocalTime": "2021-09-18",
                        "estimatedShipDate": "2022-07-18",
                        "priceable": false,
                        "deliveryLinePrice": 10.46,
                        "deliveryRetailPrice": 10.46,
                        "deliveryLineType": "SHIPPING",
                        "deliveryDiscountAmount": 0,
                        "recipientContact": {
                            "personName": {
                                "firstName": "Nandhu",
                                "lastName": "Nair"
                            },
                            "company": {
                                "name": "FXO"
                            },
                            "emailDetail": {
                                "emailAddress": "nandhu.nair@igglobal.com"
                            },
                            "phoneNumberDetails": [{
                                "phoneNumber": {
                                    "number": "8986776897"
                                },
                                "usage": "PRIMARY"
                            }]
                        },
                        "shipmentDetails": {
                            "address": {
                                "streetLines": [
                                    "7900 Legacy Dr",
                                    null
                                ],
                                "city": "Plano",
                                "stateOrProvinceCode": "75024",
                                "postalCode": "75024",
                                "countryCode": "US"
                            },
                            "serviceType": "EXPRESS_SAVER"
                        }
                    }],
                    "discounts": []
                },
                {
                    "grossAmount": 34.99,
                    "totalDiscountAmount": 12.25,
                    "netAmount": 22.74,
                    "taxableAmount": 22.74,
                    "taxAmount": 1.88,
                    "totalAmount": 24.62,
                    "estimatedVsActual": "ACTUAL",
                    "productLines": [{
                        "instanceId": "0",
                        "productId": "1463680545590",
                        "unitQuantity": 50,
                        "priceable": true,
                        "unitOfMeasurement": "EACH",
                        "productRetailPrice": 34.99,
                        "productDiscountAmount": 12.25,
                        "productLinePrice": 22.74,
                        "productLineDiscounts": [{
                            "type": "AR_CUSTOMERS",
                            "amount": 12.25
                        }],
                        "productLineDetails": [{
                            "detailCode": "40005",
                            "priceRequired": false,
                            "priceOverridable": false,
                            "description": "Full Pg Clr Flyr 50",
                            "unitQuantity": 1,
                            "quantity": 1,
                            "detailPrice": 22.74,
                            "detailDiscountPrice": 12.25,
                            "detailUnitPrice": 34.99,
                            "detailDiscountedUnitPrice": 12.25,
                            "detailDiscounts": [{
                                "type": "AR_CUSTOMERS",
                                "amount": 12.2465
                            }],
                            "detailCategory": "PRINTING"
                        }],
                        "name": "Fast Order Flyer",
                        "userProductName": "Fast Order Flyer",
                        "type": "PRINT_ORDER"
                    }],
                    "deliveryLines": [{
                        "recipientReference": "1",
                        "priceable": true,
                        "deliveryLinePrice": 0,
                        "deliveryRetailPrice": 0,
                        "deliveryLineType": "PACKING_AND_HANDLING",
                        "deliveryDiscountAmount": 0
                    }],
                    "discounts": [{
                        "type": "AR_CUSTOMERS",
                        "amount": 12.25
                    }],
                    "rateQuoteId": "eyJxdW90ZUl"
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
                    "retailTransactionId": "DNEK3572021092800012",
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
                        "retailTransactionId": "DNEK3572021092800012",
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

    protected $checkoutResponse2 = '{
        "transactionId": "0090062b-4066-4a1f-abd4-6b6e8759a7f1",
        "output": {
            "checkout": {
                "transactionHeader": {
                    "guid": "2c140ead-2974-4eef-ad44-ef47f7b93864",
                    "type": "SALE",
                    "requestDateTime": "2021-09-28 15:50:57",
                    "transactionDateTime": "2021-09-28T10:20:58Z",
                    "retailTransactionId": "DNEK3572021092800012",
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
                            "estimatedShipDate": "2021-09-17",
                            "deliveryLinePrice": "11.09",
                            "deliveryRetailPrice": "11.09",
                            "deliveryLineType": "PICKUP",
                            "deliveryDiscountAmount": "0.0",
                            "estimatedDeliveryLocalTime":"2021-09-19",
                            "estimatedDeliveryDuration": {
                                "value": 3,
                                "unit": "BUSINESSDAYS"
                            },
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
                                "url": "https://staging3.office.fedex.com/rest/V1/fedexoffice/orders/201019760902"
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

    protected $checkoutResponse1 = '{
        "transactionId": "0090062b-4066-4a1f-abd4-6b6e8759a7f1",
        "output": {
        "checkout": {
            "transactionHeader": {
            "guid": "2c140ead-2974-4eef-ad44-ef47f7b93864",
            "type": "SALE",
            "requestDateTime": "2021-09-28 15:50:57",
            "transactionDateTime": "2021-09-28T10:20:58Z",
            "retailTransactionId": "DNEK3572021092800012",
            "fedExCartId": "797c4439-4ce2-45f6-9d86-88345669f5c5",
            "rateQuoteId": "eyJxdW90ZUlkIjoiMzFhY2U1MTktNmNi"
            },
            "lineItems": [
            {
                "type": "PRINT_PRODUCT",
                "retailPrintOrderDetails": [
                {
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
                        "phoneNumberDetails": [
                        {
                            "phoneNumber": {
                            "number": "1234567890"
                            },
                            "usage": "PRIMARY"
                        }
                        ]
                    }
                    },
                    "responsibleCenterDetail": [
                    {
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
                        "phoneNumberDetails": [
                        {
                            "phoneNumber": {
                            "number": "972.731.0997"
                            }
                        }
                        ]
                    }
                    ],
                    "productLines": [
                    {
                        "instanceId": "0",
                        "productId": "1463680545590",
                        "unitQuantity": 50,
                        "unitOfMeasurement": "EACH",
                        "productRetailPrice": "11.09",
                        "productDiscountAmount": "0.0",
                        "productLinePrice": "11.09",
                        "productLineDetails": [
                        {
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
                        }
                        ],
                        "name": "Fast Order Flyer",
                        "userProductName": "Flyers",
                        "type": "PRINT_PRODUCT",
                        "priceable": true
                    }
                    ],
                    "deliveryLines": [
                    {
                        "deliveryLineId": "2810",
                        "recipientReference": "2810",
                        "estimatedShipDate": "2021-09-17",
                        "deliveryLinePrice": "11.09",
                        "deliveryRetailPrice": "11.09",
                        "deliveryLineType": "PICKUP",
                        "deliveryDiscountAmount": "0.0",
                        "estimatedDeliveryDuration": {
                        "value": 3,
                        "unit": "BUSINESSDAYS"
                        },
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
                        "phoneNumberDetails": [
                            {
                            "phoneNumber": {
                                "number": "1234567890"
                            },
                            "usage": "PRIMARY"
                            }
                        ]
                        },
                        "pickupDetails": {
                        "locationName": "0798",
                        "requestedPickupLocalTime": "2021-09-28T15:00:00"
                        },
                        "productAssociation": [
                        {
                            "productRef": "0",
                            "quantity": "50.0"
                        }
                        ],
                        "productTotals": {
                        "productDiscountAmount": "0.0",
                        "productNetAmount": "11.09",
                        "productTaxableAmount": "11.09",
                        "productTaxAmount": "0.91",
                        "productTotalAmount": "12.00"
                        }
                    }
                    ],
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
                        "url": "https://staging3.office.fedex.com/rest/V1/fedexoffice/orders/201019760902/status"
                    }
                    },
                    "origin": {
                    "orderNumber": "2010197609025497",
                    "orderClient": "MAGENTO",
                    "apiCustomer": "l7e4acbdd6b7d341b0b59234bbdbd4e82e"
                    }
                }
                ]
            }
            ],
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
            "phoneNumberDetails": [
                {
                "phoneNumber": {
                    "number": "1234567890"
                },
                "usage": "PRIMARY"
                }
            ]
            },
            "tenders": [
            {
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
            }
            ],
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

    protected Auth|MockObject $baseAuthMock;
    protected MockObject|PublicCookieMetadata $cookieMetadata;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

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

        $this->orderCollectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->orderCollectionMock = $this->createMock(Collection::class);

        $this->quoteRepositoryMock = $this->createMock(CartRepositoryInterface::class);

        $this->convertOrder = $this->getMockBuilder(ConvertOrder::class)
            ->setMethods([
                'canInvoice',
                'itemToShipmentItem',
                'addStatusHistoryComment',
                'setIsCustomerNotified',
                'save',
                'getCustomTaxAmount',
                'getBaseGrandTotal',
                'getGrandTotal',
                'getId',
                'getBaseCurrency',
                'toShipment'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteManagement = $this->getMockBuilder(QuoteManagement::class)
            ->setMethods(['submit'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentFactory = $this->getMockBuilder(PaymentFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->payment = $this->getMockBuilder(Payment::class)
            ->setMethods(['setMethod', 'setLastTransId', 'setTransactionId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->invoiceService = $this->getMockBuilder(InvoiceService::class)
            ->setMethods(['prepareInvoice'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionFactory = $this->getMockBuilder(TransactionFactory::class)
            ->setMethods(['create', 'addObject', 'save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->producingAddress = $this->getMockBuilder(ProducingAddressFactory::class)
            ->setMethods(['create', 'addData', 'save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cartSummaryMock = $this->getMockBuilder(CartSummary::class)
            ->setMethods([
                'getUpdateContinueShoppingCtaToggle',
                'getAllPrintProductUrl'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->order = $this->getMockBuilder(Order::class)
            ->setMethods([
                'load',
                'loadByIncrementId',
                'getShipmentsCollection',
                'setData',
                'canInvoice',
                'addStatusHistoryComment',
                'setIsCustomerNotified',
                'save',
                'getCustomTaxAmount',
                'getBaseGrandTotal',
                'getGrandTotal',
                'getId',
                'getPayment',
                'getBaseCurrency',
                'formatTxt',
                'hasShipments',
                'toShipment',
                'submit',
                'setStatus',
                'canShip',
                'getAllItems',
                'getSubtotal',
                'setSubtotal',
                'setBaseSubtotal',
                'setGrandTotal',
                'setBaseGrandTotal',
                'setTotalPaid',
                'setBaseTotalPaid',
                'getShippingDescription',
                'getStatus'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderInterface = $this->getMockBuilder(OrderInterface::class)
            ->setMethods([
                'load',
                'getShipmentsCollection',
                'setData',
                'canInvoice',
                'addStatusHistoryComment',
                'setIsCustomerNotified',
                'save',
                'getCustomTaxAmount',
                'getBaseGrandTotal',
                'getGrandTotal',
                'getId',
                'getPayment',
                'getBaseCurrency',
                'formatTxt',
                'toShipment',
                'submit',
                'setStatus',
                'canShip',
                'getAllItems',
                'setTotalPaid',
                'setBaseTotalPaid'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cart = $this->getMockBuilder(Cart::class)
            ->setMethods(['getQuote', 'truncate'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods([
                'getAllItems',
                'getShippingAddress',
                'save',
                'getIsFromShipping',
                'getIsFromPickup',
                'getIsFromFedexAccount',
                'getAllVisibleItems',
                'getCustomerPickupLocationData',
                'setData',
                'getId',
                'getGtn',
                'getData',
                'setPayment',
                'getPayment',
                'importData',
                'setCustomerId',
                'getBillingAddress',
                'getShippingMethod',
                'setShippingMethod',
                'getEmail',
                'setCustomerEmail',
                'getCustomerFirstname',
                'getCustomerLastname',
                'getMiddlename',
                'setCustomerMiddlename',
                'getFirstname',
                'getLastname',
                'setCustomerFirstname',
                'setCustomerLastname',
                'setCustomerIsGuest',
                'getSiteConfiguredPaymentUsed',
                'getShippingDescription',
                'setShippingDescription',
                'getSubtotal',
                'getGrandTotal',
                'getCustomerEmail',
                'getCustomerId',
                'setCustomerGroupId',
                'setCollectShippingRates',
                'setIsActive'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->item = $this->getMockBuilder(Item::class)
            ->setMethods([
                'saveItemOptions',
                'setCustomPrice',
                'setQty',
                'setRowTotal',
                'setOriginalCustomPrice',
                'setInstanceId',
                'save',
                'setDiscount',
                'setBaseRowTotal',
                'setIsSuperMode',
                'getOptionByCode',
                'removeOption',
                'getQty',
                'getProduct',
                'getQtyToShip',
                'getIsVirtual',
                'getLockedDoShip'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->setMethods(['getOptionByCode', 'setName', 'save'])
            ->disableOriginalconstructor()
            ->getMock();

        $this->option = $this->getMockBuilder(QuoteItemOption::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->shipment = $this->getMockBuilder(Shipment::class)
            ->setMethods([
                "setData",
                "getId",
                "setShipmentStatus",
                "addTrack",
                "getTracks",
                "loadByIncrementId",
                "register",
                "getOrder",
                "setIsInProcess",
                "save",
                "addItem",
                "setQty"
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->shipmentItem = $this->getMockBuilder(ShipmentItem::class)
            ->setMethods(['setQty'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->invoiceMock = $this->getMockBuilder(Invoice::class)
            ->setMethods(['register', 'save', 'getOrder'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryHelper = $this->getMockBuilder(DeliveryHelper::class)
            ->setMethods(['isCommercialCustomer', 'getCustomer'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->addressInterface = $this->getMockBuilder(AddressInterface::class)
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

        $this->quote->method('getShippingAddress')->willReturn($this->addressInterface);
        $this->addressInterface->method('setCollectShippingRates')->willReturnSelf();

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->setMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->setMethods([
                'isLoggedIn',
                'getCustomerId',
                'getCustomerCompany',
                'getCustomAttribute',
                'getValue',
                'getCustomer',
                'getGroupId',
                'create',
                'getOnBehalfOf',
                'clearQuote',
                'unsAll',
                'clearStorage',
                'setRetailTransactionId',
                'getRetailTransactionId',
                'unsRetailTransactionId'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->cookieManagerMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->setMethods(['setPublicCookie', 'getCookie'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->setMethods(['dispatch'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cookieMetadataFactoryMock = $this->getMockBuilder(CookieMetadataFactory::class)
            ->setMethods(['createPublicCookieMetadata', 'setDuration', 'setSecure', 'setPath', 'setHttpOnly'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->ssoConfiguration = $this->getMockBuilder(SsoConfiguration::class)
            ->setMethods(['isFclCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(['getIsSdeStore'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->setMethods([
                'getOrderInProgress',
                'setOrderInProgress',
                'unsOrderInProgress',
                'getRateQuoteResponse',
                'setRateQuoteResponse',
                'unsRateQuoteResponse',
                'getCustomShippingCarrierCode',
                'getCustomShippingMethodCode',
                'getCustomShippingTitle',
                'clearQuote',
                'unsAll',
                'clearStorage'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->localizedException = $this->getMockBuilder(LocalizedException::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->setMethods(['create', 'load'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->optimizeItemInstanceHelper = $this->getMockBuilder(OptimizeItemInstanceHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['pushQuoteIdQueue', 'pushOrderIdInQueueForShipmentCreation'])
            ->getMock();
        $this->reorderInstanceHelper = $this->getMockBuilder(ReorderInstanceHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['pushOrderIdInQueue'])
            ->getMock();

        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->requestQueryValidatorMock = $this->createMock(RequestQueryValidator::class);
        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->setMethods(
                [
                    'getConnection',
                    'select',
                    'from',
                    'where',
                    'fetchRow',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributeRepositoryInterface = $this->getMockBuilder(AttributeRepositoryInterface::class)
            ->setMethods(['get', 'getAttributeId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->timezone = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shipmentHelper = $this->getMockBuilder(ShipmentHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasShipmentCreated'])
            ->getMock();

        $this->orderApprovalViewModel = $this->getMockBuilder(OrderApprovalViewModel::class)
            ->setMethods(['isOrderApprovalB2bEnabled'])
            ->disableOriginalConstructor()
            ->getMock();


        $this->objectManager = new ObjectManager($this);
        $this->helperData = $this->objectManager->getObject(
            Data::class,
            [
                'context' => $this->contextMock,
                'orderRepository' => $this->orderRepository,
                'convertOrder' => $this->convertOrder,
                'quoteManagement' => $this->quoteManagement,
                'paymentFactory' => $this->paymentFactory,
                'logger' => $this->logger,
                'invoiceService' => $this->invoiceService,
                'transactionFactory' => $this->transactionFactory,
                'producingAddress' => $this->producingAddress,
                'orderCollection' => $this->order,
                'toggleConfig' => $this->toggleConfigMock,
                'helper' => $this->deliveryHelper,
                'customerRepository' => $this->customerRepositoryMock,
                'customerSession' => $this->customerSessionMock,
                'ssoConfiguration' => $this->ssoConfiguration,
                'cookieManager' => $this->cookieManagerMock,
                'cookieMetadataFactory' => $this->cookieMetadataFactoryMock,
                'orderCollectionFactory' => $this->orderCollectionFactoryMock,
                'checkoutSession' => $this->checkoutSessionMock,
                'sdeHelper' => $this->sdeHelper,
                'reorderInstanceHelper' => $this->reorderInstanceHelper,
                'optimizeItemInstanceHelper' => $this->optimizeItemInstanceHelper,
                'quoteFactory' => $this->quoteFactory,
                'cart' => $this->cart,
                '_eventManager' => $this->eventManager,
                'quoteItem' => $this->quoteItem,
                'option' => $this->option,
                'request' => $this->requestMock,
                'requestQueryValidator' => $this->requestQueryValidatorMock,
                'resourceConnection' => $this->resourceConnection,
                'attributeRepositoryInterface' => $this->attributeRepositoryInterface,
                'timezone' => $this->timezone,
                'shipmentHelper' => $this->shipmentHelper,
                'authHelper' => $this->baseAuthMock,
                'orderApprovalViewModel' => $this->orderApprovalViewModel,
                'quoteRepository' => $this->quoteRepositoryMock,
                'cartSummary' => $this->cartSummaryMock
            ]
        );
    }

    /**
     * @test testIsDuplicateOrder
     */
    public function testIsDuplicateOrder()
    {
        $this->orderCollectionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->orderCollectionMock);
        $this->orderCollectionMock->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->orderCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->orderCollectionMock->expects($this->any())->method('getSize')->willReturn(1);
        $this->assertEquals(true, $this->helperData->isDuplicateOrder(self::QUOTE_ID));
    }

    /**
     * @test testIsDuplicateOrderWithSizeZero
     */
    public function testIsDuplicateOrderWithSizeZero()
    {
        $this->orderCollectionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->orderCollectionMock);
        $this->orderCollectionMock->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->orderCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->orderCollectionMock->expects($this->any())->method('getSize')->willReturn(0);
        $this->checkoutSessionMock->expects($this->once())->method('getOrderInProgress')->willReturn(false);
        $this->checkoutSessionMock->expects($this->once())->method('setOrderInProgress')->willReturn(true);

        $this->assertEquals(false, $this->helperData->isDuplicateOrder(self::QUOTE_ID));
    }

    /**
     * @test testIsDuplicateOrderWithException
     */
    public function testIsDuplicateOrderWithException()
    {
        $this->orderCollectionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->orderCollectionMock);
        $this->orderCollectionMock->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->orderCollectionMock->expects($this->any())->method('addFieldToFilter')
            ->willThrowException(new \Exception());

        $this->assertEquals(false, $this->helperData->isDuplicateOrder(self::QUOTE_ID));
    }

    /**
     * @test testPlaceOrderWithFedexPaymentMethod
     */
    public function testPlaceOrderWithFedexPaymentMethod()
    {
        $paymentData = (object)[
            "fedexAccountNumber" => self::FEDEX_ACCOUNT_NUMBER,
            "paymentMethod" => 'fedex',
            "poReferenceId" => '1234',
            "number" => '8599'
        ];
        $paymentMethod = 'fedexaccount';
        $importData = ['method' => $paymentMethod];

        $this->quote->expects($this->any())->method('getId')->willReturn(123);
        $this->testGetPaymentParametersDataWithFedexPaymentMethod();
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(true);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->customerSessionMock->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getGroupId')->willReturnSelf();
        $this->quote->expects($this->any())->method('setCustomerGroupId')->willReturnSelf();
        $this->paymentFactory->expects($this->any())->method('create')->willreturn($this->payment);
        $this->payment->expects($this->any())->method('setMethod')->with($paymentMethod)->willReturnSelf();
        $this->quote->expects($this->any())->method('setPayment')->with($this->payment)->willReturnSelf();
        $this->quote->expects($this->any())->method('getPayment')->willReturnSelf();
        $this->quote->expects($this->any())->method('importData')->with($importData)->willReturnSelf();
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->deliveryHelper->expects($this->any())->method('getCustomer')->willReturnSelf(true);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterface);
        $this->addressInterface->expects($this->any())->method('setCollectShippingRates')->willReturnSelf();
        $this->addressInterface->expects($this->any())->method('collectShippingRates')->willReturnSelf();
        $this->addressInterface->expects($this->any())->method('getShippingMethod')->willReturn('TX_Flat');
        $this->addressInterface->expects($this->any())->method('getShippingDescription')->willReturn('Test');
        $this->addressInterface->expects($this->any())->method('setShippingMethod')->willReturn('TX_Flat');
        $this->testUpdateCustomerInformation();
        $this->testVerifyQuoteIntegrity();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $productArr = ['external_prod' => [0 => ['userProductName' => 'Flyer']]];
        $this->quote->expects($this->any())->method("getAllItems")->willReturn([0 => $this->quoteItem]);
        $this->quoteItem->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($productArr));
        $this->quoteItem->expects($this->any())->method('save')->willReturnSelf();

        $this->testCreateOrderFromQuote();
        $this->order->expects($this->any())->method('canShip')->willReturn(true);
        $this->convertOrder->expects($this->any())->method('toShipment')->willReturn($this->shipment);
        $this->order->expects($this->any())->method("getAllItems")->willReturn([$this->item]);
        $this->item->expects($this->any())->method("getQtyToShip")->willReturn(33);
        $this->item->expects($this->any())->method("getIsVirtual")->willReturn(true);
        $this->item->expects($this->any())->method("getQtyToShip")->willReturn(3);
        $this->convertOrder->expects($this->any())->method('itemToShipmentItem')->willReturn($this->shipmentItem);
        $this->shipmentItem->expects($this->any())->method('setQty')->with(33)->willReturnSelf();
        $this->shipment->expects($this->any())->method('register')->willReturnSelf();
        $this->shipment->expects($this->any())->method('getOrder')->willReturnSelf();
        $this->shipment->expects($this->any())->method('setIsInProcess')->with(false)->willReturnSelf();
        $this->shipment->expects($this->exactly(2))->method('setData')->willReturnSelf();
        $this->shipment->expects($this->any())->method('setShipmentStatus')->with(1)->willReturnSelf();
        $this->shipment->expects($this->any())->method('save')->willReturnSelf();
        $this->shipment->expects($this->any())->method('getOrder')->willReturnSelf();
        $this->order->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->any())->method('setFedexAccountNumber')
            ->with(self::FEDEX_ACCOUNT_NUMBER)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setRetailTransactionId')->with(22)
            ->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('save')->willReturnSelf();
        $this->testGenerateInvoice();

        $this->assertNotNull($this->helperData->placeOrder($this->quote, 13, 22, 22, ['test' => 'data'], $paymentData));
    }

    /**
     * @test testPlaceOrder
     */
    public function testPlaceOrderWithoutVirtual()
    {
        $paymentData = (object)[
            "fedexAccountNumber" => self::FEDEX_ACCOUNT_NUMBER,
            "paymentMethod" => 'fedex',
            "poReferenceId" => '1234',
            "number" => '8599'
        ];
        $paymentMethod = 'fedexaccount';
        $importData = ['method' => $paymentMethod];

        $this->quote->expects($this->any())->method('getId')->willReturn(123);
        $this->testGetPaymentParametersDataWithFedexPaymentMethod();
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(true);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->customerSessionMock->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getGroupId')->willReturnSelf();
        $this->quote->expects($this->any())->method('setCustomerGroupId')->willReturnSelf();
        $this->paymentFactory->expects($this->any())->method('create')->willreturn($this->payment);
        $this->payment->expects($this->any())->method('setMethod')->with($paymentMethod)->willReturnSelf();
        $this->quote->expects($this->any())->method('setPayment')->with($this->payment)->willReturnSelf();
        $this->quote->expects($this->any())->method('getPayment')->willReturnSelf();
        $this->quote->expects($this->any())->method('importData')->with($importData)->willReturnSelf();
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->deliveryHelper->expects($this->any())->method('getCustomer')->willReturnSelf(true);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterface);
        $this->addressInterface->expects($this->any())->method('setCollectShippingRates')->willReturnSelf();
        $this->addressInterface->expects($this->any())->method('collectShippingRates')->willReturnSelf();
        $this->addressInterface->expects($this->any())->method('getShippingMethod')->willReturn('TX_Flat');
        $this->addressInterface->expects($this->any())->method('getShippingDescription')->willReturn('Test');
        $this->addressInterface->expects($this->any())->method('setShippingMethod')->willReturn('TX_Flat');
        $this->testUpdateCustomerInformation();
        $this->testVerifyQuoteIntegrity();
        $this->quote->expects($this->any())->method("getAllItems")->willReturn([]);
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->testCreateOrderFromQuote();
        $this->order->expects($this->any())->method('canShip')->willReturn(true);
        $this->convertOrder->expects($this->any())->method('toShipment')->willReturn($this->shipment);
        $this->order->expects($this->any())->method("getAllItems")->willReturn([$this->item]);
        $this->item->expects($this->any())->method("getQtyToShip")->willReturn(33);
        $this->item->expects($this->any())->method("getIsVirtual")->willReturn(false);
        $this->item->expects($this->any())->method("getQtyToShip")->willReturn(3);
        $this->convertOrder->expects($this->any())->method('itemToShipmentItem')->willReturn($this->shipmentItem);
        $this->shipmentItem->expects($this->any())->method('setQty')->with(33)->willReturnSelf();
        $this->shipment->expects($this->any())->method('register')->willReturnSelf();
        $this->shipment->expects($this->any())->method('getOrder')->willReturnSelf();
        $this->shipment->expects($this->any())->method('setIsInProcess')->with(false)->willReturnSelf();
        $this->shipment->expects($this->exactly(2))->method('setData')->willReturnSelf();
        $this->shipment->expects($this->any())->method('setShipmentStatus')->with(1)->willReturnSelf();
        $this->shipment->expects($this->any())->method('save')->willReturnSelf();
        $this->shipment->expects($this->any())->method('getOrder')->willReturnSelf();
        $this->order->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->any())->method('setFedexAccountNumber')
            ->with(self::FEDEX_ACCOUNT_NUMBER)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setRetailTransactionId')->with(22)
            ->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('save')->willReturnSelf();
        $this->testGenerateInvoice();

        $this->assertNotNull($this->helperData->placeOrder($this->quote, 13, 22, 22, ['test' => 'data'], $paymentData));
    }

    /**
     * @test testPlaceOrderWithCatchException
     */
    public function testPlaceOrderWithCatchException()
    {
        $paymentData = (object)[
            "fedexAccountNumber" => self::FEDEX_ACCOUNT_NUMBER,
            "paymentMethod" => 'fedex',
            "poReferenceId" => '1234',
            "number" => '8599'
        ];
        $paymentMethod = 'fedexaccount';
        $importData = ['method' => $paymentMethod];

        $this->quote->expects($this->any())->method('getId')->willReturn(123);
        $this->testGetPaymentParametersDataWithFedexPaymentMethod();
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(true);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->customerSessionMock->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getGroupId')->willReturnSelf();
        $this->quote->expects($this->any())->method('setCustomerGroupId')->willReturnSelf();
        $this->paymentFactory->expects($this->any())->method('create')->willreturn($this->payment);
        $this->payment->expects($this->any())->method('setMethod')->with($paymentMethod)->willReturnSelf();
        $this->quote->expects($this->any())->method('setPayment')->with($this->payment)->willReturnSelf();
        $this->quote->expects($this->any())->method('getPayment')->willReturnSelf();
        $this->quote->expects($this->any())->method('importData')->with($importData)->willReturnSelf();
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->deliveryHelper->expects($this->any())->method('getCustomer')->willReturnSelf(true);
        $this->quote->expects($this->any())->method("getAllItems")->willReturn([]);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterface);
        $this->addressInterface->expects($this->any())->method('setCollectShippingRates')->willReturnSelf();
        $this->addressInterface->expects($this->any())->method('collectShippingRates')->willReturnSelf();
        $this->addressInterface->expects($this->any())->method('getShippingMethod')->willReturn('TX_Flat');
        $this->addressInterface->expects($this->any())->method('getShippingDescription')->willReturn('Test');
        $this->addressInterface->expects($this->any())->method('setShippingMethod')->willReturn('TX_Flat');
        $this->testUpdateCustomerInformation();
        $this->testVerifyQuoteIntegrity();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->testCreateOrderFromQuote();
        $this->order->expects($this->any())->method('canShip')->willReturn(true);
        $this->convertOrder->expects($this->any())->method('toShipment')->willReturn($this->shipment);
        $this->order->expects($this->any())->method("getAllItems")->willReturn([$this->item]);
        $this->item->expects($this->any())->method("getQtyToShip")->willReturn(false);
        $this->item->expects($this->any())->method("getIsVirtual")->willReturn(true);
        $this->item->expects($this->any())->method("getQtyToShip")->willReturn(3);
        $this->convertOrder->expects($this->any())->method('itemToShipmentItem')->willReturn($this->shipmentItem);
        $this->shipmentItem->expects($this->any())->method('setQty')->with(33)->willReturnSelf();
        $this->shipment->expects($this->any())->method('register')->willReturnSelf();
        $this->shipment->expects($this->any())->method('getOrder')->willReturnSelf();
        $this->shipment->expects($this->any())->method('setIsInProcess')->with(false)->willReturnSelf();
        $this->shipment->expects($this->exactly(2))->method('setData')->willReturnSelf();
        $this->shipment->expects($this->any())->method('setShipmentStatus')->with(1)->willReturnSelf();
        $this->shipment->expects($this->any())->method('save')->willThrowException(new \Exception());
        $this->testGenerateInvoice();
        $this->helperData->placeOrder($this->quote, 13, 22, 22, ['test' => 'data'], $paymentData);
    }

    /**
     * @test testPlaceOrder
     */
    public function testPlaceOrderWithCCPaymentMethod()
    {
        $paymentData = (object)[
            "fedexAccountNumber" => self::FEDEX_ACCOUNT_NUMBER,
            "paymentMethod" => 'cc',
            "poReferenceId" => '1234',
            "number" => '8599'
        ];
        $paymentMethod = 'fedexccpay';
        $importData = ['method' => $paymentMethod];

        $this->quote->expects($this->any())->method('getId')->willReturn(123);
        $this->testGetPaymentParametersDataWithCCPaymentMethod();
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(false);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->customerSessionMock->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getGroupId')->willReturnSelf();
        $this->quote->expects($this->any())->method('setCustomerGroupId')->willReturnSelf();
        $this->paymentFactory->expects($this->any())->method('create')->willreturn($this->payment);
        $this->payment->expects($this->any())->method('setMethod')->with($paymentMethod)->willReturnSelf();
        $this->quote->expects($this->any())->method('setPayment')->with($this->payment)->willReturnSelf();
        $this->quote->expects($this->any())->method('getPayment')->willReturnSelf();
        $this->quote->expects($this->any())->method('importData')->with($importData)->willReturnSelf();
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->deliveryHelper->expects($this->any())->method('getCustomer')->willReturnSelf(true);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterface);
        $this->addressInterface->expects($this->any())->method('setCollectShippingRates')->willReturnSelf();
        $this->addressInterface->expects($this->any())->method('collectShippingRates')->willReturnSelf();
        $this->addressInterface->expects($this->any())->method('getShippingMethod')->willReturn('TX_Flat');
        $this->addressInterface->expects($this->any())->method('getShippingDescription')->willReturn('Test');
        $this->addressInterface->expects($this->any())->method('setShippingMethod')->willReturn('TX_Flat');
        $this->testUpdateCustomerInformation();
        $this->testVerifyQuoteIntegrity();
        $this->quote->expects($this->any())->method("getAllItems")->willReturn([]);
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->testCreateOrderFromQuote();
        $this->order->expects($this->any())->method('canShip')->willReturn(true);
        $this->convertOrder->expects($this->any())->method('toShipment')->willReturn($this->shipment);
        $this->order->expects($this->any())->method("getAllItems")->willReturn([$this->item]);
        $this->item->expects($this->any())->method("getQtyToShip")->willReturn(false);
        $this->item->expects($this->any())->method("getIsVirtual")->willReturn(true);
        $this->item->expects($this->any())->method("getQtyToShip")->willReturn(3);
        $this->convertOrder->expects($this->any())->method('itemToShipmentItem')->willReturn($this->shipmentItem);
        $this->shipmentItem->expects($this->any())->method('setQty')->with(33)->willReturnSelf();
        $this->shipment->expects($this->any())->method('register')->willReturnSelf();
        $this->shipment->expects($this->any())->method('getOrder')->willReturnSelf();
        $this->shipment->expects($this->any())->method('setIsInProcess')->with(false)->willReturnSelf();
        $this->shipment->expects($this->exactly(2))->method('setData')->willReturnSelf();
        $this->shipment->expects($this->any())->method('setShipmentStatus')->with(1)->willReturnSelf();
        $this->shipment->expects($this->any())->method('save')->willReturnSelf();
        $this->shipment->expects($this->any())->method('getOrder')->willReturnSelf();
        $this->order->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->any())->method('setFedexAccountNumber')
            ->with(self::FEDEX_ACCOUNT_NUMBER)->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('setRetailTransactionId')->with(22)
            ->willReturnSelf();
        $this->orderPaymentInterface->expects($this->any())->method('save')->willReturnSelf();
        $this->testGenerateInvoice();
        $this->assertNotNull($this->helperData->placeOrder($this->quote, 13, 22, 22, ['test' => 'data'], $paymentData));
    }

    /**
     * @test testGetPaymentParametersDataWithFedexPaymentMethod
     */
    public function testGetPaymentParametersDataWithFedexPaymentMethod()
    {
        $paymentData = (object)[
            "fedexAccountNumber" => self::FEDEX_ACCOUNT_NUMBER,
            "paymentMethod" => 'fedex',
            "poReferenceId" => '1234',
            "number" => '8599'
        ];

        $this->quote->expects($this->any())->method('getData')->willReturn(123456);
        $this->quote->expects($this->any())->method('getSiteConfiguredPaymentUsed')->willReturn(true);

        $this->assertNotNull($this->helperData->getPaymentParametersData($this->quote, $paymentData));
    }

    /**
     * @test testGetPaymentParametersDataWithCCPaymentMethod
     */
    public function testGetPaymentParametersDataWithCCPaymentMethod()
    {
        $paymentData = (object)[
            "fedexAccountNumber" => self::FEDEX_ACCOUNT_NUMBER,
            "paymentMethod" => 'cc',
            "poReferenceId" => '1234',
            "number" => '8599',
            "nameOnCard" => 'Ayush'
        ];

        $this->quote->expects($this->any())->method('getData')->willReturn(123456);
        $this->quote->expects($this->any())->method('getSiteConfiguredPaymentUsed')->willReturn(true);

        $this->assertNotNull($this->helperData->getPaymentParametersData($this->quote, $paymentData));
    }

    /**
     * @test testGetPaymentParametersDataWithInStorePaymentMethod
     */
    public function testGetPaymentParametersDataWithInStorePaymentMethod()
    {
        $paymentData = (object)[
            "fedexAccountNumber" => self::FEDEX_ACCOUNT_NUMBER,
            "paymentMethod" => 'instore',
            "poReferenceId" => '1234',
            "number" => '8599'
        ];

        $this->quote->expects($this->any())->method('getData')->willReturn(123456);
        $this->quote->expects($this->any())->method('getSiteConfiguredPaymentUsed')->willReturn(true);

        $this->assertNotNull($this->helperData->getPaymentParametersData($this->quote, $paymentData));
    }

    /**
     * @test testUpdateCustomerInformation
     */
    public function testUpdateCustomerInformation()
    {
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(false);
        $this->quote->expects($this->any())->method('setCustomerId')->with(null)->willReturnSelf();
        $this->quote->expects($this->any())->method('setCustomerIsGuest')->with(true)->willReturnSelf();
        $this->quote->expects($this->any())->method('getCustomerEmail')->willReturn(null);
        $this->quote->expects($this->any())->method('getBillingAddress')->willReturnSelf();
        $this->quote->expects($this->any())->method('getEmail')->willReturn('abc@abc.com');
        $this->quote->expects($this->any())->method('setCustomerEmail')->willReturnSelf();
        $this->quote->expects($this->any())->method('getCustomerFirstname')->willReturn(null);
        $this->quote->expects($this->any())->method('getCustomerFirstname')->willReturn(null);
        $this->quote->expects($this->any())->method('getFirstname')->willReturn('abc');
        $this->quote->expects($this->any())->method('setCustomerFirstname')->with('abc')->willreturnSelf();
        $this->quote->expects($this->any())->method('getLastname')->willReturn('xyz');
        $this->quote->expects($this->any())->method('setCustomerLastname')->with('xyz')->willreturnSelf();
        $this->quote->expects($this->any())->method('getMiddlename')->willReturn(null);
        $this->quote->expects($this->any())->method('setCustomerMiddlename')->willreturnSelf();

        $this->assertNotNull($this->helperData->updateCustomerInformation($this->quote));
    }


    /**
     * @test testCreateOrderFromQuote
     */
    public function testCreateOrderFromQuote()
    {
        $this->requestQueryValidatorMock->expects($this->any())->method('isGraphQlRequest')->willReturn(true);
        $this->quoteManagement->expects($this->any())->method('submit')->with($this->quote)->willReturn($this->order);
        $this->checkoutSessionMock->expects($this->any())
            ->method('getRateQuoteResponse')
            ->willReturn($this->output);

        $this->order->expects($this->any())->method('getSubtotal')->willReturn(12);

        $this->quote->expects($this->any())->method('getSubtotal')->willReturnOnConsecutiveCalls('21', '22');
        $this->order->expects($this->any())->method('setSubtotal')->willReturnSelf();
        $this->order->expects($this->any())->method('setBaseSubtotal')->willReturnSelf();

        $this->order->expects($this->any())->method('getGrandTotal')->willReturn(12);
        $this->quote->expects($this->any())->method('getGrandTotal')->willReturn(21);
        $this->order->expects($this->any())->method('setGrandTotal')->willReturnSelf();
        $this->order->expects($this->any())->method('setBaseGrandTotal')->willReturnSelf();
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterface);

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->order->expects($this->any())->method('setStatus')->with('pending')->willReturnSelf();
        $this->order->expects($this->any())->method('save')->willReturnSelf();

        $this->assertNotNull($this->helperData->createOrderFromQuote($this->quote));
    }

    /**
     * @test testCreateOrderFromQuote with Pending Approval status
     */
    public function testCreateOrderFromQuoteWithPendingApprovalStatus()
    {
        $this->quoteManagement->expects($this->any())->method('submit')->with($this->quote)->willReturn($this->order);
        $this->checkoutSessionMock->expects($this->any())
            ->method('getRateQuoteResponse')
            ->willReturn($this->output);
        $this->order->expects($this->any())->method('save')->willReturnSelf();
        $this->orderApprovalViewModel->expects($this->any())->method('isOrderApprovalB2bEnabled')
        ->willReturn(true);
        $this->order->expects($this->any())->method('setStatus')->with('pending_approval')->willReturnSelf();

        $this->assertNotNull($this->helperData->createOrderFromQuote($this->quote));
    }

    /**
     * @test testCreateOrderFromQuote
     */
    public function testCreateOrderFromQuoteWithFalse()
    {
        $this->quoteManagement->expects($this->any())->method('submit')->with($this->quote)->willReturn($this->order);

        $this->order->expects($this->any())->method('getSubtotal')->willReturn(12);
        $this->quote->expects($this->any())->method('getSubtotal')->willReturnOnConsecutiveCalls('21', '22');
        $this->order->expects($this->any())->method('setSubtotal')->willReturnSelf();
        $this->order->expects($this->any())->method('setBaseSubtotal')->willReturnSelf();

        $this->order->expects($this->any())->method('getGrandTotal')->willReturn(12);
        $this->quote->expects($this->any())->method('getGrandTotal')->willReturn(21);
        $this->order->expects($this->any())->method('setGrandTotal')->willReturnSelf();
        $this->order->expects($this->any())->method('setBaseGrandTotal')->willReturnSelf();

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->order->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->order->expects($this->any())->method('save')->willReturnSelf();

        $this->assertNotNull($this->helperData->createOrderFromQuote($this->quote));
    }

    /**
     * test testIsSetOrderIdIf
     */
    public function testIsSetOrderIdIf()
    {
        $orderNumber = 1234;
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->quote->expects($this->any())->method("setData")->willReturn($this->quote);
        $this->quote->expects($this->any())->method('save')->willReturn($this->quote);

        $this->assertEquals(true, $this->helperData->isSetOrderId($this->quote, $orderNumber));
    }

    /**
     * test testIsSetOrderIdIfCatchStatement
     */
    public function testIsSetOrderIdIfCatchStatement()
    {
        $orderNumber = 1234;
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->quote->expects($this->any())->method("setData")->willReturn($this->quote);
        $this->quote->expects($this->any())->method('save')->willThrowException(new \Exception());

        $this->assertEquals(false, $this->helperData->isSetOrderId($this->quote, $orderNumber));
    }

    /**
     * test GenerateInvoice
     */
    public function testGenerateInvoice()
    {
        $orderId = 12;
        $this->orderRepository->expects($this->any())->method('get')->willReturn($this->order);
        $this->order->expects($this->any())->method('getId')->willReturn(true);
        $this->order->expects($this->any())->method('canInvoice')->willReturn(true);

        $this->invoiceService->expects($this->any())->method('prepareInvoice')->willReturn($this->invoiceMock);
        $this->invoiceMock->expects($this->any())->method('register')->willReturnSelf();
        $this->invoiceMock->expects($this->any())->method('save')->willReturnSelf();
        $this->invoiceMock->expects($this->any())->method('getOrder')->willReturn($this->order);

        $this->transactionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->transactionFactory->expects($this->any())->method('addObject')->willReturnSelf();
        $this->transactionFactory->expects($this->any())->method('save')->willReturnSelf();

        $this->order->expects($this->any())->method('load')->willReturnSelf();
        $this->order->expects($this->any())->method('setTotalPaid')->willReturnSelf();

        $this->assertEquals(null, $this->helperData->generateInvoice($orderId));
    }

    /*Genrate Invoice With Exception*/

    public function testGenerateInvoiceWithException()
    {
        $orderID = 12;
        $exception = new \Magento\Framework\Exception\NoSuchEntityException();
        $this->orderRepository->expects($this->any())->method('get')->willThrowException($exception);

        $this->order->expects($this->any())->method('getId')->willReturn(true);
        $this->order->expects($this->any())->method('canInvoice')->willReturn(true);

        $this->invoiceService->expects($this->any())->method('prepareInvoice')->willReturn($this->invoiceMock);
        $this->invoiceMock->expects($this->any())->method('register')->willReturnSelf();
        $this->invoiceMock->expects($this->any())->method('save')->willReturnSelf();
        $this->invoiceMock->expects($this->any())->method('getOrder')->willReturn($this->order);

        $this->transactionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->transactionFactory->expects($this->any())->method('addObject')->willReturnSelf();
        $this->transactionFactory->expects($this->any())->method('save')->willReturnSelf();

        $this->assertEquals(null, $this->helperData->generateInvoice($orderID));
    }

    /**
     * generate order producing address from checkout response
     */
    public function testPrepareOrderProducingAddress()
    {
        $orderId = 121;
        $this->order->expects($this->any())->method('load')->willReturnSelf();
        $this->order->expects($this->any())->method('getShipmentsCollection')->willReturn([$this->order]);
        $this->order->expects($this->any())->method('getId')->willReturn(1);
        $this->producingAddress->expects($this->any())->method('create')->willReturnSelf();
        $this->producingAddress->expects($this->any())->method('addData')->willReturnSelf();
        $this->producingAddress->expects($this->any())->method('save')->willReturnSelf();

        $response = $this->helperData->prepareOrderProducingAddress($this->checkoutResponse, $orderId);
        $this->assertSame(null, $response);
    }

    /**
     * generate order producing address from checkout response with estimate shipping address
     */
    public function testPrepareOrderProducingAddressWithShipDate()
    {
        $orderId = 121;
        $this->order->expects($this->any())->method('load')->willReturnSelf();
        $this->order->expects($this->any())->method('getShipmentsCollection')->willReturn($this->order);
        $this->order->expects($this->any())->method('getId')->willReturn(1);
        $this->producingAddress->expects($this->any())->method('create')->willReturnSelf();
        $this->producingAddress->expects($this->any())->method('addData')->willReturnSelf();

        $response = $this->helperData->prepareOrderProducingAddress($this->checkoutResponse1, $orderId);
        $this->assertSame(null, $response);
    }

    public function testPrepareOrderProducingAddressWithEstimatedDeliveryLocalTime()
    {
        $orderId = 121;

        $this->order->expects($this->any())->method('load')->willReturnSelf();
        $this->order->expects($this->any())->method('getShipmentsCollection')->willReturn($this->order);
        $this->order->expects($this->any())->method('getId')->willReturn(1);
        $this->producingAddress->expects($this->any())->method('create')->willReturnSelf();
        $this->producingAddress->expects($this->any())->method('addData')->willReturnSelf();
        $this->producingAddress->expects($this->any())->method('save')->willReturnSelf();
        $response = $this->helperData->prepareOrderProducingAddress($this->checkoutResponse2, $orderId);
        $this->assertSame(null, $response);
    }

    /**
     * generate order producing address from checkout response
     */
    public function testPrepareOrderProducingAddressWithException()
    {
        $checkoutResponse = '';
        $orderId = 121;
        $response = $this->helperData->prepareOrderProducingAddress($checkoutResponse, $orderId);
        $this->assertSame(null, $response);
    }

    public function testSaveOrderProducingAddressWithEstimateTimeNull()
    {
        $addressInfo['phone_number'] = '1234567890';
        $addressInfo['email_address'] = 'r@r.com';
        $addressInfo['address'] = 'address';
        $orderId = '121';
        $estimatedDuration = 5;
        $estimatedTime = "2021-09-17";

        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->order->expects($this->any())->method('load')->willThrowException($exception);

        $this->helperData->saveOrderProducingAddress($addressInfo, $orderId, $estimatedTime, $estimatedDuration);
    }

    /**
     * Test Unit to get Customer UUID
     */
    public function testGetUuid()
    {
        $this->customerSessionMock->expects($this->exactly(2))->method('getCustomerId')
            ->will($this->onConsecutiveCalls(125, 125));
        $this->customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(false);
        $this->customerRepositoryMock->expects($this->any())->method('getById')->willReturn($this->customerSessionMock);
        $this->customerSessionMock->expects($this->any())->method('getCustomAttribute')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getValue')->willReturn(12);

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->attributeRepositoryInterface->expects($this->any())
            ->method('get')
            ->willReturnSelf();

        $this->resourceConnection->expects($this->any())->method('getConnection')->willReturnSelf();
        $this->resourceConnection->expects($this->any())->method('select')->willReturnSelf();
        $this->resourceConnection->expects($this->any())->method('from')->willReturnSelf();
        $this->resourceConnection->expects($this->any())->method('where')->willReturnSelf();
        $this->attributeRepositoryInterface->expects($this->any())->method('get')->willReturnSelf();
        $this->attributeRepositoryInterface->expects($this->any())->method('getAttributeId')->willReturnSelf();
        $this->resourceConnection->expects($this->any())->method('fetchRow')->willReturn(['value' => 'test']);
        $this->assertEquals('test', $this->helperData->getUuid());

        //$this->assertEquals(12, $this->helperData->getUuid());
    }

    /**
     * Test Unit to get Customer UUID With Exception
     */
    public function testGetUuidWithException()
    {
        $this->customerSessionMock->expects($this->any())->method('getCustomerId')->willReturn(125);
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->customerSessionMock->expects($this->any())->method('getCustomerCompany')->willThrowException($exception);
        $this->assertEquals(null, $this->helperData->getUuid());
    }

    /**
     * Test SetCookie
     */
    public function testSetCookie()
    {
        $cookieName = 'test';
        $value = 10;
        $duration = 600;
        $secure = true;
        $httpOnly = false;
        $path = '/';

        $this->cookieMetadata = $this->getMockBuilder(\Magento\Framework\Stdlib\Cookie\PublicCookieMetadata::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cookieMetadataFactoryMock->expects($this->any())->method('createPublicCookieMetadata')->with()
            ->willReturn($this->cookieMetadata);
        $this->cookieMetadataFactoryMock->expects($this->any())->method('setDuration')->with($duration)
            ->willReturn($this->cookieMetadata);
        $this->cookieMetadataFactoryMock->expects($this->any())->method('setSecure')->with($secure)
            ->willReturn($this->cookieMetadata);
        $this->cookieMetadataFactoryMock->expects($this->any())->method('setPath')->with($path)
            ->willReturn($this->cookieMetadata);
        $this->cookieMetadataFactoryMock->expects($this->any())->method('setHttpOnly')->with($httpOnly)
            ->willReturn($this->cookieMetadata);
        $this->cookieManagerMock->expects($this->any())->method('setPublicCookie')
            ->withConsecutive([$cookieName, $value, $this->cookieMetadata])->willReturnSelf();

        $this->assertEquals(
            null,
            $this->helperData->setCookie($cookieName, $value, $duration, $secure, $httpOnly, $path)
        );
    }

    /**
     * Test GetCookie
     */
    public function testGetCookie()
    {
        $this->cookieManagerMock->expects($this->any())->method('getCookie')->willReturn('Some Cookie');
        $this->assertEquals('Some Cookie', $this->helperData->getCookie('Cookie'));
    }

    /**
     * Test testGetRateQuoteId
     */
    public function testGetRateQuoteId()
    {
        $expectedRateQuoteId = 'eyJxdW90ZUl';
        $this->assertEquals(
            $expectedRateQuoteId,
            $this->helperData->getRateQuoteId(json_decode($this->rateQuoteMockResponse, true))
        );
    }

    /**
     * Test testGetRateQuoteIdWithNull
     */
    public function testGetRateQuoteIdWithNull()
    {
        $this->assertEquals(null, $this->helperData->getRateQuoteId($this->rateQuoteMockErrorResponse));
    }

    /**
     * Test testGetOrderTotalFromRateQuoteResponse
     */
    public function testGetOrderTotalFromRateQuoteResponse()
    {
        $expectedOrderTotal = 35.08;
        $this->assertEquals(
            $expectedOrderTotal,
            $this->helperData->getOrderTotalFromRateQuoteResponse(json_decode($this->rateQuoteMockResponse, true))
        );
    }

    /**
     * Test testGetOrderTotalFromNewRateQuoteResponse
     */
    public function testGetOrderTotalFromNewRateQuoteResponse()
    {
        $expectedOrderTotal = 35.08;
        $this->assertEquals(
            $expectedOrderTotal,
            $this->helperData->getOrderTotalFromRateQuoteResponse(json_decode($this->newRateQuoteMockResponse, true))
        );
    }

    /**
     * Test testGetDeliveryLinePriceFromOldResponse
     */
    public function testGetDeliveryLinePriceFromOldResponse()
    {
        $expectedDeliveryPrice = 10.46;
        $this->testRetrieveDeliveryLinePriceFromActual();
        $this->assertEquals(
            $expectedDeliveryPrice,
            $this->helperData->getDeliveryLinePrice(json_decode($this->rateQuoteMockResponse, true))
        );
    }

    /**
     * Test testGetDeliveryLinePriceFromNewResponse
     */
    public function testGetDeliveryLinePriceFromNewResponse()
    {
        $expectedDeliveryPrice = 10.46;
        $this->testRetrieveDeliveryLinePriceFromEstimatedShipping();
        $this->assertEquals(
            $expectedDeliveryPrice,
            $this->helperData->getDeliveryLinePrice(json_decode($this->newRateQuoteMockResponse, true))
        );
    }

    /**
     * Test testGetDeliveryLinePriceForErrorResponse
     */
    public function testGetDeliveryLinePriceForErrorResponse()
    {
        $expectedDeliveryPrice = null;
        $this->testRetrieveDeliveryLinePriceFromEstimatedShipping();
        $this->assertEquals(
            $expectedDeliveryPrice,
            $this->helperData->getDeliveryLinePrice(json_decode($this->rateQuoteMockErrorResponse, true))
        );
    }

    /**
     * Test testRetrieveDeliveryLinePriceFromEstimatedShipping
     */
    public function testRetrieveDeliveryLinePriceFromEstimatedShipping()
    {
        $rateQuoteDetails = json_decode(
            $this->newRateQuoteMockResponse,
            true
        )['output']['rateQuote']['rateQuoteDetails'];
        $estimatedVsActual = 'ESTIMATED';
        $expectedDeliveryPrice = 10.46;
        $this->assertEquals(
            $expectedDeliveryPrice,
            $this->helperData->retrieveDeliveryLinePrice($rateQuoteDetails, $estimatedVsActual)
        );
    }

    /**
     * Test testRetrieveDeliveryLinePriceFromActual
     */
    public function testRetrieveDeliveryLinePriceFromActual()
    {
        $rateQuoteDetails = json_decode($this->rateQuoteMockResponse, true)['output']['rateQuote']['rateQuoteDetails'];
        $estimatedVsActual = 'ACTUAL';
        $expectedDeliveryPrice = 10.46;
        $this->assertEquals(
            $expectedDeliveryPrice,
            $this->helperData->retrieveDeliveryLinePrice($rateQuoteDetails, $estimatedVsActual)
        );
    }

    /**
     * @test testVerifyQuoteIntegrity
     */
    public function testVerifyQuoteIntegrity()
    {
        $this->quote->expects($this->any())->method('getBillingAddress')->willReturnSelf();
        $this->quote->expects($this->any())->method('getShippingMethod')->willReturn("self::SHIPPING_METHOD_NAME");
        $this->quote->expects($this->any())->method('getShippingDescription')->willReturn("fedex description");
        $this->quote->expects($this->any())->method('getCustomerId')->willReturn(12345);

        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterface);
        $this->addressInterface->expects($this->any())->method('getShippingMethod')->willReturn(false);
        $this->addressInterface->expects($this->any())->method('setShippingMethod')->willReturnSelf();
        $this->addressInterface->expects($this->any())->method('setShippingDescription')->willReturnSelf();

        $this->addressInterface->expects($this->any())->method('getCustomerId')->willReturn(false);
        $this->addressInterface->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->quote->expects($this->any())->method('getId')->willReturn(123);

        $this->assertNull($this->helperData->verifyQuoteIntegrity($this->quote));
    }

    /**
     * @test testVerifyQuoteIntegrityWithShippingShipMethod
     */
    public function testVerifyQuoteIntegrityWithShippingShipMethod()
    {
        $this->quote->expects($this->any())->method('getBillingAddress')->willReturnSelf();
        $this->quote->expects($this->any())->method('getShippingMethod')->willReturn(false);
        $this->quote->expects($this->any())->method('setShippingMethod')->willReturnSelf();
        $this->quote->expects($this->any())->method('setShippingDescription')->willReturnSelf();
        $this->quote->expects($this->any())->method('getCustomerId')->willReturn(false);
        $this->quote->expects($this->any())->method('setCustomerId')->willReturnSelf();

        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterface);
        $this->addressInterface->expects($this->any())->method('getShippingMethod')
            ->willReturn(self::SHIPPING_METHOD_NAME);
        $this->addressInterface->expects($this->any())->method('getShippingDescription')
            ->willReturn("fedex description");
        $this->addressInterface->expects($this->any())->method('getCustomerId')->willReturn(12345);
        $this->quote->expects($this->any())->method('getId')->willReturn(123);

        $this->assertNull($this->helperData->verifyQuoteIntegrity($this->quote));
    }

    /**
     * @test testVerifyQuoteIntegrityWithoutShippingBilling
     */
    public function testVerifyQuoteIntegrityWithoutShippingBilling()
    {
        $this->quote->expects($this->any())->method('getBillingAddress')->willReturnSelf();
        $this->quote->expects($this->any())->method('getShippingMethod')->willReturn(false);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterface);
        $this->addressInterface->expects($this->any())->method('getShippingMethod')->willReturn(false);
        $this->quote->expects($this->any())->method('getId')->willReturn(123);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->checkoutSessionMock->expects($this->any())->method('getCustomShippingCarrierCode')
            ->willReturn('fedexshipping');
        $this->checkoutSessionMock->expects($this->any())->method('getCustomShippingMethodCode')
            ->willReturn('PRIORITY_OVERNIGHT');
        $this->checkoutSessionMock->expects($this->any())->method('getCustomShippingTitle')
            ->willReturn('FedEx Priority Overnight - Thursday, May 25, 12:00pm');
        $this->quote->expects($this->any())->method('setShippingMethod')->willReturnSelf();
        $this->quote->expects($this->any())->method('setShippingDescription')->willReturnSelf();
        $this->addressInterface->expects($this->any())->method('setShippingMethod')->willReturnSelf();
        $this->addressInterface->expects($this->any())->method('setShippingDescription')->willReturnSelf();
        $this->assertNull($this->helperData->verifyQuoteIntegrity($this->quote));
    }

    /**
     * @test testVerifyQuoteIntegrityWithoutShippingBilling
     */
    public function testVerifyQuoteIntegrityWithoutShippingBillingWithToggleOff()
    {
        $this->quote->expects($this->any())->method('getBillingAddress')->willReturnSelf();
        $this->quote->expects($this->any())->method('getShippingMethod')->willReturn(false);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterface);
        $this->addressInterface->expects($this->any())->method('getShippingMethod')->willReturn(false);
        $this->quote->expects($this->any())->method('getId')->willReturn(123);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->assertNull($this->helperData->verifyQuoteIntegrity($this->quote));
    }

    /**
     * @test testVerifyQuoteIntegrityWithShippingBilling
     */
    public function testVerifyQuoteIntegrityWithShippingBilling()
    {
        $this->quote->expects($this->any())->method('getBillingAddress')->willReturnSelf();
        $this->quote->expects($this->any())->method('getShippingMethod')->willReturn(self::SHIPPING_METHOD_NAME);
        $this->quote->expects($this->any())->method('getCustomerId')->willReturn(12345);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterface);
        $this->addressInterface->expects($this->any())->method('getShippingMethod')
            ->willReturn(self::SHIPPING_METHOD_NAME);
        $this->addressInterface->expects($this->any())->method('getCustomerId')->willReturn(12345);
        $this->quote->expects($this->any())->method('getId')->willReturn(123);

        $this->assertNull($this->helperData->verifyQuoteIntegrity($this->quote));
    }

    /**
     * Test case for getProductJsonData
     */
    public function testGetProductJsonData()
    {
        $value = json_encode([
            'external_prod' => [
                '0' => [
                    'catalogReference' => 'value',
                    'preview_url' => 'value2',
                    'fxo_product' => 'value3',
                ],
            ],
        ]);

        $this->assertNotNull($this->helperData->getProductJsonData($value));
    }

    /**
     * Test case for getCreditCartDetails
     */
    public function testGetCreditCartDetails()
    {
        $response = [
            'output' => [
                'creditCard' => [
                    'creditCardToken' => 'XCsdfer4534hhjjthyr',
                    'cardHolderName' => 'Ayush Sood'
                ]
            ]
        ];
        $ccToken = 'ABC';
        $nameOnCard = "Ayush Sood";
        $this->assertNotNull(
            $this->helperData->getCreditCartDetails(
                json_decode(json_encode($response)),
                $ccToken,
                $nameOnCard
            )
        );
    }

    /**
     * Test CreateOrderFromQuote with express checkout toggle
     *
     * @return void
     */
    public function testCreateOrderFromQuoteWithECToggle()
    {
        $this->quoteManagement->expects($this->any())->method('submit')->with($this->quote)->willReturn($this->order);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->assertNotNull($this->helperData->createOrderFromQuote($this->quote));
    }

    /**
     * Test CreateOrderFromQuote with express checkout toggle and exception
     *
     * @return void
     */
    public function testCreateOrderFromQuoteWithECToggleWithException()
    {
        $phrase = new Phrase(__('Order Exception message'));
        $exception = new LocalizedException($phrase);
        $this->quoteManagement->expects($this->any())->method('submit')->with($this->quote)->willReturn($this->order);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->order->expects($this->any())->method('save')->willThrowException($exception);
        $this->checkoutSessionMock->expects($this->any())->method('unsOrderInProgress')->willReturnSelf();
        $this->assertNotEquals(null, $this->helperData->createOrderFromQuote($this->quote));
    }

    /**
     * Test case for getCustomerOnBehalfOf
     */
    public function testGetCustomerOnBehalfOf()
    {
        $this->customerSessionMock->expects($this->any())->method('getOnBehalfOf')->willReturn(true);
        $this->assertNotNull($this->helperData->getCustomerOnBehalfOf([]));
    }

    /**
     * Test Case for cleanProductItemInstance
     */
    public function testCleanProductItemInstance()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->optimizeItemInstanceHelper->expects($this->any())->method('pushQuoteIdQueue')->willReturnSelf();
        $this->assertNull($this->helperData->cleanProductItemInstance(12));
    }

    /**
     * Test case for reorderInstanceSave
     */
    public function testReorderInstanceSave()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->reorderInstanceHelper->expects($this->any())->method('pushOrderIdInQueue')->willReturnSelf();
        $this->assertNull($this->helperData->reorderInstanceSave(12));
    }

    /**
     * Test case for clearQuoteCheckoutSessionAndStorage
     */
    public function testClearQuoteCheckoutSessionAndStorage()
    {
        $this->customerSessionMock->expects($this->any())->method('clearQuote')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('unsAll')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('clearStorage')->willReturnSelf();
        $this->quoteFactory->expects($this->any())->method('create')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->cart->expects($this->any())->method('truncate')->willReturnSelf();
        $this->eventManager->expects($this->any())->method('dispatch')->willReturnSelf();
    }

    /**
     * Test case for clearQuoteCheckoutSessionAndStorage
     */
    public function testClearQuoteCheckoutSessionAndStorageGraphQl()
    {
        $this->requestQueryValidatorMock->expects($this->any())->method('isGraphQlRequest')->willReturn(true);
        $this->checkoutSessionMock->expects($this->once())->method('clearQuote')->willReturnSelf();
        $this->checkoutSessionMock->expects($this->once())->method('unsAll')->willReturnSelf();
        $this->checkoutSessionMock->expects($this->once())->method('clearStorage')->willReturnSelf();
        $this->quoteFactory->expects($this->never())->method('create');
        $this->quote->expects($this->never())->method('save')->willReturnSelf();
        $this->cart->expects($this->once())->method('truncate')->willReturnSelf();
        $this->helperData->clearQuoteCheckoutSessionAndStorage(53491, 31498);
    }

    /**
     * Test case for producingAddress
     */
    public function testProducingAddress()
    {
        $this->assertNull($this->helperData->producingAddress($this->checkoutResponse[0], $this->quote, 12));
    }

    /**
     * Test case for getTransactionAndProductLineDetails
     */
    public function testGetTransactionAndProductLineDetails()
    {
        $this->assertNotNull($this->helperData->getTransactionAndProductLineDetails($this->checkoutResponseArray));
    }

    /**
     * Test case for testCallShipmentCreationIfAlreadyCreated
     */
    public function testCallShipmentCreationIfAlreadyCreated()
    {
        $this->quote->expects($this->any())->method('getData')
            ->withConsecutive(
                ['fedex_ship_account_number'],
                ['fedex_ship_account_number'],
                ['fxo_shipment_id'],
                ['fxo_shipment_id']
            )->willReturnOnConsecutiveCalls(
                '90876543',
                '90876543',
                '2012',
                '2012'
            );
        $this->orderRepository->expects($this->any())->method('get')->willReturn($this->order);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->shipmentHelper->expects($this->any())->method('hasShipmentCreated')->willReturn(true);
        $this->assertTrue($this->helperData->createShipment($this->quote, 123));
    }

    /**
     * Test case for createShipment
     */
    public function testCreateShipment()
    {
        $this->quote->expects($this->any())->method('getData')
            ->withConsecutive(
                ['fedex_ship_account_number'],
                ['fedex_ship_account_number'],
                ['fxo_shipment_id'],
                ['fxo_shipment_id']
            )->willReturnOnConsecutiveCalls(
                '90876543',
                '90876543',
                '2012',
                '2012'
            );
        $this->order->expects($this->any())->method("getShippingDescription")->willReturn('Standard Shipping - 2023-11-30 End of Day');
        $this->timezone->expects($this->any())->method("date")->willReturn(new \DateTime('2023-11-30'));
        $this->orderRepository->expects($this->any())->method('get')->willReturn($this->order);
        $this->convertOrder->expects($this->any())->method('toShipment')->willReturn($this->shipment);
        $this->order->expects($this->any())->method("getAllItems")->willReturn([$this->item]);
        $this->item->expects($this->any())->method("getQtyToShip")->willReturn(true);
        $this->item->expects($this->any())->method("getIsVirtual")->willReturn(false);
        $this->item->expects($this->any())->method("getQtyToShip")->willReturn(3);
        $this->convertOrder->expects($this->any())->method('itemToShipmentItem')->willReturn($this->shipmentItem);
        $this->shipmentItem->expects($this->any())->method('setQty')->with(33)->willReturnSelf();
        $this->shipment->expects($this->any())->method('register')->willReturnSelf();
        $this->shipment->expects($this->any())->method('getOrder')->willReturnSelf();
        $this->shipment->expects($this->any())->method('setIsInProcess')->with(false)->willReturnSelf();
        $this->shipment->expects($this->exactly(3))->method('setData')->willReturnSelf();
        $this->shipment->expects($this->any())->method('setShipmentStatus')->with(1)->willReturnSelf();
        $this->shipment->expects($this->any())->method('save')->willReturnSelf();
        $this->shipment->expects($this->any())->method('getOrder')->willReturnSelf();
        $this->shipment->expects($this->any())->method('save')->willReturnSelf();
        $this->helperData->createShipment($this->quote, 123);
    }

    /**
     * Test case for createShipmentWithException
     */
    public function testCreateShipmentWithException()
    {
        $this->quote->expects($this->any())->method('getData')
            ->withConsecutive(
                ['fedex_ship_account_number'],
                ['fedex_ship_account_number'],
                ['fxo_shipment_id'],
                ['fxo_shipment_id']
            )->willReturnOnConsecutiveCalls(
                '90876543',
                '90876543',
                '2012',
                '2012'
            );
        $this->order->expects($this->any())->method("getShippingDescription")->willReturn('Standard Shipping - 2023-11-30 End of Day');
        $this->timezone->expects($this->any())->method("date")->willReturn(new \DateTime('2023-11-30'));
        $this->orderRepository->expects($this->any())->method('get')->willReturn($this->order);
        $this->convertOrder->expects($this->any())->method('toShipment')->willReturn($this->shipment);
        $this->order->expects($this->any())->method("getAllItems")->willReturn([$this->item]);
        $this->item->expects($this->any())->method("getQtyToShip")->willReturn(true);
        $this->item->expects($this->any())->method("getIsVirtual")->willReturn(false);
        $this->item->expects($this->any())->method("getQtyToShip")->willReturn(3);
        $this->convertOrder->expects($this->any())->method('itemToShipmentItem')->willReturn($this->shipmentItem);
        $this->shipmentItem->expects($this->any())->method('setQty')->with(33)->willReturnSelf();
        $this->shipment->expects($this->any())->method('register')->willReturnSelf();
        $this->shipment->expects($this->any())->method('getOrder')->willReturnSelf();
        $this->shipment->expects($this->any())->method('setIsInProcess')->with(false)->willReturnSelf();
        $this->shipment->expects($this->exactly(3))->method('setData')->willReturnSelf();
        $this->shipment->expects($this->any())->method('setShipmentStatus')->with(1)->willReturnSelf();
        $this->shipment->expects($this->any())->method('save')->willThrowException(new \Exception());
        $this->helperData->createShipment($this->quote, 123);
    }

    /**
     * Test case for createShipmentWithContinue
     */
    public function testCreateShipmentWithContinue()
    {
        $this->quote->expects($this->any())->method('getData')
            ->withConsecutive(
                ['fedex_ship_account_number'],
                ['fedex_ship_account_number'],
                ['fxo_shipment_id'],
                ['fxo_shipment_id']
            )->willReturnOnConsecutiveCalls(
                '90876543',
                '90876543',
                '2012',
                '2012'
            );
        $this->order->expects($this->any())->method("getShippingDescription")->willReturn('Standard Shipping - 2023-11-30 End of Day');
        $this->timezone->expects($this->any())->method("date")->willReturn(new \DateTime('2023-11-30'));
        $this->orderRepository->expects($this->any())->method('get')->willReturn($this->order);
        $this->convertOrder->expects($this->any())->method('toShipment')->willReturn($this->shipment);
        $this->order->expects($this->any())->method("getAllItems")->willReturn([$this->item]);
        $this->item->expects($this->any())->method("getQtyToShip")->willReturn(false);
        $this->item->expects($this->any())->method("getIsVirtual")->willReturn(false);
        $this->item->expects($this->any())->method("getQtyToShip")->willReturn(3);
        $this->convertOrder->expects($this->any())->method('itemToShipmentItem')->willReturn($this->shipmentItem);
        $this->shipmentItem->expects($this->any())->method('setQty')->with(33)->willReturnSelf();
        $this->shipment->expects($this->any())->method('register')->willReturnSelf();
        $this->shipment->expects($this->any())->method('getOrder')->willReturnSelf();
        $this->shipment->expects($this->any())->method('setIsInProcess')->with(false)->willReturnSelf();
        $this->shipment->expects($this->exactly(3))->method('setData')->willReturnSelf();
        $this->shipment->expects($this->any())->method('setShipmentStatus')->with(1)->willReturnSelf();
        $this->shipment->expects($this->any())->method('save')->willReturnSelf();
        $this->shipment->expects($this->any())->method('getOrder')->willReturnSelf();
        $this->shipment->expects($this->any())->method('save')->willReturnSelf();
        $this->helperData->createShipment($this->quote, 123);
    }

    /**
     * Test case save Retail Transaction id in case of rate quote alert
     */
    public function testSaveRetailTransactionId()
    {
        $this->customerSessionMock->expects($this->once())->method('setRetailTransactionId')
            ->with('ADSKDF9C6435480505X')
            ->willReturnSelf();

        $this->assertNull($this->helperData->saveRetailTransactionId('ADSKDF9C6435480505X'));
    }

    /**
     * Test case for getRetailTransactionIdFromSession
     */
    public function testGetRetailTransactionIdFromSession()
    {
        $this->customerSessionMock->expects($this->once())->method('getRetailTransactionId')
            ->willReturn('ADSKDF9C6435480505X');

        $this->assertNotNull($this->helperData->getRetailTransactionIdFromSession());
    }

    /**
     * Test case for unsetRetailTransactionId
     */
    public function testUnsetRetailTransactionId()
    {
        $this->customerSessionMock->expects($this->once())->method('unsRetailTransactionId')
            ->willReturnSelf();

        $this->assertNull($this->helperData->unsetRetailTransactionId());
    }

    /**
     * Test case for updateQuoteInfoIfRateQuotePriceMisMatch
     */
    public function testUpdateQuoteInfoIfRateQuotePriceMisMatch()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->quote->expects($this->any())
            ->method('getGrandTotal')
            ->willReturn('$0.48');
        $this->checkoutSessionMock->expects($this->once())
            ->method('getRateQuoteResponse')
            ->willReturn($this->output);
        $this->checkoutSessionMock->expects($this->once())
            ->method('unsRateQuoteResponse')
            ->willReturnSelf();

        $this->assertEquals(true,
            $this->helperData->updateQuoteInfoIfRateQuotePriceMisMatch($this->quote, $this->output)
        );
    }

    /**
     * Test case for setQuotePaymentInfo
     */
    public function testSetQuotePaymentInfo()
    {
        $paymentData = [
            "fedexAccountNumber" => self::FEDEX_ACCOUNT_NUMBER,
            "paymentMethod" => 'fedex',
            "poReferenceId" => '1234',
            "number" => '8599'
        ];
        $paymentMethod = 'fedex';

        $this->customerSessionMock->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getGroupId')->willReturnSelf();
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(true);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->quote->expects($this->any())->method('getPayment')->willReturnSelf();
        $this->payment->expects($this->any())->method('setMethod')->with($paymentMethod)->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getGroupId')->willReturn(1);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->quote->expects($this->any())->method('setCustomerGroupId')->willReturnSelf();
        $this->assertNotNull($this->helperData->setQuotePaymentInfo($this->quote, $paymentData));
    }

    /**
     * Test case for setQuotePaymentInfoforNonSde
     */
    public function testSetQuotePaymentInfoForNonSde()
    {
        $paymentData = [
            "fedexAccountNumber" => self::FEDEX_ACCOUNT_NUMBER,
            "paymentMethod" => 'fedex',
            "poReferenceId" => '1234',
            "number" => '8599'
        ];
        $paymentMethod = 'fedex';
        $importData = ['method' => $paymentMethod];

        $this->customerSessionMock->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getGroupId')->willReturnSelf();
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(false);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->paymentFactory->expects($this->any())->method('create')->willreturn($this->payment);
        $this->payment->expects($this->any())->method('setMethod')->with($paymentMethod)->willReturnSelf();
        $this->quote->expects($this->any())->method('setPayment')->with($this->payment)->willReturnSelf();
        $this->quote->expects($this->any())->method('getPayment')->willReturnSelf();
        $this->quote->expects($this->any())->method('importData')->with($importData)->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getGroupId')->willReturn(1);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->quote->expects($this->any())->method('setCustomerGroupId')->willReturnSelf();
        $this->assertNotNull($this->helperData->setQuotePaymentInfo($this->quote, $paymentData));
    }

    /**
     * Test case for setQuotePaymentInfoforNotloggedin
     */
    public function testSetQuotePaymentInfoForNotLoggedIn()
    {
        $paymentData = [
            "fedexAccountNumber" => self::FEDEX_ACCOUNT_NUMBER,
            "paymentMethod" => 'fedex',
            "poReferenceId" => '1234',
            "number" => '8599'
        ];
        $paymentMethod = 'fedex';

        $this->customerSessionMock->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getGroupId')->willReturnSelf();
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(true);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->quote->expects($this->any())->method('getPayment')->willReturnSelf();
        $this->payment->expects($this->any())->method('setMethod')->with($paymentMethod)->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getGroupId')->willReturn(1);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(false);
        $this->assertNotNull($this->helperData->setQuotePaymentInfo($this->quote, $paymentData));
    }

    public function testIsQuoteOrderAvailable()
    {
        $this->quote->expects($this->any())->method('getGtn')->willReturn('2010515745179190');
        $this->quote->expects($this->any())->method('getId')->willReturn(self::QUOTE_ID);
        $this->order->expects($this->any())->method('loadByIncrementId')->with('2010515745179190')
        ->willReturnSelf();
        $this->order->expects($this->any())->method('getStatus')->willReturn("pending");

        $this->assertFalse($this->helperData->isQuoteOrderAvailable($this->quote));
    }

    public function testIsQuoteOrderAvailableWithTry()
    {
        $this->quote->expects($this->any())->method('getGtn')->willReturn('2010515745179190');
        $this->quote->expects($this->any())->method('getId')->willReturn(self::QUOTE_ID);
        $this->order->expects($this->any())->method('loadByIncrementId')->with('2010515745179190')
        ->willReturnSelf();
        $this->order->expects($this->any())->method('getStatus')->willReturn("");
        $this->quote->expects($this->any())->method('setIsActive')->willReturnSelf();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->quoteRepositoryMock->expects($this->any())->method('save')->with($this->quote)->willReturnSelf();
        $this->checkoutSessionMock->expects($this->once())->method('clearQuote')->willReturnSelf();
        $this->checkoutSessionMock->expects($this->once())->method('unsAll')->willReturnSelf();
        $this->checkoutSessionMock->expects($this->once())->method('clearStorage')->willReturnSelf();
        $this->cart->expects($this->once())->method('truncate')->willReturnSelf();

        $this->assertNotNull($this->helperData->isQuoteOrderAvailable($this->quote));
    }

    public function testGetActiveQuote()
    {
        $this->quoteRepositoryMock->expects($this->any())->method('getActive')->willReturn($this->quote);
        $this->assertNotNull($this->helperData->getActiveQuote(1));
    }

    public function testSaveQuoteByRepository()
    {
        $this->quoteRepositoryMock->expects($this->any())->method('save')->with($this->quote)->willReturnSelf();
        $this->assertNull($this->helperData->saveQuoteByRepository($this->quote));
    }

    /**
     * Test toggle value for Millionaires - B-2154431: Update Continue Shopping CTA
     *
     * @return boolean
     */
    public function testGetUpdateContinueShoppingCtaToggle() {
        $this->cartSummaryMock
            ->expects($this->any())
            ->method('getUpdateContinueShoppingCtaToggle')
            ->willReturn(1);
        $this->assertEquals(true, $this->cartSummaryMock->getUpdateContinueShoppingCtaToggle());
    }

    /**
     * Test CTA retail/commercial site url for continue shopping button
     *
     * @return string
     */
    public function testGetAllPrintProductUrl() {
        $this->cartSummaryMock
            ->expects($this->any())
            ->method('getAllPrintProductUrl')
            ->willReturn('URL');
        return $this->assertNotEmpty($this->cartSummaryMock->getAllPrintProductUrl());
    }
}
