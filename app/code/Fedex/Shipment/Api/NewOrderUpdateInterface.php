<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Api;

use \Magento\Framework\Exception\NoSuchEntityException;

/**
 * @codeCoverageIgnore
 */
interface NewOrderUpdateInterface
{

    /**
     *
     */
    const COMPLETE = 'complete';

    /**
     * from Magento
     */
    const CANCELED = 'canceled';

    /**
     * from OMS
     */
    const CANCELLED = 'cancelled';

    /**
     *
     */
    const CONFIRMED = 'confirmed';

    /**
     *
     */
    const NEW = 'new';

    /**
     * @deprecated use READY_FOR_PICKUP instead
     */
    const READYFORPICKUP = 'ready_for_pickup';

    /**
     *
     */
    const READY_FOR_PICKUP = 'ready_for_pickup';

    /**
     *
     */
    const SHIPPED = 'shipped';

    /**
     *
     */
    const DELIVERED = 'delivered';

    /**
     * @deprecated use IN_PROGRESS instead
     */
    const INPROGRESS = 'in_progress';

    /**
     *
     */
    const IN_PROGRESS = 'in_progress';

    /**
     * @deprecated use IN_PROCESS instead
     */
    const INPROCESS = 'in_process';

    /**
     *
     */
    const IN_PROCESS = 'in_process';

    /**
     *
     */
    const PENDING = 'pending';

    /**
     * @param string $orderId
     * @return array
     **/
    public function updateOrderStatus(string $orderIncrementId);
}
