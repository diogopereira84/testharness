<?php
declare(strict_types=1);
namespace Fedex\Catalog\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Psr\Log\LoggerInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * @codeCoverageIgnore
 */
class UpdateAttributeShippingEstimatorAttributesAltText implements DataPatchInterface
{
    const CATALOG_PRODUCT_ENTITY_TEXT_TABLE = 'catalog_product_entity_text';
    const SHIPPING_ESTIMATE_CONTENT_CODE = 'shipping_estimator_content';

    /**
     * UpdateAttributeShippingEstimatorAttributes constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ProductResourceModel $productResourceModel
     * @param LoggerInterface $logger ,
     * @param AttributeRepositoryInterface $attributeRepositoryInterface
     * @param ConfigInterface $resourceConfig
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        private ModuleDataSetupInterface     $moduleDataSetup,
        private ProductResourceModel         $productResourceModel,
        private LoggerInterface              $logger,
        protected AttributeRepositoryInterface $attributeRepositoryInterface,
        private ConfigInterface              $resourceConfig,
        private ProductRepositoryInterface   $productRepository
    )
    {
    }

    /**
     * @inheritdoc
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
             $productIds = $this->getProductsIds();
            /**
             * Estimate shipping images  replacement array with alt tag
             */
            $estimateShippingReplacementArray = $this->getEstShippingReplacementArray();
            foreach ($productIds as $productId) {
                $product = $this->productRepository->getById($productId);
                $currentShippingEstimatorContentValue = $product->getShippingEstimatorContent();
                if (!empty($currentShippingEstimatorContentValue)) {
                    $shippingEstimatorContent = strtr($currentShippingEstimatorContentValue,
                    $estimateShippingReplacementArray);
                    $product->setShippingEstimatorContent($shippingEstimatorContent);
                    $this->productResourceModel->saveAttribute($product, static::SHIPPING_ESTIMATE_CONTENT_CODE);
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

    /**
     * Get products ids
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getProductsIds(): array
    {
            $shippingEstimatorContentAttributeId = $this->attributeRepositoryInterface->get(Product::ENTITY,
             static::SHIPPING_ESTIMATE_CONTENT_CODE)->getAttributeId();
            $connection = $this->resourceConfig->getConnection();
            $select = $connection->select()->from(
                $this->resourceConfig->getTable(static::CATALOG_PRODUCT_ENTITY_TEXT_TABLE),
                ['row_id']
            )->where(
                'attribute_id = ?',
                $shippingEstimatorContentAttributeId
            );
            return  array_column($connection->fetchAll($select), 'row_id');
    }

    /**
     * Get replacement array of alt text
     * @return string[]
     */
    private function getEstShippingReplacementArray(): array
    {
        return [
            '<img class="img-auto mr-5 valign-text-bottom" src="{{view url="images/store.png"}}">'
            => '<img class="img-auto mr-5 valign-text-bottom" src="{{view url="images/store.png"}}"
              alt="Gray Store icon" >',
            '<img class="img-auto mr-5 valign-text-bottom" src="{{view url="images/delivery.png"}}">'
            => ' <img class="img-auto mr-5 valign-text-bottom" src="{{view url="images/delivery.png"}}"
             alt="Gray delivery truck icon" >',
            '<img class="img-auto tooltip-toggle" src="{{view url="images/info.png"}}">'
            => '<img class="img-auto tooltip-toggle" src="{{view url="images/info.png"}}" alt="Blue tooltip icon">',
            '<img class="img-auto" src="{{view url="images/info.png"}}">'
            => '<img class="img-auto"  src="{{view url="images/info.png"}}" alt="Blue tooltip icon">',
            '<img class="img-auto mt-30" src="{{view url="images/shippingcost.png"}}">'
            => '<img class="img-auto mt-30" src="{{view url="images/shippingcost.png"}}"
             alt="Shipping calculator with shipping box icon" >',
            '&lt;img class="img-auto mr-5 valign-text-bottom"
             src="{{view url="images/store.png"}}"&gt;' => '&lt;img class="img-auto mr-5 valign-text-bottom"
              alt="Gray Store icon"  src="{{view url="images/store.png"}}"&gt;',
            '&lt;img class="img-auto mr-5 valign-text-bottom"
             src="{{view url="images/delivery.png"}}"&gt;' => '&lt;img class="img-auto mr-5 valign-text-bottom"
              alt="Gray delivery truck icon" src="{{view url="images/delivery.png"}}"&gt;',
            '&lt;img class="img-auto tooltip-toggle"
             src="{{view url="images/info.png"}}"&gt;' => '&lt;img class="img-auto tooltip-toggle"
              alt="Blue tooltip icon" src="{{view url="images/info.png"}}"&gt;',
            '&lt;img class="img-auto"
             src="{{view url="images/info.png"}}"&gt;' => '&lt;img class="img-auto"
              alt="Blue tooltip icon" src="{{view url="images/info.png"}}"&gt;',
            '&lt;img class="img-auto mt-30"
             src="{{view url="images/shippingcost.png"}}"&gt;' => '&lt;img class="img-auto mt-30"
              alt="Shipping calculator with shipping box icon"
               src="{{view url="images/shippingcost.png"}}"&gt;'
        ];
    }
}
