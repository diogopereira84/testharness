<?php
/**
 * @category    Fedex
 * @package     Fedex_Mars
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Mars\Api;

interface ClientInterface
{
    /**
     * Send order json
     *
     * @param string $json
     * @param int $id
     * @return void
     */
    public function sendJson(string $json, int $id): void;
}
