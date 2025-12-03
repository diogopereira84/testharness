<?php
 
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
 
declare(strict_types=1);
 
namespace Fedex\MarketplaceCheckout\Plugin\Model\Quote;
 
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Mirakl\Connector\Helper\Config;
use Mirakl\Connector\Model\Quote\OfferCollector;
use Mirakl\Connector\Model\Quote\Updater;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\Model\QuoteOptions;
use Fedex\MarketplaceCheckout\Helper\Data;
use Mirakl\MMP\Front\Domain\Shipping\OrderShippingFee;
 
class UpdaterPluginTest extends TestCase
{
    /**
     * @var (\Mirakl\Connector\Model\Quote\OfferCollector & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $offerCollectorMock;
 
    /**
     * @var (\Mirakl\Connector\Helper\Config & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $configMock;
 
    /**
     * @var (\Magento\Framework\Pricing\PriceCurrencyInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $priceCurrencyMock;
 
    /**
     * @var (\Fedex\MarketplaceCheckout\Model\QuoteOptions & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteOptionsMock;
 
    /**
     * @var (\Fedex\MarketplaceCheckout\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $dataMock;
 
    /**
     * @var UpdaterPlugin
     */
    protected $updaterPlugin;
 
    /**
     * @var (\Mirakl\Connector\Model\Quote\Updater & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $updaterMock;
 
    /**
     * @var (\Magento\Quote\Api\Data\CartInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteMock;
 
    /**
     * @var (\Magento\Quote\Api\Data\CartItemInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $itemMock;
 
    /**
     * @var (\Magento\Quote\Api\Data\CartItemInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $shippingRateOfferMock;
 
    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->offerCollectorMock = $this->createMock(OfferCollector::class);
        $this->configMock = $this->createMock(Config::class);
        $this->priceCurrencyMock = $this->createMock(PriceCurrencyInterface::class);
        $this->quoteOptionsMock = $this->createMock(QuoteOptions::class);
        $this->dataMock = $this->createMock(Data::class);
 
        $this->updaterPlugin = new UpdaterPlugin(
            $this->offerCollectorMock,
            $this->configMock,
            $this->priceCurrencyMock,
            $this->quoteOptionsMock,
            $this->dataMock
        );
 
        $this->updaterMock = $this->createMock(Updater::class);
        $this->quoteMock = $this->createMock(CartInterface::class);
 
        $this->itemMock = $this->getMockBuilder(CartItemInterface::class)
            ->addMethods(['getPriceInclTax', 'removeMessageByText'])
            ->onlyMethods(['getPrice'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
 
        $this->shippingRateOfferMock = $this->getMockBuilder(CartItemInterface::class)
            ->onlyMethods(['getPrice'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }
 
    /**
     * @covers \Fedex\MarketplaceCheckout\Plugin\Model\Quote\UpdaterPlugin::__construct
     */
    public function testConstructor(): void
    {
        $offerCollectorMock = $this->createMock(OfferCollector::class);
        $configMock = $this->createMock(Config::class);
        $priceCurrencyMock = $this->createMock(PriceCurrencyInterface::class);
        $quoteOptionsMock = $this->createMock(QuoteOptions::class);
        $dataMock = $this->createMock(Data::class);
 
        $updaterPlugin = new UpdaterPlugin(
            $offerCollectorMock,
            $configMock,
            $priceCurrencyMock,
            $quoteOptionsMock,
            $dataMock
        );
 
        $this->assertInstanceOf(UpdaterPlugin::class, $updaterPlugin);
 
        $reflectionClass = new \ReflectionClass(UpdaterPlugin::class);
 
        $offerCollectorProperty = $reflectionClass->getProperty('offerCollector');
        $offerCollectorProperty->setAccessible(true);
        $this->assertSame($offerCollectorMock, $offerCollectorProperty->getValue($updaterPlugin));
 
        $configProperty = $reflectionClass->getProperty('config');
        $configProperty->setAccessible(true);
        $this->assertSame($configMock, $configProperty->getValue($updaterPlugin));
 
        $priceCurrencyProperty = $reflectionClass->getProperty('priceCurrency');
        $priceCurrencyProperty->setAccessible(true);
        $this->assertSame($priceCurrencyMock, $priceCurrencyProperty->getValue($updaterPlugin));
 
        $quoteOptionsProperty = $reflectionClass->getProperty('quoteOptions');
        $quoteOptionsProperty->setAccessible(true);
        $this->assertSame($quoteOptionsMock, $quoteOptionsProperty->getValue($updaterPlugin));
 
        $dataProperty = $reflectionClass->getProperty('data');
        $dataProperty->setAccessible(true);
        $this->assertSame($dataMock, $dataProperty->getValue($updaterPlugin));
    }
 
    /**
     * @covers \Fedex\MarketplaceCheckout\Plugin\Model\Quote\UpdaterPlugin::afterSynchronize
     */
    public function testAfterSynchronizeRemoveMessage(): void
    {
        $this->offerCollectorMock->expects($this->once())
            ->method('getItemsWithOffer')
            ->with($this->quoteMock)
            ->willReturn([$this->itemMock]);
 
        $this->updaterMock->expects($this->once())
            ->method('getItemShippingRateOffer')
            ->with($this->itemMock)
            ->willReturn($this->shippingRateOfferMock);
 
        $this->configMock->expects($this->once())
            ->method('getOffersIncludeTax')
            ->with($this->quoteMock->getStoreId())
            ->willReturn(false);
 
        $this->itemMock->expects($this->once())
            ->method('getPrice')
            ->willReturn(90.00);
 
        $this->shippingRateOfferMock->expects($this->once())
            ->method('getPrice')
            ->willReturn(95.00);
 
        $this->priceCurrencyMock->expects($this->exactly(2))
            ->method('format')
            ->willReturnCallback(function ($amount) {
                return number_format($amount, 2, '.', '');
            })
            ->willReturnOnConsecutiveCalls('90.00', '95.00');
 
        $this->itemMock->expects($this->once())
            ->method('removeMessageByText')
            ->with(__('Price has changed from %1 to %2', '90.00', '95.00'));
 
        $this->updaterPlugin->afterSynchronize($this->updaterMock, null, $this->quoteMock);
    }
 
    /**
     * @covers \Fedex\MarketplaceCheckout\Plugin\Model\Quote\UpdaterPlugin::afterSynchronize
     */
    public function testAfterSynchronizeSamePrice(): void
    {
        $this->offerCollectorMock->expects($this->once())
            ->method('getItemsWithOffer')
            ->with($this->quoteMock)
            ->willReturn([$this->itemMock]);
 
        $this->updaterMock->expects($this->once())
            ->method('getItemShippingRateOffer')
            ->with($this->itemMock)
            ->willReturn($this->shippingRateOfferMock);
 
        $this->configMock->expects($this->once())
            ->method('getOffersIncludeTax')
            ->with($this->quoteMock->getStoreId())
            ->willReturn(false);
 
        $this->itemMock->expects($this->any())
            ->method('getPrice')
            ->willReturn(95.00);
 
        $this->shippingRateOfferMock->expects($this->once())
            ->method('getPrice')
            ->willReturn(95.00);
 
        $this->itemMock->expects($this->never())->method('removeMessageByText');
 
        $this->updaterPlugin->afterSynchronize($this->updaterMock, null, $this->quoteMock);
    }
 
    /**
     * @covers \Fedex\MarketplaceCheckout\Plugin\Model\Quote\UpdaterPlugin::afterSynchronize
     */
    public function testAfterSynchronizeSamePriceWithOffersIncludesTax(): void
    {
        $this->offerCollectorMock->expects($this->once())
            ->method('getItemsWithOffer')
            ->with($this->quoteMock)
            ->willReturn([$this->itemMock]);
 
        $this->updaterMock->expects($this->once())
            ->method('getItemShippingRateOffer')
            ->with($this->itemMock)
            ->willReturn($this->shippingRateOfferMock);
 
        $this->configMock->expects($this->once())
            ->method('getOffersIncludeTax')
            ->with($this->quoteMock->getStoreId())
            ->willReturn(true);
 
        $this->itemMock->expects($this->any())
            ->method('getPriceInclTax')
            ->willReturn(95.00);
 
        $this->shippingRateOfferMock->expects($this->once())
            ->method('getPrice')
            ->willReturn(95.00);
 
        $this->itemMock->expects($this->never())->method('removeMessageByText');
 
        $this->updaterPlugin->afterSynchronize($this->updaterMock, null, $this->quoteMock);
    }
 
    /**
     * @covers \Fedex\MarketplaceCheckout\Plugin\Model\Quote\UpdaterPlugin::afterGetItemOrderShippingFee
     */
    public function testAfterGetItemOrderShippingFeeReturnsUnchangedWhenMiraklShippingDataPresent(): void
    {
        $orderShippingFeeMock = $this->getMockBuilder(OrderShippingFee::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOffers'])
            ->getMock();
 
        $item = $this->getMockBuilder(CartItemInterface::class)
            ->addMethods(['getAdditionalData', 'getMiraklOfferId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->once())
            ->method('getAdditionalData')
            ->willReturn(json_encode(['mirakl_shipping_data' => ['foo']]));
 
        $item->expects($this->never())->method('getMiraklOfferId');
 
        $orderShippingFeeMock->expects($this->never())->method('getOffers');
 
        $result = $this->updaterPlugin
            ->afterGetItemOrderShippingFee($this->updaterMock, $orderShippingFeeMock, $item);
 
        $this->assertSame($orderShippingFeeMock, $result);
    }
 
    /**
     * @covers \Fedex\MarketplaceCheckout\Plugin\Model\Quote\UpdaterPlugin::afterGetItemOrderShippingFee
     */
    public function testAfterGetItemOrderShippingFeeZeroesLinePriceWhenOfferIdMatches(): void
    {
        $orderShippingFeeMock = $this->getMockBuilder(OrderShippingFee::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOffers'])
            ->getMock();
 
        $shippingRateOffer = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId', 'setLineShippingPrice'])
            ->getMock();
        $shippingRateOffer->expects($this->once())
            ->method('getId')
            ->willReturn('offer123');
        $shippingRateOffer->expects($this->once())
            ->method('setLineShippingPrice')
            ->with(0);
 
        $orderShippingFeeMock->expects($this->once())
            ->method('getOffers')
            ->willReturn([$shippingRateOffer]);
 
        $item = $this->getMockBuilder(CartItemInterface::class)
            ->addMethods(['getAdditionalData', 'getMiraklOfferId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->once())
            ->method('getAdditionalData')
            ->willReturn(json_encode([]));
        $item->expects($this->once())
            ->method('getMiraklOfferId')
            ->willReturn('offer123');
 
        $result = $this->updaterPlugin
            ->afterGetItemOrderShippingFee($this->updaterMock, $orderShippingFeeMock, $item);
 
        $this->assertSame($orderShippingFeeMock, $result);
    }
}
