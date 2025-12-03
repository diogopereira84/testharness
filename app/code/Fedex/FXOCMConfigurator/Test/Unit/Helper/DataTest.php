<?php

namespace Fedex\FXOCMConfigurator\Test\Unit\Helper;

use Fedex\FXOCMConfigurator\Helper\Data;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Response\RedirectInterface;
use Fedex\SelfReg\Block\Landing;
use Magento\Framework\App\Request\Http;

class DataTest extends TestCase
{
    protected $redirectInterface;
    protected $landingMock;
    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var MockObject|ToggleConfig
     */
    protected $toggleConfigMock;

    /**
     * @var MockObject|ScopeConfigInterface
     */
    protected $scopeConfigMock;

    protected $stateMock;

    protected $redirect;

    protected $landing;

    /**
     * @var MockObject|EncryptorInterface
     */
    protected $encryptorMock;

    /**
     * @var Http|MockObject
     */
    protected $request;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->encryptorMock = $this->getMockBuilder(EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['decrypt'])
            ->getMockForAbstractClass();

        $this->stateMock = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAreaCode'])
            ->getMock();

        $this->redirectInterface = $this->getMockBuilder(RedirectInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
              'getRefererUrl',
            ])
            ->getMockForAbstractClass();

        $this->landingMock = $this->getMockBuilder(Landing::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLogoSrc'])
            ->getMock();

        $this->request = $this->getMockBuilder(Http::class)
            ->setMethods(['getModuleName'])
            ->disableOriginalConstructor()
            ->getMock();

        $context = $objectManager->getObject(Context::class);

        $this->dataHelper = $objectManager->getObject(
            Data::class,
            [
                'context' => $context,
                'toggleConfig' => $this->toggleConfigMock,
                'scopeConfig' => $this->scopeConfigMock,
                'encryptorInterface' => $this->encryptorMock,
                'state' => $this->stateMock,
                'redirect' => $this->redirectInterface,
                'landing' => $this->landingMock,
                'request' => $this->request
            ]
        );
    }

    /**
     * Test the getFxoCMToggle method with a true value.
     */
    public function testGetFxoCMToggleTrue()
    {
        $this->redirectInterface->expects($this->any())->method('getRefererUrl')
        ->willReturn('https://www.test.com/configurator/index/index/');
        
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::FXO_CM_TOGGLE)
            ->willReturn(true);

        $result = $this->dataHelper->getFxoCMToggle();
        $this->assertTrue($result);
    }

    /**
     * Test the getFxoCMToggle method with a false value.
     */
    public function testGetFxoCMToggleFalse()
    {
        $this->redirectInterface->expects($this->any())->method('getRefererUrl')
        ->willReturn('https://www.test.com/iframe/index/index/');
        
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::FXO_CM_TOGGLE)
            ->willReturn(false);

        $result = $this->dataHelper->getFxoCMToggle();
        $this->assertFalse($result);
    }

    /**
     * Test case to get the SDK url from Store config.
     */
    public function testGetFxoCMSdkUrl () {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn('fxocmsdkurl.js');

        $this->assertEquals($this->dataHelper->getFxoCMSdkUrl(),false);
    }

    /**
     * Test case to get the skuonly product id from Store config.
     */
    public function testGetSkuOnlyProductId() {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn('123456678');

        $this->assertEquals($this->dataHelper->getSkuOnlyProductId(),false);
    }

    public function testGetFxoCMBaseUrl()
    {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn('fxocmbaseurl.js');
        
        $this->assertEquals($this->dataHelper->getFxoCMBaseUrl(),false);
    }

    public function testGetFxoCMClientId()
    {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn('encryptedClientId');

        $this->assertEquals($this->dataHelper->getFxoCMClientId(),false);
    }

    public function testCheckAreaCodeString() {

        $this->stateMock
            ->expects($this->any())
            ->method('getAreaCode')
            ->willReturn("frontend");

        $this->assertEquals('frontend', $this->dataHelper->checkAreaCode());
    }

    /**
     * Test the getBatchUploadToggle method with a true value.
     */
    public function testgetBatchUploadToggleTrue()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::BATCH_UPLOAD_TOGGLE)
            ->willReturn(true);

        $result = $this->dataHelper->getBatchUploadToggle();
        $this->assertTrue($result);
    }

    /**
     * Test the getNewDocumentsApiImagePreviewToggle method with a true value.
     */
    public function testgetNewDocumentsApiImagePreviewToggleTrue()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::NEW_DOCUMENTS_API_IMAGE_PREVIEW)
            ->willReturn(true);

        $result = $this->dataHelper->getNewDocumentsApiImagePreviewToggle();
        $this->assertTrue($result);
    }

    /**
     * Test the isNonStandardCatalogToggleEnabled method with a true value.
     */
    public function testIsNonStandardCatalogToggleEnabled()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('explorers_non_standard_catalog')
            ->willReturn(true);

        $result = $this->dataHelper->isNonStandardCatalogToggleEnabled();
        $this->assertTrue($result);
    }

     /**
     * Test the isNonStandardCatalogToggleEnabled method with a true value.
     */
    public function testIsNonStandardCatalogToggleEnabledWithFalse()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('explorers_non_standard_catalog')
            ->willReturn(false);

        $result = $this->dataHelper->isNonStandardCatalogToggleEnabled();
        $this->assertFalse($result);
    }

    /**
     * Test the isNonStandardCatalogToggleEnabled method with a true value.
     */
    public function testIsCompanySettingNonStandardCatalogToggleEnabled()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('explorers_company_setting_non_standard_catalog')
            ->willReturn(true);

        $result = $this->dataHelper->isCompanySettingNonStandardCatalogToggleEnabled();
        $this->assertTrue($result);
    }

    /**
     * Test the isNonStandardCatalogToggleEnabled method with a false value.
     */
    public function testIsCompanySettingNonStandardCatalogToggleEnabledWithFalse()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('explorers_company_setting_non_standard_catalog')
            ->willReturn(false);

        $result = $this->dataHelper->isCompanySettingNonStandardCatalogToggleEnabled();
        $this->assertFalse($result);
    }

    /**
     * Test the isNscUserFlow method with a true value.
     */
    public function testIsNscUserFlow()
    {
        $this->request->expects($this->any())
            ->method('getModuleName')
            ->willReturn('catalogmvp');

        $this->assertEquals(true, $this->dataHelper->isNscUserFlow());
    }

    /**
     * Test the isNscUserFlow method with a false value.
     */
    public function testIsNscUserFlowFalse()
    {
        $this->request->expects($this->any())
            ->method('getModuleName')
            ->willReturn('test');

        $this->assertEquals(false, $this->dataHelper->isNscUserFlow());
    }

    /**
    * Test case for redirect URL
    *
    */
    public function testgetRedirectUrl() {
        $this->redirectInterface->expects($this->any())->method('getRefererUrl')
        ->willReturn('www.test.com');
        $result = $this->dataHelper->getRedirectUrl();
        $this->assertEquals('www.test.com',$result);
    }

    /**
     * Test case for FXOCM integration Type
     */
    public function testGetIntegrationType()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::INTEGRATION_TYPE)
            ->willReturn('IFRAME');

        $result = $this->dataHelper->getIntegrationType();
        $this->assertEquals('IFRAME',$result);
    }

    /**
     * Test the  Get FXO CM Footer Content
     */
    public function testgetFooterContent()
    {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn('footer content');
        $this->assertEquals($this->dataHelper->getFooterContent(),false);
    }

    /**
     * Test Get Logo Url
     */
    public function testgetLogoUrl()
    {
        $this->landingMock
            ->expects($this->any())
            ->method('getLogoSrc')
            ->willReturn('test.jpg');
        $this->assertEquals($this->dataHelper->getLogoUrl(),'test.jpg');
    }


    /**
     * Test
     */
    public function testgetFooterText()
    {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn('fxocm_footer_text');
        $this->assertEquals($this->dataHelper->getFooterText(),false);
    }

    /**
     * Test
     */
    public function testgetFooterLink()
    {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn('fxocm_footer_link');
        $this->assertEquals($this->dataHelper->getFooterLink(),false);
    }

    /**
     * Test the getBatchUploadToggle method with a true value.
     */
    public function testCheckFxoCmEproCustomDocEnabled()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::FXO_CM_EPRO_CUSTOM_DOC)
            ->willReturn(true);

        $result = $this->dataHelper->checkFxoCmEproCustomDocEnabled();
        $this->assertTrue($result);
    }

    /**
     * Test 100 Char Limit Toggle.
     */
    public function testgetCharLimitToggle()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::CHAR_LIMIT_TOGGLE)
            ->willReturn(true);

        $result = $this->dataHelper->getCharLimitToggle();
        $this->assertTrue($result);
    }

     /**
     * Test
     */
    public function testisCatalogEllipsisControlEnabled()
    {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn('enabled');
        $this->assertEquals($this->dataHelper->isCatalogEllipsisControlEnabled(),false);
    }

    /**
     * Test
     */
    public function testgetCatalogEllipsisControlTotalCharacters()
    {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn('total_characters');
        $this->assertEquals($this->dataHelper->getCatalogEllipsisControlTotalCharacters(),false);
    }


    /**
     * Test
     */
    public function testgetCatalogEllipsisControlStartCharacters()
    {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn('start_characters');
        $this->assertEquals($this->dataHelper->getCatalogEllipsisControlStartCharacters(),false);
    }

    /**
     * Test
     */
    public function testgetCatalogEllipsisControlEndCharacters()
    {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn('end_characters');
        $this->assertEquals($this->dataHelper->getCatalogEllipsisControlEndCharacters(),false);
    }

    /**
     * Test toggle value for fixed qty handler
     */
    public function testGetFixedQtyHandlerToggle() {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::FXO_CM_FIXED_QTY_HANDLE_FOR_CATALOG_MVP)
            ->willReturn(true);

        $result = $this->dataHelper->getFixedQtyHandlerToggle();
        $this->assertTrue($result);
    }

    /**
     * Test Get toggle value for allowFileUpload issue D-177591
     */
    public function testGetFixAllowFileUploadToggle() {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::TECH_TITANS_FIX_ALLOW_FILE_UPLOAD_ISSUE)
            ->willReturn(true);

        $this->assertTrue($this->dataHelper->getFixAllowFileUploadToggle());
    }

    /**
     * Test Get Toggle Value epro Custom doc for migrated Document Toggle
     */
    public function testGetEproMigratedCustomDocToggle() {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::MILLIONAIRES_EPRO_MIGRATED_CUSTOM_DOC)
            ->willReturn(true);
        $this->assertTrue($this->dataHelper->getEproMigratedCustomDocToggle());
    }

    /**
     * Test Get Epro upload to quote feature toggle enable or disable
     */
    public function testGetPrintReadyCustomDocFixToggle() {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::EXPLORERS_PRINTREADY_CUSTOM_DOC_FIX)
            ->willReturn(true);
        $this->assertTrue($this->dataHelper->getPrintReadyCustomDocFixToggle());
    }

    /**
     * Test Get Epro upload to quote feature toggle enable or disable
     */
    public function testIsEproUploadToQuoteToggleEnable() {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::EXPLORERS_EPRO_UPLOAD_TO_QUOTE)
            ->willReturn(true);
        $this->assertTrue($this->dataHelper->isEproUploadToQuoteToggleEnable());
    }

    /**
     * Test function to get converted size modal text configuration value
     */
    public function testGetConvertToSizeModalText()
    {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn('text');
        $this->assertEquals($this->dataHelper->getConvertToSizeModalText(), false);
    }
    
    /*
     * Test Get SelfReg Preview Image for cart page toggle enable or disable
     */
    public function testIsSelfRegPreviewImage() {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::MILLIONAIRES_CUSTOM_DOC_CART_PAGE_IMAGE)
            ->willReturn(true);
        $this->assertTrue($this->dataHelper->isSelfRegPreviewImage());
    }

    /*
     * Test Get Toggle to migrate document of workspace
     */
    public function testGetUserWorkSpaceNewDocumentToggle() {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::USER_WORKSPACE_NEW_DOCUMENTS_UPDATE)
            ->willReturn(true);
        $this->assertTrue($this->dataHelper->getUserWorkSpaceNewDocumentToggle());
    }


    /*
     * Test Get Commercial Cart LineItems Toggle
     */
    public function testgetCommercialCartLineItemsToggle() {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::MILLIONAIRES_COMMERCIAL_CART_PAGE_LINE_ITEMS)
            ->willReturn(true);
        $this->assertTrue($this->dataHelper->getCommercialCartLineItemsToggle());
    }

    /*
     * Test Get ePro legacy synced LineItems Toggle
     */
    public function testgetEproLegacyLineItemsToggle() {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::MILLIONAIRES_EPRO_LEGACY_LINE_ITEMS)
            ->willReturn(true);
        $this->assertTrue($this->dataHelper->getEproLegacyLineItemsToggle());
    }
    
    /**
     * Test Get toggle value for allowFileUpload issue B-2293456
     */
    public function testGetAllowFileUploadCatalogFlow() {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::EXPLORERS_ALLOW_FILE_UPLOAD_CATALOG_FLOW)
            ->willReturn(true);

        $this->assertTrue($this->dataHelper->getAllowFileUploadCatalogFlow());
    }

     /**
     * Get toggle value for remove reorderable documents(legacy document) API call from the nightly job B-2353493
     */
    public function testGetLegacyDocsNoReorderCronToggle() {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::REMOVE_LEGACY_DOCUMENT_REORDER_CRON_TOGGLE)
            ->willReturn(true);

        $this->assertTrue($this->dataHelper->getLegacyDocsNoReorderCronToggle());
    }
     
    /*
     * Test Remove legacy document from workspace
     */
    public function testRemoveLegacyDocumentFromWorkspace() {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::TECHTITANS_WORKSPACE_REMOVE_LEGACY_DOCUMENT)
            ->willReturn(true);
        $this->assertTrue($this->dataHelper->removeLegacyDocumentFromWorkspace());
    }
}
