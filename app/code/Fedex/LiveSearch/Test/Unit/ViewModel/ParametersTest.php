<?php

namespace Fedex\LiveSearch\Test\Unit\ViewModel;

use Fedex\LiveSearch\ViewModel\Parameters;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Fedex\ProductUnavailabilityMessage\Model\CheckProductAvailabilityDataModel;
use Magento\Catalog\Helper\Image;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\Session;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\ServicesId\Model\ServicesConfigInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\Catalog\Model\Config;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\LiveSearch\Api\Data\ConfigInterface;
use Fedex\Punchout\Helper\Data as PunchOutHelper;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use PHPUnit\Framework\TestCase;

class ParametersTest extends TestCase
{
    private $imageHelper;
    private $config;
    private $servicesConfig;
    private $deliveryHelper;
    private $punchOutHelper;
    private $storeManager;
    private $catalogConfig;
    private $customerSession;
    private $searchCriteriaBuilder;
    private $sharedCatalogRepository;
    private $catalogMvp;
    private $authHelper;
    private $toggleConfig;
    private $checkProductAvailabilityDataModel;
    private $marketplaceCheckoutHelper;
    private $attributeSetRepository;
    private $saasCommonConfig;
    private $parameters;

    protected function setUp(): void
    {
        $this->imageHelper = $this->createMock(Image::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->servicesConfig = $this->createMock(ServicesConfigInterface::class);
        $this->deliveryHelper = $this->createMock(DeliveryHelper::class);
        $this->punchOutHelper = $this->createMock(PunchOutHelper::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStore'])
            ->getMockForAbstractClass();
        $this->catalogConfig = $this->createMock(Config::class);
        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn', 'getCustomerGroupId'])
            ->addMethods(['getSharedCatalogId', 'setSharedCatalogId'])
            ->getMock();
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->sharedCatalogRepository = $this->createMock(SharedCatalogRepositoryInterface::class);
        $this->catalogMvp = $this->getMockBuilder(CatalogMvp::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getParentGroupId', 'isSelfRegCustomerAdmin', 'getChildGroupIds'])
        ->getMock();
        $this->authHelper = $this->getMockBuilder(AuthHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkProductAvailabilityDataModel = $this->createMock(CheckProductAvailabilityDataModel::class);
        $this->marketplaceCheckoutHelper = $this->createMock(MarketplaceCheckoutHelper::class);
        $this->attributeSetRepository = $this->createMock(AttributeSetRepositoryInterface::class);
        $this->saasCommonConfig = $this->createMock(\Fedex\SaaSCommon\Api\ConfigInterface::class);

        $this->parameters = new Parameters(
            $this->imageHelper,
            $this->config,
            $this->servicesConfig,
            $this->deliveryHelper,
            $this->punchOutHelper,
            $this->storeManager,
            $this->catalogConfig,
            $this->customerSession,
            $this->searchCriteriaBuilder,
            $this->sharedCatalogRepository,
            $this->catalogMvp,
            $this->authHelper,
            $this->toggleConfig,
            $this->checkProductAvailabilityDataModel,
            $this->marketplaceCheckoutHelper,
            $this->attributeSetRepository,
            $this->saasCommonConfig
        );
    }

    public function testGetDefaultPlaceholderUrl()
    {
        $type = 'image';
        $expectedUrl = 'http://example.com/placeholder.jpg';

        $this->imageHelper->method('getDefaultPlaceholderUrl')
            ->with($type)
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->parameters->getDefaultPlaceholderUrl($type));
    }

    public function testGetServiceUrl()
    {
        $expectedUrl = 'http://example.com/service';

        $this->config->method('getServiceUrl')
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->parameters->getServiceUrl());
    }

    public function testGetGraphqlServiceUrl()
    {
        $baseUrl = 'http://example.com/';
        $expectedUrl = $baseUrl . 'graphql';

        $storeInterface = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseUrl'])
            ->getMockForAbstractClass();
        $this->storeManager->method('getStore')
            ->willReturn($storeInterface);
        $storeInterface->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_WEB)
            ->willReturn($baseUrl);

        $this->assertEquals($expectedUrl, $this->parameters->getGraphqlServiceUrl());
    }

    public function testGetXApiKey()
    {
        $sandboxApiKey = 'sandbox_key';
        $productionApiKey = 'production_key';

        $this->config->method('getToggleValueForLiveSearchProductionMode')
            ->willReturn(false);
        $this->servicesConfig->method('getSandboxApiKey')
            ->willReturn($sandboxApiKey);

        $this->assertEquals($sandboxApiKey, $this->parameters->getXApiKey());

        $this->resetMockConfiguration();

        $this->config->method('getToggleValueForLiveSearchProductionMode')
            ->willReturn(true);
        $this->servicesConfig->method('getProductionApiKey')
            ->willReturn($productionApiKey);

        $this->assertEquals($productionApiKey, $this->parameters->getXApiKey());
    }

    public function testGetSiteName()
    {
        $expectedSiteName = 'Fedex';

        $this->deliveryHelper->method('getCompanySite')
            ->willReturn($expectedSiteName);

        $this->assertEquals($expectedSiteName, $this->parameters->getSiteName());
    }

    public function testGetTazToken()
    {
        $expectedToken = 'taz_token';

        $this->punchOutHelper->method('getTazToken')
            ->willReturn($expectedToken);

        $this->assertEquals($expectedToken, $this->parameters->getTazToken());
    }

    public function testIsCommercialCustomer()
    {
        $this->deliveryHelper->method('isCommercialCustomer')
            ->willReturn(true);

        $this->assertTrue($this->parameters->isCommercialCustomer());
    }

    public function testIsDisplayUnitCost3p1pProductsToggleEnabled()
    {
        $this->catalogConfig->method('getTigerDisplayUnitCost3P1PProducts')
            ->willReturn(true);

        $this->assertTrue($this->parameters->isDisplayUnitCost3p1pProductsToggleEnabled());
    }

    public function testIsEllipsisControlEnabled()
    {
        $this->config->method('isEllipsisControlEnabled')
            ->willReturn(true);

        $this->assertTrue($this->parameters->isEllipsisControlEnabled());
    }

    public function testGetTigerB2315919Toggle()
    {
        $this->catalogConfig->method('getTigerB2315919Toggle')
            ->willReturn(true);

        $this->assertTrue($this->parameters->getTigerB2315919Toggle());
    }

    public function testGetEllipsisControlTotalCharacters()
    {
        $expectedTotalCharacters = 100;

        $this->config->method('getEllipsisControlTotalCharacters')
            ->willReturn($expectedTotalCharacters);

        $this->assertEquals($expectedTotalCharacters, $this->parameters->getEllipsisControlTotalCharacters());
    }

    public function testGetEllipsisControlStartCharacters()
    {
        $expectedStartCharacters = 50;

        $this->config->method('getEllipsisControlStartCharacters')
            ->willReturn($expectedStartCharacters);

        $this->assertEquals($expectedStartCharacters, $this->parameters->getEllipsisControlStartCharacters());
    }

    public function testGetEllipsisControlEndCharacters()
    {
        $expectedEndCharacters = 20;

        $this->config->method('getEllipsisControlEndCharacters')
            ->willReturn($expectedEndCharacters);

        $this->assertEquals($expectedEndCharacters, $this->parameters->getEllipsisControlEndCharacters());
    }

    public function testGetSharedCatalogId()
    {
        $expectedSharedCatalogId = [1];
        $this->config->method('getGuestUserSharedCatalogId')
            ->willReturn(1);

        $this->deliveryHelper->method('isCommercialCustomer')
            ->willReturn(true);

        $this->customerSession->method('isLoggedIn')
            ->willReturn(true);

        $this->catalogMvp->method('getParentGroupId')
            ->willReturn(11);

        $this->customerSession->method('getSharedCatalogId')
            ->willReturn($expectedSharedCatalogId);

        $this->assertEquals($expectedSharedCatalogId, $this->parameters->getSharedCatalogId());
    }
    public function testGetSharedCatalogIdAuthToggleOn()
    {
        $expectedSharedCatalogId = [1];
        $this->config->method('getGuestUserSharedCatalogId')
            ->willReturn(1);

        $this->deliveryHelper->method('isCommercialCustomer')
            ->willReturn(true);

        $this->authHelper->method('isLoggedIn')
            ->willReturn(true);

        $this->catalogMvp->method('getParentGroupId')
            ->willReturn(11);

        $this->customerSession->method('getSharedCatalogId')
            ->willReturn($expectedSharedCatalogId);

        $this->assertEquals($expectedSharedCatalogId, $this->parameters->getSharedCatalogId());
    }

    public function testGetSharedCatalogIdForGuestUser()
    {
        $expectedSharedCatalogId = 2;
        $this->config->method('getGuestUserSharedCatalogId')
            ->willReturn($expectedSharedCatalogId);
        $this->deliveryHelper->method('isCommercialCustomer')
            ->willReturn(false);

        $this->assertEquals([$expectedSharedCatalogId], $this->parameters->getSharedCatalogId());
    }

    public function testGetSharedCatalogIdForLoggedInUserWithoutSharedCatalogId()
    {
        $customerGroupId = 4;
        $sharedCatalogIdFromRepository = 0;

        $this->setupSharedCatalogRepositoryMock($customerGroupId, $sharedCatalogIdFromRepository);

        $this->deliveryHelper->method('isCommercialCustomer')
            ->willReturn(true);
        $this->customerSession->method('isLoggedIn')
            ->willReturn(true);
        $this->customerSession->method('getSharedCatalogId')
            ->willReturn(null);
        $this->customerSession->method('getCustomerGroupId')
            ->willReturn($customerGroupId);
        $this->customerSession->method('setSharedCatalogId')
            ->willReturnSelf();

        $this->assertEquals([$sharedCatalogIdFromRepository], $this->parameters->getSharedCatalogId());
    }
    public function testGetSharedCatalogIdForLoggedInUserWithoutSharedCatalogIdAuthToggleOn()
    {
        $customerGroupId = 4;
        $sharedCatalogIdFromRepository = 3;

        $this->setupSharedCatalogRepositoryMock($customerGroupId, $sharedCatalogIdFromRepository);

        $this->deliveryHelper->method('isCommercialCustomer')
            ->willReturn(true);
        $this->authHelper->method('isLoggedIn')
            ->willReturn(true);
        $this->customerSession->method('getSharedCatalogId')
            ->willReturn(null);
        $this->customerSession->method('getCustomerGroupId')
            ->willReturn($customerGroupId);
        $this->customerSession->method('setSharedCatalogId')
            ->willReturnSelf();

        $this->assertEquals([$sharedCatalogIdFromRepository], $this->parameters->getSharedCatalogId());
    }

    private function setupSharedCatalogRepositoryMock(int $customerGroupId, int $sharedCatalogId): void
    {
        $searchCriteria = $this->createMock(\Magento\Framework\Api\SearchCriteria::class);
        $this->searchCriteriaBuilder->method('addFilter')
            ->with('customer_group_id', $customerGroupId)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')
            ->willReturn($searchCriteria);

        $sharedCatalogMock = $this->createMock(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class);
        $sharedCatalogMock->method('getId')
            ->willReturn($sharedCatalogId);
        $sharedCatalogSearchResultsMock = $this->getMockBuilder(\Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getTotalCount', 'getItems'])
            ->getMockForAbstractClass();
        $sharedCatalogSearchResultsMock->method('getTotalCount')
            ->willReturn(1);
        $sharedCatalogSearchResultsMock->method('getItems')
            ->willReturn([$sharedCatalogMock]);
        $this->sharedCatalogRepository->method('getList')
            ->with($searchCriteria)
            ->willReturn($sharedCatalogSearchResultsMock);
    }

    public function testGetEnhancedCommercialSortByToggle()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->with(Parameters::TIGER_ENHANCED_COMMERCIAL_SORT_BY_TOGGLE)
            ->willReturn(true);

        $this->assertTrue($this->parameters->getEnhancedCommercialSortByToggle());
    }

    public function testIsEssendantToggleEnabled()
    {
        $this->marketplaceCheckoutHelper->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $this->assertTrue($this->parameters->isEssendantToggleEnabled());
    }

    public function testIsE441563ToggleEnabled()
    {
        $this->checkProductAvailabilityDataModel->method('isE441563ToggleEnabled')
            ->willReturn(true);

        $this->assertTrue($this->parameters->isE441563ToggleEnabled());
    }

    public function testGetAttributeSetList()
    {
        $attributeSet1 = $this->createMock(\Magento\Eav\Api\Data\AttributeSetInterface::class);
        $attributeSet1->method('getAttributeSetName')
            ->willReturn('AttributeSet1');
        $attributeSet1->method('getAttributeSetId')
            ->willReturn(1);

        $attributeSet2 = $this->createMock(\Magento\Eav\Api\Data\AttributeSetInterface::class);
        $attributeSet2->method('getAttributeSetName')
            ->willReturn('AttributeSet2');
        $attributeSet2->method('getAttributeSetId')
            ->willReturn(2);

        $searchCriteria = $this->createMock(\Magento\Framework\Api\SearchCriteria::class);
        $this->searchCriteriaBuilder->method('create')
            ->willReturn($searchCriteria);

        $this->attributeSetRepository->method('getList')
            ->with($searchCriteria)
            ->willReturn(new \Magento\Framework\Api\SearchResults(['items' => [$attributeSet1, $attributeSet2]]));

        $expectedResult = [
            'AttributeSet1' => 1,
            'AttributeSet2' => 2
        ];

        $this->assertEquals($expectedResult, $this->parameters->getAttributeSetList());
    }

    public function resetMockConfiguration()
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->parameters = new Parameters(
            $this->imageHelper,
            $this->config,
            $this->servicesConfig,
            $this->deliveryHelper,
            $this->punchOutHelper,
            $this->storeManager,
            $this->catalogConfig,
            $this->customerSession,
            $this->searchCriteriaBuilder,
            $this->sharedCatalogRepository,
            $this->catalogMvp,
            $this->authHelper,
            $this->toggleConfig,
            $this->checkProductAvailabilityDataModel,
            $this->marketplaceCheckoutHelper,
            $this->attributeSetRepository,
            $this->saasCommonConfig
        );
    }
    /**
     * Test for getUnpublishedSearchVisibility method
     *
     * @dataProvider unpublishedSearchVisibilityDataProvider
     */
    public function testGetUnpublishedSearchVisibility($toggleEnabled, $isAdmin, $expected)
    {
        // Set up the toggle condition
        $this->catalogConfig->expects($toggleEnabled ? $this->once() : $this->atLeastOnce())
            ->method('getTigerB2315919Toggle')
            ->willReturn($toggleEnabled);

        // Set up the admin check - should only be called if toggle is enabled
        if ($toggleEnabled) {
            $this->catalogMvp->expects($this->once())
                ->method('isSelfRegCustomerAdmin')
                ->willReturn($isAdmin);
        } else {
            $this->catalogMvp->expects($this->never())
                ->method('isSelfRegCustomerAdmin');
        }

        // Test the result
        $result = $this->parameters->getUnpublishedSearchVisibility();

        // Basic equality check
        $this->assertEquals($expected, $result);

        // Type check - ensure we're getting a boolean
        $this->assertIsBool($result);

        // Logical assertions based on conditions
        if (!$toggleEnabled || !$isAdmin) {
            $this->assertFalse($result, 'Should be false if any condition fails');
        } else {
            $this->assertTrue($result, 'Should be true only when all conditions are met');
        }
    }

    /**
     * Data provider for testGetUnpublishedSearchVisibility
     */
    public function unpublishedSearchVisibilityDataProvider()
    {
        return [
            'Toggle disabled' => [
                'toggleEnabled' => false,
                'isAdmin' => true,
                'expected' => false
            ],
            'Toggle enabled, not admin' => [
                'toggleEnabled' => true,
                'isAdmin' => false,
                'expected' => false
            ],
            'All conditions met' => [
                'toggleEnabled' => true,
                'isAdmin' => true,
                'expected' => true
            ]
        ];
    }
    /**
     * Test getSharedCatalogId when parentGroupId exists
     */
    public function testGetSharedCatalogIdWithParentGroupId(): void
    {
        // Set up test data
        $groupId = 5;
        $parentGroupId = 10;
        $sharedCatalogIdFromParent = [15, 16];
        $expectedGroupIds = [10, 5, 15, 16]; // parentGroupId, groupId, and shared catalog IDs

        // Configure mocks with verification
        $this->authHelper->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->deliveryHelper->expects($this->once())
            ->method('isCommercialCustomer')
            ->willReturn(true);

        $this->customerSession->expects($this->once())
            ->method('getSharedCatalogId')
            ->willReturn(null); // No cached value

        $this->customerSession->expects($this->once())
            ->method('getCustomerGroupId')
            ->willReturn($groupId);

        // This is the key condition - parent group ID exists
        $this->catalogMvp->expects($this->once())
            ->method('getParentGroupId')
            ->with($groupId)
            ->willReturn($parentGroupId);

        // Should not check admin status when parent exists
        $this->catalogMvp->expects($this->once())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(false); // The return value doesn't matter in the parent ID path

        // Never call getChildGroupIds when parent exists
        $this->catalogMvp->expects($this->never())
            ->method('getChildGroupIds');

        // Mock getSharedCatalogIdByGroupId for the parent
        $parametersMock = $this->getMockBuilder(Parameters::class)
            ->setConstructorArgs([
                $this->imageHelper,
                $this->config,
                $this->servicesConfig,
                $this->deliveryHelper,
                $this->punchOutHelper,
                $this->storeManager,
                $this->catalogConfig,
                $this->customerSession,
                $this->searchCriteriaBuilder,
                $this->sharedCatalogRepository,
                $this->catalogMvp,
                $this->authHelper,
                $this->toggleConfig,
                $this->checkProductAvailabilityDataModel,
                $this->marketplaceCheckoutHelper,
                $this->attributeSetRepository,
                $this->saasCommonConfig
            ])
            ->onlyMethods(['getSharedCatalogIdByGroupId'])
            ->getMock();

        $parametersMock->expects($this->once())
            ->method('getSharedCatalogIdByGroupId')
            ->with($parentGroupId)
            ->willReturn($sharedCatalogIdFromParent);

        // Expect the session to cache the result
        $this->customerSession->expects($this->once())
            ->method('setSharedCatalogId')
            ->with($expectedGroupIds);

        // Execute the method
        $result = $parametersMock->getSharedCatalogId();

        // Verify the result
        $this->assertEquals($expectedGroupIds, $result);

        // Additional assertions
        $this->assertIsArray($result, 'Result should be an array');
        $this->assertCount(4, $result, 'Result should contain 4 IDs');
        $this->assertContains($parentGroupId, $result, 'Parent group ID should be in result');
        $this->assertContains($groupId, $result, 'Customer group ID should be in result');

        // Verify first elements are the group IDs
        $this->assertEquals($parentGroupId, $result[0], 'First element should be parent group ID');
        $this->assertEquals($groupId, $result[1], 'Second element should be customer group ID');

        // Verify shared catalog IDs are included
        foreach ($sharedCatalogIdFromParent as $catalogId) {
            $this->assertContains($catalogId, $result, 'Shared catalog ID should be in result');
        }
    }

    /**
     * Test getSharedCatalogId when user is admin
     */
    public function testGetSharedCatalogIdWithAdminUser(): void
    {
        // Set up test data
        $groupId = 5;
        $childGroupIds = [6, 7, 8]; // Child groups of the admin
        $sharedCatalogIds = [20, 21]; // Shared catalog IDs for the admin group
        $expectedGroupIds = [6, 7, 8, 20, 21]; // Child group IDs and shared catalog IDs

        // Configure mocks with verification
        $this->authHelper->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->deliveryHelper->expects($this->once())
            ->method('isCommercialCustomer')
            ->willReturn(true);

        $this->customerSession->expects($this->once())
            ->method('getSharedCatalogId')
            ->willReturn(null); // No cached value

        $this->customerSession->expects($this->once())
            ->method('getCustomerGroupId')
            ->willReturn($groupId);

        // This is the first key condition - no parent group
        $this->catalogMvp->expects($this->once())
            ->method('getParentGroupId')
            ->with($groupId)
            ->willReturn(null);

        // This is the second key condition - user is admin
        $this->catalogMvp->expects($this->once())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(true);

        // Admin gets access to child group IDs
        $this->catalogMvp->expects($this->once())
            ->method('getChildGroupIds')
            ->with($groupId)
            ->willReturn($childGroupIds);

        // Mock getSharedCatalogIdByGroupId for the admin group
        $parametersMock = $this->getMockBuilder(Parameters::class)
            ->setConstructorArgs([
                $this->imageHelper,
                $this->config,
                $this->servicesConfig,
                $this->deliveryHelper,
                $this->punchOutHelper,
                $this->storeManager,
                $this->catalogConfig,
                $this->customerSession,
                $this->searchCriteriaBuilder,
                $this->sharedCatalogRepository,
                $this->catalogMvp,
                $this->authHelper,
                $this->toggleConfig,
                $this->checkProductAvailabilityDataModel,
                $this->marketplaceCheckoutHelper,
                $this->attributeSetRepository,
                $this->saasCommonConfig
            ])
            ->onlyMethods(['getSharedCatalogIdByGroupId'])
            ->getMock();

        $parametersMock->expects($this->once())
            ->method('getSharedCatalogIdByGroupId')
            ->with($groupId)
            ->willReturn($sharedCatalogIds);

        // Expect the session to cache the result
        $this->customerSession->expects($this->once())
            ->method('setSharedCatalogId')
            ->with($expectedGroupIds);

        // Execute the method
        $result = $parametersMock->getSharedCatalogId();

        // Basic result verification
        $this->assertEquals($expectedGroupIds, $result);

        // Additional assertions
        $this->assertIsArray($result, 'Result should be an array');
        $this->assertCount(count($childGroupIds) + count($sharedCatalogIds), $result, 'Result should have combined length');

        // Admin's own group ID should NOT be in the result (only child groups)
        $this->assertNotContains($groupId, $result, 'Admin group ID should not be in result');

        // Verify all child group IDs are present
        foreach ($childGroupIds as $childId) {
            $this->assertContains($childId, $result, 'Child group ID should be in result');
        }

        // Verify all shared catalog IDs are present
        foreach ($sharedCatalogIds as $catalogId) {
            $this->assertContains($catalogId, $result, 'Shared catalog ID should be in result');
        }

        // Verify correct array structure (child groups first, then shared catalogs)
        foreach ($childGroupIds as $index => $childId) {
            $this->assertEquals($childId, $result[$index], "Child group ID should be at position $index");
        }
    }
    /**
     * Test getSharedCatalogId for regular user (not admin, no parent group)
     */
    public function testGetSharedCatalogIdForRegularUser(): void
    {
        // Test data
    $customerGroupId = 5;

    // Create mock search criteria
    $searchCriteriaMock = $this->createMock(\Magento\Framework\Api\SearchCriteria::class);

    // Configure the search criteria builder
    $this->searchCriteriaBuilder->expects($this->once())
        ->method('addFilter')
        ->with('customer_group_id', $customerGroupId)
        ->willReturnSelf();

    $this->searchCriteriaBuilder->expects($this->once())
        ->method('create')
        ->willReturn($searchCriteriaMock);

    // Create a mock search results object with zero results
    $searchResultsMock = $this->createMock(\Magento\SharedCatalog\Api\Data\SearchResultsInterface::class);
    $searchResultsMock->expects($this->once())
        ->method('getTotalCount')
        ->willReturn(0); // Key part: no results found

    $searchResultsMock->expects($this->never())
        ->method('getItems'); // getItems should never be called

    // Configure the repository to return our empty results
    $this->sharedCatalogRepository->expects($this->once())
        ->method('getList')
        ->with($searchCriteriaMock)
        ->willReturn($searchResultsMock);

    // Customer session should not be updated
    $this->customerSession->expects($this->never())
        ->method('setSharedCatalogId');

    // Call the method
    $result = $this->parameters->getSharedCatalogIdByGroupId($customerGroupId);

    // Verify the result is an empty array
    $this->assertIsArray($result);
    $this->assertEmpty($result, 'Result should be an empty array when no shared catalogs exist');
    }

    public function testIsTigerD200529EnabledReturnsTrue()
    {
        $this->saasCommonConfig->method('isTigerD200529Enabled')->willReturn(true);

        $this->assertTrue($this->parameters->isTigerD200529Enabled());
    }

    public function testIsTigerD200529EnabledReturnsFalse()
    {
        $this->saasCommonConfig->method('isTigerD200529Enabled')->willReturn(false);

        $this->assertFalse($this->parameters->isTigerD200529Enabled());
    }

    public function testGetCustomerGroupId_ReturnsGroupId()
    {
        $this->customerSession->method('getCustomerGroupId')->willReturn(3);

        $this->assertSame(3, $this->parameters->getCustomerGroupId());
    }

    public function testGetCustomerGroupId_ReturnsNotLoggedInIdOnException()
    {
        $this->customerSession->method('getCustomerGroupId')
            ->will($this->throwException(new \Magento\Framework\Exception\LocalizedException(__('error'))));

        $this->assertSame(1, $this->parameters->getCustomerGroupId());
    }

    public function testGetLiveSearchParameters()
    {
        $environmentId = 'env1';
        $websiteCode = 'web1';
        $storeCode = 'store1';
        $storeViewCode = 'view1';
        $customerGroupCode = 'group1';

        // Mock all methods called by getLiveSearchParameters
        $this->parameters = $this->getMockBuilder(Parameters::class)
            ->setConstructorArgs([
                $this->imageHelper,
                $this->config,
                $this->servicesConfig,
                $this->deliveryHelper,
                $this->punchOutHelper,
                $this->storeManager,
                $this->catalogConfig,
                $this->customerSession,
                $this->searchCriteriaBuilder,
                $this->sharedCatalogRepository,
                $this->catalogMvp,
                $this->authHelper,
                $this->toggleConfig,
                $this->checkProductAvailabilityDataModel,
                $this->marketplaceCheckoutHelper,
                $this->attributeSetRepository,
                $this->saasCommonConfig
            ])
            ->onlyMethods([
                'getDefaultPlaceholderUrl',
                'getServiceUrl',
                'getGraphqlServiceUrl',
                'getXApiKey',
                'getCustomerGroupId',
                'isDisplayUnitCost3p1pProductsToggleEnabled',
                'isEllipsisControlEnabled',
                'getEllipsisControlTotalCharacters',
                'getEllipsisControlStartCharacters',
                'getEllipsisControlEndCharacters',
                'getSharedCatalogId',
                'isE441563ToggleEnabled',
                'isEssendantToggleEnabled',
                'getEnhancedCommercialSortByToggle',
                'getUnpublishedSearchVisibility',
                'getAttributeSetList',
                'getIsVariantQueryEnabled',
                'isTigerD200529Enabled',
                'isTigerD236292Enabled',
                'isCommercialCustomer',
                'getSiteName',
                'getTazToken',
                'isAllowOwnDocument',
                'isTigerTeamD217182Enabled',
                'isTigerTeamD240007Enabled',
            ])
            ->getMock();

        // Set return values for all dependencies
        $this->parameters->method('getDefaultPlaceholderUrl')->with('thumbnail')->willReturn('placeholder_url');
        $this->parameters->method('getServiceUrl')->willReturn('service_url');
        $this->parameters->method('getGraphqlServiceUrl')->willReturn('graphql_url');
        $this->parameters->method('getXApiKey')->willReturn('x_api_key');
        $this->parameters->method('getCustomerGroupId')->willReturn(123);
        $this->parameters->method('isDisplayUnitCost3p1pProductsToggleEnabled')->willReturn(true);
        $this->parameters->method('isEllipsisControlEnabled')->willReturn(true);
        $this->parameters->method('getEllipsisControlTotalCharacters')->willReturn(10);
        $this->parameters->method('getEllipsisControlStartCharacters')->willReturn(2);
        $this->parameters->method('getEllipsisControlEndCharacters')->willReturn(3);
        $this->parameters->method('getSharedCatalogId')->willReturn([99]);
        $this->parameters->method('isE441563ToggleEnabled')->willReturn(true);
        $this->parameters->method('isEssendantToggleEnabled')->willReturn(true);
        $this->parameters->method('getEnhancedCommercialSortByToggle')->willReturn(true);
        $this->parameters->method('getUnpublishedSearchVisibility')->willReturn(false);
        $this->parameters->method('getAttributeSetList')->willReturn(['set1' => 1]);
        $this->parameters->method('getIsVariantQueryEnabled')->willReturn(true);
        $this->parameters->method('isTigerD200529Enabled')->willReturn(true);
        $this->parameters->method('isCommercialCustomer')->willReturn(true);
        $this->parameters->method('getSiteName')->willReturn('site_name');
        $this->parameters->method('getTazToken')->willReturn('taz_token');
        $this->parameters->method('isAllowOwnDocument')->willReturn(true);
        $this->parameters->method('isTigerD236292Enabled')->willReturn(true);
        $this->parameters->method('isTigerTeamD217182Enabled')->willReturn(true);
        $this->parameters->method('isTigerTeamD240007Enabled')->willReturn(true);

        $result = $this->parameters->getLiveSearchParameters(
            $environmentId,
            $websiteCode,
            $storeCode,
            $storeViewCode,
            $customerGroupCode
        );

        $expected = [
            'environmentId' => $environmentId,
            'websiteCode' => $websiteCode,
            'storeCode' => $storeCode,
            'storeViewCode' => $storeViewCode,
            'customerGroup' => $customerGroupCode,
            'placeholderImage' => 'placeholder_url',
            'serviceUrl' => 'service_url',
            'graphqlServiceUrl' => 'graphql_url',
            'XApiKey' => 'x_api_key',
            'customerGroupId' => 123,
            'displayUnitCost3p1pProductsToggle' => true,
            'ellipsisEnabled' => true,
            'ellipsisTotalCharacters' => 10,
            'ellipsisStartCharacters' => 2,
            'ellipsisEndCharacters' => 3,
            'sharedCatalogId' => [99],
            'isE441563ToggleEnabled' => true,
            'isEssendantToggleEnabled' => true,
            'isEnhancedCommercialSortByEnabled' => true,
            'hideUnpublishedInSearch' => true,
            'attributeSets' => ['set1' => 1],
            'enableVariantQuery' => true,
            'isTigerD200529Enabled' => true,
            'isTigerD236292Enabled' => true,
            'allowOwnDocument' => true,
            'tiger_team_d_217182' => true,
            'tigerTeamD240007Enabled' => true,
            'siteName' => 'site_name',
            'TazToken' => 'taz_token',
        ];

        $this->assertEquals($expected, $result);
    }
    
    /**
     * Tests that the isAllowOwnDocument() method returns true when the toggle config and company's allowOwnDocument flag are enabled.
     */
    public function testIsAllowOwnDocument()
    {
        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->with(Parameters::TIGER_TEAM_D_217182_FIX)
            ->willReturn(true);

        $companyMock = $this->getMockForAbstractClass(
            \Magento\Company\Api\Data\CompanyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getAllowOwnDocument']
        );

        $companyMock->method('getAllowOwnDocument')->willReturn(true);

        $customerMock = $this->createMock(\Magento\Customer\Api\Data\CustomerInterface::class);
        $this->deliveryHelper
            ->method('getCustomer')
            ->willReturn($customerMock);

        $this->deliveryHelper
            ->method('getAssignedCompany')
            ->with($customerMock)
            ->willReturn($companyMock);

        $parameters = new Parameters(
            $this->imageHelper,
            $this->config,
            $this->servicesConfig,
            $this->deliveryHelper,
            $this->punchOutHelper,
            $this->storeManager,
            $this->catalogConfig,
            $this->customerSession,
            $this->searchCriteriaBuilder,
            $this->sharedCatalogRepository,
            $this->catalogMvp,
            $this->authHelper,
            $this->toggleConfig,
            $this->checkProductAvailabilityDataModel,
            $this->marketplaceCheckoutHelper,
            $this->attributeSetRepository,
            $this->saasCommonConfig
        );

        $this->assertTrue($parameters->isAllowOwnDocument());
    }

    /**
     * Tests that isAllowOwnDocument() returns false when the toggle config is disabled.
     */
    public function testIsAllowOwnDocumentReturnsFalseWhenToggleDisabled()
    {
        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->with(Parameters::TIGER_TEAM_D_217182_FIX)
            ->willReturn(false);

        $parameters = new Parameters(
            $this->imageHelper,
            $this->config,
            $this->servicesConfig,
            $this->deliveryHelper,
            $this->punchOutHelper,
            $this->storeManager,
            $this->catalogConfig,
            $this->customerSession,
            $this->searchCriteriaBuilder,
            $this->sharedCatalogRepository,
            $this->catalogMvp,
            $this->authHelper,
            $this->toggleConfig,
            $this->checkProductAvailabilityDataModel,
            $this->marketplaceCheckoutHelper,
            $this->attributeSetRepository,
            $this->saasCommonConfig
        );

        $this->assertFalse($parameters->isAllowOwnDocument());
    }

}
