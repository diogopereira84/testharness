<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Model;

use Psr\Log\LoggerInterface;
class SendDeliveryNotification
{
    /**
     * Construct
     *
     * @param LoggerInterface $logger
     * @param SendDeliveryNotificationRequestBuilder $sendNotification
     */
    public function __construct(
        private LoggerInterface                        $logger,
        private SendDeliveryNotificationRequestBuilder $sendNotification
    ) {
    }


    /**
     * Send delivery notification.
     *
     * @param string $message
     * @return void
     */
    public function execute(string $message)
    {
        $this->logger->info('Send Delivery notification data: '.$message);
        $this->sendNotification->sendDeliverNotification(json_decode($message, true));
    }
}
