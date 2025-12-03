<?php

/**
 * @category    Fedex
 * @package     Fedex_ShippingEstimator
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\ShippingEstimator\Test\Unit\Model\Service;

use Fedex\CoreApi\Client\AbstractApiClient;
use Fedex\ShippingEstimator\Helper\Data;
use Fedex\Header\Helper\Data as HeaderData;
use Fedex\ShippingEstimator\Model\Service\Delivery;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class DeliveryTest extends TestCase
{
    /**
     * Mock headerData
     *
     * @var HeaderData|MockObject
     */
    protected $headerData;
    /**
     * Mock curl
     *
     * @var Curl|MockObject
     */
    protected $curl;
    /**
     * Mock toggleConfig
     *
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfig;
    /**
     * Mock helper
     *
     * @var Data
     */
    private Data $helper;

    /**
     * Mock apiClient
     *
     * @var AbstractApiClient
     */
    private AbstractApiClient $abstractApiClientMock;

    /**
     * Mock punchoutHelper
     *
     * @var PunchoutHelper
     */
    private PunchoutHelper $punchoutHelper;

    /**
     * Mock logger
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Object to test
     *
     * @var Delivery
     */
    private Delivery $deliveryObject;

    /**
     * Main set up method
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->helper = $this->createMock(Data::class);
        $this->abstractApiClientMock = $this->createMock(AbstractApiClient::class);
        $this->punchoutHelper = $this->createMock(PunchoutHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->headerData = $this->createMock(HeaderData::class);
        $this->curl = $this->createMock(Curl::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);

        $this->deliveryObject = $objectManager->getObject(
            Delivery::class,
            [
                'helper' => $this->helper,
                'apiClient' => $this->abstractApiClientMock,
                'punchoutHelper' => $this->punchoutHelper,
                'logger' => $this->logger,
                'headerData' => $this->headerData,
                'curl' => $this->curl,
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetDeliveryInfo()
    {
        $tokenData['access_token'] = '123224345';
        $tokenData['auth_token'] = '23345464';
        $tokenData = json_encode($tokenData);

        $data = [
            'products' =>
            json_encode([["instanceId" => "sadsadad", "qty" => "2"]]),
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
                'products' => [(object)(["instanceId" => "sadsadad", "qty" => "2"])],
                'deliveries' => $deliveries,
                'validateContent' => false
            ]
        ];

        $info = [
            [
                'label' => null,
                'methods' => [[
                    'price' => ['value' => '19.99', 'currencySymbol' => '$', 'currencyCode' => 'USD'],
                    'label' => 'FedEx Local Delivery',
                    'description' => 'Arrives by Thursday, September 23 by 12:00 PM'
                ]]
            ]
        ];

        $response['response'] = [
            'data' => $info,
            'hasError' => false,
            'message' => ''
        ];

        $output = '
        "output" : {
            "deliveryOptions" : [
                {
                    "deliveryReference" : "default",
                    "shipmentOptions" : [
                        {
                            "serviceType" : "LOCAL_DELIVERY_AM",
                            "serviceDescription" : "FedEx Local Delivery",
                            "currency" : "USD",
                            "estimatedShipmentRate" : "19.99",
                            "estimatedShipDate" : "2021-09-22",
                            "estimatedDeliveryLocalTime" : "2021-09-23T12:00:00",
                            "priceable" : true
                        },
                        {
                            "serviceType" : "LOCAL_DELIVERY_PM",
                            "serviceDescription" : "FedEx Local Delivery",
                            "currency" : "USD",
                            "estimatedShipmentRate" : "19.99",
                            "estimatedShipDate" : "2021-09-22",
                            "estimatedDeliveryLocalTime" : "2021-09-23T17:00:00",
                            "priceable" : true
                        }
                    ]
                }
            ]
        }';

        $serializedResult = '{"hasError":"false","data":"","message":null,"errors":false,' . $output . '}';
        $chippestDelivery =  [
            'cheapest_delivery' => [
                [
                    'price' => ['value' => '19.99', 'currencySymbol' => '$', 'currencyCode' => 'USD'],
                    'label' => 'FedEx Local Delivery',
                    'description' => 'Arrives by Thursday, September 23 by 12:00 PM'
                ]
            ],
            'fastest_delivery' => ''
        ];

        $response['status'] = true;
        $this->abstractApiClientMock->domain = 'https://test.fedex.com/';
        $this->helper->expects($this->any())->method('getdeliveryApiUrl')->willReturn('http://api-url.com');
        $this->punchoutHelper->expects($this->any())->method('getAuthGatewayToken')->willReturn($tokenData);
        $this->headerData->expects($this->any())->method('getAuthHeaderValue')->willReturn("client_id: ");
        $this->helper->expects($this->any())->method('createRequestPayload')->willReturn($requestBody);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->curl->expects($this->any())->method('getBody')->willreturn($serializedResult);
        $this->helper->expects($this->any())->method('formatResult')->willReturn($chippestDelivery);

        $this->assertEquals($response, $this->deliveryObject->getDeliveryInfo($data));
    }

    /**
     * @return void
     */
    public function testGetDeliveryInfoWithToggleOff()
    {
        $tokenData['access_token'] = '123224345';
        $tokenData['auth_token'] = '23345464';
        $tokenData = json_encode($tokenData);
        $data = [
            'products' =>
            json_encode([["instanceId" => "sadsadad", "qty" => "2"]]),
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
                'products' => [(object)(["instanceId" => "sadsadad", "qty" => "2"])],
                'deliveries' => $deliveries,
                'validateContent' => false
            ]
        ];
        $info = [
            [
                'label' => null,
                'methods' => [[
                    'price' => ['value' => '19.99', 'currencySymbol' => '$', 'currencyCode' => 'USD'],
                    'label' => 'FedEx Local Delivery',
                    'description' => 'Arrives by Thursday, September 23 by 12:00 PM'
                ]]
            ]
        ];

        $response['response'] = [
            'data' => $info,
            'hasError' => false,
            'message' => ''
        ];
        $output = '
            "output" : {
              "deliveryOptions" : [ {
                "deliveryReference" : "default",
                "shipmentOptions" : [ {
                  "serviceType" : "LOCAL_DELIVERY_AM",
                  "serviceDescription" : "FedEx Local Delivery",
                  "currency" : "USD",
                  "estimatedShipmentRate" : "19.99",
                  "estimatedShipDate" : "2021-09-22",
                  "estimatedDeliveryLocalTime" : "2021-09-23T12:00:00",
                  "priceable" : true
                }, {
                  "serviceType" : "LOCAL_DELIVERY_PM",
                  "serviceDescription" : "FedEx Local Delivery",
                  "currency" : "USD",
                  "estimatedShipmentRate" : "19.99",
                  "estimatedShipDate" : "2021-09-22",
                  "estimatedDeliveryLocalTime" : "2021-09-23T17:00:00",
                  "priceable" : true
                } ]
              } ]
          }';
        $serializedResult = '{"hasError":"false","data":"","message":null,"errors":false,' . $output . '}';
        $chippestDelivery =  [
            'cheapest_delivery' => [
                [
                    'price' => ['value' => '19.99', 'currencySymbol' => '$', 'currencyCode' => 'USD'],
                    'label' => 'FedEx Local Delivery',
                    'description' => 'Arrives by Thursday, September 23 by 12:00 PM'
                ]
            ],
            'fastest_delivery' => ''
        ];
        $response['status'] = true;
        $this->abstractApiClientMock->domain = 'https://test.fedex.com/';
        $this->punchoutHelper->expects($this->any())->method('getAuthGatewayToken')->willReturn($tokenData);
        $this->headerData->expects($this->any())->method('getAuthHeaderValue')->willReturn("client_id: ");
        $this->helper->expects($this->any())->method('createRequestPayload')->willReturn($requestBody);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->abstractApiClientMock->expects($this->any())->method('execute')->willReturn($serializedResult);
        $this->helper->expects($this->any())->method('formatResult')->willReturn($chippestDelivery);
        $this->helper->expects($this->any())->method('getdeliveryApiUrl')->willReturn('http://api-url.com');

        $this->assertEquals($response, $this->deliveryObject->getDeliveryInfo($data));
    }

    /**
     * @return void
     */
    public function testGetDeliverywithFastInfo()
    {
        $tokenData['access_token'] = '123224345';
        $tokenData['auth_token'] = '23345464';
        $tokenData = json_encode($tokenData);
        $data = [
            'products' =>
            json_encode([["instanceId" => "sadsadad", "qty" => "2"]]),
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
                'products' => [(object)(["instanceId" => "sadsadad", "qty" => "2"])],
                'deliveries' => $deliveries,
                'validateContent' => false
            ]
        ];
        $info = [
            [
                'label' => null,
                'methods' => [[
                    'price' => ['value' => '19.99', 'currencySymbol' => '$', 'currencyCode' => 'USD'],
                    'label' => 'FedEx Local Delivery',
                    'description' => 'Arrives by Thursday, September 23 by 12:00 PM'
                ]]
            ]
        ];
        $fastInfo =
            [
                'label' => "",
                'methods' => [[
                    'price' => ['value' => '19.99', 'currencySymbol' => '$', 'currencyCode' => 'USD'],
                    'label' => 'FedEx Local Delivery',
                    'description' => 'Arrives by Thursday, September 23 by 12:00 PM'
                ]]
            ];
        array_push($info, $fastInfo);
        $deliveryOption = [
            'serviceType' => "LOCAL_DELIVERY_AM",
            "serviceDescription" => "FedEx Local Delivery",
            "currency" => "USD",
            "estimatedShipmentRate" => "19.99",
            "estimatedShipDate" => "2021-09-22",
            "estimatedDeliveryLocalTime" => "2021-09-23T12:00:00",
            "priceable" => true,
            'estimatedDeliveryDuration' => ['value' => 1],
        ];

        $response['response'] = [
            'data' => [$fastInfo, [
                'label' => null,
                'methods' => $deliveryOption
            ]],
            'hasError' => false,
            'message' => ''
        ];
        $response['status'] = true;
        $output = '
            "output" : {
              "deliveryOptions" : [ {
                "deliveryReference" : "default",
                "shipmentOptions" : [ {
                  "serviceType" : "LOCAL_DELIVERY_AM",
                  "serviceDescription" : "FedEx Local Delivery",
                  "currency" : "USD",
                  "estimatedShipmentRate" : "19.99",
                  "estimatedShipDate" : "2021-09-22",
                  "estimatedDeliveryLocalTime" : "2021-09-23T12:00:00",
                  "priceable" : true,
                  "fastest_delivery":false
                }, {
                  "serviceType" : "LOCAL_DELIVERY_PM",
                  "serviceDescription" : "FedEx Local Delivery",
                  "currency" : "USD",
                  "estimatedShipmentRate" : "19.99",
                  "estimatedShipDate" : "2021-09-22",
                  "estimatedDeliveryLocalTime" : "2021-09-23T17:00:00",
                  "priceable" : true,
                  "fastest_delivery":true
                } ]
              } ]
          }';
        $serializedResult = '{"hasError":"false","data":"","message":null,"errors":false,' . $output . '}';

        $chippestDelivery =  [
            'cheapest_delivery' => [
                [
                    'price' => ['value' => '19.99', 'currencySymbol' => '$', 'currencyCode' => 'USD'],
                    'label' => 'FedEx Local Delivery',
                    'description' => 'Arrives by Thursday, September 23 by 12:00 PM'
                ]
            ],
            'fastest_delivery' => $deliveryOption
        ];

        $this->abstractApiClientMock->domain = 'https://test.fedex.com/';
        $this->punchoutHelper->expects($this->any())->method('getAuthGatewayToken')->willReturn($tokenData);
        $this->headerData->expects($this->any())->method('getAuthHeaderValue')->willReturn("client_id: ");
        $this->helper->expects($this->any())->method('createRequestPayload')->willReturn($requestBody);
        $this->abstractApiClientMock->expects($this->any())->method('execute')->willReturn($serializedResult);
        $this->helper->expects($this->any())->method('formatResult')->willReturn($chippestDelivery);
        $this->helper->expects($this->any())->method('getdeliveryApiUrl')->willReturn('http://api-url.com');

        $this->assertEquals($response, $this->deliveryObject->getDeliveryInfo($data));
    }

    /**
     * @return void
     */
    public function testGetDeliveryInfoValidResultEmpty()
    {
        $tokenData['access_token'] = '123224345';
        $tokenData['auth_token'] = '23345464';
        $tokenData = json_encode($tokenData);
        $data = [
            'products' =>
            json_encode([["instanceId" => "sadsadad", "qty" => "2"]]),
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
                'products' => [(object)(["instanceId" => "sadsadad", "qty" => "2"])],
                'deliveries' => $deliveries,
                'validateContent' => false
            ]
        ];
        $info = [
            [
                'label' => null,
                'methods' => [[
                    'price' => ['value' => '19.99', 'currencySymbol' => '$', 'currencyCode' => 'USD'],
                    'label' => 'FedEx Local Delivery',
                    'description' => 'Arrives by Thursday, September 23 by 12:00 PM'
                ]]
            ]
        ];

        $response['response'] = [
            'data' => $info,
            'hasError' => false,
            'message' => ''
        ];
        $serializedResult = '{"output":{}}';
        $responseValidateResult = [
            'hasError' => true,
            'message' => 'Error: API returned no delivery options',
            'data' => ''
        ];
        $response['response'] = $responseValidateResult;
        $response['status'] = false;

        $this->abstractApiClientMock->domain = 'https://test.fedex.com/';
        $this->punchoutHelper->expects($this->any())->method('getAuthGatewayToken')->willReturn($tokenData);
        $this->headerData->expects($this->any())->method('getAuthHeaderValue')->willReturn("client_id: ");
        $this->helper->expects($this->any())->method('createRequestPayload')->willReturn($requestBody);
        $this->abstractApiClientMock->expects($this->any())->method('execute')->willReturn($serializedResult);
        $this->helper->expects($this->any())->method('getdeliveryApiUrl')->willReturn('http://api-url.com');

        $this->assertEquals($response, $this->deliveryObject->getDeliveryInfo($data));
    }
    /**
     * @return void
     */
    public function testGetDeliveryInfoWithErrors()
    {
        $tokenData['access_token'] = '123224345';
        $tokenData['auth_token'] = '23345464';
        $tokenData = json_encode($tokenData);

        $data = [
            'products' => json_encode([["instanceId" => "sadsadad", "qty" => "2"]]),
            'postalCode' => '12345',
            'stateOrProvinceCode' => 'TX',
            'validateContent' => '123456'
        ];

        $requestBody = [
            'deliveryOptionsRequest' => [
                'fedExAccountNumber' => null,
                'site' => null,
                'products' => [(object)(["instanceId" => "sadsadad", "qty" => "2"])],
                'deliveries' => [],
                'validateContent' => false
            ]
        ];

        // Response with errors but no output
        $serializedResult = '{"hasError":"true","data":"","message":null,"errors":["Error message"]}';

        $responseValidateResult = [
            'hasError' => true,
            'message' => ['Error message'],
            'data' => ''
        ];

        $response['response'] = $responseValidateResult;
        $response['status'] = false;

        // Expect logger->error to be called
        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        $this->abstractApiClientMock->domain = 'https://test.fedex.com/';
        $this->punchoutHelper->expects($this->any())->method('getAuthGatewayToken')->willReturn($tokenData);
        $this->headerData->expects($this->any())->method('getAuthHeaderValue')->willReturn("client_id: ");
        $this->helper->expects($this->any())->method('createRequestPayload')->willReturn($requestBody);
        $this->abstractApiClientMock->expects($this->any())->method('execute')->willReturn($serializedResult);
        $this->helper->expects($this->any())->method('getdeliveryApiUrl')->willReturn('http://api-url.com');

        $this->assertEquals($response, $this->deliveryObject->getDeliveryInfo($data));
    }

    /**
     * @return void
     */
    public function testGetDeliveryInfoWithEmptyResult()
    {
        $tokenData['access_token'] = '123224345';
        $tokenData['auth_token'] = '23345464';
        $tokenData = json_encode($tokenData);

        $data = [
            'products' => json_encode([["instanceId" => "sadsadad", "qty" => "2"]]),
            'postalCode' => '12345',
            'stateOrProvinceCode' => 'TX',
            'validateContent' => '123456'
        ];

        $requestBody = [
            'deliveryOptionsRequest' => [
                'fedExAccountNumber' => null,
                'site' => null,
                'products' => [(object)(["instanceId" => "sadsadad", "qty" => "2"])],
                'deliveries' => [],
                'validateContent' => false
            ]
        ];

        $serializedResult = '{"errors":null}';
        $responseValidateResult = [
            'hasError' => true,
            'message' => null,
            'data' => ''
        ];

        $response['response'] = $responseValidateResult;
        $response['status'] = false;

        // Expect logger->error to be called with the "found no data" message
        $this->logger->expects($this->atLeastOnce())
            ->method('error');

        $this->abstractApiClientMock->domain = 'https://test.fedex.com/';
        $this->punchoutHelper->expects($this->any())->method('getAuthGatewayToken')->willReturn($tokenData);
        $this->headerData->expects($this->any())->method('getAuthHeaderValue')->willReturn("client_id: ");
        $this->helper->expects($this->any())->method('createRequestPayload')->willReturn($requestBody);
        $this->abstractApiClientMock->expects($this->any())->method('execute')->willReturn($serializedResult);
        $this->helper->expects($this->any())->method('getdeliveryApiUrl')->willReturn('http://api-url.com');

        $this->assertEquals($response, $this->deliveryObject->getDeliveryInfo($data));
    }
}
