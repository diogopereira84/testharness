<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Controller;

use Exception;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Action\Context;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Fedex\CatalogMvp\Controller\Index\SearchCategoryByName;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\App\RequestInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Class SearchCategoryByNameTest
 *
 */
class SearchCategoryByNameTest extends TestCase
{
    protected $requestMock;
    protected $resultFactoryMock;
    protected $categoryMock;
    protected $catalogMvpHelperMock;
    protected $categoryCollectionMock;
    protected $resultMock;
    /**
     * @var (\Fedex\CatalogMvp\Test\Unit\Controller\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $subcategoriesMock;
    protected $toggleConfigMock;
    protected $SearchCategoryByName;
    public const HAS_CHILDREN = 1;
    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var CategoryFactory|MockObject
     */
    protected $categoryFactoryMock;

    /**
     * @var JsonFactory|MockObject
     */
    protected $resultJsonFactoryMock;

    /**
     * @var CategoryRepository|MockObject
     */
    protected $categoryRepositoryMock;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactory;
    protected $requestInterMock;

    protected function setUp(): void
    {
        $this->categoryFactoryMock = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'load', 'assignProductToCategory','getCollection'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestInterMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        
        $this->categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllChildren','getName','getId','hasChildren','getChildrenCategories','getLevel'])
            ->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);

        $this->categoryRepositoryMock = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get','getChildrenCategories'])
            ->getMock();

        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setData'])
            ->getMock();
        
        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCatalogBreakpointToggle'])
            ->getMock();

        $this->categoryCollectionMock = $this->getMockBuilder(CategoryCollection::class)
            ->setMethods(['addAttributeToSelect','addAttributeToFilter','setPageSize','getIterator','getName','hasChildren','getId','getLevel'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultMock = $this->createMock(Raw::class);
        $this->subcategoriesMock = $this->getMockBuilder(Collection::class)
            ->setMethods(['addAttributeToSort', 'addAttributeToSelect', 'addFieldToFilter','getIterator'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();         
                

        $objectManagerHelper = new ObjectManager($this);
        $this->SearchCategoryByName = $objectManagerHelper->getObject(
            SearchCategoryByName::class,
            [
                'categoryRepository' => $this->categoryRepositoryMock,
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'categoryFactory' => $this->categoryFactoryMock,
                'category' => $this->categoryMock,
                'collection' => $this->categoryCollectionMock,
                'context' => $this->contextMock,
                'resultFactory' => $this->resultFactoryMock,
                'toggleConfig'=>$this->toggleConfigMock,
                'catalogMvpHelper' => $this->catalogMvpHelperMock,
		'request' => $this->requestInterMock
            ]
        );
    }

    /**
     * @test testExecute
     */
    public function testExecute()
    {
        $this->requestInterMock->expects($this->exactly(2))->method('getParam')
        ->withConsecutive(['search_category_by_name'], ['currentCategoryId'])
        ->willReturnOnConsecutiveCalls('test', 12);
        $this->categoryFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->categoryFactoryMock->expects($this->any())->method('load')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getAllChildren')->willReturn('12','15');
        $this->categoryFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->categoryFactoryMock->expects($this->any())
        ->method('getCollection')->willReturn($this->categoryCollectionMock);
        $this->categoryCollectionMock->expects($this->any())
        ->method('addAttributeToSelect')->willReturn($this->categoryCollectionMock);
        $this->categoryCollectionMock->expects($this->any())
        ->method('addAttributeToFilter')->willReturn($this->categoryCollectionMock);
        $this->categoryCollectionMock->expects($this->any())
        ->method('addAttributeToFilter')->willReturn($this->categoryCollectionMock);
        $this->categoryCollectionMock->expects($this->any())
        ->method('setPageSize')->willReturnSelf();
        $this->catalogMvpHelperMock->expects($this->any())->method('getCatalogBreakpointToggle')->willReturn(false);
        $categortIterator = new \ArrayIterator([0 => $this->categoryCollectionMock]);
        $this->categoryCollectionMock->expects($this->any())->method('getIterator')->willReturn($categortIterator);
        $this->categoryCollectionMock->expects($this->any())->method('getName')->willReturn('Sample Category');
        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_RAW)
            ->willReturn($this->resultMock);
        $this->resultMock->expects($this->any())
            ->method('setContents')
            ->willReturnSelf();
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);  
        $this->categoryRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->categoryMock);       
        $this->SearchCategoryByName->execute();
    }

    public function testExecuteforAllCat()
    {
        $this->requestInterMock->expects($this->exactly(2))->method('getParam')
        ->withConsecutive(['search_category_by_name'], ['currentCategoryId'])
        ->willReturnOnConsecutiveCalls('test', 12);
        $this->categoryFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->categoryFactoryMock->expects($this->any())->method('load')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getAllChildren')->willReturn('12','15');
        $this->categoryFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->categoryFactoryMock->expects($this->any())
        ->method('getCollection')->willReturn($this->categoryCollectionMock);
        $this->categoryCollectionMock->expects($this->any())
        ->method('addAttributeToSelect')->willReturn($this->categoryCollectionMock);
        $this->categoryCollectionMock->expects($this->any())
        ->method('addAttributeToFilter')->willReturn($this->categoryCollectionMock);
        $this->categoryCollectionMock->expects($this->any())
        ->method('addAttributeToFilter')->willReturn($this->categoryCollectionMock);
        $this->categoryCollectionMock->expects($this->any())
        ->method('setPageSize')->willReturnSelf();
        $this->catalogMvpHelperMock->expects($this->any())->method('getCatalogBreakpointToggle')->willReturn(false);
        $categortIterator = new \ArrayIterator([0 => $this->categoryCollectionMock]);
        $this->categoryCollectionMock->expects($this->any())->method('getIterator')->willReturn($categortIterator);
        $this->categoryCollectionMock->expects($this->any())->method('getName')->willReturn('Sample Category');
        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_RAW)
            ->willReturn($this->resultMock);
        $this->resultMock->expects($this->any())
            ->method('setContents')
            ->willReturnSelf();
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);  
        $this->categoryRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->categoryMock);       
        $this->SearchCategoryByName->execute();
    }

    /**
     * @test testExecute
     */
    public function testExecuteElse()
    {
        $this->requestInterMock->expects($this->exactly(2))->method('getParam')
        ->withConsecutive(['search_category_by_name'], ['currentCategoryId'])
        ->willReturnOnConsecutiveCalls('', 12);
        $this->categoryFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->categoryFactoryMock->expects($this->any())->method('load')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getAllChildren')->willReturn('12','15');
        $this->categoryFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->categoryFactoryMock->expects($this->any())
        ->method('getCollection')->willReturn($this->categoryCollectionMock);
        $this->categoryCollectionMock->expects($this->any())
        ->method('addAttributeToSelect')->willReturn($this->categoryCollectionMock);
        $this->categoryCollectionMock->expects($this->any())
        ->method('addAttributeToFilter')->willReturn($this->categoryCollectionMock);
        $this->categoryCollectionMock->expects($this->any())
        ->method('addAttributeToFilter')->willReturn($this->categoryCollectionMock);
        $this->categoryCollectionMock->expects($this->any())
        ->method('setPageSize')->willReturnSelf();
        $categortIterator = new \ArrayIterator([0 => $this->categoryCollectionMock]);
        $this->categoryCollectionMock->expects($this->any())->method('getIterator')->willReturn($categortIterator);
        $this->categoryCollectionMock->expects($this->any())->method('getName')->willReturn('Sample Category');
        $this->testNoValueSearched();
        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_RAW)
            ->willReturn($this->resultMock);
        $this->resultMock->expects($this->any())
            ->method('setContents')
            ->willReturnSelf();
        $this->SearchCategoryByName->execute();
    }

     /**
     * @test testNoValueSearched
     */
    public function testNoValueSearched()
    {
        $this->categoryRepositoryMock->expects($this->any())->method('get')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getChildrenCategories')
            ->willReturn($this->categoryCollectionMock);
        $this->catalogMvpHelperMock->expects($this->any())->method('getCatalogBreakpointToggle')->willReturn(false);
        $categortIterator = new \ArrayIterator([0 => $this->categoryCollectionMock]);
        $this->categoryCollectionMock->expects($this->any())->method('getIterator')->willReturn($categortIterator);
        $this->categoryCollectionMock->expects($this->any())->method('hasChildren')->willReturn(self::HAS_CHILDREN);
        $this->categoryCollectionMock->expects($this->any())->method('getId')->willReturn(1);
        $this->SearchCategoryByName->noValueSearched(9,'html');
    }
    /**
     * @test testNoValueSearched
     */
    public function testNoValueSearchedNochild()
    {
        $this->categoryRepositoryMock->expects($this->any())->method('get')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getChildrenCategories')
            ->willReturn($this->categoryCollectionMock);
        $categortIterator = new \ArrayIterator([0 => $this->categoryCollectionMock]);
        $this->categoryCollectionMock->expects($this->any())->method('getIterator')->willReturn($categortIterator);
        $this->categoryCollectionMock->expects($this->exactly(2))->method('hasChildren')->willReturnOnConsecutiveCalls(0, 0);
        $this->categoryCollectionMock->expects($this->any())->method('getId')->willReturn(1);
        $this->SearchCategoryByName->noValueSearched(9,'html');
    }
   }
