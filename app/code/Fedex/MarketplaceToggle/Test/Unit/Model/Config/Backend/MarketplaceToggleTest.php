<?php

declare(strict_types=1);

namespace Fedex\MarketplaceToggle\Test\Model\Config\Backend;

use Fedex\MarketplaceToggle\Model\Config\Backend\MarketplaceToggle;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config as StoreConfig;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\App\ScopeInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use \Magento\Framework\Module\Manager as ModuleManager;

class MarketplaceToggleTest extends TestCase
{
    protected $context;
    /**
     * @var MockObject|ValueFactory
     */
    protected MockObject|ValueFactory $configValueFactory;

    /**
     * @var MockObject|Config
     */
    protected MockObject|Config $resourceConfig;

    /**
     * @var MockObject|StoreConfig
     */
    protected MockObject|StoreConfig $storeConfig;

    /**
     * @var MockObject|ModuleManager
     */
    protected MockObject|ModuleManager $moduleManager;

    /**
     * @var MockObject|TypeListInterface
     */
    protected MockObject|TypeListInterface $cacheTypeList;

    /**
     * @var MarketplaceToggle
     */
    protected MarketplaceToggle $marketplaceToggle;


    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->configValueFactory = $this->getMockBuilder(ValueFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeConfig = $this->getMockBuilder(StoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->moduleManager = $this->getMockBuilder(ModuleManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cacheTypeList = $this->getMockBuilder(TypeListInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $managerInterface = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->method('getEventDispatcher')->willReturn($managerInterface);

        $this->marketplaceToggle = new MarketplaceToggle(
            $this->context,
            $this->getMockBuilder(Registry::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock(),
            $this->cacheTypeList,
            $this->configValueFactory,
            $this->resourceConfig,
            $this->storeConfig,
            $this->moduleManager,
            $this->getMockBuilder(AbstractResource::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(AbstractDb::class)->disableOriginalConstructor()->getMock(),
            []
        );
    }

    /**
     * Test Method for addRecord.
     */
    public function testAddRecord()
    {
        $this->moduleManager->method('isEnabled')->willReturn(true);
        $this->resourceConfig->method('saveConfig')->willReturn($this->resourceConfig);
        $this->moduleManager->expects($this->once())->method('isEnabled');
        $this->resourceConfig->expects($this->once())->method('saveConfig');
        $this->cacheTypeList->expects($this->once())->method('cleanType');
        $this->marketplaceToggle->afterSave();
    }
}
