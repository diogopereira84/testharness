<?php
/**
 * @category    Fedex
 * @package     Fedex_Recaptcha
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Recaptcha\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class RemoveGoogleCaptchaToggle implements DataPatchInterface
{
    /**
     * Removal of Feature Toggle Tiger Team - E-394714 - Google reCaptcha implementation in POD 2.0.
     */
    private const TIGER_GOOGLE_RECAPTCHA =
        'environment_toggle_configuration/environment_toggle/tiger_google_recaptcha';


    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup
    )
    {
    }

    /**
     * @return void
     */
    public function apply()
    {
        $configPathsToRemove = [
            self::TIGER_GOOGLE_RECAPTCHA,
        ];

        foreach ($configPathsToRemove as $configPath) {
            $this->moduleDataSetup->getConnection()->delete(
                $this->moduleDataSetup->getTable('core_config_data'),
                ['path = ?' => $configPath]
            );
        }
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }
}
