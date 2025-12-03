<?php
/**
 * @category    Fedex
 * @package     Fedex_Mars
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Mars\Api;

/**
 * Order interface.
 *
 * @api
 * @since 100.0.2
 */
interface OrderProcessInterface
{
    /**
     * Convert order to json
     *
     * @param int $id The order ID.
     * @return array
     */
    public function getOrderJson(int $id): array;
}
