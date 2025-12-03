<?php

declare (strict_types = 1);

namespace Fedex\MarketplaceProduct\Test\Unit\Model\Config\Backend;

use Fedex\MarketplaceProduct\Model\Config\Backend\MarketplaceProduct;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config as StoreConfig;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use \Magento\Framework\Module\Manager as ModuleManager;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class MarketplaceProductTest extends \PHPUnit\Framework\TestCase
{
    protected $context;
    protected $productCollectionFactory;
    /**
     * @var (\Magento\Catalog\Model\ResourceModel\Product\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $productCollection;
    protected $collectionMock;
    /**
     * Mirakl product id
     */
    private CONST MIRAKL_PRODUCT_ID = '1afdd993-f77a-4345-bfef-d35f9c0748ec';

    /**
     * Mirakl layout id
     */
    private CONST MIRAKL_LAYOUT_ID = 'product-full-width';

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
     * @var MarketplaceProduct
     */
    protected MarketplaceProduct $marketplaceProduct;

    /**
     * @var |MockObject
     */
    protected $toggleConfig;

    /**
     * @var ProductResourceModel|MockObject
     */
    private $productMock;

    private $request;

    /**
     * @var CollectionFactory;
     */
    private $collectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

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

        $this->productCollectionFactory =
            $this->getMockBuilder(ProductCollectionFactory::class)
                ->setMethods(['create', 'addAttributeToSelect'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->productCollection = $this->getMockBuilder(Collection::class)
            ->setMethods([
                'setStore',
                'addIdFilter',
                'addStoreFilter',
                'getItems',
                'setPageLayout',
                'joinAttribute',
                'addAttributeToSelect',
                'addOptionsToResult',
                'addAttributeToFilter',
                'saveAttribute'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $managerInterface = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->method('getEventDispatcher')->willReturn($managerInterface);

        $this->productMock = $this->getMockBuilder(ProductResourceModel::class)
            ->setMethods(['saveAttribute', 'getResource'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->setMethods(
                [
                    'addAttributeToSelect',
                    'addAttributeToFilter',
                    'load',
                    'setData',
                    'saveAttribute',
                    'getResource'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->marketplaceProduct = new MarketplaceProduct(
            $this->context,
            $this->getMockBuilder(Registry::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock(),
            $this->cacheTypeList,
            $this->configValueFactory,
            $this->resourceConfig,
            $this->moduleManager,
            $this->productCollectionFactory,
            $this->toggleConfig,
            $this->productMock,
            $this->request,
            $this->collectionFactory,
            $this->storeManager,
            $this->getMockBuilder(AbstractResource::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(AbstractDb::class)->disableOriginalConstructor()->getMock(),
            []
        );
    }

    /**
     * Test 3p Enabled
     *
     * @return void
     * @throws LocalizedException
     */
    public function test3pEnabled(): void
    {
        $this->setCommonVal();
        $this->marketplaceProduct->setData(['value' => true]);
        $this->marketplaceProduct->afterSave();
    }

    /**
     * Set common values
     *
     * @return void
     * @throws LocalizedException
     */
    private function setCommonVal(): void
    {
        $items = $this->getMockedItems();
        $this->setCollectionMock($items);
        $this->cacheTypeList->expects($this->any())->method('cleanType');
        $this->collectionMock->method('getResource')->willReturn($this->productMock);
        $this->productCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->collectionMock);
    }

    /**
     * Set Collection Mock
     *
     * @param DataObject $items
     * @return void
     * @throws LocalizedException
     */
    private function setCollectionMock(DataObject $items): void
    {
        $this->collectionMock->addItem($items);
        $this->collectionMock->expects($this->any())->method('addAttributeToSelect')
            ->with('*')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('addAttributeToFilter')
            ->with('mirakl_mcm_product_id', ['notnull' => true])->willReturn($this->productMock);
    }

    /**
     * Return mocked items
     *
     * @return DataObject
     */
    private function getMockedItems(): DataObject
    {
        return new DataObject(
            ['mirakl_mcm_product_id' => self::MIRAKL_PRODUCT_ID, 'page_layout' => self::MIRAKL_LAYOUT_ID]
        );
    }
}
