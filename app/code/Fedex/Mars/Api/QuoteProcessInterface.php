<?php
/**
 * @category    Fedex
 * @package     Fedex_Mars
 * @copyright   Copyright (c) 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\Mars\Api;

/**
 * Quote interface.
 *
 * @api
 * @since 100.0.2
 */
interface QuoteProcessInterface
{
    /**
     * Convert quote to json
     *
     * @param int $id The quote ID.
     * @return array
     */
    public function getQuoteJson(int $id): array;
}
