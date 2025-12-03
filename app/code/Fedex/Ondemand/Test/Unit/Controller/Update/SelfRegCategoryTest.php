<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Ondemand\Test\Unit\Controller\Update;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\Group;
use Fedex\Ondemand\Controller\Update\SelfRegCategory;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\CategoryManagementInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product;
use Magento\CatalogPermissions\Model\PermissionFactory;
use Magento\CatalogPermissions\Model\Permission;
use Magento\Framework\Json\Helper\Data;
use Magento\Catalog\Api\Data\CategoryTreeInterface;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection as PermissionCollection;

class SelfRegCategoryTest extends TestCase
{
    protected $storeGroupFactoryMock;
    protected $storeGroupMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var (\Magento\Framework\Json\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $jsonHelperMock;
    protected $categoryManagementInterfaceMock;
    protected $categoryFactoryMock;
    protected $categoryMock;
    protected $categoryCollectionMock;
    protected $collectionFactoryMock;
    protected $collectionMock;
    /**
     * @var (\Magento\Catalog\Api\CategoryLinkManagementInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $categoryLinkManagementInterfaceMock;
    protected $productFactoryMock;
    protected $productMock;
    protected $permissionFactoryMock;
    protected $permissionMock;
    protected $categoryTreeInterfaceMock;
    protected $categoryPermissionCollectionMock;
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $selfRegCategoryMock;
    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->storeGroupFactoryMock  = $this->getMockBuilder(GroupFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->storeGroupMock  = $this->getMockBuilder(Group::class)
            ->setMethods(['load', 'getRootCategoryId', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->loggerMock  = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['info', 'error'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->jsonHelperMock  = $this->getMockBuilder(Data::class)
            ->setMethods(['jsonEncode'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->categoryManagementInterfaceMock  = $this->getMockBuilder(CategoryManagementInterface::class)
            ->setMethods(['getTree', 'move'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
            
        $this->categoryFactoryMock  = $this->getMockBuilder(CategoryFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->categoryMock  = $this->getMockBuilder(Category::class)
            ->setMethods(['load', 'getId', 'setId',
            'getName', 'getUrlKey','setName', 'setUrlKey', 'save', 'getCollection'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->categoryCollectionMock  = $this->getMockBuilder(CategoryCollection::class)
            ->setMethods(['getIterator', 'addAttributeToFilter'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->collectionFactoryMock  = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionMock  = $this->getMockBuilder(Collection::class)
            ->setMethods(['getIterator','addCategoriesFilter', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->categoryLinkManagementInterfaceMock  = $this->getMockBuilder(CategoryLinkManagementInterface::class)
            ->setMethods(['getTree'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
            
        $this->productFactoryMock  = $this->getMockBuilder(ProductFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->productMock  = $this->getMockBuilder(Product::class)
            ->setMethods(['load', 'getCategoryIds', 'getSku', 'setCategoryIds', 'save'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->permissionFactoryMock  = $this->getMockBuilder(PermissionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->permissionMock  = $this->getMockBuilder(Permission::class)
            ->setMethods(['getCollection', 'setData', 'getData', 'save'])
            ->disableOriginalConstructor()
            ->getMock();
 
        $this->categoryTreeInterfaceMock  = $this->getMockBuilder(CategoryTreeInterface::class)
            ->setMethods(['getId', 'getChildrenData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
               
        $this->categoryPermissionCollectionMock  = $this->getMockBuilder(PermissionCollection::class)
            ->setMethods(['getIterator', 'addFieldToFilter', 'getSize'])
            ->disableOriginalConstructor()
            ->getMock();
 
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        
        $this->selfRegCategoryMock = $this->objectManagerHelper->getObject(
            SelfRegCategory::class,
            [
                'groupFactory' => $this->storeGroupFactoryMock,
                'logger' => $this->loggerMock,
                'jsonHelper' => $this->jsonHelperMock,
                'categoryManagementInterface' => $this->categoryManagementInterfaceMock,
                'categoryFactory' => $this->categoryFactoryMock,
                'collectionFactory' => $this->collectionFactoryMock,
                'categoryLinkManagementInterface' => $this->categoryLinkManagementInterfaceMock,
                'productFactory' => $this->productFactoryMock,
                'permissionFactory' => $this->permissionFactoryMock
            ]
        );
    }
    
    /**
     * testExecute
     */
    public function testExecute()
    {
        $rootCatId = 2;
        $categoryIds = [9, 10, 11, 68];
        $catInfo = [['entity_id' => 1274, 'id' => 1274, 'parent_id' => 1, 'name' => 'SDE root category']];
        
        
        $this->storeGroupFactoryMock->expects($this->any())->method('create')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('load')->willReturnSelf();
        $this->storeGroupMock->expects($this->any())->method('getRootCategoryId')->willReturn($rootCatId);
        
        $this->categoryFactoryMock->expects($this->any())->method('create')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getCollection')->willReturn($this->categoryCollectionMock);
        $this->categoryCollectionMock->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        
        $categoryIteratorMock = new \ArrayIterator([1 => $this->categoryMock]);
        $this->categoryCollectionMock->expects($this->any())->method('getIterator')->willReturn($categoryIteratorMock);
        $this->categoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->categoryMock->expects($this->any())->method('getId')->willReturn(3);
        $this->categoryMock->expects($this->any())->method('getName')->willReturn('Print Products');
        
        
        $this->categoryManagementInterfaceMock->expects($this->any())->method('getTree')
                    ->willReturn($this->categoryTreeInterfaceMock);
        
        $this->categoryTreeInterfaceMock->method('getChildrenData')
                ->withConsecutive([], [])
                    ->willReturnOnConsecutiveCalls([$this->categoryTreeInterfaceMock],
                    [$this->categoryTreeInterfaceMock]);
        
        $this->categoryTreeInterfaceMock->expects($this->any())->method('getId')
                    ->willReturn(23);
                    
        $this->categoryFactoryMock->expects($this->any())->method('create')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('load')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('setId')->willReturn(null);
        $this->categoryMock->expects($this->any())->method('getName')->willReturn('Print Products');
        $this->categoryMock->expects($this->any())->method('setName')->willReturn(null);
        $this->categoryMock->expects($this->any())->method('getUrlKey')->willReturn('print-products');
        $this->categoryMock->expects($this->any())->method('setUrlKey')->willReturn(null);
        $this->categoryMock->expects($this->any())->method('save')->willReturnSelf();
        
        // move category
        $this->categoryManagementInterfaceMock->expects($this->any())->method('move')->willReturnSelf();
        
        // add permission
        $this->permissionFactoryMock->expects($this->any())->method('create')->willReturn($this->permissionMock);
        $this->permissionMock->expects($this->any())
            ->method('getCollection')->willReturn($this->categoryPermissionCollectionMock);
        $this->categoryPermissionCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->categoryPermissionCollectionMock->expects($this->any())->method('getSize')->willReturn(10);
        $this->categoryPermissionCollectionMock->expects($this->any())->method('getSize')->willReturn(10);
        
        $permissionIteratorMock = new \ArrayIterator([1 => $this->permissionMock]);
        $this->categoryPermissionCollectionMock->expects($this->any())
            ->method('getIterator')->willReturn($permissionIteratorMock);
        $this->permissionMock->expects($this->any())->method('getData')->willReturn([]);
        $this->permissionMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->permissionMock->expects($this->any())->method('save')->willReturnSelf();
        
        // assign product
        $this->collectionFactoryMock->expects($this->any())->method('create')->willReturn($this->collectionMock);
        $this->collectionMock->expects($this->any())->method('addCategoriesFilter')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('getData')->willReturn($catInfo);
        
        $this->productFactoryMock->expects($this->any())->method('create')->willReturn($this->productMock);
        $this->productMock->expects($this->any())->method('load')->willReturnSelf();
        $this->productMock->expects($this->any())->method('getCategoryIds')->willReturn($categoryIds);
        
        $this->productMock->expects($this->any())->method('setCategoryIds')->willReturnSelf();
        $this->productMock->expects($this->any())->method('save')->willReturnSelf();
        
        $this->assertNull($this->selfRegCategoryMock->execute());
    }
    
    public function testExecuteWithException()
    {
        $rootCatId = 2;
        $categoryIds = [9, 10, 11, 68];
        $this->storeGroupFactoryMock->expects($this->any())->method('create')->willReturn($this->storeGroupMock);
        $exception = new \Exception();
        $this->storeGroupMock->expects($this->any())->method('load')->willThrowException($exception);
        $this->assertNull($this->selfRegCategoryMock->execute());
    }
}
