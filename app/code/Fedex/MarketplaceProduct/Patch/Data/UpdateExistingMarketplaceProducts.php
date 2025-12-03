<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Patch\Data;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

class UpdateExistingMarketplaceProducts implements DataPatchInterface
{
    public function __construct(
        protected ModuleDataSetupInterface    $moduleDataSetup,
        protected EavSetupFactory             $eavSetupFactory,
        private AttributeSetCollectionFactory $_attributeSetCollection,
        private LoggerInterface               $logger,
        private CollectionFactory             $productCollectionFactory,
        private ProductRepositoryInterface    $productRepository,
        private State                         $state
    ) {
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function apply()
    {
        $this->state->setAreaCode('adminhtml');

        $this->moduleDataSetup->getConnection()->startSetup();

        $attributeSetName = 'FXONonCustomizableProducts';

        $attributeSetCollection = $this->_attributeSetCollection->create()
            ->addFieldToSelect('attribute_set_id')
            ->addFieldToFilter('attribute_set_name', $attributeSetName)
            ->getFirstItem()
            ->toArray();

        $attributeSetId = $attributeSetCollection['attribute_set_id'];

        if ($attributeSetId) {

            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToSelect('entity_id')
                ->addFieldToFilter('attribute_set_id', ['neq' => $attributeSetId])
                ->addFieldToFilter('mirakl_mcm_product_id', ['neq' => ''])
                ->addFieldToFilter('mirakl_mcm_product_id', ['notnull' => true]);

            foreach ($productCollection as $product) {
                $product->setData('customizable_product', 1);
                try {
                    $this->productRepository->save($product);
                } catch (\Exception $e) {
                    $messageToLog = 'UpdateExistingMarketplaceProducts: Error updating product : ' . $e->getMessage() . ' Product Sku: ' . $product->getSku();
                    $this->logger->error($messageToLog);
                }
            }
        }
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
