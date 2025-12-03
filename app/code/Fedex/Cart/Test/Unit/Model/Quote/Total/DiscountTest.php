<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Test\Unit\Model\Quote\Total;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\QuoteValidator;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Tax\Model\Calculation;
use Magento\Framework\UrlInterface;
use Fedex\Cart\Model\Quote\Total\Discount;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\CartItemInterface;

class DiscountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Magento\Quote\Model\QuoteValidator & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteValidator;
    /**
     * @var (\Magento\Framework\Pricing\PriceCurrencyInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $priceCurrencyInterface;
    /**
     * @var (\Magento\Tax\Model\Calculation & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $calculation;
    protected $urlInterface;
    protected $total;
    protected $quote;
    protected $quoteItem;
    protected $shippingAssignmentInterface;
    protected $shippingInterface;
    protected $address;
    protected $cartItemInterface;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $discount;
    /**
     * @var Session|MockObject
     */
    protected $session;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->quoteValidator = $this->getMockBuilder(QuoteValidator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceCurrencyInterface = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->calculation = $this->getMockBuilder(Calculation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlInterface = $this->getMockBuilder(UrlInterface::class)
            ->setMethods(['getCurrentUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->total = $this->getMockBuilder(Total::class)
            ->setMethods([
                'setTotalAmount',
                'setBaseTotalAmount',
                'setSubtotalInclTax',
                'setBaseSubtotalInclTax'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods([
                'getAllVisibleItems',
                'getDiscount',
                'getCustomTaxAmount',
                'setCustomTaxAmount',
                'save',
                'isVirtual',
                'getBillingAddress',
                'getShippingAddress'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteItem = $this->getMockBuilder(Item::class)
            ->setMethods(['getDiscount'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingAssignmentInterface = $this->getMockBuilder(ShippingAssignmentInterface::class)
            ->setMethods(['getShipping', 'getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->shippingInterface = $this->getMockBuilder(ShippingInterface::class)
            ->setMethods(['getAddress'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartItemInterface = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->discount = $this->objectManager->getObject(
            Discount::class,
            [
                'quoteValidator' => $this->quoteValidator,
                'priceCurrency' => $this->priceCurrencyInterface,
                'taxCalculator' => $this->calculation,
                'urlInterface' => $this->urlInterface
            ]
        );
    }

    /**
     * Test for getLabel
     */
    public function testGetLabel()
    {
        $this->assertNotNull($this->discount->getLabel());
    }

    /**
     * Test for fetch
     */
    public function testFetch()
    {
        $this->quote->expects($this->any())->method('getDiscount')->willReturn(2);
        $this->assertNotNull($this->discount->fetch($this->quote, $this->total));
    }

    /**
     * Test for collect
     */
    public function testCollect()
    {
        $this->shippingAssignmentInterface->expects($this->any())->method('getShipping')
        ->willReturn($this->shippingInterface);
        $this->shippingInterface->expects($this->any())->method('getAddress')
        ->willReturn($this->address);
        $this->shippingAssignmentInterface->expects($this->any())->method('getItems')
        ->willReturn([$this->cartItemInterface]);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->quoteItem]);
        $this->quoteItem->expects($this->any())->method('getDiscount')->willReturn(2);
        $this->quote->expects($this->any())->method('getCustomTaxAmount')->willReturn(5);
        $this->urlInterface->expects($this->any())->method('getCurrentUrl')->willReturn('totals-information');
        $this->quote->expects($this->any())->method('setCustomTaxAmount')->willReturnSelf();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->assertNotNull($this->discount->collect($this->quote, $this->shippingAssignmentInterface, $this->total));
    }

    /**
     * Test for collect
     */
    public function testCollectWithoutItemsCount()
    {
        $this->shippingAssignmentInterface->expects($this->any())->method('getShipping')
        ->willReturn($this->shippingInterface);
        $this->shippingInterface->expects($this->any())->method('getAddress')
        ->willReturn($this->address);
        $this->shippingAssignmentInterface->expects($this->any())->method('getItems')
        ->willReturn([]);

        $this->assertNotNull($this->discount->collect($this->quote, $this->shippingAssignmentInterface, $this->total));
    }

    /**
     * Test for clearValues
     */
    public function testClearValues()
    {
        $this->total->expects($this->any())->method('setTotalAmount')->willReturnSelf();

        $this->total->expects($this->any())->method('setBaseTotalAmount')->willReturnSelf();

        $this->total->expects($this->once())->method('setSubtotalInclTax')->with(0);
        $this->total->expects($this->once())->method('setBaseSubtotalInclTax')->with(0);

        $reflection = new \ReflectionClass($this->discount);
        $method = $reflection->getMethod('clearValues');
        $method->setAccessible(true);

        $method->invoke($this->discount, $this->total);
    }
}

