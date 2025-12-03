<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\Model;

use Fedex\Cart\ViewModel\ProductInfoHandler;
use Fedex\ProductBundle\Api\ConfigInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Catalog\Model\Product\Media\Config as MediaConfig;

class OrderBundleInfoProvider
{
    /**
     * @param CustomerSession $customerSession
     * @param OrderRepositoryInterface $orderRepository
     * @param MediaConfig $mediaConfig
     * @param ProductInfoHandler $productInfoHandler
     * @param ConfigInterface $productBundleConfig
     */
    public function __construct(
        private readonly CustomerSession          $customerSession,
        private readonly OrderRepositoryInterface $orderRepository,
        private MediaConfig                       $mediaConfig,
        private ProductInfoHandler                $productInfoHandler,
        private readonly ConfigInterface          $productBundleConfig
    )
    {
    }

    /**
     * Get bundle items information for the success page
     *
     * @return array<string, array>
     */
    public function getBundleItemsSuccessPage(): array
    {
        try {
            if (!$this->productBundleConfig->isTigerE468338ToggleEnabled()) {
                return [];
            }

            $lastOrderId = $this->customerSession->getLastOrderId();
            if (!$lastOrderId) {
                return [];
            }

            $lastOrder = $this->orderRepository->get($lastOrderId);
            return $this->collectBundleItemsData($lastOrder->getAllItems());
        } catch (NoSuchEntityException $e) {
            // Consider adding logging here
            return [];
        }
    }

    /**
     * Collect bundle items data from order items
     *
     * @param \Magento\Sales\Api\Data\OrderItemInterface[] $orderItems
     * @return array<string, array>
     */
    private function collectBundleItemsData(array $orderItems): array
    {
        $productItems = [];

        foreach ($orderItems as $item) {
            if ($item->getProductType() !== Type::TYPE_BUNDLE || $item->getParentItem()) {
                continue;
            }

            $productItems[] = $this->prepareBundleItemData($item);
        }

        return $productItems;
    }

    /**
     * Prepare bundle parent item data
     *
     * @param \Magento\Sales\Api\Data\OrderItemInterface $item
     * @return array
     */
    private function prepareBundleItemData($item): array
    {
        $child_ids = [];
        $childrenItems = [];
        foreach ($item->getChildrenItems() as $childItem) {
            $child_ids[] = $childItem->getQuoteItemId();
            $childrenItems[$childItem->getQuoteItemId()] = $this->prepareChildItemData($childItem);
        }

        return [
            'id' => $item->getItemId(),
            'type'=> $item->getProductType(),
            'name' => $item->getName(),
            'price' => $item->getPrice(),
            'subtotal' => ($item->getRowTotal() - $item->getDiscountAmount()),
            'discount' => $item->getDiscountAmount(),
            'image' => $this->getProductImageUrl($item),
            'child_ids' => $child_ids,
            'children_data' => $childrenItems
        ];
    }

    /**
     * Prepare bundle child item data
     *
     * @param \Magento\Sales\Api\Data\OrderItemInterface $childItem
     * @return array
     */
    private function prepareChildItemData($childItem): array
    {
        $externalProd = (array)$this->productInfoHandler->getItemExternalProd($childItem);
        return [
            'name' => $childItem->getName(),
            'image' => $this->getProductImageUrl($childItem),
            'preview_url' => $externalProd['preview_url'] ?? null,
            'qty' => $childItem->getQtyOrdered(),
            'price' => $childItem->getPrice(),
            'subtotal' => $childItem->getRowTotal(),
            'discount' => $childItem->getDiscountAmount()
        ];
    }

    /**
     * Get product image URL from order item
     *
     * @param \Magento\Sales\Api\Data\OrderItemInterface $item
     * @return string|null
     */
    private function getProductImageUrl($item): ?string
    {
        $product = $item->getProduct();
        if ($product && $product->getImage()) {
            return $this->mediaConfig->getMediaUrl($product->getImage());
        }

        return null;
    }
}
