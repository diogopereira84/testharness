<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\Model;

use Fedex\Cart\ViewModel\ProductInfoHandler;
use Fedex\ProductBundle\Api\ConfigInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Model\Order\Item as OrderItem;

class BundleProductValidator
{
    /**
     * @param CheckoutSession $checkoutSession
     * @param ProductInfoHandler $productInfoHandler
     */
    public function __construct(
        private readonly CheckoutSession    $checkoutSession,
        private readonly ProductInfoHandler $productInfoHandler,
        private readonly ConfigInterface $productBundleConfig
    )
    {
    }

    /**
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isBundleProductSetupCompleted(): bool
    {
        if (!$this->productBundleConfig->isTigerE468338ToggleEnabled()) {
            return true;
        }

        $quoteItems = $this->getQuoteItems();

        foreach ($quoteItems as $item) {
            if (!$this->hasChildren($item)) {
                continue;
            }

            $childrenProduct = $item->getChildren();
            foreach ($childrenProduct as $childProduct) {
                $externalProd = (array)$this->productInfoHandler->getItemExternalProd($childProduct);
                if (empty($externalProd['contentAssociations'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param QuoteItem $item
     * @return bool
     */
    public function isBundleItemSetupCompleted(QuoteItem $item): bool
    {
        if (!$this->productBundleConfig->isTigerE468338ToggleEnabled()) {
            return true;
        }

        if (!$this->hasChildren($item)) {
            return true;
        }

        $childrenProduct = $item->getChildren();
        foreach ($childrenProduct as $childProduct) {
            $externalProd = (array)$this->productInfoHandler->getItemExternalProd($childProduct);
            if (empty($externalProd['contentAssociations'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param QuoteItem $item
     * @return bool
     */
    public function isBundleParentSetupCompleted(QuoteItem $item): bool
    {
        if (!$this->productBundleConfig->isTigerE468338ToggleEnabled()) {
            return true;
        }

        if (!$this->hasParent($item)) {
            return true;
        }

        $parentItem = $item->getParentItem();

        if (!$parentItem) {
            return true;
        }

        return $this->isBundleItemSetupCompleted($parentItem);
    }

    /**
     * @param QuoteItem $item
     * @return bool
     */
    public function isBundleChildSetupCompleted(QuoteItem $item): bool
    {
        if (!$this->productBundleConfig->isTigerE468338ToggleEnabled()) {
            return true;
        }

        $externalProd = (array)$this->productInfoHandler->getItemExternalProd($item);
        return !empty($externalProd['contentAssociations']);
    }

    /**
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function hasBundleProductInCart(): bool
    {
        $quoteItems = $this->getQuoteItems();
        foreach ($quoteItems as $item) {
            if ($this->hasChildren($item)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $instanceId
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function hasQuoteItemWithInstanceId($instanceId): bool
    {
        $quoteItems = $this->getQuoteItems();
        foreach ($quoteItems as $item) {
            if ($item->getInstanceId() == $instanceId) {
                return true;
            }
        }
        return false;
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuoteItems()
    {
        $quote = $this->checkoutSession->getQuote();
        if (!$quote) {
            return [];
        }
        return $quote->getAllItems();
    }

    /**
     * @param QuoteItem $item
     * @return bool
     */
    public function hasChildren(QuoteItem $item): bool
    {
        $children = $item->getChildren();
        return !empty($children);
    }

    /**
     * @param QuoteItem $item
     * @return bool
     */
    public function hasParent(QuoteItem $item): bool
    {
        $parentItem = $item->getParentItem();
        return $parentItem !== null;
    }

    /**
     * @param QuoteItem $item
     * @return int
     */
    public function getBundleChildrenCount(QuoteItem $item): int
    {
        if (!$this->hasChildren($item)) {
            return 0;
        }

        $children = $item->getChildren();
        return count($children);
    }

    public function getBundleChildrenItemsCount($item): int
    {
        if (!$this->hasChildrenItems($item)) {
            return 0;
        }

        $childrenItems = $item instanceof OrderItem ? $item->getChildrenItems() : $item->getChildren();
        return count($childrenItems);
    }

    public function hasChildrenItems($item): bool
    {
        $children = $item instanceof OrderItem ? $item->getChildrenItems() : $item->getChildren();
        return !empty($children);
    }

    /**
     * @param QuoteItem $item
     * @return bool
     */
    public function isBundleChild(QuoteItem $item): bool
    {
        $parentItem = $item->getParentItem();
        if (!$parentItem) {
            return false;
        }

        $parentProduct = $parentItem->getProduct();

        return $parentProduct && $parentProduct->getTypeId() === Type::TYPE_BUNDLE;
    }
}
