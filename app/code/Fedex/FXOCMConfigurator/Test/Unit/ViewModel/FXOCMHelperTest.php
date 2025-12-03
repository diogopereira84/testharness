<?php

namespace Fedex\FXOCMConfigurator\Test\Unit\ViewModel;

use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Fedex\FXOCMConfigurator\Helper\Data;
use Fedex\FXOCMConfigurator\ViewModel\FXOCMHelper;
use Fedex\CloudDriveIntegration\Helper\Data as cloudHelper;
use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\Cart\ViewModel\ProductInfoHandler;
use Magento\Checkout\Model\Cart;
use PHPUnit\Framework\TestCase;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Company\Api\Data\CompanyInterface;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\Company\Helper\Data as CompanyHelper;
use Magento\Customer\Model\SessionFactory;
use Magento\Customer\Model\Session;
use Fedex\FXOCMConfigurator\Helper\Batchupload;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\Base\Helper\Auth;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\ScopeInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;

class FXOCMHelperTest extends TestCase
{
    protected $productInfoHandlerMock;
    protected $cartMock;
    protected $selfRegHelperMock;
    protected $productRepositoryMock;
    protected $deliveryHelperMock;
    protected $catalogmvpMock;
    protected $productInterfaceMock;
    protected $companyInterface;
    protected $adminConfigMock;
    protected $companyHelperMock;
    protected $sessionFactoryMock;
    protected $sessionMock;
    protected $punchoutMock;
    protected $batchUploadMock;
    protected $storeManagerInterfaceMock;
    protected $storeMock;
    protected $curlMock;
    protected $configInterfaceMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var FXOCMHelper
     */
    protected $fxocmHelper;

    /**
     * @var MockObject|Data
     */
    protected $dataHelperMock;

    protected $cloudHelperMock;

    protected $ssoconfigMock;

    protected $productInfoHandler;

    protected $cart;

    protected $sdeHelper;

    protected $adminConfigHelper;

    protected $customerSessionFactory;

    protected $batchupload;

    protected $punchoutHelper;

    protected Auth|MockObject $baseAuthMock;

    protected $catalogDocumentRefranceApi;

    protected $quoteItemCollectionFactory;

    protected $performanceImprovementPhaseTwoConfig;

    protected $productBundleConfig;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->dataHelperMock = $this->getMockBuilder(Data::class)
            ->setMethods([
                'getFxoCMSdkUrl',
                'getFxoCMToggle',
                'getFxoCMBaseUrl',
                'getFxoCMClientId',
                'checkAreaCode',
                'isEnabled',
                'getBatchUploadToggle',
                'getNewDocumentsApiImagePreviewToggle',
                'getRedirectUrl',
                'isNonStandardCatalogToggleEnabled',
                'isCompanySettingNonStandardCatalogToggleEnabled',
                'isNscUserFlow',
                'getIntegrationType',
                'getFooterContent',
                'getFooterText',
                'getFooterLink',
                'getLogoUrl',
                'checkFxoCmEproCustomDocEnabled',
                'getFixedQtyHandlerToggle',
                'getFixAllowFileUploadToggle',
                'getSkuOnlyProductId',
                'getEproMigratedCustomDocToggle',
                'getPrintReadyCustomDocFixToggle',
                'isEproUploadToQuoteToggleEnable',
                'getConvertToSizeModalText',
                'getConvertToSizeModalRedirectLink',
                'getUserWorkSpaceNewDocumentToggle',
                'getEproIntegrationType',
                'getAllowFileUploadCatalogFlow',
                'removeLegacyDocumentFromWorkspace'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cloudHelperMock = $this->getMockBuilder(cloudHelper::class)
            ->setMethods(['isEnabled', 'isBoxEnabled','isDropboxEnabled','isGoogleEnabled', 'isMicrosoftEnabled'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->ssoconfigMock = $this->getMockBuilder(SsoConfiguration::class)
            ->setMethods(['isFclCustomer', 'isRetail','getHomeUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productInfoHandlerMock = $this->getMockBuilder(ProductInfoHandler::class)
            ->setMethods(['getItemExternalProd'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartMock = $this->getMockBuilder(Cart::class)
            ->onlyMethods(['getItems', 'getQuote'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(['getIsSdeStore'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->selfRegHelperMock = $this->getMockBuilder(SelfReg::class)
            ->setMethods(['isSelfRegCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->deliveryHelperMock = $this->getMockBuilder(DeliveryHelper::class)
            ->setMethods(['getAssignedCompany', 'isCommercialCustomer', 'getCompanySite', 'isSelfRegCustomerAdminUser', 'getCompanyName', 'isEproCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogmvpMock = $this->getMockBuilder(CatalogMvp::class)
            ->setMethods(['isMvpSharedCatalogEnable'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productInterfaceMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyInterface = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
              'getBoxEnabled',
              'getDropboxEnabled',
              'getGoogleEnabled',
              'getMicrosoftEnabled'
            ])
            ->getMockForAbstractClass();

        $this->adminConfigMock = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
              'isUploadToQuoteToggle',
              'getUploadToQuoteConfigValue',
              'getNonStandardCatalogConfigValue',
              'isUploadToQuoteEnable',
              'isUploadToQuoteEnableForNSCFlow',
              'isAllowNonStandardCatalogForUser'
            ])
            ->getMock();

       $this->companyHelperMock = $this->getMockBuilder(CompanyHelper::class)
            ->setMethods(['getFedexAccountNumber'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionFactoryMock = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn'])
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->punchoutMock = $this->getMockBuilder(PunchoutHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTazToken','getAuthGatewayToken'])
            ->getMock();

        $this->batchUploadMock = $this->getMockBuilder(BatchUpload::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getUserWorkspaceSessionValue',
                'customerId',
                'getUserworkSpaceFromCustomerId',
                'addDataInSession',
                'getApplicationType',
                'getRetailPrintUrl',
                'getCommercialPrintUrl'
            ])->getMock();

       $this->storeManagerInterfaceMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseUrl', 'getId'])
            ->getMock();

        $this->curlMock = $this->createMock(Curl::class);

        $this->configInterfaceMock = $this->createMock(ScopeConfigInterface::class);

        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->catalogDocumentRefranceApi = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['documentLifeExtendApiCallWithDocumentId'])
            ->getMock();

        $this->quoteItemCollectionFactory = $this->createMock(CollectionFactory::class);

        $this->performanceImprovementPhaseTwoConfig = $this->createMock(PerformanceImprovementPhaseTwoConfig::class);

        $this->productBundleConfig = $this->createMock(ConfigInterface::class);

        $this->fxocmHelper = $objectManager->getObject(
            FXOCMHelper::class,
            [
                'fxocmhelper' => $this->dataHelperMock,
                'cloudHelper' => $this->cloudHelperMock,
                'ssoConfiguration' => $this->ssoconfigMock,
                'productInfoHandler' => $this->productInfoHandlerMock,
                'cart' => $this->cartMock,
                'catalogmvp' => $this->catalogmvpMock,
                'selfRegHelper' => $this->selfRegHelperMock,
                'productRepository' => $this->productRepositoryMock,
                'deliveryHelper' => $this->deliveryHelperMock,
                'sdeHelper' => $this->sdeHelper,
                'adminConfigHelper' => $this->adminConfigMock,
                'companyHelper' => $this->companyHelperMock,
                'customerSessionFactory' => $this->sessionFactoryMock,
                'batchupload' => $this->batchUploadMock,
                'storeManager' => $this->storeManagerInterfaceMock,
                'quoteItemCollectionFactory' => $this->quoteItemCollectionFactory,
                'punchoutHelper' => $this->punchoutMock,
                'authHelper' => $this->baseAuthMock,
                'performanceImprovementPhaseTwoConfig' => $this->performanceImprovementPhaseTwoConfig,
                'configInterface' => $this->configInterfaceMock,
                'curl' => $this->curlMock,
                'logger' => $this->loggerMock,
                'catalogDocumentRefranceApi' => $this->catalogDocumentRefranceApi,
                'productBundleConfig' => $this->productBundleConfig
            ]
        );
    }

    /**
     * Test the isIframeSDKEnable method when getFxoCMToggle returns true.
     */
    public function testIsIframeSDKEnableTrue()
    {
        $this->dataHelperMock->method('getFxoCMToggle')
            ->willReturn(true);

        $result = $this->fxocmHelper->isIframeSDKEnable();
        $this->assertTrue($result);
    }

    /**
     * Test the isIframeSDKEnable method when getFxoCMToggle returns false.
     */
    public function testIsIframeSDKEnableFalse()
    {
        $this->dataHelperMock->method('getFxoCMToggle')
            ->willReturn(false);

        $result = $this->fxocmHelper->isIframeSDKEnable();
        $this->assertFalse($result);
    }

    /**
     * Test case to get the SDK url from Store config.
     */
    public function testGetSdkUrl() {
        $this->dataHelperMock->expects($this->any())->method('getFxoCMSdkUrl')
        ->willReturn('example.js');
        $this->assertEquals($this->fxocmHelper->getSdkUrl(),true);
    }

    /**
     * Test case to get skuonly product id from Store config.
     */
    public function testGetSkuOnlyProductId() {
        $this->dataHelperMock->expects($this->any())->method('getSkuOnlyProductId')
        ->willReturn('123456');
        $this->assertEquals($this->fxocmHelper->getSkuOnlyProductId(),true);
    }

    /**
     * Test case to get the SDK url from Store config.
     */
    public function testGetBaseUrl() {
        $this->dataHelperMock->expects($this->any())->method('getFxoCMBaseUrl')
        ->willReturn('example.js');
        $this->assertEquals($this->fxocmHelper->getBaseUrl(),true);
    }

    /**
     * Test case to get the SDK url from Store config.
     */
    public function testGetClientId() {
        $this->dataHelperMock->expects($this->any())->method('getFxoCMClientId')
        ->willReturn('example.js');
        $this->assertEquals($this->fxocmHelper->getClientId(),true);
    }

    /**
     * Test to check area code
     */
    public function testCheckAreaCode() {
        $this->dataHelperMock->expects($this->any())->method('checkAreaCode')
        ->willReturn('adminhtml');
        $this->assertEquals('adminhtml',$this->fxocmHelper->checkAreaCode());
    }

    /**
     * Test to check upload enabled
     */
    public function testIsEnabled() {
        $this->testIsBoxEnabledIf();
        $this->testIsDropboxEnabledIf();
        $this->testIsGoogleEnabledIf();
        $this->testIsMicrosoftEnabledIf();
        $this->assertEquals(true, $this->fxocmHelper->isEnabled());
    }

    /**
     * Test to check box enabled if
     */
    public function testIsBoxEnabledIf() {
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')
        ->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getAssignedCompany')
        ->willReturn($this->companyInterface);
        $this->cloudHelperMock->expects($this->any())->method('isBoxEnabled')
        ->willReturn(true);
        $this->companyInterface->expects($this->any())->method('getBoxEnabled')
        ->willReturn(true);
        $this->assertEquals(true, $this->fxocmHelper->isBoxEnabled());
    }

    /**
     * Test to check box enabled else
     */
    public function testIsBoxEnabledElse() {
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')
        ->willReturn(false);
        $this->cloudHelperMock->expects($this->any())->method('isBoxEnabled')
        ->willReturn(true);
        $this->assertEquals(true, $this->fxocmHelper->isBoxEnabled());
    }

    /**
     * Test to check dropbox enabled if
     */
    public function testIsDropboxEnabledIf() {
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')
        ->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getAssignedCompany')
        ->willReturn($this->companyInterface);
        $this->cloudHelperMock->expects($this->any())->method('isDropboxEnabled')
        ->willReturn(true);
        $this->companyInterface->expects($this->any())->method('getDropboxEnabled')
        ->willReturn(true);
        $this->assertEquals(true, $this->fxocmHelper->isDropboxEnabled());
    }

    /**
     * Test to check dropbox enabled else
     */
    public function testIsDropboxEnabledElse() {
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')
        ->willReturn(false);
        $this->cloudHelperMock->expects($this->any())->method('isDropboxEnabled')
        ->willReturn(true);
        $this->assertEquals(true, $this->fxocmHelper->isDropboxEnabled());
    }

    /**
     * Test to check google enabled If
     */
    public function testIsGoogleEnabledIf() {
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')
        ->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getAssignedCompany')
        ->willReturn($this->companyInterface);
        $this->cloudHelperMock->expects($this->any())->method('isGoogleEnabled')
        ->willReturn(true);
        $this->companyInterface->expects($this->any())->method('getGoogleEnabled')
        ->willReturn(true);
        $this->assertEquals(true, $this->fxocmHelper->isGoogleEnabled());
    }

    /**
     * Test to check google enabled Else
     */
    public function testIsGoogleEnabledElse() {
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')
        ->willReturn(false);
        $this->cloudHelperMock->expects($this->any())->method('isGoogleEnabled')
        ->willReturn(true);
        $this->assertEquals(true, $this->fxocmHelper->isGoogleEnabled());
    }

    /**
     * Test to check microsoft enabled If
     */
    public function testIsMicrosoftEnabledIf() {
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')
        ->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getAssignedCompany')
        ->willReturn($this->companyInterface);
        $this->cloudHelperMock->expects($this->any())->method('isMicrosoftEnabled')
        ->willReturn(true);
        $this->companyInterface->expects($this->any())->method('getMicrosoftEnabled')
        ->willReturn(true);
        $this->assertEquals(true, $this->fxocmHelper->isMicrosoftEnabled());
    }

    /**
     * Test to check microsoft enabled Else
     */
    public function testIsMicrosoftEnabledElse() {
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')
        ->willReturn(false);
        $this->cloudHelperMock->expects($this->any())->method('isMicrosoftEnabled')
        ->willReturn(true);
        $this->assertEquals(true, $this->fxocmHelper->isMicrosoftEnabled());
    }

    /**
     * Test to check is retail customer
     */
    public function testIsRetail() {
        $this->ssoconfigMock->expects($this->any())->method('isRetail')
        ->willReturn(true);
        $this->assertEquals(true, $this->fxocmHelper->isRetail());
    }

    public function testGetIsSdeStore() {
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')
        ->willReturn(true);
        $this->assertEquals(true, $this->fxocmHelper->getIsSdeStore());
    }

    /**
     * Get External Product
     */
    public function testGetExternalProd() {
        $items = ['1','2'];
        $this->productInfoHandlerMock->expects($this->any())->method('getItemExternalProd')
        ->willReturn($items);
        $this->assertEquals($items, $this->fxocmHelper->getExternalProd($items));
    }

    /**
     * Get Cart Item
     */
    public function testIsTigerE468338ToggleEnabled() {
        $this->productBundleConfig->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(true);
        $this->assertTrue($this->fxocmHelper->isTigerE468338ToggleEnabled());
    }

    /**
     * Get Cart Item
     */
    public function testGetCheckoutItem() {
        $items = ['1','2'];
        $this->productBundleConfig->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(false);
        $this->cartMock->expects($this->any())->method('getItems')
        ->willReturn($items);
        $this->assertEquals($items, $this->fxocmHelper->getCheckoutItem());
    }

    /**
     * Get Cart Item
     */
    public function testGetCheckoutItemToggleEnabled() {
        $items = ['1','2'];
        $this->productBundleConfig->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(true);

        $quote = $this->createMock(Quote::class);
        $quote->expects($this->once())->method('getAllVisibleItems')
            ->willReturn($items);
        $this->cartMock->expects($this->once())->method('getQuote')
            ->willReturn($quote);

        $result = $this->fxocmHelper->getCheckoutItem();
        $this->assertEquals($items, $result);
        $this->assertCount(2, $result);
    }

    public function testIsMvpSharedCatalogEnable() {
        $this->catalogmvpMock->expects($this->any())->method('isMvpSharedCatalogEnable')
        ->willReturn(true);
        $this->assertEquals(true, $this->fxocmHelper->isMvpSharedCatalogEnable());
    }

    public function testIsSelfRegCustomer() {
        $this->selfRegHelperMock->expects($this->any())->method('isSelfRegCustomer')
        ->willReturn(true);
        $this->assertEquals(true, $this->fxocmHelper->isSelfRegCustomer());
    }

    public function testGetProductIfSku() {
        $this->productRepositoryMock->expects($this->any())->method('get')
        ->willReturn($this->productInterfaceMock);
        $this->assertEquals($this->productInterfaceMock, $this->fxocmHelper->getProductData(123));
    }

    public function testGetProductCatchCase()
    {
        $this->productRepositoryMock->expects($this->any())->method('get')
        ->willThrowException(new NoSuchEntityException());
        $this->assertEquals(false, $this->fxocmHelper->getProductData(123));
    }

    public function testGetNonStandardCatalogToggleEnabled()
    {
        $this->dataHelperMock->expects($this->any())->method('isNonStandardCatalogToggleEnabled')->willReturn(true);
        $this->dataHelperMock->expects($this->any())->method('isCompanySettingNonStandardCatalogToggleEnabled')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getAssignedCompany')
            ->willReturn($this->companyInterface);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getId')->willReturn(12);

        $this->assertTrue($this->fxocmHelper->getNonStandardCatalogToggleEnabled());
    }

    public function testGetNonStandardCatalogToggleDisabled()
    {
        $this->dataHelperMock->expects($this->any())->method('isNonStandardCatalogToggleEnabled')->willReturn(false);
        $this->deliveryHelperMock->expects($this->any())->method('getAssignedCompany')
            ->willReturn($this->companyInterface);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getId')->willReturn(12);
        $this->assertFalse($this->fxocmHelper->getNonStandardCatalogToggleEnabled());
    }

    public function testGetPrintInstructionsForCompanyAdmin()
    {
        $configValue = [];

        $this->testGetNonStandardCatalogToggleEnabled();
        $this->deliveryHelperMock->expects($this->any())->method('isSelfRegCustomerAdminUser')->willReturn(true);

        $configValue['isSelfRegCustomerAdminUser'] = true;
        $configValue['isNonStandardCatalogToggleEnabled'] = true;

        $this->assertEquals($configValue, $this->fxocmHelper->getPrintInstructionsForCompanyAdmin());
    }

    public function testGetAdditionalPrintInstructionsConfigValue()
    {
        $uploadToQuoteArray = [];
        $this->testGetEnableUploadToQuoteForNSCFlow();

        $uploadToQuoteArray['title'] = $this->adminConfigMock->expects($this->any())
        ->method('getNonStandardCatalogConfigValue')
        ->willReturn('test');
        $uploadToQuoteArray['Message'] = $this->adminConfigMock->expects($this->any())
        ->method('getNonStandardCatalogConfigValue')
        ->willReturn('test');
        $uploadToQuoteArray['EnableUploadToQuote'] = $this->adminConfigMock->expects($this->any())
        ->method('getNonStandardCatalogConfigValue')
        ->willReturn('test');
        $uploadToQuoteArray['warrningMessage'] = $this->adminConfigMock->expects($this->any())
        ->method('getNonStandardCatalogConfigValue')
        ->willReturn('test');

        $this->assertNotNull($this->fxocmHelper->getAdditionalPrintInstructionsConfigValue());
    }

    public function testGetUploadToQuoteConfigValue()
    {
        $uploadToQuoteArray = [];
        $this->testGetEnableUploadToQuoteForNSCFlow();

        $uploadToQuoteArray['title'] = $this->adminConfigMock->expects($this->any())
        ->method('getUploadToQuoteConfigValue')
        ->willReturn('test');
        $uploadToQuoteArray['Message'] = $this->adminConfigMock->expects($this->any())
        ->method('getUploadToQuoteConfigValue')
        ->willReturn('test');
        $uploadToQuoteArray['EnableUploadToQuote'] = $this->adminConfigMock->expects($this->any())
        ->method('getUploadToQuoteConfigValue')
        ->willReturn('test');
        $uploadToQuoteArray['warrningMessage'] = $this->adminConfigMock->expects($this->any())
        ->method('getUploadToQuoteConfigValue')
        ->willReturn('test');

        $this->assertNotNull($this->fxocmHelper->getUploadToQuoteConfigValue());
    }

    public function testGetFedexAccountNumber()
    {
        $this->deliveryHelperMock->expects($this->any())->method('getAssignedCompany')
        ->willReturn($this->companyInterface);
        $this->companyHelperMock->expects($this->any())->method('getFedexAccountNumber')
        ->willReturn('6542378');
        $this->assertNotNull($this->fxocmHelper->getFedexAccountNumber());
    }

    /**
     * Check logged in customer
     */
    public function testCheckLoggedInCustomer()
    {
        $this->sessionFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->sessionMock);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')
        ->willReturn(true);
        $this->assertNotNull($this->fxocmHelper->checkLoggedInCustomer());
    }

    /**
     * Test the Batch Upload Toggle.
     */
    public function testBatchUploadToggleTrue()
    {
        $this->dataHelperMock->method('getBatchUploadToggle')
            ->willReturn(true);

        $result = $this->fxocmHelper->isBatchUploadEnable();
        $this->assertTrue($result);
    }

    /**
     * Test New Documents Api Image Enable Toggle.
     */
    public function testisNewDocumentsApiImageEnableTrue()
    {
        $this->dataHelperMock->method('getNewDocumentsApiImagePreviewToggle')
            ->willReturn(true);

        $result = $this->fxocmHelper->isNewDocumentsApiImageEnable();
        $this->assertTrue($result);
    }

    public function testGetUserworkspaceSession() {
        $this->batchUploadMock->expects($this->any())->method('getUserWorkspaceSessionValue')
        ->willReturn('test');
        $result = $this->fxocmHelper->getUserworkspaceSession();
        $this->assertEquals('test',$result);
    }

    public function testGetCustomerId() {
        $this->batchUploadMock->expects($this->any())->method('customerId')
        ->willReturn(12);
        $result = $this->fxocmHelper->getCustomerId();
        $this->assertEquals(12,$result);
    }

    public function testGetUserworkSpaceFromCustomerId() {
        $this->batchUploadMock->expects($this->any())->method('getUserworkSpaceFromCustomerId')
        ->willReturn('test');
        $result = $this->fxocmHelper->getUserworkSpaceFromCustomerId(12);
        $this->assertEquals('test',$result);
    }

    public function testAddDataInSession() {
        $this->batchUploadMock
            ->expects($this->any())
            ->method('addDataInSession')
            ->willReturnSelf();
        $result = $this->fxocmHelper->addDataInSession('12');
        $this->assertNotNull($result);
    }

    public function testGetWorkspaceData() {
        $this->batchUploadMock->expects($this->any())->method('getUserWorkspaceSessionValue')
        ->willReturn('');
        $this->testGetCustomerId();
        $this->testGetUserworkSpaceFromCustomerId();
        $this->testAddDataInSession();
        $result = $this->fxocmHelper->getWorkspaceData();
        $this->assertNotNull($result);
    }

    /**
    * Test case for redirect URL
    *
    */
    public function testgetRedirectUrl() {
        $this->dataHelperMock->expects($this->any())->method('getRedirectUrl')
        ->willReturn('www.test.com');
        $result = $this->fxocmHelper->getRedirectUrl();
        $this->assertEquals('www.test.com',$result);
    }


    /**
     * Test for getBaseUrl method.
     *
     * @return Null|String
     */
    public function testGetallProductsPageUrl()
    {
        $this->batchUploadMock->expects($this->any())->method('getApplicationType')
        ->willReturn('retail');
        $this->batchUploadMock->expects($this->any())->method('getRetailPrintUrl')
        ->willReturn('print-products-retail.html');
        $this->batchUploadMock->expects($this->any())->method('getCommercialPrintUrl')
        ->willReturn('b2b-print-products.html');
        $expectedResult = 'https://staging3.office.fedex.com/default/';
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')->willReturn($expectedResult);
        $actualResult = $this->fxocmHelper->getallProductsPageUrl();
        $expectedResultModf = 'https://staging3.office.fedex.com/print-products-retail.html';
        $this->assertEquals($expectedResultModf, $actualResult);
    }

        /**
     * Test for getBaseUrl method.
     *
     * @return Null|String
     */
    public function testGetallProductsPageUrlCommercial()
    {
        $this->batchUploadMock->expects($this->any())->method('getApplicationType')
        ->willReturn('selfreg');
        $this->batchUploadMock->expects($this->any())->method('getRetailPrintUrl')
        ->willReturn('print-products-retail.html');
        $this->batchUploadMock->expects($this->any())->method('getCommercialPrintUrl')
        ->willReturn('b2b-print-products.html');
        $expectedResult = 'https://staging3.office.fedex.com/ondemand/';
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')->willReturn($expectedResult);
        $actualResult = $this->fxocmHelper->getallProductsPageUrl();
        $expectedResultModf = 'https://staging3.office.fedex.com/ondemand/b2b-print-products.html';
        $this->assertEquals($expectedResultModf, $actualResult);
    }

    public function testGetEnableUploadToQuote() {
        $this->deliveryHelperMock->expects($this->any())->method('getAssignedCompany')
        ->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getId')
        ->willReturn(12);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getId')->willReturn(12);
        $this->adminConfigMock->expects($this->any())->method('isUploadToQuoteEnable')->willReturn(true);
        $result = $this->fxocmHelper->getEnableUploadToQuote();
        $this->assertTrue($result);
    }

    public function testIsAllowNonStandardCatalog() {
        $this->deliveryHelperMock->expects($this->any())->method('getAssignedCompany')
            ->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getId')
            ->willReturn(12);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getId')->willReturn(12);
        $this->adminConfigMock->expects($this->any())->method('isAllowNonStandardCatalogForUser')->willReturn(true);
        $result = $this->fxocmHelper->isAllowNonStandardCatalog();
        $this->assertTrue($result);
    }

    public function testIsAllowNonStandardCatalogFalse() {
        $this->deliveryHelperMock->expects($this->any())->method('getAssignedCompany')
            ->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getId')
            ->willReturn(12);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getId')->willReturn(12);
        $this->adminConfigMock->expects($this->any())->method('isAllowNonStandardCatalogForUser')->willReturn(false);
        $result = $this->fxocmHelper->isAllowNonStandardCatalog();
        $this->assertFalse($result);
    }

    public function testGetEnableUploadToQuoteForNSCFlow() {
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getId')->willReturn(12);
        $this->adminConfigMock->expects($this->any())->method('isUploadToQuoteEnableForNSCFlow')->willReturn(true);
        $result = $this->fxocmHelper->getEnableUploadToQuoteForNSCFlow();
        $this->assertTrue($result);
    }

    /**
     * Get company site name
     */
    public function testGetSiteName() {
        $this->deliveryHelperMock->expects($this->any())->method('getCompanySite')
        ->willReturn('l6site51');
        $result = $this->fxocmHelper->getSiteName();
        $this->assertEquals('l6site51', $result);
    }

    /**
     * Get company site name
     */
    public function testGetSiteNamewithElse() {
        $this->deliveryHelperMock->expects($this->any())->method('getCompanySite')
        ->willReturn(null);
        $this->deliveryHelperMock->expects($this->any())->method('getCompanyName')
        ->willReturn('l6site51');
        $result = $this->fxocmHelper->getSiteName();
        $this->assertEquals('l6site51', $result);
    }

    /**
     * Get Taz token
     */
    public function testGetTazToken() {
        $this->punchoutMock->expects($this->any())->method('getTazToken')
        ->willReturn('123456asdfgh');
        $result = $this->fxocmHelper->getTazToken();
        $this->assertNotNull($result);
    }

    /**
     * Get Inetgration Type
     */
    public function testGetIntegrationType() {

        $this->dataHelperMock->expects($this->any())
        ->method('getEproIntegrationType')
        ->willReturn(false);

        $this->deliveryHelperMock
            ->expects($this->any())
            ->method('isEproCustomer')
            ->willReturn(false);

        $this->dataHelperMock->expects($this->any())->method('getIntegrationType')
        ->willReturn('IFRAME');
        $this->assertEquals($this->fxocmHelper->getIntegrationType(),"IFRAME");
    }

    /**
     * Test get Footer Content
     */
    public function testgetFooterContent()
    {
        $this->dataHelperMock->method('getFooterContent')
            ->willReturn('Footer Content');

        $this->assertEquals($this->fxocmHelper->getFooterContent(),"Footer Content");
    }

    /*
     * Test get Logo Url
     */
    public function testgetLogoUrl()
    {
        $this->dataHelperMock->method('getLogoUrl')
            ->willReturn('test.jpg');

        $this->assertEquals($this->fxocmHelper->getLogoUrl(),"test.jpg");
    }

    /*
     * Test get Company Name
     */
    public function testGetCompanyName()
    {
        $this->ssoconfigMock->expects($this->any())->method('isRetail')
        ->willReturn(false);
        $this->deliveryHelperMock
            ->expects($this->any())
            ->method('getCompanyName')
            ->willReturn('l6site51');

        $this->assertEquals("l6site51",$this->fxocmHelper->getCompanyName());
    }


    /**
     * Test get Footer Text
     */
    public function testgetFooterText()
    {
        $this->dataHelperMock->method('getFooterText')
            ->willReturn('fxocm_footer_text');

        $this->assertEquals($this->fxocmHelper->getFooterText(),"fxocm_footer_text");
    }

    /**
     * Test get Footer Link
     */
    public function testgetFooterLink()
    {
        $this->dataHelperMock->method('getFooterLink')
            ->willReturn('fxocm_footer_link');

        $this->assertEquals($this->fxocmHelper->getFooterLink(),"fxocm_footer_link");
    }

    /*
     * Test get check epro customer
     */
    public function testIsEproCustomer() {
        $this->deliveryHelperMock
            ->expects($this->any())
            ->method('isEproCustomer')
            ->willReturn(true);
        $this->assertEquals("true",$this->fxocmHelper->isEproCustomer());
    }

    /**
     * Test the Batch Upload Toggle.
     */
    public function testCheckFxoCmEproCustomDocEnabled()
    {
        $this->dataHelperMock->method('checkFxoCmEproCustomDocEnabled')
            ->willReturn(true);

        $result = $this->fxocmHelper->checkFxoCmEproCustomDocEnabled();
        $this->assertTrue($result);
    }

    /**
     * Test toggle value for fixed qty handler
     */
    public function testGetFixedQtyHandlerToggle() {
        $this->dataHelperMock
            ->method('getFixedQtyHandlerToggle')
            ->willReturn(true);

        $result = $this->fxocmHelper->getFixedQtyHandlerToggle();
        $this->assertTrue($result);
    }

    /**
     * Test Get toggle value for allowFileUpload issue D-177591
     * @param boolean
     */
    public function testGetFixAllowFileUploadToggle() {
        $this->dataHelperMock
            ->method('getFixAllowFileUploadToggle')
            ->willReturn(true);
        $this->assertTrue($this->fxocmHelper->getFixAllowFileUploadToggle());
    }

    /**
     * Test Get Toggle Value epro Custom doc for migrated Document Toggle
     */
    public function testGetEproMigratedCustomDocToggle() {
        $this->dataHelperMock
            ->method('getEproMigratedCustomDocToggle')
            ->willReturn(true);
        $result = $this->fxocmHelper->getEproMigratedCustomDocToggle();
        $this->assertTrue($result);
    }

    /**
     * Test Get Toggle Value Epro Upload to Quote
     */
    public function testIsEproUploadToQuoteToggleEnable() {
        $this->dataHelperMock
            ->method('isEproUploadToQuoteToggleEnable')
            ->willReturn(true);
        $result = $this->fxocmHelper->isEproUploadToQuoteToggleEnable();
        $this->assertTrue($result);
    }

    /**
     * Test Get Page Group from print ready call
     */
    public function testGetPageGroupsPrintReadySuccessWithArrayReturn()
    {
        $documentId = '123';
        $returnArray = true;
        $url = 'http://example.com/api';
        $dataString = json_encode([
            "printReadyRequest" => [
                "documentId" => $documentId,
                "conversionOptions" => [
                    "lockContentOrientation" => false,
                    "minDPI" => 200,
                    "defaultImageWidthInInches" => "8.5",
                    "defaultImageHeightInInches" => "11"
                ],
                "normalizationOptions" => [
                    "lockContentOrientation" => false,
                    "marginWidthInInches" => "0",
                    "targetWidthInInches" => "",
                    "targetHeightInInches" => "",
                    "targetOrientation" => "UNKNOWN"
                ],
                "previewURL" => true,
                "expiration" => [
                    "units" => "DAYS",
                    "value" => 365
                ]
            ]
        ]);
        $response = [
            'output' => [
                'document' => [
                    'documentMetrics' => [
                        'pageGroups' => [
                            [
                                'startPageNumber' => 1,
                                'endPageNumber' => 5,
                                'pageWidthInInches' => 8.5,
                                'pageHeightInInches' => 11
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->configInterfaceMock->method('getValue')
            ->willReturn($url);
        $this->punchoutMock->method('getAuthGatewayToken')
            ->willReturn('token');
        $this->curlMock->method('post')
            ->willReturn(true);
        $this->curlMock->method('getBody')
            ->willReturn(json_encode($response));

        $result = $this->fxocmHelper->getPageGroupsPrintReady($documentId, $returnArray);
        $expected = [
            [
                'start' => 1,
                'end' => 5,
                'width' => 8.5,
                'height' => 11
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test Get Page Group Print Ready with error
     */
    public function testGetPageGroupsPrintReadyError()
    {
        $documentId = '123';
        $returnArray = false;
        $url = 'http://example.com/api';
        $dataString = json_encode([
            "printReadyRequest" => [
                "documentId" => $documentId,
                "conversionOptions" => [
                    "lockContentOrientation" => false,
                    "minDPI" => 200,
                    "defaultImageWidthInInches" => "8.5",
                    "defaultImageHeightInInches" => "11"
                ],
                "normalizationOptions" => [
                    "lockContentOrientation" => false,
                    "marginWidthInInches" => "0",
                    "targetWidthInInches" => "",
                    "targetHeightInInches" => "",
                    "targetOrientation" => "UNKNOWN"
                ],
                "previewURL" => true,
                "expiration" => [
                    "units" => "DAYS",
                    "value" => 365
                ]
            ]
        ]);
        $response = ['errors' => 'Some error occurred'];

        $this->configInterfaceMock->method('getValue')
            ->willReturn($url);
        $this->punchoutMock->method('getAuthGatewayToken')
            ->willReturn('token');
        $this->curlMock->method('post')
            ->willReturn(true);
        $this->curlMock->method('getBody')
            ->willReturn(json_encode($response));

        $result = $this->fxocmHelper->getPageGroupsPrintReady($documentId, $returnArray);
        $this->assertEquals('[]', $result);
    }

    /**
     * Test Get Page Group with exception
     */
    public function testGetPageGroupsPrintReadyException()
    {
        $documentId = '123';
        $returnArray = false;
        $url = 'http://example.com/api';
        $dataString = json_encode([
            "printReadyRequest" => [
                "documentId" => $documentId,
                "conversionOptions" => [
                    "lockContentOrientation" => false,
                    "minDPI" => 200,
                    "defaultImageWidthInInches" => "8.5",
                    "defaultImageHeightInInches" => "11"
                ],
                "normalizationOptions" => [
                    "lockContentOrientation" => false,
                    "marginWidthInInches" => "0",
                    "targetWidthInInches" => "",
                    "targetHeightInInches" => "",
                    "targetOrientation" => "UNKNOWN"
                ],
                "previewURL" => true,
                "expiration" => [
                    "units" => "DAYS",
                    "value" => 365
                ]
            ]
        ]);
        $response = ['errors' => 'Some error occurred'];

        $this->configInterfaceMock->method('getValue')
            ->willReturn($url);
        $this->punchoutMock->method('getAuthGatewayToken')
            ->willReturn('token');

        $this->curlMock->expects($this->any())->method('getBody')
        ->willThrowException(new \Exception('Exception'));

        $result = $this->fxocmHelper->getPageGroupsPrintReady($documentId, $returnArray);
        $this->assertEquals('[]', $result);
    }

    /**
     * Test Print Ready Custom Doc Toggle
     */
    public function testGetPrintReadyCustomDocFixToggle()
    {
        $this->dataHelperMock->method('getPrintReadyCustomDocFixToggle')
            ->willReturn(true);

        $result = $this->fxocmHelper->getPrintReadyCustomDocFixToggle();
        $this->assertTrue($result);
    }

    /**
     * Test function to get converted size modal text configuration value
     */
    public function testGetConvertToSizeModalText() {
        $this->dataHelperMock
            ->method('getConvertToSizeModalText')
            ->willReturn('text');
        $this->assertEquals($this->fxocmHelper->getConvertToSizeModalText(), 'text');
    }

    /**
     * Test to get home Url
     */
    public function testgetHomeUrl() {
        $this->ssoconfigMock->expects($this->any())->method('getHomeUrl')
        ->willReturn('https://staging3.office.fedex.com/');
        $this->assertEquals(true, $this->fxocmHelper->getHomeUrl());
    }


    /**
     * Test getTrackOrderUrl method.
     *
     * @return void
     */
    public function testgetGeneralConfig()
    {
        $expectedUrl = 'sso/general/query_parameter';
        $this->configInterfaceMock ->expects($this->once())
            ->method('getValue')
            ->with('sso/general/query_parameter', ScopeInterface::SCOPE_STORE)
            ->willReturn($expectedUrl);

        $getGeneralConfigUrl = $this->fxocmHelper->getGeneralConfig('sso/general/query_parameter');
        $this->assertEquals($expectedUrl, $getGeneralConfigUrl);
    }

    /**
     * Test New Documents Api Image Enable Toggle.
     */
    public function testIsUserWorkSpaceNewDocumentToggle()
    {
        $this->dataHelperMock->method('getUserWorkSpaceNewDocumentToggle')
            ->willReturn(true);

        $result = $this->fxocmHelper->isUserWorkSpaceNewDocumentToggle();
        $this->assertTrue($result);
    }

     /**
     * Test migrateWorkspaceId.
     */
    public function testMigrateWorkspaceId() {
        $response = [
            'output' => [
                'document' => [
                    'documentId' => '123123123123123123123123'
                ]
            ]
        ];
        $workSpaceData = '{ "files": [ { "name": "lambo.jpg", "id": 2, "size": 7292, "uploadDateTime": "2024-10-03T06:07:11.027Z" } ], "projects": [ { "associatedDocuments": [ { "name": "lambo.jpg", "id": 2, "size": 7292, "uploadDateTime": "2024-10-03T06:07:11.027Z" } ], "products": { "id": 1559886500133, "version": 2, "name": "Postcards", "qty": 50, "priceable": true, "features": [ { "id": 1448981549269, "name": "Sides", "choice": { "id": 1448988124807, "name": "Double-Sided", "properties": [ { "id": 1470166759236, "name": "SIDE_NAME", "value": "Double Sided" }, { "id": 1461774376168, "name": "SIDE", "value": "DOUBLE" } ] } }, { "id": 1448981549109, "name": "Paper Size", "choice": { "id": 1538130156765, "name": "5.50x8.50", "properties": [ { "id": 1571841122054, "name": "DISPLAY_HEIGHT", "value": "8.74" }, { "id": 1571841164815, "name": "DISPLAY_WIDTH", "value": "5.74" }, { "id": 1449069906033, "name": "MEDIA_HEIGHT", "value": "8.74" }, { "id": 1449069908929, "name": "MEDIA_WIDTH", "value": "5.74" } ] } }, { "id": 1448984679218, "name": "Orientation", "choice": { "id": 1449000016327, "name": "Horizontal", "properties": [ { "id": 1453260266287, "name": "PAGE_ORIENTATION", "value": "LANDSCAPE" } ] } }, { "id": 1534920174638, "name": "Envelope", "choice": { "id": 1634129308274, "name": "None", "properties": [] } }, { "id": 1559890755349, "name": "Color/Black & White", "choice": { "id": 1448988600611, "name": "Full Color", "properties": [ { "id": 1453242778807, "name": "PRINT_COLOR", "value": "COLOR" } ] } }, { "id": 1448981549741, "name": "Paper Type", "choice": { "id": 1535620925780, "name": "Matte Cover (100 lb.)", "properties": [ { "id": 1450324098012, "name": "MEDIA_TYPE", "value": "CC4" }, { "id": 1453234015081, "name": "PAPER_COLOR", "value": "#FFFFFF" }, { "id": 1470166630346, "name": "MEDIA_NAME", "value": "100# Matte" } ] } }, { "id": 1448981549581, "name": "Print Color", "choice": { "id": 1448988600611, "name": "Full Color", "properties": [ { "id": 1453242778807, "name": "PRINT_COLOR", "value": "COLOR" } ] } }, { "id": 1595519381715, "name": "Bleeds", "choice": { "id": 1595519381716, "name": "Full Bleed", "properties": [ { "id": 1595519381717, "name": "BLEED_TYPE", "value": "Full Bleed" } ] } } ], "properties": [ { "id": 1470151737965, "name": "TEMPLATE_AVAILABLE", "value": "YES" }, { "id": 1614715469176, "name": "IMPOSE_TEMPLATE_ID", "value": "0" }, { "id": 1453243262198, "name": "ENCODE_QUALITY", "value": "100" }, { "id": 1455050109636, "name": "DEFAULT_IMAGE_WIDTH", "value": "4.49" }, { "id": 1453894861756, "name": "LOCK_CONTENT_ORIENTATION", "value": "true" }, { "id": 1470151626854, "name": "SYSTEM_SI", "value": "ATTENTION TEAM MEMBER: DO NOT use the Production Instructions listed on the Job Ticket. Use the following instructions to produce this Quick Postcard order. Reset qty in PPA to 25 of the 2-up file. Print 11x17 100lb (CCX4 Matte). PRINT: Double Sided. Print Color: COLOR. Trim to bleed 8.5x5.5 with no folding, yield 50 pieces." }, { "id": 1453895478444, "name": "MIN_DPI", "value": "150.0" }, { "id": 1464709502522, "name": "PRODUCT_QTY_SET", "value": "50" }, { "id": 1490292304799, "name": "CUSTOMER_SI" }, { "id": 1455050109631, "name": "DEFAULT_IMAGE_HEIGHT", "value": "5.74" }, { "id": 1494365340946, "name": "PREVIEW_TYPE", "value": "DYNAMIC" }, { "id": 1453242488328, "name": "ZOOM_PERCENTAGE", "value": "50" }, { "id": 1454950109636, "name": "USER_SPECIAL_INSTRUCTIONS" } ], "pageExceptions": [], "proofRequired": false, "instanceId": 1727935590880, "userProductName": "lambo", "inserts": [], "exceptions": [], "addOns": [], "contentAssociations": [ { "fileSizeBytes": 0, "printReady": true, "contentReqId": 1455709847200, "purpose": "SINGLE_SHEET_FRONT", "pageGroups": [ { "start": 1, "end": 1, "width": 5.74, "height": 8.74, "orientation": "LANDSCAPE" } ], "physicalContent": false }, { "fileSizeBytes": 0, "printReady": true, "contentReqId": 1455709871072, "purpose": "SINGLE_SHEET_BACK", "pageGroups": [ { "start": 1, "end": 1, "width": 5.74, "height": 8.74, "orientation": "LANDSCAPE" } ], "physicalContent": false } ], "productionContentAssociations": [], "products": [], "externalSkus": [], "isOutSourced": false, "contextKeys": [] }, "integratorProductReference": "1693928958143-4", "availableSizes": "4.25\" x 5.5\", 5.5\" x 8.5\", 4\" x 6\"", "maxFiles": "2", "productType": "Postcards", "hasUserChangedProjectNameManually": false, "supportedProductSizes": { "featureId": "1448981549109", "featureName": "Size", "choices": [ { "choiceId": "1534756174767", "choiceName": "4.25\" x 5.5\"", "properties": [ { "name": "MEDIA_HEIGHT", "value": "5.74" }, { "name": "MEDIA_WIDTH", "value": "4.49" }, { "name": "DISPLAY_HEIGHT", "value": "5.5" }, { "name": "DISPLAY_WIDTH", "value": "4.25" } ] }, { "choiceId": "1538130156765", "choiceName": "5.5\" x 8.5\"", "properties": [ { "name": "MEDIA_HEIGHT", "value": "8.74" }, { "name": "MEDIA_WIDTH", "value": "5.74" }, { "name": "DISPLAY_HEIGHT", "value": "8.74" }, { "name": "DISPLAY_WIDTH", "value": "5.74" } ] }, { "choiceId": "1453753163807", "choiceName": "4\" x 6\"", "properties": [ { "name": "MEDIA_HEIGHT", "value": "6.25" }, { "name": "MEDIA_WIDTH", "value": "4.25" }, { "name": "DISPLAY_HEIGHT", "value": "6" }, { "name": "DISPLAY_WIDTH", "value": "4" } ] } ] } } ] }';
        $this->catalogDocumentRefranceApi->method('documentLifeExtendApiCallWithDocumentId')->willReturn($response);
        $result = $this->fxocmHelper->migrateWorkspaceId($workSpaceData);
        $this->assertNotNull($result);
    }

    /**
     * Test Get toggle value for allowFileUpload B-2293456
     * @param boolean
     */
    public function testGetAllowFileUploadCatalogFlow() {
        $this->dataHelperMock
            ->method('getAllowFileUploadCatalogFlow')
            ->willReturn(true);
        $this->assertTrue($this->fxocmHelper->getAllowFileUploadCatalogFlow());
    }

     /**
     * Test Get Toggle to remove legacy document from workspace B-2353482
     * @param boolean
     */
    public function testIsRemoveLegacyDocumentToggle() {
        $this->dataHelperMock
            ->method('removeLegacyDocumentFromWorkspace')
            ->willReturn(true);
        $this->assertTrue($this->fxocmHelper->isRemoveLegacyDocumentToggle());
    }

    /**
     * Test Method to remove legacy document from workspace
     *
     * @return  void
     */
    public function testRemoveLegacyDocFromWorkspace()
    {
        $inputJson = json_encode([
            'files' => [
                ['id' => 'abc123', 'name' => 'doc1.pdf'],
                ['id' => 123, 'name' => 'doc2.pdf']
            ],
            'projects' => [
                [
                    'associatedDocuments' => [
                        ['id' => 'xyz456', 'name' => 'doc3.pdf'],
                        ['id' => 456, 'name' => 'doc4.pdf']
                    ]
                ]
            ]
        ]);

        $expectedOutput = json_encode([
            'files' => [
                ['id' => 'abc123', 'name' => 'doc1.pdf']
            ],
            'projects' => [
                [
                    'associatedDocuments' => [
                        ['id' => 'xyz456', 'name' => 'doc3.pdf']
                    ]
                ]
            ]
        ], JSON_PRETTY_PRINT);

        $result = $this->fxocmHelper->removeLegacyDocFromWorkspace($inputJson);

        $this->assertJsonStringEqualsJsonString($expectedOutput, $result);
    }
}
