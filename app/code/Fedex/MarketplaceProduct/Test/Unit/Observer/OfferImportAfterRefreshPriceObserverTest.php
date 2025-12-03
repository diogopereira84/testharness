<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceProduct
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Observer;
use Fedex\Catalog\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Action;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\DirectoryList as DirectoryListFileSystem;
use Magento\Store\Model\Store;
use Magento\Catalog\Api\Data\BasePriceInterfaceFactory;
use Magento\Catalog\Api\BasePriceStorageInterface;
use Fedex\MarketplaceProduct\Observer\OfferImportAfterRefreshPriceObserver;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class OfferImportAfterRefreshPriceObserverTest extends \PHPUnit\Framework\TestCase
{
    protected $file;
    protected $csv;
    /**
     * @var (\Magento\Framework\Filesystem\DirectoryList & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $directoryList;
    protected $basePriceInterfaceFactory;
    /**
     * @var (\Magento\Catalog\Api\BasePriceStorageInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $basePriceStorage;
    protected $productAction;
    protected $productModel;
    protected $catalogConfig;
    protected $observer;
    protected $event;
    protected $process;
    protected $offerImportAfterRefreshPriceObserver;
    protected $toggleConfig;
    protected function setUp(): void
    {
        $this->file = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->csv = $this->getMockBuilder(Csv::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryList = $this->getMockBuilder(DirectoryListFileSystem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->basePriceInterfaceFactory = $this->getMockBuilder(BasePriceInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->basePriceStorage = $this->getMockBuilder(BasePriceStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productAction = $this->getMockBuilder(Action::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateAttributes'])
            ->getMock();
        $this->productModel = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIdBySku'])
            ->getMock();
        $this->catalogConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTigerDisplayUnitCost3P1PProducts'])
            ->getMock();

        $this->observer = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->addMethods(['getFile'])
            ->onlyMethods(['getEvent'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->event = $this->getMockBuilder(\Magento\Framework\Event::class)
            ->addMethods(['getProcess', 'getSkus'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->process = $this->getMockBuilder(\Mirakl\Process\Model\Process::class)
            ->onlyMethods(['output'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->event->method('getProcess')->willReturn($this->process);
        $this->observer->method('getEvent')->willReturn($this->event);

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getToggleConfigValue'])
            ->getMock();

        $this->toggleConfig->method('getToggleConfigValue')->willReturn(false);

        $this->offerImportAfterRefreshPriceObserver = new OfferImportAfterRefreshPriceObserver(
            $this->file,
            $this->csv,
            $this->directoryList,
            $this->basePriceInterfaceFactory,
            $this->basePriceStorage,
            $this->productAction,
            $this->productModel,
            $this->catalogConfig,
            $this->toggleConfig
        );
    }

    public function testExecute(): void
    {
        $this->event->expects($this->once())->method('getSkus')->willReturn(['sku-1', 'sku-2']);
        $this->observer->expects($this->exactly(2))->method('getFile')->willReturn('file.csv');
        $this->process->expects($this->exactly(3))->method('output');

        $this->file->expects($this->once())
            ->method('isExists')
            ->willReturn(true);

        $csvData = [
            ['product-sku', 'price', 'unit-cost', 'base-qty'],
            ['SKU1', '10.0', '1.0', '30'],
            ['SKU2', '20.0', '2.0', '60']
        ];

        $this->csv->expects($this->once())
            ->method('setDelimiter')
            ->with(';');
        $this->csv->expects($this->once())
            ->method('getData')
            ->willReturn($csvData);

        $basePriceInterface = $this->getMockBuilder(\Magento\Catalog\Api\Data\BasePriceInterface::class)
            ->getMock();
        $this->basePriceInterfaceFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($basePriceInterface);
        $basePriceInterface->expects($this->exactly(2))
            ->method('setStoreId')
            ->with(Store::DEFAULT_STORE_ID);

        $this->catalogConfig->expects($this->once())
            ->method('getTigerDisplayUnitCost3P1PProducts')
            ->willReturn(true);
        $this->productModel->expects($this->any())
            ->method('getIdBySku')
            ->withConsecutive(['SKU1'], ['SKU2'])
            ->willReturnOnConsecutiveCalls('123', '321');
        $this->productAction->expects($this->any())
            ->method('updateAttributes')
            ->withConsecutive(
                [['123'], ['base_price' => 10.0, 'base_quantity' => 30, 'unit_cost' => 1.0], Store::DEFAULT_STORE_ID],
                [['321'], ['base_price' => 20.0, 'base_quantity' => 60, 'unit_cost' => 2.0], Store::DEFAULT_STORE_ID]
            );

        $this->offerImportAfterRefreshPriceObserver->execute($this->observer);
    }

    public function testExecuteWithoutSkus()
    {
        $this->event->expects($this->once())->method('getSkus')->willReturn([]);
        $this->offerImportAfterRefreshPriceObserver->execute($this->observer);
    }

    public function testExecuteThrowException(): void
    {
        $this->event->expects($this->once())->method('getSkus')->willReturn(['sku-1']);
        $this->observer->expects($this->exactly(2))->method('getFile')->willReturn('file.csv');
        $this->process->expects($this->exactly(3))->method('output');

        $this->file->expects($this->once())
            ->method('isExists')
            ->willReturn(true);

        $invalidCsvData = [
            ['price'],
            ['10.0']
        ];

        $this->csv->expects($this->once())
            ->method('setDelimiter')
            ->with(';');
        $this->csv->expects($this->once())
            ->method('getData')
            ->willReturn($invalidCsvData);

        $this->offerImportAfterRefreshPriceObserver->execute($this->observer);
    }
}
