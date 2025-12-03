<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedDetails\Test\Unit\Model;

use Fedex\SharedDetails\Model\PickupStoreEmailResolver;
use Fedex\Shipment\Model\ProducingAddress;
use Fedex\Shipment\Model\ProducingAddressFactory;
use Fedex\Shipment\Model\ResourceModel\ProducingAddress\CollectionFactory as ProducingAddressCollectionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use IteratorAggregate;

class PickupStoreEmailResolverTest extends TestCase
{
    /** @var ProducingAddressCollectionFactory|MockObject */
    private $producingAddressCollectionFactoryMock;

    /** @var LoggerInterface|MockObject */
    private $loggerMock;

    /** @var PickupStoreEmailResolver */
    private $resolver;

    protected function setUp(): void
    {
        $this->producingAddressCollectionFactoryMock = $this->createMock(ProducingAddressCollectionFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->resolver = new PickupStoreEmailResolver(
            $this->producingAddressCollectionFactoryMock,
            $this->loggerMock
        );
    }

    public function testGetStoreEmailsByOrderIdsReturnsEmails(): void
    {
        $orderIds = [57341, 57340];
        $expectedEmails = [
            57341 => 'store1@example.com',
            57340 => 'store2@example.com'
        ];

        // Use addMethods for both getOrderId and getEmailAddress if they may not exist
        $producingAddress1 = $this->getMockBuilder(\Fedex\Shipment\Model\ProducingAddress::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOrderId', 'getEmailAddress'])
            ->getMock();
        $producingAddress1->method('getOrderId')->willReturn(57341);
        $producingAddress1->method('getEmailAddress')->willReturn('store1@example.com');

        $producingAddress2 = $this->getMockBuilder(\Fedex\Shipment\Model\ProducingAddress::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOrderId', 'getEmailAddress'])
            ->getMock();
        $producingAddress2->method('getOrderId')->willReturn(57340);
        $producingAddress2->method('getEmailAddress')->willReturn('store2@example.com');

        // Use addMethods for addFieldToFilter and getIterator on a mock that implements IteratorAggregate
        $collectionMock = $this->getMockBuilder(\ArrayObject::class)
            ->addMethods(['addFieldToFilter'])
            ->getMock();

        $collectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('order_id', ['in' => $orderIds])
            ->willReturnSelf();

        // Set the ArrayObject to contain our producing addresses
        $collectionMock->exchangeArray([$producingAddress1, $producingAddress2]);

        $this->producingAddressCollectionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        $result = $this->resolver->getStoreEmailsByOrderIds($orderIds);
        $this->assertEquals($expectedEmails, $result);
    }

    public function testGetStoreEmailsByOrderIdsReturnsEmptyForEmptyInput(): void
    {
        $result = $this->resolver->getStoreEmailsByOrderIds([]);
        $this->assertEquals([], $result);
    }

    public function testGetStoreEmailsByOrderIdsLogsException(): void
    {
        $orderIds = [57341, 57340];

        $this->producingAddressCollectionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willThrowException(new \Exception('DB error'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to get store emails'));

        $result = $this->resolver->getStoreEmailsByOrderIds($orderIds);
        $this->assertEquals([], $result);
    }
}
