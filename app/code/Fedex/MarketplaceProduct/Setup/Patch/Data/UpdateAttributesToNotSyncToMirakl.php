<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Manuel Rosario <manuel.rosario.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

use Fedex\Catalog\Setup\Patch\Data\CreateAboutThisProductAttributes;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;

class UpdateAttributesToNotSyncToMirakl implements DataPatchInterface
{

    private const MAP_SKU_ATTRIBUTE = 'map_sku';

    private const META_KEYWORD = 'meta_keyword';

    private const SPECIFIC_PRODUCT_INFO_NEW = 'shipping_estimator_content_alert_new';

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
            $attrProduct = $this->eavAttribute->getIdByCode(Product::ENTITY, self::MAP_SKU_ATTRIBUTE);
            if (!empty($attrProduct)) {
                $eavSetup->updateAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    self::MAP_SKU_ATTRIBUTE,
                    [
                        'mirakl_is_exportable' => false
                    ]
                );
            }


            $attrProduct = $this->eavAttribute->getIdByCode(Product::ENTITY, self::META_KEYWORD);
            if (!empty($attrProduct)) {
                $eavSetup->updateAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    self::META_KEYWORD,
                    [
                        'mirakl_is_exportable' => false
                    ]
                );
            }

            $attrProduct = $this->eavAttribute->getIdByCode(Product::ENTITY, self::SPECIFIC_PRODUCT_INFO_NEW);
            if (!empty($attrProduct)) {
                $eavSetup->updateAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    self::SPECIFIC_PRODUCT_INFO_NEW,
                    [
                        'mirakl_is_exportable' => false
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
            UpdateMapSkuAttribute::class,
            CreateAboutThisProductAttributes::class
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
