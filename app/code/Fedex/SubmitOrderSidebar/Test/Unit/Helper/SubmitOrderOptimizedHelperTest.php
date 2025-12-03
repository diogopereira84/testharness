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
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\SubmitOrderSidebar\Helper\SubmitOrderOptimizedHelper;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Magento\Sales\Model\Order;
use Fedex\Shipment\Model\ProducingAddressFactory;
use Magento\Directory\Model\Country;
use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Sales\Api\Data\ShipmentSearchResultInterface;
use Magento\Sales\Api\Data\ShipmentInterface;

/**
 * SubmitOrderOptimizedHelper Test Case
 */
class SubmitOrderOptimizedHelperTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $checkoutSessionMock;
    protected $quoteFactory;
    protected $quote;
    protected $order;
    protected $producingAddressFactory;
    protected $country;
    protected $searchCriteriaBuilder;
    protected $searchCriteria;
    /**
     * @var (\Magento\Framework\Api\SearchCriteriaBuilder & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $searchCriteriaInterface;
    protected $shipmentRepositoryInterface;
    protected $shipmentSearchResultInterface;
    protected $shipmentInterface;
    protected $helperData;
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

    protected $output = [
        'transactionId' => '6d1dd203-a1db-494b-a59b-e63c8e9a8042',
        'output' => [
            'rateQuote' => [
                'currency' => 'USD',
                'rateQuoteDetails' => [
                    0 => [
                        'supportContact'=> [
                            'address' => [
                                'phone_number' => '98765454321',
                                'email_address' => 'ayush.sood@fedex.com',
                                'streetLines' => [
                                    "8290 State Highway 121"
                                ],
                                'city' => 'Plano',
                                'stateOrProvinceCode' => 'TX',
                                'postalCode' => '75024',
                                'countryCode' => '+1'
                            ],
                            'email' => 'jaikumar.osv@fedex.com',
                            'phoneNumberDetails'=> [
                                'phoneNumber' => [
                                    'number' => '9876543211'
                                ]
                            ],
                        ],
                        'productLines' => [
                            0 => [
                                'instanceId' => '0',
                                'productId' => '1447174746733',
                                'retailPrice' => '$0.51',
                                'discountAmount' => '($0.058)',
                                'unitQuantity' => 1,
                                'linePrice' => '$0.50',
                                'lineDiscounts' => [
                                    0 => [
                                        'amount' => '($0.052)',
                                        'type' => 'ACCOUNT',
                                    ],
                                ],
                                'priceable' => true,
                                'productLineDetails' => [
                                    0 => [
                                        'detailCode' => '0173',
                                        'description' => 'Single Sided Color',
                                        'detailCategory' => 'PRINTING',
                                        'unitQuantity' => 1,
                                        'unitOfMeasurement' => 'EACH',
                                        'detailPrice' => '$0.50',
                                        'detailDiscountPrice' => '($0.051)',
                                        'detailUnitPrice' => '$0.5900',
                                        'detailDiscountedUnitPrice' => '($0.051)',
                                        'detailDiscounts' => [
                                            0 => [
                                                'amount' => '($0.06)',
                                                'type' => 'AR_CUSTOMERS',
                                            ],
                                        ],
                                    ],
                                ],
                                'productRetailPrice' => '$0.53',
                                'productDiscountAmount' => '($0.052)',
                                'productLinePrice' => '$0.55',
                                'productLineDiscounts' => [
                                    0 => [
                                        'amount' => '($0.057)',
                                        'type' => 'ACCOUNT',
                                    ],
                                ],
                                'editable' => false,
                            ],
                        ],
                        'deliveryLines' => [
                            0 => [
                                'recipientReference' => '',
                                'linePrice' => '$0.01',
                                'lineType' => 'PACKING_AND_HANDLING',
                                'deliveryLinePrice' => '$0.02',
                                'deliveryLineType' => 'PACKING_AND_HANDLING',
                                'priceable' => true,
                                'deliveryRetailPrice' => '$0.03',
                                'deliveryDiscountAmount' => '$0.045',
                                'estimatedDeliveryLocalTime' => '2021-06-22T12:00:01'
                            ],
                            1 => [
                                'recipientReference' => '',
                                'linePrice' => '$19.97',
                                'estimatedDeliveryLocalTime' => '2021-06-22T12:00:02',
                                'estimatedShipDate' => '2021-06-21',
                                'lineType' => 'SHIPPING',
                                'deliveryLinePrice' => '$19.96',
                                'deliveryLineType' => 'SHIPPING',
                                'priceable' => true,
                                'deliveryRetailPrice' => '$19.90',
                                'deliveryDiscountAmount' => '$0.08',
                            ],
                        ],
                        'grossAmount' => '$20.58',
                        'discounts' => [
                            0 => [
                                'amount' => '($0.15)',
                                'type' => 'ACCOUNT',
                            ],
                        ],
                        'totalDiscountAmount' => '($0.23)',
                        'netAmount' => '$20.50',
                        'taxableAmount' => '$20.54',
                        'taxAmount' => '$0.04',
                        'totalAmount' => '$20.57',
                        'estimatedVsActual' => 'ACTUAL',
                    ],
                ],
            ],
        ],
    ];

    protected $outputWithAdditionData = [
        'transactionId' => '6d1dd203-a1db-494b-a59b-e63c8e9a8042',
        'output' => [
            'rateQuote' => [
                'currency' => 'USD',
                'rateQuoteDetails' => [
                    0 => [
                        'productLines' => [
                            0 => [
                                'instanceId' => '0',
                                'productId' => '1447174746733',
                                'retailPrice' => '$0.59',
                                'discountAmount' => '($0.15)',
                                'unitQuantity' => 1,
                                'linePrice' => '$0.501',
                                'lineDiscounts' => [
                                    0 => [
                                        'amount' => '($0.25)',
                                        'type' => 'ACCOUNT',
                                    ],
                                ],
                                'priceable' => true,
                                'productLineDetails' => [
                                    0 => [
                                        'detailCode' => '0173',
                                        'description' => 'Single Sided Color',
                                        'detailCategory' => 'PRINTING',
                                        'unitQuantity' => 1,
                                        'unitOfMeasurement' => 'EACH',
                                        'detailPrice' => '$0.54',
                                        'detailDiscountPrice' => '($0.35)',
                                        'detailUnitPrice' => '$0.5900',
                                        'detailDiscountedUnitPrice' => '($0.45)',
                                        'detailDiscounts' => [
                                            0 => [
                                                'amount' => '($0.55)',
                                                'type' => 'AR_CUSTOMERS',
                                            ],
                                        ],
                                    ],
                                ],
                                'productRetailPrice' => '$0.59',
                                'productDiscountAmount' => '($0.65)',
                                'productLinePrice' => '$0.54',
                                'productLineDiscounts' => [
                                    0 => [
                                        'amount' => '($0.75)',
                                        'type' => 'ACCOUNT',
                                    ],
                                ],
                                'editable' => false,
                            ],
                        ],
                        'deliveryLines' => [
                            0 => [
                                'recipientReference' => '',
                                'linePrice' => '$0.80',
                                'lineType' => 'PACKING_AND_HANDLING',
                                'deliveryLinePrice' => '$0.90',
                                'deliveryLineType' => 'PACKING_AND_HANDLING',
                                'priceable' => true,
                                'deliveryRetailPrice' => '$0.10',
                                'deliveryDiscountAmount' => '$0.00',
                                'estimatedDeliveryDuration' => [
                                    'value' => 'test',
                                    'unit' => 'test'
                                ]
                            ],
                            1 => [
                                'recipientReference' => '',
                                'linePrice' => '$19.90',
                                'estimatedDeliveryLocalTime' => '2021-06-22T12:00:09',
                                'estimatedShipDate' => '2021-06-21',
                                'lineType' => 'SHIPPING',
                                'deliveryLinePrice' => '$19.99',
                                'deliveryLineType' => 'SHIPPING',
                                'priceable' => true,
                                'deliveryRetailPrice' => '$19.99',
                                'deliveryDiscountAmount' => '$0.00',
                            ],
                        ],
                        'grossAmount' => '$20.58',
                        'discounts' => [
                            0 => [
                                'amount' => '($0.05)',
                                'type' => 'ACCOUNT',
                            ],
                        ],
                        'totalDiscountAmount' => '($0.05)',
                        'netAmount' => '$20.53',
                        'taxableAmount' => '$20.53',
                        'taxAmount' => '$0.04',
                        'totalAmount' => '$20.57',
                        'estimatedVsActual' => 'ACTUAL',
                    ],
                ],
            ],
        ],
    ];

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->setMethods(['unsOrderInProgress', 'getAlternateContact', 'getAlternatePickupPerson', 'getQuote','setAlternateContactAvailable'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->setMethods(['create', 'load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->order = $this->getMockBuilder(Order::class)
            ->setMethods(['getStoreId', 'getId', 'save'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->producingAddressFactory = $this->getMockBuilder(ProducingAddressFactory::class)
            ->setMethods(
                ['create', 'addData', 'save', 'getCollection','addFieldToFilter','load', 'getId', 'getData','setData']
                )
            ->disableOriginalConstructor()
            ->getMock();
        $this->country = $this->getMockBuilder(Country::class)
            ->setMethods(['addData', 'save', 'load', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->setMethods(['addFilter', 'create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaInterface = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->shipmentRepositoryInterface = $this->getMockBuilder(ShipmentRepositoryInterface::class)
            ->setMethods(['getList'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->shipmentSearchResultInterface = $this->getMockBuilder(ShipmentSearchResultInterface::class)
            ->setMethods(['save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->shipmentInterface = $this->getMockBuilder(ShipmentInterface::class)
        ->setMethods(['setData', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->helperData = $this->objectManager->getObject(
            SubmitOrderOptimizedHelper::class,
            [
                'context' => $this->contextMock,
                'checkoutSession' => $this->checkoutSessionMock,
                'quoteFactory' => $this->quoteFactory,
                'producingAddressFactory' => $this->producingAddressFactory,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'shipmentRepositoryInterface' => $this->shipmentRepositoryInterface
            ]
        );
    }

    /**
     * Test case for getRetailTransactionId
     */
    public function testGetRetailTransactionId()
    {
        $this->assertNotNull($this->helperData->getRetailTransactionId($this->checkoutResponseArray));
    }

    /**
     * Test case for getQuoteObject
     */
    public function testGetQuoteObject()
    {
        $this->quoteFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->quoteFactory->expects($this->any())->method('load')->willReturnSelf();
        $this->assertNotNull($this->helperData->getQuoteObject(12));
    }

    /**
     * @return void
     */
    public function testUnsetOrderInProgress():void
    {
        $this->checkoutSessionMock->expects($this->any())->method('unsOrderInProgress')->willReturn(null);
        $this->assertEquals(null, $this->helperData->unsetOrderInProgress(null));
    }

    /**
     * @return void
     */
    public function testIsAlternateContact():void
    {
        $this->checkoutSessionMock->expects($this->any())->method('getAlternateContact')->willReturn(null);
        $this->assertEquals(null, $this->helperData->isAlternateContact());
    }

    /**
     * @return void
     */
    public function testIsAlternatePickupPerson():void
    {
        $this->checkoutSessionMock->expects($this->any())->method('getAlternatePickupPerson')->willReturn(null);
        $this->assertEquals(null, $this->helperData->isAlternatePickupPerson());
    }

    public function testGetCheckoutSessionQuote()
    {
        $this->checkoutSessionMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->assertNotNull($this->helperData->getCheckoutSessionQuote());
    }

    /**
     * Test case for getAdditionalInfo
     */
    public function testgetAdditionalInfo()
    {
        $this->assertNotNull(
            $this->helperData->getAdditionalInfo($this->output['output']['rateQuote']['rateQuoteDetails'])
        );
    }

    /**
     * Test case for getAdditionalInfo
     */
    public function testgetAdditionalInfoWithElse()
    {
        $this->assertNotNull(
            $this->helperData->getAdditionalInfo(
                $this->outputWithAdditionData['output']['rateQuote']['rateQuoteDetails']
            )
        );
    }

    /**
     * Test case for saveOrderProducingAddress
     */
    public function testsaveOrderProducingAddress()
    {
        $addressInfo = [
            'address' => 'Legacy Dr',
            'phone_number' => '9876543212',
            'email_address' => 'ayush.sood@infogain.com'
        ];
        $addtionalData = [
            'estimated_time' => '2021-06-22T12:00:08',
            'estimated_duration' => '2021-06-22T12:00:07'
        ];
        $this->order->expects($this->any())->method('getStoreId')->willReturn(12);
        $this->order->expects($this->any())->method('getId')->willReturn(12);
        $this->producingAddressFactory->expects($this->any())->method('create')->willReturn($this->country);
        $this->country->expects($this->any())->method('addData')->willReturnSelf();
        $this->country->expects($this->any())->method('save')->willReturnSelf();
        $this->assertNull(
            $this->helperData->saveOrderProducingAddress($addressInfo, $this->order, $addtionalData)
        );
    }

    /**
     * Test case for saveOrderProducingAddressWithException
     */
    public function testsaveOrderProducingAddressWithException()
    {
        $exception = new Exception();
        $addressInfo = [
            'address' => 'Legacy Dr',
            'phone_number' => '9876543212',
            'email_address' => 'ayush.sood@infogain.com'
        ];
        $addtionalData = [
            'estimated_time' => '2021-06-22T12:00:00',
            'estimated_duration' => '2021-06-22T12:00:00'
        ];
        $this->order->expects($this->any())->method('getStoreId')->willThrowException($exception);
        $this->assertNull(
            $this->helperData->saveOrderProducingAddress($addressInfo, $this->order, $addtionalData)
        );
    }

    /**
     * Test case for prepareOrderProducingAddress
     */
    public function testprepareOrderProducingAddress()
    {
        $this->testsaveOrderProducingAddress();
        $this->assertNull(
            $this->helperData->prepareOrderProducingAddress(
                $this->output['output']['rateQuote']['rateQuoteDetails'],
                $this->order
            )
        );
    }

    /**
     * Test case for prepareOrderProducingAddressWithException
     */
    public function testprepareOrderProducingAddressWithException()
    {
        $this->testsaveOrderProducingAddress();
        $this->assertNull(
            $this->helperData->prepareOrderProducingAddress(
                $this->outputWithAdditionData['output']['rateQuote']['rateQuoteDetails'],
                $this->order
            )
        );
    }

    /**
     * Test case for producingAddress
     */
    public function testproducingAddress()
    {
        $this->testprepareOrderProducingAddress();
        $this->assertNull(
            $this->helperData->producingAddress(
                $this->output['output']['rateQuote']['rateQuoteDetails'],
                $this->order
            )
        );
    }

    /**
     * Test case for getOrderProducingAddressIdByOrderId
     */
    public function testgetOrderProducingAddressIdByOrderId()
    {
        $this->producingAddressFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->producingAddressFactory->expects($this->any())->method('getCollection')->willReturnSelf();
        $this->producingAddressFactory->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->producingAddressFactory->expects($this->any())->method('load')
        ->willReturn([$this->producingAddressFactory]);
        $this->producingAddressFactory->expects($this->any())->method('getId')->willReturn(12);
        $this->helperData->getOrderProducingAddressIdByOrderId(12);
    }

    /**
     * Test case for getOrderProducingAddressIdByOrderIdWithException
     */
    public function testgetOrderProducingAddressIdByOrderIdWithException()
    {
        $exception = new Exception();
        $this->producingAddressFactory->expects($this->any())->method('create')->willThrowException($exception);
        $this->producingAddressFactory->expects($this->any())->method('getCollection')->willReturnSelf();
        $this->producingAddressFactory->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->producingAddressFactory->expects($this->any())->method('load')
        ->willReturn([$this->producingAddressFactory]);
        $this->producingAddressFactory->expects($this->any())->method('getId')->willReturn(12);
        $this->helperData->getOrderProducingAddressIdByOrderId(12);
    }

    /**
     * Test case for updateOrderProducingAddressDataAfterShipment
     */
    public function testupdateOrderProducingAddressDataAfterShipment()
    {
        $addtionalData = '{
            "estimated_time":"23:12L45"
        }';
        $this->order->expects($this->any())->method('getId')->willReturn(12);
        $this->searchCriteriaBuilder->expects($this->any())
        ->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->any())->method('create')->willReturn($this->searchCriteria);
        $this->shipmentRepositoryInterface->expects($this->any())
        ->method('getList')->willReturn($this->shipmentSearchResultInterface);
        $this->shipmentSearchResultInterface->expects($this->any())
        ->method('getItems')->willReturn([$this->shipmentInterface]);
        $this->producingAddressFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->producingAddressFactory->expects($this->any())->method('getCollection')->willReturnSelf();
        $this->producingAddressFactory->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->producingAddressFactory->expects($this->any())->method('load')
        ->willReturn($this->producingAddressFactory);
        $this->producingAddressFactory->expects($this->any())->method('getId')->willReturn(12);
        $this->producingAddressFactory->expects($this->any())->method('create')->willReturn($this->country);
        $this->country->expects($this->any())->method('load')->willReturn(json_decode(json_encode($this->country)));
        $this->producingAddressFactory->expects($this->any())->method('getData')
        ->willReturn($addtionalData);
        $this->shipmentInterface->expects($this->any())->method('setData')->willReturnSelf();
        $this->shipmentInterface->expects($this->any())->method('getId')->willReturn(12);
        $this->producingAddressFactory->expects($this->any())->method('setData')->willReturnSelf();
        $this->shipmentSearchResultInterface->expects($this->any())->method('save')->willReturnSelf();
        $this->order->expects($this->any())->method('save')->willReturnSelf();
        $this->assertNull($this->helperData->updateOrderProducingAddressDataAfterShipment($this->order));
    }

    /**
     * Test case for updateOrderProducingAddressDataAfterShipmentWithElse
     */
    public function testupdateOrderProducingAddressDataAfterShipmentWithElse()
    {
        $additionalData = '{
            "estimated_duration":"23:12L45"
        }';
        $this->order->expects($this->any())->method('getId')->willReturn(12);
        $this->searchCriteriaBuilder->expects($this->any())
        ->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->any())->method('create')->willReturn($this->searchCriteria);
        $this->shipmentRepositoryInterface->expects($this->any())
        ->method('getList')->willReturn($this->shipmentSearchResultInterface);
        $this->shipmentSearchResultInterface->expects($this->any())
        ->method('getItems')->willReturn([$this->shipmentInterface]);
        $this->producingAddressFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->producingAddressFactory->expects($this->any())->method('getCollection')->willReturnSelf();
        $this->producingAddressFactory->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->producingAddressFactory->expects($this->any())->method('load')
        ->willReturn($this->producingAddressFactory);
        $this->producingAddressFactory->expects($this->any())->method('getId')->willReturn(12);
        $this->producingAddressFactory->expects($this->any())->method('create')->willReturn($this->country);
        $this->country->expects($this->any())->method('load')->willReturn(json_decode(json_encode($this->country)));
        $this->producingAddressFactory->expects($this->any())->method('getData')
        ->willReturn($additionalData);
        $this->shipmentInterface->expects($this->any())->method('setData')->willReturnSelf();
        $this->shipmentInterface->expects($this->any())->method('getId')->willReturn(12);
        $this->producingAddressFactory->expects($this->any())->method('setData')->willReturnSelf();
        $this->shipmentSearchResultInterface->expects($this->any())->method('save')->willReturnSelf();
        $this->assertNull($this->helperData->updateOrderProducingAddressDataAfterShipment($this->order));
    }

    /**
     * Test case for updateOrderProducingAddressDataAfterShipmentWithException
     */
    public function testupdateOrderProducingAddressDataAfterShipmentWithException()
    {
        $exception = new Exception();
        $this->order->expects($this->any())->method('getId')->willReturn(12);
        $this->searchCriteriaBuilder->expects($this->any())
        ->method('addFilter')->willThrowException($exception);
        $this->assertNull($this->helperData->updateOrderProducingAddressDataAfterShipment($this->order));
    }

    public function testGetProductLinesDetails()
    {
        $this->assertNotNull($this->helperData->getProductLinesDetails($this->output));
    }

    public function testGetProductLinesDetailsWithEmpty()
    {
        $this->assertEquals([], $this->helperData->getProductLinesDetails([]));
    }

    public function testGetEstimatedVsActualDetails()
    {
        $this->assertNotNull($this->helperData->getEstimatedVsActualDetails($this->output));
    }

    public function testGetEstimatedVsActualDetailsWithEmpty()
    {
        $this->assertNull($this->helperData->getEstimatedVsActualDetails([]));
    }
    public function testSetAlternateContactFlag()
    {
        $this->checkoutSessionMock->expects($this->once())
            ->method('setAlternateContactAvailable')
            ->with(true);
            
        $this->helperData->setAlternateContactFlag(true);
    }
}
