<?php
declare(strict_types=1);

namespace Fedex\ExpiredItems\Test\Unit\Model\Quote;

use PHPUnit\Framework\TestCase;
use Fedex\ExpiredItems\Model\Quote\UpdaterModel;
use Magento\Quote\Model\ResourceModel\QuoteFactory as QuoteResourceFactory;
use Magento\Quote\Model\ResourceModel\Quote\ItemFactory as QuoteItemResourceFactory;
use Magento\Quote\Model\ResourceModel\Quote\Item\OptionFactory as CustomOptionResourceFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Tax\Model\CalculationFactory as TaxCalculationFactory;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Mirakl\Connector\Model\ResourceModel\OfferFactory as OfferResourceFactory;
use Mirakl\Connector\Helper\Config;
use Mirakl\Connector\Helper\Quote as QuoteHelper;
use Mirakl\Connector\Helper\Tax as TaxHelper;
use Mirakl\Connector\Model\Quote\OfferCollector;
use Mirakl\Connector\Model\Quote\Synchronizer;
use Fedex\ProductUnavailabilityMessage\Model\CheckProductAvailabilityDataModel;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Mirakl\MMP\Front\Domain\Collection\Shipping\OrderShippingFeeCollection;

class UpdaterModelTest extends TestCase
{
    /**
     * @var (\Mirakl\MMP\Front\Domain\Collection\Shipping\OrderShippingFeeCollection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderShippingFeeCollectionMock;
    private UpdaterModel $updaterModel;

    // Mocks for dependencies
    private MockObject $quoteResourceFactoryMock;
    private MockObject $quoteItemResourceFactoryMock;
    private MockObject $customOptionResourceFactoryMock;
    private MockObject $priceCurrencyMock;
    private MockObject $taxCalculationFactoryMock;
    private MockObject $eventManagerMock;
    private MockObject $offerResourceFactoryMock;
    private MockObject $configMock;
    private MockObject $offerCollectorMock;
    private MockObject $quoteSynchronizerMock;
    private MockObject $quoteHelperMock;
    private MockObject $taxHelperMock;
    private MockObject $loggerMock;
    private MockObject $checkProductAvailabilityDataModelMock;

    // Common Mocks for CartInterface and CartItemInterface
    private MockObject $quoteMock;
    private MockObject $itemMock;

    protected function setUp(): void
    {
        // Create common mocks for all dependencies
        $this->quoteResourceFactoryMock = $this->createMock(QuoteResourceFactory::class);
        $this->quoteItemResourceFactoryMock = $this->createMock(QuoteItemResourceFactory::class);
        $this->customOptionResourceFactoryMock = $this->createMock(CustomOptionResourceFactory::class);
        $this->priceCurrencyMock = $this->createMock(PriceCurrencyInterface::class);
        $this->taxCalculationFactoryMock = $this->createMock(TaxCalculationFactory::class);
        $this->eventManagerMock = $this->createMock(EventManagerInterface::class);
        $this->offerResourceFactoryMock = $this->createMock(OfferResourceFactory::class);
        $this->configMock = $this->createMock(Config::class);
        $this->offerCollectorMock = $this->createMock(OfferCollector::class);
        $this->quoteSynchronizerMock = $this->createMock(Synchronizer::class);
        $this->quoteHelperMock = $this->createMock(QuoteHelper::class);
        $this->taxHelperMock = $this->createMock(TaxHelper::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['error']) // Mock specific methods
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->checkProductAvailabilityDataModelMock = $this->createMock(CheckProductAvailabilityDataModel::class);

        $this->quoteMock = $this->getMockBuilder(CartInterface::class)
            ->setMethods(['setHasError', 'addMessage','getItemsCount','getItems','collectTotals']) // Mock specific methods
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->itemMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->setMethods(['getQty', 'getProduct', 'getName']) // Mock specific methods
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock->method('getItemsCount')->willReturn(1);
        $this->quoteMock->method('getItems')->willReturn([$this->itemMock]);
        $this->quoteMock->expects($this->any())
            ->method('setHasError')
            ->willReturn(null);
        $this->orderShippingFeeCollectionMock = $this->createMock(OrderShippingFeeCollection::class);

        // Instantiate the UpdaterModel with all the mocks
        $this->updaterModel = new UpdaterModel(
            $this->quoteResourceFactoryMock,
            $this->quoteItemResourceFactoryMock,
            $this->customOptionResourceFactoryMock,
            $this->priceCurrencyMock,
            $this->taxCalculationFactoryMock,
            $this->eventManagerMock,
            $this->offerResourceFactoryMock,
            $this->configMock,
            $this->offerCollectorMock,
            $this->quoteSynchronizerMock,
            $this->quoteHelperMock,
            $this->taxHelperMock,
            $this->loggerMock,
            $this->checkProductAvailabilityDataModelMock
        );
    }

    /**
     * @return void
     */
    public function testSynchronizeWithEmptyQuote()
    {
        $this->quoteMock->method('getItemsCount')->willReturn(0); // Empty quote
        $this->quoteMock->expects($this->any())->method('setHasError')->with(false);
    }

    /**
     * @return void
     */
    public function testSynchronizeWithValidQuote()
    {
        $this->quoteMock->method('getItemsCount')->willReturn(1);
        $this->quoteMock->method('getItems')->willReturn([$this->itemMock]);
        $this->quoteHelperMock->method('isMiraklQuote')->willReturn(true);
        $this->itemMock->method('getQty')->willReturn(2);
        $this->itemMock->method('getProduct')->willReturn($this->createMock(\Magento\Catalog\Model\Product::class));
        $this->itemMock->method('getName')->willReturn('Sample Product');
        $this->quoteSynchronizerMock->method('getShippingFees')->willReturn($this->createMock(OrderShippingFeeCollection::class));
        $this->updaterModel->synchronize($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('collectTotals');
        $this->loggerMock->expects($this->never())->method('error');
    }

    /**
     * @return void
     */
    public function testSynchronizeWithQuoteError()
    {
        $this->quoteMock->method('getItemsCount')->willReturn(1);
        $this->quoteMock->method('getItems')->willReturn([$this->itemMock]);
        $this->quoteHelperMock->method('isMiraklQuote')->willReturn(true);
        $this->itemMock->method('getQty')->willReturn(10);
        $this->itemMock->method('getProduct')->willReturn($this->createMock(\Magento\Catalog\Model\Product::class));
        $this->itemMock->method('getName')->willReturn('Sample Product');
        $this->checkProductAvailabilityDataModelMock->method('isE441563ToggleEnabled')->willReturn(false);
        $this->quoteSynchronizerMock->method('getShippingFees')->willReturn(0);
        $this->loggerMock->expects($this->any())->method('error');
        $this->updaterModel->synchronize($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('setHasError')->with(true);
    }
}
