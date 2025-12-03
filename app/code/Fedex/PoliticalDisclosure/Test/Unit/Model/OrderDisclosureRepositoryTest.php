<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PoliticalDisclosure\Test\Unit\Model;

use Fedex\PoliticalDisclosure\Model\OrderDisclosure;
use Fedex\PoliticalDisclosure\Model\OrderDisclosureFactory;
use Fedex\PoliticalDisclosure\Model\OrderDisclosureRepository;
use Fedex\PoliticalDisclosure\Model\ResourceModel\OrderDisclosure as Resource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderDisclosureRepositoryTest extends TestCase
{
    /** @var OrderDisclosureFactory&MockObject */
    private OrderDisclosureFactory $factory;

    /** @var Resource&MockObject */
    private Resource $resource;

    private OrderDisclosureRepository $repository;

    protected function setUp(): void
    {
        $this->factory   = $this->createMock(OrderDisclosureFactory::class);
        $this->resource  = $this->createMock(Resource::class);

        $this->repository = new OrderDisclosureRepository(
            $this->factory,
            $this->resource
        );
    }

    public function testGetByOrderIdReturnsNullWhenNotFound(): void
    {
        $model = $this->createConfiguredMock(OrderDisclosure::class, ['getId' => null]);
        $this->factory->method('create')->willReturn($model);

        $this->resource
            ->expects($this->once())
            ->method('load')
            ->with($model, 123, 'order_id');

        $this->assertNull($this->repository->getByOrderId(123));
    }

    public function testGetByOrderIdReturnsModelWhenFound(): void
    {
        $model = $this->createConfiguredMock(OrderDisclosure::class, ['getId' => 42]);
        $this->factory->method('create')->willReturn($model);

        $this->resource
            ->expects($this->once())
            ->method('load')
            ->with($model, 123, 'order_id');

        $this->assertSame($model, $this->repository->getByOrderId(123));
    }

    public function testSaveDelegatesToResource(): void
    {
        $model = $this->createMock(OrderDisclosure::class);

        $this->resource
            ->expects($this->once())
            ->method('save')
            ->with($model);

        $result = $this->repository->save($model);
        $this->assertSame($model, $result);
    }

    public function testDeleteByOrderIdNotFoundReturnsFalse(): void
    {
        $model = $this->createConfiguredMock(OrderDisclosure::class, ['getId' => null]);
        $this->factory->method('create')->willReturn($model);

        $this->resource
            ->expects($this->once())
            ->method('load')
            ->with($model, 321, 'order_id');

        $this->assertFalse($this->repository->deleteByOrderId(321));
    }

    public function testDeleteByOrderIdDeletesAndReturnsTrue(): void
    {
        $model = $this->createConfiguredMock(OrderDisclosure::class, ['getId' => 99]);
        $this->factory->method('create')->willReturn($model);

        $this->resource
            ->expects($this->once())
            ->method('load')
            ->with($model, 321, 'order_id');

        $this->resource
            ->expects($this->once())
            ->method('delete')
            ->with($model);

        $this->assertTrue($this->repository->deleteByOrderId(321));
    }
}
