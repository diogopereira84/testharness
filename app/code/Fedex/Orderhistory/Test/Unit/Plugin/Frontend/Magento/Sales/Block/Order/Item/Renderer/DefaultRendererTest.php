<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Orderhistory\Test\Unit;

use Fedex\Cart\ViewModel\ProductInfoHandler;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\ViewModel\OrderHistoryEnhacement;
use Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer as DefaultRendererBlock;
use Fedex\Orderhistory\Plugin\Frontend\Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer;

class DefaultRendererTest extends \PHPUnit\Framework\TestCase
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
     * @var ProductInfoHandler
     */
    private ProductInfoHandler $productInfoHandler;

    /**
     * Is called before running a test
     */
    protected function setUp(): void
    {
        $this->orderHistoryEnhacement = $this->getMockBuilder(OrderHistoryEnhacement::class)
            ->setMethods(['isModuleEnabled', 'isEnhancementEnabeled','isMarketplaceCommercialToggleEnabled'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productInfoHandler = $this->getMockBuilder(ProductInfoHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->blockHistoryMock = $this->getMockBuilder(DefaultRendererBlock::class)
            ->setMethods(['setTemplate', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->buttons = $this->objectManager->getObject(
            DefaultRenderer::class,
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
        $this->orderHistoryEnhacement->expects($this->any())->method('isEnhancementEnabeled')->willReturn(true);
        $this->orderHistoryEnhacement->expects($this->any())->method('isMarketplaceCommercialToggleEnabled')->willReturn(true);
        $this->blockHistoryMock->expects($this->any())->method('setTemplate')->willReturnSelf();

        $matcher = $this->exactly(2);
        $this->blockHistoryMock->expects($matcher)->method('setData')
            ->willReturnCallback(function (string $param) use ($matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals($param, 'order_enhancement_view_model'),
                    2 => $this->assertEquals($param, 'productinfo_handler_view_model'),
                };
            })
            ->willReturnSelf();

        $this->assertEquals(null, $this->buttons->beforeToHtml($this->blockHistoryMock));
    }
}
