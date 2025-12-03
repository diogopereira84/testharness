<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\MarketplaceWebhook\Model\SendDeliveryNotification;
use Fedex\MarketplaceWebhook\Model\SendDeliveryNotificationRequestBuilder;

class SendDeliveryNotificationTest extends TestCase
{
    /**
     * Test execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $message = '{"key": "value"}';

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Send Delivery notification data: ' . $message);

        $sendNotification = $this->createMock(SendDeliveryNotificationRequestBuilder::class);
        $sendNotification->expects($this->once())
            ->method('sendDeliverNotification')
            ->with(json_decode($message, true));

        $sendDeliveryNotification = new SendDeliveryNotification($logger, $sendNotification);
        $sendDeliveryNotification->execute($message);
    }
}
