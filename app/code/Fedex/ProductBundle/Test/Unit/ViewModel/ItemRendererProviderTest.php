<?php

declare(strict_types=1);

namespace Fedex\ProductBundle\Test\Unit\ViewModel;

use Fedex\ProductBundle\ViewModel\ItemRendererProvider;
use Fedex\Cart\ViewModel\ProductInfoHandler;
use Fedex\Orderhistory\ViewModel\OrderHistoryEnhacement;
use Fedex\UploadToQuote\ViewModel\QuoteDetailsViewModel;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Element\Template;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\TestCase;

class ItemRendererProviderTest extends TestCase
{
    private $layout;
    private $orderHistoryEnhacement;
    private $productInfoHandler;
    private $quoteDetailsViewModel;
    private $uploadToQuoteViewModel;
    private $provider;

    protected function setUp(): void
    {
        $this->layout = $this->createMock(LayoutInterface::class);
        $this->orderHistoryEnhacement = $this->createMock(OrderHistoryEnhacement::class);
        $this->productInfoHandler = $this->createMock(ProductInfoHandler::class);
        $this->quoteDetailsViewModel = $this->createMock(QuoteDetailsViewModel::class);
        $this->uploadToQuoteViewModel = $this->createMock(UploadToQuoteViewModel::class);
        $this->provider = new ItemRendererProvider(
            $this->layout,
            $this->orderHistoryEnhacement,
            $this->productInfoHandler,
            $this->quoteDetailsViewModel,
            $this->uploadToQuoteViewModel
        );
    }

    public function testGetRendererForItemReturnsRenderer()
    {
        $item = $this->createMock(QuoteItem::class);
        $item->method('getProductType')->willReturn('simple');
        $rendererList = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->addMethods(['getRenderer'])
            ->getMock();
        $templateBlock = $this->createMock(\Magento\Framework\View\Element\Template::class);
        $rendererList->expects($this->once())
            ->method('getRenderer')
            ->with('simple', 'default', 'Fedex_ProductBundle::cart/item/default.phtml')
            ->willReturn($templateBlock);
        $this->layout->method('getBlock')->willReturn($rendererList);
        $result = $this->provider->getRendererForItem($item);
        $this->assertSame($templateBlock, $result);
        $this->assertInstanceOf(\Magento\Framework\View\Element\Template::class, $result);
    }

    public function testGetRendererForItemReturnsNullIfNoRendererList()
    {
        $item = $this->createMock(QuoteItem::class);
        $item->method('getProductType')->willReturn('simple');
        $this->layout->method('getBlock')->willReturn(null);
        $result = $this->provider->getRendererForItem($item);
        $this->assertNull($result);
    }

    public function testGetItemHtmlReturnsHtml()
    {
        $item = $this->createMock(QuoteItem::class);
        $renderer = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['toHtml'])
            ->addMethods(['setItem'])
            ->getMock();
        $renderer->expects($this->once())->method('setItem')->with($item);
        $renderer->expects($this->once())->method('toHtml')->willReturn('html_output');
        $rendererList = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->addMethods(['getRenderer'])
            ->getMock();
        $rendererList->method('getRenderer')->willReturn($renderer);
        $this->layout->method('getBlock')->willReturn($rendererList);
        $item->method('getProductType')->willReturn('simple');
        $result = $this->provider->getItemHtml($item);
        $this->assertSame('html_output', $result);
    }

    public function testGetItemHtmlReturnsEmptyStringIfNoRenderer()
    {
        $item = $this->createMock(QuoteItem::class);
        $this->layout->method('getBlock')->willReturn(null);
        $item->method('getProductType')->willReturn('simple');
        $result = $this->provider->getItemHtml($item);
        $this->assertSame('', $result);
    }

    public function testGetMyOrdersChildItemHtmlReturnsHtml()
    {
        $item = $this->createMock(OrderItem::class);
        $block = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setTemplate', 'setData', 'toHtml'])
            ->addMethods(['setName', 'setArea'])
            ->getMock();
        $block->expects($this->once())->method('setTemplate')->with('Fedex_ProductBundle::order/item/reorder/default.phtml')->willReturnSelf();
        $block->expects($this->once())->method('setName')->with('fedex_myorders_children_items')->willReturnSelf();
        $block->expects($this->once())->method('setArea')->with('frontend')->willReturnSelf();
        $block->expects($this->once())->method('setData')->with(
            [
                'order_item' => $item,
                'productinfo_handler_view_model' => $this->productInfoHandler,
                'order_enhancement_view_model' => $this->orderHistoryEnhacement
            ]
        )->willReturnSelf();
        $block->expects($this->once())->method('toHtml')->willReturn('order_child_html');
        $this->layout->method('createBlock')->willReturn($block);
        $result = $this->provider->getMyOrdersChildItemHtml($item);
        $this->assertSame('order_child_html', $result);
    }

    public function testGetMyOrdersDetailsChildItemHtmlReturnsHtml()
    {
        $item = $this->createMock(OrderItem::class);
        $block = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setTemplate', 'setData', 'toHtml'])
            ->addMethods(['setName', 'setArea'])
            ->getMock();
        $block->expects($this->once())->method('setTemplate')->with('Fedex_ProductBundle::order/retail-view.phtml')->willReturnSelf();
        $block->expects($this->once())->method('setName')->with('fedex_order_details_children_items')->willReturnSelf();
        $block->expects($this->once())->method('setArea')->with('frontend')->willReturnSelf();
        $block->expects($this->once())->method('setData')->with(
            [
                'order_item' => $item,
                'productinfo_handler_view_model' => $this->productInfoHandler,
                'order_enhancement_view_model' => $this->orderHistoryEnhacement
            ]
        )->willReturnSelf();
        $block->expects($this->once())->method('toHtml')->willReturn('order_details_html');
        $this->layout->method('createBlock')->willReturn($block);
        $result = $this->provider->getMyOrdersDetailsChildItemHtml($item);
        $this->assertSame('order_details_html', $result);
    }

    public function testGetMyOrdersDetailsCommercialChildItemHtmlReturnsHtml()
    {
        $item = $this->createMock(OrderItem::class);
        $block = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setTemplate', 'setData', 'toHtml'])
            ->addMethods(['setName', 'setArea'])
            ->getMock();
        $block->expects($this->once())->method('setTemplate')->with('Fedex_ProductBundle::sales/order/items/renderer/default.phtml')->willReturnSelf();
        $block->expects($this->once())->method('setName')->with('fedex_order_details_children_items')->willReturnSelf();
        $block->expects($this->once())->method('setArea')->with('frontend')->willReturnSelf();
        $block->expects($this->once())->method('setData')->with(
            [
                'item' => $item,
                'productinfo_handler_view_model' => $this->productInfoHandler,
                'order_enhancement_view_model' => $this->orderHistoryEnhacement
            ]
        )->willReturnSelf();
        $block->expects($this->once())->method('toHtml')->willReturn('order_details_commercial_html');
        $this->layout->method('createBlock')->willReturn($block);
        $result = $this->provider->getMyOrdersDetailsCommercialChildItemHtml($item);
        $this->assertSame('order_details_commercial_html', $result);
    }

    public function testGetQuoteSuccessPageChildItemHtmlReturnsHtml()
    {
        $item = $this->createMock(QuoteItem::class);
        $block = $this->getMockBuilder(\Fedex\UploadToQuote\Block\QuoteDetails::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setTemplate', 'setData', 'toHtml'])
            ->addMethods(['setName', 'setArea'])
            ->getMock();
        $block->expects($this->once())->method('setTemplate')->with('Fedex_ProductBundle::quote/success/items/renderer/default.phtml')->willReturnSelf();
        $block->expects($this->once())->method('setName')->with('fedex_myorders_children_items')->willReturnSelf();
        $block->expects($this->once())->method('setArea')->with('frontend')->willReturnSelf();
        $block->expects($this->exactly(5))->method('setData')->withConsecutive(
            ['quote_item', $item],
            ['quoteDetailsViewModel', $this->quoteDetailsViewModel],
            ['uploadToQuoteViewModal', $this->uploadToQuoteViewModel],
            ['productInfoHandler', $this->productInfoHandler],
            ['order_enhancement_view_model', $this->orderHistoryEnhacement]
        )->willReturnSelf();
        $block->expects($this->once())->method('toHtml')->willReturn('quote_success_html');
        $this->layout->method('createBlock')->willReturn($block);
        $result = $this->provider->getQuoteSuccessPageChildItemHtml($item);
        $this->assertSame('quote_success_html', $result);
    }

    public function testGetQuoteDetailsChildItemHtmlReturnsHtml()
    {
        $item = $this->createMock(QuoteItem::class);
        $block = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setTemplate', 'setData', 'toHtml'])
            ->addMethods(['setName', 'setArea'])
            ->getMock();
        $block->expects($this->once())->method('setTemplate')->with('Fedex_ProductBundle::quote/items/renderer/default.phtml')->willReturnSelf();
        $block->expects($this->once())->method('setName')->with('fedex_myorders_children_items')->willReturnSelf();
        $block->expects($this->once())->method('setArea')->with('frontend')->willReturnSelf();
        $block->expects($this->once())->method('setData')->with(
            [
                'quote_item' => $item,
                'quoteDetailsViewModel' => $this->quoteDetailsViewModel,
                'uploadToQuoteViewModal' => $this->uploadToQuoteViewModel,
                'productInfoHandler' => $this->productInfoHandler,
                'order_enhancement_view_model' => $this->orderHistoryEnhacement
            ]
        )->willReturnSelf();
        $block->expects($this->once())->method('toHtml')->willReturn('quote_details_html');
        $this->layout->method('createBlock')->willReturn($block);
        $result = $this->provider->getQuoteDetailsChildItemHtml($item);
        $this->assertSame('quote_details_html', $result);
    }
}

