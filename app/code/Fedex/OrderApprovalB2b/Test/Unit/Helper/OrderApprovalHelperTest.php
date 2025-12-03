<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Test\Unit\Helper;

use Fedex\OrderApprovalB2b\Helper\OrderApprovalHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Test class for OrderApprovalHelper
 */
class OrderApprovalHelperTest extends TestCase
{
    protected $dataObjectFactory;
    protected $quote;
    /**
     * @var StoreManagerInterface $storeManagerMock
     */
    protected $storeManagerMock;

    /**
     * @var OrderApprovalHelper $orderApprovalHelperMock
     */
    protected $orderApprovalHelperMock;

    /**
     * @var OrderRepositoryInterface $orderRepositoryMock
     */
    protected $orderRepositoryMock;

    /**
     * @var OrderPaymentInterface $orderPaymentInterface
     */
    protected $orderPaymentInterface;

    /**
     * @var CartRepositoryInterface $quoteRepository
     */
    protected $quoteRepository;

    /**
     * @var TimezoneInterface $timezoneMock
     */
    protected $timezoneMock;

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
                    'deliveryLines' => [
                      [
                        'recipientReference' => '1',
                        'estimatedDeliveryDuration' =>
                          [
                              'value' => 1,
                              'unit' => 'BUSINESSDAYS',
                          ],
                        'estimatedDeliveryLocalTime' => '2023-06-22T23:59:00',
                        'estimatedShipDate' => '2023-07-13',
                        'priceable' => true,
                        'deliveryLinePrice' => '$0.00',
                        'deliveryRetailPrice' => '$9.99',
                        'deliveryLineType' => 'SHIPPING',
                        'deliveryDiscountAmount' => '($9.99)',
                        'deliveryLineDiscounts' =>
                          [
                            0 =>
                                [
                                    'type' => 'COUPON',
                                    'amount' => '($9.99)',
                                ],
                          ],
                        'shipmentDetails' =>
                          [
                            'address' =>
                              [
                                  'streetLines' =>
                                      [
                                          0 => 'Line one',
                                          1 => 'Line two',
                                      ],
                                  'city' => 'Plano',
                                  'stateOrProvinceCode' => 'TX',
                                  'postalCode' => '75024',
                                  'countryCode' => 'US',
                                  'addressClassification' => 'BUSINESS',
                              ],
                            'serviceType' => 'GROUND_US',
                          ],
                          'pickupDetails' => [
                            'locationName' => 4474,
                          ],
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
     * @var DataTest|MockObject
     */
    protected $outputShipData = [
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
                      'deliveryLines' => [
                        [
                          'recipientReference' => '1',
                          'estimatedDeliveryDuration' =>
                            [
                                'value' => 1,
                                'unit' => 'BUSINESSDAYS',
                            ],
                          'estimatedDeliveryLocalTime' => '2023-06-22T23:59:00',
                          'estimatedShipDate' => '2023-07-13',
                          'priceable' => true,
                          'deliveryLinePrice' => '$0.00',
                          'deliveryRetailPrice' => '$9.99',
                          'deliveryLineType' => 'SHIPPING',
                          'deliveryDiscountAmount' => '($9.99)',
                          'deliveryLineDiscounts' =>
                            [
                              0 =>
                                  [
                                      'type' => 'COUPON',
                                      'amount' => '($9.99)',
                                  ],
                            ],
                          'shipmentDetails' =>
                            [
                              'address' =>
                                [
                                    'streetLines' =>
                                        [
                                            0 => 'Line one',
                                            1 => 'Line two',
                                        ],
                                    'city' => 'Plano',
                                    'stateOrProvinceCode' => 'TX',
                                    'postalCode' => '75024',
                                    'countryCode' => 'US',
                                    'addressClassification' => 'BUSINESS',
                                ],
                              'serviceType' => 'GROUND_US',
                            ]
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
                      'estimatedVsActual' => 'ESTIMATED',
                  ],
                  1 => [
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
                    'deliveryLines' => [
                      [
                        'recipientReference' => '1',
                        'estimatedDeliveryDuration' =>
                          [
                              'value' => 1,
                              'unit' => 'BUSINESSDAYS',
                          ],
                        'estimatedDeliveryLocalTime' => '2023-06-22T23:59:00',
                        'estimatedShipDate' => '2023-07-13',
                        'priceable' => true,
                        'deliveryLinePrice' => '$0.00',
                        'deliveryRetailPrice' => '$9.99',
                        'deliveryLineType' => 'SHIPPING',
                        'deliveryDiscountAmount' => '($9.99)',
                        'deliveryLineDiscounts' =>
                          [
                            0 =>
                                [
                                    'type' => 'COUPON',
                                    'amount' => '($9.99)',
                                ],
                          ],
                        'shipmentDetails' =>
                          [
                            'address' =>
                              [
                                  'streetLines' =>
                                      [
                                          0 => 'Line one',
                                          1 => 'Line two',
                                      ],
                                  'city' => 'Plano',
                                  'stateOrProvinceCode' => 'TX',
                                  'postalCode' => '75024',
                                  'countryCode' => 'US',
                                  'addressClassification' => 'BUSINESS',
                              ],
                            'serviceType' => 'GROUND_US',
                          ],
                          'pickupDetails' => [
                            'locationName' => 4474,
                          ],
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
     * Init mocks for tests.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
        ->disableOriginalConstructor()
        ->setMethods(['getStore','getBaseUrl'])
        ->getMockForAbstractClass();
      
        $this->dataObjectFactory = $this->getMockBuilder(DataObjectFactory::class)
        ->setMethods([
            'create',
            'getQuoteData',
            'getRateQuoteResponse',
            'setRateQuoteResponse',
            'getOrderNumber',
            'getOrderData'
        ])->disableOriginalConstructor()
        ->getMock();
      
        $this->quote = $this->getMockBuilder(Quote::class)
        ->setMethods(['getData'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->setMethods([
                'get',
                'getPayment',
                'getShippingAddress',
                'getCustomerFirstname',
                'getCustomerLastname',
                'getCustomerEmail',
                'getShippingDescription',
                'getStreet',
                'getCity',
                'getPostcode',
                'getCountryId',
                'getBillingFields'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->orderPaymentInterface = $this->getMockBuilder(OrderPaymentInterface::class)
            ->setMethods(['getFedexAccountNumber','getPayment'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'get',
                'getShippingAddress',
                'getPickupAddress',
                'getIsAlternatePickup',
                'getCustomerTelephone',
                'getEstimatedPickupTime'
            ])
            ->getMockForAbstractClass();

        $this->timezoneMock = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'format'])
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);

        $this->orderApprovalHelperMock = $objectManagerHelper->getObject(
            OrderApprovalHelper::class,
            [
                'storeManager' => $this->storeManagerMock,
                'timezoneInterface' => $this->timezoneMock
            ]
        );
    }

    /**
     * Test buildOrderSuccessResponse for shipping
     *
     * @return void
     */
    public function testBuildOrderSuccessResponse()
    {
        $paymentData = (object)[
        "fedexAccountNumber" => 653243286,
        "paymentMethod" => 'fedex',
        "poReferenceId" => '1234',
        "number" => '8599'
        ];

        $rateQuoteMockResponseArray = [
          'output' => [
              'rateQuote' => [
                  'rateQuoteDetails' => [
                      0 => [
                          'estimatedVsActual' => 'ACTUAL',
                          'productLines' => [
                              'productUnitPrice' => 34,
                              'productLineDetails' => [
                                [
                                  'detailRetailPrice' => 33,
                                ],
                              ]
                          ],
                      ]
                  ]
              ]
          ]
        ];

        $this->dataObjectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('getRateQuoteResponse')
          ->willReturn($rateQuoteMockResponseArray);
        $this->dataObjectFactory->expects($this->any())->method('setRateQuoteResponse')
        ->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')
        ->willReturn(123456);
        $this->dataObjectFactory->expects($this->any())->method('getOrderData')
        ->willReturnSelf();
        $this->storeManagerMock->expects($this->any())
        ->method('getStore')
        ->willReturnSelf();
        $this->storeManagerMock->expects($this->any())
        ->method('getBaseUrl')
        ->willReturn('https://staging3.office.fedex.com/');

        $this->assertNull($this->orderApprovalHelperMock
        ->buildOrderSuccessResponse(
            $this->dataObjectFactory,
            $paymentData,
            $this->quote
        ));
    }

    /**
     * Test buildOrderSuccessResponse for shipping with Shipping Account.
     *
     * @return void
     */
    public function testBuildOrderSuccessResponseWithShippingAcc()
    {
        $paymentData = (object)[
        "fedexAccountNumber" => 653243286,
        "paymentMethod" => 'fedex',
        "poReferenceId" => '1234',
        "number" => '8599'
        ];

        $rateQuoteMockResponseArray = [
          'output' => [
              'rateQuote' => [
                  'rateQuoteDetails' => [
                      0 => [
                          'estimatedVsActual' => 'ESTIMATED',
                          'deliveryLines' => [
                              'deliveryLineType' => 'SHIPPING',
                              'recipientContact' => 34,
                          ],
                      ],
                      1 => [
                          'estimatedVsActual' => 'ACTUAL',
                          'productLines' => [
                              'productUnitPrice' => 34,
                              'productLineDetails' => [
                                [
                                  'detailRetailPrice' => 33,
                                ],
                              ]
                          ],
                      ]
                  ]
              ]
          ]
        ];

        $this->dataObjectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('getRateQuoteResponse')
          ->willReturn($rateQuoteMockResponseArray);
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')
        ->willReturn(123456);
        $this->dataObjectFactory->expects($this->any())->method('getOrderData')
        ->willReturnSelf();
        $this->storeManagerMock->expects($this->any())
        ->method('getStore')
        ->willReturnSelf();
        $this->storeManagerMock->expects($this->any())
        ->method('getBaseUrl')
        ->willReturn('https://staging3.office.fedex.com/');

        $this->assertNull($this->orderApprovalHelperMock
        ->buildOrderSuccessResponse(
            $this->dataObjectFactory,
            $paymentData,
            $this->quote
        ));
    }

    /**
     * Test getTransactionTotals
     *
     * @return void
     */
    public function testGetTransactionTotals()
    {
        $transactionTotals =[
        "currency" =>"USD",
        "grossAmount" => 0.61,
        "totalDiscountAmount" => 0.03,
        "netAmount" => 0.58,
        "taxAmount" => 0.05,
        "totalAmount" => 0.63
        ];

        $this->assertIsArray($this->orderApprovalHelperMock->getTransactionTotals($this->outputShipData, true));
    }

    /**
     * Test getMarketplaceData
     *
     * @return void
     */
    public function testGetMarketplaceData()
    {
        $data =[
        "enabled" => true,
        "shipping_method" => "",
        "shipping_price" =>"",
        "lineItems" => [],
        "shipping_address" => []
        ];

        $this->assertEquals($data, $this->orderApprovalHelperMock->getMarketplaceData());
    }

    /**
     * Test prepareProductAssociations
     *
     * @return void
     */
    public function testPrepareProductAssociations()
    {
        $data['productAssociation'] = [
        [
          'productRef'=> '149157',
          'quantity'=> '1',
        ]
        ];

        $this->assertIsArray($this->orderApprovalHelperMock
        ->prepareProductAssociations($this->output['output']['rateQuote']['rateQuoteDetails'][0]));
    }

    /**
     * Test getProductLines
     *
     * @return void
     */
    public function testGetProductLines()
    {
        $this->assertIsArray($this->orderApprovalHelperMock
        ->getProductLines($this->output['output']['rateQuote']['rateQuoteDetails'][0]));
    }

    /**
     * Test getDeliveryLines
     *
     * @return void
     */
    public function testGetDeliveryLines()
    {
        $this->assertIsArray($this->orderApprovalHelperMock
        ->getDeliveryLines(
            $this->output['output']['rateQuote']['rateQuoteDetails'][0],
            $this->quote,
            []
        ));
    }

    /**
     * Test getDeliveryLines with shipping account.
     *
     * @return void
     */
    public function testGetDeliveryLinesWithShippingAcc()
    {
        $this->assertIsArray($this->orderApprovalHelperMock
        ->getDeliveryLines(
            $this->output['output']['rateQuote']['rateQuoteDetails'][0],
            $this->quote,
            $this->outputShipData['output']['rateQuote']['rateQuoteDetails'][1]
        ));
    }

    /**
     * Test prepareShippingDeliveryLines
     *
     * @return void
     */
    public function testPrepareShippingDeliveryLines()
    {
        $this->assertIsArray($this->orderApprovalHelperMock
        ->prepareShippingDeliveryLines(
            $this->output['output']['rateQuote']['rateQuoteDetails'][0],
            $this->quote,
            []
        ));
    }

    /**
     * Test prepareShippingDeliveryLines with shipping account.
     *
     * @return void
     */
    public function testPrepareShippingDeliveryLinesWithShippingAcc()
    {
        $this->assertIsArray($this->orderApprovalHelperMock
        ->prepareShippingDeliveryLines(
            $this->output['output']['rateQuote']['rateQuoteDetails'][0],
            $this->quote,
            $this->outputShipData['output']['rateQuote']['rateQuoteDetails']
        ));
    }

    /**
     * Test preparePickupDeliveryLines
     *
     * @return void
     */
    public function testPreparePickupDeliveryLines()
    {
        $this->assertIsArray($this->orderApprovalHelperMock
        ->preparePickupDeliveryLines(
            $this->output['output']['rateQuote']['rateQuoteDetails'][0],
            $this->quote
        ));
    }

    /**
     * Test prepareLineItems
     *
     * @return void
     */
    public function testPrepareLineItems()
    {
        $paymentData = (object)[
        "fedexAccountNumber" => 653243286,
        "paymentMethod" => 'fedex',
        "poReferenceId" => '1234',
        "number" => '8599'
        ];
        $this->storeManagerMock->expects($this->any())
        ->method('getStore')
        ->willReturnSelf();
        $this->storeManagerMock->expects($this->any())
        ->method('getBaseUrl')
        ->willReturn('https://staging3.office.fedex.com/');

        $this->assertIsArray($this->orderApprovalHelperMock
        ->prepareLineItems(
            $this->dataObjectFactory,
            $this->output['output']['rateQuote']['rateQuoteDetails'][0],
            $paymentData,
            $this->quote,
            false
        ));
    }

    /**
     * Test prepareLineItems with shipping account no
     *
     * @return void
     */
    public function testPrepareLineItemsWithShippingAcc()
    {
        $paymentData = (object)[
        "fedexAccountNumber" => 653243286,
        "paymentMethod" => 'fedex',
        "poReferenceId" => '1234',
        "number" => '8599'
        ];
        $this->storeManagerMock->expects($this->any())
        ->method('getStore')
        ->willReturnSelf();
        $this->storeManagerMock->expects($this->any())
        ->method('getBaseUrl')
        ->willReturn('https://staging3.office.fedex.com/');

        $this->assertIsArray($this->orderApprovalHelperMock
        ->prepareLineItems(
            $this->dataObjectFactory,
            $this->outputShipData['output']['rateQuote']['rateQuoteDetails'],
            $paymentData,
            $this->quote,
            true
        ));
    }

    /**
     * Test prepareOrderShippingRequest
     *
     * @return void
     */
    public function testPrepareOrderShippingRequest()
    {
        $accNumber = "603977505";
        $requestData = '{"paymentData":"test"}';
        $this->orderRepositoryMock->expects($this->any())
            ->method('get')
            ->with('123')
            ->willReturnSelf();
        $this->orderRepositoryMock->expects($this->any())
            ->method('getBillingFields')
            ->willReturn([]);

        $this->assertNotEquals(
            $requestData,
            $this->orderApprovalHelperMock->prepareOrderShippingRequest(
                $accNumber,
                $this->orderRepositoryMock
            )
        );
    }

    /**
     * Test prepareOrderPickupRequest
     *
     * @return void
     */
    public function testPrepareOrderPickupRequest()
    {
        $requestData = '{"paymentData":"test"}';
        $this->orderRepositoryMock->expects($this->any())
            ->method('get')
            ->with('123')
            ->willReturnSelf();
        $this->orderRepositoryMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->orderPaymentInterface);
        $this->orderRepositoryMock->expects($this->once())
            ->method('getShippingAddress')
            ->willReturnSelf();
        $this->orderPaymentInterface->expects($this->once())
            ->method('getFedexAccountNumber')
            ->willReturn('653243286');
        $this->quoteRepository->expects($this->any())
            ->method('get')
            ->willReturnSelf();
        $this->quoteRepository->expects($this->once())
            ->method('getShippingAddress')
            ->willReturnSelf();
        $this->quoteRepository->expects($this->once())
            ->method('getPickupAddress')
            ->willReturn('test address');
        $this->quoteRepository->expects($this->any())
            ->method('getEstimatedPickupTime')
            ->willReturnSelf();
        $this->orderRepositoryMock->expects($this->once())
            ->method('getCustomerFirstname')
            ->willReturn('Avi');
        $this->orderRepositoryMock->expects($this->once())
            ->method('getCustomerLastname')
            ->willReturn('test');
        $this->orderRepositoryMock->expects($this->once())
            ->method('getCustomerEmail')
            ->willReturn('test@test.com');
        $this->timezoneMock->expects($this->any())
            ->method('date')
            ->willReturnSelf();
        $this->timezoneMock->expects($this->any())
            ->method('format')
            ->willReturn("2024-06-17 01:01:01");
        $this->orderRepositoryMock->expects($this->any())
            ->method('getBillingFields')
            ->willReturn([]);
        $this->assertNotEquals(
            $requestData,
            $this->orderApprovalHelperMock->prepareOrderPickupRequest(
                $this->orderRepositoryMock,
                $this->quoteRepository
            )
        );
    }

    /**
     * Test getErrorResponseMsgs
     *
     * @return void
     */
    public function testGetErrorResponseMsgs()
    {
        $msg = true;
        $erroCodes = [
            [
                "code" => "SHIPMENTDELIVERY.ADDRESS.INVALID",
                "msg" => $msg
            ],
            [
                "code" => "SHIPMENTDELIVERY.HOLDUNTILDATE.INVALID",
                "msg" => $msg
            ],
            [
                "code" => "SHIPMENTDELIVERY.SERVICETYPE.INVALID",
                "msg" => $msg
            ],
            [
                "code" => "SHIPMENTDELIVERY.WEIGHT.EXCEEDED",
                "msg" => $msg
            ],
            [
                "code" => "SHIPMENTDELIVERY.FEDEXACCOUNTNUMBER.INVALID",
                "msg" => $msg
            ],
            [
                "code" => "SHIPMENTDELIVERY.NOT.AVAILABLE",
                "msg" => $msg
            ],
            [
                "code" => "SHIPMENTDELIVERY.INAVLID.ADDRESSCLASSIFICATION",
                "msg" => $msg
            ],
            [
                "code" => "INVALID.PICKUP.LOCATION",
                "msg" => $msg
            ],
            [
                "code" => "",
                "msg" => $msg
            ]
        ];
        foreach ($erroCodes as $code) {
            $this->assertTrue(
                $code['msg'],
                $this->orderApprovalHelperMock->getErrorResponseMsgs($code['code'], true)
            );
        }
    }
}
