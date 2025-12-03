<?php

/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\Quote\IntegrationItem;

use Fedex\Cart\Api\Data\CartIntegrationItemInterface;
use Fedex\Cart\Api\Data\CartIntegrationItemInterfaceFactory;
use Fedex\Cart\Model\Quote\IntegrationItem;
use Fedex\Cart\Model\Quote\IntegrationItem\Repository;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationItem as ResourceData;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationItem\CollectionFactory as IntegrationItemCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RepositoryTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var ResourceData|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resourceMock;

    /**
     * @var IntegrationItemCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $integrationItemCollectionFactoryMock;

    /**
     * @var CartIntegrationItemInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $integrationItemInterfaceFactoryMock;

    /**
     * @var DataObjectHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dataObjectHelperMock;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var CartIntegrationItemInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $integrationItemInterfaceMock;

    protected const EXCEPTION_MESSAGE = 'exception message';

    public function setUp(): void
    {
        $this->integrationItemInterfaceMock = $this->createMock(IntegrationItem::class);

        $this->resourceMock = $this->getMockBuilder(ResourceData::class)
            ->onlyMethods(['save', 'load', 'delete'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->integrationItemCollectionFactoryMock = $this->getMockBuilder(
            IntegrationItemCollectionFactory::class
        )->disableOriginalConstructor()->getMockForAbstractClass();
        $this->integrationItemInterfaceFactoryMock = $this->getMockBuilder(
            CartIntegrationItemInterfaceFactory::class
        )->onlyMethods(['create'])->disableOriginalConstructor()->getMockForAbstractClass();
        $this->dataObjectHelperMock = $this->getMockBuilder(DataObjectHelper::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->repository = new Repository(
            $this->resourceMock,
            $this->integrationItemCollectionFactoryMock,
            $this->integrationItemInterfaceFactoryMock,
            $this->dataObjectHelperMock,
            $this->loggerMock
        );
    }

    public function testSave()
    {
        $this->resourceMock->expects($this->once())->method('save')->willReturnSelf();
        $result = $this->repository->save($this->integrationItemInterfaceMock);

        $this->assertEquals($this->integrationItemInterfaceMock, $result);
    }

    public function testSaveException()
    {
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);
        $this->expectException(CouldNotSaveException::class);
        $this->resourceMock->expects($this->once())->method('save')
        ->willThrowException(new \Exception(self::EXCEPTION_MESSAGE));

        $this->repository->save($this->integrationItemInterfaceMock);
    }

    public function testSaveByQuoteItemId()
    {
        $integrationItemId = 15;
        $itemData = '{}';

        $this->resourceMock->expects($this->any())->method('load')
            ->willThrowException(new NoSuchEntityException(__(self::EXCEPTION_MESSAGE)));
        $this->integrationItemInterfaceFactoryMock->expects($this->any())->method('create')->willReturn(
            $this->integrationItemInterfaceMock
        );
        $this->resourceMock->expects($this->once())->method('save')->willReturnSelf();
        $result = $this->repository->saveByQuoteItemId($integrationItemId, $itemData);

        $this->assertEquals($this->integrationItemInterfaceMock, $result);
    }

    public function testSaveByQuoteItemIdException()
    {

        $integrationItemId = 15;
        $itemData = '{}';

        $this->integrationItemInterfaceFactoryMock->expects($this->any())->method('create')->willReturn(
            $this->integrationItemInterfaceMock
        );
        $this->resourceMock->expects($this->any())->method('save')
            ->willThrowException(new ValidatorException(__(self::EXCEPTION_MESSAGE)));

        $this->expectException(CouldNotSaveException::class);

        $result = $this->repository->saveByQuoteItemId($integrationItemId, $itemData);

        $this->assertEquals($this->integrationItemInterfaceMock, $result);
    }

    public function testGetById()
    {
        $integrationItemId = 15;
        $this->integrationItemInterfaceMock->expects($this->exactly(2))
            ->method('getId')->willReturn($integrationItemId);
        $this->integrationItemInterfaceFactoryMock->expects($this->once())->method('create')->willReturn(
            $this->integrationItemInterfaceMock
        );
        $this->resourceMock->expects($this->once())->method('load')->willReturn(null);

        $result = $this->repository->getById($integrationItemId);

        $this->assertEquals($this->integrationItemInterfaceMock, $result);
        $this->assertEquals($integrationItemId, $result->getId());
    }

    public function testGetByQuoteItemId()
    {
        $integrationItemId = 15;
        $this->integrationItemInterfaceMock->expects($this->exactly(2))
            ->method('getItemId')->willReturn($integrationItemId);
        $this->integrationItemInterfaceFactoryMock->expects($this->once())->method('create')->willReturn(
            $this->integrationItemInterfaceMock
        );
        $this->resourceMock->expects($this->once())->method('load')->willReturn(null);

        $result = $this->repository->getByQuoteItemId($integrationItemId);

        $this->assertEquals($this->integrationItemInterfaceMock, $result);
        $this->assertEquals($integrationItemId, $result->getItemId());
    }

    public function testGetByQuoteItemIdWithException()
    {
        $integrationItemId = 0;
        $this->integrationItemInterfaceMock->expects($this->once())
            ->method('getItemId')->willReturn($integrationItemId);

        $this->expectExceptionMessage('Requested quote item id doesn\'t exist');
        $this->expectException(NoSuchEntityException::class);

        $this->integrationItemInterfaceFactoryMock->expects($this->once())->method('create')->willReturn(
            $this->integrationItemInterfaceMock
        );
        $this->resourceMock->expects($this->once())->method('load')->willReturn(null);

        $result = $this->repository->getByQuoteItemId($integrationItemId);

        $this->assertEquals($this->integrationItemInterfaceMock, $result);
        $this->assertEquals($integrationItemId, $result->getItemId());
    }

    public function testGetByIdException()
    {
        $integrationItemId = 15;

        $this->expectExceptionMessage('Requested integrationItem doesn\'t exist');
        $this->expectException(NoSuchEntityException::class);

        $this->integrationItemInterfaceMock->expects($this->once())->method('getId')->willReturn(null);
        $this->integrationItemInterfaceFactoryMock->expects($this->once())->method('create')->willReturn(
            $this->integrationItemInterfaceMock
        );
        $this->resourceMock->expects($this->once())->method('load')->willReturn(null);

        $this->repository->getById($integrationItemId);
    }

    public function testDelete()
    {
        $this->integrationItemInterfaceMock->expects($this->once())->method('getId')->willReturn(1);
        $this->resourceMock->expects($this->once())->method('delete')->willReturn(true);

        $this->assertTrue($this->repository->delete($this->integrationItemInterfaceMock));
    }

    public function testDeleteValidatorException()
    {
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);
        $this->expectException(CouldNotSaveException::class);

        $this->integrationItemInterfaceMock->expects($this->once())->method('getId')->willReturn(1);
        $this->resourceMock->expects($this->once())->method('delete')
            ->willThrowException(new ValidatorException(__(self::EXCEPTION_MESSAGE)));

        $this->repository->delete($this->integrationItemInterfaceMock);
    }

    public function testDeleteException()
    {
        $this->expectExceptionMessage('Unable to remove integrationItem 1');
        $this->expectException(StateException::class);

        $this->integrationItemInterfaceMock->expects($this->once())->method('getId')->willReturn(1);
        $this->resourceMock->expects($this->once())->method('delete')
            ->willThrowException(new \Exception(self::EXCEPTION_MESSAGE));

        $this->repository->delete($this->integrationItemInterfaceMock);
    }

    public function testDeleteById()
    {
        $integrationItemId = 15;
        $this->integrationItemInterfaceMock->expects($this->exactly(2))
            ->method('getId')->willReturn($integrationItemId);
        $this->integrationItemInterfaceFactoryMock->expects($this->once())->method('create')->willReturn(
            $this->integrationItemInterfaceMock
        );
        $this->resourceMock->expects($this->once())->method('load')->willReturn(null);

        $this->assertTrue($this->repository->deleteById(1));
    }
}
