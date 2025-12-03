<?php

namespace Fedex\OptimizeProductinstance\Model;

use Fedex\OptimizeProductinstance\Api\OptimizeInstanceMessageInterface;
use Fedex\OptimizeProductinstance\Api\OptimizeInstanceSubscriberInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Catalog\Model\ProductFactory;
use Fedex\OptimizeProductinstance\Model\OrderCompressionFactory;
use Fedex\Delivery\Helper\Data as ProductDataHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;

class UpdateOrderItemInstanceSubscriber implements OptimizeInstanceSubscriberInterface
{
    public const EXPLORERS_OPTIMIZE_QUOTES_AND_ORDERS = "explorers_optimize_quotes_and_orders_within_14_months";

    /**
     * CleanUpdateOrderItemInstanceSubscriber constructor
     *
     * @param OrderFactory $orderFactory
     * @param ProductFactory $productFactory
     * @param OrderCompressionFactory $orderCompressionFactory
     * @param ProductDataHelper $productDataHelper
     * @param ToggleConfig $toggleConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected OrderFactory $orderFactory,
        protected ProductFactory $productFactory,
        protected OrderCompressionFactory $orderCompressionFactory,
        protected ProductDataHelper $productDataHelper,
        protected ToggleConfig $toggleConfig,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function processMessage(OptimizeInstanceMessageInterface $message)
    {
        try {
            if ($this->toggleConfig->getToggleConfigValue(self::EXPLORERS_OPTIMIZE_QUOTES_AND_ORDERS)) {
                $tempTableOrderId = (int) $message->getMessage();
                $orderCompressionData = $this->orderCompressionFactory->create()->load($tempTableOrderId);
                $orderId = $orderCompressionData->getOrderId();
                $order = $this->orderFactory->create()->load($orderId);
                $items = $order->getAllItems();
                $tempOrderStatus = 0;
                foreach ($items as $item) {
                    
                    if ($item->getMiraklShopId()) {
                        continue;
                    }
                    $productId = $item->getProductId();
                    $product = $this->productFactory->create()->load($productId);
                    $productAttributeSetName = $this->productDataHelper->getProductAttributeName($product->getAttributeSetId());
                    if ($productAttributeSetName == "FXOPrintProducts") {
                        $isUpdatedInstanceFormat = $this->updateOrderItemsOptionFormat($item);
                        if ($isUpdatedInstanceFormat != false) {
                            $tempOrderStatus = 1;
                            $this->logger->info("Updated product Instance queue successfully for item :" .$item->getId());
                            $item->setData('product_options', $isUpdatedInstanceFormat);
                            $item->save();
                        }
                    }
                }
                $orderCompressionData->setStatus($tempOrderStatus);
                $orderCompressionData->save();
            }
        } catch (\Exception $e) {
            $this->logger->error(
                "Error in processing clean product Instance queue for the Order Id:" . $orderId .'-' . var_export($e->getMessage(), true)
            );
        }
    }

    /**
     * Update Orde item data
     *
     * @param object $orderItem
     */
    public function updateOrderItemsOptionFormat($orderItem)
    {
        $productOptionData = $orderItem->getData('product_options');
        $newFormatInstanceData = [];

        if (isset($productOptionData['info_buyRequest']) && !array_key_exists('fxoMenuId', $productOptionData['info_buyRequest'])) {
            $externalProduct = $productOptionData['info_buyRequest']['external_prod'][0];

            $fxoProductData = $externalProduct["fxo_product"] ?? '';
            $fxoProductData = json_decode($fxoProductData, true);

            // Prepare New Product Instance Format
            $newFormatInstanceData['external_prod'][0]['productionContentAssociations'] = $externalProduct['productionContentAssociations'] ?? [];
            $newFormatInstanceData['external_prod'][0]['userProductName'] = $externalProduct['userProductName'] ?? '';
            $newFormatInstanceData['external_prod'][0]['id'] = $externalProduct['id'] ?? '';
            $newFormatInstanceData['external_prod'][0]['version'] = $externalProduct['version'] ?? '';
            $newFormatInstanceData['external_prod'][0]['name'] = $externalProduct['name'] ?? '';
            $newFormatInstanceData['external_prod'][0]['qty'] = $externalProduct['qty'] ?? '';
            $newFormatInstanceData['external_prod'][0]['priceable'] = $externalProduct['priceable'] ?? '';
            $newFormatInstanceData['external_prod'][0]['instanceId'] = $externalProduct['instanceId'] ?? '';
            $newFormatInstanceData['external_prod'][0]['proofRequired'] = $externalProduct['proofRequired'] ?? '';
            $newFormatInstanceData['external_prod'][0]['isOutSourced'] = $externalProduct['isOutSourced'] ?? '';
            $newFormatInstanceData['external_prod'][0]['features'] = $externalProduct['features'] ?? [];
            $newFormatInstanceData['external_prod'][0]['pageExceptions'] = $externalProduct['pageExceptions'] ?? [];
            $newFormatInstanceData['external_prod'][0]['contentAssociations'] = $externalProduct['contentAssociations'] ?? [];
            $newFormatInstanceData['external_prod'][0]['properties'] = $externalProduct['properties'] ?? [];
            $newFormatInstanceData['external_prod'][0]['preview_url'] = $externalProduct['preview_url'] ?? '';
            $newFormatInstanceData['external_prod'][0]['isEditable'] = $externalProduct['isEditable'] ?? false;
            $newFormatInstanceData['external_prod'][0]['isEdited'] = $externalProduct['isEdited'] ?? false;
            $newFormatInstanceData['external_prod'][0]['fxoMenuId'] = $fxoProductData['fxoMenuId'] ?? '';
            $newFormatInstanceData['productConfig'] = $fxoProductData ? $fxoProductData['fxoProductInstance']['productConfig'] : [];
            if ($fxoProductData) {
                unset($newFormatInstanceData['productConfig']['product']);
            }
            $newFormatInstanceData['productRateTotal'] = $fxoProductData ? $fxoProductData['fxoProductInstance']['productRateTotal'] : [];
            $newFormatInstanceData['quantityChoices'] = $fxoProductData ? $fxoProductData['fxoProductInstance']['quantityChoices'] : [];
            $newFormatInstanceData['fileManagementState'] = $fxoProductData ? $fxoProductData['fxoProductInstance']['fileManagementState'] : [];
            $newFormatInstanceData['fxoMenuId'] = $fxoProductData ? $fxoProductData['fxoMenuId'] : '';
            $productOptionData['info_buyRequest'] = $newFormatInstanceData;

            return $productOptionData;
        } else {
            return false;
        }
    }
}
