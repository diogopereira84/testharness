<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\Catalog\Test\Unit\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Request\Http;
use Magento\Company\Model\CompanyFactory;
use Magento\Company\Model\Company;
use Magento\Company\Model\ResourceModel\Company\Collection as CompanyCollection;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory as SharedCatalogCollectionFactory;
use Psr\Log\LoggerInterface;
use Fedex\Catalog\Controller\Adminhtml\Index\CategorySharedCatalog;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;

class CategorySharedCatalogTest extends TestCase
{
    protected $contextMock;
    protected $resultJsonFactoryMock;
    protected $requestMock;
    protected $companyFactoryMock;
    protected $companyMock;
    protected $companyCollectionMock;
    protected $sharedCatalogCollectionFactory;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $categoryRepositoryMock;
    protected $category;
    protected $categorySharedCatalogMock;
    public const CUSTOMER_GROUP_ID = 12;
    public const SHARED_CATALOG_ID = 9;

	protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['getRequest'])
                                ->getMock();
        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData', 'create'])
            ->getMock();
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyFactoryMock = $this->getMockBuilder(CompanyFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','load'])
            ->getMock();
        $this->companyMock = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection'])
            ->getMock();
        $this->companyCollectionMock = $this->getMockBuilder(CompanyCollection::class)
            ->DisableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getId', 'getFirstItem', 'getCustomerGroupId'])
            ->getMock();
        $this->sharedCatalogCollectionFactory = $this->getMockBuilder(SharedCatalogCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'addFieldToFilter', 'getIterator', 'getSize', 'getFirstItem', 'getId'])
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['info', 'error'])
            ->getMockForAbstractClass();
        $this->categoryRepositoryMock = $this
            ->getMockBuilder(CategoryRepositoryInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPath'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->categorySharedCatalogMock = $objectManagerHelper->getObject(
            CategorySharedCatalog::class,
            [
                'context' => $this->contextMock,
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'request' => $this->requestMock,
                'companyFactory' => $this->companyFactoryMock,
                'collectionFactory' => $this->sharedCatalogCollectionFactory,
                'logger' => $this->loggerMock,
                'categoryRepository' => $this->categoryRepositoryMock
            ]
        );
    }

    /**
     * testExecute
     *
     */
    public function testExecute()
    {
        $expectedData = '{"shared_catalog_id":9}';
        $categoryId = 32;
        $categoryPath = '1/487/86';

        $this->contextMock
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->requestMock->expects($this->once())->method('getParam')->with('category_id')->willReturn($categoryId);
        $this->category->expects($this->any())->method('getPath')->willReturn($categoryPath);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($this->category);
        $this->companyFactoryMock->expects($this->once())->method('create')->willReturn($this->companyMock);
        $this->companyMock->expects($this->once())->method('getCollection')->willReturn($this->companyCollectionMock);
        $this->companyCollectionMock->expects($this->once())
            ->method('addFieldToFilter')->willReturn($this->companyCollectionMock);
        $this->companyCollectionMock->expects($this->once())
            ->method('getFirstItem')->willReturn($this->companyCollectionMock);
        $this->companyCollectionMock->expects($this->once())->method('getId')->willReturn(2);
        $this->companyCollectionMock->expects($this->once())
            ->method('getCustomerGroupId')->willReturn(self::CUSTOMER_GROUP_ID);
        $this->testGetSharedCatalog();

        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->resultJsonFactoryMock->expects($this->any())->method('setData')->willReturn($expectedData);
        $this->assertEquals($expectedData, $this->categorySharedCatalogMock->execute());
    }

    /**
     * testGetSharedCatalog
     */
    public function testGetSharedCatalog()
    {
        $this->sharedCatalogCollectionFactory->expects($this->any())->method('create')
            ->willReturnSelf();

        $this->sharedCatalogCollectionFactory->expects($this->any())->method('addFieldToFilter')
            ->willReturnSelf();
        $this->sharedCatalogCollectionFactory->expects($this->any())->method('getSize')->willReturn(1);
        $this->sharedCatalogCollectionFactory->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->sharedCatalogCollectionFactory->expects($this->any())->method('getId')
            ->willReturn(self::SHARED_CATALOG_ID);

        $this->assertEquals(
            self::SHARED_CATALOG_ID,
            $this->categorySharedCatalogMock->getSharedCatalog(self::CUSTOMER_GROUP_ID)
        );
    }
}