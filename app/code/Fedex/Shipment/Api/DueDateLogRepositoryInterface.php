<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Fedex\Shipment\Api;

interface DueDateLogRepositoryInterface
{
    /**
     * @param int $orderId
     * @return mixed
     */
    public function getByOrderId(int $orderId);

    /**
     * @param int $orderId
     * @param string $dueDate
     * @return bool
     */
    public function isNewerThanLast(int $orderId, string $dueDate): bool;
}
