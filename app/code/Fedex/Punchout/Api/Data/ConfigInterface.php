<?php
/**
 * @category Fedex
 * @package Fedex_Punchout
 * @copyright Copyright (c) 2024 Fedex
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Punchout\Api\Data;

/**
 * Interface ConfigInterface
 */
interface ConfigInterface
{
    /**
     * Return E-398120 Migrate ePro New Platform Order Creation Toggle
     *
     * @param int|string|null $companyId
     * @return bool|int|null
     **/
    public function getMigrateEproNewPlatformOrderCreationToggle(int|string|null $companyId): bool|int|null;
}
