<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Psr\Log\LoggerInterface;

class UpdateExistingProducts implements DataPatchInterface
{
    /**
     * @param CollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param EavSetupFactory $eavSetupFactory
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param LoggerInterface $logger
     * @param State $state
     */
    public function __construct(
        private CollectionFactory $productCollectionFactory,
        private ProductRepositoryInterface $productRepository,
        protected EavSetupFactory $eavSetupFactory,
        protected AttributeSetRepositoryInterface $attributeSetRepository,
        private LoggerInterface $logger,
        private State $state
    ) {
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function apply()
    {
        $this->state->setAreaCode('adminhtml');

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToSelect(['attribute_set_id', 'product_attribute_sets_id'])
            ->addFieldToFilter('product_attribute_sets_id', ['null' => true]);

        $messagesList = [];
        foreach ($productCollection as $product) {

            try {
                $attributeSetId = (int) $product->getAttributeSetId();

                if ($attributeSetId) {
                    $product->setProductAttributeSetsId($attributeSetId);
                    $this->productRepository->save($product);
                }
            } catch (\Exception $e) {
                $messageToLog = 'Error updating existing products: ' . $e->getMessage() . ' Product Sku: ' . $product->getSku();
                $this->logger->error($messageToLog);

                $messagesList[] = $messageToLog;
            }
        }

        return $messagesList;
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
