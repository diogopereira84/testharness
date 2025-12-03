<?php
/**
 * @category    Fedex
 * @package     Fedex_ShippingEstimator
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\ShippingEstimator\Test\Unit\Helper;

use Fedex\ShippingEstimator\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Store\Model\ScopeInterface;

class DataTest extends TestCase
{
    /**
     * Mock context
     *
     * @var Context|MockObject
     */
    private $context;

    /**
     * Mock scopeConfig
     *
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Object to test
     *
     * @var Data
     */
    private Data $testObject;

    /**
     * Main set up method
     */
    public function setUp() : void
    {
        $this->objectManager = new ObjectManager($this);
        $this->context = $this->createMock(Context::class);
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->onlyMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->testObject = $this->objectManager->getObject(
            Data::class,
            [
                'scopeConfig' => $this->scopeConfig
            ]
        );
    }

    /**
     * @return void
     */
    public function testFormatResult():void
    {
        $result = [
            'output' => [
                'deliveryOptions' => [
                    0 => [
                        'shipmentOptions' =>  [
                            0 => [
                                'serviceType' => "LOCAL_DELIVERY_AM",
                                "serviceDescription" => "FedEx Local Delivery",
                                "currency" => "USD",
                                "estimatedShipmentRate" => "19.99",
                                "estimatedShipDate" => "2021-09-22",
                                "estimatedDeliveryLocalTime" => "2021-09-23T12:00:00",
                                "priceable" => true
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertNotNull($this->testObject->formatResult($result));
    }

    /**
     * @return void
     */
    public function testFormatResultEmpty():void
    {
        $result = [
            'output' => [
                'deliveryOptions' => [
                    0 => [
                        'shipmentOptions' => []
                    ]
                ]
            ]
        ];

        $response = ['cheapest_delivery' => '', 'fastest_delivery' => ''];

        $this->assertEquals($response, $this->testObject->formatResult($result));
    }

    /**
     * @return void
     */
    public function testCreateRequestPayload():void
    {
        $params = [
            'products'=> json_encode(
                [
                    [
                        "instanceId" => "sadsadad",
                        "qty"=>"2"
                    ]
                ]
            ),
            'postalCode' => '12345',
            'stateOrProvinceCode' => 'TX',
            'validateContent' => '123456'
        ];
        $productAssociations[] = [
            'id' => 'sadsadad',
            'quantity' => '2'
        ];
        $deliveries = [
            [
                'deliveryReference' => 'default',
                'address' => [
                    'streetLines' => [],
                    'city' => 'null',
                    'countryCode' => 'US',
                    'stateOrProvinceCode' => 'TX',
                    'postalCode' => '12345',
                    'addressClassification' => 'Home',
                ],
                'requestedDeliveryTypes' => [
                    'requestedShipment' => [
                        'productionLocationId' => null,
                        'fedExAccountNumber' => null,
                    ]
                ],
                'productAssociations' => $productAssociations,
            ]
        ];
        $requestBody = [
            'deliveryOptionsRequest' => [
                'fedExAccountNumber' => null,
                'site' => null,
                'products' =>[(object)(["instanceId"=>"sadsadad","qty"=>"2"])],
                'deliveries' => $deliveries,
                'validateContent' => false
            ]
        ];

        $this->assertEquals($requestBody, $this->testObject->createRequestPayload($params));
    }
    /**
     * @return void
     */

    public function testGetDeliveryReference()
    {
        $this->scopeConfig->expects($this->once())->method('getValue')
            ->with(Data::XML_PATH_FEDEX_SHIPP_DELIV_REF, ScopeInterface::SCOPE_STORE)
            ->willReturn('default');
        $this->assertEquals('default', $this->testObject->getDeliveryReference());
    }

    /**
     * @return void
     */
    public function testGetAddressClassification():void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_FEDEX_SHIPP_ADDR_CLASSI,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('test');
        $this->assertEquals('test', $this->testObject->getAddressClassification());
    }

    /**
     * @return void
     */
    public function testGetdeliveryApiUrl():void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_FEDEX_GEL_DELIVRY_URL,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('test');
        $this->assertEquals('test', $this->testObject->getdeliveryApiUrl());
    }

    /**
     * @return void
     */
    public function testGetCheapestDelivery():void
    {
        $deliveryOption =  [
            0 => [
                'serviceType' => "LOCAL_DELIVERY_AM",
                "serviceDescription" => "FedEx Local Delivery",
                "currency" => "USD",
                "estimatedShipmentRate" => "19.99",
                "estimatedShipDate" => "2021-09-22",
                "estimatedDeliveryLocalTime" => "2021-09-23T12:00:00",
                "priceable" => true
            ],
            1 => [
                'serviceType' => "LOCAL_DELIVERY_AM",
                "serviceDescription" => "FedEx Local Delivery",
                "currency" => "USD",
                "estimatedShipmentRate" => "10.99",
                "estimatedShipDate" => "2021-09-22",
                "estimatedDeliveryLocalTime" => "2021-09-23T12:00:00",
                "priceable" => true
            ]
        ];
        usort($deliveryOption, function ($a, $b) {
            return $a[Data::ESTIMATED_SHIPPING_RATE] - $b[Data::ESTIMATED_SHIPPING_RATE];
        });

        $this->assertNotNull($this->testObject->getCheapestDelivery($deliveryOption));
    }

    /**
     * @return void
     */
    public function testGetFastestDelivery()
    {
        $deliveryOption =  [
            0 => [
                'serviceType' => "LOCAL_DELIVERY_AM",
                "serviceDescription" => "FedEx Local Delivery",
                "currency" => "USD",
                "estimatedShipmentRate" => "19.99",
                "estimatedShipDate" => "2021-09-22",
                "estimatedDeliveryLocalTime" => "2021-09-23T12:00:00",
                "priceable" => true
            ],
            1 => [
                'serviceType' => "LOCAL_DELIVERY_AM",
                "serviceDescription" => "FedEx Local Delivery",
                "currency" => "USD",
                "estimatedShipmentRate" => "10.99",
                "estimatedShipDate" => "2021-09-22",
                "estimatedDeliveryLocalTime" => "2021-09-23T12:00:00",
                "priceable" => true
            ],
        ];
        usort($deliveryOption, function ($a, $b) {
            return strtotime($a[Data::ESTIMATED_DELIVERY_LOCALTIME])-strtotime($b[Data::ESTIMATED_DELIVERY_LOCALTIME]);
        });

        $this->assertNotNull($this->testObject->getFastestDelivery($deliveryOption));
    }

    /**
     * @return void
     */
    public function testGetFastestDeliveryWithEmptyDeliveryOption()
    {
        $this->assertEquals('', $this->testObject->getFastestDelivery([]));
    }

    /**
     * @return void
     */
    public function testGetFastestDeliveryWithEstimatedDeliveryDuration()
    {
        $deliveryOption =  [
            0 => [
                'serviceType' => "LOCAL_DELIVERY_AM",
                "serviceDescription" => "FedEx Local Delivery",
                "currency" => "USD",
                "estimatedShipmentRate" => "19.99",
                "estimatedShipDate" => "2021-09-22",
                "estimatedDeliveryLocalTime" => "2021-09-23T12:00:00",
                "priceable" => true,
                'estimatedDeliveryDuration'=> ['value'=>1],
            ],
            1 => [
                'serviceType' => "LOCAL_DELIVERY_AM",
                "serviceDescription" => "FedEx Local Delivery",
                "currency" => "USD",
                "estimatedShipmentRate" => "10.99",
                "estimatedShipDate" => "2021-09-22",
                "estimatedDeliveryLocalTime" => "2021-09-23T12:00:00",
                "priceable" => true,
                'estimatedDeliveryDuration'=> ['value'=>1]
            ]
        ];
        $deliveryOption[0]['estimatedDeliveryLocalTime'] = "2021-09-23T12:00:00";
        usort($deliveryOption, function ($a, $b) {
            return strtotime($a[Data::ESTIMATED_DELIVERY_LOCALTIME])-strtotime($b[Data::ESTIMATED_DELIVERY_LOCALTIME]);
        });

        $this->assertNotNull($this->testObject->getFastestDelivery($deliveryOption));
    }

    /**
     * @return void
     */
    public function testSetDelivery()
    {
        $deliveryOption =  [
            0 => [
                'serviceType' => "LOCAL_DELIVERY_AM",
                "serviceDescription" => "FedEx Local Delivery",
                "currency" => "USD",
                "estimatedShipmentRate" => "19.99",
                "estimatedShipDate" => "2021-09-22",
                "estimatedDeliveryLocalTime" => "2021-09-23T12:00:00",
                "priceable" => true
            ],
        ];

        $this->assertNotNull($this->testObject->setDelivery($deliveryOption));
    }

    /**
     * @return void
     */
    public function testSetDeliveryElseWithIf()
    {
        $deliveryOption =  [
            0 => [
                'serviceType' => "FEDEX_HOME_DELIVERY",
                "serviceDescription" => "FedEx Local Delivery",
                "currency" => "USD",
                "estimatedShipDate" => "2021-09-22",
                "estimatedDeliveryLocalTime" => "2021-09-23T12:00:00",
                'estimatedDeliveryDuration'=> ['value' => 1],
                "priceable" => true
            ],
        ];

        $this->assertNotNull($this->testObject->setDelivery($deliveryOption));
    }

    /**
     * @return void
     */
    public function testSetDeliveryElseWithToggleOnElse()
    {
        $deliveryOption =  [
            0 => [
                'serviceType' => "FEDEX_HOME_DELIVERY",
                "serviceDescription" => "FedEx Local Delivery",
                "currency" => "USD",
                "estimatedShipDate" => "2021-09-22",
                'estimatedDeliveryDuration'=> ['value' => 1],
                "priceable" => true
            ],
        ];

        $this->assertNotNull($this->testObject->setDelivery($deliveryOption));
    }

    /**
     * @return void
     */
    public function testSetDeliveryElseWithElse()
    {
        $deliveryOption =  [
            0 => [
                'serviceType' => "FEDEX_HOME_DELIVERY",
                "serviceDescription" => "FedEx Local Delivery",
                "currency" => "USD",
                "estimatedShipDate" => "2021-09-22",
                'estimatedDeliveryDuration'=> ['value' => 1],
                "priceable" => true
            ],
        ];

        $this->assertNotNull($this->testObject->setDelivery($deliveryOption));
    }

    /**
     * @return void
     */
    public function testGetCheapestDeliveryLabel()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_FEDEX_SHIPP_CHEAP_LBL,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('test');
        $this->assertEquals('test', $this->testObject->getCheapestDeliveryLabel());
    }

    /**
     * @return void
     */
    public function testGetFastestDeliveryLabel()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_FEDEX_SHIPP_FAST_LBL,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('test');
        $this->assertEquals('test', $this->testObject->getFastestDeliveryLabel());
    }
}
