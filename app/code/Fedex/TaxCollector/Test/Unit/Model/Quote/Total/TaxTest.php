<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\TaxCollector\Test\Unit\Model\Quote\Total;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\TaxCollector\Model\Quote\Total\Tax;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote\Address\Total;

/**
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class TaxTest extends TestCase
{
    /**
     * @var (\Magento\Quote\Api\Data\ShippingInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $shippingInterfaceMock;
    protected $addressMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $taxMock;
    /**
     * @var Quote
     */
    private $quoteMock;

    /**
     * @var ShippingAssignmentInterface
     */
    private $shippingAssignmentInterfaceMock;

    /**
     * @var Total
     */
    private $totalMock;

    protected function setUp(): void
    {
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomTaxAmount', 'setTotalAmount', 'setBaseTotalAmount', 'setCustomTaxAmount'])
            ->getMock();

        $this->totalMock = $this->getMockBuilder(Total::class)
            ->disableOriginalConstructor()
            ->setMethods(['setTotalAmount', 'setBaseTotalAmount', 'setCustomTaxAmount'])
            ->getMock();

        $this->shippingAssignmentInterfaceMock = $this->getMockBuilder(ShippingAssignmentInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipping', 'getAddress', 'getItems'])
            ->getMockForAbstractClass();

        $this->shippingInterfaceMock = $this->getMockBuilder(ShippingInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAddress'])
            ->getMockForAbstractClass();

        $this->addressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->taxMock = $this->objectManager->getObject(Tax::class, []);
    }

    /**
     * Test calculate amount with custom tax
     * @return void
     */
    public function testCollect()
    {
        $this->shippingAssignmentInterfaceMock->expects($this->any())->method('getShipping')
            ->willReturnSelf();
        $this->shippingAssignmentInterfaceMock->expects($this->any())->method('getAddress')
            ->willReturn($this->addressMock);
        $this->shippingAssignmentInterfaceMock->expects($this->any())->method('getItems')
            ->willReturn([]);

        $this->assertEquals(
            $this->taxMock,
            $this->taxMock->collect(
                $this->quoteMock,
                $this->shippingAssignmentInterfaceMock,
                $this->totalMock
            )
        );
    }

    /**
     * Test calculate amount with custom tax
     * @return void
     */
    public function testCollectWithoutItems()
    {
        $this->shippingAssignmentInterfaceMock->expects($this->any())->method('getShipping')
            ->willReturnSelf();
        $this->shippingAssignmentInterfaceMock->expects($this->any())->method('getAddress')
            ->willReturn($this->addressMock);
        $this->shippingAssignmentInterfaceMock->expects($this->any())->method('getItems')
            ->willReturn([1]);
        $this->quoteMock->expects($this->any())->method('getCustomTaxAmount')->willReturn(12);

        $this->assertEquals(
            $this->taxMock,
            $this->taxMock->collect(
                $this->quoteMock,
                $this->shippingAssignmentInterfaceMock,
                $this->totalMock
            )
        );
    }

    /**
     * Test calculate amount with custom tax
     * @return void
     */
    public function testCollectWithoutCustomTaxAmount()
    {
        $this->shippingAssignmentInterfaceMock->expects($this->any())->method('getShipping')
            ->willReturnSelf();
        $this->shippingAssignmentInterfaceMock->expects($this->any())->method('getAddress')
            ->willReturn($this->addressMock);
        $this->shippingAssignmentInterfaceMock->expects($this->any())->method('getItems')
            ->willReturn([1]);
        $this->quoteMock->expects($this->any())->method('getCustomTaxAmount')->willReturn(null);

        $this->assertEquals(
            '',
            $this->taxMock->collect(
                $this->quoteMock,
                $this->shippingAssignmentInterfaceMock,
                $this->totalMock
            )
        );
    }

    /**
     * Test fetch custom tax totals
     * @return void
     */
    public function testFetch()
    {
        $expected = [
            'code' => 'custom_tax_amount',
            'title' => 'Custom Tax Amount',
            'value' => 12
        ];

        $this->quoteMock->expects($this->any())->method('getCustomTaxAmount')->willReturn(12);
        $testMethod = new \ReflectionMethod(
            \Fedex\TaxCollector\Model\Quote\Total\Tax::class,
            'clearValues',
        );
        $testMethod->invoke($this->taxMock, $this->totalMock);
        $this->assertEquals($expected, $this->taxMock->fetch($this->quoteMock, $this->totalMock));
    }

    /**
     * Test fetch custom tax totals
     * @return void
     */
    public function testFetchWithoutCustomTaxAmount()
    {
        $this->quoteMock->expects($this->any())->method('getCustomTaxAmount')->willReturn(null);

        $this->assertEquals([], $this->taxMock->fetch($this->quoteMock, $this->totalMock));
    }
}
