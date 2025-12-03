<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Model;

use Fedex\MarketplaceProduct\Api\Data\ShopInterface;
use Fedex\MarketplaceProduct\Api\OfferRepositoryInterface;
use Fedex\MarketplaceProduct\Api\ShopManagementInterface;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use Fedex\MarketplaceProduct\Model\ShopManagement;
use Magento\Catalog\Api\Data\ProductInterface;
use PHPUnit\Framework\TestCase;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;

class ShopManagementTest extends TestCase
{
    /**
     * @var OfferRepositoryInterface
     */
    private OfferRepositoryInterface $offerRepository;

    /**
     * @var ShopRepositoryInterface
     */
    private ShopRepositoryInterface $shopRepository;

    /**
     * @var ShopManagementInterface
     */
    private ShopManagementInterface $shopManagement;

    /**
     * @var AdminConfigHelper
     */
    private AdminConfigHelper $adminConfigHelper;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->offerRepository = $this->createMock(OfferRepositoryInterface::class);
        $this->shopRepository = $this->createMock(ShopRepositoryInterface::class);
        $this->adminConfigHelper = $this->createMock(AdminConfigHelper::class);
        $this->shopManagement = new ShopManagement(
            $this->offerRepository,
            $this->shopRepository,
            $this->adminConfigHelper
        );
    }

    public function testGetShopByProductId()
    {
        $productId = 1;
        $productSku = 'ABC-123';
        $shopId     = 2;
        $product    = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->expects($this->once())
            ->method('getSku')
            ->willReturn($productSku);

        $offerMock = $this->getMockBuilder(\Fedex\MarketplaceProduct\Api\Data\OfferInterface::class)
            ->setMethods(['getShopId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $offerMock->expects($this->once())
            ->method('getShopId')
            ->willReturn((string)$shopId);

        $this->offerRepository->expects($this->once())
            ->method('getById')
            ->with('ABC-123')
            ->willReturn($offerMock);

        $shopMock = $this->getMockBuilder(ShopInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shopRepository->expects($this->once())
            ->method('getById')
            ->with($shopId)
            ->willReturn($shopMock);

        $this->assertSame($shopMock, $this->shopManagement->getShopByProduct($product));
    }
}
