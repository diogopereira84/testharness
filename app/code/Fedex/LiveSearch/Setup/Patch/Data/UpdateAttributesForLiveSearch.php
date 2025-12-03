<?php
declare(strict_types=1);

namespace Fedex\LiveSearch\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateAttributesForLiveSearch implements DataPatchInterface
{
    private const UPLOAD_FILE_ATTRIBUTE_NAME = 'upload_file_search_action';
    private const CUSTOMIZE_ATTRIBUTE_NAME = 'customize_search_action';

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $eavSetupFactoryObj */
        $eavSetupFactoryObj = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetupFactoryObj->updateAttribute(
            Product::ENTITY,
            self::UPLOAD_FILE_ATTRIBUTE_NAME,
            'default_value',
            1
        );

        $eavSetupFactoryObj->updateAttribute(
            Product::ENTITY,
            self::CUSTOMIZE_ATTRIBUTE_NAME,
            'default_value',
            1
        );

        $this->moduleDataSetup->getConnection()->endSetup();

    }
}
