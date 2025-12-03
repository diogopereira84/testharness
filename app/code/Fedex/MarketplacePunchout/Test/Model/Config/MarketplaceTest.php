<?php

declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Test\Unit\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Fedex\MarketplacePunchout\Model\Config\Marketplace;
use Magento\Framework\Exception\NoSuchEntityException;
use Mirakl\Core\Model\Shop;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceProduct\Api\OfferRepositoryInterface;
use Fedex\MarketplaceProduct\Api\Data\OfferInterface;
use Fedex\MarketplaceProduct\Helper\Data;

class MarketplaceTest extends TestCase
{
    protected $toggleConfig;
    protected $offerRepository;
    protected $data;
    /**
     * Xpath enable external product update to quote
     */
    private const XPATH_ENABLE_SHOPS_CONNECTION_DATA_CHANGE = 'environment_toggle_configuration/environment_toggle/tiger_e426628_shops_connection_data_change';

    /** @var Marketplace */
    protected Marketplace $marketplace;

    /** @var ScopeConfigInterface|Stub */
    protected ScopeConfigInterface|Stub $scopeConfig;

    /** @var EncryptorInterface|Stub */
    private EncryptorInterface|Stub $encryptor;

    public function setUp(): void
    {
        $this->scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $this->encryptor = $this->createStub(EncryptorInterface::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->offerRepository = $this->createMock(OfferRepositoryInterface::class);
        $this->data = $this->createMock(Data::class);

        $this->scopeConfig->method('isSetFlag')->willReturn(true);
        $this->marketplace = new Marketplace(
            $this->scopeConfig,
            $this->encryptor,
            $this->toggleConfig,
            $this->offerRepository,
            $this->data
        );
    }

    public function testGetFromId()
    {
        $this->scopeConfig->method('getValue')->willReturn('test');
        $this->assertEquals('test', $this->marketplace->getFromId());
    }

    public function testGetToId()
    {
        $this->scopeConfig->method('getValue')->willReturn('test');
        $this->assertEquals('test', $this->marketplace->getToId());
    }

    public function testGetSenderIdentity()
    {
        $this->scopeConfig->method('getValue')->willReturn('test');
        $this->assertEquals('test', $this->marketplace->getSenderIdentity());
    }

    public function testGetSenderSharedSecret()
    {
        $this->encryptor->method('decrypt')->willReturn('test');
        $this->scopeConfig->method('getValue')->willReturn('test');

        $this->assertEquals('test', $this->marketplace->getSenderSharedSecret());
    }

    public function testGetNavitorUrl()
    {
        $this->scopeConfig->method('getValue')->willReturn('test');
        $this->assertEquals('test', $this->marketplace->getNavitorUrl());
    }

    public function testGetAccountNumber()
    {
        $this->scopeConfig->method('getValue')->willReturn('test');
        $this->assertEquals('test', $this->marketplace->getAccountNumber());
    }

    public function testGetMarketplaceDowntimeTitle()
    {
        $this->scopeConfig->method('getValue')->willReturn('test');
        $this->assertEquals('test', $this->marketplace->getMarketplaceDowntimeTitle());
    }

    public function testGetMarketplaceDowntimeMsg()
    {
        $this->scopeConfig->method('getValue')->willReturn('test');
        $this->assertEquals('test', $this->marketplace->getMarketplaceDowntimeMsg());
    }

    public function testIsEnableShopsConnection()
    {
        $this->toggleConfig->method('getToggleConfig')
            ->with(self::XPATH_ENABLE_SHOPS_CONNECTION_DATA_CHANGE)->willReturn(true);
        $this->assertEquals('test', $this->marketplace->isEnableShopsConnection());
    }

    public function testGetShopCustomAttributesByProductSku(): void
    {
        $productSku = 'SKU123';
        $offer = $this->createMock(OfferInterface::class);
        $this->offerRepository->expects($this->once())
            ->method('getById')
            ->with($productSku)
            ->willReturn($offer);

        $this->data->expects($this->once())
            ->method('getCustomAttributes')
            ->with([$offer])
            ->willReturn(['attribute1' => 'value1', 'attribute2' => 'value2']);

        $result =  $this->marketplace->getShopCustomAttributesByProductSku($productSku);
        $this->assertEquals(['attribute1' => 'value1', 'attribute2' => 'value2'], $result);
    }

    public function testGetShopByOfferSuccess(): void
    {
        $productSku = 'TEST123';
        $offerMock = $this->createMock(OfferInterface::class);
        $shopMock = $this->createMock(Shop::class);

        $this->offerRepository->method('getById')->with($productSku)->willReturn($offerMock);
        $this->data->method('getShopByOffer')->with([$offerMock])->willReturn($shopMock);

        $result = $this->marketplace->getShopByOffer($productSku);

        $this->assertSame($shopMock, $result);
    }

    public function testGetShopByOfferNoSuchEntityException(): void
    {
        $productSku = 'INVALID_SKU';

        $this->offerRepository->method('getById')->with($productSku)->willThrowException(new NoSuchEntityException());

        $this->expectException(NoSuchEntityException::class);

        $this->marketplace->getShopByOffer($productSku);
    }

    public function testGetOfferSuccess(): void
    {
        $productSku = 'TEST123';
        $offerMock = $this->createMock(\Fedex\MarketplaceProduct\Api\Data\OfferInterface::class);

        $this->offerRepository->method('getById')->with($productSku)->willReturn($offerMock);

        $result = $this->marketplace->getOffer($productSku);

        $this->assertSame($offerMock, $result);
    }

    public function testGetOfferNoSuchEntityException(): void
    {
        $productSku = 'INVALID_SKU';

        $this->offerRepository->method('getById')->with($productSku)->willThrowException(new NoSuchEntityException());

        $this->expectException(NoSuchEntityException::class);

        $this->marketplace->getOffer($productSku);
    }
}
