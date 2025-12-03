<?php

declare (strict_types = 1);

namespace Fedex\MarketplaceProduct\Test\Unit\Model;

use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Framework\Model\Context;
use Magento\Framework\Pricing\PriceInfo\Factory as PriceInfoFactory;
use Magento\Framework\Registry;
use Fedex\MarketplaceProduct\Model\Offer;
use Mirakl\Connector\Model\ResourceModel\Offer\Collection as OfferCollection;
use Mirakl\Core\Model\ResourceModel\Offer\State\Collection as OfferStateCollection;
use Mirakl\Core\Model\ResourceModel\Offer\State\CollectionFactory as StateCollectionFactory;
use Mirakl\Core\Model\ResourceModel\ShopFactory as ShopResourceFactory;
use Mirakl\Core\Model\Shop;
use Mirakl\Core\Model\ShopFactory;
use Mirakl\MMP\Common\Domain\Collection\DiscountRangeCollection;
use Mirakl\MMP\Common\Domain\Discount;
use PHPUnit\Framework\TestCase;

/**
 * Test Offer
 */
class OfferTest extends TestCase
{
    /**
     * @var Offer
     */
    private $offer;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ProductType
     */
    private $catalogProductType;

    /**
     * @var PriceInfoFactory
     */
    private $priceInfoFactory;

    /**
     * @var StateCollectionFactory
     */
    private $stateCollectionFactory;

    /**
     * @var ShopFactory
     */
    private $shopFactory;

    /**
     * @var ShopResourceFactory
     */
    private $shopResourceFactory;


    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->context                = $this->createMock(Context::class);
        $this->registry               = $this->createMock(Registry::class);
        $this->catalogProductType     = $this->createMock(ProductType::class);
        $this->priceInfoFactory       = $this->createMock(PriceInfoFactory::class);
        $this->stateCollectionFactory = $this->createMock(StateCollectionFactory::class);
        $this->shopFactory            = $this->createMock(ShopFactory::class);
        $this->shopResourceFactory    = $this->createMock(ShopResourceFactory::class);

        $this->offer = $this->getMockBuilder(Offer::class)
            ->setConstructorArgs([
                $this->context,
                $this->registry,
                $this->createMock(\Mirakl\Connector\Model\ResourceModel\Offer::class),
                $this->createMock(OfferCollection::class),
                $this->catalogProductType,
                $this->priceInfoFactory,
                $this->stateCollectionFactory,
                $this->shopFactory,
                $this->shopResourceFactory,
            ])
            ->setMethods([
                'getAvailableStartDate',
                'getAvailableEndDate',
                'getChannels',
                'getCurrencyIsoCode',
                'getDescription',
                'getDiscountEndDate',
                'getDiscountStartDate',
                'getDiscountPrice',
                'getDiscountRanges',
                'getFavoriteRank',
                'getLogisticClass',
                'getMaxOrderQuantity',
                'getMinOrderQuantity',
                'getMinShippingPrice',
                'getMinShippingPriceAdditional',
                'getMinShippingType',
                'getMinShippingZone',
                'getOfferId',
                'getOriginPrice',
                'getPackageQuantity',
                'getPrice',
                'getPriceAdditionalInfo',
                'getProductSku',
                'getProductTaxCode',
                'getQuantity',
                'getShopId',
                'getStoreId',
                'setStoreId',
                'getShopName',
                'getStateCode',
                'getTotalPrice',
            ])
            ->getMock();
    }

    /**
     * @test
     */
    public function testGetId()
    {
        $this->offer->setData('offer_id', '123');
        $this->assertEquals('123', $this->offer->getId());
    }

    /**
     * @test
     */
    public function testSetId()
    {
        $this->offer->setId('123');
        $this->assertEquals('123', $this->offer->getData('offer_id'));
    }
}
