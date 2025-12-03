<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SharedCatalogCustomization\Cron;

use Magento\Framework\App\ResourceConnection;
use Fedex\SharedCatalogCustomization\Helper\ManageCatalogItems;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;
use Magento\SharedCatalog\Api\ProductItemRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class CatalogSyncDeleteItemCron
{
    /**
     * CatalogSyncDeleteItem Cron constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param ManageCatalogItems $manageCatalogItemsHelper
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param Registry $registry
     * @param LoggerInterface $logger
     * @param ProductItemRepositoryInterface $itemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        protected ResourceConnection   $resourceConnection,
        protected ManageCatalogItems $manageCatalogItemsHelper,
        protected ProductRepositoryInterface $productRepositoryInterface,
        protected Registry $registry,
        protected LoggerInterface $logger,
        protected ProductItemRepositoryInterface $itemRepository,
        protected SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
    }

    /**
     * Check Unassigned category Product and Delete Item
     *
     * @return void
     */
    public function execute()
    {
        $productsIdsWithoutCategory = $this->getProductsWithoutCategory();

        if ($productsIdsWithoutCategory != false) {

            foreach ($productsIdsWithoutCategory as $key => $productData) {
                // check Product is exist in negotiable quote
                $isExistInNegotiable = $this
                    ->manageCatalogItemsHelper
                    ->checkNegotiableQuote($productData['entity_id']);
                if (!$isExistInNegotiable) {
                    $this->deleteItem($productData['entity_id'], $productData['sku']);
                }
            }
        }
    }

    /**
     * Check Unassigned category
     *
     * @return array|Boolean $productsWithoutCategory|false
     */
    public function getProductsWithoutCategory()
    {
        $productsWithoutCategory = null;

        try {
            //Get attribute id by code
            $attributeSetId = $this->manageCatalogItemsHelper
                ->getAttrSetId($this->manageCatalogItemsHelper::ATTRIBUTE_SET_NAME) ?? null;
            $resourceConnection = $this->resourceConnection->getConnection();
            $select = $resourceConnection->select();
            $query = $select->from(['cpe' => 'catalog_product_entity'], ['cpe.entity_id', 'cpe.sku'])
                ->where('cpe.attribute_set_id=?', $attributeSetId)
                ->where('cpe.entity_id not in (select distinct product_id from catalog_category_product)');
            $productsWithoutCategory = $resourceConnection->fetchAll($query);
            $this->logger->info(__METHOD__.':'.__LINE__.':Products retrieved successfully.');
        } catch (\Exception $exception) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$exception->getMessage());

            return false;
        }

        return  $productsWithoutCategory;
    }

    /**
     * Delete unassigned category product item
     *
     * @param int $productId
     * @param string $sku
     * @return void
     */
    public function deleteItem($productId, $sku)
    {
        $product = $this->productRepositoryInterface->getById($productId);

        try {
            $this->deleteSharedCatalogItem($sku, $productId);

            //Delete the product.
            $this->registry->register('isSecureArea', true);
            $this->productRepositoryInterface->delete($product);
            $this->registry->unregister('isSecureArea');
            $this->logger->info(__METHOD__.':'.__LINE__.':Item '.$productId.' has been deleted by automatic scheduler');
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':Product item '.$productId.' deletion an error '.$e->getMessage());
        }
    }

     /**
      * Clean up shared catalog items that depends on the product.
      *
      * @param string $sku
      * @param int $productId
      * @return $this
      */
    public function deleteSharedCatalogItem($sku, $productId)
    {

        try {
            $this->searchCriteriaBuilder
                ->addFilter(\Magento\SharedCatalog\Api\Data\ProductItemInterface::SKU, $sku);
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $items = $this->itemRepository->getList($searchCriteria)->getItems();
            foreach ($items as $item) {
                $this->itemRepository->delete($item);
                $this->logger->info(__METHOD__.':'.__LINE__.':Item '.$productId.' has been deleted by automatic scheduler from Shared Catalog.');
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':Product item  sku '.$sku.' Shared Catalog item deletion an error '.$e->getMessage());
        }
    }
}
