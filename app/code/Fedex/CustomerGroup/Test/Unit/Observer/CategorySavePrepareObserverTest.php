<?php

namespace Fedex\CustomerGroup\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\Framework\Event\ObserverInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Psr\Log\LoggerInterface;
use Fedex\CustomerGroup\Model\FolderPermission;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Fedex\CustomerGroup\Observer\CategorySavePrepareObserver;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

class CategorySavePrepareObserverTest extends TestCase
{
    protected $catalogMvpHelperMock;
    protected $folderPermission;
    protected $productCollectionFactory;
    protected $productCollection;
    protected $productMock;
    protected $observerMock;
    protected $categoryMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $categorySavePrepareObserver;
    protected function setUp(): void
    {
    	$this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'folderPermissionToggle'
            ])
            ->getMock();

        $this->folderPermission = $this->getMockBuilder(FolderPermission::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCustomerGroupIds',
                'unAssignCustomerGroupId',
                'assignCustomerGroupId'
            ])
            ->getMock();

        $this->productCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->productCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToSelect', 'addCategoriesFilter', 'load','getIterator'])
            ->getMock();

        $this->productMock = $this
            ->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getId'
            ])->getMock();

        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategory', 'getRequest', 'getPostValue'])
            ->getMock();

         $this->categoryMock = $this
            ->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getId'
            ])->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical'])
            ->getMockForAbstractClass();

    	$objectManagerHelper = new ObjectManager($this);
        $this->categorySavePrepareObserver = $objectManagerHelper->getObject(
            CategorySavePrepareObserver::class,
            [
                'catalogMvpHelper' => $this->catalogMvpHelperMock,
                'logger' => $this->loggerMock,
                'folderPermission' => $this->folderPermission,
                'productCollectionFactory' => $this->productCollectionFactory
            ]
        );
    }
    /**
     * @test testExecute
     */
    public function testExecute()
    {
    	$groupIds = [1,2];
    	$postData = ['vm_category_products'=>'{"1":0,"2":1}'];
    	$this->catalogMvpHelperMock->expects($this->any())
            ->method('folderPermissionToggle')
            ->willReturn(true);
        $this->observerMock
            ->expects($this->any())
            ->method('getCategory')
            ->willReturn($this->categoryMock);
        $this->categoryMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(2);
        $this->folderPermission
        	->expects($this->any())
        	->method('getCustomerGroupIds')
        	->willReturn($groupIds);
        $this->observerMock
            ->expects($this->any())
            ->method('getRequest')
            ->willReturnSelf();
        $this->observerMock
            ->expects($this->any())
            ->method('getPostValue')
            ->willReturn($postData);
        $this->productCollectionFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);
        $this->productCollection
            ->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturn($this->productCollection);
        $this->productCollection
            ->expects($this->any())
            ->method('addCategoriesFilter')
            ->willReturn($this->productCollection);
        $this->productCollection
             ->expects($this->any())
            ->method('load')
            ->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())->method('getIterator')
        ->willReturn(new \ArrayIterator([$this->productMock]));

        $this->productMock->expects($this->any())->method('getId')->willReturn(3);

        $this->assertNotNull($this->categorySavePrepareObserver->execute($this->observerMock));

    }
    /**
     * @test testExecute with else
     */
    public function testExecutewithElse()
    {
    	$groupIds = [1,2];
    	$postData = ['vm_category_productss'=>'{"1":0,"2":1}'];
    	$this->catalogMvpHelperMock->expects($this->any())
            ->method('folderPermissionToggle')
            ->willReturn(true);
        $this->observerMock
            ->expects($this->any())
            ->method('getCategory')
            ->willReturn($this->categoryMock);
        $this->categoryMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(2);
        $this->folderPermission
        	->expects($this->any())
        	->method('getCustomerGroupIds')
        	->willReturn($groupIds);
        $this->observerMock
            ->expects($this->any())
            ->method('getRequest')
            ->willReturnSelf();
        $this->observerMock
            ->expects($this->any())
            ->method('getPostValue')
            ->willReturn($postData);
        $this->productCollectionFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);
        $this->productCollection
            ->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturn($this->productCollection);
        $this->productCollection
            ->expects($this->any())
            ->method('addCategoriesFilter')
            ->willReturn($this->productCollection);
        $this->productCollection
             ->expects($this->any())
            ->method('load')
            ->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())->method('getIterator')
        ->willReturn(new \ArrayIterator([$this->productMock]));

        $this->productMock->expects($this->any())->method('getId')->willReturn(3);

        $this->assertNotNull($this->categorySavePrepareObserver->execute($this->observerMock));

    }

}