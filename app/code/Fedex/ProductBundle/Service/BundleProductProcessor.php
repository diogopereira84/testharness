<?php
declare(strict_types=1);
namespace Fedex\ProductBundle\Service;

use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;

class BundleProductProcessor
{
    public const INSTANCE_ID = 'instanceId';
    public const FXO_PRODUCT = 'fxo_product';
    public const INFO_BUY_REQUEST = 'info_buyRequest';

    public function __construct(
        private SerializerInterface            $serializer,
        private UploadToQuoteViewModel         $uploadToQuoteViewModel,
        protected FXORate                      $fxoRateHelper,
        protected FXORateQuote                 $fxoRateQuote,
        private InstoreConfig                  $instoreConfig,
        private OrderItemRepositoryInterface   $orderItemRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
    )
    {
    }

    /**
     * Process bundle child items with associated product data
     *
     * @param array|string|null $productsData
     * @param Item $quoteItem
     * @param Quote $quote
     * @return mixed
     * @throws LocalizedException
     */
    public function processBundleItems(array|string|null $productsData, Item $quoteItem, Quote $quote): mixed
    {
        $bundleItems = $quoteItem->getChildren();
        $productDataBySKU = $this->mapProductsBySku($productsData);

        foreach ($bundleItems as $childItem) {
            $sku = $childItem->getProduct()->getSku();

            if (!isset($productDataBySKU[$sku])) {
                continue;
            }

            $this->addChildItemOptions($childItem, $productDataBySKU[$sku]);
        }

        return $this->handleFXORateQuote($quote);
    }

    /**
     * Map product data array by SKU key
     *
     * @param array|string|null $productsData
     * @return array
     */
    private function mapProductsBySku(array|string|null $productsData): array
    {
        if (is_string($productsData)) {
            $productsData = json_decode($productsData, true);
        }

        if (!is_array($productsData)) {
            return [];
        }

        $mappedProducts = [];

        foreach ($productsData as $product) {
            if (empty($product['integratorProductReference'])) {
                continue;
            }

            $sku = $product['integratorProductReference'];
            unset($product['contentAssociations']);

            $product['fxoMenuId'] = $sku;
            $product['isEdited'] = false;
            $product['isEditable'] = false;

            $mappedProducts[$sku] = $product;
        }

        return $mappedProducts;
    }

    /**
     * Add necessary options and metadata to child quote item
     *
     * @param Item $childItem
     * @param array $productData
     * @return void
     */
    private function addChildItemOptions(Item $childItem, array $productData): void
    {
        $quantityChoices = $productData['quantityChoices'] ?? [];
        unset($productData['quantityChoices']);
        $infoBuyRequest = [
            'external_prod' => [0 => $productData],
            'quantityChoices' => $quantityChoices
        ];

        $serialized = $this->serializer->serialize($infoBuyRequest);

        $childItem->addOption([
            'product_id' => $childItem->getProductId(),
            'code'       => self::INFO_BUY_REQUEST,
            'value'      => $serialized,
        ]);

        $childItem->setSiType(
            $this->uploadToQuoteViewModel->getSiType($serialized)
        );

        $childItem->setInstanceId($productData[self::INSTANCE_ID] ?? null);
        $childItem->save();
    }

    /**
     * Handle FXO rate quote response logic
     *
     * @param Quote $quote
     * @return mixed
     * @throws GraphQlFujitsuResponseException
     */
    private function handleFXORateQuote(Quote $quote): mixed
    {
        if (!$this->fxoRateHelper->isEproCustomer()) {
            try {
                $response = $this->fxoRateQuote->getFXORateQuote($quote);

                if (!empty($response['errors'])) {
                    $quote->setFxoRateError(true);
                }

                return $response;
            } catch (GraphQlFujitsuResponseException $e) {
                if ($this->instoreConfig->isEnabledThrowExceptionOnGraphqlRequests()) {
                    throw new GraphQlFujitsuResponseException(__($e->getMessage()));
                }

                return null;
            }
        }

        return $this->fxoRateHelper->getFXORate($quote);
    }

    /**
     * Handle bundle item reordering by setting instance IDs
     *
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param Item $productAdded
     * @return void
     */
    public function handleBundleItemReorder($orderItem, $productAdded): void
    {
        /** @var \Magento\Sales\Model\Order\Item[] $orderItemsByProductId */
        $orderItemsByProductId = [];
        foreach ($orderItem->getChildrenItems() as $orderItemChild) {
            $orderItemsByProductId[$orderItemChild->getProductId()] = $orderItemChild;
        }
        foreach ($productAdded->getChildren() as $quoteItemChild) {
            $orderItemChild = $orderItemsByProductId[$quoteItemChild->getProductId()];
            $orderItemInfoBuyRequest = $orderItemChild->getProductOptionByCode('info_buyRequest');

            $infoBuyRequest = $orderItemChild->getProductOptionByCode('info_buyRequest');
            if ($this->uploadToQuoteViewModel->isUploadToQuoteEnable()) {
                $infoBuyRequest = $this->uploadToQuoteViewModel->resetCustomerSI($infoBuyRequest);
            }

            $quoteItemChild->setData('qty', (int)$orderItemChild->getQtyOrdered());
            $quoteItemChild->addOption([
                'product_id' => $quoteItemChild->getProductId(),
                'code'       => self::INFO_BUY_REQUEST,
                'value'      => $this->serializer->serialize($infoBuyRequest),
            ]);
            if(!empty($orderItemInfoBuyRequest['external_prod'])
                && !empty($orderItemInfoBuyRequest['external_prod'][0])
                && !empty($orderItemInfoBuyRequest['external_prod'][0]['instanceId'])) {
                $quoteItemChild->setInstanceId($orderItemInfoBuyRequest['external_prod'][0]['instanceId']);
            }
        }
    }

    /**
     * Check if the reorder items contain bundle products
     *
     * @param array $reorderItems
     * @param int $customerId
     * @return bool
     */
    public function reorderHasBundleProducts(array $reorderItems): bool
    {
        $requestedOrdersIds = [];
        $itemsIds = [];
        foreach ($reorderItems as $itemId => $reorderItem) {
            $itemsIds[] = $itemId;
            $requestedOrdersIds[] = $reorderItem['order_id'];
        }
        $itemsIds = array_unique($itemsIds);
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(OrderItemInterface::ITEM_ID, $itemsIds, 'in')
            ->addFilter(OrderItemInterface::ORDER_ID, $requestedOrdersIds, 'in')
            ->addFilter(OrderItemInterface::PRODUCT_TYPE, Type::TYPE_BUNDLE)
            ->create();

        $items = $this->orderItemRepository->getList($searchCriteria)->getItems();

        if (!empty($items)) {
            return true;
        }

        return false;
    }

    /**
     * Map products by SKU for quote approval
     *
     * @param array|string|null $productsData
     * @return array
     */
    public function mapProductsBySkuForQuoteApproval(array|string|null $productsData): array
    {
        if (is_string($productsData)) {
            $productsData = json_decode($productsData, true);
        }

        if (!is_array($productsData)) {
            return [];
        }

        $mappedProducts = [];

        foreach ($productsData as $product) {
            if (empty($product['productConfig']['integratorProductReference'])) {
                continue;
            }
            $sku = $product['productConfig']['integratorProductReference'];
            $mappedProducts[$sku] = json_encode($product);
        }

        return $mappedProducts;
    }
}
