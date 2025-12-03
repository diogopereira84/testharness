<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types=1);

namespace Fedex\Orderhistory\Model\Reorder;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\MarketplacePunchout\Model\Config\Marketplace;
use Fedex\MarketplacePunchout\Model\Reorder\Marketplace\Add as ReorderAdd;
use Fedex\Orderhistory\ViewModel\OrderHistoryEnhacement;
use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\ProductBundle\Service\BundleProductProcessor;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Cart\CustomerCartResolver;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Fedex\MarketplaceCheckout\Helper\Data;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;

/**
 * Allows customer quickly to reorder previously added products and put them to the Cart
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Reorder
{
    /**
     * Error message codes
     */
    private const ERROR_PRODUCT_NOT_FOUND = 'PRODUCT_NOT_FOUND';
    private const ORDER_NOT_FOUND= 'ORDER NOT FOUND';
    private const ERROR_INSUFFICIENT_STOCK = 'INSUFFICIENT_STOCK';
    private const ERROR_NOT_SALABLE = 'NOT_SALABLE';
    private const ERROR_UNDEFINED = 'UNDEFINED';
    public const RATE_DETAILS = 'rate';
    public const RATE_QUOTE_DETAILS = 'rateQuote';

    /**
     * List of error messages and codes.
     */
    private const MESSAGE_CODES = [
        'The required options you selected are not available' => self::ERROR_NOT_SALABLE,
        'Product that you are trying to add is not available' => self::ERROR_NOT_SALABLE,
        'This product is out of stock' => self::ERROR_NOT_SALABLE,
        'There are no source items' => self::ERROR_NOT_SALABLE,
        'You can\'t reorder a Bundle product at this time.' => self::ERROR_NOT_SALABLE,
        'The fewest you may purchase is' => self::ERROR_INSUFFICIENT_STOCK,
        'The most you may purchase is' => self::ERROR_INSUFFICIENT_STOCK,
        'The requested qty is not available' => self::ERROR_INSUFFICIENT_STOCK,
    ];
    const TECH_TITANS_B_2041921 = 'tech_titans_B2041921_detected_on_fxo_ecommerce_platform';
    const TIGER_D_217530 = 'tiger_d217530_shopping_cart_not_clearing_3p_items';

    /**
     * @var \Magento\Sales\Model\Reorder\Data\Error[]
     */
    private $errors = [];

    /**
     * @param LoggerInterface $logger
     * @param CartRepositoryInterface $cartRepository
     * @param CustomerCartResolver $customerCartProvider
     * @param ProductCollectionFactory $productCollectionFactory
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param SerializerInterface $serializerInterface
     * @param CustomerSession $customerSession
     * @param StoreManagerInterface $storeManagerInterface
     * @param FXORate $fxoRateHelper
     * @param OrderHistoryEnhacement $orderHistoryEnhacement
     * @param FXORateQuote $fxoRateQuote
     * @param ToggleConfig $toggleConfig
     * @param ReorderAdd $reorderAdd
     * @param Marketplace $config
     * @param CartItemRepositoryInterface $cartItem
     * @param Data $helper
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel
     * @param BundleProductProcessor $bundleProductProcessor
     */
    public function __construct(
        private LoggerInterface                $logger,
        private CartRepositoryInterface        $cartRepository,
        private CustomerCartResolver           $customerCartProvider,
        private ProductCollectionFactory       $productCollectionFactory,
        private OrderItemRepositoryInterface   $orderItemRepository,
        private SerializerInterface            $serializerInterface,
        private CustomerSession                $customerSession,
        private StoreManagerInterface          $storeManagerInterface,
        protected FXORate                      $fxoRateHelper,
        protected OrderHistoryEnhacement       $orderHistoryEnhacement,
        protected FXORateQuote                 $fxoRateQuote,
        protected ToggleConfig                 $toggleConfig,
        protected ReorderAdd                   $reorderAdd,
        private Marketplace                    $config,
        private CartItemRepositoryInterface    $cartItem,
        private Data                           $helper,
        private readonly OrderRepository       $orderRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        protected UploadToQuoteViewModel       $uploadToQuoteViewModel,
        protected BundleProductProcessor       $bundleProductProcessor,
        protected ConfigInterface              $productBundleConfig
    )
    {
    }

    /**
     * Allows customer quickly to reorder previously added products and put them to the Cart
     *
     * @param array $reorderItems
     * @return \Magento\Sales\Model\Reorder\Data\ReorderOutput
     * @throws InputException Order is not found
     * @throws NoSuchEntityException The specified customer does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException Could not create customer Cart
     */
    public function execute(array $reorderItems)
    {
        $storeId = $this->storeManagerInterface->getStore()->getId();
        if ($this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_B_2041921)) {
            $storeId = (string)$this->storeManagerInterface->getStore()->getId();
        }
        $customerId = (int)$this->customerSession->getCustomer()->getId();
        $cart = $this->customerCartProvider->resolve($customerId);
        //B-2041921 valid if items belongs to the logged in customer
        if ($this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_B_2041921)) {
            if (!$this->validateCustomerItems($reorderItems, $customerId)) {
                return $this->prepareOutput($cart);
            }
        }
        if (!$this->productBundleConfig->isTigerE468338ToggleEnabled()
            && $this->bundleProductProcessor->reorderHasBundleProducts($reorderItems)) {
            $this->addError(
                (string)__('You can\'t reorder a Bundle product at this time.'),
                self::ERROR_NOT_SALABLE
            );
            return $this->prepareOutput($cart);
        }
        $reorderProductsId = $reorderFedexProductsId = [];

        foreach ($reorderItems as $orderKey => &$item) {
            $reorderProductsId[] = !$cart->hasProductId($item['product_id']) ? $item['product_id'] : '';
            $reorderFedexProductsId[$orderKey] = $item;
        }
        $this->addItemsToCart($cart, $reorderFedexProductsId, $storeId);
        $this->saveCart($cart);
        $this->updateCartRate($cart, $reorderProductsId);

        // Reload cart for item count and totals to refresh
        $newCart = $this->cartRepository->get($cart->getId());
        $this->cartRepository->save($newCart);

        return $this->prepareOutput($cart);
    }

    private function saveCart($cart)
    {
        try {
            $this->cartRepository->save($cart);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            // handle exception from \Magento\Quote\Model\QuoteRepository\SaveHandler::save
            $this->addError($e->getMessage());
        }
    }

    public function updateCartRate($cart, $reorderProductsId)
    {
        if (!$this->fxoRateHelper->isEproCustomer()) {
            $fxoRateResponse = $this->fxoRateQuote->getFXORateQuote($cart, 'reorder');
        } else {
            $fxoRateResponse = $this->fxoRateHelper->getFXORate($cart, 'reorder');
        }
        $items = $cart->getAllVisibleItems();
        $updatedCart = $this->cartRepository->get($cart->getId());
        $this->deleteCartItem($fxoRateResponse, $items, $reorderProductsId, $updatedCart);
    }

    /**
     * deleteCartItem.
     * @param fxoRateResponse $fxoRateResponse
     * @param items $items
     * @param reorderProductsId $reorderProductsId
     * @param updatedCart $updatedCart
     * @return void
     */
    public function deleteCartItem($fxoRateResponse, $items, $reorderProductsId, $updatedCart)
    {
        // Remove recently added item from cart due to Rate API Fail
        if (!isset($fxoRateResponse) || isset($fxoRateResponse['errors'][0])) {
            foreach ($items as $item) {
                if (!$item->getCustomPrice() || in_array($item->getProductId(), $reorderProductsId)) {
                    $this->cartItem->deleteById((int)$updatedCart->getId(), (int)$item->getId());
                }
                if (isset($fxoRateResponse['errors'])) {
                    $this->addError($fxoRateResponse['errors']);
                } else {
                    $this->addError($this->helper->getReorderErrorMessage());
                }
            }
        }
        $this->removeCustomOption($items);
    }

    /**
     * removeCustomOption.
     * @param items $items
     * @return void
     */
    public function removeCustomOption($items)
    {
        // start code for remove custom option from quote_item_option table for multiline cart item
        foreach ($items as $item) {
            $verifyProductAttributeSetName = $this->validateAttributeSetName($item);
            if ($verifyProductAttributeSetName) {
                $optionIds = $item->getOptionByCode('custom_option');
                if ($optionIds) {
                    $item->removeOption('custom_option')->save();
                }
            }
        }
    }

    /**
     * Add collections of order items to cart.
     *
     * @param Quote $cart
     * @param array $reorderItems
     * @param string $storeId
     * @return void
     */
    private function addItemsToCart(Quote $cart, array $reorderItems, string $storeId): void
    {
        $orderItemProductIds = [];
        /** @var \Magento\Sales\Model\Order\Item[] $orderItemsByProductId */
        $orderItemsByProductId = [];

        foreach ($reorderItems as $itemId => $reorderItem) {
            $item = $this->orderItemRepository->get($itemId);
            if ($item->getParentItem() === null) {
                if (isset($reorderItem['offerId'])) {
                    $item->setMiraklOfferId($reorderItem['offerId']);
                    $item->setOrderIncrementId($reorderItem['order_increment_id']);
                }
                $orderItemProductIds[] = $item->getProductId();
                $orderItemsByProductId[$item->getProductId()][$item->getId()] = $item;
            }
        }

        $products = $this->getOrderProducts($storeId, $orderItemProductIds);

        // compare founded products and throw an error if some product not exists
        $productsNotFound = array_diff($orderItemProductIds, array_keys($products));
        // @codeCoverageIgnoreStart
        if (!empty($productsNotFound)) {
            foreach ($productsNotFound as $productId) {
                /** @var \Magento\Sales\Model\Order\Item $orderItemProductNotFound */
                $this->addError(
                    (string)__('Could not find a product with ID "%1"', $productId),
                    self::ERROR_PRODUCT_NOT_FOUND
                );
            }
        }
        // @codeCoverageIgnoreEnd

        foreach ($orderItemsByProductId as $productId => $orderItems) {
            // @codeCoverageIgnoreStart
            if (!isset($products[$productId])) {
                continue;
            }
            // @codeCoverageIgnoreEnd
            $product = $products[$productId];
            foreach ($orderItems as $orderItem) {
                $this->addItemToCart($orderItem, $cart, clone $product);
            }
        }
    }

    /**
     * Get order products by store id and order item product ids.
     *
     * @param string $storeId
     * @param int[] $orderItemProductIds
     * @return Product[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getOrderProducts(string $storeId, array $orderItemProductIds): array
    {
        /** @var Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->setStore($storeId)
            ->addIdFilter($orderItemProductIds)
            ->addStoreFilter()
            ->addAttributeToSelect('*')
            ->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner')
            ->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner')
            ->addOptionsToResult();

        return $collection->getItems();
    }

    /**
     * Adds order item product to cart.
     *
     * @param Item|Object $orderItem
     * @param Quote $cart
     * @param Product $product
     * @return void
     */
    public function addItemToCart($orderItem, Quote $cart, Product $product): void
    {
        $info = $orderItem->getProductOptionByCode('info_buyRequest');
        if ($this->uploadToQuoteViewModel->isUploadToQuoteEnable()) {
           $info = $this->uploadToQuoteViewModel->resetCustomerSI($info);
        }
        $info = new \Magento\Framework\DataObject($info);
        $info->setData('qty', $orderItem->getQtyOrdered());
        $productId = $orderItem->getProductId();
        $verifyProductAttributeSetName = $this->validateAttributeSetName($orderItem);

        if ($verifyProductAttributeSetName) {
            $randomNumber = rand(1, 100000000000000);
            $customOptions = [
                'label' => 'fxoProductInstance',
                'value' => $productId . $randomNumber,
            ];
            $product->addCustomOption('custom_option', $this->serializerInterface->serialize($customOptions));
        }

        // code added for custom options start

        $customizeFields = $orderItem->getProductOptionByCode('customize_fields');
        if ($customizeFields) {
            $product->addCustomOption('customize_fields', $this->serializerInterface->serialize($customizeFields));
        }
        // code added for custom options end

        $addProductResult = null;
        try {
            if ($orderItem->getMiraklOfferId()) {
                if ($this->toggleConfig->getToggleConfigValue(self::TIGER_D_217530)) {
                    $this->reorderAdd->addItemToCartEnhancement($orderItem, $cart);
                } else {
                    $this->reorderAdd->addItemToCart($orderItem);
                }

            } else {
                $addProductResult = $cart->addProduct($product, $info);
                if(!empty($info['external_prod']) && !empty($info['external_prod'][0]) && !empty($info['external_prod'][0]['instanceId'])) {
                    $addProductResult->setInstanceId($info['external_prod'][0]['instanceId']);
                }

                if ($this->productBundleConfig->isTigerE468338ToggleEnabled()
                    && $addProductResult->getProductType() === Product\Type::TYPE_BUNDLE
                    && $addProductResult->getChildren()) {
                    $this->bundleProductProcessor->handleBundleItemReorder($orderItem, $addProductResult);
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $this->addError($this->getCartItemErrorMessage($orderItem, $product, $e->getMessage()));
        } catch (\Throwable $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $this->addError($this->getCartItemErrorMessage($orderItem, $product), self::ERROR_UNDEFINED);
        }

        // error happens in case the result is string
        if (is_string($addProductResult)) {
            $errors = array_unique(explode("\n", $addProductResult));
            foreach ($errors as $error) {
                $this->addError($this->getCartItemErrorMessage($orderItem, $product, $error));
            }
        }
    }

    /**
     * Add order line item error
     *
     * @param string $message
     * @param string|null $code
     * @return void
     */
    private function addError(string $message, string $code = null): void
    {
        $this->errors[] = new \Magento\Sales\Model\Reorder\Data\Error(
            $message,
            $code ?? $this->getErrorCode($message)
        );
    }

    /**
     * Get message error code. Ad-hoc solution based on message parsing.
     *
     * @param string $message
     * @return string
     */
    private function getErrorCode(string $message): string
    {
        $code = self::ERROR_UNDEFINED;

        $matchedCodes = array_filter(
            self::MESSAGE_CODES,
            function ($key) use ($message) {
                return false !== strpos($message, $key);
            },
            ARRAY_FILTER_USE_KEY
        );

        // @codeCoverageIgnoreStart
        if (!empty($matchedCodes)) {
            $code = current($matchedCodes);
        }
        // @codeCoverageIgnoreEnd

        return $code;
    }

    /**
     * Prepare output
     *
     * @param CartInterface $cart
     * @return \Magento\Sales\Model\Reorder\Data\ReorderOutput
     */
    private function prepareOutput(CartInterface $cart): \Magento\Sales\Model\Reorder\Data\ReorderOutput
    {
        $output = new \Magento\Sales\Model\Reorder\Data\ReorderOutput($cart, $this->errors);
        $this->errors = [];
        // we already show user errors, do not expose it to cart level
        $cart->setHasError(false);
        return $output;
    }

    /**
     * Get error message for a cart item
     *
     * @param Item $item
     * @param ProductInterface $product
     * @param string|null $message
     * @return string
     */
    private function getCartItemErrorMessage(Item $item, ProductInterface $product, string $message = null): string
    {
        // try to get sku from line-item first.
        // for complex product type: if custom option is not available it can cause error
        $sku = $item->getSku() ?? $product->getSku();
        return (string)($message
            ? __('Could not add the product with SKU "%1" to the shopping cart: %2', $sku, $message)
            : __('Could not add the product with SKU "%1" to the shopping cart', $sku));
    }

    /**
     * Validate Attribute set name
     *
     * @param Object $orderItem
     * @return bool
     */
    public function validateAttributeSetName($orderItem)
    {
        $productId = $orderItem->getProductId();
        $isCustomize = $this->orderHistoryEnhacement->getProductCustomAttributeValue($productId, 'customizable');
        $productObj = $this->orderHistoryEnhacement->loadProductObj($productId);
        $attributeSetId = $productObj->getAttributeSetId();
        $attributeSetName = $this->orderHistoryEnhacement->getProductAttributeSetName($attributeSetId);

        if (($attributeSetName == 'FXOPrintProducts') || ($isCustomize)) {
            return true;
        }

        return false;
    }

    /**
     * @param array $reorderItems
     * @param int $customerId
     * @return bool
     */
    public function validateCustomerItems(array $reorderItems, int $customerId): bool
    {
        $requestedOrdersIds = [];
        $itemsIds = [];
        foreach ($reorderItems as $itemId => $reorderItem) {
            $itemsIds[] = $itemId;
            $requestedOrdersIds[] = $reorderItem['order_id'];
            if ($this->productBundleConfig->isTigerE468338ToggleEnabled() && isset($reorderItem['children'])) {
                $itemsIds = array_merge($itemsIds, $reorderItem['children']);
            }
        }
        $itemsIds = array_unique($itemsIds);
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(OrderItemInterface::ITEM_ID, $itemsIds, 'in')
            ->addFilter(OrderItemInterface::ORDER_ID, $requestedOrdersIds, 'in')
            ->create();

        $items = $this->orderItemRepository->getlist($searchCriteria)->getItems();

        if (empty($items) || count($items) != count($itemsIds)) {
            $this->addError(
                (string)__('Orders and items do not match'),
                self::ORDER_NOT_FOUND
            );
            return false;
        }

        return $this->validateCustomerOrder($requestedOrdersIds, $customerId);
    }

    /**
     * @param array $requestedOrdersIds
     * @param int $customerId
     * @return bool
     */
    public function validateCustomerOrder(array $requestedOrdersIds, int $customerId): bool
    {
        $requestedOrdersIds = array_unique($requestedOrdersIds);
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $requestedOrdersIds, 'in')
            ->addFilter('customer_id', $customerId)
            ->create();
        $orders = $this->orderRepository->getList($searchCriteria)->getItems();
        if (empty($orders) || count($orders) != count($requestedOrdersIds)) {
            $this->addError(
                (string)__('the requested order or item does not match the logged in customer'),
                self::ORDER_NOT_FOUND
            );
            return false;
        }

        foreach ($orders as $order) {
            if (!in_array($order->getEntityId(), $requestedOrdersIds)) {
                $this->addError(
                    (string)__('the requested order or item does not match the logged in customer'),
                    self::ORDER_NOT_FOUND
                );
                return false;
            }
        }
        return true;
    }
}
