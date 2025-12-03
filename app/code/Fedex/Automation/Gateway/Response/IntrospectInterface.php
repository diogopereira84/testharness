<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Gateway\Response;

interface IntrospectInterface
{
    /**
     * Active key
     */
    public const ACTIVE = 'active';

    /**
     * Get active status
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Set active status
     *
     * @param bool $active
     * @return IntrospectInterface
     */
    public function setActive(bool $active): IntrospectInterface;
}
