<?php
/**
 * @category  Fedex
 * @package   Fedex_Cms
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Cms\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class SetupPageTypeDefaultValueToCmsPage implements DataPatchInterface
{
    /**
     * CMS page primary key column
     */
    private const CMS_PAGE_PRIMARY_KEY = 'page_id';

    /**
     *  CMS page identifier for production/staging/staging2
     */
    private const CMS_404_PAGE_IDENTIFIER_PRODUCTION = 'no-route';

    /**
     * CMS page identifier for staging3
     */
    private const CMS_404_PAGE_IDENTIFIER_STAGING3 = 'main_store';

    /**
     * CMS pages to ignore in the update
     */
    private const PAGES_TO_IGNORE = [
        self::CMS_404_PAGE_IDENTIFIER_STAGING3,
        self::CMS_404_PAGE_IDENTIFIER_PRODUCTION
    ];

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

        $cmsPages = $this->moduleDataSetup->getConnection()->fetchAll(
            $this->moduleDataSetup->getConnection()->select()
                ->from(
                    $this->moduleDataSetup->getTable('cms_page'),
                    [self::CMS_PAGE_PRIMARY_KEY]
                )
                ->where('identifier NOT IN (?)', self::PAGES_TO_IGNORE)
        );
        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable('cms_page'),
            ['page_type' => 'content'],
            [
                'page_id IN (?)' => array_column(
                    $cmsPages,
                    self::CMS_PAGE_PRIMARY_KEY
                )
            ]
        );

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }
}
