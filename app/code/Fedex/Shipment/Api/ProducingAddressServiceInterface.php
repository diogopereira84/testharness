<?php
declare(strict_types=1);

namespace Fedex\Shipment\Api;

use Fedex\Shipment\Model\ProducingAddress;

interface ProducingAddressServiceInterface
{
    /**
     * Load ProducingAddress by order_id
     *
     * @param int|string $orderId
     * @return ProducingAddress|null
     */
    public function getByOrderId($orderId): ?ProducingAddress;
}

