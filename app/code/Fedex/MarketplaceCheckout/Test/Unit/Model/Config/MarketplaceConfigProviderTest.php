<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model\Config;

use PHPUnit\Framework\TestCase;

use Fedex\MarketplaceCheckout\Model\Config\MarketplaceConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;

class MarketplaceConfigProviderTest extends TestCase
{
    protected $toggleConfigMock;
    protected $marketplaceConfigProvider;
    const PROMO_CODE_MESSAGE = 'some promo code message';
    const CART_QUANTITY_TOOLTIP = 'some tooltip message';

    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->marketplaceConfigProvider = new MarketplaceConfigProvider(
            $this->toggleConfigMock
        );
    }

    public function testGetPromoCodeMessage()
    {
        $this->toggleConfigMock->expects($this->once())->method('getValue')
            ->willReturn(self::PROMO_CODE_MESSAGE);
        $this->assertEquals(
            $this->marketplaceConfigProvider->getPromoCodeMessage(),
            self::PROMO_CODE_MESSAGE
        );
    }

    public function testGetCartQuantityTooltip()
    {
        $this->toggleConfigMock->expects($this->once())->method('getValue')
            ->willReturn(self::CART_QUANTITY_TOOLTIP);
        $this->assertEquals(
            $this->marketplaceConfigProvider->getCartQuantityTooltip(),
            self::CART_QUANTITY_TOOLTIP
        );
    }

    /**
     * @return void
     */
    public function testGetPromoCodeMessageEnabledToggle()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->willReturn(false);
        $this->assertFalse($this->marketplaceConfigProvider->getpromoCodeMessageEnabledToggle());
    }
}
