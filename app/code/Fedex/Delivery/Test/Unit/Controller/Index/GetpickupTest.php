<?php
namespace Fedex\Delivery\Test\Unit\Controller\Index;

use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Controller\ResultInterface;
use Fedex\Delivery\Controller\Index\Getpickup;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Fedex\Delivery\Helper\Delivery;
use Fedex\InBranch\Model\InBranchValidation;

class GetpickupTest extends TestCase
{
    protected $cart;
    protected $quote;
    protected $item;
    protected $option;
    /**
     * @var (\Magento\Framework\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    /**
     * @var (\Fedex\Company\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyHelper;
    protected $productionLocationCollection;
    protected $productionLocation;
    protected $helper;
    protected $deliveryHelper;
    protected $company;
    /**
     * @var (\Fedex\Delivery\Logger\Logger & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $customapilogger;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $logger;
    protected $toggleConfig;
    protected $resultJsonFactory;
    protected $curl;
    /**
     * @var (\Fedex\InBranch\Model\InBranchValidation & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $inBranchValidationMock;
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $getPickup;
    private const EXPLORERS_RESTRICTED_AND_RECOMMENDED_PRODUCTION = 'explorers_restricted_and_recommended_production';
    private const SGC_PROMISE_TIME_TOGGLE = 'sgc_promise_time_pickup_options';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $configInterface;

    /**
     * @var \Magento\Checkout\Model\CartFactory
     */
    protected $cartFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Fedex\Punchout\Helper\Data $gateTokenHelper;
     */
    protected $gateTokenHelper;

    /**
     * @var \Fedex\Shipto\Model\ProductionLocationFactory $productionLocationFactory;
     */
    protected $productionLocationFactory;

    /**
     * @var \Magento\Company\Api\CompanyRepositoryInterface
     */
    protected $companyRepository;

    /**
     * @var CartDataHelper
     */
    protected $cartDataHelper;

    /**
     * @var QuoteHelper
     */
    private QuoteHelper $quoteHelper;

    /**
     * Creating the Mock.
     *
     * @author  Infogain <Team_Explorer@infogain.com>
     * @license Reserve For fedEx
     * @return  MockBuilder
     */
    protected function setUp(): void
    {
        $this->cartFactory = $this->getMockBuilder(\Magento\Checkout\Model\CartFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cart = $this->getMockBuilder(\Magento\Checkout\Model\Cart::class)
            ->setMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->setMethods([
                'getShippingAddress',
                'getAllItems',
                'getData',
                'setGrandTotal',
                'setBaseGrandTotal',
                'setCustomTaxAmount',
                'setShippingMethod',
                'save',
                'setCustomerShippingAddress',
                'setIsFromShipping',
                'getAllVisibleItems',
                'hasItems'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->item = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->setMethods(['getOptionByCode'])
            ->disableOriginalconstructor()
            ->getMock();

        $this->option = $this->getMockBuilder(Magento\Quote\Model\Quote\Item\Option::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->configInterface = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->context = $this->getMockBuilder(\Magento\Framework\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->setMethods(['getPostValue','getContent'])
            ->getMockForAbstractClass();

        $this->gateTokenHelper = $this->getMockBuilder(\Fedex\Punchout\Helper\Data::class)
            ->setMethods(['getTazToken', 'getAuthGatewayToken'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyHelper = $this->getMockBuilder(\Fedex\Company\Helper\Data::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productionLocationCollection =
            $this->getMockBuilder(\Fedex\Shipto\Model\ResourceModel\ProductionLocation\Collection::class)
                ->disableOriginalConstructor()
                ->setMethods(['addFieldToFilter','getSize','getIterator'])
                ->getMock();

        $this->cartDataHelper = $this->getMockBuilder(\Fedex\Cart\Helper\Data::class)
            ->setMethods(['decryptData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productionLocationFactory = $this->getMockBuilder(\Fedex\Shipto\Model\ProductionLocationFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productionLocation = $this->getMockBuilder(\Fedex\Shipto\Model\ProductionLocation::class)
            ->disableOriginalConstructor()
            ->setMethods(['load','getCollection','delete','loadByIncrementId','getSize'])
            ->getMock();

        $this->helper = $this->getMockBuilder(\Fedex\Delivery\Helper\Data::class)
            ->setMethods(['isCommercialCustomer','getAssignedCompany','getId','getCompanySite','updateDateTimeFormat'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryHelper = $this->getMockBuilder(Delivery::class)
            ->setMethods(['isDeliveryApiMockEnabled','getDeliveryMockApiUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->company = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyInterface::class)
            ->setMethods([
                'getAllowProductionLocation',
                'getFedexAccountNumber',
                'getProductionLocationOption',
                'getShippingAccountNumber',
                'getExtensionAttributes',
                'getHcToggle'
            ])->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customapilogger = $this->getMockBuilder(\Fedex\Delivery\Logger\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->toggleConfig = $this->getMockBuilder(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyRepository = $this->getMockBuilder(\Magento\Company\Api\CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','setData'])
            ->getMockForAbstractClass();

        $this->curl = $this->getMockBuilder(Curl::class)
            ->setMethods(['getBody', 'get'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->inBranchValidationMock = $this->createMock(InBranchValidation::class);

        $this->quoteHelper = $this->createMock(QuoteHelper::class);

        $this->objectManagerHelper  = new ObjectManagerHelper($this);
        $this->getPickup = $this->objectManagerHelper->getObject(
            Getpickup::class,
            [
                'cartFactory'               => $this->cartFactory,
                'helper'                    => $this->helper,
                'companyHelper'             => $this->companyHelper,
                'configInterface'           => $this->configInterface,
                'logger'                    => $this->logger,
                'customapilogger'           => $this->customapilogger,
                'request'                   => $this->request,
                'gateTokenHelper'           => $this->gateTokenHelper,
                'productionLocationFactory' => $this->productionLocationFactory,
                'toggleConfig'              => $this->toggleConfig,
                'companyRepository'         => $this->companyRepository,
                'curl'                      => $this->curl,
                'resultJsonFactory'         => $this->resultJsonFactory,
                'cartDataHelper'            => $this->cartDataHelper,
                'context'                   => $this->context,
                'quoteHelper'               => $this->quoteHelper,
                'inBranchValidation'        =>$this->inBranchValidationMock
            ]
        );
    }

    /**
     * Test execute.
     *
     * @return array
     */
    public function testExecute()
    {
        $tazToken = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJ
        pc3MiOiJ0YXoiLCJleHAiOjE2NjY3MzcwODQsImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVt
        LnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImVmN2YxN2ZiLWQ0YzEtNDhjZi04ZmNhLWFkNmU3ZWMyYWJhNCIsImNsa
        WVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.RZe5AgGm2sWBemKc2hEkqVou6hY-har5bl8YrRoyD
        ykIaltcKsZMLGzaasQD8EY-fn43bMhvbup3PEicStLahCemd-YCrtLH6bYwD-8peog6-1fUnGolEaBbFAiNXMIor-YMVN
        vYieaa7YLd6s59nHxn54gKeCGoiRIVhOVtsk98wE4RPwrv_aKfDKGi26qH9A8kimDO5kRXeJULkcoostYal_M7Zu-Ydxm
        Xhg0sXj2TrIsENYSl5-AAnCCvYwY8ARjP8QxGQaiujfafPgdylzx1Y1MWT03rVhjc4mR3BjivsFa6nAobEWskSEwOGkMB
        jON2ff-qU4IQqtSRS3nNlEIIV9_FVjPEekRsGMI3KPUYQNdzRKHFb8SrkNbFeb5P_LWTWX-eZK4AXNhUKv11Y3J-K5QpPb
        OFbHVleglVdn2Qv4brn_oGROo2gkX5vxwIqC3F8TMWcyxFjifscSVkfmTWhmo1oMUQthQq1DKBqzgiVvnp5fYzc2nuwRp4
        msPOU_MTn2hzWm4M29pbYlX7KtvxaN1sw5QIhv6vJpcqgTs_1SJ66M-jdctFr0guhQtm-cNusbivw8qoBaKEnj3L4Bbve7
        HqiGynn5Wgrn1vl6egAN2Q4D-9gLFdzPG309UqTlhILb3EpZ4HIMc9JMmgDRivYM7B56qw16CKSsW6jW0';

        $requestData =  [
            'zipcode' => '75824',
            'city' => 'Plano',
            'stateCode' => 'TX',
            'radius' => '10',
        ];

        $response = json_encode(
            [
                'output' => [
                    'deliveryOptions' => [
                        0 => [
                            'pickupOptions' =>  [
                                0 => [
                                    'estimatedDeliveryLocalTime' => '2022-12-02T16:00:00',
                                    'location' => []
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->cartFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->cart);

        $this->cart->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->quote);

        $this->resultJsonFactory
            ->method('create')
            ->willReturnSelf();

        $this->request->expects($this->any())
            ->method('getPostValue')
            ->willReturn($requestData);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->helper->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);

        $this->helper->expects($this->any())
            ->method('getAssignedCompany')
            ->willReturnSelf();

        $this->helper->expects($this->any())
            ->method('getId')
            ->willReturn('TestId');

        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->company);

        $this->company->expects($this->any())
            ->method('getAllowProductionLocation')
            ->willReturn(1);

        $this->company->expects($this->any())
            ->method('getProductionLocationOption')
            ->willReturn('recommended_location_all_locations');

        $this->productionLocationFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productionLocation);

        $this->productionLocation->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->productionLocationCollection);

        $this->productionLocationCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->productionLocationCollection->expects($this->any())
            ->method('getSize')
            ->willReturn(1);

        $this->productionLocationCollection->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->productionLocation]));

        $this->quote->expects($this->any())
            ->method('getAllItems')
            ->willReturn([0 => $this->item]);

        $this->quote->expects($this->any())
            ->method('getData')
            ->willReturn('4111 1121 1111 1111');

        $this->cartDataHelper->expects($this->any())
            ->method('decryptData')
            ->willReturn('4111 1311 1111 1111');

        $this->item->expects($this->any())
            ->method('getOptionByCode')
            ->willReturn($this->option);

        $prodarray['external_prod'][0] = [
            'instanceId'       => 0,
            'catalogReference' => 1,
            'preview_url'      => 'url',
            'fxo_product'      => 'product',
        ];

        $this->option->expects($this->any())
            ->method('getValue')
            ->willReturn(json_encode($prodarray));

        $this->helper->expects($this->any())
            ->method('getCompanySite')
            ->willReturn('TestSite');

        $this->gateTokenHelper->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn('4f5e303d-c10f-40bf-a586-16e177faaec3');

        $this->gateTokenHelper->expects($this->any())
            ->method('getTazToken')
            ->willReturn($tazToken);

        $this->configInterface->expects($this->any())
            ->method('getValue')
            ->with("fedex/general/delivery_api_url")
            ->willReturn('https://api.test.office.fedex.com/order/fedexoffice/v2/deliveryoptions');

        $this->deliveryHelper->expects($this->any())
            ->method('isDeliveryApiMockEnabled')
            ->willReturn(true);

        $this->deliveryHelper->expects($this->any())
            ->method('getDeliveryMockApiUrl')
            ->willReturn("https://localhost:2424/order/fedexoffice/v2/deliveryoptions");

        $this->curl->expects($this->any())
            ->method('get')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($response);

        $this->resultJsonFactory->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->assertNotNull($this->getPickup->execute());
    }

    /**
     * Test execute.
     *
     * @return void
     */
    public function testExecuteWithCitAndStateCodeNull()
    {
        $requestData =  [
            'zipcode' => '75024',
            'city' => null,
            'stateCode' => null,
            'radius' => '10',
        ];

        $response = '';

        $this->cartFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->cart);

        $this->cart->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->quote);

        $this->resultJsonFactory
            ->method('create')
            ->willReturnSelf();

        $this->request->expects($this->any())
            ->method('getPostValue')
            ->willReturn($requestData);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->helper->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);

        $this->helper->expects($this->any())
            ->method('getAssignedCompany')
            ->willReturnSelf();

        $this->helper->expects($this->any())
            ->method('getId')
            ->willReturn('TestId');

        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->company);

        $this->company->expects($this->any())
            ->method('getAllowProductionLocation')
            ->willReturn(1);

        $this->company->expects($this->any())
            ->method('getProductionLocationOption')
            ->willReturn('recommended_location_all_locations');

        $this->productionLocationFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productionLocation);

        $this->productionLocation->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->productionLocationCollection);

        $this->productionLocationCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->productionLocationCollection->expects($this->any())
            ->method('getSize')
            ->willReturn(1);

        $this->productionLocationCollection->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->productionLocation]));

        $this->quote->expects($this->any())
            ->method('getAllVisibleItems')
            ->willReturn([0 => $this->item]);
        $this->quote->expects($this->any())
            ->method('hasItems')
            ->willReturn(1);
        $this->quote->expects($this->any())
            ->method('getData')
            ->willReturn('4111 1111 1111 1111');

        $this->item->expects($this->any())
            ->method('getOptionByCode')
            ->willReturn($this->option);

        $prodarray['external_prod'][0] = [
            'instanceId'       => 0,
            'catalogReference' => 1,
            'preview_url'      => 'url',
            'fxo_product'      => 'product',
        ];

        $this->option->expects($this->any())
            ->method('getValue')
            ->willReturn(json_encode($prodarray));

        $this->helper->expects($this->any())
            ->method('getCompanySite')
            ->willReturn('TestSite');

        $this->curl->expects($this->any())
            ->method('get')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($response);

        $this->resultJsonFactory->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->assertNotNull($this->getPickup->execute());
    }

    /**
     * Test execute.
     *
     * @return array
     */
    public function testExecuteWithException()
    {
        $this->cartFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->cart);

        $this->cart->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->quote);

        $this->resultJsonFactory
            ->method('create')
            ->willReturnSelf();

        $exception = new \Exception();

        $this->request->expects($this->any())
            ->method('getPostValue')
            ->willThrowException($exception);

        $this->assertNull($this->getPickup->execute());
    }

    /**
     * Test execute.
     *
     * @return array
     */
    public function testgetLocationsData()
    {
        $recommendedIds = [];
        $response = [
            'output' => [
                'deliveryOptions' => [
                    0 => [
                        'pickupOptions' => [
                            0 => [
                                'estimatedDeliveryLocalTime' => '2022-12-02T16:00:00',
                                'is_recommended' =>false,
                                'location' =>[
                                    'id' => '3111',
                                    'name' => 'Plano TX Central Expressway',
                                    'type' => 'OFFICE_PRINT',
                                    'preferredLocation' => false,
                                    'address' =>
                                        [
                                            'streetLines' =>
                                                [
                                                    0 => '925 Central Expy',
                                                    1 => 'Ste 100',
                                                ],
                                            'city' => 'Plano',
                                            'stateOrProvinceCode' => 'TX',
                                            'postalCode' => '75075',
                                            'countryCode' => 'US',
                                        ],
                                    'premium' => false,
                                    'geoCode' =>
                                        [
                                            'latitude' => '33.0138647',
                                            'longitude' => '-96.7094454',
                                        ],
                                ],
                                'availableOrderPriorities' => [
                                    0 => [
                                        'orderPriority' => 'STANDARD',
                                        'estimatedDeliveryLocalTime' => '2022-12-13T16:00:00',
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->helper->expects($this->any())
        ->method('getAssignedCompany')
        ->willReturnSelf();
        $this->helper->expects($this->any())
        ->method('getId')
        ->willReturn('TestId');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->withConsecutive([self::EXPLORERS_RESTRICTED_AND_RECOMMENDED_PRODUCTION], [self::SGC_PROMISE_TIME_TOGGLE])
            ->willReturnOnConsecutiveCalls(true, true);
        $this->helper->expects($this->any())
            ->method('updateDateTimeFormat')
            ->willReturn('Friday, December 13, 4:00pm');


        $this->assertNotNull($this->getPickup->getLocationsData($response,$recommendedIds));
    }

    public function testGetRestrictedOrRecommendedLocationsWithToggle()
    {
        $isCommercialCustomer = true;
        $isCalledForPickup = false;
        $companyId = null;

        $this->helper->expects($this->any())
        ->method('getAssignedCompany')
        ->willReturnSelf();
        $this->helper->expects($this->any())
        ->method('getId')
        ->willReturn(98);

        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->company);

        $this->company->expects($this->any())
            ->method('getAllowProductionLocation')
            ->willReturn(1);

        $this->company->expects($this->any())
            ->method('getProductionLocationOption')
            ->willReturn('recommended_location_all_locations');

        $this->productionLocationFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productionLocation);

        $this->productionLocation->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->productionLocationCollection);

        $this->productionLocationCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->productionLocationCollection->expects($this->any())
            ->method('getSize')
            ->willReturn(10);

        $this->productionLocationCollection->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->productionLocation]));


        // Mocking toggleConfig value
        $this->toggleConfig->method('getToggleConfigValue')
            ->with(self::EXPLORERS_RESTRICTED_AND_RECOMMENDED_PRODUCTION)
            ->willReturn(true);

        $result = $this->getPickup->getRestrictedOrRecommendedLocations($isCommercialCustomer, $isCalledForPickup);
        // Assert that the result contains the correct recommended location IDs
        $this->assertEquals([$companyId], $result);
    }

    public function testGetRestrictedOrRecommendedLocationsWithoutToggle()
    {
        $isCommercialCustomer = true;
        $isCalledForPickup = false;
        $companyId = null;

        $this->helper->expects($this->any())
        ->method('getAssignedCompany')
        ->willReturnSelf();
        $this->helper->expects($this->any())
        ->method('getId')
        ->willReturn('TestId');

        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->company);

        $this->company->expects($this->any())
            ->method('getAllowProductionLocation')
            ->willReturn(1);

        $this->company->expects($this->any())
            ->method('getProductionLocationOption')
            ->willReturn('recommended_location_all_locations');

        $this->productionLocationFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productionLocation);

        $this->productionLocation->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->productionLocationCollection);

        $this->productionLocationCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->productionLocationCollection->expects($this->any())
            ->method('getSize')
            ->willReturn(1);

        $this->productionLocationCollection->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->productionLocation]));

        $this->toggleConfig->method('getToggleConfigValue')
            ->with(self::EXPLORERS_RESTRICTED_AND_RECOMMENDED_PRODUCTION)
            ->willReturn(false);

        $result = $this->getPickup->getRestrictedOrRecommendedLocations($isCommercialCustomer, $isCalledForPickup);

        $this->assertEquals([$companyId], $result);
    }

}
