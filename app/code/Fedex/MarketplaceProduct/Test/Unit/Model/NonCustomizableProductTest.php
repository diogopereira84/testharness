<?php

namespace Fedex\MarketplaceProduct\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceProduct\Helper\Data as MiraklHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Catalog\Model\Product;
use Fedex\MarketplaceProduct\Model\Offer;
use Magento\CatalogInventory\Model\Configuration;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;

class NonCustomizableProductTest extends TestCase
{
    protected $stockRegistry;
    protected $logger;
    protected $imageHelper;
    private $toggleConfigMock;
    private $checkoutSessionMock;
    private $miraklHelperMock;
    private $nonCustomizableProduct;
    protected $configuration;
    private $marketplaceCheckoutHelper;
    private $attributeSetRepository;

    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->checkoutSessionMock = $this->createMock(CheckoutSession::class);
        $this->miraklHelperMock = $this->createMock(MiraklHelper::class);
        $this->stockRegistry = $this->getMockBuilder(StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->imageHelper = $this->createMock(Image::class);
        $this->marketplaceCheckoutHelper = $this->createMock(MarketplaceCheckoutHelper::class);
        $this->configuration = $this->getMockBuilder(Configuration::class)
            ->onlyMethods(['getMaxSaleQty', 'getMinSaleQty'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributeSetRepository = $this->createMock(AttributeSetRepositoryInterface::class);
        $this->nonCustomizableProduct = new NonCustomizableProduct(
            $this->toggleConfigMock,
            $this->checkoutSessionMock,
            $this->miraklHelperMock,
            $this->stockRegistry,
            $this->logger,
            $this->imageHelper,
            $this->configuration,
            $this->marketplaceCheckoutHelper,
            $this->attributeSetRepository
        );
    }


    public function testIsMktCbbEnabled()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(NonCustomizableProduct::XPATH_ENABLE_MKT_CBB_PRODUCTS)
            ->willReturn(true);

        $this->assertTrue($this->nonCustomizableProduct->isMktCbbEnabled());
    }

    public function testIsMktCbbDisabled()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(NonCustomizableProduct::XPATH_ENABLE_MKT_CBB_PRODUCTS)
            ->willReturn(false);

        $this->assertFalse($this->nonCustomizableProduct->isMktCbbEnabled());
    }

    public function testIsThirdPartyOnlyCartWithAnyPunchoutDisabledWhenMktCbbDisabled()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(false);

        $this->assertFalse($this->nonCustomizableProduct->isThirdPartyOnlyCartWithAnyPunchoutDisabled());
    }

    public function testIsThirdPartyOnlyCartWithAnyPunchoutDisabledWhenAllPunchoutEnabled()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);

        $quoteMock = $this->createMock(Quote::class);
        $this->checkoutSessionMock->method('getQuote')->willReturn($quoteMock);

        $itemMock1 = $this->getMockBuilder(Item::class)
            ->setMethods(['getProduct'])
            ->addMethods(['getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock2 = $this->getMockBuilder(Item::class)
            ->setMethods(['getProduct'])
            ->addMethods(['getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock->method('getAllVisibleItems')->willReturn([$itemMock1, $itemMock2]);

        $itemMock1->method('getAdditionalData')->willReturn(json_encode(['punchout_enabled' => true]));
        $itemMock2->method('getAdditionalData')->willReturn(json_encode(['punchout_enabled' => true]));

        $productMock1 = $this->createMock(Product::class);
        $productMock2 = $this->createMock(Product::class);

        $itemMock1->method('getProduct')->willReturn($productMock1);
        $itemMock2->method('getProduct')->willReturn($productMock2);

        $this->miraklHelperMock->method('hasAvailableOffersForProduct')
            ->willReturnMap([
                [$productMock1, true],
                [$productMock2, true]
            ]);

        $this->assertFalse($this->nonCustomizableProduct->isThirdPartyOnlyCartWithAnyPunchoutDisabled());
    }

    public function testIsThirdPartyOnlyCartWithAnyPunchoutDisabledWhenOnePunchoutDisabled()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);

        $quoteMock = $this->createMock(Quote::class);
        $this->checkoutSessionMock->method('getQuote')->willReturn($quoteMock);

        $itemMock1 = $this->getMockBuilder(Item::class)
            ->setMethods(['getProduct'])
            ->addMethods(['getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock2 = $this->getMockBuilder(Item::class)
            ->setMethods(['getProduct'])
            ->addMethods(['getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock->method('getAllVisibleItems')->willReturn([$itemMock1, $itemMock2]);

        $itemMock1->method('getAdditionalData')->willReturn(json_encode(['punchout_enabled' => true]));
        $itemMock2->method('getAdditionalData')->willReturn(json_encode(['punchout_enabled' => false]));

        $productMock1 = $this->createMock(Product::class);
        $productMock2 = $this->createMock(Product::class);

        $itemMock1->method('getProduct')->willReturn($productMock1);
        $itemMock2->method('getProduct')->willReturn($productMock2);

        $this->miraklHelperMock->method('hasAvailableOffersForProduct')
            ->willReturnMap([
                [$productMock1, true],
                [$productMock2, true]
            ]);

        $this->assertTrue($this->nonCustomizableProduct->isThirdPartyOnlyCartWithAnyPunchoutDisabled());
    }

    public function testIsThirdPartyOnlyCartWithAnyPunchoutDisabledWhenNoMiraklOffers()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);

        $quoteMock = $this->createMock(Quote::class);
        $this->checkoutSessionMock->method('getQuote')->willReturn($quoteMock);

        $itemMock = $this->getMockBuilder(Item::class)
            ->setMethods(['getProduct'])
            ->addMethods(['getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock->method('getAllVisibleItems')->willReturn([$itemMock]);

        $itemMock->method('getAdditionalData')->willReturn(json_encode(['punchout_enabled' => true]));

        $productMock = $this->createMock(Product::class);

        $itemMock->method('getProduct')->willReturn($productMock);

        $this->miraklHelperMock->method('hasAvailableOffersForProduct')
            ->with($productMock)
            ->willReturn(false);

        $this->assertTrue($this->nonCustomizableProduct->isThirdPartyOnlyCartWithAnyPunchoutDisabled());
    }

    public function testGetMinMaxPunchoutInfoWhenMktCbbDisabled()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(false);

        $product = $this->createMock(Product::class);
        $offerMock = $this->getMockBuilder(Offer::class)
            ->setMethods(['getAdditionalInfo'])
            ->addMethods(['getMinOrderQuantity', 'getMaxOrderQuantity'])
            ->disableOriginalConstructor()
            ->getMock();

        $result = $this->nonCustomizableProduct->getMinMaxPunchoutInfo($offerMock, $product);

        $this->assertEquals([null, null, false], $result);
    }

    public function testGetMinMaxPunchoutInfoWithValidOffer()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);

        $product = $this->createMock(Product::class);
        $offerMock = $this->getMockBuilder(Offer::class)
            ->setMethods(['getAdditionalInfo'])
            ->addMethods(['getMinOrderQuantity', 'getMaxOrderQuantity'])
            ->disableOriginalConstructor()
            ->getMock();
        $offerMock->method('getMinOrderQuantity')->willReturn(2);
        $offerMock->method('getMaxOrderQuantity')->willReturn(10);
        $offerMock->method('getAdditionalInfo')->willReturn(['punchout_enabled' => 'false']);

        $result = $this->nonCustomizableProduct->getMinMaxPunchoutInfo($offerMock, $product);

        $this->assertEquals([2, 10, true], $result);
    }

    public function testGetMinMaxPunchoutInfoWithNullOffer()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);

        $result = $this->nonCustomizableProduct->getMinMaxPunchoutInfo(null, null);

        $this->assertEquals([null, null, false], $result);
    }

    public function testIsProductPunchoutDisabledForThirdPartyItemWhenMktCbbDisabled()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(false);

        $itemMock = $this->getMockBuilder(Item::class)
            ->setMethods(['getProduct'])
            ->addMethods(['getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertFalse($this->nonCustomizableProduct->isProductPunchoutDisabledForThirdPartyItem($itemMock));
    }

    public function testIsProductPunchoutDisabledForThirdPartyItemWhenPunchoutEnabled()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);

        $itemMock = $this->getMockBuilder(Item::class)
            ->setMethods(['getProduct'])
            ->addMethods(['getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock->method('getAdditionalData')->willReturn(json_encode(['punchout_disabled' => false]));

        $this->assertFalse($this->nonCustomizableProduct->isProductPunchoutDisabledForThirdPartyItem($itemMock));
    }

    public function testIsProductPunchoutDisabledForThirdPartyItemWhenPunchoutDisabled()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);

        $itemMock = $this->getMockBuilder(Item::class)
            ->setMethods(['getProduct'])
            ->addMethods(['getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock->method('getAdditionalData')->willReturn(json_encode(['punchout_disabled' => true]));

        $this->assertFalse($this->nonCustomizableProduct->isProductPunchoutDisabledForThirdPartyItem($itemMock));
    }

    public function testIsProductPunchoutDisabledForThirdPartyItemWithInvalidJson()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);

        $itemMock = $this->getMockBuilder(Item::class)
            ->setMethods(['getProduct'])
            ->addMethods(['getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock->method('getAdditionalData')->willReturn('invalid json');

        $this->assertFalse($this->nonCustomizableProduct->isProductPunchoutDisabledForThirdPartyItem($itemMock));
    }

    public function testValidateProductMaxQtyWithinLimit()
    {
        $productId = 1;
        $itemQty = 5;
        $maxQty = 10;

        $stockItem = $this->createMock(StockItemInterface::class);
        $stockItem->method('getMaxSaleQty')->willReturn($maxQty);

        $this->stockRegistry->expects($this->once())
            ->method('getStockItem')
            ->with($productId)
            ->willReturn($stockItem);

        $this->logger->expects($this->never())->method('info');

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->willReturnMap([
                [NonCustomizableProduct::XPATH_D213961, false],
                [NonCustomizableProduct::XPATH_ENABLE_MKT_CBB_PRODUCTS, true]
            ]);

        $result = $this->nonCustomizableProduct->validateProductMaxQty($productId, $itemQty);
        $this->assertEquals('', $result);
    }

    public function testValidateProductMaxQtyWithinLimitToggleOn()
    {
        $itemQty = 5;
        $maxQty = 10;

        $product = $this->createMock(Product::class);
        $offerMock = $this->getMockBuilder(Offer::class)
            ->setMethods(['getAdditionalInfo'])
            ->addMethods(['getMaxOrderQuantity'])
            ->disableOriginalConstructor()
            ->getMock();
        $offerMock->expects($this->atMost(2))->method('getMaxOrderQuantity')->willReturn($maxQty);

        $this->miraklHelperMock->expects($this->once())
            ->method('getBestOffer')
            ->with($product)
            ->willReturn($offerMock);

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->willReturnMap([
                [NonCustomizableProduct::XPATH_D213961, true],
                [NonCustomizableProduct::XPATH_ENABLE_MKT_CBB_PRODUCTS, true]
            ]);

        $result = $this->nonCustomizableProduct->validateProductMaxQty($product, $itemQty);
        $this->assertEquals('', $result);
    }

    public function testValidateProductMaxQtyExceedsLimit()
    {
        $productId = 1;
        $itemQty = 15;
        $maxQty = 10;

        $stockItem = $this->createMock(StockItemInterface::class);
        $stockItem->method('getMaxSaleQty')->willReturn($maxQty);

        $this->stockRegistry->expects($this->once())
            ->method('getStockItem')
            ->with($productId)
            ->willReturn($stockItem);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Item quantity is greater than max quantity allowed.'));

        $result = $this->nonCustomizableProduct->validateProductMaxQty($productId, $itemQty);
        $this->assertEquals('Max Qty Allowed: 10', $result);
    }

    public function testValidateProductMaxQtyExceedsLimitToggleOn()
    {
        $itemQty = 10;
        $maxQty = 5;

        $product = $this->createMock(Product::class);
        $offerMock = $this->getMockBuilder(Offer::class)
            ->setMethods(['getAdditionalInfo'])
            ->addMethods(['getMaxOrderQuantity'])
            ->disableOriginalConstructor()
            ->getMock();
        $offerMock->expects($this->atMost(2))
            ->method('getMaxOrderQuantity')
            ->willReturn($maxQty);

        $this->miraklHelperMock->expects($this->once())
            ->method('getBestOffer')
            ->with($product)
            ->willReturn($offerMock);

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->willReturnMap([
                [NonCustomizableProduct::XPATH_D213961, true],
                [NonCustomizableProduct::XPATH_ENABLE_MKT_CBB_PRODUCTS, true]
            ]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Item quantity is greater than max quantity allowed.'));

        $result = $this->nonCustomizableProduct->validateProductMaxQty($product, $itemQty);
        $this->assertEquals('Max Qty Allowed: '.$maxQty, $result);
    }

    public function testGetProductImage()
    {
        $product = $this->createMock(Product::class);
        $imageId = 'product_small_image';
        $expectedUrl = 'http://example.com/image.jpg';

        $this->imageHelper->expects($this->once())
            ->method('init')
            ->with($product, $imageId)
            ->willReturnSelf();

        $this->imageHelper->expects($this->once())
            ->method('getUrl')
            ->willReturn($expectedUrl);

        $result = $this->nonCustomizableProduct->getProductImage($product, $imageId);
        $this->assertEquals($expectedUrl, $result);
    }

    /**
     * @return void
     */
    public function testGetMinMaxPunchoutInfoWithValidOfferWithEmptyMinAndMax()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $product = $this->createMock(Product::class);
        $offerMock = $this->getMockBuilder(Offer::class)
            ->setMethods(['getAdditionalInfo'])
            ->addMethods(['getMinOrderQuantity', 'getMaxOrderQuantity'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->configuration->method('getMaxSaleQty')->willReturn(999999);
        $this->configuration->method('getMinSaleQty')->willReturn(1);
        $offerMock->method('getMinOrderQuantity')->willReturn(null);
        $offerMock->method('getMaxOrderQuantity')->willReturn(null);
        $offerMock->method('getAdditionalInfo')->willReturn(['punchout_enabled' => 'false']);

        $result = $this->nonCustomizableProduct->getMinMaxPunchoutInfo($offerMock, $product);

        $this->assertEquals([null, null, 1], $result);
    }

    public function testCheckIfNonCustomizableProductReturnsFalseIfNoProduct()
    {
        $this->assertFalse($this->nonCustomizableProduct->checkIfNonCustomizableProduct(null));
    }

    public function testCheckIfNonCustomizableProductReturnsFalseIfNoAttributeSetId()
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getAttributeSetId')->willReturn(null);

        $this->assertFalse($this->nonCustomizableProduct->checkIfNonCustomizableProduct($product));
    }

    public function testCheckIfNonCustomizableProductReturnsTrueForMatchingAttributeSet()
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getAttributeSetId')->willReturn(123);

        $attributeSet = $this->createMock(\Magento\Eav\Api\Data\AttributeSetInterface::class);
        $attributeSet->method('getAttributeSetName')->willReturn(NonCustomizableProduct::FXO_NON_CUSTOMIZABLE_PRODUCTS_ATTR_SET);

        $this->attributeSetRepository->method('get')->with(123)->willReturn($attributeSet);

        $this->assertTrue($this->nonCustomizableProduct->checkIfNonCustomizableProduct($product));
    }

    public function testCheckIfNonCustomizableProductReturnsFalseForNonMatchingAttributeSet()
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getAttributeSetId')->willReturn(456);

        $attributeSet = $this->createMock(\Magento\Eav\Api\Data\AttributeSetInterface::class);
        $attributeSet->method('getAttributeSetName')->willReturn('OtherSet');

        $this->attributeSetRepository->method('get')->with(456)->willReturn($attributeSet);

        $this->assertFalse($this->nonCustomizableProduct->checkIfNonCustomizableProduct($product));
    }

    public function testLoadAttributeSetReturnsNullIfIdIsEmpty()
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Attribute set ID is empty.');

        $reflection = new \ReflectionClass($this->nonCustomizableProduct);
        $method = $reflection->getMethod('loadAttributeSet');
        $method->setAccessible(true);

        $result = $method->invoke($this->nonCustomizableProduct, null);
        $this->assertNull($result);
    }

    public function testLoadAttributeSetReturnsCachedValue()
    {
        $reflection = new \ReflectionClass($this->nonCustomizableProduct);
        $property = $reflection->getProperty('attributeSetLoaded');
        $property->setAccessible(true);
        $property->setValue($this->nonCustomizableProduct, [123 => 'TestSet']);

        $method = $reflection->getMethod('loadAttributeSet');
        $method->setAccessible(true);

        $result = $method->invoke($this->nonCustomizableProduct, 123);
        $this->assertEquals('TestSet', $result);
    }

    public function testLoadAttributeSetReturnsNameFromRepository()
    {
        $attributeSetMock = $this->createMock(\Magento\Eav\Api\Data\AttributeSetInterface::class);
        $attributeSetMock->method('getAttributeSetName')->willReturn('SetName');
        $this->attributeSetRepository->expects($this->once())
            ->method('get')
            ->with(456)
            ->willReturn($attributeSetMock);

        $reflection = new \ReflectionClass($this->nonCustomizableProduct);
        $method = $reflection->getMethod('loadAttributeSet');
        $method->setAccessible(true);

        $result = $method->invoke($this->nonCustomizableProduct, 456);
        $this->assertEquals('SetName', $result);
    }

    public function testLoadAttributeSetLogsErrorOnException()
    {
        $this->attributeSetRepository->expects($this->once())
            ->method('get')
            ->willThrowException(new \Exception('fail!'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error loading attribute set (ID: 789): fail!'));

        $reflection = new \ReflectionClass($this->nonCustomizableProduct);
        $method = $reflection->getMethod('loadAttributeSet');
        $method->setAccessible(true);

        $result = $method->invoke($this->nonCustomizableProduct, 789);
        $this->assertNull($result);
    }
}
