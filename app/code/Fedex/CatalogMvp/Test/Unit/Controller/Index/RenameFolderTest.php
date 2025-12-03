<?php
declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Controller\Index;

use Fedex\CatalogMvp\Controller\Index\RenameFolder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CatalogMvp\Api\ConfigInterface as CatalogMvpConfigInterface;
use Fedex\CatalogMvp\Helper\EtagHelper;

class RenameFolderTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    protected $catalogMvp;
    protected $categoryRepositoryInterface;
    protected $category;
    protected $resultJsonFactory;
    protected $resultJson;
    protected $request;
    protected $storeManager;
    protected $store;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $renameFolder;
    protected $toggleConfigMock;
    protected $catalogMvpConfigInterface;
    protected $etagHelper;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogMvp = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpSharedCatalogEnable','isSharedCatalogPermissionEnabled', 'generateCategoryName', 'isD231833FixEnabled'])
            ->getMock();

        $this->categoryRepositoryInterface = $this->getMockBuilder(CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        $this->category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['load','save','setName','setStoreId','setEtag','getParentId'])
            ->getMock();
        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPost'])
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();
        $this->store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId'])
            ->getMockForAbstractClass();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
            
        $this->catalogMvpConfigInterface = $this->getMockBuilder(CatalogMvpConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isB2371268ToggleEnabled'])
            ->getMockForAbstractClass();

        $this->etagHelper = $this->getMockBuilder(EtagHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateEtag'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->renameFolder = $this->objectManager->getObject(
            RenameFolder::class,
            [
                'context' => $this->context,
                'category' => $this->category,
                'categoryRepositoryInterface' => $this->categoryRepositoryInterface,
                'resultJsonFactory' => $this->resultJsonFactory,
                'catalogMvp' => $this->catalogMvp,
                '_request' => $this->request,
                'storeManager' => $this->storeManager,
                'toggleConfig'=>$this->toggleConfigMock,
                'catalogMvpConfigInterface' => $this->catalogMvpConfigInterface,
                'etagHelper' => $this->etagHelper
            ]
        );
    }

    public function testExecute(): void
    {
        $data = [];
        $data['id'] = 234;
        $data['name'] = "Rename Folder";
        $this->request->expects($this->any())->method('getPost')->willReturn($data);
        $this->catalogMvp->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvp->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setData')->willReturnSelf();
        $this->categoryRepositoryInterface->expects($this->any())->method('get')->willReturn($this->category);
        $this->category->expects($this->any())->method('setName')->willReturnSelf();
        $this->category->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->category->expects($this->any())->method('save')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->store);
        $this->store->expects($this->any())->method('getStoreId')->willReturn(89);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);    
        $this->categoryRepositoryInterface->expects($this->any())->method('get')->willReturn($this->category);  
        $this->catalogMvpConfigInterface->expects($this->any())->method('isB2371268ToggleEnabled')->willReturn(true);
        $this->etagHelper->expects($this->any())->method('generateEtag')->willReturn('test');
        $this->category->expects($this->any())->method('setEtag')->willReturn('test');
        $this->assertEquals($this->resultJson, $this->renameFolder->execute());
    }
    public function testExecuteWithException(): void
    {
        $data = [];
        $data['id'] = 234;
        $data['name'] = "Rename Folder";
        $this->request->expects($this->any())->method('getPost')->willReturn($data);
        $this->catalogMvp->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvp->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setData')->willReturnSelf();
        $this->categoryRepositoryInterface->expects($this->any())->method('get')->willReturn($this->category);
        $this->category->expects($this->any())->method('setName')->willReturnSelf();
        $this->category->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->store);
        $this->store->expects($this->any())->method('getStoreId')->willReturn(89);
        $this->category->expects($this->any())->method('save')->willThrowException(new \Exception());
        $this->assertEquals($this->resultJson, $this->renameFolder->execute());
    }

    public function testExecuteWithParentCategoryEtagGeneration(): void
    {
        $data = [];
        $data['id'] = 234;
        $data['name'] = "Rename Folder";
        $this->request->expects($this->any())->method('getPost')->willReturn($data);
        
        // Setting up mock return values
        $this->catalogMvp->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvp->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setData')->willReturnSelf();
        $this->categoryRepositoryInterface->expects($this->any())->method('get')->willReturn($this->category);
        $this->category->expects($this->any())->method('setName')->willReturnSelf();
        $this->category->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->category->expects($this->any())->method('save')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->store);
        $this->store->expects($this->any())->method('getStoreId')->willReturn(89);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);    
        $this->catalogMvpConfigInterface->expects($this->any())->method('isB2371268ToggleEnabled')->willReturn(true);
        
        $parentCategory = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'setEtag', 'save'])
            ->getMock();

        $parentCategoryId = 567;
        $this->category->expects($this->any())->method('getParentId')->willReturn($parentCategoryId);
        
        $this->etagHelper->expects($this->any())
            ->method('generateEtag')
            ->with($parentCategory)
            ->willReturn('parent-test-etag');
        
        $parentCategory->expects($this->any())
            ->method('setEtag')
            ->with('parent-test-etag')
            ->willReturnSelf();

        $parentCategory->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->categoryRepositoryInterface->expects($this->any())->method('get')->willReturn($parentCategory);
        $this->etagHelper->expects($this->any())->method('generateEtag')->willReturn('test');
        $this->assertEquals($this->resultJson, $this->renameFolder->execute());
    }
}
