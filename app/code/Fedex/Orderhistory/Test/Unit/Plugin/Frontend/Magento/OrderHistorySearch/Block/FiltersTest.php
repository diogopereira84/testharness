<?php

namespace Fedex\Orderhistory\Test\Unit\Frontend\Magento\OrderHistorySearch\Block;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\ViewModel\OrderHistoryEnhacement;
use Magento\OrderHistorySearch\Block\Filters as PluginFilter;
use Fedex\Orderhistory\Plugin\Frontend\Magento\OrderHistorySearch\Block\Filters;

class FiltersTest extends \PHPUnit\Framework\TestCase
{
    protected $pluginFilterMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $filters;
    /**
     * @var OrderHistoryEnhacement
     */
    protected $orderHistoryEnhacement;

    /**
     * Is called before running a test
     */
    protected function setUp(): void
    {
        $this->orderHistoryEnhacement = $this->getMockBuilder(OrderHistoryEnhacement::class)
            ->setMethods(['isModuleEnabled', 'isRetailOrderHistoryEnabled', 'isSdeStore'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->pluginFilterMock = $this->getMockBuilder(PluginFilter::class)
            ->setMethods(['setTemplate', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->filters = $this->objectManager->getObject(
            Filters::class,
            [
                'orderHistoryEnhacement' => $this->orderHistoryEnhacement
            ]
        );
    }

    /**
     * testBeforeToHtml
     */
    public function testBeforeToHtml()
    {
        $this->orderHistoryEnhacement->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->orderHistoryEnhacement->expects($this->any())->method('isSdeStore')->willReturn(true);
        $this->orderHistoryEnhacement->expects($this->any())->method('isRetailOrderHistoryEnabled')->willReturn(true);
        $this->pluginFilterMock->expects($this->any())->method('setTemplate')->willReturnSelf();
        $this->pluginFilterMock->expects($this->any())->method('setData')
            ->with('order_enhancement_view_model', $this->orderHistoryEnhacement)
            ->willReturnSelf();
        $this->assertEquals(null, $this->filters->beforeToHtml($this->pluginFilterMock));
    }
}
