<?php
declare(strict_types=1);

namespace Fedex\AllPrintProducts\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\State as AppState;
use Magento\Framework\App\Area;
use Psr\Log\LoggerInterface;
use Exception;

class UpdateProductsPublishedAttribute implements DataPatchInterface, PatchVersionInterface
{

    /**
     * UpdateProductsPublishedAttribute constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory $productCollectionFactory
     * @param AppState $appState
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ModuleDataSetupInterface        $moduleDataSetup,
        private ProductRepositoryInterface      $productRepository,
        private CollectionFactory               $productCollectionFactory,
        private AppState                        $appState,
        private LoggerInterface                 $logger

    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * {@inheritdoc}
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function apply(): self
    {
        $this->appState->setAreaCode(Area::AREA_ADMINHTML);

        $this->moduleDataSetup->getConnection()->startSetup();

        $attributeCode = 'published';
        $newValue = 1;

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToSelect('entity_id')
            ->addFieldToFilter('mirakl_mcm_product_id', ['neq' => ''])
            ->addFieldToFilter('mirakl_mcm_product_id', ['notnull' => true]);


        if (empty($productCollection)) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' No products found to update.');
            $this->moduleDataSetup->getConnection()->endSetup();
            return $this;
        }

        foreach ($productCollection as $product) {
            try {
                $product->setData($attributeCode, $newValue);
                $this->productRepository->save($product);
            } catch (Exception $exception) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error updating product ID ' . $product->getId() . ': ' . $exception->getMessage());
            }
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }
}
