<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Model;

use Fedex\MarketplaceProduct\Model\ShopFactory;
use Fedex\MarketplaceProduct\Model\ShopRepository;
use Fedex\MarketplaceProduct\Api\Data\ShopInterface;
use Fedex\MarketplaceProduct\Model\Shop;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use Mirakl\Core\Model\ResourceModel\Shop as Resource;

/**
 * Test
 */
class ShopRepositoryTest extends TestCase
{
    /**
     * @var ShopRepository
     */
    private ShopRepository $shopRepository;

    /**
     * @var ShopFactory
     */
    private ShopFactory $factory;

    /**
     * @var Resource
     */
    private Resource $resource;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->factory = $this->createMock(ShopFactory::class);
        $this->resource = $this->createMock(Resource::class);
        $this->shopRepository = new ShopRepository($this->factory, $this->resource);
    }

    /**
     * Test getById()
     */
    public function testGetById()
    {
        $shopId = '123';
        $shop   = $this->createMock(\Fedex\MarketplaceProduct\Model\Shop::class);

        $this->factory->expects($this->once())
            ->method('create')
            ->willReturn($shop);

        $this->resource->expects($this->once())
            ->method('load')
            ->with($shop, $shopId);

        $shop->method('getId')
            ->willReturn($shopId);

        $result = $this->shopRepository->getById(123);

        $this->assertEquals($shop, $result);
    }

    /**
     * Test getById() with NoSuchEntityException
     */
    public function testGetByIdNoSuchEntityException()
    {
        $shopId = 123;
        $shop   = $this->createMock(\Fedex\MarketplaceProduct\Model\Offer::class);

        $this->factory->expects($this->once())
            ->method('create')
            ->willReturn($shop);

        $this->resource->expects($this->once())
            ->method('load')
            ->with($shop, $shopId);

        $shop->method('getId')
            ->willReturn(null);

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Could not find shop id: 123.');

        $this->shopRepository->getById($shopId);
    }


    /**
     * Test getByIds() returns associative array of shops (dedup + cache reuse)
     */
    public function testGetByIds(): void
    {
        $idsRequested = [10, 20, 10];
        $uniqueIds = [10, 20];

        $shop10 = $this->createMock(Shop::class);
        $shop20 = $this->createMock(Shop::class);

        $this->factory->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($shop10, $shop20);

        $this->resource->expects($this->exactly(2))
            ->method('load')
            ->withConsecutive(
                [$shop10, $uniqueIds[0]],
                [$shop20, $uniqueIds[1]]
            );

        $shop10->method('getId')->willReturn('10');
        $shop20->method('getId')->willReturn('20');

        $result = $this->shopRepository->getByIds($idsRequested);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey(10, $result);
        $this->assertArrayHasKey(20, $result);
        $this->assertSame($shop10, $result[10]);
        $this->assertSame($shop20, $result[20]);
    }

    /**
     * Test getByIds() propagates NoSuchEntityException when one ID is missing
     */
    public function testGetByIdsWithMissingShop(): void
    {
        $idsRequested = [1, 2];

        $shop1 = $this->createMock(Shop::class);
        $shop2 = $this->createMock(Shop::class);

        $this->factory->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($shop1, $shop2);

        $this->resource->expects($this->exactly(2))
            ->method('load')
            ->withConsecutive(
                [$shop1, 1],
                [$shop2, 2]
            );

        $shop1->method('getId')->willReturn('1');
        $shop2->method('getId')->willReturn(null);

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Could not find shop id: 2.');

        $this->shopRepository->getByIds($idsRequested);
    }
}
