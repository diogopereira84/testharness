<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model;

use Fedex\MarketplaceCheckout\Helper\Data as HelperData;
use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Fedex\MarketplaceCheckout\Model\Config\MarketplaceConfigProvider;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\Model\ConfigProvider;
use Fedex\MarketplaceCheckout\Model\Config\ToastDeliveryMessage;

class ConfigProviderTest extends TestCase
{
    /**
     * @var (\Fedex\MarketplaceProduct\Model\NonCustomizableProduct & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $nonCustomizableProductModel;
    private const PROMO_CODE_MESSAGE           = 'some promo code message';

    private const MARKETPLACE_TOAST_TITLE      = 'Toast title';

    private const MARKETPLACE_SHIPPING_CONTENT = 'Shipping content';

    private const MARKETPLACE_PICKUP_CONTENT   = 'Pickup content';

    /**
     * @var HandleMktCheckout
     */
    private $handleMktCheckoutMock;

    /**
     * @var MarketplaceConfigProvider
     */
    private MarketplaceConfigProvider $marketplaceConfigProvider;

    /**
     * @var ToastDeliveryMessage
     */
    protected ToastDeliveryMessage $toastDeliveryMessage;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var HelperData
     */
    private $helperData;

    protected function setUp(): void
    {
        $this->handleMktCheckoutMock = $this->getMockBuilder(HandleMktCheckout::class)
            ->disableOriginalConstructor()
            ->addMethods(['checkoutDeliveryMethodsTooltip'])
            ->onlyMethods(
                [
                    'getCheckoutShippingAccountMessage',
                    'getCheckoutDeliveryMethodsTooltip',
                    'getTigerTeamD180031Fix'
                ]
            )
            ->getMock();
        $this->marketplaceConfigProvider = $this->createMock(MarketplaceConfigProvider::class);
        $this->toastDeliveryMessage = $this->createMock(ToastDeliveryMessage::class);
        $this->helperData = $this->createMock(HelperData::class);
        $this->nonCustomizableProductModel = $this->createMock(NonCustomizableProduct::class);
        $this->configProvider = new ConfigProvider(
            $this->handleMktCheckoutMock,
            $this->marketplaceConfigProvider,
            $this->toastDeliveryMessage,
            $this->helperData,
            $this->nonCustomizableProductModel
        );
    }

    /**
     * Test getConfig enable mkt checkout true.
     *
     * @return void
     */
    public function testGetConfigReturnsArrayWithIsEnableMktCheckoutKey(): void
    {
        $this->toastDeliveryMessage->expects($this->once())
            ->method('getMarketplaceToastTitle')
            ->willReturn(self::MARKETPLACE_TOAST_TITLE);

        $this->toastDeliveryMessage->expects($this->once())
            ->method('getMarketplaceToastShippingContent')
            ->willReturn(self::MARKETPLACE_SHIPPING_CONTENT);

        $this->toastDeliveryMessage->expects($this->once())
            ->method('getMarketplaceToastPickupContent')
            ->willReturn(self::MARKETPLACE_PICKUP_CONTENT);

        $this->marketplaceConfigProvider->expects($this->once())
            ->method('getPromoCodeMessage')->willReturn(self::PROMO_CODE_MESSAGE);

        $this->handleMktCheckoutMock->expects($this->once())
            ->method('getCheckoutShippingAccountMessage')
            ->willReturn('');
        $this->handleMktCheckoutMock->expects($this->any())
            ->method('getCheckoutDeliveryMethodsTooltip')
            ->willReturn('message');

        $this->handleMktCheckoutMock->expects($this->any())
            ->method('getTigerTeamD180031Fix')
            ->willReturn(false);

        $this->helperData->expects($this->once())
            ->method('isEssendantToggleEnabled')
            ->willReturn(false);
        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->helperData->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);
        $this->helperData->expects($this->once())
            ->method('checkIfItemsAreAllNonCustomizableProduct')
            ->with($quoteMock)
            ->willReturn(false);

        $expectedResult = [
            'checkoutDeliveryMethodsTooltip' => 'message',
            'promoCodeMessage' => self::PROMO_CODE_MESSAGE,
            'toastTitle' => self::MARKETPLACE_TOAST_TITLE,
            'toastShippingContent' => self::MARKETPLACE_SHIPPING_CONTENT,
            'toastPickupContent' => self::MARKETPLACE_PICKUP_CONTENT,
            'isCustomerShippingAccount3PEnabled' => false,
            'shippingAccountMessage' => '',
            'promoCodeMessageEnabledToggle' => false,
            'toggle_D180031_fix'=> false,
            'isExpectedDeliveryDateEnabled' => false,
            'reviewSubmitCancellationMessage' => '',
            'isMktCbbEnabled' => false,
            'isEssendantEnabled' => false,
            'onlyNonCustomizableCart' => false,
        ];

        $this->assertSame($expectedResult, $this->configProvider->getConfig());
    }

    /**
     * Test getConfig enable mkt checkout false.
     *
     * @return void
     */
    public function testGetConfigReturnsArrayWithIsEnableMktCheckoutKeyFalse(): void
    {
        $this->toastDeliveryMessage->expects($this->once())
            ->method('getMarketplaceToastTitle')
            ->willReturn(self::MARKETPLACE_TOAST_TITLE);

        $this->toastDeliveryMessage->expects($this->once())
            ->method('getMarketplaceToastShippingContent')
            ->willReturn(self::MARKETPLACE_SHIPPING_CONTENT);

        $this->toastDeliveryMessage->expects($this->once())
            ->method('getMarketplaceToastPickupContent')
            ->willReturn(self::MARKETPLACE_PICKUP_CONTENT);

        $this->marketplaceConfigProvider->expects($this->once())
            ->method('getPromoCodeMessage')->willReturn(self::PROMO_CODE_MESSAGE);

        $this->handleMktCheckoutMock->expects($this->once())
            ->method('getCheckoutShippingAccountMessage')
            ->willReturn('');
        $this->handleMktCheckoutMock->expects($this->any())
            ->method('getCheckoutDeliveryMethodsTooltip')
            ->willReturn('message');

        $this->handleMktCheckoutMock->expects($this->any())
            ->method('getTigerTeamD180031Fix')
            ->willReturn(false);

        $this->helperData->expects($this->once())
            ->method('isEssendantToggleEnabled')
            ->willReturn(true);
        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->helperData->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);
        $this->helperData->expects($this->once())
            ->method('checkIfItemsAreAllNonCustomizableProduct')
            ->with($quoteMock)
            ->willReturn(true);

        $expectedResult = [
            'checkoutDeliveryMethodsTooltip' => 'message',
            'promoCodeMessage' => self::PROMO_CODE_MESSAGE,
            'toastTitle' => self::MARKETPLACE_TOAST_TITLE,
            'toastShippingContent' => self::MARKETPLACE_SHIPPING_CONTENT,
            'toastPickupContent' => self::MARKETPLACE_PICKUP_CONTENT,
            'isCustomerShippingAccount3PEnabled' => false,
            'shippingAccountMessage' => '',
            'promoCodeMessageEnabledToggle' => false,
            'toggle_D180031_fix'=> false,
            'isExpectedDeliveryDateEnabled' => false,
            'reviewSubmitCancellationMessage' => '',
            'isMktCbbEnabled' => false,
            'isEssendantEnabled' => true,
            'onlyNonCustomizableCart' => true,
        ];
        $this->assertSame($expectedResult, $this->configProvider->getConfig());
    }
}
