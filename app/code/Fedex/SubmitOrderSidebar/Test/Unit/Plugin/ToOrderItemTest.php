<?php

namespace Fedex\SubmitOrderSidebar\Test\Unit\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote\Item\ToOrderItem as QuoteToOrderItem;
use Fedex\SubmitOrderSidebar\Plugin\ToOrderItem as ToOrderItemPlugin;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\ItemFactory;
use PHPUnit\Framework\TestCase;

class ToOrderItemTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var ToOrderItemPlugin
     */
    protected $toOrderItemPlugin;

    /**
     * @var CatalogMvp|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $catalogMvpHelperMock;

    /**
     * @var QuoteToOrderItem|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $quoteToOrderItemMock;

    /**
     * @var Item|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $quoteItemMock;

    /**
     * @var OrderItemFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $orderItemFactoryMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteToOrderItemMock = $this->getMockBuilder(QuoteToOrderItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteItemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderItemFactoryMock = $this->getMockBuilder(ItemFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toOrderItemPlugin = $this->objectManager->getObject(
            ToOrderItemPlugin::class,
            [
                'catalogMvpHelper' => $this->catalogMvpHelperMock,
            ]
        );
    }

    public function testAroundConvert()
    {
        // Mock customDocumentToggle return value
        $this->catalogMvpHelperMock->expects($this->once())
            ->method('customDocumentToggle')
            ->willReturn(true);

        // Mock Quote Item's additional Options
        $additionalOptionsMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item\Option::class)
            ->disableOriginalConstructor()
            ->getMock();

        $additionalOptionsMock->expects($this->any())
            ->method('getValue')
            ->willReturn('{"option1": "value1", "option2": "value2"}');

        $this->quoteItemMock->expects($this->once())
            ->method('getOptionByCode')
            ->with('customize_fields')
            ->willReturn($additionalOptionsMock);

        // Mock Order Item
        $orderItemMock = $this->getMockBuilder(OrderItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteToOrderItemMock->expects($this->once())
            ->method('convert')
            ->willReturn($orderItemMock);

        // Execute the method under test
        $result = $this->toOrderItemPlugin->aroundConvert(
            $this->quoteToOrderItemMock,
            function ($item, $data) {
                return $this->quoteToOrderItemMock->convert($item, $data);
            },
            $this->quoteItemMock
        );

        // Assertions
        $this->assertInstanceOf(OrderItem::class, $result);
        $this->assertEquals(null, $result->getProductOptions());
    }
}
