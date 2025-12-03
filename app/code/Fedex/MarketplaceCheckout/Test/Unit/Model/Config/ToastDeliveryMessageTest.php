<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\MarketplaceCheckout\Model\Config\ToastDeliveryMessage;
use PHPUnit\Framework\TestCase;

class ToastDeliveryMessageTest extends TestCase
{
    protected $scopeConfig;
    private const MARKETPLACE_TOAST_TITLE      = 'Toast title';

    private const MARKETPLACE_SHIPPING_CONTENT = 'Shipping content';

    private const MARKETPLACE_PICKUP_CONTENT   = 'Pickup content';

    /**
     * @var ToastDeliveryMessage
     */
    private ToastDeliveryMessage $toastDeliveryMessage;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->toastDeliveryMessage = new ToastDeliveryMessage($this->scopeConfig);
    }

    /**
     * Test getMarketplaceToastTitle method.
     *
     * @return void
     */
    public function testGetMarketplaceToastTitle(): void
    {
        $this->scopeConfig->expects($this->once())->method('getValue')
            ->willReturn(self::MARKETPLACE_TOAST_TITLE);

        $this->assertEquals(
            $this->toastDeliveryMessage->getMarketplaceToastTitle(),
            self::MARKETPLACE_TOAST_TITLE
        );
    }

    /**
     * Test getMarketplaceToastShippingContent method.
     *
     * @return void
     */
    public function testGetMarketplaceToastShippingContent(): void
    {
        $this->scopeConfig->expects($this->once())->method('getValue')
            ->willReturn(self::MARKETPLACE_SHIPPING_CONTENT);

        $this->assertEquals(
            $this->toastDeliveryMessage->getMarketplaceToastShippingContent(),
            self::MARKETPLACE_SHIPPING_CONTENT
        );
    }

    /**
     * Test getMarketplaceToastPickupContent method.
     *
     * @return void
     */
    public function testGetMarketplaceToastPickupContent(): void
    {
        $this->scopeConfig->expects($this->once())->method('getValue')
            ->willReturn(self::MARKETPLACE_PICKUP_CONTENT);

        $this->assertEquals(
            $this->toastDeliveryMessage->getMarketplaceToastPickupContent(),
            self::MARKETPLACE_PICKUP_CONTENT
        );
    }
}
