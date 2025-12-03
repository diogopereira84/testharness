<?php

/**
 * Copyright © Fedex. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Test\Unit\Plugin\Result;

use Fedex\Delivery\Helper\Data;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SSO\Plugin\Result\Page;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Result\Page as Subject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for PageTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class PageTest extends TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $toggleConfigMock;
    /**
     * @var (\Magento\Framework\View\Page\Config & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $pageConfig;
    protected $requestMock;
    protected $responseMock;
    protected $subject;
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $page;
    /**
     * @var Context
     */
    private $context;

    /**
     * @var Data
     */
    private $deliveryHelper;

    /**
     * @var ToggleConfig
     */
    protected $toggleConfig;

    /**
     * Dependancy Initilization
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->setMethods(['getRouteName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->pageConfig = $this->getMockBuilder(Config::class)
            ->setMethods(['getPageLayout'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->responseMock = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->subject = $this->getMockBuilder(Subject::class)
            ->setMethods(['getConfig', 'getPageLayout', 'addBodyClass'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->page = $this->objectManagerHelper->getObject(
            Page::class,
            [
                'Context' => $this->contextMock,
                'deliveryHelper' => $this->deliveryHelper,
                'pageConfig' => $this->pageConfig,
                'request' => $this->requestMock,
                'toggleConfig' => $this->toggleConfigMock,
            ]
        );
    }
    /**
     * Test Before Render Result
     *
     * @return (array)
     */
    public function testBeforeRenderResult()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->requestMock->expects($this->any())->method('getRouteName')->willReturn('customer');
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(0);
        $this->subject->expects($this->any())->method('getconfig')->willReturnSelf();
        $this->subject->expects($this->any())->method('addBodyClass')->willReturnSelf();
        $this->assertIsArray($this->page->beforeRenderResult($this->subject, $this->responseMock));
    }
    /**
     * Test Before Render Result Is Not A Customer
     *
     * @return void
     */
    public function testBeforeRenderResultIsNotaCustomer()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->requestMock->expects($this->any())->method('getRouteName')->willReturn('notcustomer');
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(0);
        $this->subject->expects($this->any())->method('getconfig')->willReturnSelf();
        $this->subject->expects($this->any())->method('addBodyClass')->willReturnSelf();
        $this->assertIsArray($this->page->beforeRenderResult($this->subject, $this->responseMock));
    }
    /**
     * Test Before Render Result With Toggle Off
     *
     * @return void
     */
    public function testBeforeRenderResultwithtoggleoff()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(0);
        $this->assertEquals('', $this->page->beforeRenderResult($this->subject, $this->responseMock));
    }
}
