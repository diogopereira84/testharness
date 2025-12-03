<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Plugin;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfiguration\Model\LegacyStockItem\CacheStorage;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;

class AdaptUpdateStockStatusBySkuPlugin
{
    /**
     * @param SetDataToLegacyStockStatus $setDataToLegacyStockStatus
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param CacheStorage $legacyStockItemCacheStorage
     */
    public function __construct(
        private SetDataToLegacyStockStatus $setDataToLegacyStockStatus,
        private GetProductTypesBySkusInterface $getProductTypesBySkus,
        private IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        private GetStockItemConfigurationInterface $getStockItemConfiguration,
        private CacheStorage $legacyStockItemCacheStorage
    )
    {
    }

    /**
     * Replicate stock status information to legacy stock.
     *
     * @param StockRegistryInterface $subject
     * @param int $itemId
     * @param string $productSku
     * @param StockItemInterface $stockItem
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterUpdateStockItemBySku(
        StockRegistryInterface $subject,
        $itemId,
        string $productSku,
        StockItemInterface $stockItem
    ) {
        // Remove cache to get updated legacy stock item on the next request.
        $this->legacyStockItemCacheStorage->delete($productSku);        

        $stockItemConfiguration = $this->getStockItemConfiguration->execute($productSku, Stock::DEFAULT_STOCK_ID);

        if ($stockItemConfiguration->isManageStock() === false
            || $stockItemConfiguration->isUseConfigManageStock() === false
        ) {
            $this->setDataToLegacyStockStatus->execute($productSku, (float)$stockItem->getQty(), 1);
            // @codeCoverageIgnoreStart  
        } else {
                      
            $productType = $this->getProductTypesBySkus->execute([$productSku])[$productSku];
            if ($this->isSourceItemManagementAllowedForProductType->execute($productType)
                && $stockItem->getQty() !== null
            ) {
                $this->setDataToLegacyStockStatus->execute(
                    $productSku,
                    (float)$stockItem->getQty(),
                    $stockItem->getIsInStock()
                );
            }
           
        }
         // @codeCoverageIgnoreEnd
        return $itemId;
    }
}
