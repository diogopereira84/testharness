<?php
declare(strict_types=1);

namespace Fedex\LiveSearch\Test\Unit\Model;

use Fedex\LiveSearch\Model\ChildProductOffer;
use Fedex\MarketplaceProduct\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ChildProductOfferTest extends TestCase
{
    private $productRepositoryMock;
    private $marketPlaceProductHelperMock;
    private $configurableMock;
    private $childProductOffer;

    protected function setUp(): void
    {
        $this->productRepositoryMock = $this->createMock(ProductRepository::class);
        $this->marketPlaceProductHelperMock = $this->createMock(Data::class);
        $this->configurableMock = $this->createMock(Configurable::class);

        $this->childProductOffer = new ChildProductOffer(
            $this->productRepositoryMock,
            $this->marketPlaceProductHelperMock,
            $this->configurableMock
        );
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testGetChildProductOfferIdReturnsOfferIdForValidConfigurableProduct(): void
    {
        $parentProduct = $this->createMock(Product::class);
        $parentProduct->method('getTypeId')->willReturn(Configurable::TYPE_CODE);

        $selectedOptions = ['size' => 'M'];

        $childProduct = $this->createMock(Product::class);

        $this->configurableMock->method('getProductByAttributes')->with($selectedOptions, $parentProduct)
            ->willReturn($childProduct);

        $bestOfferMock = $this->createMock(\Mirakl\Connector\Model\Offer::class);
        $bestOfferMock->method('getId')->willReturn(12345);
        $this->marketPlaceProductHelperMock->method('getBestOffer')->with($childProduct)
            ->willReturn($bestOfferMock);

        $offerId = $this->childProductOffer->getChildProductOfferId($parentProduct, $selectedOptions);
        $this->assertEquals(12345, $offerId);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testGetChildProductOfferIdThrowsLocalizedExceptionForNonConfigurableProduct(): void
    {
        $parentProduct = $this->createMock(Product::class);
        $parentProduct->method('getTypeId')->willReturn('simple');

        $selectedOptions = ['size' => 'M'];

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('The provided product is not a configurable product.');

        $this->childProductOffer->getChildProductOfferId($parentProduct, $selectedOptions);
    }
}
