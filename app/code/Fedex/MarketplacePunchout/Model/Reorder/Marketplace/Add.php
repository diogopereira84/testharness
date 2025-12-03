<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Jyoti thakur <jyoti.thakur.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model\Reorder\Marketplace;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Model\Reorder\OrderInfoBuyRequestGetter;
use Psr\Log\LoggerInterface;
use Fedex\Cart\Model\Quote\ThirdPartyProduct\Update;
use Fedex\MarketplaceCheckout\Helper\Data;
use Fedex\Cart\Model\Quote\ThirdPartyProduct\ExternalProd;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Magento\Quote\Model\Quote;
use JsonException;

class Add
{
    /**
     * @param Cart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param RequestInterface $request
     * @param SearchCriteriaBuilder $searchBuilder
     * @param ReorderApi $reorderApi
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     * @param Update $update
     * @param OrderInfoBuyRequestGetter $orderInfoBuyRequestGetter
     * @param Data $helper
     * @param ExternalProd $externalProd
     * @param NonCustomizableProduct $nonCustomizableProduct
     */
    public function __construct(
        private Cart $cart,
        private ProductRepositoryInterface $productRepository,
        private RequestInterface $request,
        private SearchCriteriaBuilder $searchBuilder,
        private ReorderApi $reorderApi,
        private SerializerInterface $serializer,
        private LoggerInterface $logger,
        private Update $update,
        private OrderInfoBuyRequestGetter $orderInfoBuyRequestGetter,
        private Data $helper,
        private ExternalProd $externalProd,
        private NonCustomizableProduct $nonCustomizableProduct
    ) {
    }

    /**
     * Adds third party product to cart for reorder
     *
     * @param object $orderItem
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function addItemToCart(object $orderItem): void
    {
        $this->searchBuilder
            ->addFilter('sku', $orderItem->getSku());
        $searchCriteria = $this->searchBuilder
            ->create();
        $searchCriteria->setPageSize(1)
            ->setCurrentPage(1);
        $products = $this->productRepository
            ->getList($searchCriteria)
            ->getItems();
        $product = last($products);

        $this->request->setPostValue('isMarketplaceProduct', true);

        $infoBuyRequest = $this->orderInfoBuyRequestGetter->getInfoBuyRequest($orderItem);

        // Add Marketplace related field if missing from original order
        $offerId = $infoBuyRequest->getData('offer_id');
        if (!isset($offerId)) {
            $infoBuyRequest['product'] = $orderItem->getData('product_id');
            $infoBuyRequest['qty'] = $orderItem->getData('qty_ordered');
            $infoBuyRequest['offer_id'] = $orderItem->getData('mirakl_offer_id');
        }

        $cart = $this->cart->addProduct($product, $infoBuyRequest);
        $this->cart->save();

        $items = $cart->getItems();
        $quoteItem = null;
        foreach ($items as $item) {
            if ($item->getProductId() == $product->getId()) {
                $quoteItem = $item;
            }
        }
        if (!$quoteItem) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Error adding product to cart.')
            );
        }

        if ($orderItem->getAdditionalData()) {
            $additionalData = json_decode($orderItem->getAdditionalData(), true);
            unset($additionalData['mirakl_shipping_data']);
            $quoteItem->setAdditionalData(json_encode($additionalData));
        }

        $quoteItem->setMiraklOfferId($orderItem->getMiraklOfferId());
        $quoteItem->setMiraklShopId($orderItem->getMiraklShopId());
        $orderIncrementId = $orderItem->getOrderIncrementId() ?? $orderItem->getOrder()->getIncrementId();
        $quoteItem = $this->updateThirdPartyItem($quoteItem, $orderIncrementId);
        $this->cart->getQuote()->addItem($quoteItem);
        $this->cart->getQuote()->save();
    }

    /**
     * Adds third party product to cart for reorder
     *
     * @param object $orderItem
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function addItemToCartEnhancement(object $orderItem, Quote $cart): void
    {
        $this->searchBuilder->addFilter('sku', $orderItem->getSku());
        $searchCriteria = $this->searchBuilder->create();
        $searchCriteria->setPageSize(1)->setCurrentPage(1);
        $products = $this->productRepository->getList($searchCriteria)->getItems();
        $product = reset($products);

        if (!$product) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Product with SKU %1 not found.', $orderItem->getSku())
            );
        }

        $infoBuyRequest = $this->orderInfoBuyRequestGetter->getInfoBuyRequest($orderItem);

        if (!$infoBuyRequest->getData('offer_id')) {
            $infoBuyRequest->setData('product', $orderItem->getData('product_id'));
            $infoBuyRequest->setData('qty', $orderItem->getData('qty_ordered'));
            $infoBuyRequest->setData('offer_id', $orderItem->getData('mirakl_offer_id'));
        }

        if ($this->nonCustomizableProduct->isD217169Enabled()
            && $this->isProductWithPunchoutDisabledInCartForUpdate($product)
        ) {
            $this->handlePunchoutDisabledProduct($cart, $product, $orderItem, $infoBuyRequest);
        } else {
            $cart->addProduct($product, $infoBuyRequest);
        }

        $quoteItem = $cart->getItemByProduct($product);

        $quoteItem->save();

        if (!$quoteItem) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Error adding product to cart.')
            );
        }

        if ($orderItem->getAdditionalData()) {
            $additionalData = json_decode($orderItem->getAdditionalData(), true);

            if (is_array($additionalData)) {
                unset($additionalData['mirakl_shipping_data']);

                try {
                    $quoteItem->setAdditionalData(json_encode($additionalData, JSON_THROW_ON_ERROR));
                } catch (JsonException $e) {
                    throw new LocalizedException(__('Failed to encode additional data: %1', $e->getMessage()));
                }
            }
        }

        $quoteItem->setMiraklOfferId($orderItem->getMiraklOfferId());
        $quoteItem->setMiraklShopId($orderItem->getMiraklShopId());
        $orderIncrementId = $orderItem->getOrderIncrementId() ?? $orderItem->getOrder()->getIncrementId();
        $this->updateThirdPartyItem($quoteItem, $orderIncrementId);

        $cart->save();
    }

    /**
     * Updates third party quote item
     *
     * @param Item $quoteItem
     * @param string $orderIncrementId
     * @return Item
     * @throws LocalizedException
     * @throws Exception
     */
    public function updateThirdPartyItem(Item $quoteItem, string $orderIncrementId): Item
    {
        $additionalData = [];
        $supplierPartAuxiliaryID = '';
        if ($quoteItem->getAdditionalData()) {
            $additionalData = (array) json_decode($quoteItem->getAdditionalData());
            $supplierPartAuxiliaryID = $additionalData['supplierPartAuxiliaryID'];
        }
        $itemQty = $additionalData['quantity'] ?? null;

        $punchoutEnabled = true;
        if ($this->nonCustomizableProduct->isMktCbbEnabled()) {
            if (isset($additionalData['punchout_enabled'])) {
                $punchoutEnabled = (bool)$additionalData['punchout_enabled'];
            }
        }

        if ($punchoutEnabled) {
            $reorderApiResponse = json_decode($this->reorderApi->getReorderApiData($supplierPartAuxiliaryID, $quoteItem->getSku(), $orderIncrementId, $itemQty));
            if (isset($reorderApiResponse->status) && $reorderApiResponse->status == 200) {
                $supplierPartAuxiliaryID = ($reorderApiResponse->response[0]->configDataModel->brokerConfigId ?? $reorderApiResponse->response[0]->configDataModel->brokerConfigID);

                $total = (double)isset($reorderApiResponse->response[0]->configDataModel->totalOrderPrice)
                    ? $reorderApiResponse->response[0]->configDataModel->totalOrderPrice
                    : $reorderApiResponse->response[0]->configDataModel->totalPrice;
                $additionalData['total'] = $total;
                $quantity = (int)isset($reorderApiResponse->response[0]->configDataModel->quantity)
                    ? $reorderApiResponse->response[0]->configDataModel->quantity
                    : $itemQty;
                $additionalData['quantity'] = $quantity;
                $productName = isset($reorderApiResponse->response[0]->configDataModel->productName)
                    ? $reorderApiResponse->response[0]->configDataModel->productName
                    : $quoteItem->getName();
                $additionalData['marketplace_name'] = $productName;
                $additionalData['reorder_item'] = true;
                $additionalData['supplierPartAuxiliaryID'] = $supplierPartAuxiliaryID;
                $imageName = $supplierPartAuxiliaryID . '.png';
                $image = $this->update->saveImage(
                    isset($reorderApiResponse->response[0]->artworkUrl)
                        ? $reorderApiResponse->response[0]->artworkUrl
                        : $additionalData['image'], $imageName);
                $quoteItem->setAdditionalData(json_encode($additionalData));

                $quantity = $additionalData['quantity'];
                $unitPrice = $total / $quantity;

                $quoteItem->setPrice($unitPrice);
                $quoteItem->setBasePrice($unitPrice);
                $quoteItem->setCustomPrice($unitPrice);
                $quoteItem->setOriginalCustomPrice($unitPrice);
                $quoteItem->setQty($quantity);
                $quoteItem->setBaseRowTotal($unitPrice);
                $quoteItem->setPriceInclTax($unitPrice);
                $quoteItem->setBasePriceInclTax($unitPrice);
                $quoteItem->setRowTotal($total);
                $quoteItem->setBaseRowTotal($total);
                $quoteItem->setRowTotalInclTax($total);
                $quoteItem->setBaseRowTotalInclTax($total);
                $quoteItem->setIsSuperMode(true);
            } else {
                $quoteItem->delete();
                return $quoteItem;
            }
        } else {
            $supplierPartID = null;
            $supplierPartAuxiliaryID = null;

            $total = $quoteItem->getPrice() * $itemQty;
            $additionalData['total'] = $total;
            $quantity = $itemQty;
            $additionalData['quantity'] = $quantity;
            $productName = $quoteItem->getName();
            $additionalData['marketplace_name'] = $productName;
            $additionalData['reorder_item'] = true;
            $additionalData['supplierPartID'] = (string) $supplierPartID;
            $additionalData['supplierPartAuxiliaryID'] = (string) $supplierPartAuxiliaryID;
            $additionalData['unit_price'] = $quoteItem->getPrice();
            $image = $this->nonCustomizableProduct->getProductImage($quoteItem->getProduct()) ?? null;
            $additionalData['image'] = $image;
            $quoteItem->setAdditionalData(json_encode($additionalData));

            $quantity = $additionalData['quantity'];
            $unitPrice = $total / $quantity;

            $quoteItem->setPrice($unitPrice);
            $quoteItem->setBasePrice($unitPrice);
            $quoteItem->setCustomPrice($unitPrice);
            $quoteItem->setOriginalCustomPrice($unitPrice);
            $quoteItem->setQty($quantity);
            $quoteItem->setBaseRowTotal($unitPrice);
            $quoteItem->setPriceInclTax($unitPrice);
            $quoteItem->setBasePriceInclTax($unitPrice);
            $quoteItem->setRowTotal($total);
            $quoteItem->setBaseRowTotal($total);
            $quoteItem->setRowTotalInclTax($total);
            $quoteItem->setBaseRowTotalInclTax($total);
            $quoteItem->setIsSuperMode(true);
        }

        $externalData = [
            'preview_url' => (string) $image,
            'name' => $quoteItem->getName(),
        ];

        $save = [
            'external_prod' => [
                0 => $externalData,
            ],
            'quantityChoices' => ['1'],
            'total' => (double) $total,
            'unit_price' => $additionalData['unit_price'],
            'image' => $image,
            'quantity' => $quantity,
            'marketplace_name' => $productName,
            'supplier_part_auxiliary_id' => (string)$supplierPartAuxiliaryID
        ];
        $quoteItem->addOption([
            'product_id' => $quoteItem->getProductId(),
            'code' => 'marketplace_data',
            'value' => $this->serializer->serialize($save),

        ]);
        $quoteItem->saveItemOptions();
        return $quoteItem;
    }

    /**
     * Checks if the product is a punchout disabled product in the cart for update
     *
     * @param ProductInterface|Product $product
     * @return bool
     */
    protected function isProductWithPunchoutDisabledInCartForUpdate(ProductInterface $product): bool
    {
        $cbbEnabled = $this->nonCustomizableProduct->isMktCbbEnabled();
        $isProductInCart = in_array($product->getId(), $this->cart->getQuoteProductIds(), true);
        $punchoutDisabled = !$product->getCustomizableProduct();
        return $cbbEnabled && $isProductInCart && $punchoutDisabled;
    }

    /**
     * Handles punchout disabled product in the cart for update
     *
     * @param Quote $cart
     * @param ProductInterface $product
     * @param object $orderItem
     * @param \Magento\Framework\DataObject $infoBuyRequest
     * @return void
     * @throws LocalizedException
     */
    private function handlePunchoutDisabledProduct(
        Quote $cart,
        ProductInterface $product,
        object $orderItem,
        \Magento\Framework\DataObject $infoBuyRequest
    ): void {
        $this->syncProductCustomOptionsWithQuoteItem($cart, $product, $orderItem, $infoBuyRequest);
        $this->updateProductQtyInCartPunchoutDisabled($orderItem, $product, $cart);
    }

    /**
     * Matches Product and QuoteItem in the cart and passes custom options from QuoteItem to Product.
     *
     * @param Quote $cart
     * @param ProductInterface|Product $product
     * @param $orderItem
     * @param \Magento\Framework\DataObject $infoBuyRequest
     * @return void
     * @throws LocalizedException
     */
    protected function syncProductCustomOptionsWithQuoteItem($cart, $product, $orderItem, $infoBuyRequest): void
    {
        $quoteItem = $this->findQuoteItemByProduct($cart, $product);

        $qty = $quoteItem->getQty() + $orderItem->getQtyOrdered();
        $this->validateMinMaxQty($product, $qty);

        foreach ($quoteItem->getOptions() as $option) {
            $this->updateCustomOption($option, $infoBuyRequest, $qty);
            $product->addCustomOption($option->getCode(), $option->getValue());
        }

        $this->updateOrderItemAdditionalDataWithQuoteItem($orderItem, $qty);
    }

    /**
     * Finds the quote item by product in the cart.
     *
     * @param Quote $cart
     * @param ProductInterface $product
     * @return Item
     * @throws LocalizedException
     */
    private function findQuoteItemByProduct($cart, $product): Item
    {
        foreach ($cart->getAllItems() as $item) {
            if ($item->getProductId() === $product->getId()) {
                return $item;
            }
        }

        throw new LocalizedException(__('Product not found in the cart.'));
    }

    /**
     * Validates the minimum and maximum quantity for a product.
     *
     * @param $product
     * @param $qty
     * @return void
     * @throws LocalizedException
     */
    protected function validateMinMaxQty($product, $qty): void
    {
        $validateProductMaxQty = $this->nonCustomizableProduct->isD213961Enabled()
            ? $this->nonCustomizableProduct->validateProductMaxQty($product, $qty)
            : $this->nonCustomizableProduct->validateProductMaxQty($product->getId(), $qty);

        if (!empty($validateProductMaxQty)) {
            throw new LocalizedException(__($validateProductMaxQty));
        }
    }

    /**
     * Updates the custom option of a quote item based on the info buy request and quantity.
     *
     * @param Item\Option $option
     * @param \Magento\Framework\DataObject $infoBuyRequest
     * @param float $qty
     * @return void
     */
    private function updateCustomOption($option, $infoBuyRequest, $qty): void
    {
        if ($option->getCode() === 'info_buyRequest') {
            $reorderInfoBuyRequest = $infoBuyRequest->getData();
            $quoteItemInfoBuyRequest = $this->serializer->unserialize($option->getValue());
            $finalInfoBuyRequest = array_merge($reorderInfoBuyRequest, $quoteItemInfoBuyRequest);
            $finalInfoBuyRequest['qty'] = $qty;
            unset($finalInfoBuyRequest['productRateTotal']);
            $option->setValue($this->serializer->serialize($finalInfoBuyRequest));
        }

        if ($option->getCode() === 'marketplace_data') {
            $marketplaceData = $this->serializer->unserialize($option->getValue());
            $marketplaceData['quantityChoices'] = [$qty];
            $marketplaceData['quantity'] = $qty;
            $marketplaceData['total'] = $marketplaceData['total'] * $qty;
            $option->setValue($this->serializer->serialize($marketplaceData));
        }
    }

    /**
     * Updates order item additional data with quote item data.
     *
     * @param $orderItem
     * @param $qty
     * @return void
     */
    protected function updateOrderItemAdditionalDataWithQuoteItem($orderItem, $qty): void
    {
        $additionalData = json_decode($orderItem->getAdditionalData() ?? '{}', true);
        if (is_array($additionalData)) {
            $additionalData['quantity'] = $qty;
            $additionalData['total'] = ($additionalData['unit_price'] ?? 0) * $qty;
            $orderItem->setAdditionalData($this->serializer->serialize($additionalData));
        }
    }

    /**
     * @param $orderItem
     * @param $product
     * @param Quote $cart
     * @return void
     * @throws LocalizedException
     */
    protected function updateProductQtyInCartPunchoutDisabled($orderItem, $product, $cart): void
    {
        $quoteItem = $cart->getItemByProduct($product);
        if ($quoteItem) {
            $qty = $quoteItem->getQty() + $orderItem->getQtyOrdered();
            if ($qty > 0) {
                $quoteItem->clearMessage();
                $quoteItem->setHasError(false);
                $quoteItem->setQty($qty);

                if ($quoteItem->getHasError()) {
                    throw new LocalizedException(__($quoteItem->getMessage()));
                }
            }
        }
    }
}
