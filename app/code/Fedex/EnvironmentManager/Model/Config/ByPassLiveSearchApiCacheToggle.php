<?php
/**
 * @category  Fedex
 * @package   Fedex_EnvironmentManager
 * @author    Bhairav Singh <bhairav.singh.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Model\Config;

class ByPassLiveSearchApiCacheToggle extends ToggleBase implements ToggleInterface
{
    /**
     * Toggle system configuration path
     */
    private const PATH = 'tech_titans_b_2398767';

    /**
     * @inheritDoc
     */
    protected function getPath(): string
    {
        return self::PATH;
    }
}
