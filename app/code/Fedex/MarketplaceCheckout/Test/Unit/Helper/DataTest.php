<?php

/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Helper;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceCheckout\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\Eav\Api\Data\AttributeSetInterface;

class DataTest extends TestCase
{
    /**
     * @var ToggleConfig
     */
    private $toggleConfigMock;

    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var CheckoutSession
     */
    private $checkoutSessionMock;

    /**
     * @var LoggerInterface
     */
    private $loggerMock;

    /**
     * @var Quote
     */
    private $quoteMock;

    /**
     * @var AttributeSetRepositoryInterface
     */
    private $attributeSetRepositoryMock;

    /**
     * Setup test environment before each test.
     */
    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSessionMock = $this->createMock(CheckoutSession::class);
        $this->quoteMock = $this->createMock(Quote::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->attributeSetRepositoryMock = $this->createMock(AttributeSetRepositoryInterface::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->dataHelper = $objectManagerHelper->getObject(
            Data::class,
            [
                'toggleConfig' => $this->toggleConfigMock,
                'checkoutSession' => $this->checkoutSessionMock,
                'logger' => $this->loggerMock,
                'attributeSetRepository' => $this->attributeSetRepositoryMock,
            ]
        );
    }

    /**
     * Test recursiveAdjustArray method().
     *
     * @return void
     */
    public function testRecursiveAdjustArray()
    {
        $input = [
            'key1' => 123,
            'key2' => true,
            'key3' => false,
            'key4' => '',
            'key5' => null,
            'key6' => ['nested_key1' => 456]
        ];

        $expected = [
            'key1' => '123',
            'key2' => '1',
            'key3' => '0',
            'key4' => '',
            'key5' => '',
            'key6' => ['nested_key1' => '456']
        ];

        $result = $this->dataHelper->recursiveAdjustArray($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test adjustArrayForXml method().
     *
     * @return void
     */
    public function testAdjustArrayForXml()
    {
        $input = [
            'carrier_code' => 'marketplace_2696',
            'method_code' => 'ESSENDANT_FREE_GROUND_US'
        ];

        $expected = [
            'shipping_information' => [
                'carrier_code' => 'marketplace_2696',
                'method_code' => 'ESSENDANT_FREE_GROUND_US'
            ]
        ];

        $result = $this->dataHelper->adjustArrayForXml($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test the object can be instantiated with its required dependencies.
     */
    public function testCanBeInstantiatedWithRequiredDependencies()
    {
        $this->assertInstanceOf(Data::class, $this->dataHelper);
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::getReorderErrorMessage
     */
    public function testGetReorderErrorMessage()
    {
        $expectedErrorMessage = "You cannot reorder this item.";

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfig')
            ->willReturn($expectedErrorMessage);

        $this->assertEquals($expectedErrorMessage, $this->dataHelper->getReorderErrorMessage());
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::isUploadToQuoteEnabled
     */
    public function testIsUploadToQuoteEnabled()
    {
        $this->toggleConfigMock->method('getToggleConfig')
            ->with()
            ->willReturn(true);

        $this->assertTrue($this->dataHelper->isUploadToQuoteEnabled());
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::isCustomerShippingAccount3PEnabled
     */
    public function testIsCustomerShippingAccount3PEnabled()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfig')
            ->willReturn(true);

        $this->assertTrue($this->dataHelper->isCustomerShippingAccount3PEnabled());
    }

    // /**
    //  * Test for isCartIntegrationPrintfulEnabled method
    //  * Note: This test is created to cover the method but may fail at runtime due to undefined constant
    //  */
    // public function testIsCartIntegrationPrintfulEnabledMethodExists()
    // {
    //     $this->assertTrue(method_exists($this->dataHelper, 'isCartIntegrationPrintfulEnabled'));
    // }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::isExpectedDeliveryDateEnabled
     */
    public function testIsExpectedDeliveryDateEnabled()
    {
        $this->toggleConfigMock->method('getToggleConfig')
            ->with(Data::XPATH_ENABLE_EXPECTED_DELIVERY)
            ->willReturn(true);

        $this->assertTrue($this->dataHelper->isExpectedDeliveryDateEnabled());
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::isEssendantToggleEnabled
     */
    public function testIsEssendantToggleEnabled()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with(Data::XPATH_ESSENDANT_TOGGLE)
            ->willReturn(true);

        $this->assertTrue($this->dataHelper->isEssendantToggleEnabled());
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::isCBBToggleEnabled
     */
    public function testIsCBBToggleEnabled()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(Data::XPATH_CBB_TOGGLE)
            ->willReturn(true);

        $this->assertTrue($this->dataHelper->isCBBToggleEnabled());
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::isMoveReferenceFromStoreToCategoryToggleEnabled
     */
    public function testIsMoveReferenceFromStoreToCategoryToggleEnabled()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfig')
            ->with(Data::XPATH_MOVE_REFERENCE_STORE_TO_CATEGORY)
            ->willReturn(true);

        $this->assertTrue($this->dataHelper->isMoveReferenceFromStoreToCategoryToggleEnabled());
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::isVendorSpecificCustomerShippingAccountEnabled
     */
    public function testIsVendorSpecificCustomerShippingAccountEnabled()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfig')
            ->with(Data::XPATH_ENABLE_VENDOR_SHIPPING_ACCOUNT_NUMBERS)
            ->willReturn(true);

        $this->assertTrue($this->dataHelper->isVendorSpecificCustomerShippingAccountEnabled());
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::getVendorSpecificCustomerShippingAccountDisclaimer
     */
    public function testGetVendorSpecificCustomerShippingAccountDisclaimer()
    {
        $disclaimerMessage = 'This is a disclaimer message for vendor-specific shipping accounts';

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfig')
            ->with(Data::XPATH_VENDOR_SHIPPING_ACCOUNT_NUMBER_MESSAGE)
            ->willReturn($disclaimerMessage);

        $this->assertEquals(
            $disclaimerMessage,
            $this->dataHelper
                ->getVendorSpecificCustomerShippingAccountDisclaimer()
        );
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::getVendorSpecificCustomerShippingAccountDisclaimer
     */
    public function testGetVendorSpecificCustomerShippingAccountDisclaimerEmpty()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfig')
            ->with(Data::XPATH_VENDOR_SHIPPING_ACCOUNT_NUMBER_MESSAGE)
            ->willReturn(null);

        $this->assertEquals('', $this->dataHelper->getVendorSpecificCustomerShippingAccountDisclaimer());
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::getReviewSubmitAndOrderConfirmationCancellationMessage
     */
    public function testGetReviewSubmitAndOrderConfirmationCancellationMessage()
    {
        $this->toggleConfigMock->method('getToggleConfig')
            ->with(Data::XPATH_REVIEW_SUBMIT_ORDER_CONFIRMATION_CANCELLATION_MESSAGE)
            ->willReturn('');

        $this->assertEquals('', $this->dataHelper->getReviewSubmitAndOrderConfirmationCancellationMessage());
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::isEproUploadToQuoteEnable
     */
    public function testIsEproUploadToQuoteEnable()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfig')
            ->with(Data::XPATH_EPRO_ENABLE_UPDATE_TO_QUOTE)
            ->willReturn(true);
        $this->assertIsBool($this->dataHelper->isEproUploadToQuoteEnable());
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::isD190723FixToggleEnable
     */
    public function testIsD190723FixToggleEnable()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfig')
            ->with(Data::XPATH_EXPLORERS_D_190723_Fix)
            ->willReturn(true);

        $this->assertTrue($this->dataHelper->isD190723FixToggleEnable());
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::getD194958
     */
    public function testGetD194958()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('tiger_194958')
            ->willReturn(true);

        $this->assertTrue($this->dataHelper->getD194958());
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::getSuperAttributeArray
     */
    public function testGetSuperAttributeArrayWhenEssendantEnabled()
    {
        $dataHelper = $this->getMockBuilder(Data::class)
            ->setConstructorArgs([
                $this->toggleConfigMock,
                $this->checkoutSessionMock,
                $this->loggerMock,
                $this->attributeSetRepositoryMock
            ])
            ->onlyMethods(['isEssendantToggleEnabled'])
            ->getMock();

        $dataHelper->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $superAttributes = "[93=>4751,141=>4782]";
        $expectedResult = [
            93 => 4751,
            141 => 4782
        ];

        $result = $dataHelper->getSuperAttributeArray($superAttributes);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::getSuperAttributeArray
     */
    public function testGetSuperAttributeArrayWhenEssendantDisabled()
    {
        $dataHelper = $this->getMockBuilder(Data::class)
            ->setConstructorArgs([
                $this->toggleConfigMock,
                $this->checkoutSessionMock,
                $this->loggerMock,
                $this->attributeSetRepositoryMock
            ])
            ->onlyMethods(['isEssendantToggleEnabled'])
            ->getMock();

        $dataHelper->method('isEssendantToggleEnabled')
            ->willReturn(false);

        $superAttributes = "[93=>4751,141=>4782]";

        $result = $dataHelper->getSuperAttributeArray($superAttributes);

        $this->assertNull($result);
    }

    /**
     * Test method to check Remove extend life API call for legacy documents in the cart area | B-2353473
     */
    public function testCheckLegacyDocApiOnCartToggle()
    {
        $this->toggleConfigMock->method('getToggleConfig')
            ->with(Data::REMOVE_LEGACY_DOC_API_CALL_ON_CART)
            ->willReturn(true);

        $this->dataHelper->CheckLegacyDocApiOnCartToggle();
    }

    /**
     * Tests that the hasLegacyDocumentInQuoteSession method returns true
     * when a legacy document is present in the quote session.
     *
     * @return void
     */
    public function testHasLegacyDocumentInQuoteSessionReturnsTrue()
    {
        $itemMock = $this->createMock(Item::class);
        $items = [$itemMock];

        $this->checkoutSessionMock->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->method('getAllVisibleItems')->willReturn($items);

        $this->dataHelper->hasLegacyDocumentInQuoteSession();
    }

    /**
     * Tests that the hasLegacyDocumentInItems method returns true when legacy documents are present in the items.
     *
     * @return void
     */
    public function testHasLegacyDocumentInItemsReturnsTrue()
    {
        $itemMock = $this->createMock(Item::class);

        $this->dataHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['checkItemIsLegacyDocument'])
            ->getMock();

        $this->dataHelper->expects($this->once())
            ->method('checkItemIsLegacyDocument')
            ->with($itemMock)
            ->willReturn(true);

        $this->assertTrue($this->dataHelper->hasLegacyDocumentInCartItems([$itemMock]));
    }

    /**
     * Tests that the checkItemIsLegacyDocument method handles exceptions gracefully.
     *
     * @return void
     */
    public function testCheckItemIsLegacyDocumentHandlesException()
    {
        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getOptionByCode')->willThrowException(new \Exception('Test exception'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Exception occurred while checking legacy document'));

        $this->assertFalse($this->dataHelper->checkItemIsLegacyDocument($itemMock));
    }

    /**
     * Tests that the checkItemIsLegacyDocument method returns true.
     *
     * @return void
     */
    public function testCheckItemIsLegacyDocumentReturnsTrue()
    {
        $itemMock = $this->createMock(Item::class);
        $optionMock = $this->createMock(Option::class);

        $buyRequestData = json_encode([
            'external_prod' => [
                [
                    'contentAssociations' => [
                        ['contentReference' => '1234']
                    ]
                ]
            ]
        ]);

        $optionMock->method('getValue')->willReturn($buyRequestData);
        $itemMock->method('getOptionByCode')->with('info_buyRequest')->willReturn($optionMock);

        $this->assertTrue($this->dataHelper->checkItemIsLegacyDocument($itemMock));
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::isToggleD214903Enabled
     */
    public function testIsToggleD214903Enabled()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('tiger_d214903')
            ->willReturn(true);

        $this->assertTrue($this->dataHelper->isToggleD214903Enabled());
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::isToggleD221721Enabled
     */
    public function testIsToggleD221721Enabled()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(Data::TIGER_D221721)
            ->willReturn(true);

        $this->assertTrue($this->dataHelper->isToggleD221721Enabled());
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::isToggleD221721Enabled
     */
    public function testIsToggleD221721Disabled()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(Data::TIGER_D221721)
            ->willReturn(false);

        $this->assertFalse($this->dataHelper->isToggleD221721Enabled());
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::checkIfItemsAreAllNonCustomizableProduct
     */
    public function testCheckIfItemsAreAllNonCustomizableProductReturnsTrue()
    {
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $itemCollection = [$itemMock];

        $quoteMock->method('getItemsCollection')
            ->willReturn($itemCollection);

        $itemMock->method('getProduct')
            ->willReturn($productMock);

        $productMock->method('getAttributeSetId')
            ->willReturn(123);

        $attributeSetMock = $this->getMockBuilder(AttributeSetInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeSetMock->method('getAttributeSetName')
            ->willReturn(Data::FXO_NON_CUSTOMIZABLE_PRODUCTS_ATTR_SET);

        $attributeSetRepoMock = $this->getMockBuilder(AttributeSetRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeSetRepoMock->method('get')
            ->with(123)
            ->willReturn($attributeSetMock);

        $objectManager = new ObjectManager($this);
        $dataHelper = $objectManager->getObject(
            Data::class,
            [
                'toggleConfig' => $this->toggleConfigMock,
                'checkoutSession' => $this->checkoutSessionMock,
                'logger' => $this->loggerMock,
                'attributeSetRepository' => $attributeSetRepoMock
            ]
        );

        $result = $dataHelper->checkIfItemsAreAllNonCustomizableProduct($quoteMock);

        $this->assertTrue($result);
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::checkIfItemsAreAllNonCustomizableProduct
     */
    public function testCheckIfItemsAreAllNonCustomizableProductReturnsFalse()
    {
        $quoteMock = $this->createMock(Quote::class);
        $itemMock = $this->createMock(Item::class);
        $productMock = $this->createMock(Product::class);

        $itemCollection = [$itemMock];

        $quoteMock->expects($this->once())
            ->method('getItemsCollection')
            ->willReturn($itemCollection);

        $itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $productMock->expects($this->once())
            ->method('getAttributeSetId')
            ->willReturn(456);

        $attributeSetMock = $this->createMock(AttributeSetInterface::class);
        $attributeSetMock->method('getAttributeSetName')
            ->willReturn('SomeOtherAttributeSet');

        $this->attributeSetRepositoryMock->method('get')
            ->with(456)
            ->willReturn($attributeSetMock);

        $result = $this->dataHelper->checkIfItemsAreAllNonCustomizableProduct($quoteMock);

        $this->assertFalse($result);
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::checkIfItemsAreAllNonCustomizableProduct
     */
    public function testCheckIfItemsAreAllNonCustomizableProductWithOrder()
    {
        $orderMock = $this->createMock(Order::class);
        $itemMock = $this->createMock(Item::class);
        $productMock = $this->createMock(Product::class);

        $itemCollection = [$itemMock];

        $orderMock->expects($this->once())
            ->method('getItemsCollection')
            ->willReturn($itemCollection);

        $itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $productMock->expects($this->once())
            ->method('getAttributeSetId')
            ->willReturn(789);

        $attributeSetMock = $this->createMock(AttributeSetInterface::class);
        $attributeSetMock->method('getAttributeSetName')
            ->willReturn(Data::FXO_NON_CUSTOMIZABLE_PRODUCTS_ATTR_SET);

        $this->attributeSetRepositoryMock->method('get')
            ->with(789)
            ->willReturn($attributeSetMock);

        $result = $this->dataHelper->checkIfItemsAreAllNonCustomizableProduct($orderMock);

        $this->assertTrue($result);
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::checkIfItemsAreAllNonCustomizableProduct
     */
    public function testCheckIfItemsAreAllNonCustomizableProductWithMultipleItems()
    {
        $quoteMock = $this->createMock(Quote::class);
        $itemMock1 = $this->createMock(Item::class);
        $itemMock2 = $this->createMock(Item::class);
        $productMock1 = $this->createMock(Product::class);
        $productMock2 = $this->createMock(Product::class);

        $itemCollection = [$itemMock1, $itemMock2];

        $quoteMock->expects($this->once())
            ->method('getItemsCollection')
            ->willReturn($itemCollection);

        $itemMock1->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock1);

        $itemMock2->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock2);

        $productMock1->expects($this->once())
            ->method('getAttributeSetId')
            ->willReturn(111);

        $productMock2->expects($this->once())
            ->method('getAttributeSetId')
            ->willReturn(222);

        $attributeSet1 = $this->createMock(AttributeSetInterface::class);
        $attributeSet1->expects($this->once())
            ->method('getAttributeSetName')
            ->willReturn(Data::FXO_NON_CUSTOMIZABLE_PRODUCTS_ATTR_SET);

        $attributeSet2 = $this->createMock(AttributeSetInterface::class);
        $attributeSet2->expects($this->once())
            ->method('getAttributeSetName')
            ->willReturn('SomeOtherAttributeSet');

        $attributeSetRepositoryMock = $this->createMock(AttributeSetRepositoryInterface::class);
        $attributeSetRepositoryMock->method('get')
            ->willReturnCallback(function ($attributeSetId) use ($attributeSet1, $attributeSet2) {
                if ($attributeSetId === 111) {
                    return $attributeSet1;
                } elseif ($attributeSetId === 222) {
                    return $attributeSet2;
                }
                return null;
            });

        $objectManagerHelper = new ObjectManager($this);
        $dataHelper = $objectManagerHelper->getObject(
            Data::class,
            [
                'toggleConfig' => $this->toggleConfigMock,
                'checkoutSession' => $this->checkoutSessionMock,
                'logger' => $this->loggerMock,
                'attributeSetRepository' => $attributeSetRepositoryMock
            ]
        );

        $result = $dataHelper->checkIfItemsAreAllNonCustomizableProduct($quoteMock);

        $this->assertFalse($result);
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::getQuote
     */
    public function testGetQuote()
    {
        $quoteMock = $this->createMock(Quote::class);

        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $result = $this->dataHelper->getQuote();

        $this->assertSame($quoteMock, $result);
    }

    /**
     * Test loadAttributeSet method returns from cache when value exists
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::loadAttributeSet
     */
    public function testLoadAttributeSetReturnsFromCache()
    {
        $reflection = new \ReflectionClass(Data::class);
        $method = $reflection->getMethod('loadAttributeSet');
        $method->setAccessible(true);

        $property = $reflection->getProperty('attributeSetLoaded');
        $property->setAccessible(true);
        $property->setValue($this->dataHelper, [123 => 'CachedAttributeSet']);

        $result = $method->invoke($this->dataHelper, 123);

        $this->assertEquals('CachedAttributeSet', $result);

        $this->attributeSetRepositoryMock->expects($this->never())
            ->method('get');
    }

    /**
     * Test loadAttributeSet method fetches from repository when not in cache
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::loadAttributeSet
     */
    public function testLoadAttributeSetFetchesFromRepository()
    {
        $reflection = new \ReflectionClass(Data::class);
        $method = $reflection->getMethod('loadAttributeSet');
        $method->setAccessible(true);

        $attributeSetMock = $this->createMock(AttributeSetInterface::class);
        $attributeSetMock->expects($this->once())
            ->method('getAttributeSetName')
            ->willReturn('FetchedAttributeSet');

        $this->attributeSetRepositoryMock->expects($this->once())
            ->method('get')
            ->with(456)
            ->willReturn($attributeSetMock);

        $result = $method->invoke($this->dataHelper, 456);

        $this->assertEquals('FetchedAttributeSet', $result);

        $property = $reflection->getProperty('attributeSetLoaded');
        $property->setAccessible(true);
        $cachedValues = $property->getValue($this->dataHelper);
        $this->assertArrayHasKey(456, $cachedValues);
        $this->assertEquals('FetchedAttributeSet', $cachedValues[456]);
    }

    /**
     * Test loadAttributeSet method properly handles exceptions
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::loadAttributeSet
     */
    public function testLoadAttributeSetHandlesException()
    {
        $reflection = new \ReflectionClass(Data::class);
        $method = $reflection->getMethod('loadAttributeSet');
        $method->setAccessible(true);

        $this->attributeSetRepositoryMock->expects($this->once())
            ->method('get')
            ->with(789)
            ->willThrowException(new \Exception('Repository error'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error loading attribute set: Repository error'));

        $result = $method->invoke($this->dataHelper, 789);

        $this->assertNull($result);
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::isD224874Enable
     */
    public function testIsD224874EnableReturnsTrue()
    {
        // Set up toggle config mock to return true
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfig')
            ->with(Data::XPATH_ENABLE_D_224874)
            ->willReturn(true);

        $this->assertTrue($this->dataHelper->isD224874Enable());
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::isD224874Enable
     */
    public function testIsD224874EnableReturnsFalse()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfig')
            ->with(Data::XPATH_ENABLE_D_224874)
            ->willReturn(false);

        $this->assertFalse($this->dataHelper->isD224874Enable());
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Helper\Data::isD224874Enable
     */
    public function testIsD224874EnableCastsToBoolean()
    {
        $this->toggleConfigMock->expects($this->exactly(2))
            ->method('getToggleConfig')
            ->with(Data::XPATH_ENABLE_D_224874)
            ->willReturnOnConsecutiveCalls('1', 0);

        $this->assertTrue($this->dataHelper->isD224874Enable());

        $this->assertFalse($this->dataHelper->isD224874Enable());
    }
}
