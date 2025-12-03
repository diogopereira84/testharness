<?php
/**
 * @category  Fedex
 * @package   Fedex_EnvironmentManager
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class FixUnableToLoadTheme implements DataPatchInterface
{
    /**
     * Old theme path value
     */
    private const OLD_THEME_PATH = 'Fedex/poc';

    /**
     * New theme path value
     */
    private const NEW_THEME_PATH = 'Fedex/office';

    /**
     * Product entity varchar table name
     */
    private const CATALOG_PRODUCT_ENTITY_VARCHAR = 'catalog_product_entity_varchar';

    /**
     * Product attribute type id
     */
    private const PRODUCT_ATTRIBUTE_TYPE_ID = 4;

    /**
     * Category entity varchar table name
     */
    private const CATALOG_CATEGORY_ENTITY_VARCHAR = 'catalog_category_entity_varchar';

    /**
     * Category attribute type id
     */
    private const CATEGORY_ATTRIBUTE_TYPE_ID = 3;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $oldTheme = $this->moduleDataSetup->getConnection()->fetchRow(
            $this->moduleDataSetup->getConnection()->select()
                ->from($this->moduleDataSetup->getTable('theme'), ['theme_id'])
                ->where('theme_path = ?', self::OLD_THEME_PATH)
        );

        $newTheme = $this->moduleDataSetup->getConnection()->fetchRow(
            $this->moduleDataSetup->getConnection()->select()
                ->from($this->moduleDataSetup->getTable('theme'), ['theme_id'])
                ->where('theme_path = ?', self::NEW_THEME_PATH)
        );

        if (!isset($newTheme['theme_id']) || !isset($oldTheme['theme_id'])) {
            return $this;
        }

        // update layout_link table to use new theme
        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable('layout_link'),
            ['theme_id' => $newTheme['theme_id']],
            ['theme_id = ?' => $oldTheme['theme_id']]
        );

        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable('cms_page'),
            ['custom_theme' => self::NEW_THEME_PATH],
            ['custom_theme = ?' => self::OLD_THEME_PATH]
        );

        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable('widget_instance'),
            ['theme_id' => $newTheme['theme_id']],
            ['theme_id = ?' => $oldTheme['theme_id']]
        );

        $customDesignProductAttribute = $this->moduleDataSetup->getConnection()->fetchRow(
            $this->moduleDataSetup->getConnection()->select()
                ->from($this->moduleDataSetup->getTable('eav_attribute'), ['attribute_id'])
                ->where('attribute_code = ?', 'custom_design')
                ->where('entity_type_id = ?', self::PRODUCT_ATTRIBUTE_TYPE_ID)
        );

        $customDesignCategoryAttribute = $this->moduleDataSetup->getConnection()->fetchRow(
            $this->moduleDataSetup->getConnection()->select()
                ->from($this->moduleDataSetup->getTable('eav_attribute'), ['attribute_id'])
                ->where('attribute_code = ?', 'custom_design')
                ->where('entity_type_id = ?', self::CATEGORY_ATTRIBUTE_TYPE_ID)
        );

        if (!isset($customDesignProductAttribute['attribute_id'])
            || !isset($customDesignCategoryAttribute['attribute_id'])) {
            return $this;
        }

        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable(self::CATALOG_PRODUCT_ENTITY_VARCHAR),
            ['value' => $newTheme['theme_id']],
            ['attribute_id = ?' => $customDesignProductAttribute['attribute_id']]
        );

        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable(self::CATALOG_CATEGORY_ENTITY_VARCHAR),
            ['value' => $newTheme['theme_id']],
            ['attribute_id = ?' => $customDesignCategoryAttribute['attribute_id']]
        );

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }
}
