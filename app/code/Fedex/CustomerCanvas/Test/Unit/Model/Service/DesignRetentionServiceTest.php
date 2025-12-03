<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Test\Unit\Model\Service;

use DateInterval;
use DateTimeImmutable;
use Fedex\CustomerCanvas\Model\ConfigProvider;
use Fedex\CustomerCanvas\Model\Service\DesignRetentionService;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option as QuoteItemOption;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DesignRetentionServiceTest extends TestCase
{
    private ConfigProvider $configProvider;
    private LoggerInterface $logger;
    private DesignRetentionService $service;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new DesignRetentionService(
            $this->configProvider,
            $this->logger
        );
    }

    public function testIsExpiredDesignReturnsTrueWhenExpired(): void
    {
        $this->configProvider
            ->method('getRetentionPeriod')
            ->willReturn("1");

        $pastDate = (new DateTimeImmutable())->sub(new DateInterval('P2M'));

        $optionMock = $this->createMock(QuoteItemOption::class);
        $optionMock->method('getData')
            ->with('value')
            ->willReturn(json_encode([
                'productConfig' => [
                    'vendorOptions' => [
                        'designCreationTime' => $pastDate->format('Y-m-d H:i:s')
                    ]
                ]
            ]));

        $itemMock = $this->createMock(QuoteItem::class);
        $itemMock->method('getOptions')->willReturn([$optionMock]);

        $this->assertTrue($this->service->isExpiredDesign($itemMock));
    }

    public function testIsExpiredDesignReturnsFalseWhenNotExpired(): void
    {
        $this->configProvider
            ->method('getRetentionPeriod')
            ->willReturn("6");

        $recentDate = (new DateTimeImmutable())->sub(new DateInterval('P1M'));

        $optionMock = $this->createMock(QuoteItemOption::class);
        $optionMock->method('getData')
            ->with('value')
            ->willReturn(json_encode([
                'productConfig' => [
                    'vendorOptions' => [
                        'designCreationTime' => $recentDate->format('Y-m-d H:i:s')
                    ]
                ]
            ]));

        $itemMock = $this->createMock(QuoteItem::class);
        $itemMock->method('getOptions')->willReturn([$optionMock]);

        $this->assertFalse($this->service->isExpiredDesign($itemMock));
    }

    public function testIsExpiredCatalogProductDesignReturnsTrueWhenExpired(): void
    {
        $this->configProvider
            ->method('getRetentionPeriod')
            ->willReturn("1");

        $pastDate = (new DateTimeImmutable())->sub(new DateInterval('P3M'));

        $productMock = $this->createMock(Product::class);
        $productMock->method('getData')
            ->with('external_prod')
            ->willReturn(json_encode([
                'vendorOptions' => [
                    ['designCreationTime' => $pastDate->format('Y-m-d H:i:s')]
                ]
            ]));

        $this->assertTrue($this->service->isExpiredCatalogProductDesign($productMock));
    }

    public function testIsExpiredCatalogProductDesignReturnsFalseWhenNotExpired(): void
    {
        $this->configProvider
            ->method('getRetentionPeriod')
            ->willReturn("14");

        $recentDate = (new DateTimeImmutable())->sub(new DateInterval('P1M'));

        $productMock = $this->createMock(Product::class);
        $productMock->method('getData')
            ->with('external_prod')
            ->willReturn(json_encode([
                'vendorOptions' => [
                    ['designCreationTime' => $recentDate->format('Y-m-d H:i:s')]
                ]
            ]));

        $this->assertFalse($this->service->isExpiredCatalogProductDesign($productMock));
    }

    public function testHandlesInvalidJsonGracefully(): void
    {
        $this->configProvider
            ->method('getRetentionPeriod')
            ->willReturn("14");

        $productMock = $this->createMock(Product::class);
        $productMock->method('getData')
            ->with('external_prod')
            ->willReturn('{invalid-json}');

        $this->logger->expects($this->any())
            ->method('error');

        $this->assertFalse($this->service->isExpiredCatalogProductDesign($productMock));
    }
}
