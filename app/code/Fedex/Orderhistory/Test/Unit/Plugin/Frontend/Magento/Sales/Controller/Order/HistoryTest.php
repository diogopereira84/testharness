<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Orderhistory\Test\Unit\Plugin\Frontend\Magento\Sales\Controller\Order;

use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Orderhistory\Plugin\Frontend\Magento\Sales\Controller\Order\History;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Sales\Controller\Order\History as BaseHistory;
use Fedex\Ondemand\Model\Config as OndemandConfig;

/**
 * Test class for Fedex\Orderhistory\Plugin\Frontend\Magento\Sales\Controller\Order
 */
class HistoryTest extends TestCase
{
    protected $resultMock;
    protected $pageConfig;
    protected $pageTitle;
    protected $toggleConfigMock;
    protected $layoutMock;
    protected $blockMock;
    protected $subjectMock;
    protected $ondemandConfigMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $historyController;
    /**
     * Set up unit tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resultMock = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getConfig', 'getLayout'])
            ->getMockForAbstractClass();

        $this->pageConfig = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getTitle'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageTitle = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['set'])
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getToggleConfigValue'])
            ->getMock();

        $this->layoutMock = $this->getMockBuilder(LayoutInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBlock'])
            ->getMockForAbstractClass();

        $this->blockMock = $this->getMockBuilder(BlockInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['setPageTitle'])
            ->getMockForAbstractClass();

        $this->subjectMock = $this->getMockBuilder(BaseHistory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->ondemandConfigMock = $this->getMockBuilder(OndemandConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMyAccountTabNameValue', 'getOrdersTabNameValue'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->historyController = $this->objectManager->getObject(
            History::class,
            [
                'toggleConfig' => $this->toggleConfigMock,
                'config' => $this->ondemandConfigMock
            ]
        );
    }

    /**
     * Test afterExecute function
     */
    public function testAfterExecute()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->resultMock->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->any())
            ->method('getTitle')
            ->willReturn($this->pageTitle);
        $this->pageTitle->expects($this->any())
            ->method('set')
            ->willReturn('My Account | FedEx Office');
        $this->resultMock->expects($this->any())
            ->method('getLayout')
            ->willReturn($this->layoutMock);
        $this->layoutMock->expects($this->any())
            ->method('getBlock')
            ->willReturn($this->blockMock);
        $this->ondemandConfigMock->expects($this->any())
            ->method('getMyAccountTabNameValue')
            ->willReturn('My Account | FedEx Office');
        $this->ondemandConfigMock->expects($this->any())
            ->method('getOrdersTabNameValue')
            ->willReturn('My Orders');

        $result = $this->historyController->afterExecute($this->subjectMock, $this->resultMock);
        $this->assertNotNull($result);
    }
}
