<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\Test\Unit\Service;

use Fedex\ProductBundle\Service\BundlePriceCalculator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BundlePriceCalculatorTest extends TestCase
{
    /**
     * @return void
     * @throws LocalizedException
     */
    public function testCalculateBundlePriceReturnsExpectedValue(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);
        $quoteRepositoryMock = $this->createMock(CartRepositoryInterface::class);

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->addMethods(['setIsSuperMode'])
            ->getMock();

        $quoteMock = $this->createMock(Quote::class);

        $childItemMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $childItemMock->method('getId')->willReturn(101);

        $parentItemMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getChildren', 'getQty', 'setCustomPrice', 'setPrice',
                'getProduct', 'getQuote', 'getId'
            ])
            ->addMethods(['setOriginalCustomPrice','setBasePrice','setDiscount',
                'setRowTotal','setBaseRowTotal','setDiscountAmount',
                'setBaseDiscountAmount', 'setPriceInclTax', 'setBasePriceInclTax'])
            ->getMock();

        $parentItemMock->method('getChildren')->willReturn([$childItemMock]);
        $parentItemMock->method('getQty')->willReturn(1.0);
        $parentItemMock->method('getProduct')->willReturn($productMock);
        $parentItemMock->method('getQuote')->willReturn($quoteMock);
        $parentItemMock->method('getId')->willReturn(555);

        // Expect repository save to be called
        $quoteRepositoryMock->expects($this->once())->method('save')->with($quoteMock);
        $productMock->expects($this->once())->method('setIsSuperMode')->with(true);
        $parentItemMock->expects($this->once())->method('setCustomPrice')->with(45.0);
        $parentItemMock->expects($this->once())->method('setOriginalCustomPrice')->with(45.0);
        $parentItemMock->expects($this->once())->method('setBasePrice')->with(45.0);
        $parentItemMock->expects($this->once())->method('setPrice')->with(45.0);
        $parentItemMock->expects($this->once())->method('setPriceInclTax')->with(50.0);
        $parentItemMock->expects($this->once())->method('setBasePriceInclTax')->with(50.0);
        $parentItemMock->expects($this->once())->method('setRowTotal')->with(50.0);
        $parentItemMock->expects($this->once())->method('setBaseRowTotal')->with(50.0);
        $parentItemMock->expects($this->once())->method('setDiscountAmount')->with(10.0);
        $parentItemMock->expects($this->once())->method('setBaseDiscountAmount')->with(10.0);
        $parentItemMock->expects($this->once())->method('setDiscount')->with(10.0);
        $quoteMock->expects($this->once())->method('collectTotals');
        $rateQuoteResponse = [
            'output' => [
                'rateQuote' => [
                    'rateQuoteDetails' => [
                        [
                            'productLines' => [
                                [
                                    'instanceId' => 101,
                                    'productRetailPrice' => 50.00,
                                    'productDiscountAmount' => 10.00,
                                    'productTaxAmount' => 5.00,
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $calculator = new BundlePriceCalculator($loggerMock, $quoteRepositoryMock);
        $calculator->calculateBundlePrice($rateQuoteResponse, $parentItemMock);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testCalculateBundlePriceAppliesCustomPriceAndSavesQuote(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $quoteRepositoryMock = $this->createMock(CartRepositoryInterface::class);

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->addMethods(['setIsSuperMode'])
            ->getMock();

        $quoteMock = $this->createMock(Quote::class);

        $childItemMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $childItemMock->method('getId')->willReturn(101);

        $parentItemMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getChildren', 'getQty', 'setCustomPrice', 'setPrice',
                'getProduct', 'getQuote', 'getId',
            ])
            ->addMethods([
                'setPriceInclTax','setBasePriceInclTax','setBaseDiscountAmount',
                'setBaseRowTotal','setOriginalCustomPrice','setDiscountAmount',
                'setBasePrice', 'setRowTotal', 'setDiscount'
            ])
            ->getMock();

        $parentItemMock->method('getChildren')->willReturn([$childItemMock]);
        $parentItemMock->method('getQty')->willReturn(2.0);
        $parentItemMock->method('getProduct')->willReturn($productMock);
        $parentItemMock->method('getQuote')->willReturn($quoteMock);
        $parentItemMock->method('getId')->willReturn(555);

        // Expect repository save to be called
        $quoteRepositoryMock->expects($this->once())->method('save')->with($quoteMock);
        $productMock->expects($this->once())->method('setIsSuperMode')->with(true);
        $quoteMock->expects($this->once())->method('collectTotals');

        // Expect all price setters to be called with correct values
        $parentItemMock->expects($this->once())->method('setCustomPrice')->with(45.0);
        $parentItemMock->expects($this->once())->method('setOriginalCustomPrice')->with(45.0);
        $parentItemMock->expects($this->once())->method('setBasePrice')->with(45.0);
        $parentItemMock->expects($this->once())->method('setPrice')->with(45.0);
        $parentItemMock->expects($this->once())->method('setPriceInclTax')->with(100.0);
        $parentItemMock->expects($this->once())->method('setBasePriceInclTax')->with(100.0);
        $parentItemMock->expects($this->once())->method('setRowTotal')->with(100.0);
        $parentItemMock->expects($this->once())->method('setBaseRowTotal')->with(100.0);
        $parentItemMock->expects($this->once())->method('setDiscountAmount')->with(20.0);
        $parentItemMock->expects($this->once())->method('setBaseDiscountAmount')->with(20.0);
        $parentItemMock->expects($this->once())->method('setDiscount')->with(20.0);

        // Input rate quote response
        $rateQuoteResponse = [
            'output' => [
                'rateQuote' => [
                    'rateQuoteDetails' => [
                        [
                            'productLines' => [
                                [
                                    'instanceId' => 101,
                                    'productRetailPrice' => 50.00,
                                    'productDiscountAmount' => 10.00,
                                    'productTaxAmount' => 5.00,
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $calculator = new BundlePriceCalculator($loggerMock, $quoteRepositoryMock);
        $calculator->calculateBundlePrice($rateQuoteResponse, $parentItemMock);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testCalculateBundlePriceHandlesZeroPrice(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $quoteRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->addMethods(['setIsSuperMode'])
            ->getMock();
        $quoteMock = $this->createMock(Quote::class);
        $childItemMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $childItemMock->method('getId')->willReturn(101);
        $parentItemMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getChildren', 'getQty', 'getProduct', 'getQuote', 'getId'])
            ->getMock();
        $parentItemMock->method('getChildren')->willReturn([$childItemMock]);
        $parentItemMock->method('getId')->willReturn(555);
        $calculator = new BundlePriceCalculator($loggerMock, $quoteRepositoryMock);
        $rateQuoteResponse = [
            'output' => [
                'rateQuote' => [
                    'rateQuoteDetails' => [
                        [
                            'productLines' => [
                                [
                                    'instanceId' => 101,
                                    'productRetailPrice' => 0.00,
                                    'productDiscountAmount' => 0.00,
                                    'productTaxAmount' => 0.00,
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $calculator->calculateBundlePrice($rateQuoteResponse, $parentItemMock);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testCalculateBundlePriceHandlesNoMatchingInstanceId(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $quoteRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->addMethods(['setIsSuperMode'])
            ->getMock();
        $quoteMock = $this->createMock(Quote::class);
        $childItemMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $childItemMock->method('getId')->willReturn(999);
        $parentItemMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getChildren', 'getQty', 'getProduct', 'getQuote', 'getId'])
            ->getMock();
        $parentItemMock->method('getChildren')->willReturn([$childItemMock]);
        $parentItemMock->method('getId')->willReturn(555);
        $calculator = new BundlePriceCalculator($loggerMock, $quoteRepositoryMock);
        $rateQuoteResponse = [
            'output' => [
                'rateQuote' => [
                    'rateQuoteDetails' => [
                        [
                            'productLines' => [
                                [
                                    'instanceId' => 101,
                                    'productRetailPrice' => 50.00,
                                    'productDiscountAmount' => 10.00,
                                    'productTaxAmount' => 5.00,
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $calculator->calculateBundlePrice($rateQuoteResponse, $parentItemMock);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testCalculateBundlePriceThrowsExceptionAndLogsError(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $quoteRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $invalidRateQuoteResponse = [
            'output' => [
                'rateQuote' => [
                    // Missing 'rateQuoteDetails'
                ]
            ]
        ];
        $loggerMock->expects($this->once())
            ->method('error')
            ->with(
                'Error calculating bundle price',
                $this->callback(function ($context) {
                    return isset($context['exception']) && $context['rateQuoteResponse'];
                })
            );
        $calculator = new BundlePriceCalculator($loggerMock, $quoteRepositoryMock);
        $calculator->calculateBundlePrice($invalidRateQuoteResponse, $this->createMock(QuoteItem::class));
    }
}
