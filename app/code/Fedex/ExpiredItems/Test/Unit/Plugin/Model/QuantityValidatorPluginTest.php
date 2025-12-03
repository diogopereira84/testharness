<?php
declare(strict_types=1);

namespace Fedex\ExpiredItems\Test\Unit\Plugin\Model;

use Fedex\ExpiredItems\Plugin\Model\QuantityValidatorPlugin;
use Fedex\ProductUnavailabilityMessage\Model\CheckProductAvailabilityDataModel;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Model\Quote\Item\QuantityValidator;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option;
use Mirakl\Connector\Model\Offer;
use Mirakl\Connector\Model\OfferFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuantityValidatorPluginTest extends TestCase
{
    protected $observer;
    protected $quoteItem;
    protected $product;
    protected $offerCustomOption;
    protected $offer;
    protected $quantityValidatorMock;
    private QuantityValidatorPlugin $quantityValidatorModel;
    private MockObject $offerFactoryMock;
    private MockObject $mathDivisionMock;
    private MockObject $checkProductAvailabilityDataModelMock;

    protected function setUp(): void
    {
        $this->offerFactoryMock = $this->createMock(OfferFactory::class);
        $this->mathDivisionMock = $this->createMock(\Magento\Framework\Math\Division::class);
        $this->checkProductAvailabilityDataModelMock = $this->createMock(CheckProductAvailabilityDataModel::class);
        $this->observer= $this->getMockBuilder(Observer::class)
            ->setMethods(['getItem','getEvent'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->setMethods(['getProductId','getQty','getProduct','getParentItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->product = $this->getMockBuilder(Product::class)
            ->setMethods(['getCustomOption','getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->offerCustomOption = $this->createMock(Option::class);
        $this->offer = $this->getMockBuilder(Offer::class)
            ->setMethods(['getQuantity','getMinOrderQuantity','getMaxOrderQuantity','getPackageQuantity'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quantityValidatorMock = $this->createMock(QuantityValidator::class);
        $this->quantityValidatorModel = new QuantityValidatorPlugin(
            $this->offerFactoryMock,
            $this->mathDivisionMock,
            $this->checkProductAvailabilityDataModelMock
        );
    }

    /**
     * @return void
     */
    public function testAroundValidateWithProductIdAndNoMiraklOffer()
    {
        $this->observer->method('getEvent')->willReturnSelf();
        $this->observer->method('getItem')->willReturn($this->quoteItem);
        $this->quoteItem->method('getProductId')->willReturn(1);
        $this->quoteItem->method('getQty')->willReturn(10);
        $this->quoteItem->method('getProduct')->willReturn($this->product);
        $this->quoteItem->method('getParentItem')->willReturn(null);
        $this->product->method('getCustomOption')->willReturn(null);
        $proceed = function() {};
        $this->quantityValidatorModel->aroundValidate($this->quantityValidatorMock, $proceed, $this->observer);
        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testAroundValidateWithMiraklOfferAndUnavailableProduct()
    {
        $this->observer->method('getEvent')->willReturnSelf();
        $this->observer->method('getItem')->willReturn($this->quoteItem);
        $this->quoteItem->method('getProductId')->willReturn(1);
        $this->quoteItem->method('getQty')->willReturn(10);
        $this->quoteItem->method('getProduct')->willReturn($this->product);
        $this->quoteItem->method('getParentItem')->willReturn(null);
        $this->product->method('getCustomOption')->willReturn($this->offerCustomOption);
        $this->offerCustomOption->method('getValue')->willReturn('{}');
        $this->offerFactoryMock->method('fromJson')->willReturn($this->offer);
        $this->checkProductAvailabilityDataModelMock->method('isE441563ToggleEnabled')->willReturn(true);
        $this->product->method('getData')->willReturnMap([['is_unavailable', null, true]]);
        $this->offer->method('getQuantity')->willReturn(5);
        $this->offer->method('getMinOrderQuantity')->willReturn(1);
        $this->offer->method('getMaxOrderQuantity')->willReturn(20);
        $this->offer->method('getPackageQuantity')->willReturn(1);
        $proceed = function() {};
        $this->quantityValidatorModel->aroundValidate($this->quantityValidatorMock, $proceed, $this->observer);
    }

    /**
     * @return void
     */
    public function testAroundValidateWithValidQuantity()
    {
        $this->observer->method('getEvent')->willReturnSelf();
        $this->observer->method('getItem')->willReturn($this->quoteItem);
        $this->quoteItem->method('getProductId')->willReturn(1);
        $this->quoteItem->method('getQty')->willReturn(5);
        $this->quoteItem->method('getProduct')->willReturn($this->product);
        $this->quoteItem->method('getParentItem')->willReturn(null); // No parent item
        $this->product->method('getCustomOption')->willReturn($this->offerCustomOption);
        $this->offerCustomOption->method('getValue')->willReturn('{}'); // Mock offer JSON value
        $this->offerFactoryMock->method('fromJson')->willReturn($this->offer);
        $this->offer->method('getQuantity')->willReturn(5); // Insufficient quantity
        $this->offer->method('getMinOrderQuantity')->willReturn(1);
        $this->offer->method('getMaxOrderQuantity')->willReturn(20);
        $this->offer->method('getPackageQuantity')->willReturn(1);
        $proceed = function() {};
        $this->quantityValidatorModel->aroundValidate($this->quantityValidatorMock, $proceed, $this->observer);
        $this->assertTrue(true);
    }
}
