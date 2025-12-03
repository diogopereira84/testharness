<?php
/**
 * @category    Fedex
 * @package     Fedex_EnvironmentManager
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Model\Config;

interface ToggleInterface
{
    /**
     * Check if Toggle status
     *
     * @return bool
     */
    public function isActive(): bool;
}
