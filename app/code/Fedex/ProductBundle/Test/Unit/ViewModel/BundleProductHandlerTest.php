<?php

declare(strict_types=1);

namespace Fedex\ProductBundle\Test\Unit\ViewModel;

use Fedex\ProductBundle\ViewModel\BundleProductHandler;
use Fedex\ProductBundle\Model\BundleProductValidator;
use Fedex\ProductBundle\Model\OrderBundleInfoProvider;
use Fedex\ProductBundle\Model\TokenProvider;
use Fedex\ProductBundle\Api\ConfigInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\TestCase;

class BundleProductHandlerTest extends TestCase
{
    private $bundleProductValidator;
    private $orderBundleInfoProvider;
    private $tokenProvider;
    private $config;
    private $handler;

    protected function setUp(): void
    {
        $this->bundleProductValidator = $this->createMock(BundleProductValidator::class);
        $this->orderBundleInfoProvider = $this->createMock(OrderBundleInfoProvider::class);
        $this->tokenProvider = $this->createMock(TokenProvider::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->handler = new BundleProductHandler(
            $this->bundleProductValidator,
            $this->orderBundleInfoProvider,
            $this->tokenProvider,
            $this->config
        );
    }

    private function resetObjectInstance()
    {
        $this->handler = new BundleProductHandler(
            $this->bundleProductValidator,
            $this->orderBundleInfoProvider,
            $this->tokenProvider,
            $this->config
        );
    }

    public function testIsTigerE468338ToggleEnabled()
    {
        $this->config->method('isTigerE468338ToggleEnabled')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->assertTrue($this->handler->isTigerE468338ToggleEnabled());
        $this->assertFalse($this->handler->isTigerE468338ToggleEnabled());
    }

    public function testIsBundleProductSetupCompleted()
    {
        $this->bundleProductValidator->method('isBundleProductSetupCompleted')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->assertTrue($this->handler->isBundleProductSetupCompleted());
        $this->assertFalse($this->handler->isBundleProductSetupCompleted());
    }

    public function testIsBundleItemSetupCompleted()
    {
        $item = $this->createMock(QuoteItem::class);
        $this->bundleProductValidator->method('isBundleItemSetupCompleted')->with($item)
            ->willReturnOnConsecutiveCalls(true, false);
        $this->assertTrue($this->handler->isBundleItemSetupCompleted($item));
        $this->assertFalse($this->handler->isBundleItemSetupCompleted($item));
    }

    public function testIsBundleParentSetupCompleted()
    {
        $item = $this->createMock(QuoteItem::class);
        $this->bundleProductValidator->method('isBundleParentSetupCompleted')->with($item)
            ->willReturnOnConsecutiveCalls(true, false);
        $this->assertTrue($this->handler->isBundleParentSetupCompleted($item));
        $this->assertFalse($this->handler->isBundleParentSetupCompleted($item));
    }

    public function testIsBundleChildSetupCompleted()
    {
        $item = $this->createMock(QuoteItem::class);
        $this->bundleProductValidator->method('isBundleChildSetupCompleted')->with($item)
            ->willReturnOnConsecutiveCalls(true, false);
        $this->assertTrue($this->handler->isBundleChildSetupCompleted($item));
        $this->assertFalse($this->handler->isBundleChildSetupCompleted($item));
    }

    public function testHasBundleProductInCart()
    {
        $this->bundleProductValidator->method('hasBundleProductInCart')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->assertTrue($this->handler->hasBundleProductInCart());
        $this->assertFalse($this->handler->hasBundleProductInCart());
    }

    public function testHasQuoteItemWithInstanceId()
    {
        $this->bundleProductValidator->method('hasQuoteItemWithInstanceId')->with('foo')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->assertTrue($this->handler->hasQuoteItemWithInstanceId('foo'));
        $this->assertFalse($this->handler->hasQuoteItemWithInstanceId('foo'));
    }

    public function testGetBundleChildrenCount()
    {
        $item = $this->createMock(QuoteItem::class);
        $this->bundleProductValidator->method('getBundleChildrenCount')->with($item)->willReturn(3);
        $this->assertSame(3, $this->handler->getBundleChildrenCount($item));
    }

    public function testGetBundleChildrenItemsCountWithQuoteItem()
    {
        $item = $this->createMock(QuoteItem::class);
        $this->bundleProductValidator->method('getBundleChildrenItemsCount')->with($item)->willReturn(2);
        $this->assertSame(2, $this->handler->getBundleChildrenItemsCount($item));
    }

    public function testGetBundleChildrenItemsCountWithOrderItem()
    {
        $item = $this->createMock(OrderItem::class);
        $this->bundleProductValidator->method('getBundleChildrenItemsCount')->with($item)->willReturn(4);
        $this->assertSame(4, $this->handler->getBundleChildrenItemsCount($item));
    }

    public function testIsBundleChild()
    {
        $item = $this->createMock(QuoteItem::class);
        $this->bundleProductValidator->method('isBundleChild')->with($item)
            ->willReturnOnConsecutiveCalls(true, false);
        $this->assertTrue($this->handler->isBundleChild($item));
        $this->assertFalse($this->handler->isBundleChild($item));
    }

    public function testGetTazToken()
    {
        $this->tokenProvider->method('getTazToken')
            ->willReturnOnConsecutiveCalls('token', 'public_token');
        $this->assertSame('token', $this->handler->getTazToken());
        $this->assertSame('public_token', $this->handler->getTazToken(true));
    }

    public function testGetCompanySite()
    {
        $this->tokenProvider->method('getCompanySite')->willReturn('site');
        $this->assertSame('site', $this->handler->getCompanySite());
    }

    public function testGetBundleItemsSuccessPage()
    {
        $this->orderBundleInfoProvider->method('getBundleItemsSuccessPage')->willReturn(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $this->handler->getBundleItemsSuccessPage());
    }

    public function testGetTitleStepOne()
    {
        $this->config->method('getTitleStepOne')->willReturn('title1');
        $this->assertSame('title1', $this->handler->getTitleStepOne());
    }

    public function testGetDescriptionStepOne()
    {
        $this->config->method('getDescriptionStepOne')->willReturn('desc1');
        $this->assertSame('desc1', $this->handler->getDescriptionStepOne());
    }

    public function testGetTitleStepTwo()
    {
        $this->config->method('getTitleStepTwo')->willReturn('title2');
        $this->assertSame('title2', $this->handler->getTitleStepTwo());
    }

    public function testGetDescriptionStepTwo()
    {
        $this->config->method('getDescriptionStepTwo')->willReturn('desc2');
        $this->assertSame('desc2', $this->handler->getDescriptionStepTwo());
    }

    public function testGetTitleStepThree()
    {
        $this->config->method('getTitleStepThree')->willReturn('title3');
        $this->assertSame('title3', $this->handler->getTitleStepThree());
    }

    public function testGetDescriptionStepThree()
    {
        $this->config->method('getDescriptionStepThree')->willReturn('desc3');
        $this->assertSame('desc3', $this->handler->getDescriptionStepThree());
    }
}

