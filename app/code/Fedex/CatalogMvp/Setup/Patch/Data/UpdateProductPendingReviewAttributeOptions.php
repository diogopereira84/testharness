<?php
/**
 * Fedex_CatalogMvp
 *
 * @category   Fedex
 * @package    Fedex_CatalogMvp
 * @author     Attri Kumar
 * @email      attri.kumar.osv@fedex.com
 * @copyright  © FedEx, Inc. All rights reserved.
 */

declare(strict_types=1);

namespace Fedex\CatalogMvp\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

class UpdateProductPendingReviewAttributeOptions implements DataPatchInterface
{
    /**
     * Initialize dependencies.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            \Fedex\CatalogMvp\Setup\Patch\Data\CatalogPendingReviewAttribute::class
        ];
    }
 
    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        if ($eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'is_pending_review')) {
            try {
                $optionsArr = ['0', '1', '2', '3'];

                $option = [];
                $option['attribute_id'] = $eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'is_pending_review');
                foreach($optionsArr as $value) {
                    $option['values'][] = $value;
                }

                $eavSetup->addAttributeOption($option);
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
            }
        }

        $this->moduleDataSetup->endSetup();
    }
}
