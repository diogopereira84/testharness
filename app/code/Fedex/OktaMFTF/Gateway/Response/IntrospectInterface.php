<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Gateway\Response;

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
