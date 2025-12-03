<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Punchout\Test\Unit\ViewModel;

use Fedex\CatalogMvp\ViewModel\MvpHelper;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\Delivery\Helper\Data;
use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Magento\Directory\Model\Currency;
use Magento\Directory\Model\PriceCurrency;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store\Config;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Model\Product;
use Magento\Company\Api\Data\CompanyInterface;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\FXOCMConfigurator\Helper\Data as FXOCMHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
/**
 * Class MvpHelperTest
 * Handle the test case
 */
class MvpHelperTest extends TestCase
{
    private CustomerSession|MockObject $customerSessionMock;
    
    
    protected $catalogDocumentRefranceApiMock;
    /**
     * @var (\Magento\Framework\Registry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $registryMock;
    protected $categoryMock;
    protected $companyHelperMock;
    protected $product;
    protected $productRepository;
    protected $companyInterface;
    protected $currencyMock;
    protected $toggleConfigMock;
    private $requestMock;
    protected $scopeConfigMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $mvpHelper;
    /**
     * @var CatalogMvp|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $catalogMvpMock;

    /**
     * @var FXOCMHelper
     */
    protected $fxoCMHelper;

    /**
     * @var Data|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $deliveryHelperMock;

    protected $registry;
    private PerformanceImprovementPhaseTwoConfig|MockObject $performanceImprovementPhase2;
    protected $priceCurrencyMock;
    protected $performanceImprovementPhaseTwoConfigMock;
    

    protected function setUp(): void
    {
        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn', 'getData']) // existing methods
            ->addMethods(['setData','create', 'getProductListLimit']) // non-existent methods
            ->getMock();



        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParam', 'getFullActionName'])
            ->getMock();


        $this->catalogMvpMock = $this->getMockBuilder(CatalogMvp::class)
            ->setMethods(['getCurrentCategory','isDownloadCatalogItemsEnable','isSharedCatalogPermissionEnabled','isSelfRegCustomerAdmin', 'checkPrintCategory', 'isMvpSharedCatalogEnable','getChildCategoryCount','getFxoMenuId','getCurrencySymbol','isEnableStopRedirectMvpAddToCart','getOrCreateCustomerSession'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogDocumentRefranceApiMock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->setMethods(['getExpiryDocuments'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryHelperMock = $this->getMockBuilder(Data::class)
            ->setMethods(['isCommercialCustomer', 'getToggleConfigurationValue', 'getConfigurationValue', 'getAssignedCompany'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->setMethods(['registry'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','getChildrenCount'])
            ->getMock();

        $this->companyHelperMock = $this->getMockBuilder(CompanyHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFedexAccountNumber'])
            ->getMock();

        $this->product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->fxoCMHelper = $this->getMockBuilder(FXOCMHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isNonStandardCatalogToggleEnabled','getCharLimitToggle'])
            ->getMockForAbstractClass();

        $this->priceCurrencyMock = $this->getMockBuilder(PriceCurrency::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->performanceImprovementPhaseTwoConfigMock = $this->getMockBuilder(PerformanceImprovementPhaseTwoConfig::class)
        ->disableOriginalConstructor()
        ->getMock();    
            

        $this->companyInterface = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                'getBoxEnabled',
                'getDropboxEnabled',
                'getGoogleEnabled',
                'getMicrosoftEnabled',
                'getId'
                ]
            )
            ->getMockForAbstractClass();

        $this->currencyMock = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();  
        
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();    

        $this->objectManager = new ObjectManager($this);

        $this->mvpHelper = $this->objectManager->getObject(
            MvpHelper::class,
            [
                'catalogMvp' => $this->catalogMvpMock,
                'deliveryHelper' => $this->deliveryHelperMock,
                'customerSession' => $this->customerSessionMock,
                'productRepository' => $this->productRepository,
                'companyHelper' => $this->companyHelperMock,
                'fxoCMHelper' => $this->fxoCMHelper,
                'priceCurrency' => $this->priceCurrencyMock, // add here
                'catalogDocumentRefranceApi' => $this->catalogDocumentRefranceApiMock,
                'performanceImprovementPhaseTwoConfig' => $this->performanceImprovementPhaseTwoConfigMock, // add here
                'request' => $this->requestMock,
                'scopeConfig' => $this->scopeConfigMock
            ]
        );
    }

    /**
     * @return boolean
     */
    public function testIsCommercialCustomer()
    {
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $result = $this->mvpHelper->isCommercialCustomer();
        $this->assertIsBool($result);
    }

    /**
     * @return boolean
     */
    public function testIsSelfRegCustomerAdmin()
    {
        $this->catalogMvpMock->expects($this->any())->method('isSelfRegCustomerAdmin')->willReturn(true);
        $result = $this->mvpHelper->isSelfRegCustomerAdmin();

        $this->assertIsBool($result);
    }

    /**
     * @return boolean
     */
    public function testIsSharedCatalogPermissionEnabled()
    {
        $this->catalogMvpMock->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $result = $this->mvpHelper->isSharedCatalogPermissionEnabled();

        $this->assertIsBool($result);
    }

    /**
     * @return boolean
     */
    public function testCheckPrintCategory()
    {
        $this->catalogMvpMock->expects($this->any())->method('checkPrintCategory')->willReturn(true);
        $result = $this->mvpHelper->checkPrintCategory();

        $this->assertIsBool($result);
    }

    /**
     * @return boolean
     */
    public function testisMvpSharedCatalogEnable()
    {
        $this->catalogMvpMock->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $result = $this->mvpHelper->isMvpSharedCatalogEnable();
        $this->assertIsBool($result);
    }

    /**
     * @return int
     */
    public function testCurrentCategory()
    {
        $this->catalogMvpMock->expects($this->any())->method('getCurrentCategory')->willReturn($this->categoryMock);
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->assertNull($this->mvpHelper->currentCategory());
    }

    /**
     * Test Case for getProductData
     */
    public function testGetProductData()
    {
        $sku = "tes-sku";
        $this->productRepository->expects($this->any())->method('get')->willReturn($this->product);
        $this->assertEquals($this->product, $this->mvpHelper->getProductData($sku));
    }

    /**
     * Test Cse for getProductData with Exception
     */
    public function testGetProductDataWithException()
    {
        $sku = "tes-sku";
        $this->productRepository->expects($this->any())->method('get')
            ->willThrowException(new NoSuchEntityException());
        $this->assertEquals(false, $this->mvpHelper->getProductData($sku));
    }

    /**
     * Test Cse for getProductData with Empty Sku
     */
    public function testGetProductDataWithoutSku()
    {
        $sku = "";
        $this->assertEquals(false, $this->mvpHelper->getProductData($sku));
    }

    /**
     * Test Cse for testgetChildCategoryCount
     */
    public function testgetChildCategoryCount()
    {

        $this->catalogMvpMock->expects($this->any())->method('getChildCategoryCount')->willReturn(true);
        $this->assertNotNull($this->mvpHelper->getChildCategoryCount());
    }

    /**
     * Test Cse for getFxoMenuId
     */
    public function testGetFxoMenuId()
    {

        $this->catalogMvpMock->expects($this->any())->method('getFxoMenuId')->willReturn('1582146604697-4');
        $this->assertEquals('1582146604697-4', $this->mvpHelper->getFxoMenuId(234));
    }

    /**
     * Test isNonStandardCatalogToggleEnabled
     *
     * @return bool
     */
    public function testIsNonStandardCatalogToggleEnabled()
    {
        $this->fxoCMHelper->expects($this->any())->method('isNonStandardCatalogToggleEnabled')->willReturn(true);
        $this->assertNotNull($this->mvpHelper->isNonStandardCatalogToggleEnabled());
    }

    /**
     * Test isCatalogMvpCloudDriveToggle
     *
     * @return void
     */
    public function testIsCloudDriveBoxEnabled()
    {
        $this->deliveryHelperMock->expects($this->any())
            ->method('getAssignedCompany')
            ->willReturn($this->companyInterface);
        $this->deliveryHelperMock->expects($this->any())
            ->method('getConfigurationValue')
            ->willReturn(true);
        $this->companyInterface->expects($this->any())
            ->method('getBoxEnabled')
            ->willReturn(true);

        $this->assertNotNull($this->mvpHelper->isCloudDriveBoxEnabled());
    }

    /**
     * Test isCatalogMvpCloudDriveToggle
     *
     * @return void
     */
    public function testIsCloudDriveBoxEnabledWithFalse()
    {
        $this->assertNotNull($this->mvpHelper->isCloudDriveBoxEnabled());
    }

    /**
     * Test isCloudDriveDropboxEnabled
     *
     * @return void
     */
    public function testIsCloudDriveDropboxEnabled()
    {
        $this->deliveryHelperMock->expects($this->any())
            ->method('getAssignedCompany')
            ->willReturn($this->companyInterface);
        $this->deliveryHelperMock->expects($this->any())
            ->method('getConfigurationValue')
            ->willReturn(true);
        $this->companyInterface->expects($this->any())
            ->method('getDropboxEnabled')
            ->willReturn(true);

        $this->assertEquals(true, $this->mvpHelper->isCloudDriveDropboxEnabled());
    }

    /**
     * Test isCloudDriveDropboxEnabled
     *
     * @return void
     */
    public function testIsCloudDriveDropboxEnabledWithFalse()
    {

        $this->assertEquals(false, $this->mvpHelper->isCloudDriveDropboxEnabled());
    }

    /**
     * Test isCatalogExpiryNotificationToggle
     *
     * @return void
     */
    public function testIsCatalogExpiryNotificationToggle()
    {
        $this->deliveryHelperMock->expects($this->any())
            ->method('getToggleConfigurationValue')
            ->willReturn(true);

        $this->assertEquals(true, $this->mvpHelper->isCatalogExpiryNotificationToggle());
    }

    /**
     * Test isCatalogExpiryNotificationToggle
     *
     * @return void
     */
    public function testIsCatalogExpiryNotificationToggleWithFalse()
    {
        $this->deliveryHelperMock->expects($this->any())
            ->method('getToggleConfigurationValue')
            ->willReturn(false);

        $this->assertEquals(false, $this->mvpHelper->isCatalogExpiryNotificationToggle());
    }

    /**
     * Test getFixedQtyHandlerToggle
     *
     * @return void
     */
    public function testGetFixedQtyHandlerToggle()
    {
        $this->deliveryHelperMock->expects($this->any())
            ->method('getToggleConfigurationValue')
            ->willReturn(true);

        $this->assertEquals(true, $this->mvpHelper->getFixedQtyHandlerToggle());
    }

    /**
     * Test getFixedQtyHandlerToggle
     *
     * @return void
     */
    public function testGetFixedQtyHandlerToggleWithFalse()
    {
        $this->deliveryHelperMock->expects($this->any())
            ->method('getToggleConfigurationValue')
            ->willReturn(false);

        $this->assertEquals(false, $this->mvpHelper->getFixedQtyHandlerToggle());
    }

    /**
     * Test isCloudDriveGoogleEnabled
     *
     * @return void
     */
    public function testIsCloudDriveGoogleEnabled()
    {
        $this->deliveryHelperMock->expects($this->any())
            ->method('getAssignedCompany')
            ->willReturn($this->companyInterface);
        $this->deliveryHelperMock->expects($this->any())
            ->method('getConfigurationValue')
            ->willReturn(true);
        $this->companyInterface->expects($this->any())
            ->method('getGoogleEnabled')
            ->willReturn(true);

        $this->assertEquals(true, $this->mvpHelper->isCloudDriveGoogleEnabled());
    }

    /**
     * Test isCloudDriveGoogleEnabled
     *
     * @return void
     */
    public function testIsCloudDriveGoogleEnabledWithFalse()
    {

        $this->assertEquals(false, $this->mvpHelper->isCloudDriveGoogleEnabled());
    }

    /**
     * Test isCloudDriveMicrosoftEnabled
     *
     * @return void
     */
    public function testIsCloudDriveMicrosoftEnabled()
    {
        $this->deliveryHelperMock->expects($this->any())
            ->method('getAssignedCompany')
            ->willReturn($this->companyInterface);
        $this->deliveryHelperMock->expects($this->any())
            ->method('getConfigurationValue')
            ->willReturn(true);
        $this->companyInterface->expects($this->any())
            ->method('getMicrosoftEnabled')
            ->willReturn(true);

        $this->assertEquals(true, $this->mvpHelper->isCloudDriveMicrosoftEnabled());
    }

    /**
     * Test isCloudDriveMicrosoftEnabled
     *
     * @return void
     */
    public function testIsCloudDriveMicrosoftEnabledWithFalse()
    {

        $this->assertEquals(false, $this->mvpHelper->isCloudDriveMicrosoftEnabled());
    }

    /**
     * Test getCompanyFedExAccountNumber
     *
     * @return void
     */
    public function testGetCompanyFedExAccountNumber()
    {
        $this->deliveryHelperMock->expects($this->any())
            ->method('getAssignedCompany')
            ->willReturn($this->companyInterface);

        $this->companyInterface->expects($this->any())
            ->method('getId')
            ->willReturn(8);

        $this->companyHelperMock->expects($this->any())
            ->method('getFedexAccountNumber')
            ->willReturn('123456789');

        $this->assertEquals('123456789', $this->mvpHelper->getCompanyFedExAccountNumber());
    }

    /**
     * Test getSkuNumber
     *
     * @return int
     */
    public function testGetSkuNumber()
    {
        $this->assertEquals('1695219572266', $this->mvpHelper->getSkuNumber());
    }

    /**
     * B-1978493
     * Test testGetCurrencySymbol
     *
     * @return boolean
     */
    public function testGetCurrencySymbol()
    {
        $storeId = 1;
        $currencySymbol = '$';

        $priceCurrencyMock = $this->getMockBuilder(PriceCurrency::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->currencyMock->expects($this->once())
            ->method('getCurrencySymbol')
            ->willReturn($currencySymbol);

        $priceCurrencyMock->expects($this->once())
            ->method('getCurrency')
            ->with($storeId)
            ->willReturn($this->currencyMock);

        $this->performanceImprovementPhase2 = $this->getMockBuilder(PerformanceImprovementPhaseTwoConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();    

        $mvpHelperCurrency = new MvpHelper(
            $this->catalogMvpMock,
            $this->deliveryHelperMock,
            $this->customerSessionMock,
            $this->productRepository,
            $this->companyHelperMock,
            $this->fxoCMHelper,
            $priceCurrencyMock,
            $this->catalogDocumentRefranceApiMock,
            $this->performanceImprovementPhase2,
            $this->requestMock,
            $this->scopeConfigMock

        );

        $this->assertEquals($currencySymbol, $mvpHelperCurrency->getCurrencySymbol($storeId));
    }

    /**
     * Test getCharLimitToggle
     *
     * @return Int
     */
    public function testGetCharLimitToggle()
    {
        $this->fxoCMHelper->expects($this->any())->method('getCharLimitToggle')->willReturn(1);
        $this->assertNotNull($this->mvpHelper->getCharLimitToggle());
    }

    /**
     * Test getIsExpiryDocument
     *
     * @return boolean
     */
    public function testGetIsExpiryDocument()
    {
        $productId = 1;
        $result[0] =  ['entity_id' => 1];
        $this->catalogDocumentRefranceApiMock->expects($this->any())->method('getExpiryDocuments')->willReturn($result);
        $this->assertNotNull($this->mvpHelper->getIsExpiryDocument($productId));
    }

    /**
     * Test getCatalogBreakpointToggle
     *
     * @return boolean
     */
    public function testGetCatalogBreakpointToggle()
    {
        $this->deliveryHelperMock->expects($this->any())
        ->method('getToggleConfigurationValue')
        ->willReturn(true);
        $this->assertEquals(true, $this->mvpHelper->getCatalogBreakpointToggle());
    }

    /**
     * Test isEnableStopRedirectMvpAddToCart
     *
     * @return boolean
     */
    public function testisEnableStopRedirectMvpAddToCart()
    {
        $this->catalogMvpMock->expects($this->any())->method('isEnableStopRedirectMvpAddToCart')->willReturn(true);
        $result = $this->mvpHelper->isEnableStopRedirectMvpAddToCart();
        $this->assertIsBool($result);
    }

    /**
     * Test eproUnableToAddInBranchProductToCartToggle
     *
     * @return boolean
     */
    public function testEproUnableToAddInBranchProductToCartToggle()
    {
        $this->deliveryHelperMock->expects($this->any())
        ->method('getToggleConfigurationValue')
        ->willReturn(true);
        $this->assertEquals(true, $this->mvpHelper->eproUnableToAddInBranchProductToCartToggle());
    }

    /**
     * Test getOrCreateCustomerSession when customer is already logged in.
     *
     * @return void
     */
    public function testGetOrCreateCustomerSessionReturnsExistingSessionIfLoggedIn()
    {
        $this->customerSessionMock->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerSessionMock->expects($this->never())
            ->method('create');

        $result = $this->mvpHelper->getOrCreateCustomerSession();

        $this->assertSame($this->customerSessionMock, $result);
    }

 


    /**
     * Test getOrCreateCustomerSession returns current session if customer is logged in.
     *
     */

    public function testGetOrCreateCustomerSessionReturnsCurrentSessionIfLoggedIn()
    {
        $this->customerSessionMock->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $result = $this->mvpHelper->getOrCreateCustomerSession();

        $this->assertSame($this->customerSessionMock, $result);
    }


    /**
     * shouldApplyCustomPagination returns true when toggle is enabled and action context matches
     */
    public function testShouldApplyCustomPaginationReturnsTrue()
    {
        $this->deliveryHelperMock->method('getToggleConfigurationValue')->willReturn(true);

        $this->requestMock->method('getFullActionName')
            ->willReturn('selfreg_ajax_productlistajax');

        $this->assertTrue($this->mvpHelper->shouldApplyCustomPagination());
    }

    public function testGetSessionPageSizeKeyReturnsList()
{
    $this->requestMock->expects($this->once())
        ->method('getParam')
        ->with('product_list_mode', 'list')
        ->willReturn('list');

    $this->assertEquals('ProductListLimitList', $this->mvpHelper->getSessionPageSizeKey());
}
public function testGetSessionPageSizeKeyReturnsGrid()
{
    $this->requestMock->expects($this->once())
        ->method('getParam')
        ->with('product_list_mode', 'list')
        ->willReturn('grid');

    $this->assertEquals('ProductListLimitGrid', $this->mvpHelper->getSessionPageSizeKey());
}
public function testGetRequestReturnsInjectedRequestMock()
{
    $this->assertSame($this->requestMock, $this->mvpHelper->getRequest());
}

public function testIsMyCurrentLimitReturnsTrueWhenLimitMatchesInListMode()
{
    $this->requestMock->method('getParam')
        ->with('product_list_mode', 'list')
        ->willReturn('list');

    $this->customerSessionMock->method('getData')
        ->with('ProductListLimitList')
        ->willReturn(20);

    $this->assertTrue($this->mvpHelper->isMyCurrentLimit(20));
}
public function testGetSessionPageSizeReturnsInt()
{
    $expectedValue = 20;

    // Assuming $this->mvpHelper is created via ObjectManager with mocks already injected
    // Just mock customerSession->getData() to return $expectedValue regardless of key
    $this->customerSessionMock->expects($this->once())
        ->method('getData')
        ->with($this->isType('string'))  // Accept any string key
        ->willReturn($expectedValue);

    $result = $this->mvpHelper->getSessionPageSize();

    $this->assertSame($expectedValue, $result);
}


public function testSetSessionPageSizeSetsDataWithCorrectKey()
{
    $pageSize = 25;
    $expectedKey = 'ProductListLimitGrid';

    $this->mvpHelper = $this->getMockBuilder(MvpHelper::class)
        ->onlyMethods(['getSessionPageSizeKey'])
        ->setConstructorArgs([
            $this->catalogMvpMock,
            $this->deliveryHelperMock,
            $this->customerSessionMock,
            $this->productRepository,
            $this->companyHelperMock,
            $this->fxoCMHelper,
            $this->priceCurrencyMock,
            $this->catalogDocumentRefranceApiMock,
            $this->performanceImprovementPhaseTwoConfigMock,
            $this->requestMock,
            $this->scopeConfigMock
        ])
        ->getMock();

    $this->mvpHelper->method('getSessionPageSizeKey')->willReturn($expectedKey);

    $this->customerSessionMock->expects($this->once())
        ->method('setData')
        ->with($expectedKey, $pageSize);

    $this->mvpHelper->setSessionPageSize($pageSize);
}

public function testGetDefaultProductLimitReturnsCorrectValue()
{
    $expectedListLimit = 10;
    $expectedGridLimit = 12;

    $this->scopeConfigMock->expects($this->exactly(2))
        ->method('getValue')
        ->withConsecutive(
            ['catalog/frontend/list_per_page', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null],
            ['catalog/frontend/grid_per_page', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null]
        )
        ->willReturnOnConsecutiveCalls($expectedListLimit, $expectedGridLimit);

    $listLimit = $this->mvpHelper->getDefaultProductLimit('list');
    $gridLimit = $this->mvpHelper->getDefaultProductLimit('grid');

    $this->assertEquals($expectedListLimit, $listLimit);
    $this->assertEquals($expectedGridLimit, $gridLimit);
}

public function testIsMyCurrentLimitWithSessionValue()
{
    $limit = 20;
    $mode = 'list';
    $sessionKey = 'ProductListLimitList';

    $this->requestMock->expects($this->once())
        ->method('getParam')
        ->with('product_list_mode', 'list')
        ->willReturn($mode);

    $this->customerSessionMock->expects($this->once())
        ->method('getData')
        ->with($sessionKey)
        ->willReturn($limit);

    $result = $this->mvpHelper->isMyCurrentLimit($limit);
    $this->assertTrue($result);
}

public function testIsMyCurrentLimitWithFallbackToDefault()
{
    $limit = 16;
    $mode = 'grid';
    $sessionKey = 'ProductListLimitGrid';
    $defaultLimit = 16;

    $this->requestMock->expects($this->once())
        ->method('getParam')
        ->with('product_list_mode', 'list')
        ->willReturn($mode);

    $this->customerSessionMock->expects($this->once())
        ->method('getData')
        ->with($sessionKey)
        ->willReturn(null);

    $this->scopeConfigMock->expects($this->once())
        ->method('getValue')
        ->with('catalog/frontend/grid_per_page', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null)
        ->willReturn($defaultLimit);

    $result = $this->mvpHelper->isMyCurrentLimit($limit);
    $this->assertTrue($result);
}



}