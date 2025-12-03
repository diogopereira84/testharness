<?php
/**
 * Fedex_CatalogMvp
 *
 * @category   Fedex
 * @package    Fedex_CatalogMvp
 * @author     Manish Chaubey
 * @email      manish.chaubey.osv@fedex.com
 * @copyright  © FedEx, Inc. All rights reserved.
 */

declare(strict_types=1);

namespace Fedex\CatalogMvp\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Fedex\CatalogMvp\Model\Source\PendingReviewStatuses;
use Psr\Log\LoggerInterface;

class CatalogPendingReviewAttribute implements DataPatchInterface
{
    /**
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
        return [];
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
                $eavSetup->removeAttribute(Product::ENTITY, 'is_pending_review');
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        $eavSetup->addAttribute(
            Product::ENTITY,
            'is_pending_review',
            [
                'group' => 'General',
                'type' => 'int',
                'label' => 'Is Pending Review',
                'input' => 'select',
                'source' => PendingReviewStatuses::class,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => false,
                'required' => false,
                'user_defined' => true,
                'default' => 0,
                'sort_order' => 100,
                'searchable' => true,
                'filterable' => true,
                'comparable' => true,
                'visible_on_front' => true,
                'filterable_in_search' => true,
                'used_in_product_listing' => true,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);

        try {
            $eavSetup->getAttributeSetId(
                Product::ENTITY,
                'PrintOnDemand'
            );
        } catch (\Exception $e) {
            $eavSetup->addAttributeSet(Product::ENTITY, 'PrintOnDemand');
        }

        $eavSetup->addAttributeToGroup(
            $entityTypeId,
            'PrintOnDemand',
            'General',
            'is_pending_review',
            23
        );

        $this->moduleDataSetup->endSetup();
    }
}
