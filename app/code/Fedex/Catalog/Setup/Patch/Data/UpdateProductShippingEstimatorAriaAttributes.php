<?php
declare(strict_types=1);
namespace Fedex\Catalog\Setup\Patch\Data;

use Fedex\Cms\Api\Cms\SimpleContentReader;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
class UpdateProductShippingEstimatorAriaAttributes implements DataPatchInterface
{
    const PAGE_SIZE = 200;

    /**
     * UpdateAttributeShippingEstimatorAttributes constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SimpleContentReader $contentReader
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductResourceModel $productResourceModel
     * @param EavSetupFactory $eavSetupFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private SimpleContentReader $contentReader,
        private ProductRepositoryInterface $productRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private ProductResourceModel $productResourceModel,
        private EavSetupFactory $eavSetupFactory,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [\Fedex\Catalog\Setup\Patch\Data\CreateProductAttributes::class];
    }

    /**
     * Apply patch
     *
     * @return DataPatchInterface|void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        try {
            $page = 0;
            $shippingEstimatorContent = $this->contentReader->getContent('attribute_shipping_estimator.html');
            $this->updateShippingEstimatorContent($shippingEstimatorContent);

            $shippingEstimatorContentAlert = $this->contentReader
                                                ->getContent('attribute_shipping_estimator_alert.html');
            $this->updateShippingEstimatorContentAlert($shippingEstimatorContentAlert);

            $this->searchCriteriaBuilder->setPageSize(self::PAGE_SIZE);

            while (true) {
                $page++;
                $products = $this->productRepository->getList(
                    $this->searchCriteriaBuilder
                        ->setCurrentPage($page)
                        ->create()
                );
                foreach ($products->getItems() as $product) {
                    $product->setShippingEstimatorContent($shippingEstimatorContent);
                    $product->setShippingEstimatorContentAlert($shippingEstimatorContentAlert);
                    $this->productResourceModel->saveAttribute($product, 'shipping_estimator_content');
                    $this->productResourceModel->saveAttribute($product, 'shipping_estimator_content_alert');
                }
                if ($products->getTotalCount() <= $page * self::PAGE_SIZE) {
                    break;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    private function updateShippingEstimatorContent($content)
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->updateAttribute(
            Product::ENTITY,
            'shipping_estimator_content',
            [
                'default_value' => $content,
            ]
        );
    }
    private function updateShippingEstimatorContentAlert($content)
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->updateAttribute(
            Product::ENTITY,
            'shipping_estimator_content_alert',
            [
                'default_value' => $content,
            ]
        );
    }
}
