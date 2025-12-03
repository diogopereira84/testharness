<?php

namespace Fedex\CatalogMvp\Helper;

use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use PHPUnit\Framework\TestCase;
use Magento\Framework\HTTP\Client\Curl;
use Magento\ServicesId\Model\ServicesConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Framework\Registry;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\LiveSearch\Api\ServiceClient;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use \Fedex\LiveSearch\Model\Config;
use Fedex\LiveSearch\ViewModel\Parameters;
use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\EnvironmentManager\Model\Config\CheckCatalogPermissionToTemplate;
use Fedex\EnvironmentManager\Model\Config\ByPassLiveSearchApiCacheToggle;
use Magento\Customer\Model\Session;
use Magento\Framework\Session\SessionManager;

class TestSessionStub extends SessionManager
{
    // Declare custom methods
    public function setProductListLimit($limit) {}
    public function getProductListLimit() {}

    // Declare methods that exist but are maybe protected or magic in parent
    public function getData($key = '', $clear = false) 
    {
        // You can add stub implementation or leave empty
    }

    public function setData($key, $value = null) { }
    public function unsetData($key = null) { }
}

class SharedCatalogLiveSearchTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Config\ScopeConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $scopeConfig;
    protected $category;
    protected $storeMock;
    protected $parametersMock;
    /**
     * @var (\Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $performanceImprovementPhase2;
    /**
     * @var (\Fedex\EnvironmentManager\Model\Config\CheckCatalogPermissionToTemplate & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $pageBrakeToggle;

    /**
     * @var (\Fedex\EnvironmentManager\Model\Config\ByPassLiveSearchApiCacheToggle & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $byPassLiveSearchApiCacheToggleMockup;

    /**
     * @var (\Magento\Catalog\Api\ProductRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $productRepository;
    protected $sharedCatalogLiveSearch;
    protected $curlMock;
    protected $servicesConfigMock;
    protected $requestMock;
    protected $storeManagerMock;
    protected $registryMock;
    protected $toggleConfigMock;
    protected $loggerMock;
    protected $cookieManagerMock;
    protected $serviceClientMock;
    protected $deliveryHelperMock;
    protected $configMock;
    protected $categoryMock;
    protected $catalogMvpHelperMock;
    protected $productCollectionFactoryMock;
    protected $productMock;
    protected $customerSessionLimit;

    protected function setUp(): void
    {
        $this->curlMock = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->servicesConfigMock = $this->createMock(ServicesConfigInterface::class);
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();
        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cookieManagerMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serviceClientMock = $this->getMockBuilder(ServiceClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->deliveryHelperMock = $this->getMockBuilder(DeliveryHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->category = $this->getMockBuilder(Category::class)
            ->addMethods(['getUrlPath'])
            ->onlyMethods(
                [
                    '__wakeup',
                    'getParentId',
                    'getLevel',
                    'dataHasChangedFor',
                    'getUrlKey',
                    'getStoreId',
                    'getId',
                    'formatUrlKey',
                    'getName',
                    'isObjectNew'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode'])
            ->getMock();

        $this->parametersMock = $this->getMockBuilder(Parameters::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSharedCatalogId'])
            ->getMock();

        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productCollectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productMock = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->performanceImprovementPhase2 = $this->getMockBuilder(PerformanceImprovementPhaseTwoConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageBrakeToggle = $this->getMockBuilder(CheckCatalogPermissionToTemplate::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepository = $this->getMockBuilder(\Magento\Catalog\Api\ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->byPassLiveSearchApiCacheToggleMockup = $this->getMockBuilder(ByPassLiveSearchApiCacheToggle::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customerSessionLimit = $this->getMockBuilder(Session::class)
            ->addMethods(['setProductListLimit', 'getProductListLimit']) // for methods that don't exist
            ->onlyMethods(['getData']) // for methods that do exist in the class
            ->disableOriginalConstructor()
            ->getMock();




        $this->sharedCatalogLiveSearch = new SharedCatalogLiveSearch(
            $this->createMock(\Magento\Framework\App\Helper\Context::class),
            $this->curlMock,
            $this->servicesConfigMock,
            $this->requestMock,
            $this->storeManagerMock,
            $this->scopeConfig,
            $this->toggleConfigMock,
            $this->loggerMock,
            $this->cookieManagerMock,
            $this->deliveryHelperMock,
            $this->configMock,
            $this->catalogMvpHelperMock,
            $this->productCollectionFactoryMock,
            $this->productMock,
            $this->parametersMock,
            $this->performanceImprovementPhase2,
            $this->pageBrakeToggle,
            $this->productRepository,
            $this->byPassLiveSearchApiCacheToggleMockup
        );
    }
    /**
     * Test testcurlCall function
     */
    public function testcurlCall()
    {

        $customerGroupCode = [
            "customerGroupCode" => 10
        ];

        $customerGroupCode = json_encode($customerGroupCode);
        $apiData = [
            "requestData" => $customerGroupCode,
            "method" => "POST"
        ];

        @define('CURLOPT_CUSTOMREQUEST', 10036);
        @define('CURLOPT_POSTFIELDS', 10015);
        @define('CURLOPT_RETURNTRANSFER', 19913);
        @define('CURLOPT_HTTPHEADER', 10023);
        @define('CURLOPT_ENCODING', 10102);

        $this->servicesConfigMock->expects($this->any())->method('getEnvironmentId')->willReturn('jasjfajdfhjahdfjahdfjajdhf');
        $this->storeManagerMock->expects($this->any())->method('getWebsite')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getCode')->willReturn('ondemand');
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getCode')->willReturn('ondemand');
        $this->servicesConfigMock->expects($this->any())->method('getSandboxApiKey')->willReturn('jasjfajdfhjahdfjahdfjajdhf');
        $this->configMock->expects($this->any())->method('getToggleValueForLiveSearchProductionMode')->willReturn(true);
        $this->servicesConfigMock->expects($this->any())->method('getProductionApiKey')->willReturn('jasjfajdfhjahdfjahdfjajdhf');
        $this->configMock->expects($this->any())->method('getServiceUrl')->willReturn('google.com');
        $this->curlMock->expects($this->any())->method('post')->willReturnSelf();
        $this->curlMock->expects($this->any())->method('getBody')->willReturn($customerGroupCode);
        $this->assertNotNull($this->sharedCatalogLiveSearch->curlCall($apiData));

    }
    /**
     * Test testcurlCallException function
     */
    public function testcurlCallException()
    {

        $customerGroupCode = [
            "customerGroupCode" => 10
        ];

        $customerGroupCode = json_encode($customerGroupCode);
        $apiData = [
            "requestData" => $customerGroupCode,
            "method" => "POST"
        ];

        $phrase = new Phrase(__('Exception message'));
        $e = new \Exception($phrase);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willThrowException($e);
        $this->storeManagerMock->expects($this->any())->method('getWebsite')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getCode')->willReturn('ondemand');
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getCode')->willReturn('ondemand');
        $this->servicesConfigMock->expects($this->any())->method('getSandboxApiKey')->willReturn('jasjfajdfhjahdfjahdfjajdhf');
        $this->configMock->expects($this->any())->method('getToggleValueForLiveSearchProductionMode')->willReturn(true);
        $this->servicesConfigMock->expects($this->any())->method('getProductionApiKey')->willReturn('jasjfajdfhjahdfjahdfjajdhf');
        $this->configMock->expects($this->any())->method('getServiceUrl')->willReturn('google.com');
        $this->curlMock->expects($this->any())->method('post')->willReturnSelf();
        $this->curlMock->expects($this->any())->method('getBody')->willReturn($customerGroupCode);

        $this->assertNotNull($this->sharedCatalogLiveSearch->curlCall($apiData));

    }

    public function productDetailsDataProvider(): array
    {
        return [
            'default_case' => [[
                "product_list_order" => "most_recent",
                "product_list_limit" => 10,
                "p" => 2,
                "product_list_mode" => 'list'
            ]],
            'default_case_with_new_product_toggle_off' => [[
                "product_list_order" => "most_recent",
                "product_list_limit" => null,
                "p" => 2,
                "product_list_mode" => 'list',
                "toggle_off_case" => true
            ]],
            'get_limit_from_session' => [[
                "product_list_order" => "0",
                "product_list_limit" => "0",
                "is_not_matching_controller" => true,
                "p" => 0,
                "product_list_mode" => 'list',
            ]],
            'with_limit_argument' => [[
                "product_list_order" => "name_asc",
                "product_list_limit" => 20,
                "p" => 2,
                "product_list_mode" => 'list'
            ]],
            'without_limit' => [[
                "product_list_order" => "name_desc",
                "product_list_limit" => null,
                "p" => 2,
                "product_list_mode" => 'list'
            ]],
            'default_filter' => [[
                "product_list_order" => "",
                "product_list_limit" => null,
                "p" => 2,
                "product_list_mode" => 'grid'
            ]]
        ];
    }
    
    public function testrequestData()
    {

        $requestDataArr = [
            "pageSize" => 10,
            "currentPage" => 1,
            "sortOrder" => "name",
            "sortBy" => "DESC",
            "categoryPath" => "this-is-test-path"
        ];

        $customerGroupCode = [
            "customerGroupCode" => 10
        ];

        $customerGroupCode = json_encode($customerGroupCode);
        $this->cookieManagerMock->expects($this->any())->method('getCookie')
            ->willReturn($customerGroupCode);
        $this->parametersMock->expects($this->any())->method('getSharedCatalogId')
            ->willReturn([8]);
        $this->byPassLiveSearchApiCacheToggleMockup->method('isActive')->willReturn(true);
        
        $this->assertNotNull($this->sharedCatalogLiveSearch->requestData($requestDataArr));
    }
    /**
     * Test testisEnabledCatalogPerformance function
     */
    public function testisEnabledCatalogPerformance()
    {
        $apiData = [
            'method' => 'POST',
            'requestData' => '{"example":"data"}',
        ];

        $this->deliveryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->assertNotNull($this->sharedCatalogLiveSearch->isEnabledCatalogPerformance());
    }

    /**
     * Test for Toggle for B-2193925 Product updated at toggle
     * @return void
     */
    public function testGetToggleStatusForNewProductUpdatedAtToggle()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->sharedCatalogLiveSearch->getToggleStatusForNewProductUpdatedAtToggle());
    }

    public function testSortingByMostRecentWithToggle()
    {
        $this->requestMock->method('getFullActionName')->willReturn('catalog_category_view');
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->requestMock->method('getParams')->willReturn([
            'product_list_order' => 'most_recent',
            'p' => 1,
            'product_list_mode' => 'grid',
        ]);
        $this->requestMock->method('getParam')->willReturnMap([
            ['product_list_mode', 'list', 'grid'],
            ['product_list_limit', null],
        ]);

        $this->catalogMvpHelperMock->method('getOrCreateCustomerSession')->willReturn($this->createMock(TestSessionStub::class));
        $this->catalogMvpHelperMock->method('getCurrentCategory')->willReturn($this->category);
        $this->category->method('getUrlPath')->willReturn('new-category');

        $this->scopeConfig->method('getValue')->willReturn(15); // default per page
        $this->catalogMvpHelperMock->method('sortingToggle')->willReturn(false);

        $this->sharedCatalogLiveSearch = $this->getMockBuilder(\Fedex\CatalogMvp\Helper\SharedCatalogLiveSearch::class)
            ->setConstructorArgs([
                $this->createMock(\Magento\Framework\App\Helper\Context::class),
                $this->curlMock,
                $this->servicesConfigMock,
                $this->requestMock,
                $this->storeManagerMock,
                $this->scopeConfig,
                $this->toggleConfigMock,
                $this->loggerMock,
                $this->cookieManagerMock,
                $this->deliveryHelperMock,
                $this->configMock,
                $this->catalogMvpHelperMock,
                $this->productCollectionFactoryMock,
                $this->productMock,
                $this->parametersMock,
                $this->performanceImprovementPhase2,
                $this->pageBrakeToggle,
                $this->productRepository,
                $this->byPassLiveSearchApiCacheToggleMockup
            ])
            ->onlyMethods(['curlCall', 'requestData', 'getToggleStatusForNewProductUpdatedAtToggle'])
            ->getMock();

        $this->sharedCatalogLiveSearch->method('curlCall')->willReturn('{"products": []}');
        $this->sharedCatalogLiveSearch->method('requestData')->willReturn(['query']);
        $this->sharedCatalogLiveSearch->method('getToggleStatusForNewProductUpdatedAtToggle')->willReturn(true);

        $this->assertEquals('{"products": []}', $this->sharedCatalogLiveSearch->getProductDeatils());
    }

    public function testGetProductDetailsViewModeChangedList()
    {
        // Arrange
        $this->requestMock->method('getFullActionName')->willReturn('catalog_category_view');

        $this->requestMock->method('getParams')->willReturn([
            'product_list_order' => 'name_asc',
            'p' => 1,
            'product_list_limit' => null,
        ]);

        $this->requestMock->method('getParam')
            ->willReturnMap([
                ['product_list_mode', 'list', 'list'],
                ['product_list_limit', null, null],
            ]);

        $customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->addMethods(['setData', 'unsetData'])
            ->getMock();

        $customerSessionMock->expects($this->once())
            ->method('getData')
            ->with('previous_view_mode')
            ->willReturn('grid'); // triggers viewModeChanged

        $customerSessionMock->expects($this->exactly(2))
            ->method('setData')
            ->withConsecutive(
                ['previous_view_mode', 'list'],
                ['ProductListLimitList', $this->anything()]
            );

        $customerSessionMock->expects($this->once())
            ->method('unsetData')
            ->with('ProductListLimitGrid');

        $this->catalogMvpHelperMock->method('getOrCreateCustomerSession')->willReturn($customerSessionMock);
        $this->catalogMvpHelperMock->method('sortingToggle')->willReturn(false);
        $this->catalogMvpHelperMock->method('getCurrentCategory')->willReturn($this->category);
        $this->category->method('getUrlPath')->willReturn('category/url-path');

        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                ['catalog/frontend/list_per_page', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, 20],
                ['catalog/frontend/grid_per_page', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, 30],
            ]);

        // Mock curlCall and requestData in a partial mock
        $sharedCatalogLiveSearch = $this->getMockBuilder(SharedCatalogLiveSearch::class)
            ->setConstructorArgs([
                $this->createMock(\Magento\Framework\App\Helper\Context::class),
                $this->curlMock,
                $this->servicesConfigMock,
                $this->requestMock,
                $this->storeManagerMock,
                $this->scopeConfig,
                $this->toggleConfigMock,
                $this->loggerMock,
                $this->cookieManagerMock,
                $this->deliveryHelperMock,
                $this->configMock,
                $this->catalogMvpHelperMock,
                $this->productCollectionFactoryMock,
                $this->productMock,
                $this->parametersMock,
                $this->performanceImprovementPhase2,
                $this->pageBrakeToggle,
                $this->productRepository,
                $this->byPassLiveSearchApiCacheToggleMockup
            ])
            ->onlyMethods(['curlCall', 'requestData'])
            ->getMock();

        $sharedCatalogLiveSearch->expects($this->once())
            ->method('requestData')
            ->willReturn(['dummyRequest']);

        $sharedCatalogLiveSearch->expects($this->once())
            ->method('curlCall')
            ->with($this->callback(function ($arg) {
                return isset($arg['method']) && $arg['method'] === 'POST';
            }))
            ->willReturn('{"success":true}');

        // Act
        $result = $sharedCatalogLiveSearch->getProductDeatils();

        // Assert
        $this->assertEquals('{"success":true}', $result);
    }

    public function testGetProductDetails_ListMode_WithLimit()
    {
        // Arrange
        $this->requestMock->method('getFullActionName')->willReturn('catalog_category_view');
        
        $this->requestMock->method('getParams')->willReturn([
            'product_list_order' => 'name_asc',
            'p' => 1,
            'product_list_limit' => 25,
        ]);

        $this->requestMock->method('getParam')->willReturnMap([
            ['product_list_mode', 'list', 'list'],
            ['product_list_limit', null, 25],
        ]);

        $customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->addMethods(['setData', 'unsetData'])
            ->getMock();

        $customerSessionMock->method('getData')->with('previous_view_mode')->willReturn('list');

        $customerSessionMock->expects($this->atLeastOnce())
            ->method('setData')
            ->withConsecutive(
                ['previous_view_mode', 'list'],
                ['ProductListLimitList', 25]
            );

        $this->catalogMvpHelperMock->method('getOrCreateCustomerSession')->willReturn($customerSessionMock);
        $this->catalogMvpHelperMock->method('sortingToggle')->willReturn(false);
        $this->catalogMvpHelperMock->method('getCurrentCategory')->willReturn($this->category);
        $this->category->method('getUrlPath')->willReturn('category/url-path');

        $this->scopeConfig->method('getValue')->willReturnMap([
            ['catalog/frontend/list_per_page', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, 20],
            ['catalog/frontend/grid_per_page', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, 30],
        ]);

        $this->sharedCatalogLiveSearch = $this->getMockBuilder(SharedCatalogLiveSearch::class)
            ->setConstructorArgs([
                $this->createMock(\Magento\Framework\App\Helper\Context::class),
                $this->curlMock,
                $this->servicesConfigMock,
                $this->requestMock,
                $this->storeManagerMock,
                $this->scopeConfig,
                $this->toggleConfigMock,
                $this->loggerMock,
                $this->cookieManagerMock,
                $this->deliveryHelperMock,
                $this->configMock,
                $this->catalogMvpHelperMock,
                $this->productCollectionFactoryMock,
                $this->productMock,
                $this->parametersMock,
                $this->performanceImprovementPhase2,
                $this->pageBrakeToggle,
                $this->productRepository,
                $this->byPassLiveSearchApiCacheToggleMockup
            ])
            ->onlyMethods(['curlCall', 'requestData'])
            ->getMock();

        $this->sharedCatalogLiveSearch->expects($this->once())
            ->method('requestData')
            ->willReturn(['dummy']);

        $this->sharedCatalogLiveSearch->expects($this->once())
            ->method('curlCall')
            ->with($this->callback(function ($arg) {
                return isset($arg['method']) && $arg['method'] === 'POST';
            }))
            ->willReturn('{"success":true}');

        $result = $this->sharedCatalogLiveSearch->getProductDeatils();

        $this->assertEquals('{"success":true}', $result);
    }


}