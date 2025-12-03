<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Test\Unit\Model\Service;

use Fedex\CustomerCanvas\Model\Service\DocumentExtractor;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\CustomerCanvas\Model\ConfigProvider;

class DocumentExtractorTest extends TestCase
{
    /** @var LoggerInterface|MockObject */
    private $loggerMock;

    /** @var DocumentExtractor */
    private $extractor;
    private $configProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->extractor = new DocumentExtractor($this->loggerMock, $this->configProvider);
    }

    public function testExtractReturnsEmptyArrayWhenNoItems(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->method('getAllItems')->willReturn([]);

        $result = $this->extractor->extract($quoteMock);
        $this->assertSame([], $result);
    }

    public function testExtractSkipsNonCustomerCanvasProducts(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getData')->with('is_customer_canvas')->willReturn(false);

        $item = $this->createMock(Item::class);
        $item->method('getProduct')->willReturn($product);

        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->method('getAllItems')->willReturn([$item]);

        $this->loggerMock->expects($this->never())->method('error');

        $result = $this->extractor->extract($quoteMock);
        $this->assertSame([], $result);
    }

    public function testExtractLogsErrorWhenJsonDecodeFails(): void
    {
        $product = $this->createConfiguredMock(Product::class, ['getData' => true]);

        $option = $this->createConfiguredMock(Option::class, [
            'getValue' => '{invalid_json',
        ]);

        $item = $this->createMock(Item::class);
        $item->method('getId')->willReturn(10);
        $item->method('getProduct')->willReturn($product);
        $item->method('getOptionByCode')->with('info_buyRequest')->willReturn($option);

        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->method('getAllItems')->willReturn([$item]);

        $this->loggerMock->expects($this->once())->method('error');

        $result = $this->extractor->extract($quoteMock);
        $this->assertSame([], $result);
    }

    public function testExtractReturnsValidDocumentData(): void
    {
        $product = $this->createConfiguredMock(Product::class, ['getData' => true]);

        $jsonData = json_encode([
            'productConfig' => [
                'configuratorStateId' => 'cfg123',
                'integratorProductReference' => 'ref456',
            ],
            'external_prod' => [
                (object)[
                    'contentAssociations' => [
                        (object)[
                            'purpose' => 'MAIN_CONTENT',
                            'contentReference' => 'doc789'
                        ],
                        (object)[
                            'purpose' => 'THUMBNAIL',
                            'contentReference' => 'ignored'
                        ]
                    ]
                ]
            ]
        ]);

        $option = $this->createConfiguredMock(Option::class, ['getValue' => $jsonData]);

        $item = $this->createMock(Item::class);
        $item->method('getId')->willReturn(15);
        $item->method('getProduct')->willReturn($product);
        $item->method('getOptionByCode')->with('info_buyRequest')->willReturn($option);

        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->method('getAllItems')->willReturn([$item]);

        $this->loggerMock->expects($this->never())->method('error');

        $result = $this->extractor->extract($quoteMock);

        $expected = [[
            'documentId' => 'doc789',
            'configuratorStateId' => 'cfg123',
            'integratorProductReference' => 'ref456',
        ]];

        $this->assertSame($expected, $result);
    }

    public function testExtractHandlesThrowableGracefully(): void
    {
        $item = $this->createMock(Item::class);
        $item->method('getProduct')->willThrowException(new \RuntimeException('Unexpected error'));
        $item->method('getId')->willReturn(22);

        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->method('getAllItems')->willReturn([$item]);

        $this->loggerMock->expects($this->once())->method('critical');

        $result = $this->extractor->extract($quoteMock);
        $this->assertSame([], $result);
    }
}
