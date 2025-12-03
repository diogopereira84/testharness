<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Pod\Test\Unit\Plugin\Checkout\Model;

use Fedex\Pod\Plugin\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\Message\Manager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Checkout\Model\Cart as MagentoCart;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    protected $plugin;
    private const METHOD_SET_VALUE = 'setValue';
    private const METHOD_GET_VALUE = 'getValue';
    private const METHOD_GET_ID = 'getId';
    private const CART_QUANTITY = [ 1 => ['qty' => 2]];

    /**
     * @var MockObject|Option
     */
    private MockObject|Option $optionMock;

    /**
     * @var Item|MockObject
     */
    private Item|MockObject $quoteItemMock;

    /**
     * @var Quote|MockObject
     */
    private MockObject|Quote $quoteMock;

    /**
     * @var MockObject|MagentoCart
     */
    private MockObject|MagentoCart $cartMock;

    protected function setUp(): void
    {
        $this->optionMock = $this->getMockBuilder(Option::class)
            ->setMethods([
                self::METHOD_SET_VALUE,
                self::METHOD_GET_VALUE,
                'save',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemMock = $this->createMock(Item::class);
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartMock = $this->getMockBuilder(MagentoCart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->plugin = new Cart($this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock(), $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->getMock());
    }

    public function testBeforeUpdateItemsFxoProduct(): void
    {
        $quoteItem2Mock = $this->createMock(Item::class);
        $this->optionMock->expects($this->once())->method(self::METHOD_GET_VALUE)->willReturn(json_encode([
            'external_prod' => [
                0 => [
                    'fxo_product' => json_encode([
                        'qty' => 1,
                        'fxoProductInstance' => ['productConfig' => ['product' => ['qty' => 1]]]
                    ])
                ]
            ]
        ]));
        $this->optionMock->expects($this->once())->method(self::METHOD_SET_VALUE)
            ->willReturn($this->optionMock);
        $this->quoteItemMock->expects($this->any())->method(self::METHOD_GET_ID)->willReturn(1);
        $this->quoteItemMock->expects($this->once())->method('getOptionByCode')->willReturn($this->optionMock);
        $quoteItem2Mock->expects($this->any())->method(self::METHOD_GET_ID)->willReturn(3);
        $this->quoteMock->expects($this->any())->method('getItems')
            ->willReturn([$quoteItem2Mock, $this->quoteItemMock]);
        $this->cartMock->expects($this->once())->method('getQuote')->willReturn($this->quoteMock);

        $this->plugin->beforeUpdateItems($this->cartMock, self::CART_QUANTITY);
    }

    public function testBeforeUpdateItemsQty(): void
    {
        $this->optionMock->expects($this->once())->method(self::METHOD_GET_VALUE)->willReturn(json_encode([
            'external_prod' => [0 => ['qty' => 1]]
        ]));
        $this->optionMock->expects($this->once())->method(self::METHOD_SET_VALUE)
            ->willReturn($this->optionMock);
        $this->quoteItemMock->expects($this->any())->method(self::METHOD_GET_ID)->willReturn(1);
        $this->quoteItemMock->expects($this->once())->method('getOptionByCode')
            ->willReturn($this->optionMock);
        $this->quoteMock->expects($this->any())->method('getItems')->willReturn([$this->quoteItemMock]);
        $this->cartMock->expects($this->once())->method('getQuote')->willReturn($this->quoteMock);

        $this->plugin->beforeUpdateItems($this->cartMock, self::CART_QUANTITY);
    }
}
