<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\RequestInterface;
use Fedex\CatalogMvp\Controller\Index\UpdateCategory;
use Magento\Framework\Phrase;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class UpdateCategoryTest
 * Handle the UpdateCategory test cases of the CatalogMvp controller
 */
class UpdateCategoryTest extends TestCase
{

    /**
     * @var (\Magento\Framework\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $categoryFactoryMock;
    protected $storeManager;
    protected $store;
    protected $categoryMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var (\Magento\Catalog\Model\ResourceModel\Category & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $categoryResourceMock;
    protected $cacheTypeListMock;
    protected $cacheFrontendPoolMock;
    protected $request;
    protected $catalogMvp;
    protected $context;
    protected $categoryFactory;
    protected $categoryResource;
    protected $logger;
    protected $cacheTypeList;
    protected $cacheFrontendPool;
    protected $categoryRepositoryInterfaceMock;
    protected  $toggleConfigMock;

    /**
     * @var Context
     */
    protected Context $registryMock;
    protected CollectionFactory $collectionFactoryMock;
    protected JsonFactory $jsonFactory;
    protected Collection $productCollectionMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryFactoryMock = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['load','create','setStoreId'])
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();
        $this->store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $this->categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->categoryResourceMock = $this->getMockBuilder(CategoryResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheTypeListMock = $this->getMockBuilder(TypeListInterface::class)
        ->disableOriginalConstructor()
        ->setMethods(['cleanType'])
        ->getMockForAbstractClass();

        $this->cacheFrontendPoolMock = $this->getMockBuilder(Pool::class)
        ->disableOriginalConstructor()
        ->setMethods(['getBackend', 'clean'])
        ->getMock();

        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create', 'setData', 'addAttributeToSelect'])
        ->getMock();

        $this->productCollectionMock = $this->getMockBuilder(Collection::class)
        ->disableOriginalConstructor(['create', 'setData', 'addAttributeToSelect'])
        ->getMock();

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create', 'setData'])
        ->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->categoryRepositoryInterfaceMock= $this->getMockBuilder(CategoryRepositoryInterface::class)
                ->setMethods(['get'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();
        $this->toggleConfigMock= $this->getMockBuilder(ToggleConfig::class)
                ->setMethods(['getToggleConfigValue'])
                ->disableOriginalConstructor()
                ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->catalogMvp = $objectManagerHelper->getObject(
            UpdateCategory::class,
            [
                'context' => $this->contextMock,
                'categoryFactory' => $this->categoryFactoryMock,
                'categoryResource'  => $this->categoryResourceMock,
                'logger' => $this->loggerMock,
                'cacheTypeList' => $this->cacheTypeListMock,
                '_cacheFrontendPool' => $this->cacheFrontendPoolMock,
                '_request' => $this->request,
                'storeManager' => $this->storeManager,
                'toggleConfig'=>$this->toggleConfigMock,
                'categoryRepositoryInterface'=> $this->categoryRepositoryInterfaceMock,
                'collectionFactory' => $this->collectionFactoryMock,
                'jsonFactory' => $this->jsonFactory
            ]
        );
    }

    /**
     * @test Execute if case
     */
    public function testExecuteIfCase()
    {
        $this->prepareRequestMock();

        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->store);
        $this->store->expects($this->any())->method('getId')->willReturn(235);

        $this->categoryFactoryMock->expects($this->any())->method('create')->willReturnSelf();

        $this->categoryFactoryMock->expects($this->any())->method('load')->willReturn($this->categoryMock);

        $this->categoryMock->expects($this->any())->method('getIsActive')->willReturn(1);

        $this->categoryMock->expects($this->any())->method('setIsActive')->willReturnSelf();

        $this->categoryMock->expects($this->any())->method('save')->willReturnSelf();

        $this->categoryMock->expects($this->any())->method('getIsActive')->willReturnSelf();

        $this->categoryMock->expects($this->any())->method('setStoreId')->willReturnSelf();

        $this->categoryMock->expects($this->any())->method('setIsActive')->willReturnSelf();

        $this->categoryMock->expects($this->any())->method('save')->willReturnSelf();

        $this->collectionFactoryMock->expects($this->any())
        ->method('create')
        ->willReturn($this->productCollectionMock);

        // Configure product collection mock
        $this->productCollectionMock->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturnSelf();
        $this->productCollectionMock->expects($this->any())
            ->method('addCategoriesFilter')
            ->willReturnSelf();
        $this->jsonFactory->expects($this->any())
        ->method('create')
        ->willReturn($this->jsonFactory);
        $this->jsonFactory->expects($this->any())
        ->method('setData')
        ->willReturn(['success' => true, 'message' => 'Category updated successfully.']);

        $this->cacheTypeListMock->expects($this->any())->method('cleanType')->willReturnSelf();

        $cachetIterator = new \ArrayIterator([0 => $this->cacheFrontendPoolMock]);

        $this->cacheFrontendPoolMock->expects($this->any())->method('getBackend')->willReturnSelf();

        $this->cacheFrontendPoolMock->expects($this->any())->method('clean')->willReturn($cachetIterator);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue') ->willReturn(true);
        $this->categoryRepositoryInterfaceMock->expects($this->any())->method('get')->willReturn($this->categoryMock);
        $this->assertNotNull($this->catalogMvp->execute());
    }

    /**
     * @test Execute Else case
     */
    public function testExecuteElseCase()
    {
        $this->prepareRequestMock();
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->store);
        $this->store->expects($this->any())->method('getId')->willReturn(235);
        $this->categoryFactoryMock->expects($this->any())->method('create')->willReturnSelf();

        $this->categoryRepositoryInterfaceMock->expects($this->any())->method('get')->willReturn($this->categoryMock);

        $this->categoryMock->expects($this->any())->method('getIsActive')->willReturn(0);

        $this->categoryMock->expects($this->any())->method('setIsActive')->willReturnSelf();

        $this->categoryMock->expects($this->any())->method('save')->willReturnSelf();

        $this->categoryMock->expects($this->any())->method('getIsActive')->willReturnSelf();

        $this->categoryMock->expects($this->any())->method('setStoreId')->willReturnSelf();

        $this->categoryMock->expects($this->any())->method('setIsActive')->willReturnSelf();

        $this->categoryMock->expects($this->any())->method('save')->willReturnSelf();

        $this->productCollectionMock->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturnSelf();
        $this->productCollectionMock->expects($this->any())
            ->method('addCategoriesFilter')
            ->willReturnSelf();
        $this->jsonFactory->expects($this->any())
        ->method('create')
        ->willReturn($this->jsonFactory);
        $this->jsonFactory->expects($this->any())
        ->method('setData')
        ->willReturn(['success' => true, 'message' => 'Category updated successfully.']);

        $this->cacheTypeListMock->expects($this->any())->method('cleanType')->willReturnSelf();

        $cachetIterator = new \ArrayIterator([0 => $this->cacheFrontendPoolMock]);

        $this->cacheFrontendPoolMock->expects($this->any())->method('getBackend')->willReturnSelf();

        $this->cacheFrontendPoolMock->expects($this->any())->method('clean')->willReturn($cachetIterator);

        $this->assertNotNull($this->catalogMvp->execute());
    }




    /**
     * Prepare Request Mock.
     *
     * @return void
     */
    private function prepareRequestMock()
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturn(123);
    }
}
