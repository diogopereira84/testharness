<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceProduct
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;

class UpdateNavitorIsCategoryAttributeToCategoryPunchout implements DataPatchInterface
{
    private const OLD_ATTRIBUTE_CODE = 'navitor_is_category';
    private const NEW_ATTRIBUTE_CODE = 'category_punchout';

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
            $attrProduct = $this->eavAttribute->getIdByCode(Product::ENTITY, self::OLD_ATTRIBUTE_CODE);
            if (!empty($attrProduct)) {
                $eavSetup->updateAttribute(
                    Product::ENTITY,
                    self::OLD_ATTRIBUTE_CODE,
                    'attribute_code',
                    self::NEW_ATTRIBUTE_CODE
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
            AddNavitorIsCategoryAttribute::class
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
