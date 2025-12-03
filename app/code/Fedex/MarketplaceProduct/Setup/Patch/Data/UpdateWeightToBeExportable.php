<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Manuel Rosario <manuel.rosario.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

use Fedex\Cms\Api\Cms\SimpleContentReader;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;

class UpdateWeightToBeExportable implements DataPatchInterface
{
    private const WEIGHT_ATTRIBUTE = 'weight';

    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private LoggerInterface $logger,
        private Attribute $eavAttribute
    ) {
    }

    /**
     * Apply patch
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        try {
            $attrProduct = $this->eavAttribute->getIdByCode(Product::ENTITY, self::WEIGHT_ATTRIBUTE);
            if (!empty($attrProduct)) {
                $eavSetup->updateAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    self::WEIGHT_ATTRIBUTE,
                    [
                        'mirakl_is_exportable' => true
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return $this->getDependencies();
    }


}
