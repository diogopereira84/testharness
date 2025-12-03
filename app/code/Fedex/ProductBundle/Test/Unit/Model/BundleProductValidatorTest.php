<?php

declare(strict_types=1);

namespace Fedex\ProductBundle\Test\Unit\Model;

use Fedex\ProductBundle\Model\BundleProductValidator;
use Fedex\Cart\ViewModel\ProductInfoHandler;
use Fedex\ProductBundle\Api\ConfigInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class BundleProductValidatorTest extends TestCase
{
    private $checkoutSession;
    private $productInfoHandler;
    private $productBundleConfig;
    private $validator;
    private $quote;

    protected function setUp(): void
    {
        $this->checkoutSession = $this->createMock(CheckoutSession::class);
        $this->productInfoHandler = $this->createMock(ProductInfoHandler::class);
        $this->productBundleConfig = $this->createMock(ConfigInterface::class);
        $this->validator = new BundleProductValidator(
            $this->checkoutSession,
            $this->productInfoHandler,
            $this->productBundleConfig
        );
        $this->quote = $this->createMock(Quote::class);
    }

    // Helper to create a QuoteItem mock
    private function createQuoteItem(array $children = [], $parent = null, $instanceId = null)
    {
        $item = $this->getMockBuilder(QuoteItem::class)
            ->onlyMethods(['getChildren', 'getParentItem', 'getProduct'])
            ->addMethods(['getInstanceId'])
            ->disableOriginalConstructor()
            ->getMock();
        $item->method('getChildren')->willReturn($children);
        $item->method('getParentItem')->willReturn($parent);
        $item->method('getInstanceId')->willReturn($instanceId);
        return $item;
    }

    // Helper to create an OrderItem mock
    private function createOrderItem(array $childrenItems = [])
    {
        $item = $this->createMock(OrderItem::class);
        $item->method('getChildrenItems')->willReturn($childrenItems);
        return $item;
    }

    // --- Tests for isBundleProductSetupCompleted ---
    public function testIsBundleProductSetupCompletedToggleDisabledReturnsTrue()
    {
        $this->productBundleConfig->method('isTigerE468338ToggleEnabled')->willReturn(false);
        $this->assertTrue($this->validator->isBundleProductSetupCompleted());
    }

    public function testIsBundleProductSetupCompletedNoChildrenReturnsTrue()
    {
        $this->productBundleConfig->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $item = $this->createQuoteItem([]);
        $this->checkoutSession->method('getQuote')->willReturn($this->quote);
        $this->quote->method('getAllItems')->willReturn([$item]);
        $this->assertTrue($this->validator->isBundleProductSetupCompleted());
    }

    public function testIsBundleProductSetupCompletedMissingContentAssociationsReturnsFalse()
    {
        $this->productBundleConfig->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $child = $this->createQuoteItem();
        $item = $this->createQuoteItem([$child]);
        $this->checkoutSession->method('getQuote')->willReturn($this->quote);
        $this->quote->method('getAllItems')->willReturn([$item]);
        $this->productInfoHandler->method('getItemExternalProd')->willReturn(['contentAssociations' => []]);
        $this->assertFalse($this->validator->isBundleProductSetupCompleted());
    }

    public function testIsBundleProductSetupCompletedWithContentAssociationsReturnsTrue()
    {
        $this->productBundleConfig->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $child = $this->createQuoteItem();
        $item = $this->createQuoteItem([$child]);
        $this->checkoutSession->method('getQuote')->willReturn($this->quote);
        $this->quote->method('getAllItems')->willReturn([$item]);
        $this->productInfoHandler->method('getItemExternalProd')->willReturn(['contentAssociations' => ['foo']]);
        $this->assertTrue($this->validator->isBundleProductSetupCompleted());
    }

    // --- Tests for isBundleItemSetupCompleted ---
    public function testIsBundleItemSetupCompletedToggleDisabledReturnsTrue()
    {
        $this->productBundleConfig->method('isTigerE468338ToggleEnabled')->willReturn(false);
        $item = $this->createQuoteItem();
        $this->assertTrue($this->validator->isBundleItemSetupCompleted($item));
    }

    public function testIsBundleItemSetupCompletedNoChildrenReturnsTrue()
    {
        $this->productBundleConfig->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $item = $this->createQuoteItem([]);
        $this->assertTrue($this->validator->isBundleItemSetupCompleted($item));
    }

    public function testIsBundleItemSetupCompletedMissingContentAssociationsReturnsFalse()
    {
        $this->productBundleConfig->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $child = $this->createQuoteItem();
        $item = $this->createQuoteItem([$child]);
        $this->productInfoHandler->method('getItemExternalProd')->willReturn(['contentAssociations' => []]);
        $this->assertFalse($this->validator->isBundleItemSetupCompleted($item));
    }

    public function testIsBundleItemSetupCompletedWithContentAssociationsReturnsTrue()
    {
        $this->productBundleConfig->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $child = $this->createQuoteItem();
        $item = $this->createQuoteItem([$child]);
        $this->productInfoHandler->method('getItemExternalProd')->willReturn(['contentAssociations' => ['foo']]);
        $this->assertTrue($this->validator->isBundleItemSetupCompleted($item));
    }

    // --- Tests for isBundleParentSetupCompleted ---
    public function testIsBundleParentSetupCompletedToggleDisabledReturnsTrue()
    {
        $this->productBundleConfig->method('isTigerE468338ToggleEnabled')->willReturn(false);
        $item = $this->createQuoteItem();
        $this->assertTrue($this->validator->isBundleParentSetupCompleted($item));
    }

    public function testIsBundleParentSetupCompletedNoParentReturnsTrue()
    {
        $this->productBundleConfig->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $item = $this->createQuoteItem([], null);
        $item->method('getParentItem')->willReturn(null);
        $this->assertTrue($this->validator->isBundleParentSetupCompleted($item));
    }

    public function testIsBundleParentSetupCompletedParentSetupCompleted()
    {
        $this->productBundleConfig->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $parent = $this->createQuoteItem();
        $item = $this->createQuoteItem([], $parent);
        $this->validator = $this->getMockBuilder(BundleProductValidator::class)
            ->setConstructorArgs([
                $this->checkoutSession,
                $this->productInfoHandler,
                $this->productBundleConfig
            ])
            ->onlyMethods(['isBundleItemSetupCompleted'])
            ->getMock();
        $this->validator->method('isBundleItemSetupCompleted')->with($parent)->willReturn(true);
        $this->assertTrue($this->validator->isBundleParentSetupCompleted($item));
    }

    public function testIsBundleChildSetupCompletedToggleDisabledReturnsTrue()
    {
        $this->productBundleConfig->method('isTigerE468338ToggleEnabled')->willReturn(false);
        $item = $this->createQuoteItem();
        $this->assertTrue($this->validator->isBundleChildSetupCompleted($item));
    }

    public function testIsBundleChildSetupCompletedWithContentAssociationsReturnsTrue()
    {
        $this->productBundleConfig->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $item = $this->createQuoteItem();
        $this->productInfoHandler->method('getItemExternalProd')->willReturn(['contentAssociations' => ['foo']]);
        $this->assertTrue($this->validator->isBundleChildSetupCompleted($item));
    }

    public function testIsBundleChildSetupCompletedMissingContentAssociationsReturnsFalse()
    {
        $this->productBundleConfig->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $item = $this->createQuoteItem();
        $this->productInfoHandler->method('getItemExternalProd')->willReturn(['contentAssociations' => []]);
        $this->assertFalse($this->validator->isBundleChildSetupCompleted($item));
    }

    // --- Tests for hasBundleProductInCart ---
    public function testHasBundleProductInCartWithChildrenReturnsTrue()
    {
        $item = $this->createQuoteItem([1]);
        $this->checkoutSession->method('getQuote')->willReturn($this->quote);
        $this->quote->method('getAllItems')->willReturn([$item]);
        $this->assertTrue($this->validator->hasBundleProductInCart());
    }

    public function testHasBundleProductInCartNoChildrenReturnsFalse()
    {
        $item = $this->createQuoteItem([]);
        $this->checkoutSession->method('getQuote')->willReturn($this->quote);
        $this->quote->method('getAllItems')->willReturn([$item]);
        $this->assertFalse($this->validator->hasBundleProductInCart());
    }

    // --- Tests for hasQuoteItemWithInstanceId ---
    public function testHasQuoteItemWithInstanceIdFoundReturnsTrue()
    {
        $item = $this->createQuoteItem([], null, 'foo');
        $this->checkoutSession->method('getQuote')->willReturn($this->quote);
        $this->quote->method('getAllItems')->willReturn([$item]);
        $this->assertTrue($this->validator->hasQuoteItemWithInstanceId('foo'));
    }

    public function testHasQuoteItemWithInstanceIdNotFoundReturnsFalse()
    {
        $item = $this->createQuoteItem([], null, 'bar');
        $this->checkoutSession->method('getQuote')->willReturn($this->quote);
        $this->quote->method('getAllItems')->willReturn([$item]);
        $this->assertFalse($this->validator->hasQuoteItemWithInstanceId('foo'));
    }

    // --- Tests for getQuoteItems ---
    public function testGetQuoteItemsReturnsItems()
    {
        $item = $this->createQuoteItem();
        $this->checkoutSession->method('getQuote')->willReturn($this->quote);
        $this->quote->method('getAllItems')->willReturn([$item]);
        $this->assertSame([$item], $this->validator->getQuoteItems());
    }

    public function testGetQuoteItemsNoQuoteReturnsEmptyArray()
    {
        $this->checkoutSession->method('getQuote')->willReturn(null);
        $this->assertSame([], $this->validator->getQuoteItems());
    }

    // --- Tests for hasChildren ---
    public function testHasChildrenReturnsTrue()
    {
        $item = $this->createQuoteItem([1]);
        $this->assertTrue($this->validator->hasChildren($item));
    }

    public function testHasChildrenReturnsFalse()
    {
        $item = $this->createQuoteItem([]);
        $this->assertFalse($this->validator->hasChildren($item));
    }

    // --- Tests for hasParent ---
    public function testHasParentReturnsTrue()
    {
        $parent = $this->createQuoteItem();
        $item = $this->createQuoteItem([], $parent);
        $this->assertTrue($this->validator->hasParent($item));
    }

    public function testHasParentReturnsFalse()
    {
        $item = $this->createQuoteItem([], null);
        $this->assertFalse($this->validator->hasParent($item));
    }

    // --- Tests for getBundleChildrenCount ---
    public function testGetBundleChildrenCountWithChildren()
    {
        $child1 = $this->createQuoteItem();
        $child2 = $this->createQuoteItem();
        $item = $this->createQuoteItem([$child1, $child2]);
        $this->assertSame(2, $this->validator->getBundleChildrenCount($item));
    }

    public function testGetBundleChildrenCountNoChildren()
    {
        $item = $this->createQuoteItem([]);
        $this->assertSame(0, $this->validator->getBundleChildrenCount($item));
    }

    // --- Tests for getBundleChildrenItemsCount ---
    public function testGetBundleChildrenItemsCountWithOrderItem()
    {
        $child1 = $this->createOrderItem();
        $child2 = $this->createOrderItem();
        $item = $this->createOrderItem([$child1, $child2]);
        $this->assertSame(2, $this->validator->getBundleChildrenItemsCount($item));
    }

    public function testGetBundleChildrenItemsCountWithQuoteItem()
    {
        $child1 = $this->createQuoteItem();
        $child2 = $this->createQuoteItem();
        $item = $this->createQuoteItem([$child1, $child2]);
        $this->assertSame(2, $this->validator->getBundleChildrenItemsCount($item));
    }

    public function testGetBundleChildrenItemsCountNoChildren()
    {
        $item = $this->createQuoteItem([]);
        $this->assertSame(0, $this->validator->getBundleChildrenItemsCount($item));
    }

    public function testGetBundleChildrenItemsCountNoChildrenOrderItem()
    {
        $item = $this->createOrderItem([]);
        $this->assertSame(0, $this->validator->getBundleChildrenItemsCount($item));
    }

    // --- Tests for hasChildrenItems ---
    public function testHasChildrenItemsWithOrderItemReturnsTrue()
    {
        $child = $this->createOrderItem();
        $item = $this->createOrderItem([$child]);
        $this->assertTrue($this->validator->hasChildrenItems($item));
    }

    public function testHasChildrenItemsWithQuoteItemReturnsTrue()
    {
        $child = $this->createQuoteItem();
        $item = $this->createQuoteItem([$child]);
        $this->assertTrue($this->validator->hasChildrenItems($item));
    }

    public function testHasChildrenItemsNoChildrenReturnsFalse()
    {
        $item = $this->createQuoteItem([]);
        $this->assertFalse($this->validator->hasChildrenItems($item));
    }

    public function testIsBundleChildWithParentBundleReturnsTrue()
    {
        $parentProduct = $this->createMock(Product::class);
        $parentProduct->method('getTypeId')->willReturn(Type::TYPE_BUNDLE);
        $parent = $this->createQuoteItem();
        $parent->method('getProduct')->willReturn($parentProduct);
        $item = $this->createQuoteItem([], $parent);
        $this->assertTrue($this->validator->isBundleChild($item));
    }

    public function testIsBundleChildWithParentNotBundleReturnsFalse()
    {
        $parentProduct = $this->createMock(Product::class);
        $parentProduct->method('getTypeId')->willReturn('simple');
        $parent = $this->createQuoteItem();
        $parent->method('getProduct')->willReturn($parentProduct);
        $item = $this->createQuoteItem([], $parent);
        $this->assertFalse($this->validator->isBundleChild($item));
    }

    public function testIsBundleChildNoParentReturnsFalse()
    {
        $item = $this->createQuoteItem([], null);
        $this->assertFalse($this->validator->isBundleChild($item));
    }
}

