<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <manuel.rosario.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Model;

use Psr\Log\LoggerInterface;

use Fedex\MarketplaceWebhook\Api\Data\CreateInvoiceMessageInterface;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;

class CreateInvoiceMarketplaceOnlyOrders
{

    /**
     * Construct
     *
     * @param LoggerInterface $logger
     * @param SubmitOrderHelper $submitOrderHelper
     */
    public function __construct(
        private LoggerInterface     $logger,
        private SubmitOrderHelper   $submitOrderHelper
    ) {
    }

    /**
     * Generate Invoice for 3P orders
     *
     * @param string $message
     * @return void
     */
    public function execute(CreateInvoiceMessageInterface $message)
    {


        try {
            $orderId = $message->getOrderId();
            $this->submitOrderHelper->generateInvoice($orderId);

        }  catch (\Exception $e) {
            return $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }
}
