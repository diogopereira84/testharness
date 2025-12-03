<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplacePunchout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Test\Model;

use Fedex\MarketplacePunchout\Model\ExpiredProducts;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\MarketplacePunchout\Model\ProductInfo;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\TestCase;

class ExpiredProductsTest extends TestCase
{
    protected $checkoutSessionMock;
    protected $productInfoMock;
    protected $timezoneMock;
    protected $expiredProducts;
    private const CURR_DATE = '2023-02-21';

    private const EXP_DATE = '2023-02-17';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->checkoutSessionMock = $this->createMock(CheckoutSession::class);
        $this->productInfoMock = $this->createMock(ProductInfo::class);
        $this->timezoneMock = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['date', 'getConfigTimezone', 'convertConfigTimeToUtc'])
            ->getMockForAbstractClass();

        $this->expiredProducts = new ExpiredProducts(
            $this->checkoutSessionMock,
            $this->productInfoMock,
            $this->timezoneMock
        );
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testExecuteReturnsExpiredItemIds(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $item1Mock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getSku'])
            ->addMethods(['getMiraklOfferId', 'getAdditionalData'])
            ->getMockForAbstractClass();
        $item2Mock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMiraklOfferId', 'getAdditionalData'])
            ->getMockForAbstractClass();
        $item3Mock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getSku'])
            ->addMethods(['getMiraklOfferId', 'getAdditionalData'])
            ->getMockForAbstractClass();

        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $quoteMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$item1Mock, $item2Mock, $item3Mock]);

        $item1Mock->expects($this->once())
            ->method('getMiraklOfferId')
            ->willReturn(123);

        $item1Mock->expects($this->exactly(2))
            ->method('getAdditionalData')
            ->willReturn('{"supplierPartAuxiliaryID": "456"}');

        $item2Mock->expects($this->once())
            ->method('getMiraklOfferId')
            ->willReturn(null);

        $item3Mock->expects($this->once())
            ->method('getMiraklOfferId')
            ->willReturn(789);

        $item1Mock->expects($this->once())
            ->method('getSku')
            ->willReturn('sku3');

        $item3Mock->expects($this->once())
            ->method('getSku')
            ->willReturn('sku3');

        $item3Mock->expects($this->exactly(2))
            ->method('getAdditionalData')
            ->willReturn('{"supplierPartAuxiliaryID": "987"}');
        $item3Mock->expects($this->exactly(2))->method('getId')->willReturn(10034);

        $this->productInfoMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(['456'], ['987'])
            ->willReturnOnConsecutiveCalls('navitorProductInfoResult', null);

        $expectedResult = [$item3Mock->getId()];

        $result = $this->expiredProducts->execute();

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testExecuteReturnsExpiredItemIdsByAdditionalData(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->addMethods(['getMiraklOfferId', 'getAdditionalData'])
            ->getMockForAbstractClass();
        $itemMock->expects($this->any())
            ->method('getMiraklOfferId')
            ->willReturn(123);
        $itemMock->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn('{"supplierPartAuxiliaryID": "456", "expire": 2}');
        $itemMock->expects($this->exactly(2))->method('getId')->willReturn(10034);

        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $quoteMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$itemMock]);

        $dateTime = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->setMethods(['modify', 'setTimezone'])
            ->getMock();
        $dateTime->expects($this->once())->method('modify')->willReturnSelf();
        $this->timezoneMock->expects($this->any())->method('date')->willReturn($dateTime);

        $this->timezoneMock->expects($this->exactly(2))->method('convertConfigTimeToUtc')
            ->willReturnOnConsecutiveCalls('');

        $this->timezoneMock->expects($this->exactly(2))->method('convertConfigTimeToUtc')
            ->withConsecutive([$dateTime], [$dateTime])
            ->willReturnOnConsecutiveCalls(self::EXP_DATE, self::CURR_DATE);

        $expectedResult = [$itemMock->getId()];
        $result = $this->expiredProducts->execute();

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testExecuteReturnsEmptyArrayWhenNoExpiredItems(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMiraklOfferId', 'getAdditionalData'])
            ->getMockForAbstractClass();

        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $quoteMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$itemMock]);

        $itemMock->expects($this->once())
            ->method('getMiraklOfferId')
            ->willReturn(null);

        $result = $this->expiredProducts->execute();

        $this->assertEquals([], $result);
    }
}
