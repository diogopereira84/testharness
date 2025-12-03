<?php
/**
 * @category  Fedex
 * @package   Fedex_Base
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Base\Api;

interface PriceEscaperInterface
{
    /**
     * Escape a price string to float.
     *
     * @param string $value
     * @return float
     */
    public function escape(string $value): float;
}
