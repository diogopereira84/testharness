<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Orderhistory\Test\Unit;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\ViewModel\OrderHistoryEnhacement;
use Magento\Sales\Block\Order\Info\Buttons as BlockHistory;
use Fedex\Orderhistory\Plugin\Frontend\Magento\Sales\Block\Order\Info\Buttons;

class ButtonsTest extends \PHPUnit\Framework\TestCase
{
    protected $blockHistoryMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $buttons;
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
            ->setMethods(['isModuleEnabled','isPrintReceiptRetail'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->blockHistoryMock = $this->getMockBuilder(BlockHistory::class)
            ->setMethods(['setTemplate', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->buttons = $this->objectManager->getObject(
            Buttons::class,
            [
                'orderHistoryEnhacement' => $this->orderHistoryEnhacement
            ]
        );
    }

    /**
     * The test itself, every test function must start with 'test'
     */
    public function testBeforeToHtml()
    {
        $this->orderHistoryEnhacement->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->orderHistoryEnhacement->expects($this->any())->method('isPrintReceiptRetail')->willReturn(true);
        $this->blockHistoryMock->expects($this->any())->method('setTemplate')->willReturnSelf();
        $this->blockHistoryMock->expects($this->any())->method('setData')
            ->with('order_enhancement_view_model', $this->orderHistoryEnhacement)
            ->willReturnSelf();
        $this->assertEquals(null, $this->buttons->beforeToHtml($this->blockHistoryMock));
    }
}
