<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SDE\Test\Unit\Helper;

use Exception;
use Fedex\Company\Model\CompanyData;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Catalog\Api\CategoryManagementInterface;
use Magento\Catalog\Api\Data\CategoryTreeInterface;
use Magento\Catalog\Model\Category;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\Group;
use Fedex\Base\Helper\Auth;

class SdeHelperTest extends TestCase
{
    protected $groupInterfaceMock;
    protected $storeGroupFactoryMock;
    protected $storeGroupMock;
    protected $data;
    /**
     * SDE store group
     */
    public const SDE_STORE_GROUP_ID = 31;

    /**
     * SDE store view id
     */
    public const SDE_STORE_VIEW_ID = 65;

    /**
     * set SDE cookie period
     */
    public const XML_PATH_FEDEX_SSO_ACTIVE_SESSION_TIMEOUT = 'sso/login_session/active_session_timeout';

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerInterfaceMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfigMock;

    /**
     * @var CustomerSession|MockObject
     */
    protected $customerSessionMock;

    /**
     * @var CategoryManagementInterface|MockObject
     */
    protected $categoryManagementMock;

    /**
     * @var Http|MockObject
     */
    protected $request;

    /**
     * @var Store|MockObject
     */
    protected $storeMock;

    /**
     * @var ScopeConfigInterface $scopeConfigMock
     */
    protected $scopeConfigMock;

    /**
     * @var CookieManagerInterface|MockObject
     */
    protected $cookieManagerMock;

    /**
     * @var StoreInterface|MockObject
     */
    protected $storeInterfaceMock;

    /**
     * @var CookieMetadataFactory|MockObject
     */
    protected $cookieMetadataFactoryMock;

    /**
     * @var WebsiteInterface|MockObject
     */
    protected $websiteInterfaceMock;

    /**
     * @var Store|MockObject
     */
    protected $categoryTreeMock;

    /**
     * @var CategoryTreeInterface|MockObject
     */
    protected $category;

    /**
     * @var Category|MockObject
     */
    protected $customerMock;

    /**
     * @var CompanyData|MockObject
     */
    protected $companyDataMock;

    protected Auth|MockObject $baseAuthMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerInterfaceMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore', 'getBaseUrl', 'getGroup'])
            ->getMockForAbstractClass();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->onlyMethods(
                [
                    'getValue',
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue', 'getToggleConfig'])
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn', 'logout', 'setLastCustomerId',
                'regenerateId', 'setCustomerAsLoggedIn', 'setStoreName',
                'setCustomerCompany', 'getCustomer', 'getCreatedIn', 'setCartReload', 'getOndemandCompanyInfo'])
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->categoryManagementMock = $this->getMockBuilder(CategoryManagementInterface::class)
            ->setMethods(['getTree'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->request = $this->getMockBuilder(Http::class)
            ->setMethods(['getModuleName', 'getControllerName', 'getActionName', 'getParam'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId', 'getCode', 'getName', 'getBaseUrl', 'getRootCategoryId'])
            ->getMock();

        $this->storeInterfaceMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getStoreId', 'getGroupId','getBaseUrl', 'getCode'])
            ->getMockForAbstractClass();

        $this->categoryTreeMock = $this->getMockBuilder(CategoryTreeInterface::class)
            ->setMethods(['getChildrenData','getIterator'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->category = $this->getMockBuilder(Category::class)
            ->setMethods(['getUrl','getName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerMock = $this->getMockBuilder(Customer::class)
            ->setMethods(['setWebsiteId', 'loadByEmail', 'load', 'getId', 'getStoreId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->groupInterfaceMock = $this->getMockBuilder(GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieManagerMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->setMethods(['setPublicCookie'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cookieMetadataFactoryMock = $this->getMockBuilder(CookieMetadataFactory::class)
            ->setMethods(
                [
                    'createPublicCookieMetadata',
                    'setDuration',
                    'setPath',
                    'setHttpOnly',
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyDataMock = $this->getMockBuilder(CompanyData::class)
            ->setMethods(['getStoreViewIdByCustomerGroup'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeGroupFactoryMock  = $this->getMockBuilder(GroupFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeGroupMock  = $this->getMockBuilder(Group::class)
            ->setMethods(['load', 'getGroupId', 'getStoreIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->data = $objectManagerHelper->getObject(
            SdeHelper::class,
            [
                'context' => $this->contextMock,
                'storeManager' => $this->storeManagerInterfaceMock,
                'scopeConfig' => $this->scopeConfigMock,
                'logger' => $this->loggerMock,
                'toggleConfig' => $this->toggleConfigMock,
                'customerSession' => $this->customerSessionMock,
                'categoryManagement' => $this->categoryManagementMock,
                'request' => $this->request,
                'customer' => $this->customerMock,
                'cookieManager' => $this->cookieManagerMock,
                'cookieMetadataFactory' => $this->cookieMetadataFactoryMock,
                'companyData' => $this->companyDataMock,
                'groupFactory' => $this->storeGroupFactoryMock,
                'authHelper' => $this->baseAuthMock
            ]
        );
    }

    /**
     * @test testGetIsSdeStore
     */
    public function testGetIsSdeStore()
    {
        // B-1515570
        $ondemandCompanyInfo = ['url_extension' => true, 'company_type' => 'sde'];

        $this->customerSessionMock->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn($ondemandCompanyInfo);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);

        $this->testGetStoreGroupCode();

        $this->assertEquals(true, $this->data->getIsSdeStore());
    }

    /**
     * @test testGetIsSdeStoreWithToggleOff
     */
    public function testGetIsSdeStoreWithToggleOff()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(0);

        $this->storeManagerInterfaceMock->expects($this->any())
            ->method('getGroup')
            ->willReturn($this->groupInterfaceMock);

        $this->groupInterfaceMock->expects($this->any())
            ->method('getCode')
            ->willReturn('');

        $this->assertEquals(false, $this->data->getIsSdeStore());
    }

    /**
     * @test testIsSsoAuthEnabled
     */
    public function testIsSsoAuthEnabled()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->with(SdeHelper::AUTH_ENABLED)
            ->willReturn(true);

        $this->assertEquals(true, $this->data->isSsoAuthEnabled());
    }

    /**
     * @test testIsSsoAuthEnabledWithFalse
     */
    public function testIsSsoAuthEnabledWithFalse()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->with(SdeHelper::AUTH_ENABLED)
            ->willReturn(false);

        $this->assertEquals(false, $this->data->isSsoAuthEnabled());
    }

    /**
     * @test testIsSdeSsoModuleActive
     */
    public function testIsSdeSsoModuleActive()
    {
        $this->testIsSsoAuthEnabled();
        $this->testGetIsSdeStore();

        $this->assertEquals(true, $this->data->isSdeSsoModuleActive());
    }

    /**
     * @test testIsSdeSsoModuleActiveWithFalse
     */
    public function testIsSdeSsoModuleActiveWithFalse()
    {
        $this->testIsSsoAuthEnabledWithFalse();

        $this->assertEquals(false, $this->data->isSdeSsoModuleActive());
    }

    /**
     * @test testIsCustomerSsoMethodEnabled
     */
    public function testIsCustomerSsoMethodEnabled()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->willReturn(true);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);

        $this->testGetStoreGroupCode();

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(2);

        $this->assertEquals(true, $this->data->isCustomerSsoMethodEnabled());
    }

    /**
     * @test testIsCustomerSsoMethodEnabledWithConfigFalse
     */
    public function testIsCustomerSsoMethodEnabledWithConfigFalse()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->willReturn(false);

        $this->assertEquals(false, $this->data->isCustomerSsoMethodEnabled());
    }

    /**
     * @test testIsCustomerSsoMethodEnabledWithFalse
     */
    public function testIsCustomerSsoMethodEnabledWithFalse()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->willReturn(true);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);

        $this->testGetStoreGroupCode();

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);

        $this->assertEquals(true, $this->data->isCustomerSsoMethodEnabled());
    }

    /**
     * @test testGetSsoLoginUrl
     */
    public function testGetSsoLoginUrl()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->willReturn(true);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);

        $this->testGetStoreGroupCode();

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(2);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->assertEquals(true, $this->data->getSsoLoginUrl());
    }

    /**
     * @test testGetSsoLoginUrlWithDisabledToggle
     */
    public function testGetSsoLoginUrlWithDisabledToggle()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->willReturn(false);

        $this->assertEquals(false, $this->data->getSsoLoginUrl());
    }

    /**
     * @test testIsSdeUserInNoneSdeStore
     */
    public function testIsSdeUserInNoneSdeStore()
    {
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerMock->expects($this->any())
            ->method('getStoreId')
            ->willReturnSelf(self::SDE_STORE_VIEW_ID);

        $this->storeManagerInterfaceMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeInterfaceMock);

        $this->storeInterfaceMock->expects($this->any())
            ->method('getGroupId')
            ->willReturn(self::SDE_STORE_GROUP_ID);

		$this->groupInterfaceMock->method('getCode')
               ->willReturnOnConsecutiveCalls(SdeHelper::SDE_STORE_CODE, '');

        $this->storeManagerInterfaceMock->expects($this->any())
            ->method('getGroup')
            ->willReturn($this->groupInterfaceMock);

        $this->assertEquals(true, $this->data->isSdeUserInNoneSdeStore());
    }

    /**
     * @test testIsSdeUserInNoneSdeStoreWithFalse
     */
    public function testIsSdeUserInNoneSdeStoreWithFalse()
    {
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->assertEquals(false, $this->data->isSdeUserInNoneSdeStore());
    }

    /**
     * @test testGetSdeSecureTitle
     */
    public function testGetSdeSecureTitle()
    {
        $title = 'Sensitive data workflow';
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->with(SdeHelper::FACE_MSG_TITLE)
            ->willReturn($title);

        $this->assertEquals($title, $this->data->getSdeSecureTitle());
    }

    /**
     * @test testGetSdeSecureTitleWithEmptyResponse
     */
    public function testGetSdeSecureTitleWithEmptyResponse()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->with(SdeHelper::FACE_MSG_TITLE)
            ->willReturn('');

        $this->assertEquals('', $this->data->getSdeSecureTitle());
    }

    /**
     * @test testGetSdeSecureContent
     */
    public function testGetSdeSecureContent()
    {
        $content = 'You are in a sensitive data enabled workflow.
        Your experience is enhanced to ensure your files are secure.';

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->with(SdeHelper::FACE_MSG_CONTENT)
            ->willReturn($content);

        $this->assertEquals($content, $this->data->getSdeSecureContent());
    }

    /**
     * @test testGetSdeSecureContentWithEmptyResponse
     */
    public function testGetSdeSecureContentWithEmptyResponse()
    {
        $content = '';

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->with(SdeHelper::FACE_MSG_CONTENT)
            ->willReturn($content);

        $this->assertEquals($content, $this->data->getSdeSecureContent());
    }

    /**
     * @test testIsFacingMsgEnable
     */
    public function testIsFacingMsgEnable()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->with(SdeHelper::FACE_MSG_ENABLE)
            ->willReturn(true);

        $this->assertEquals(true, $this->data->isFacingMsgEnable());
    }

    /**
     * @test testIsFacingMsgEnableWithSDESsoFalse
     */
    public function testIsFacingMsgEnableWithSDESsoFalse()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->with(SdeHelper::FACE_MSG_ENABLE)
            ->willReturn(false);

        $this->assertEquals(false, $this->data->isFacingMsgEnable());
    }

    /**
     * @test testIsFacingMsgEnableWithFalse
     */
    public function testIsFacingMsgEnableWithFalse()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->with(SdeHelper::FACE_MSG_ENABLE)
            ->willReturn(false);

        $this->assertEquals(false, $this->data->isFacingMsgEnable());
    }

    /**
     * @test testGetSdeSecureImagePath
     */
    public function testGetSdeSecureImagePath()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->with(SdeHelper::FACE_MSG_IMAGE)
            ->willReturn('image_path');

        $this->storeManagerInterfaceMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('media');

        $this->assertEquals('mediasde/image_path', $this->data->getSdeSecureImagePath());
    }

    /**
     * @test testIsProductSdeMaskEnable
     */
    public function testIsProductSdeMaskEnable()
    {
        $this->testGetIsSdeStore();

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->with(SdeHelper::PRODUCT_MASK_IMAGE_ENABLED)
            ->willReturn(true);

        $this->assertEquals(true, $this->data->isProductSdeMaskEnable());
    }

    /**
     * @test testIsProductSdeMaskEnableWithToggleOff
     */
    public function testIsProductSdeMaskEnableWithToggleOff()
    {
        $this->testGetIsSdeStore();

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->with(SdeHelper::PRODUCT_MASK_IMAGE_ENABLED)
            ->willReturn(false);

        $this->assertEquals(false, $this->data->isProductSdeMaskEnable());
    }

    /**
     * @test testIsProductSdeMaskEnableWithFalse
     */
    public function testIsProductSdeMaskEnableWithFalse()
    {
        $this->testGetIsSdeStoreWithToggleOff();

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->with(SdeHelper::PRODUCT_MASK_IMAGE_ENABLED)
            ->willReturn(2);

        $this->assertEquals(false, $this->data->isProductSdeMaskEnable());
    }

    /**
     * @test testGetSdeMaskSecureImagePath
     */
    public function testGetSdeMaskSecureImagePath()
    {
        $this->testGetIsSdeStore();

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->willReturn(true);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->willReturn(SdeHelper::PRODUCT_MASK_IMAGE_PATH);

        $this->storeManagerInterfaceMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('media');

        $this->assertEquals('mediasde/1', $this->data->getSdeMaskSecureImagePath());
    }

    /**
     * @test testGetSdeMaskSecureImagePathWithFalse
     */
    public function testGetSdeMaskSecureImagePathWithFalse()
    {
        $this->testGetIsSdeStore();

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->willReturn(false);

        $this->assertEquals(false, $this->data->getSdeMaskSecureImagePath());
    }

    /**
     * @test testGetSdeCategoryUrl
     */
    public function testGetSdeCategoryUrl()
    {
        $categoryData = '';

        $this->storeManagerInterfaceMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $storeId = $this->storeMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/sde_default/');

        $storeId = $this->storeMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(64);

        $this->storeMock->expects($this->any())
            ->method('getRootCategoryId')
            ->willReturn(1274);

        $this->testGetCategoryData();

        $this->categoryTreeMock->expects($this->any())->method('getChildrenData')
            ->willReturn(new \ArrayIterator([$this->category]));

        $this->category->expects($this->any())
            ->method('getName')
            ->willReturn('Print Product');

        $this->category->expects($this->any())
            ->method('getUrl')
            ->willReturn('https://staging3.office.fedex.com/sde_default/print-products.html');

        $this->data->getSdeCategoryUrl();
    }

    /**
     * @test testGetCategoryData
     */
    public function testGetCategoryData()
    {
        $this->categoryManagementMock->expects($this->any())
            ->method('getTree')
            ->willReturn($this->categoryTreeMock);

        $this->assertEquals($this->categoryTreeMock, $this->data->getCategoryData(1274));
    }

    /**
     * @test testGetCategoryDataWithException
     */
    public function testGetCategoryDataWithException()
    {
        $exception = new NoSuchEntityException();

        $this->categoryManagementMock->expects($this->any())
            ->method('getTree')
            ->willThrowException($exception);

        $this->data->getCategoryData(1274);
    }

    /**
     * @test testSdeCommercialCheckout
     */
    public function testSdeCommercialCheckout()
    {
        $this->testGetIsSdeStore();

        $this->request->expects($this->any())
            ->method('getModuleName')
            ->willReturn('checkout');

        $this->request->expects($this->any())
            ->method('getControllerName')
            ->willReturn('index');

        $this->request->expects($this->any())
            ->method('getActionName')
            ->willReturn('index');

        $this->assertEquals(true, $this->data->sdeCommercialCheckout());
    }

    /**
     * @test testSdeCommercialCheckoutWithFalse
     */
    public function testSdeCommercialCheckoutWithFalse()
    {
        $this->testGetIsSdeStore();

        $this->request->expects($this->any())
            ->method('getModuleName')
            ->willReturn('cms');

        $this->request->expects($this->any())
            ->method('getControllerName')
            ->willReturn('index');

        $this->request->expects($this->any())
            ->method('getActionName')
            ->willReturn('index');

        $this->assertEquals(false, $this->data->sdeCommercialCheckout());
    }

    /**
     * @test testGetDirectSignatureMessage
     */
    public function testGetDirectSignatureMessage()
    {
        $signatureMessage = 'Direct Signature is required and has been applied to all delivery methods.
        Additional fees may be applied.';

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->with(SdeHelper::XML_PATH_SDE_DIRECT_SIGNATURE_MESSAGE)
            ->willReturn($signatureMessage);

        $this->assertEquals($signatureMessage, $this->data->getDirectSignatureMessage());
    }

    /**
     * @test testGetStoreGroupCode
     */
    public function testGetStoreGroupCode()
    {
        $this->storeManagerInterfaceMock->expects($this->any())
            ->method('getGroup')
            ->willReturn($this->groupInterfaceMock);

        $this->groupInterfaceMock->expects($this->any())
            ->method('getCode')
            ->willReturn(SdeHelper::SDE_STORE_CODE);

        $this->assertEquals(SdeHelper::SDE_STORE_CODE, $this->data->getStoreGroupCode(self::SDE_STORE_GROUP_ID));
    }

    /**
     * @test testGetCustomerStoreGroupCode
     */
    public function testGetCustomerStoreGroupCode()
    {
        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerMock->expects($this->any())
            ->method('getStoreId')
            ->willReturnSelf(self::SDE_STORE_VIEW_ID);

        $this->storeManagerInterfaceMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeInterfaceMock);

        $this->storeInterfaceMock->expects($this->any())
            ->method('getGroupId')
            ->willReturn(self::SDE_STORE_GROUP_ID);

        $this->testGetStoreGroupCode();

        $this->assertEquals(SdeHelper::SDE_STORE_CODE, $this->data->getCustomerStoreGroupCode());
    }

    /**
     * @test testGetCustomerStoreGroupCodeWithException
     */
    public function testGetCustomerStoreGroupCodeWithException()
    {
        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerMock->expects($this->any())
            ->method('getStoreId')
            ->willReturnSelf(self::SDE_STORE_VIEW_ID);

        //throw exception
        $phrase = new Phrase(__('Exception message'));
        $exception = new Exception($phrase);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willThrowException($exception);

        $this->assertEquals('', $this->data->getCustomerStoreGroupCode());
    }

    /**
     * @test testGetSdeCustomerStoreUrl
     */
    public function testGetSdeCustomerStoreUrl()
    {
        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerMock->expects($this->any())
            ->method('getStoreId')
            ->willReturnSelf(self::SDE_STORE_VIEW_ID);

        $this->storeManagerInterfaceMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('baseurl');

        $this->assertNotNull($this->data->getCustomerStoreUrl(2));
    }

    /**
     * @test testGetCustomerStoreIdByCustomerGroup
     */
    public function testGetCustomerStoreIdByCustomerGroup()
    {
        $this->companyDataMock->expects($this->any())
            ->method('getStoreViewIdByCustomerGroup')
            ->willReturn(self::SDE_STORE_VIEW_ID);

        $this->assertEquals(self::SDE_STORE_VIEW_ID, $this->data->getCustomerStoreIdByCustomerGroup(2));
    }

    /**
     * @test testGetCustomerStoreIdByCustomerGroupWithStoreIdNull
     */
    public function testGetCustomerStoreIdByCustomerGroupWithStoreIdNull()
    {
        $this->companyDataMock->expects($this->any())
            ->method('getStoreViewIdByCustomerGroup')
            ->willReturn(null);

        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(self::SDE_STORE_VIEW_ID);

        $this->assertEquals(65, $this->data->getCustomerStoreIdByCustomerGroup(2));
    }


    public function testIsMarketplaceProduct()
    {
        $this->request->method('getParam')->willReturn('true');
        $this->assertEquals(true, $this->data->isMarketplaceProduct());
    }

    public function testIsMarketplaceProductWithoutParam()
    {
        $this->request->method('getParam')->willReturn(null);
        $this->assertEquals(false, $this->data->isMarketplaceProduct());
    }

    /**
     * Test Base Url.
     *
     * @return Null|String
     */
    public function testBaseUrl()
    {
        $expectedResult = 'https://staging3.office.fedex.com/default/';
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')->willReturn($expectedResult);
        $actualResult = $this->data->getBaseUrl();
        $expectedResultModf = 'https://staging3.office.fedex.com/default/';
        $this->assertEquals($expectedResultModf, $actualResult);
    }
}
