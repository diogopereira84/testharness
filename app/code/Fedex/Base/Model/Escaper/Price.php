<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Base\Model\Escaper;

use Fedex\Base\Api\PriceEscaperInterface;

class Price implements PriceEscaperInterface
{
    /**
     * Regex to pattern mach price
     */
    private const NUMBER_PATTERN_MATCH = "/[^-0-9\.]/";

    /**
     * @inheritDoc
     */
    public function escape(string $value): float
    {
        return floatval(preg_replace(
            self::NUMBER_PATTERN_MATCH,
            "",
            $value
        ));
    }
}
