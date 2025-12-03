<?php
declare(strict_types=1);
namespace Fedex\Catalog\Setup\Patch\Data;

use Fedex\Cms\Api\Cms\SimpleContentReader;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class UpdateShippingEstimateAttributeDefaultValue
 */
/**
 * @codeCoverageIgnore
 */
class UpdateShippingEstimateAttributeDefaultValue implements DataPatchInterface
{
    const ATTRIBUTE_CODE = 'shipping_estimator_content';
    const FIELD = 'default_value';

    /**
     * UpdateAttributeShippingEstimatorAttributes constructor.
     * @param EavSetup $eavSetup
     * @param SimpleContentReader $contentReader
     */
    public function __construct(
        private EavSetup $eavSetup,
        private SimpleContentReader $contentReader
    )
    {
    }
    /**
     * Get array of patches that have to be executed prior to this.
     * @return string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases()
    {
        return $this->getDependencies();
    }

    /**
     * Run code inside patch
     * If code fails, patch must be reverted, in case when we are speaking about schema - than under revert
     * means run PatchInterface::revert()
     *
     * If we speak about data, under revert means: $transaction->rollback()
     *
     * @return $this
     */
    public function apply()
    {

        $this->eavSetup->updateAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            self::ATTRIBUTE_CODE,
            self::FIELD,
            $this->contentReader->getContent('attribute_shipping_estimator.html')
        );
        return $this;
    }
}
