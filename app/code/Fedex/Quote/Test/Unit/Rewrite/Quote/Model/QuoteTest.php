<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\Quote\Test\Unit\Rewrite\Quote\Model;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote as ParentQuoteModel;
use Fedex\Quote\Rewrite\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Framework\DataObject\Factory;
use Magento\Framework\DataObject;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory;

class QuoteTest extends TestCase
{
    protected $item;
    protected $quoteMock;
    protected $customerInterfaceMock;
    /**
     * @var (\Magento\Customer\Api\Data\AddressInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $addressInterfaceMock;
    protected $objectFactoryMock;
    protected $extensibleDataObjectConverterMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $quoteData;

    protected $quoteItemCollectionFactoryMock;
    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->item = $this->getMockBuilder(Item::class)
            ->setMethods([
                'getOptionByCode',
                'setDiscountAmount',
                'setBaseDiscountAmount',
                'setDiscount',
                'getProduct',
                'setRowTotal',
                'save',
                'setQuote',
                'getId',
                'getHasChildren',
                'getChildren',
            ])->disableOriginalConstructor()->getMock();

        $this->quoteMock = $this->getMockBuilder(ParentQuoteModel::class)
            ->setMethods(['getAllVisibleItems', 'getCouponCode'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerInterfaceMock = $this->getMockBuilder(CustomerInterface::class)
            ->setMethods(['getId', 'getAddresses', 'setAddresses'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->addressInterfaceMock = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectFactoryMock = $this->getMockBuilder(Magento\Framework\DataObject\Factory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->extensibleDataObjectConverterMock = $this->getMockBuilder(ExtensibleDataObjectConverter::class)
            ->setMethods(['toFlatArray'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteItemCollectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
             ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->quoteData = $this->objectManager->getObject(
            Quote::class,
            [
                'extensibleDataObjectConverter' => $this->extensibleDataObjectConverterMock
            ]
        );
    }

    /**
     * Test setCustomer Function
     */
    public function testSetCustomer()
    {
        $customerId = 12;
        $this->customerInterfaceMock->expects($this->any())->method('getId')->willReturn(12);
        $this->customerInterfaceMock->expects($this->any())->method('getAddresses')
        ->willReturn(['plano']);

        $this->extensibleDataObjectConverterMock->expects($this->any())
            ->method('toFlatArray')
            ->willReturn(['customer_id' => $customerId]);

        $this->objectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->customerInterfaceMock->expects($this->exactly(2))->method('setAddresses')
        ->will($this->onConsecutiveCalls([], ['plano']));

        $this->quoteData->setCustomer($this->customerInterfaceMock);
    }
}
