<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceProduct
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Niket Kanoi
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

use Exception;
use Magento\Catalog\Api\Data\EavAttributeInterface;
use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

class CreateProductDetailCategoryAttribute implements DataPatchInterface
{
    public const ADDITIONAL_INFORMATION_ATTRIBUTE_CODE = "product_additional_information";

    public function __construct(
        protected ModuleDataSetupInterface $moduleDataSetup,
        protected EavSetupFactory          $eavSetupFactory,
        protected LoggerInterface          $logger
    ) {
    }

    public function apply(): static
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        try {
            $eavSetup->addAttribute(
                Category::ENTITY,
                static::ADDITIONAL_INFORMATION_ATTRIBUTE_CODE,
                [
                    'type' => 'varchar',
                    'label' => __('Product Detail Page Additional Information'),
                    'input' => 'text',
                    'required' => false,
                    'sort_order' => 200,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Design',
                    EavAttributeInterface::USED_IN_PRODUCT_LISTING => 1,
                    EavAttributeInterface::IS_VISIBLE_ON_FRONT => 1,
                    'mirakl_is_exportable' => false
                ]
            );
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return $this->getDependencies();
    }
}
