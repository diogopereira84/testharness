<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);
namespace Fedex\InBranch\Test\Unit\Helper;

use Magento\Checkout\Model\Session;
use PHPUnit\Framework\TestCase;
use Fedex\InBranch\Helper\Data;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote;
use Magento\Catalog\Model\Product;

class DataTest extends TestCase
{

    protected $cartSessionMock;
    protected $quoteMock;
    protected $quoteItemMock;
    protected $inBranchDataHelper;
    /**
     * Setup mock objects
     */
    protected function setUp(): void
    {
        $this->cartSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteMock = $this
            ->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inBranchDataHelper = (new ObjectManager($this))->getObject(
            Data::class,
            [
                'cartSession' => $this->cartSessionMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testCheckInCartINBranch()
    {
        $this->cartSessionMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->quoteItemMock]);
        $productMock = $this->getMockBuilder(Product::class)
            ->setMethods(['getProductLocationBranchNumber'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemMock->expects($this->any())->method('getProduct')->willReturn($productMock);
        $productMock->expects($this->any())->method('getProductLocationBranchNumber')->willReturn('0718');
        $this->assertEquals('0718', $this->inBranchDataHelper->checkInCartINBranch());
    }
     /**
      * @return void
      */
    public function testCheckInCartINBranchFalse()
    {
        $this->cartSessionMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->quoteItemMock]);
        $productMock = $this->getMockBuilder(Product::class)
            ->setMethods(['getProductLocationBranchNumber'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemMock->expects($this->any())->method('getProduct')->willReturn($productMock);
        $productMock->expects($this->any())->method('getProductLocationBranchNumber')->willReturn(false);
        $this->assertEquals(false, $this->inBranchDataHelper->checkInCartINBranch());
    }
}
