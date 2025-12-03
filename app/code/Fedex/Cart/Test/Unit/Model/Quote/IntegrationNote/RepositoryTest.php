<?php

/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */

declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\Quote\IntegrationNote;

use Fedex\Cart\Api\Data\CartIntegrationNoteInterface;
use Fedex\Cart\Model\Quote\IntegrationNote\Command\DeleteByIdInterface;
use Fedex\Cart\Model\Quote\IntegrationNote\Command\GetInterface;
use Fedex\Cart\Model\Quote\IntegrationNote\Command\GetByParentIdInterface;
use Fedex\Cart\Model\Quote\IntegrationNote\Command\GetListInterface;
use Fedex\Cart\Model\Quote\IntegrationNote\Command\SaveInterface;
use Fedex\Cart\Model\Quote\IntegrationNote\Repository;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    /**
     * @var (\Fedex\Cart\Model\Quote\IntegrationNote\Command\GetByParentIdInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $commandGetByParentIdMock;
    /**
     * @var Repository
     */
    private Repository $instance;

    /**
     * @var DeleteByIdInterface|MockObject
     */
    private DeleteByIdInterface|MockObject $commandDeleteByIdMock;

    /**
     * @var GetInterface|MockObject
     */
    private GetInterface|MockObject $commandGetMock;

    /**
     * @var GetListInterface|MockObject
     */
    private GetListInterface|MockObject $commandGetListMock;

    /**
     * @var SaveInterface|MockObject
     */
    private SaveInterface|MockObject $commandSaveMock;

    /**
     * @var CartIntegrationNoteInterface|MockObject
     */
    private CartIntegrationNoteInterface|MockObject $cartIntegrationNoteMock;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        $this->commandDeleteByIdMock = $this->createMock(DeleteByIdInterface::class);
        $this->commandGetMock = $this->createMock(GetInterface::class);
        $this->commandGetByParentIdMock = $this->createMock(GetByParentIdInterface::class);
        $this->commandGetListMock = $this->createMock(GetListInterface::class);
        $this->commandSaveMock = $this->createMock(SaveInterface::class);
        $this->instance = new Repository(
            $this->commandDeleteByIdMock,
            $this->commandGetMock,
            $this->commandGetListMock,
            $this->commandSaveMock,
            $this->commandGetByParentIdMock
        );

        $this->cartIntegrationNoteMock = $this->createMock(CartIntegrationNoteInterface::class);
    }

    /**
     * Test case for IntegrationNote save method
     *
     * @return void
     */
    public function testSave(): void
    {
        $cartIntegrationNoteId = 1;

        $this->commandSaveMock->expects(static::once())
            ->method('execute')
            ->with($this->cartIntegrationNoteMock)
            ->willReturn($cartIntegrationNoteId);

        $result = $this->instance->save($this->cartIntegrationNoteMock);
        static::assertSame($result, $cartIntegrationNoteId);
    }

    /**
     * Test case for the get() method of the IntegrationNote repository.
     *
     * @return void
     */
    public function testGet(): void
    {
        $cartIntegrationNoteId = 1;

        $this->commandGetMock->expects(static::once())
            ->method('execute')
            ->with($cartIntegrationNoteId)
            ->willReturn($this->cartIntegrationNoteMock);

        $result = $this->instance->get($cartIntegrationNoteId);
        static::assertInstanceOf(CartIntegrationNoteInterface::class, $result);
    }

    /**
     * Test for the getList method of the IntegrationNote repository
     *
     * @return void
     * @covers \Fedex\Cart\Model\Quote\IntegrationNote\Repository::getList
     */
    public function testGetList(): void
    {
        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $searchResults = $this->createMock(SearchResultsInterface::class);
        $this->commandGetListMock->expects(static::once())
            ->method('execute')
            ->with($searchCriteria)
            ->willReturn($searchResults);

        $result = $this->instance->getList($searchCriteria);
        static::assertInstanceOf(SearchResultsInterface::class, $result);
    }

    /**
     * Test for the getByParentId method of the IntegrationNote repository
     *
     * @return void
     */
    public function testDeleteById(): void
    {
        $cartIntegrationNoteId = 3;

        $this->commandDeleteByIdMock->expects(static::once())
            ->method('execute')
            ->with($cartIntegrationNoteId);

        $this->instance->deleteById($cartIntegrationNoteId);
    }

    /**
     * Test getByParentId retrieves a cart integration note by parent ID
     */
    public function testGetByParentId(): void
    {
        $parentId = 42;

        $this->commandGetByParentIdMock->expects(static::once())
            ->method('execute')
            ->with($parentId)
            ->willReturn($this->cartIntegrationNoteMock);

        $result = $this->instance->getByParentId($parentId);

        static::assertInstanceOf(CartIntegrationNoteInterface::class, $result);

        static::assertSame($this->cartIntegrationNoteMock, $result);
    }
}
