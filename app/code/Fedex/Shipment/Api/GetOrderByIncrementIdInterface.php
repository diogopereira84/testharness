<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Fedex\Shipment\Api;

use Magento\Sales\Api\Data\OrderInterface;

interface GetOrderByIncrementIdInterface
{
    public function execute(string $incrementId): ?OrderInterface;
}
