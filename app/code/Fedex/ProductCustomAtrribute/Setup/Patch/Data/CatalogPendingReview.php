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

namespace Fedex\ProductCustomAtrribute\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
 
class CatalogPendingReview implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface
     * @param EavSetupFactory
     */
    public function __construct(
        readonly private ModuleDataSetupInterface $moduleDataSetup,
        readonly private EavSetupFactory $eavSetupFactory
    ) {
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

        $eavSetup->addAttribute(
            Product::ENTITY,
            'pending_review',
            [
                'type' => 'int',
                'label' => 'Pending Review',
                'input' => 'boolean',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'default' => 0,
                'group' => 'General',
                'sort_order' => 100,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => false,
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
            'pending_review',
            23
        );

        $this->moduleDataSetup->endSetup();
    }
}
