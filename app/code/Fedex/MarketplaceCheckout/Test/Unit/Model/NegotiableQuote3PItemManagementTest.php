<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model;

use Fedex\MarketplaceCheckout\Model\NegotiableQuote3PItemManagement;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\NegotiableQuote\Model\Customer;
use Magento\NegotiableQuote\Model\NegotiableItem\GetNegotiatedPrice;
use Magento\NegotiableQuote\Model\NegotiableQuoteItemFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Tax\Model\Config as TaxConfig;
use Magento\NegotiableQuote\Model\Quote\TotalsFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteItemManagement;
use Fedex\MarketplaceCheckout\Helper\Data;
use Magento\NegotiableQuote\Model\Action\Item\Price\Update;
use Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuoteItem;
use Magento\NegotiableQuote\Model\Customer\StoreCustomerRate;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Quote\Api\Data\CurrencyInterface;
use Magento\NegotiableQuote\Model\Quote\Totals;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteItemInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteItemExtensionInterface;
use Magento\Quote\Api\Data\CartItemExtensionInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Framework\Api\ExtensionAttributesInterface;

/**
 * Test for NegotiableQuote3PItemManagement class.
 *
 */
class NegotiableQuote3PItemManagementTest extends TestCase
{
    /**
     * @var (\Fedex\MarketplaceCheckout\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $helper;
    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var Config|MockObject
     */
    private $taxConfig;

    /**
     * @var NegotiableQuoteItemFactory|MockObject
     */
    private $negotiableQuoteItemFactory;

    /**
     * @var ExtensionAttributesFactory|MockObject
     */
    private $extensionFactory;

    /**
     * @var TotalsFactory|MockObject
     */
    private $quoteTotalsFactory;

    /**
     * @var Update|MockObject
     */
    private $priceUpdater;

    /**
     * @var NegotiableQuoteItem|MockObject
     */
    private $negotiableQuoteItemResource;

    /**
     * @var StoreCustomerRate|MockObject
     */
    private $storeCustomerRate;

    /**
     * @var NegotiableQuoteItemManagement
     */
    private $neqotiableQuoteItemManagement;

    /**
     * @var GetNegotiatedPrice
     */
    protected $getNegotiatedPrice;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var CartInterface
     */
    protected $cartInterface;

    /**
     * @var NegotiableQuoteInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $negotiableQuoteInterface;

    /**
     * @var CurrencyInterface
     */
    protected $currency;

    /**
     * @var Totals
     */
    protected $totals;

    /**
     * @var CartItemInterface
     */
    protected $cartItemInterface;

    /**
     * @var CartExtensionInterface
     */
    protected $cartExtensionInterface;

    /**
     * @var CartItemExtensionInterface
     */
    protected $cartItemExtensionInterface;

    /**
     * @var NegotiableQuoteItemInterface
     */
    protected $negotiableQuoteItemInterface;

    /**
     * @var Item
     */
    protected $item;

    /**
     * @var ExtensionAttributesInterface
     */
    protected $extensionAttributesInterface;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->setMethods(['save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->negotiableQuoteInterface = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->setMethods(['getStatus','setData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->taxConfig = $this->getMockBuilder(TaxConfig::class)
            ->setMethods(['priceIncludesTax'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteItemFactory = $this
            ->getMockBuilder(NegotiableQuoteItemFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->extensionFactory = $this->getMockBuilder(ExtensionAttributesFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->quoteTotalsFactory = $this->getMockBuilder(TotalsFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->priceUpdater = $this->getMockBuilder(Update::class)
            ->disableOriginalConstructor()
            ->setMethods(['update'])
            ->getMock();
        $this->negotiableQuoteItemResource = $this
            ->getMockBuilder(NegotiableQuoteItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeCustomerRate = $this
            ->getMockBuilder(StoreCustomerRate::class)
            ->disableOriginalConstructor()
            ->setMethods(['init', 'getStoreTaxRateFactor', 'getCustomerTaxRateFactor'])
            ->getMock();
        $this->helper = $this
            ->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['is3pFuseOrderSearchEnabled', 'isUploadToQuoteEnabled', 'isD224874Enable'])
            ->getMock();
        
        // Mock the isD224874Enable method to return false by default
        $this->helper->method('isD224874Enable')
            ->willReturn(false);

        // Mock the isD224874Enable method to return false by default
        $this->helper->method('isD224874Enable')
            ->willReturn(false);

        // Mock the isD224874Enable method to return false by default
        $this->helper->method('isD224874Enable')
            ->willReturn(false);

        $this->getNegotiatedPrice = $this->getMockBuilder(GetNegotiatedPrice::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartInterface = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems', 'getAllVisibleItems', 'setTotalsCollectedFlag', 'collectTotals'])
            ->getMockForAbstractClass();

        $this->currency = $this->getMockBuilder(CurrencyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseCurrencyCode', 'getQuoteCurrencyCode', 'getBaseToQuoteRate'])
            ->getMockForAbstractClass();

        $this->totals = $this->getMockBuilder(Totals::class)
            ->setMethods(['getCatalogTotalPrice', 'getSubtotal'])
            ->disableOriginalConstructor()
            ->setMethods(['is3pFuseOrderSearchEnabled','isUploadToQuoteEnabled'])
            ->getMock();

        $this->cartItemInterface = $this->getMockBuilder(CartItemInterface::class)
            ->setMethods([
                'getCustomPrice',
                'getStoreId',
                'setCustomPrice',
                'setOriginalCustomPrice',
                'setBaseTaxCalculationPrice',
                'setTaxCalculationPrice'
                ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cartExtensionInterface = $this->getMockBuilder(CartExtensionInterface::class)
            ->setMethods(['getNegotiableQuote'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cartItemExtensionInterface = $this->getMockBuilder(CartItemExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setNegotiableQuoteItem', 'getNegotiableQuoteItem'])
            ->getMockForAbstractClass();

        $this->negotiableQuoteItemInterface = $this->getMockBuilder(NegotiableQuoteItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'save', 'getExtensionAttributes', 'setData'])
            ->getMockForAbstractClass();

        $this->extensionAttributesInterface = $this->getMockBuilder(ExtensionAttributesInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMockForAbstractClass();

        $this->item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getBasePrice',
                    'getItemId',
                    'setExtensionAttributes',
                    'getExtensionAttributes',
                    'setCustomPrice',
                    'setOriginalCustomPrice',
                    'setBaseTaxCalculationPrice',
                    'setTaxCalculationPrice',
                    'getBaseTaxAmount',
                    'getQty',
                    'getChildren',
                    'isChildrenCalculated',
                    'getBaseDiscountAmount',
                    'getStoreId',
                    'getCustomPrice'
                ]
            )
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->neqotiableQuoteItemManagement = $this->objectManager->getObject(
            NegotiableQuote3PItemManagement::class,
            [
                'quoteRepository' => $this->quoteRepository,
                'taxConfig' => $this->taxConfig,
                'negotiableQuoteItemFactory' => $this->negotiableQuoteItemFactory,
                'extensionFactory' => $this->extensionFactory,
                'quoteTotalsFactory' => $this->quoteTotalsFactory,
                'priceUpdater' => $this->priceUpdater,
                'negotiableQuoteItemResource' => $this->negotiableQuoteItemResource,
                'getNegotiatedPrice' => $this->getNegotiatedPrice,
                'helper' => $this->helper,
                'storeCustomerRate' => $this->storeCustomerRate
            ]
        );
    }

    /**
     * Test for constructor with injected StoreCustomerRate dependency as null.
     */
    public function testConstructorWithInjectedStoreCustomerRate(): void
    {
        $this->neqotiableQuoteItemManagement = new NegotiableQuote3PItemManagement(
            $this->quoteRepository,
            $this->taxConfig,
            $this->negotiableQuoteItemFactory,
            $this->extensionFactory,
            $this->quoteTotalsFactory,
            $this->priceUpdater,
            $this->negotiableQuoteItemResource,
            $this->getNegotiatedPrice,
            $this->helper,
            null
        );

        $this->assertInstanceOf(NegotiableQuote3PItemManagement::class, $this->neqotiableQuoteItemManagement);
    }

     /**
      * Test for updateQuoteItemsCustomPrices method.
      *
      * @param float|int $originalPrice
      * @param float|int $originalTax
      * @param float|int $originalDiscount
      * @param float|int $negotiatedPriceValue
      * @param int|null $negotiatedPriceType
      * @param float|int $baseToQuoteRate
      * @param array $updateData
      * @param int $originalSubtotalCalls
      * @param int $negotiatedPriceTypeCalls
      * @param int $baseToQuoteRateCalls
      * @param bool $needSave
      * @param string $negotiatedStatus
      * @param float|null $quoteItemCustomPrice
      * @return void
      * @throws NoSuchEntityException
      * @dataProvider updateQuoteItemsCustomPricesDataProvider
      * @SuppressWarnings(PHPMD.ExcessiveParameterList)
      */
    public function testUpdateQuoteItemsCustomPrices(
        $originalPrice,
        $originalTax,
        $originalDiscount,
        $negotiatedPriceValue,
        ?int $negotiatedPriceType,
        $baseToQuoteRate,
        array $updateData,
        int $originalSubtotalCalls,
        int $negotiatedPriceTypeCalls,
        int $baseToQuoteRateCalls,
        bool $needSave,
        string $negotiatedStatus,
        ?float $quoteItemCustomPrice
    ) {
        $quoteId = 1;
        $quoteItemId = 2;
        $quoteItemQty = 1;
        $baseCurrency = 'USD';
        $quoteCurrency = 'EUR';
        $storeId = 3;
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems', 'getAllVisibleItems', 'setTotalsCollectedFlag', 'collectTotals'])
            ->getMockForAbstractClass();
        $this->quoteRepository->expects($this->any())->method('get')->with($quoteId, ['*'])->willReturn($quote);
        $quoteNegotiation = $this->mockNegotiableQuote($quote);
        $quoteNegotiation->expects($this->any())->method('getStatus')->willReturn($negotiatedStatus);
        $quoteNegotiation->expects($this->any())
            ->method('getNegotiatedPriceValue')->willReturn($negotiatedPriceValue);
        $quoteNegotiation->expects($this->exactly($negotiatedPriceTypeCalls))
            ->method('getNegotiatedPriceType')->willReturn($negotiatedPriceType);

        $invPrice = $negotiatedStatus === NegotiableQuoteInterface::STATUS_CREATED && $quoteItemCustomPrice ? 1 : 0;
        $quoteNegotiation->expects($this->exactly($invPrice))
            ->method('setNegotiatedPriceValue');
        $quoteNegotiation->expects($this->exactly($invPrice))
            ->method('setNegotiatedPriceType')
            ->with(NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PROPOSED_TOTAL);
        $quote->expects($this->any())->method('getCurrency')->willReturn($this->currency);
        list($quoteItem, $negotiableQuoteItem) = $this->mockQuoteItem($quoteItemId, $quoteItemCustomPrice);
        $quote->expects($this->any())->method('getAllItems')->willReturn([$quoteItem]);
        $quote->expects($this->any())->method('getAllVisibleItems')->willReturn([$quoteItem]);
        $negotiableQuoteItem->expects($this->any())->method('getOriginalPrice')->willReturn($originalPrice);
        $quoteItem->expects($this->any())->method('getStoreId')->willReturn($storeId);
        $this->taxConfig->expects($this->any())->method('priceIncludesTax')->with($storeId)->willReturn(true);
        $negotiableQuoteItem->expects($this->any())->method('getOriginalTaxAmount')->willReturn($originalTax);
        $negotiableQuoteItem->expects($this->any())
            ->method('getOriginalDiscountAmount')->willReturn($originalDiscount);
        $this->currency->expects($this->any())->method('getBaseCurrencyCode')->willReturn($baseCurrency);
        $this->currency->expects($this->any())
            ->method('getQuoteCurrencyCode')->willReturn($quoteCurrency);
        $this->currency->expects($this->exactly($baseToQuoteRateCalls))
            ->method('getBaseToQuoteRate')->willReturn($baseToQuoteRate);
        $quoteTotals = $this->getMockBuilder(Totals::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteTotalsFactory->expects($this->exactly($originalSubtotalCalls))
            ->method('create')->with(['quote' => $quote])->willReturn($quoteTotals);
        $quoteTotals->method('getCatalogTotalPrice')->willReturn($originalPrice);
        $quoteItem->expects($this->any())->method('getQty')->willReturn($quoteItemQty);
        $this->priceUpdater->expects($this->any())->method('update')->with(
            $quoteItem,
            ['qty' => $quoteItemQty] + $updateData
        )->willReturnSelf();
        $quoteItem->expects($this->exactly($negotiatedPriceValue ? 0 : 1))
            ->method('setCustomPrice')->with(null)->willReturnSelf();
        $quoteItem->expects($this->exactly($negotiatedPriceValue ? 0 : 1))
            ->method('setOriginalCustomPrice')->with(null)->willReturnSelf();
        $quoteItem->expects($this->any())->method('setBaseTaxCalculationPrice')->with(null)->willReturnSelf();
        $quoteItem->expects($this->any())->method('setTaxCalculationPrice')->with(null)->willReturnSelf();
        $quote->expects($this->any())->method('setTotalsCollectedFlag')->with(false)->willReturnSelf();
        $this->quoteRepository->expects($this->exactly($needSave ? 1 : 0))
            ->method('save')->with($quote)->willReturn($quote);
        $quote->expects($this->any())->method('collectTotals')->willReturnSelf();

        if (isset($updateData['custom_price'])) {
            $this->storeCustomerRate
                ->expects($this->any())
                ->method('getStoreTaxRateFactor')
                ->with($quoteItem)
                ->willReturn(1.0);

            $this->storeCustomerRate
                ->expects($this->any())
                ->method('getCustomerTaxRateFactor')
                ->with($quoteItem)
                ->willReturn(1.0);
        }
        $result = $this->neqotiableQuoteItemManagement->updateQuoteItemsCustomPrices($quoteId, $needSave);
        $this->assertEquals(true, $result);
    }

    /**
     * Mock negotiable quote.
     *
     * @param MockObject $quote
     * @return MockObject
     */
    private function mockNegotiableQuote(MockObject $quote)
    {
        $quoteNegotiation = $this->getMockBuilder(NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->any())->method('getExtensionAttributes')->willReturn($this->cartExtensionInterface);
        $this->cartExtensionInterface->expects($this->any())
            ->method('getNegotiableQuote')->willReturn($quoteNegotiation);
        $quoteNegotiation->expects($this->any())->method('getIsRegularQuote')->willReturn(true);
        return $quoteNegotiation;
    }

    /**
     * Mock quote item.
     *
     * @param int $quoteItemId
     * @param float|null $customPrice
     * @return array
     */
    private function mockQuoteItem($quoteItemId, $customPrice = null)
    {
        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getBasePrice',
                    'getItemId',
                    'setExtensionAttributes',
                    'getExtensionAttributes',
                    'setCustomPrice',
                    'setOriginalCustomPrice',
                    'setBaseTaxCalculationPrice',
                    'setTaxCalculationPrice',
                    'getBaseTaxAmount',
                    'getQty',
                    'getChildren',
                    'isChildrenCalculated',
                    'getBaseDiscountAmount',
                    'getStoreId',
                    'getCustomPrice'
                ]
            )
            ->getMock();
        $negotiableQuoteItem = $this->getMockBuilder(
            NegotiableQuoteItemInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['load', 'save', 'getExtensionAttributes', 'setData'])
            ->getMockForAbstractClass();
        $this->negotiableQuoteItemFactory->expects($this->any())->method('create')->willReturn($negotiableQuoteItem);
        $quoteItem->expects($this->any())->method('getItemId')->willReturn($quoteItemId);
        $quoteItem->method('getCustomPrice')->willReturn($customPrice);
        $negotiableQuoteItem->expects($this->any())->method('load')->with($quoteItemId)->willReturnSelf();
        $negotiableQuoteItem->expects($this->any())->method('setItemId')->with($quoteItemId)->willReturnSelf();
        $quoteItemExtension = $this->getMockBuilder(
            CartItemExtensionInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['setNegotiableQuoteItem', 'getNegotiableQuoteItem'])
            ->getMockForAbstractClass();
        $negotiableItemExtension = $this->getMockBuilder(NegotiableQuoteItemExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setNegotiatedPriceType',
                    'setNegotiatedPriceValue',
                    'getNegotiatedPriceValue' ,
                    'getNegotiatedPriceType'
                ]
            )
            ->getMockForAbstractClass();
        $this->extensionFactory->expects($this->any())
            ->method('create')->will(
                $this->onConsecutiveCalls($quoteItemExtension, $negotiableItemExtension)
            );
        $quoteItemExtension->expects($this->any())
            ->method('setNegotiableQuoteItem')->with($negotiableQuoteItem)->willReturnSelf();
        $quoteItem->expects($this->any())
            ->method('setExtensionAttributes')->with($quoteItemExtension)->willReturnSelf();
        $quoteItem->expects($this->any())->method('getExtensionAttributes')
            ->willReturnOnConsecutiveCalls(
                null,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
            );
        $quoteItemExtension->expects($this->any())
            ->method('getNegotiableQuoteItem')->willReturn($negotiableQuoteItem);
        $negotiableQuoteItem->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($negotiableItemExtension);
        return [$quoteItem, $negotiableQuoteItem];
    }

    /**
     * Data provider for testUpdateQuoteItemsCustomPrices.
     *
     * @return array
     */
    public function updateQuoteItemsCustomPricesDataProvider()
    {
        return [
            [
                200,    //original price
                10,     //original tax
                5,      //original discount
                30,     //negotiated price value
                NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PERCENTAGE_DISCOUNT,    //negotiated price type
                0.5,   //rate for base currency to quote currency
                ['custom_price' => 0],     //expected quote item total
                1,
                1,
                2,
                true,
                NegotiableQuoteInterface::STATUS_CREATED,
                5
            ]
        ];
    }

    /**
     * Test for recalculateOriginalPriceTax method.
     *
     * @param bool $needRecalculatePrice
     * @param bool $needRecalculateRule
     * @param bool $isChildrenCalculated
     * @param int $recalculationCalls
     * @param int $baseToQuoteRateCalls
     * @return void
     * @dataProvider recalculateOriginalPriceTaxDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRecalculateOriginalPriceTax(
        $needRecalculatePrice,
        $needRecalculateRule,
        $isChildrenCalculated,
        $recalculationCalls,
        $baseToQuoteRateCalls
    ) {
        $quoteId = 1;
        $quoteItemId = 2;
        $originalPrice = 200;
        $originalTax = 10;
        $originalDiscount = 5;
        $baseCurrency = 'USD';
        $quoteCurrency = 'EUR';
        $baseToQuoteRate = 0.75;
        $quoteItemQty = 1;
        $appliedRuleIds = [98, 99];
        $storeId = 3;
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                ['setTotalsCollectedFlag', 'collectTotals', 'getAppliedRuleIds', 'getShippingAddress', 'getAllItems']
            )
            ->getMockForAbstractClass();
        $this->quoteRepository->expects($this->exactly(2))->method('get')->with($quoteId, ['*'])->willReturn($quote);
        $quoteNegotiation = $this->mockNegotiableQuote($quote);
        $quoteNegotiation->method('getStatus')->willReturn(NegotiableQuoteInterface::STATUS_CREATED);
        $quote->expects($this->exactly(2))->method('getCurrency')->willReturn($this->currency);
        list($quoteItem, $negotiableQuoteItem) = $this->mockQuoteItem($quoteItemId);
        $quote->expects($this->any())->method('getAllItems')->willReturn([$quoteItem]);
        $negotiableQuoteItem->expects($this->any())->method('getOriginalPrice')->willReturn($originalPrice);
        $quoteItem->expects($this->any())->method('getStoreId')->willReturn($storeId);
        $this->taxConfig->expects($this->any())->method('priceIncludesTax')->with($storeId)->willReturn(true);
        $negotiableQuoteItem->expects($this->any())->method('getOriginalTaxAmount')->willReturn($originalTax);
        $negotiableQuoteItem->expects($this->any())
            ->method('getOriginalDiscountAmount')->willReturn($originalDiscount);
        $this->currency->expects($this->any())->method('getBaseCurrencyCode')->willReturn($baseCurrency);
        $this->currency->expects($this->any())->method('getQuoteCurrencyCode')->willReturn($quoteCurrency);
        $this->currency->expects($this->exactly($baseToQuoteRateCalls))
            ->method('getBaseToQuoteRate')->willReturn($baseToQuoteRate);
        $quoteTotals = $this->getMockBuilder(Totals::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteTotalsFactory->expects($this->exactly(1))
            ->method('create')->with(['quote' => $quote])->willReturn($quoteTotals);
        $quoteTotals->method('getCatalogTotalPrice')->willReturn($originalPrice);
        $quoteTotals->method('getSubtotal')->willReturn($originalPrice);
        $quoteItem->expects($this->any())->method('setCustomPrice')->with(null)->willReturnSelf();
        $quoteItem->expects($this->any())->method('setOriginalCustomPrice')->with(null)->willReturnSelf();
        $quoteItem->expects($this->any())->method('setBaseTaxCalculationPrice')->with(null)->willReturnSelf();
        $quoteItem->expects($this->any())->method('setTaxCalculationPrice')->with(null)->willReturnSelf();
        $quote->expects($this->exactly(3))->method('setTotalsCollectedFlag')->with(false)->willReturnSelf();
        $quote->expects($this->exactly(2))->method('collectTotals')->willReturnSelf();
        $address = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isObjectNew'])
            ->getMockForAbstractClass();
        $quote->expects($this->exactly(3))->method('getShippingAddress')->willReturn($address);
        $quote->expects($this->exactly(3))->method('getBillingAddress')->willReturn($address);
        $address->expects($this->exactly(6))->method('isObjectNew')
            ->withConsecutive([], [], [true], [true], [false], [false])
            ->willReturnOnConsecutiveCalls(false, false, true, true, false, false);
        $quoteItem->expects($this->any())->method('getBasePrice')->willReturn($originalPrice);
        $quoteItem->expects($this->any())->method('getBaseTaxAmount')->willReturn($originalTax);
        $quoteItem->expects($this->any())->method('getQty')->willReturn($quoteItemQty);
        $negotiableQuoteItem->expects($this->any())->method('setOriginalPrice')
            ->withConsecutive([$originalPrice], [$originalPrice + $originalDiscount])->willReturnSelf();
        $negotiableQuoteItem->expects($this->any())
            ->method('setOriginalTaxAmount')->with($originalTax / $quoteItemQty)->willReturnSelf();
        $childItem = $this->getMockBuilder(Item::class)
            ->addMethods(['getBaseDiscountAmount'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteItem->expects($this->exactly($recalculationCalls))->method('getChildren')->willReturn([$childItem]);
        $quoteItem->expects($this->exactly($recalculationCalls))
            ->method('isChildrenCalculated')->willReturn($isChildrenCalculated);
        $childItem->expects($this->exactly($needRecalculateRule && $isChildrenCalculated ? 1 : 0))
            ->method('getBaseDiscountAmount')->willReturn($originalDiscount);
        $quoteItem->expects($this->exactly($needRecalculateRule && !$isChildrenCalculated ? 1 : 0))
            ->method('getBaseDiscountAmount')->willReturn($originalDiscount);
        $negotiableQuoteItem->expects($this->any())
            ->method('setOriginalDiscountAmount')->willReturnSelf();
        $negotiableQuoteItem->expects($this->any())->method('setData');
        $quote->expects($this->exactly($recalculationCalls))->method('getAppliedRuleIds')->willReturn($appliedRuleIds);
        $quoteNegotiation->expects($this->exactly($recalculationCalls))
            ->method('setAppliedRuleIds')->with($appliedRuleIds)->willReturnSelf();
        $this->negotiableQuoteItemResource->expects($this->any())->method('saveList')->with([$negotiableQuoteItem]);
        $this->assertTrue(
            $this->neqotiableQuoteItemManagement->recalculateOriginalPriceTax(
                $quoteId,
                $needRecalculatePrice,
                $needRecalculateRule
            )
        );
    }

     /**
      * Data provider for testRecalculateOriginalPriceTax.
      *
      * @return array
      * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
      */
    public function recalculateOriginalPriceTaxDataProvider()
    {
        return [
            [true, true, true, 1, 0],
            [true, true, false, 1, 0],
            [false, false, false, 0, 1]
        ];
    }

    public function testGetQuoteThrowsNoSuchEntityExceptionWhenNegotiableQuoteIsNull()
    {
        $quoteId = 1;

        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $quote->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->cartExtensionInterface);

        $this->cartExtensionInterface->expects($this->any())
            ->method('getNegotiableQuote')
            ->willReturn(null);

        $this->quoteRepository->expects($this->any())
            ->method('get')
            ->with($quoteId, ['*'])
            ->willReturn($quote);

        $reflection = new \ReflectionClass(NegotiableQuote3PItemManagement::class);
        $method = $reflection->getMethod('getQuote');
        $method->setAccessible(true);
        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);
        $method->invoke($this->neqotiableQuoteItemManagement, $quoteId);
    }

    /**
     * Test for updateQuoteItemsCustomPrices method.
     *
     * @param float|int $originalPrice
     * @param float|int $originalTax
     * @param float|int $originalDiscount
     * @param float|int $negotiatedPriceValue
     * @param int|null $negotiatedPriceType
     * @param float|int $baseToQuoteRate
     * @param array $updateData
     * @param int $originalSubtotalCalls
     * @param int $negotiatedPriceTypeCalls
     * @param int $baseToQuoteRateCalls
     * @param bool $needSave
     * @param string $negotiatedStatus
     * @param float|null $quoteItemCustomPrice
     * @return void
     * @throws NoSuchEntityException
     * @dataProvider updateQuoteItemsCustomPricesDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function testUpdateQuoteItemsCustomPricesIs3pFuseOrderSearchIsUploadToQuoteEnable(
        $originalPrice,
        $originalTax,
        $originalDiscount,
        $negotiatedPriceValue,
        ?int $negotiatedPriceType,
        $baseToQuoteRate,
        array $updateData,
        int $originalSubtotalCalls,
        int $negotiatedPriceTypeCalls,
        int $baseToQuoteRateCalls,
        bool $needSave,
        string $negotiatedStatus,
        ?float $quoteItemCustomPrice
    ) {
        $quoteId = 1;
        $quoteItemId = 2;
        $quoteItemQty = 1;
        $baseCurrency = 'USD';
        $quoteCurrency = 'EUR';
        $storeId = 3;
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getAllItems',
                'getAllVisibleItems',
                'setTotalsCollectedFlag',
                'collectTotals',
                'getCustomPrice',
                'getStatus'
                ])
            ->getMockForAbstractClass();

        $negotiableItemExtension = $this->getMockBuilder(NegotiableQuoteItemExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setNegotiatedPriceType',
                    'setNegotiatedPriceValue',
                    'getNegotiatedPriceValue' ,
                    'getNegotiatedPriceType'
                ]
            )
            ->getMockForAbstractClass();

        $this->helper->method('is3pFuseOrderSearchEnabled')->willReturn(true);
        $this->helper->method('isUploadToQuoteEnabled')->willReturn(true);
        $this->quoteRepository->expects($this->any())->method('get')->with($quoteId, ['*'])->willReturn($quote);
        $this->storeCustomerRate->method('init')->willReturnSelf($quote);

        $this->extensionFactory->expects($this->any())
            ->method('create')->will(
                $this->onConsecutiveCalls($this->cartItemExtensionInterface, $negotiableItemExtension)
            );
        $this->cartItemExtensionInterface->expects($this->any())
            ->method('setNegotiableQuoteItem')->willReturnSelf();

        $quote->expects($this->any())->method('getExtensionAttributes')->willReturn($this->cartExtensionInterface);
        $this->cartExtensionInterface->expects($this->any())
            ->method('getNegotiableQuote')->willReturn($this->negotiableQuoteInterface);
        $this->negotiableQuoteInterface->method('getIsRegularQuote')->willReturn(true);

        $this->negotiableQuoteInterface->expects($this->any())->method('getStatus')->willReturn('test');

        $quote->expects($this->any())
            ->method('getCustomPrice')
            ->willReturn(100);

        $quote->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$this->cartItemInterface]);

        $this->cartItemInterface->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->cartItemExtensionInterface);

        $this->cartItemExtensionInterface->expects($this->any())
            ->method('getNegotiableQuoteItem')->willReturn($this->negotiableQuoteItemInterface);

        $this->negotiableQuoteItemInterface->expects($this->any())
            ->method('getOriginalPrice')
            ->willReturn($originalPrice);

        $this->negotiableQuoteItemFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->negotiableQuoteItemInterface);

        $this->item->expects($this->any())->method('getItemId')->willReturn($quoteItemId);
        $this->negotiableQuoteItemInterface->expects($this->any())
            ->method('load')
            ->with($quoteItemId)
            ->willReturnSelf();

        $this->negotiableQuoteItemInterface->expects($this->any())
            ->method('setItemId')
            ->with($quoteItemId)
            ->willReturnSelf();

        $this->extensionFactory->expects($this->any())
            ->method('create')->willReturn($this->extensionAttributesInterface);

        $this->cartItemInterface->expects($this->any())->method('getStoreId')->willReturn($storeId);

        $this->cartItemInterface->expects($this->any())->method('setCustomPrice')->with(null)->willReturnSelf();
        $this->cartItemInterface->expects($this->any())->method('setOriginalCustomPrice')->with(null)->willReturnSelf();

        $this->cartItemInterface->expects($this->any())
            ->method('setBaseTaxCalculationPrice')
            ->with(null)
            ->willReturnSelf();
        $this->cartItemInterface->expects($this->any())
            ->method('setTaxCalculationPrice')
            ->with(null)
            ->willReturnSelf();

        $quote->expects($this->any())->method('getCurrency')->willReturn($this->currency);

        $this->currency->expects($this->any())->method('getBaseCurrencyCode')->willReturn($baseCurrency);
        $this->currency->expects($this->any())->method('getQuoteCurrencyCode')->willReturn($quoteCurrency);

        $this->quoteTotalsFactory->expects($this->any())
            ->method('create')
            ->with(['quote' => $quote])
            ->willReturn($this->totals);

        $this->totals->expects($this->exactly(2))
            ->method('getCatalogTotalPrice')
            ->withConsecutive([true], [])
            ->willReturnOnConsecutiveCalls(1000, 900);

        $this->negotiableQuoteInterface->expects($this->any())
            ->method('getStatus')
            ->willReturn(NegotiableQuoteInterface::STATUS_CREATED);

        $this->totals->expects($this->exactly(2))
            ->method('getSubtotal')
            ->with()
            ->willReturnOnConsecutiveCalls(800, 750);

        $this->negotiableQuoteInterface->expects($this->exactly(4))
            ->method('setData')
            ->withConsecutive(
                [NegotiableQuoteInterface::ORIGINAL_TOTAL_PRICE, 1000],
                [NegotiableQuoteInterface::BASE_ORIGINAL_TOTAL_PRICE, 900],
                [NegotiableQuoteInterface::BASE_NEGOTIATED_TOTAL_PRICE, 800],
                [NegotiableQuoteInterface::NEGOTIATED_TOTAL_PRICE, 750]
            );

        $this->quoteRepository->expects($this->any())
            ->method('save')
            ->willReturn($quote);

        $result = $this->neqotiableQuoteItemManagement->updateQuoteItemsCustomPrices($quoteId, $needSave);
        $this->assertEquals(true, $result);
    }

    public function testGetQuoteOriginalSubtotal()
    {
        $expectedSubtotal = 1234.56;
        $totalsMock = $this->createMock(Totals::class);
        $totalsMock->expects($this->once())
            ->method('getCatalogTotalPrice')
            ->willReturn($expectedSubtotal);

        $this->quoteTotalsFactory->expects($this->once())
            ->method('create')
            ->with(['quote' => $this->cartInterface])
            ->willReturn($totalsMock);

        $reflection = new \ReflectionClass(NegotiableQuote3PItemManagement::class);
        $method = $reflection->getMethod('getQuoteOriginalSubtotal');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->neqotiableQuoteItemManagement, [$this->cartInterface]);
        $this->assertEquals($expectedSubtotal, $result);
    }

    /**
     * Tests the getPriceMultiplier method for the scenario where a percentage discount is applied.
     *
     * This test mocks a quote and a negotiable quote with a 10% discount, sets the negotiable quote
     * on the quote, and uses reflection to invoke the protected/private getPriceMultiplier method.
     * It asserts that the resulting price multiplier is 0.9, reflecting the 10% discount.
     *
     * @return void
     */
    public function testGetPriceMultiplierWithPercentageDiscount()
    {
        $quote = $this->createMock(CartInterface::class);
        $negotiableQuote = $this->createMock(NegotiableQuoteInterface::class);

        $negotiableQuote->method('getNegotiatedPriceType')
            ->willReturn(NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PERCENTAGE_DISCOUNT);
        $negotiableQuote->method('getNegotiatedPriceValue')
            ->willReturn(10); // 10% discount

        $this->setNegotiableQuoteOnQuote($quote, $negotiableQuote);

        $reflection = new \ReflectionClass($this->neqotiableQuoteItemManagement);
        $method = $reflection->getMethod('getPriceMultiplier');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->neqotiableQuoteItemManagement, [$quote]);

        $this->assertEquals(0.9, $result);
    }

    /**
     * Tests the getPriceMultiplier method for the scenario where the negotiable quote
     * has an amount discount type. Mocks a $100 discount on a $1000 subtotal and asserts
     * that the price multiplier is correctly calculated as 0.9 (i.e., 1 - 100 / 1000).
     *
     * Steps:
     * - Mocks a quote and negotiable quote with an amount discount of $100.
     * - Sets up the quote totals to return a catalog total price of $1000.
     * - Invokes the protected getPriceMultiplier method via reflection.
     * - Asserts that the returned multiplier is 0.9.
     */
    public function testGetPriceMultiplierWithAmountDiscount()
    {
        $quote = $this->createMock(CartInterface::class);
        $negotiableQuote = $this->createMock(NegotiableQuoteInterface::class);

        $negotiableQuote->method('getNegotiatedPriceType')
            ->willReturn(NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_AMOUNT_DISCOUNT);
        $negotiableQuote->method('getNegotiatedPriceValue')
            ->willReturn(100);

        $this->setNegotiableQuoteOnQuote($quote, $negotiableQuote);

        $this->quoteTotalsFactory->expects($this->once())
            ->method('create')
            ->with(['quote' => $quote])
            ->willReturn($this->totals);

        $this->totals->method('getCatalogTotalPrice')
            ->willReturn(1000);

        $reflection = new \ReflectionClass($this->neqotiableQuoteItemManagement);
        $method = $reflection->getMethod('getPriceMultiplier');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->neqotiableQuoteItemManagement, [$quote]);

        $this->assertEquals(0.9, $result);
    }

    /**
     * Tests the getPriceMultiplier method when the negotiable quote has a proposed total price.
     *
     * This test verifies that the price multiplier is correctly calculated as the ratio of the
     * negotiated proposed total to the original catalog total price. It mocks the necessary
     * dependencies, sets up the negotiable quote with a proposed total, and asserts that the
     * multiplier returned by the private getPriceMultiplier method is as expected.
     *
     * Scenario:
     * - Negotiated price type is set to PROPOSED_TOTAL.
     * - Negotiated price value is set to 800.
     * - Original catalog total price is set to 1000.
     * - The expected price multiplier is 0.8 (800 / 1000).
     */
    public function testGetPriceMultiplierWithProposedTotal()
    {
        $quote = $this->createMock(CartInterface::class);
        $negotiableQuote = $this->createMock(NegotiableQuoteInterface::class);

        $negotiableQuote->method('getNegotiatedPriceType')
            ->willReturn(NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PROPOSED_TOTAL);
        $negotiableQuote->method('getNegotiatedPriceValue')
            ->willReturn(800); // Proposed total

        $this->setNegotiableQuoteOnQuote($quote, $negotiableQuote);

        $this->quoteTotalsFactory->expects($this->once())
            ->method('create')
            ->with(['quote' => $quote])
            ->willReturn($this->totals);

        $this->totals->method('getCatalogTotalPrice')
            ->willReturn(1000); // Original subtotal

        $reflection = new \ReflectionClass($this->neqotiableQuoteItemManagement);
        $method = $reflection->getMethod('getPriceMultiplier');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->neqotiableQuoteItemManagement, [$quote]);

        $this->assertEquals(0.8, $result); // 800 / 1000
    }

    /**
     * Sets up the mock behavior for retrieving the negotiable quote from a quote object.
     *
     * Configures the provided quote mock to return the cart extension attributes mock
     * when getExtensionAttributes() is called, and sets up the cart extension attributes
     * mock to return the specified negotiable quote mock when getNegotiableQuote() is called.
     *
     * @param \PHPUnit\Framework\MockObject\MockObject $quote The quote mock object.
     * @param \PHPUnit\Framework\MockObject\MockObject $negotiableQuote The negotiable quote mock object to be returned.
     */
    private function setNegotiableQuoteOnQuote($quote, $negotiableQuote)
    {
        $quote->method('getExtensionAttributes')->willReturn($this->cartExtensionInterface);
        $this->cartExtensionInterface->method('getNegotiableQuote')->willReturn($negotiableQuote);
    }

    public function testPreserveQuoteCustomPriceReturnsTrueWhenStatusCreatedAndCustomPriceExists()
    {
        $this->cartInterface->method('getAllItems')->willReturn([$this->cartItemInterface]);
        $this->cartItemInterface->method('getCustomPrice')->willReturn(100);

        $reflection = new \ReflectionClass($this->neqotiableQuoteItemManagement);
        $method = $reflection->getMethod('preserveQuoteCustomPrice');
        $method->setAccessible(true);

        $result = $method->invokeArgs(
            $this->neqotiableQuoteItemManagement,
            [$this->cartInterface, NegotiableQuoteInterface::STATUS_CREATED]
        );
        $this->assertEquals(
            true,
            $result,
            'Expected custom price preservation when status is CREATED and custom price exists.'
        );
    }

    public function testPreserveQuoteCustomPriceReturnsFalseWhenNoItems()
    {
        $this->cartInterface->method('getAllItems')->willReturn([]);

        $reflection = new \ReflectionClass($this->neqotiableQuoteItemManagement);
        $method = $reflection->getMethod('preserveQuoteCustomPrice');
        $method->setAccessible(true);

        $result = $method->invokeArgs(
            $this->neqotiableQuoteItemManagement,
            [$this->cartInterface, NegotiableQuoteInterface::STATUS_CREATED]
        );
        $this->assertEquals(
            false,
            $result,
            'Expected false when no quote items are present.'
        );
    }

    public function testUpdateQuoteItemsCustomPricesElseBranch()
    {
        $quoteId = 123;
        $qty = 2;
        $originalPrice = 100.0;
        $priceMultiplier = 1.1;
        $storeTaxRate = 1.2;
        $customerTaxRate = 1.0;
        $baseToQuoteRate = 1.5;

        $this->helper->method('isUploadToQuoteEnabled')->willReturn(true);
        $this->quoteRepository->method('get')->with($quoteId, ['*'])->willReturn($this->cartInterface);
        $this->cartInterface->method('getAllItems')->willReturn([$this->cartItemInterface]);
        $this->cartInterface->method('getCurrency')->willReturn($this->currency);
        $this->currency->method('getBaseCurrencyCode')->willReturn('USD');
        $this->currency->method('getQuoteCurrencyCode')->willReturn('EUR');
        $this->currency->method('getBaseToQuoteRate')->willReturn($baseToQuoteRate);

        $this->storeCustomerRate->expects($this->once())
            ->method('init')
            ->with($this->cartInterface);

        $this->storeCustomerRate->expects($this->once())
            ->method('getStoreTaxRateFactor')
            ->with($this->cartItemInterface)
            ->willReturn($storeTaxRate);

        $this->storeCustomerRate->expects($this->once())
            ->method('getCustomerTaxRateFactor')
            ->with($this->cartItemInterface)
            ->willReturn($customerTaxRate);

        $this->cartInterface->method('getExtensionAttributes')
            ->willReturn($this->cartExtensionInterface);
        $this->cartExtensionInterface->method('getNegotiableQuote')
            ->willReturn($this->negotiableQuoteInterface);
        $this->negotiableQuoteInterface->method('getIsRegularQuote')
            ->willReturn(true);
        $this->negotiableQuoteInterface->method('getStatus')
            ->willReturn(NegotiableQuoteInterface::STATUS_CREATED);
        $this->negotiableQuoteInterface->method('getNegotiatedPriceValue')->willReturn(10);
        $this->negotiableQuoteInterface->method('getNegotiatedPriceType')
            ->willReturn(NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PERCENTAGE_DISCOUNT);
        $this->cartItemInterface->method('getExtensionAttributes')
            ->willReturn($this->cartItemExtensionInterface);
        $this->cartItemExtensionInterface->method('getNegotiableQuoteItem')
            ->willReturn($this->negotiableQuoteItemInterface);
        $this->negotiableQuoteItemInterface->method('getOriginalPrice')
            ->willReturn($originalPrice);
        $this->cartItemInterface->method('getQty')->willReturn($qty);
        $this->cartItemInterface->method('getStoreId')->willReturn(1);
        $this->quoteTotalsFactory->method('create')->willReturn($this->totals);
        $this->totals->expects($this->any())->method('getCatalogTotalPrice')
            ->willReturn(1000);
        $this->totals->method('getSubtotal')->willReturn(800);
        $this->totals->method('getSubtotal')->with()->willReturn(750);
        $this->cartInterface->expects($this->once())->method('collectTotals');
        $this->quoteRepository->expects($this->once())
            ->method('save')
            ->with($this->cartInterface);

        $result = $this->neqotiableQuoteItemManagement->updateQuoteItemsCustomPrices($quoteId, true);
        $this->assertEquals(true, $result);
    }
}
