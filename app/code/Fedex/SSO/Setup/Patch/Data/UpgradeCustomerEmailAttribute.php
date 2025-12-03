<?php
declare(strict_types=1);

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Setup\Patch\Data;

use Zend_Validate_Exception;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;

/**
 * UpgradeCustomerAttribute Patch class
 */
class UpgradeCustomerEmailAttribute implements DataPatchInterface
{
    /**
     * AddressAttribute constructor.
     *
     * @param Config $eavConfig
     * @param EavSetupFactory $eavSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     */
    public function __construct(
        private Config $eavConfig,
        private EavSetupFactory $eavSetupFactory,
        private AttributeSetFactory $attributeSetFactory
    )
    {
    }

    /**
     * Get Dependencies
     *
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Create customer account for seconday_email
     *
     * @return void
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     */
    public function apply(): void
    {
        $eavSetup = $this->eavSetupFactory->create();

        $customerEntity = $this->eavConfig->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $eavSetup->addAttribute('customer', 'secondary_email', [
            'type' => 'varchar',
            'label' => 'Secondary Email',
            'input' => 'text',
            'source' => '',
            'required' => false,
            'visible' => true,
            'unique' => false,
            'position' => 500,
            'sort_order' => 500,
            'user_defined' => false,
            'system' => false,
            'backend' => '',
            'source' => Boolean::class,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'is_used_in_grid' => true,
            'apply_to' => 'simple,grouped,bundle,configurable,virtual',
            'is_visible_in_grid' => true,
            'is_filterable_in_grid' => true,
            'visible' => true,
            'is_html_allowed_on_front' => false,
            'visible_on_front' => true
        ]);

        $customerAttribute = $this->eavConfig->getAttribute('customer', 'secondary_email');

        $customerAttribute->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => ['adminhtml_customer']
        ]);
        $customerAttribute->save();
    }

    /**
     * Get Aliases
     *
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
