<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2024 FedEx
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
namespace Fedex\MarketplacePunchout\Test\ViewModel;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplacePunchout\ViewModel\Notification;
use Fedex\MarketplacePunchout\Model\Config\Marketplace;

class NotificationTest extends TestCase
{
    /**
     * Test getMarketplaceNotificationTitle method.
     *
     * @return void
     */
    public function testGetMarketplaceNotificationTitle()
    {
        $marketplace = $this->createMock(Marketplace::class);
        $marketplace->method('getMarketplaceDowntimeTitle')
            ->willReturn('Test Title');

        $notification = new Notification($marketplace);
        $title = $notification->getMarketplaceNotificationTitle();

        $this->assertEquals('Test Title', $title);
    }

    /**
     * Test getMarketplaceNotificationMsg method.
     *
     * @return void
     */
    public function testGetMarketplaceNotificationMsg()
    {
        $marketplace = $this->createMock(Marketplace::class);
        $marketplace->method('getMarketplaceDowntimeMsg')
            ->willReturn('Test Message');

        $notification = new Notification($marketplace);
        $message = $notification->getMarketplaceNotificationMsg();

        $this->assertEquals('Test Message', $message);
    }

    /**
     * Test getMarketplaceMessage method.
     *
     * @return void
     */
    public function testGetMarketplaceMessage()
    {
        $marketplace = $this->createMock(Marketplace::class);
        $marketplace->method('getMarketplaceDowntimeTitle')
            ->willReturn('Test Title');
        $marketplace->method('getMarketplaceDowntimeMsg')
            ->willReturn('Test Message');

        $notification = new Notification($marketplace);
        $expectedMessage = htmlspecialchars(json_encode([
            'category' => 'marketplace_pdp',
            'type'     => 'warning',
            'title'    => 'Test Title',
            'text'     => 'Test Message'
        ]));
        $message = $notification->getMarketplaceMessage();

        $this->assertEquals($expectedMessage, $message);
    }
}
