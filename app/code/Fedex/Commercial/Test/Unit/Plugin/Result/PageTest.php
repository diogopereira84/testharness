<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\Commercial\Test\Unit\Plugin\Result;

use Fedex\Commercial\Plugin\Result\Page;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Result\Page as subject;
use Magento\Framework\App\ResponseInterface;
use Fedex\CustomerDetails\Helper\Data;
use Fedex\CustomizedMegamenu\Helper\Data as DataHelper;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\Commercial\Helper\CommercialHelper;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;

/**
 * Test class for SdeSsoConfiguration
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class PageTest extends TestCase
{

    protected $selfregHelper;
    protected $sdeHelper;
    protected $dataHelper;
    protected $commercialHelper;
    /**
     * @var (\Fedex\CustomerDetails\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $helperData;
    protected $subject;
    protected $responseInterfaceMock;
    protected $config;
    protected $request;
    protected $catalogMvpHelperMock;
    protected $deliveryHelperMock;
    protected $uploadToQuoteViewModelMock;
    protected $orderApprovalViewModel;
    protected $fuseBidViewModel;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $page;
    /**
     * Test setUp
     */
    protected function setUp(): void
    {

        $this->selfregHelper = $this->getMockBuilder(SelfReg::class)
            ->setMethods(['isSelfRegCompany', 'isSelfRegCustomer', 'isSelfRegCustomerAdmin', 'isSelfRegCustomerWithFclEnabled'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(['getIsSdeStore', 'getLogoutUrl', 'getIsRequestFromSdeStoreFclLogin'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->commercialHelper = $this->getMockBuilder(CommercialHelper::class)
            ->setMethods(['isGlobalCommercialCustomer', 'isCommercialReorderEnable'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->helperData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->subject = $this->getMockBuilder(subject::class)
            ->setMethods(['getConfig'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->responseInterfaceMock = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->config = $this->getMockBuilder(Magento\Framework\View\Page\Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['addBodyClass'])
            ->getMock();

        $this->request = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->setMethods(['getFullActionName', 'getModuleName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->setMethods(['isMvpSharedCatalogEnable', 'checkPrintCategory','isSharedCatalogPermissionEnabled','isMvpCatalogEnabledForCompany'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryHelperMock = $this->getMockBuilder(DeliveryHelper::class)
            ->setMethods(['isCommercialCustomer', 'isCustomerAdminUser','getToggleConfigurationValue','checkPermission', 'isEproCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->uploadToQuoteViewModelMock = $this->getMockBuilder(UploadToQuoteViewModel::class)
            ->setMethods(['isUploadToQuoteEnable'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->orderApprovalViewModel = $this->getMockBuilder(OrderApprovalViewModel::class)
            ->setMethods(['isOrderApprovalB2bEnabled'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->fuseBidViewModel = $this->getMockBuilder(FuseBidViewModel::class)
            ->setMethods(['isFuseBidToggleEnabled'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->page = $this->objectManager->getObject(
            page::class,
            [
                'helperData' => $this->helperData,
                'deliveryHelper' => $this->deliveryHelperMock,
                'sdeHelper' => $this->sdeHelper,
                'commercialHelper' => $this->commercialHelper,
                'selfRegHelper' => $this->selfregHelper,
                'request' => $this->request,
                'catalogMvp' => $this->catalogMvpHelperMock,
                'uploadToQuoteViewModel' => $this->uploadToQuoteViewModelMock,
                'orderApprovalViewModel' => $this->orderApprovalViewModel,
                'fuseBidViewModel' => $this->fuseBidViewModel
            ]
        );
    }

    /**
     *  test case for afterGetFormattedAddress
     */
    public function testBeforeRenderResult()
    {
        $this->commercialHelper->expects($this->any())
            ->method('isGlobalCommercialCustomer')
            ->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())
            ->method('isCustomerAdminUser')
            ->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpCatalogEnabledForCompany')
            ->willReturn(true);
        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(false);
        $this->subject->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->config);
        $this->config->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('commercial-epro-store');
        $exectedResult = $this->page->beforeRenderResult($this->subject, $this->responseInterfaceMock);
        $this->assertIsArray($exectedResult);
    }

    /**
     *  test case for afterGetFormattedAddress
     */
    public function testBeforeRenderResultNew()
    {
        $this->commercialHelper->expects($this->any())
            ->method('isGlobalCommercialCustomer')
            ->willReturn(true);
        $this->subject->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->config);
        $this->config->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('commercial-store-home');
        $this->uploadToQuoteViewModelMock->expects($this->once())
            ->method('isUploadToQuoteEnable')
            ->willReturn('upload-to-quote');
        $this->orderApprovalViewModel->expects($this->any())
            ->method('isOrderApprovalB2bEnabled')
            ->willReturn('commercial-order-approval');
        $this->fuseBidViewModel->expects($this->any())
            ->method('isFuseBidToggleEnabled')
            ->willReturn('fuse-bidding-quote');
        $exectedResult = $this->page->beforeRenderResult($this->subject, $this->responseInterfaceMock);

        $this->assertIsArray($exectedResult);
    }

    /**
     *  test case for afterGetFormattedAddress
     */
    public function testBeforeRenderResultNew2()
    {
        $this->commercialHelper->expects($this->any())
            ->method('isCommercialReorderEnable')
            ->willReturn(true);
        $this->subject->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->config);
        $this->config->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('epro-order-history-reorder');
        $exectedResult = $this->page->beforeRenderResult($this->subject, $this->responseInterfaceMock);
        $this->assertIsArray($exectedResult);
    }
    /**
     *  test case for afterGetFormattedAddress
     */
    public function testBeforeRenderResultNew3()
    {
        $this->selfregHelper->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(true);
        $this->selfregHelper->expects($this->any())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(true);
        $this->request->expects($this->any())->method('getFullActionName')->willReturn('company_users_index');
        $this->request->expects($this->any())->method('getModuleName')->willReturn('company');
        $this->subject->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->config);
        $this->config->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('epro-company-user');
        $this->deliveryHelperMock->expects($this->any())
            ->method('getToggleConfigurationValue')
            ->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())
            ->method('checkPermission')
            ->willReturn(true);
        $exectedResult = $this->page->beforeRenderResult($this->subject, $this->responseInterfaceMock);
        $this->assertIsArray($exectedResult);
    }

    /**
     *  test case for afterGetFormattedAddress
     */
    public function testBeforeRenderResultSefRegStore()
    {
        $this->selfregHelper->expects($this->any())
            ->method('isSelfRegCompany')
            ->willReturn(true);
        $this->selfregHelper->expects($this->any())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(true);
        $this->selfregHelper->expects($this->any())
            ->method('isSelfRegCustomerWithFclEnabled')
            ->willReturn(false);
        $this->sdeHelper->expects($this->any())
            ->method('getIsRequestFromSdeStoreFclLogin')
            ->willReturn(false);
        $this->subject->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->config);
        $this->config->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('selfreg-store');
        $exectedResult = $this->page->beforeRenderResult($this->subject, $this->responseInterfaceMock);
        $this->assertIsArray($exectedResult);
    }

    /**
     *  test case for afterGetFormattedAddress
     */
    public function testBeforeRenderResultSefRegStoreWithCommercialEnhancedToggleOn()
    {
        $this->selfregHelper->expects($this->any())
            ->method('isSelfRegCompany')
            ->willReturn(true);
        $this->selfregHelper->expects($this->any())
            ->method('isSelfRegCustomerWithFclEnabled')
            ->willReturn(true);
        $this->sdeHelper->expects($this->any())
            ->method('getIsRequestFromSdeStoreFclLogin')
            ->willReturn(true);
        $this->subject->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->config);
        $this->config->expects($this->atMost(5))
            ->method('addBodyClass')
            ->willReturnCallback(
                function (string $class) {
                    $selfRegStoreFclClass = 'selfreg-store-fcl';
                    $selfRegStoreClass = 'selfreg-store';
                    if ($class == $selfRegStoreClass) {
                        $this->assertEquals($selfRegStoreClass, $class);
                    }
                    if ($class == $selfRegStoreFclClass) {
                        $this->assertEquals($selfRegStoreFclClass, $class);
                    }
                }
            );
        $exectedResult = $this->page->beforeRenderResult($this->subject, $this->responseInterfaceMock);
        $this->assertIsArray($exectedResult);
    }

    /**
     *  test case for testBeforeRenderResultForMvpClass
     * 	B-1569415
     */
    public function testBeforeRenderResultForMvpClass()
    {
        $this->deliveryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);
        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(false);
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpSharedCatalogEnable')
            ->willReturn(true);
        $this->subject->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->config);
        $this->config->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('catalog-mvp-customer-admin');
        $exectedResult = $this->page->beforeRenderResult($this->subject, $this->responseInterfaceMock);
        $this->assertIsArray($exectedResult);
    }

     /**
     * Test for catalog mvp shared catalog only
     */
    public function testBeforeRenderResultForCategoryView()
    {
        $this->deliveryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);
        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(false);
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpSharedCatalogEnable')
            ->willReturn(true);
        $this->request->expects($this->any())->method('getFullActionName')->willReturn('catalog_category_view');
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('checkPrintCategory')
            ->willReturn(false);
        $this->subject->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->config);
        $this->config->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('catalog-mvp-shared-catalog');
        $exectedResult = $this->page->beforeRenderResult($this->subject, $this->responseInterfaceMock);
        $this->assertIsArray($exectedResult);
    }

    /**
     * Test for catalog Pagination show
     */
    public function testBeforeRenderResultForCategoryViewPagination()
    {
        $this->deliveryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);
        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(false);
        $this->deliveryHelperMock->expects($this->any())
            ->method('getToggleConfigurationValue')
            ->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())
            ->method('isEproCustomer')
            ->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())
            ->method('getToggleConfigurationValue')
            ->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpSharedCatalogEnable')
            ->willReturn(false);
        $this->request->expects($this->any())->method('getFullActionName')->willReturn('catalog_category_view');
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('checkPrintCategory')
            ->willReturn(false);
        $this->subject->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->config);
        $this->config->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('catalog-mvp-shared-catalog');
        $exectedResult = $this->page->beforeRenderResult($this->subject, $this->responseInterfaceMock);
        $this->assertIsArray($exectedResult);
    }

     /**
     * Test for catalog print product
     */
    public function testBeforeRenderResultForCategoryViewPrintProduct()
    {
        $this->deliveryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);
        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(false);
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpSharedCatalogEnable')
            ->willReturn(true);
        $this->request->expects($this->any())->method('getFullActionName')->willReturn('catalog_category_view');
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('checkPrintCategory')
            ->willReturn(true);
        $this->config->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('categorypath-b2b-print-products');
        $this->config->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('category-b2b-print-products');
        $this->subject->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->config);
        $this->config->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('catalog-mvp-shared-catalog');
        $exectedResult = $this->page->beforeRenderResult($this->subject, $this->responseInterfaceMock);
        $this->assertIsArray($exectedResult);
    }

    /**
     * Test for catalog mvp customer admin
     */
    public function testBeforeRenderResultForCustomerAdmin()
    {
        $this->deliveryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);
        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(false);
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpSharedCatalogEnable')
            ->willReturn(true);
        $this->selfregHelper->expects($this->any())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isSharedCatalogPermissionEnabled')
            ->willReturn(true);
        $this->subject->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->config);
        $this->config->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('catalog-mvp-customer-admin');
        $exectedResult = $this->page->beforeRenderResult($this->subject, $this->responseInterfaceMock);
        $this->assertIsArray($exectedResult);
    }
}
