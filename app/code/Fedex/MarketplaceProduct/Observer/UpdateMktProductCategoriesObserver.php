<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Observer;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class UpdateMktProductCategoriesObserver implements ObserverInterface
{
    /**
     * Xpath epro categories.
     */
    private const XPATH_EPRO_PRINT = 'ondemand_setting/category_setting/epro_print';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CategoryLinkManagementInterface $categoryLinkManagement
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private CategoryLinkManagementInterface $categoryLinkManagement,
        private CategoryRepositoryInterface $categoryRepository,
        private ResourceConnection $resourceConnection,
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $event      = $observer->getEvent();
        $configPath = $event->getChangedPaths();

        if (in_array(self::XPATH_EPRO_PRINT, $configPath)) {
            $connection = $this->resourceConnection->getConnection();
            $tableName  = $this->resourceConnection->getTableName('catalog_product_entity');

            $select = $connection->select()
                ->from($tableName, ['entity_id', 'sku'])
                ->where('mirakl_mcm_product_id IS NOT NULL');

            $products                       = $connection->fetchAll($select);
            $b2bDefaultPrintProductCategory = $this->getEproPrintCategoryId();

            foreach ($products as $product) {
                $sku                            = $product['sku'];
                $existingCategories             = $this->getProductCategoryIds($product['entity_id']);
                $newCategoryIds                 = [$b2bDefaultPrintProductCategory];
                $mergedCategoryIds              = array_unique(array_merge($newCategoryIds, $existingCategories));

                if (!empty($mergedCategoryIds)) {
                    $this->categoryLinkManagement->assignProductToCategories($sku, $mergedCategoryIds);
                }
            }
        }
    }

    /**
     * Get Epro print category ID.
     *
     * @return mixed
     */
    public function getEproPrintCategoryId(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_EPRO_PRINT,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $productId
     * @return array
     */
    public function getProductCategoryIds($productId): array
    {
        $connection  = $this->resourceConnection->getConnection();
        $tableName   = $this->resourceConnection->getTableName('catalog_category_product');
        $categoryIds = [];

        $select = $connection->select()
            ->from($tableName, ['category_id'])
            ->where('product_id = ?', $productId);

        $categories = $connection->fetchAll($select);

        foreach ($categories as $category) {
            $categoryIds[] = $category['category_id'];
        }
        return $categoryIds;
    }
}
