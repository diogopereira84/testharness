<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CusomizedMegamenu\Test\Unit\Plugin\Result;

use Magento\Framework\View\Element\Context;
use Fedex\CustomizedMegamenu\Plugin\Result\Page as CustomizedPage;
use Magento\Framework\View\Result\Page;
use Magento\Framework\App\ResponseInterface;
use Fedex\CustomerDetails\Helper\Data;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\UrlInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PageTest extends \PHPUnit\Framework\TestCase
{
    protected $urlInterfaceMock;
    protected $toggleConfigMock;
    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextMock;

    /**
     * @var CustomizedPage|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $customizedPage;

    /**
     * @var Page|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $subjectMock;

    /**
     * @var ResponseInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $responseInterfaceMock;

    /**
     * @var Data|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dataHelperMock;

    /**
     * @var SdeHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $sdeHelperMock;

    /**
     * @var DeliveryDataHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $deliveryHelperMock;

    /**
     * @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $objectManager;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;
    
    // @codingStandardsIgnoreEnd
    protected function setUp(): void
    {
        $this->subjectMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['addBodyClass', 'getConfig'])
            ->getMock();
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->responseInterfaceMock = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sdeHelperMock = $this->getMockBuilder(SdeHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlInterfaceMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentUrl'])
            ->getMockForAbstractClass();
        $this->deliveryHelperMock = $this->getMockBuilder(DeliveryDataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
            
        $this->objectManager = new ObjectManager($this);
        $this->customizedPage = $this->objectManager->getObject(
            CustomizedPage::class,
            [
                'context' => $this->contextMock,
                'helperData' => $this->dataHelperMock,
                'deliveryHelper' => $this->deliveryHelperMock,
                'sdeHelper' => $this->sdeHelperMock,
                'toggleConfig' => $this->toggleConfigMock,
                'urlInterface'=> $this->urlInterfaceMock
            ]
        );
    }

    /**
     * Test before render result add body class
     *
     * @return  void
     */
    public function testBeforeRenderResult()
    {
        $this->subjectMock->expects($this->any())
            ->method('getConfig')
            ->willReturnSelf();
        $this->subjectMock->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('cms-sde-home');
        $this->subjectMock->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('megamenu-primary-menu');
        $this->subjectMock->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('megamenu-improvement-feature');
        $this->sdeHelperMock->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);
        $this->urlInterfaceMock->expects($this->any())
            ->method('getCurrentUrl')
            ->willReturn("xyz");
        $beforeRenderResult = $this->customizedPage
        ->beforeRenderResult($this->subjectMock, $this->responseInterfaceMock);

        $this->assertEquals([$this->responseInterfaceMock], $beforeRenderResult);
    }

    /**
     * Test before render result add body class
     *
     * @return  void
     */
    public function testBeforeRenderResultDisabledToggle()
    {
        $this->subjectMock->expects($this->any())
            ->method('getConfig')
            ->willReturnSelf();
        $this->subjectMock->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('cms-sde-home');
        $this->subjectMock->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('megamenu-primary-menu');
        $this->subjectMock->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('megamenu-improvement-feature');
        $this->sdeHelperMock->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);
        $this->urlInterfaceMock->expects($this->any())
            ->method('getCurrentUrl')
            ->willReturn("xyz");
        $beforeRenderResult = $this->customizedPage
        ->beforeRenderResult($this->subjectMock, $this->responseInterfaceMock);

        $this->assertEquals([$this->responseInterfaceMock], $beforeRenderResult);
    }

    /**
     * Test before render result add body class
     *
     * @return  void
     */
    public function testBeforeRenderResultEnabledDownloadToggle()
    {
        $this->subjectMock->expects($this->any())
            ->method('getConfig')
            ->willReturnSelf();
        $this->subjectMock->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('cms-sde-home');
        $this->subjectMock->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('megamenu-primary-menu');
        $this->subjectMock->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('megamenu-improvement-feature', 'catalog_mvp_custom_docs');
        $this->sdeHelperMock->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);
        $this->urlInterfaceMock->expects($this->any())
            ->method('getCurrentUrl')
            ->willReturn("xyz");
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
            ->withConsecutive(
                ['change_customer_roles_and_permissions']
            )
            ->willReturnOnConsecutiveCalls(true, true);
        $beforeRenderResult = $this->customizedPage
        ->beforeRenderResult($this->subjectMock, $this->responseInterfaceMock);

        $this->assertEquals([$this->responseInterfaceMock], $beforeRenderResult);
    }

    /**
     * Test before render result add body class for Sde Not logged In Case
     *
     * @return  void
     */
    public function testBeforeRenderResultNotLoggedInSde()
    {
        $this->subjectMock->expects($this->any())
            ->method('getConfig')
            ->willReturnSelf();
        $this->subjectMock->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('cms-sde-home');
        $this->subjectMock->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('megamenu-primary-menu');
        $this->subjectMock->expects($this->any())
            ->method('addBodyClass')
            ->willReturn('megamenu-improvement-feature');
        $this->sdeHelperMock->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(false);
        $this->urlInterfaceMock->expects($this->any())
            ->method('getCurrentUrl')
            ->willReturn("xyz");
        $this->deliveryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())
            ->method('isCustomerEproAdminUser')
            ->willReturn(true);
        $beforeRenderResult = $this->customizedPage
        ->beforeRenderResult($this->subjectMock, $this->responseInterfaceMock);

        $this->assertEquals([$this->responseInterfaceMock], $beforeRenderResult);
    }
}