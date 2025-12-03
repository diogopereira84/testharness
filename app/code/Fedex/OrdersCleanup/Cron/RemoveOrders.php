<?php
/**
 * @category    Fedex
 * @package     Fedex_OrdersCleanup
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\OrdersCleanup\Cron;

use Exception;
use Fedex\OrdersCleanup\Helper\Data;
use Fedex\OrdersCleanup\Helper\RemoveOrders as RemoveOrdersHelper;

class RemoveOrders
{

    /**
     * Constructor
     *
     * @param RemoveOrdersHelper $removeOrdersHelper
     * @param Data $helper
     */
    public function __construct(
        private readonly RemoveOrdersHelper       $removeOrdersHelper,
        private readonly Data                     $helper,
    ) {
    }

    /**
     * Execute the cron
     *
     * @return void
     * @throws Exception
     */
    public function execute(): void
    {
        try {
            $this->removeOrdersHelper->removeOrders();
        } catch (Exception $e) {
            $this->helper->logMessage(
                __METHOD__ . ':' . __LINE__,
                $e->getMessage(),
                true
            );
        }
    }
}
