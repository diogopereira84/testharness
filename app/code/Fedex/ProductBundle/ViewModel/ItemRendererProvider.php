<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\ViewModel;

use Fedex\Cart\ViewModel\ProductInfoHandler;
use Fedex\Orderhistory\ViewModel\OrderHistoryEnhacement;
use Fedex\UploadToQuote\ViewModel\QuoteDetailsViewModel;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Framework\View\LayoutInterface;

class ItemRendererProvider implements ArgumentInterface
{
    private const DEFAULT_TYPE = 'default';

    public function __construct(
        protected readonly LayoutInterface        $layout,
        protected readonly OrderHistoryEnhacement $orderHistoryEnhacement,
        protected readonly ProductInfoHandler     $productInfoHandler,
        protected readonly QuoteDetailsViewModel  $quoteDetailsViewModel,
        protected readonly UploadToQuoteViewModel $uploadToQuoteViewModel,
        private readonly string                   $rendererListName = 'checkout.cart.item.renderers',
        private readonly ?array                   $overriddenTemplates = ['simple' => 'Fedex_ProductBundle::cart/item/default.phtml'],
        private readonly ?string                  $rendererTemplate = null
    ) {}

    private function getRendererList(): ?BlockInterface
    {
        return $this->rendererListName
            ? $this->layout->getBlock($this->rendererListName)
            : $this->layout->getBlock('renderer.list');
    }

    /**
     * Get the renderer block for a quote item
     */
    public function getRendererForItem(QuoteItem $item): ?\Magento\Framework\View\Element\Template
    {
        $type = $item->getProductType() ?: self::DEFAULT_TYPE;
        $rendererList = $this->getRendererList();
        if ($rendererList === null) {
            return null;
        }
        $overriddenTemplates = $this->overriddenTemplates ?? [];
        $template = $overriddenTemplates[$type] ?? $this->rendererTemplate;
        return $rendererList->getRenderer($type, self::DEFAULT_TYPE, $template);
    }

    /**
     * Get item row html
     */
    public function getItemHtml(QuoteItem $item): string
    {
        $renderer = $this->getRendererForItem($item);
        if ($renderer === null) {
            return '';
        }
        $renderer->setItem($item);
        return $renderer->toHtml();
    }

    /**
     * Helper to create a template block with common data
     */
    private function createTemplateBlock(
        string $template,
        string $name,
        array $data = []
    ): \Magento\Framework\View\Element\Template {
        $block = $this->layout->createBlock(\Magento\Framework\View\Element\Template::class)
            ->setTemplate($template)
            ->setName($name)
            ->setData($data)
            ->setArea('frontend');
        return $block;
    }

    /**
     * Get item row html
     */
    public function getMyOrdersChildItemHtml(\Magento\Sales\Model\Order\Item $item): string
    {
        return $this->createTemplateBlock(
            'Fedex_ProductBundle::order/item/reorder/default.phtml',
            'fedex_myorders_children_items',
            [
                'order_item' => $item,
                'productinfo_handler_view_model' => $this->productInfoHandler,
                'order_enhancement_view_model' => $this->orderHistoryEnhacement,
            ]
        )->toHtml();
    }

    /**
     * Get item row html
     */
    public function getMyOrdersDetailsChildItemHtml(\Magento\Sales\Model\Order\Item $item): string
    {
        return $this->createTemplateBlock(
            'Fedex_ProductBundle::order/retail-view.phtml',
            'fedex_order_details_children_items',
            [
                'order_item' => $item,
                'productinfo_handler_view_model' => $this->productInfoHandler,
                'order_enhancement_view_model' => $this->orderHistoryEnhacement,
            ]
        )->toHtml();
    }

    /**
     * Get item row html
     */
    public function getMyOrdersDetailsCommercialChildItemHtml(\Magento\Sales\Model\Order\Item $item): string
    {
        return $this->createTemplateBlock(
            'Fedex_ProductBundle::sales/order/items/renderer/default.phtml',
            'fedex_order_details_children_items',
            [
                'item' => $item,
                'productinfo_handler_view_model' => $this->productInfoHandler,
                'order_enhancement_view_model' => $this->orderHistoryEnhacement,
            ]
        )->toHtml();
    }

    /**
     * Get item row html
     */
    public function getQuoteSuccessPageChildItemHtml(QuoteItem $item): string
    {
        $block = $this->layout->createBlock(\Fedex\UploadToQuote\Block\QuoteDetails::class)
            ->setTemplate('Fedex_ProductBundle::quote/success/items/renderer/default.phtml')
            ->setName('fedex_myorders_children_items')
            ->setArea('frontend')
            ->setData('quote_item', $item)
            ->setData('quoteDetailsViewModel', $this->quoteDetailsViewModel)
            ->setData('uploadToQuoteViewModal', $this->uploadToQuoteViewModel)
            ->setData('productInfoHandler', $this->productInfoHandler)
            ->setData('order_enhancement_view_model', $this->orderHistoryEnhacement);
        return $block->toHtml();
    }

    /**
     * Get item row html
     */
    public function getQuoteDetailsChildItemHtml(QuoteItem $item): string
    {
        return $this->createTemplateBlock(
            'Fedex_ProductBundle::quote/items/renderer/default.phtml',
            'fedex_myorders_children_items',
            [
                'quote_item' => $item,
                'quoteDetailsViewModel' => $this->quoteDetailsViewModel,
                'uploadToQuoteViewModal' => $this->uploadToQuoteViewModel,
                'productInfoHandler' => $this->productInfoHandler,
                'order_enhancement_view_model' => $this->orderHistoryEnhacement,
            ]
        )->toHtml();
    }
}
