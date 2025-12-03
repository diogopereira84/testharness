<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Model;

use Fedex\MarketplaceProduct\Model\OfferRepository;
use Fedex\MarketplaceProduct\Api\Data\OfferInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use Mirakl\Connector\Model\ResourceModel\Offer as Resource;
use Fedex\MarketplaceProduct\Model\OfferFactory;

class OfferRepositoryTest extends TestCase
{
    /**
     * @var OfferRepository
     */
    protected $offerRepository;

    /**
     * @var OfferFactory
     */
    private OfferFactory $factory;

    /**
     * @var Resource
     */
    private Resource $resource;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->factory = $this->createMock(OfferFactory::class);
        $this->resource = $this->createMock(Resource::class);
        $this->offerRepository = new OfferRepository($this->factory, $this->resource);
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetById()
    {
        $shopId = '123';
        $shop   = $this->createMock(\Fedex\MarketplaceProduct\Model\Offer::class);

        $this->factory->expects($this->once())
            ->method('create')
            ->willReturn($shop);

        $this->resource->expects($this->once())
            ->method('load')
            ->with($shop, $shopId, 'product_sku');

        $shop->method('getId')
            ->willReturn($shopId);

        $result = $this->offerRepository->getById($shopId);

        $this->assertEquals($shop, $result);
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetByIdThrowsNoSuchEntityException()
    {
        $shopId = '123';
        $shop   = $this->createMock(\Fedex\MarketplaceProduct\Model\Offer::class);

        $this->factory->expects($this->once())
            ->method('create')
            ->willReturn($shop);

        $this->resource->expects($this->once())
            ->method('load')
            ->with($shop, $shopId, 'product_sku');

        $shop->method('getId')
            ->willReturn(null);

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Could not find offer id: 123.');

        $this->offerRepository->getById($shopId);
    }
}
