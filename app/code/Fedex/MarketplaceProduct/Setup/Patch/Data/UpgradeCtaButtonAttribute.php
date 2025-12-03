<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Psr\Log\LoggerInterface;

class UpgradeCtaButtonAttribute implements DataPatchInterface, PatchRevertableInterface
{
    private const CTA_VALUE_ATTRIBUTE = 'cta_value';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory,
        private readonly LoggerInterface $logger
    ) {

    }

    /**
     * Apply the upgrade patch
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $eavSetupFactoryObject = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        try {
            $attribute = $eavSetupFactoryObject->getAttribute(Product::ENTITY, self::CTA_VALUE_ATTRIBUTE);
            if (isset($attribute['apply_to'])) {
                $applyTo = $attribute['apply_to'];
                if (!in_array('configurable', explode(',', $applyTo))) {
                    $applyTo = $applyTo . ',configurable';
                    $eavSetupFactoryObject->updateAttribute(
                        Product::ENTITY,
                        self::CTA_VALUE_ATTRIBUTE,
                        'apply_to',
                        $applyTo
                    );
                    $this->logger->info('Updated apply_to attribute for cta_value to include configurable products.');
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error updating cta_value attribute: ' . $e->getMessage());
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * Revert the patch
     */
    public function revert()
    {
        $this->moduleDataSetup->startSetup();
        $eavSetupFactoryObject = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        try {
            $attribute = $eavSetupFactoryObject->getAttribute(Product::ENTITY, self::CTA_VALUE_ATTRIBUTE);
            if (isset($attribute['apply_to'])) {
                $applyTo = $attribute['apply_to'];
                if (strpos($applyTo, 'configurable') !== false) {
                    $applyTo = str_replace(',configurable', '', $applyTo);
                    $eavSetupFactoryObject->updateAttribute(
                        Product::ENTITY,
                        self::CTA_VALUE_ATTRIBUTE,
                        'apply_to',
                        $applyTo
                    );
                    $this->logger->info('Reverted apply_to attribute for cta_value to simple products only.');
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error reverting cta_value attribute: ' . $e->getMessage());
        }

        $this->moduleDataSetup->endSetup();
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
    public static function getDependencies()
    {
        return [];
    }
}
