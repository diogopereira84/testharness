<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Tiago Hayashi Daniel <tdaniel@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\Quote\Integration;

use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterfaceFactory;
use Fedex\Cart\Model\Quote\Integration;
use Fedex\Cart\Model\Quote\Integration\Repository;
use Fedex\Cart\Model\ResourceModel\Quote\Integration as ResourceData;
use Fedex\Cart\Model\ResourceModel\Quote\Integration\CollectionFactory as IntegrationCollectionFactory;
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
     * @var IntegrationCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $integrationCollectionFactoryMock;
    /**
     * @var CartIntegrationInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $integrationInterfaceFactoryMock;
    /**
     * @var DataObjectHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dataObjectHelperMock;
    /**
     * @var Repository
     */
    private $repository;
    /**
     * @var CartIntegrationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $integrationInterfaceMock;

    protected const EXCEPTION_MESSAGE = 'exception message';

    public function setUp(): void
    {
        $this->integrationInterfaceMock = $this->createMock(Integration::class);

        $this->resourceMock = $this->getMockBuilder(ResourceData::class)
            ->onlyMethods(['save', 'load', 'delete'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->integrationCollectionFactoryMock = $this->getMockBuilder(IntegrationCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->integrationInterfaceFactoryMock = $this->getMockBuilder(CartIntegrationInterfaceFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->dataObjectHelperMock = $this->getMockBuilder(DataObjectHelper::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->repository = new Repository(
            $this->resourceMock,
            $this->integrationCollectionFactoryMock,
            $this->integrationInterfaceFactoryMock,
            $this->dataObjectHelperMock,
            $this->loggerMock
        );
    }

    public function testSave()
    {
        $this->resourceMock->expects($this->once())->method('save')->willReturnSelf();
        $result = $this->repository->save($this->integrationInterfaceMock);

        $this->assertEquals($this->integrationInterfaceMock, $result);
    }

    public function testSaveException()
    {
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);
        $this->expectException(CouldNotSaveException::class);
        $this->resourceMock->expects($this->once())->method('save')
        ->willThrowException(new \Exception(self::EXCEPTION_MESSAGE));

        $this->repository->save($this->integrationInterfaceMock);
    }

    public function testGetById()
    {
        $integrationId = 15;
        $this->integrationInterfaceMock->expects($this->exactly(2))->method('getId')->willReturn($integrationId);
        $this->integrationInterfaceFactoryMock->expects($this->once())->method('create')->willReturn(
            $this->integrationInterfaceMock
        );
        $this->resourceMock->expects($this->once())->method('load')->willReturn(null);

        $result = $this->repository->getById($integrationId);

        $this->assertEquals($this->integrationInterfaceMock, $result);
        $this->assertEquals($integrationId, $result->getId());
    }

    public function testGetByQuoteId()
    {
        $quoteId = 15;
        $this->integrationInterfaceMock->expects($this->exactly(2))->method('getQuoteId')->willReturn($quoteId);
        $this->integrationInterfaceFactoryMock->expects($this->once())->method('create')->willReturn(
            $this->integrationInterfaceMock
        );
        $this->resourceMock->expects($this->once())->method('load')->willReturn(null);

        $result = $this->repository->getByQuoteId($quoteId);

        $this->assertEquals($this->integrationInterfaceMock, $result);
        $this->assertEquals($quoteId, $result->getQuoteId());
    }

    public function testGetByQuoteIdWithException()
    {
        $quoteId = 8;
        $this->expectException(NoSuchEntityException::class);

        $this->integrationInterfaceFactoryMock->expects($this->once())->method('create')->willReturn(
            $this->integrationInterfaceMock
        );
        $this->resourceMock->expects($this->once())->method('load')->willReturn(null);

        $result = $this->repository->getByQuoteId($quoteId);

        $this->assertEquals($this->integrationInterfaceMock, $result);
        $this->assertNotNull($result->getQuoteId());
    }

    public function testGetByIdException()
    {
        $integrationId = 15;

        $this->expectExceptionMessage('Requested integration doesn\'t exist');
        $this->expectException(NoSuchEntityException::class);

        $this->integrationInterfaceMock->expects($this->once())->method('getId')->willReturn(null);
        $this->integrationInterfaceFactoryMock->expects($this->once())->method('create')->willReturn(
            $this->integrationInterfaceMock
        );
        $this->resourceMock->expects($this->once())->method('load')->willReturn(null);

        $this->repository->getById($integrationId);
    }

    public function testDelete()
    {
        $this->integrationInterfaceMock->expects($this->once())->method('getId')->willReturn(1);
        $this->resourceMock->expects($this->once())->method('delete')->willReturn(true);

        $this->assertTrue($this->repository->delete($this->integrationInterfaceMock));
    }

    public function testDeleteValidatorException()
    {
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);
        $this->expectException(CouldNotSaveException::class);

        $this->integrationInterfaceMock->expects($this->once())->method('getId')->willReturn(1);
        $this->resourceMock->expects($this->once())->method('delete')
            ->willThrowException(new ValidatorException(__(self::EXCEPTION_MESSAGE)));

        $this->repository->delete($this->integrationInterfaceMock);
    }

    public function testDeleteException()
    {
        $this->expectExceptionMessage('Unable to remove integration 1');
        $this->expectException(StateException::class);

        $this->integrationInterfaceMock->expects($this->once())->method('getId')->willReturn(1);
        $this->resourceMock->expects($this->once())->method('delete')
            ->willThrowException(new \Exception(self::EXCEPTION_MESSAGE));

        $this->repository->delete($this->integrationInterfaceMock);
    }

    public function testDeleteById()
    {
        $integrationId = 15;
        $this->integrationInterfaceMock->expects($this->exactly(2))->method('getId')->willReturn($integrationId);
        $this->integrationInterfaceFactoryMock->expects($this->once())->method('create')->willReturn(
            $this->integrationInterfaceMock
        );
        $this->resourceMock->expects($this->once())->method('load')->willReturn(null);

        $this->assertTrue($this->repository->deleteById(1));
    }
}
