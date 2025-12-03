<?php

/**
 * @copyright Copyright (c) 2023 Fedex.
 */

declare(strict_types=1);

namespace Fedex\CatalogMvp\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class PublishUnpublishCategory implements DataPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory
    )
    {
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(Category::ENTITY, 'is_publish', [
            'type'     => 'int',
            'label'    => 'Publish Category',
            'input'    => 'boolean',
            'source'   => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
            'visible'  => true,
            'default'  => '1',
            'required' => false,
            'global'   => ScopedAttributeInterface::SCOPE_GLOBAL,
            'group'    => 'Currently Active',
          ]
        );
        $this->moduleDataSetup->endSetup();
    }

    public function getAliases()
    {
        return $this->getDependencies();
    }

    public static function getDependencies()
    {
        return [];
    }
}
