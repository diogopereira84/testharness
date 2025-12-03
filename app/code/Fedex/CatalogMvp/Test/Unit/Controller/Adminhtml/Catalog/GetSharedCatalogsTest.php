<?php
declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Controller\Adminhtml\Catalog;

use Fedex\CatalogMvp\Controller\Adminhtml\Catalog\GetSharedCatalogs;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory as SharedCatalogCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Company\Model\CompanyFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Store\Model\GroupFactory;
use Fedex\AccountValidation\Model\AccountValidation;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection as SharedCatalogCollection;
use Magento\Framework\DataObject;

class GetSharedCatalogsTest extends TestCase
{
    /** @var GetSharedCatalogs */
    private GetSharedCatalogs $controller;

    /** @var Context|\PHPUnit\Framework\MockObject\MockObject */
    private $contextMock;

    /** @var JsonFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $resultJsonFactoryMock;

    /** @var Json|\PHPUnit\Framework\MockObject\MockObject */
    private $resultJsonMock;

    /** @var RequestInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $requestMock;

    /** @var SharedCatalogCollectionFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $sharedCatalogCollectionFactoryMock;

    /** @var CategoryCollectionFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryCollectionFactoryMock;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $loggerMock;

    /** @var CompanyFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $companyFactoryMock;

    /** @var CategoryFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryFactoryMock;

    /** @var GroupFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $groupFactoryMock;

    /** @var AccountValidation|\PHPUnit\Framework\MockObject\MockObject */
    private $accountValidationMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->resultJsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->resultJsonMock = $this->createMock(Json::class);
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->sharedCatalogCollectionFactoryMock = $this->createMock(SharedCatalogCollectionFactory::class);
        $this->categoryCollectionFactoryMock = $this->createMock(CategoryCollectionFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->companyFactoryMock = $this->createMock(CompanyFactory::class);
        $this->categoryFactoryMock = $this->createMock(CategoryFactory::class);
        $this->groupFactoryMock = $this->createMock(GroupFactory::class);
        $this->accountValidationMock = $this->createMock(AccountValidation::class);

        $this->contextMock->method('getRequest')->willReturn($this->requestMock);
        $this->resultJsonFactoryMock->method('create')->willReturn($this->resultJsonMock);

        $this->controller = new GetSharedCatalogs(
            $this->contextMock,
            $this->resultJsonFactoryMock,
            $this->sharedCatalogCollectionFactoryMock,
            $this->categoryCollectionFactoryMock,
            $this->loggerMock,
            $this->companyFactoryMock,
            $this->categoryFactoryMock,
            $this->groupFactoryMock,
            $this->accountValidationMock
        );
    }

    public function testExecuteWithInvalidType(): void
    {
        $this->requestMock->method('getParam')->willReturn('invalid');
        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with(
                [
                'success' => false,
                'error' => __('Invalid type parameter.'),
                ]
            );

        $this->controller->execute();
    }
  
    public function testExecuteCatchesExceptionAndReturnsErrorJson(): void
    {
        $this->requestMock->method('getParam')->willReturn('catalogs');
        $callCount = 0;
        $resultJsonMock = $this->resultJsonMock;
        $this->resultJsonMock
            ->method('setData')
            ->willReturnCallback(
                function ($data) use (&$callCount, $resultJsonMock) {
                    $callCount++;
                    if ($callCount === 1) {
                        throw new \Exception('Test Execute Error');
                    }
                    return $resultJsonMock;
                }
            );

        $loggerCallCount = 0;
        $this->loggerMock->expects($this->exactly(2))
            ->method('error')
            ->willReturnCallback(function ($message) use (&$loggerCallCount) {
                if ($loggerCallCount === 0) {
                    $this->assertIsString($message);
                } else {
                    $this->assertStringContainsString('Test Execute Error', (string)$message);
                }
                $loggerCallCount++;
                return null;
            });

        $result = $this->controller->execute();
        $this->assertSame($this->resultJsonMock, $result);
    }

    public function testGetSharedCatalogsSuccess(): void
    {
        $collectionMock = $this->createMock(SharedCatalogCollection::class);
        $catalogMock = new DataObject(['id' => 1, 'name' => 'Test Catalog']);
        $collectionMock->method('setOrder')->willReturnSelf();
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([$catalogMock]));

        $this->sharedCatalogCollectionFactoryMock->method('create')->willReturn($collectionMock);

        $result = $this->invokeMethod($this->controller, 'getSharedCatalogs');
        $this->assertEquals([['id' => 1, 'name' => 'Test Catalog']], $result);
    }

    public function testGetSharedCatalogsException(): void
    {
        $this->sharedCatalogCollectionFactoryMock->method('create')->willThrowException(new \Exception('DB Error'));
        $this->loggerMock->expects($this->once())->method('error');

        $result = $this->invokeMethod($this->controller, 'getSharedCatalogs');
        $this->assertEquals([], $result);
    }

    public function testGetDiscountNumberReturnsNullForInvalidId(): void
    {
        $result = $this->invokeMethod($this->controller, 'getDiscountNumberBasedOnSharedCatalogId', [0]);
        $this->assertNull($result);
    }

    public function testGetDiscountNumberReturnsDiscountAccount(): void
    {
        $sharedCatalogMock = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->onlyMethods(['getId', 'getCustomerGroupId'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogMock->method('getId')->willReturn(1);
        $sharedCatalogMock->method('getCustomerGroupId')->willReturn(10);

        $sharedCatalogColBuilder = $this->getMockBuilder(
            \Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection::class
        );
        $sharedCatalogColBuilder->onlyMethods(['addFieldToFilter', 'getFirstItem']);
        $sharedCatalogColBuilder->disableOriginalConstructor();
        $sharedCatalogCollectionMock = $sharedCatalogColBuilder->getMock();
        $sharedCatalogCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $sharedCatalogCollectionMock->method('getFirstItem')->willReturn($sharedCatalogMock);

        $this->sharedCatalogCollectionFactoryMock->method('create')->willReturn($sharedCatalogCollectionMock);

        // company data object returned by the company collection
        $companyDataMock = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getData'])
            ->getMock();
        $companyDataMock->method('getId')->willReturn(123);
        $companyDataMock->method('getData')->willReturnCallback(function ($key) {
            if ($key === 'discount_account_number') {
                return '612351937';
            }
            return null;
        });

        // company collection that returns the company data object
        $companyCollectionMock = $this->getMockBuilder(\Magento\Company\Model\ResourceModel\Company\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'setPageSize', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $companyCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $companyCollectionMock->method('setPageSize')->willReturnSelf();
        $companyCollectionMock->method('getFirstItem')->willReturn($companyDataMock);

        $companyModelMock = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection'])
            ->getMock();
        $companyModelMock->method('getCollection')->willReturn($companyCollectionMock);

        $this->companyFactoryMock->method('create')->willReturn($companyModelMock);

        $result = $this->invokeMethod($this->controller, 'getDiscountNumberBasedOnSharedCatalogId', [1]);
        $this->assertEquals('612351937', $result);
    }

    public function testGetDiscountNumberReturnsNullWhenSharedCatalogHasNoId(): void
    {
        $sharedCatalogMock = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogMock->method('getId')->willReturn(null);

        $sharedCatalogCollectionMock =
        $this->getMockBuilder(\Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'setPageSize', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $sharedCatalogCollectionMock->method('setPageSize')->willReturnSelf();
        $sharedCatalogCollectionMock->method('getFirstItem')->willReturn($sharedCatalogMock);

        $this->sharedCatalogCollectionFactoryMock->method('create')->willReturn($sharedCatalogCollectionMock);

        $this->assertNull($this->invokeMethod($this->controller, 'getDiscountNumberBasedOnSharedCatalogId', [1]));
    }

    public function testGetDiscountNumberReturnsNullWhenCustomerGroupInvalid(): void
    {
        $sharedCatalogMock = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->onlyMethods(['getId', 'getCustomerGroupId'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogMock->method('getId')->willReturn(1);
        $sharedCatalogMock->method('getCustomerGroupId')->willReturn(0);

        $sharedCatalogCollectionMock =
        $this->getMockBuilder(\Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'setPageSize', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $sharedCatalogCollectionMock->method('setPageSize')->willReturnSelf();
        $sharedCatalogCollectionMock->method('getFirstItem')->willReturn($sharedCatalogMock);

        $this->sharedCatalogCollectionFactoryMock->method('create')->willReturn($sharedCatalogCollectionMock);

        $this->assertNull($this->invokeMethod($this->controller, 'getDiscountNumberBasedOnSharedCatalogId', [1]));
    }

    public function testGetDiscountNumberReturnsNullWhenCompanyNotFound(): void
    {
        $sharedCatalogMock = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->onlyMethods(['getId', 'getCustomerGroupId'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogMock->method('getId')->willReturn(1);
        $sharedCatalogMock->method('getCustomerGroupId')->willReturn(10);

        $sharedCatalogCollectionMock =
        $this->getMockBuilder(\Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'setPageSize', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $sharedCatalogCollectionMock->method('setPageSize')->willReturnSelf();
        $sharedCatalogCollectionMock->method('getFirstItem')->willReturn($sharedCatalogMock);

        $this->sharedCatalogCollectionFactoryMock->method('create')->willReturn($sharedCatalogCollectionMock);

        $companyDataMock = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $companyDataMock->method('getId')->willReturn(null);

        $companyCollectionMock = $this->getMockBuilder(\Magento\Company\Model\ResourceModel\Company\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'setPageSize', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $companyCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $companyCollectionMock->method('setPageSize')->willReturnSelf();
        $companyCollectionMock->method('getFirstItem')->willReturn($companyDataMock);

        $companyModelMock = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->onlyMethods(['getCollection'])
            ->disableOriginalConstructor()
            ->getMock();
        $companyModelMock->method('getCollection')->willReturn($companyCollectionMock);

        $this->companyFactoryMock->method('create')->willReturn($companyModelMock);

        $this->assertNull($this->invokeMethod($this->controller, 'getDiscountNumberBasedOnSharedCatalogId', [1]));
    }

    public function testGetDiscountNumberFallsBackToFedexAccount(): void
    {
        $sharedCatalogMock = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->onlyMethods(['getId', 'getCustomerGroupId'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogMock->method('getId')->willReturn(1);
        $sharedCatalogMock->method('getCustomerGroupId')->willReturn(10);

        $sharedCatalogCollectionMock =
        $this->getMockBuilder(\Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $sharedCatalogCollectionMock->method('getFirstItem')->willReturn($sharedCatalogMock);

        $this->sharedCatalogCollectionFactoryMock->method('create')->willReturn($sharedCatalogCollectionMock);

        // company data object returned by the company collection (no discount, has fedex account)
        $companyDataMock = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getData'])
            ->getMock();
        $companyDataMock->method('getId')->willReturn(123);
        $companyDataMock->method('getData')->willReturnCallback(function ($key) {
            if ($key === 'discount_account_number') {
                return null;
            }
            if ($key === 'fedex_account_number') {
                return '603977505';
            }
            return null;
        });

        $companyCollectionMock = $this->getMockBuilder(\Magento\Company\Model\ResourceModel\Company\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'setPageSize', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $companyCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $companyCollectionMock->method('setPageSize')->willReturnSelf();
        $companyCollectionMock->method('getFirstItem')->willReturn($companyDataMock);

        $companyModelMock = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection'])
            ->getMock();
        $companyModelMock->method('getCollection')->willReturn($companyCollectionMock);

        $this->companyFactoryMock->method('create')->willReturn($companyModelMock);

        $result = $this->invokeMethod($this->controller, 'getDiscountNumberBasedOnSharedCatalogId', [1]);
        $this->assertEquals('603977505', $result);
    }

    public function testGetDiscountNumberReturnsNullWhenNoAccountsPresent(): void
    {
        $sharedCatalogMock = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->onlyMethods(['getId', 'getCustomerGroupId'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogMock->method('getId')->willReturn(1);
        $sharedCatalogMock->method('getCustomerGroupId')->willReturn(10);

        $sharedCatalogCollectionMock =
        $this->getMockBuilder(\Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'setPageSize', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $sharedCatalogCollectionMock->method('setPageSize')->willReturnSelf();
        $sharedCatalogCollectionMock->method('getFirstItem')->willReturn($sharedCatalogMock);

        $this->sharedCatalogCollectionFactoryMock->method('create')->willReturn($sharedCatalogCollectionMock);

        $companyDataMock = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->onlyMethods(['getId', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $companyDataMock->method('getId')->willReturn(123);
        $companyDataMock->method('getData')->willReturnCallback(fn($k) => null);

        $companyCollectionMock = $this->getMockBuilder(\Magento\Company\Model\ResourceModel\Company\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'setPageSize', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $companyCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $companyCollectionMock->method('setPageSize')->willReturnSelf();
        $companyCollectionMock->method('getFirstItem')->willReturn($companyDataMock);

        $companyModelMock = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->onlyMethods(['getCollection'])
            ->disableOriginalConstructor()
            ->getMock();
        $companyModelMock->method('getCollection')->willReturn($companyCollectionMock);

        $this->companyFactoryMock->method('create')->willReturn($companyModelMock);

        $this->assertNull($this->invokeMethod($this->controller, 'getDiscountNumberBasedOnSharedCatalogId', [1]));
    }

    public function testGetDiscountNumberLogsAndReturnsNullOnException(): void
    {
        $this->sharedCatalogCollectionFactoryMock->method('create')->willThrowException(new \Exception('DB failure'));
        $this->loggerMock->expects($this->once())->method('error');

        $result = $this->invokeMethod($this->controller, 'getDiscountNumberBasedOnSharedCatalogId', [1]);
        $this->assertNull($result);
    }
    
    /**
     * Helper to call protected/private methods
     */
    protected function invokeMethod(object $object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    public function testGetCategoriesBySharedCatalogWithInvalidId(): void
    {
        $this->assertSame([], $this->invokeMethod($this->controller, 'getCategoriesBySharedCatalog', [0]));
    }

    public function testGetCategoriesBySharedCatalogReturnsEmptyWhenSharedCatalogNotFound(): void
    {
        $sharedCatalogMock = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogMock->method('getId')->willReturn(null);

        $sharedCatalogCollectionMock =
        $this->getMockBuilder(\Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $sharedCatalogCollectionMock->method('getFirstItem')->willReturn($sharedCatalogMock);

        $this->sharedCatalogCollectionFactoryMock->method('create')->willReturn($sharedCatalogCollectionMock);

        $this->assertSame([], $this->invokeMethod($this->controller, 'getCategoriesBySharedCatalog', [1]));
    }

    public function testGetCategoriesBySharedCatalogReturnsEmptyWhenCompanyNotFound(): void
    {
        $sharedCatalogMock = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->onlyMethods(['getId', 'getCustomerGroupId'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogMock->method('getId')->willReturn(1);
        $sharedCatalogMock->method('getCustomerGroupId')->willReturn(10);

        $sharedCatalogCollectionMock =
        $this->getMockBuilder(\Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $sharedCatalogCollectionMock->method('getFirstItem')->willReturn($sharedCatalogMock);

        $this->sharedCatalogCollectionFactoryMock->method('create')->willReturn($sharedCatalogCollectionMock);

        $companyDataMock = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $companyDataMock->method('getId')->willReturn(null);

        $companyCollectionMock = $this->getMockBuilder(\Magento\Company\Model\ResourceModel\Company\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'setPageSize', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $companyCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $companyCollectionMock->method('setPageSize')->willReturnSelf();
        $companyCollectionMock->method('getFirstItem')->willReturn($companyDataMock);

        $companyModelMock = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->onlyMethods(['getCollection'])
            ->disableOriginalConstructor()
            ->getMock();
        $companyModelMock->method('getCollection')->willReturn($companyCollectionMock);

        $this->companyFactoryMock->method('create')->willReturn($companyModelMock);

        $this->assertSame([], $this->invokeMethod($this->controller, 'getCategoriesBySharedCatalog', [1]));
    }

    public function testGetCategoriesBySharedCatalogSuccess(): void
    {
        // Shared catalog
        $sharedCatalogMock = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->onlyMethods(['getId', 'getCustomerGroupId'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogMock->method('getId')->willReturn(1);
        $sharedCatalogMock->method('getCustomerGroupId')->willReturn(10);

        $sharedCatalogCollectionMock =
        $this->getMockBuilder(\Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $sharedCatalogCollectionMock->method('getFirstItem')->willReturn($sharedCatalogMock);
        $this->sharedCatalogCollectionFactoryMock->method('create')->willReturn($sharedCatalogCollectionMock);

        $companyDataMock = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->onlyMethods(['getId'])
            ->addMethods(['getSharedCatalogId'])
            ->disableOriginalConstructor()
            ->getMock();
        $companyDataMock->method('getId')->willReturn(123);
        $companyDataMock->method('getSharedCatalogId')->willReturn(3);

        $companyCollectionMock = $this->getMockBuilder(\Magento\Company\Model\ResourceModel\Company\Collection::class)
            ->onlyMethods(['addFieldToFilter', 'setPageSize', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $companyCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $companyCollectionMock->method('setPageSize')->willReturnSelf();
        $companyCollectionMock->method('getFirstItem')->willReturn($companyDataMock);

        $companyModelMock = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->onlyMethods(['getCollection'])
            ->disableOriginalConstructor()
            ->getMock();
        $companyModelMock->method('getCollection')->willReturn($companyCollectionMock);

        $this->companyFactoryMock->method('create')->willReturn($companyModelMock);

        // Parent and child categories
        $parentCategoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->onlyMethods(['getPath', 'getId', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $parentCategoryMock->method('getPath')->willReturn('1/2/3');
        $parentCategoryMock->method('getId')->willReturn(100);
        $parentCategoryMock->method('getName')->willReturn('Parent Cat');

        $childCategoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->onlyMethods(['getParentId', 'getId', 'getName', 'getPath'])
            ->disableOriginalConstructor()
            ->getMock();
        $childCategoryMock->method('getParentId')->willReturn(100);
        $childCategoryMock->method('getId')->willReturn(200);
        $childCategoryMock->method('getName')->willReturn('Child Cat');
        $childCategoryMock->method('getPath')->willReturn('1/2/3/200');

        $categoryCollectionMock =
        $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category\Collection::class)
            ->onlyMethods(['addAttributeToSelect', 'getIterator'])
            ->disableOriginalConstructor()
            ->getMock();
        $categoryCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $categoryCollectionMock
        ->method('getIterator')->willReturn(new \ArrayIterator([$parentCategoryMock, $childCategoryMock]));

        $this->categoryCollectionFactoryMock->method('create')->willReturn($categoryCollectionMock);

        $result = $this->invokeMethod($this->controller, 'getCategoriesBySharedCatalog', [1]);

        $expected = [
            [
                'value' => 100,
                'label' => 'Parent Cat',
                'optgroup' => [
                    [
                        'value' => 200,
                        'label' => 'Child Cat',
                        'optgroup' => [],
                    ],
                ],
            ],
        ];

        $this->assertIsArray($result);
        $this->assertEquals($expected, $result);
    }

    public function testGetCategoriesBySharedCatalogLogsAndReturnsEmptyOnException(): void
    {
        $this->sharedCatalogCollectionFactoryMock->method('create')->willThrowException(new \Exception('DB failure'));
        $this->loggerMock->expects($this->once())->method('error');

        $result = $this->invokeMethod($this->controller, 'getCategoriesBySharedCatalog', [1]);
        $this->assertSame([], $result);
    }

    public function testBuildCategoryTree()
    {
        // Mock parent category
        $parentCategoryMock = $this->createMock(\Magento\Catalog\Model\Category::class);
        $parentCategoryMock->method('getId')->willReturn(1);
        $parentCategoryMock->method('getName')->willReturn('Parent Category');

        // Mock child category
        $childCategoryMock = $this->createMock(\Magento\Catalog\Model\Category::class);
        $childCategoryMock->method('getId')->willReturn(2);
        $childCategoryMock->method('getName')->willReturn('Child Category');
        $childCategoryMock->method('getParentId')->willReturn(1);

        // Mock category collection
        $categoryCollection = [$childCategoryMock];

        $result = $this->invokeMethod($this->controller, 'buildCategoryTree', [
            $parentCategoryMock,
            $categoryCollection
        ]);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['value']);
        $this->assertEquals('Parent Category', $result['label']);
        $this->assertCount(1, $result['optgroup']);
        $this->assertEquals(2, $result['optgroup'][0]['value']);
        $this->assertEquals('Child Category', $result['optgroup'][0]['label']);
    }

    public function testGetDefaultRootCategory()
    {
        $groupMock = $this->createMock(\Magento\Store\Model\Group::class);
        $groupMock->method('getRootCategoryId')->willReturn(10);

        $this->groupFactoryMock->method('create')->willReturn($groupMock);
        $groupMock->method('load')->with('ondemand', 'code')->willReturnSelf();

        $rootCategoryMock = $this->createMock(\Magento\Catalog\Model\Category::class);
        $rootCategoryMock->method('getId')->willReturn(10);
        $rootCategoryMock->method('getName')->willReturn('Root Category');

        $this->categoryFactoryMock->method('create')->willReturn($rootCategoryMock);
        $rootCategoryMock->method('load')->with(10)->willReturnSelf();

        $result = $this->invokeMethod($this->controller, 'getDefaultRootCategory');

        $this->assertIsArray($result);
        $this->assertEquals(10, $result['value']);
        $this->assertEquals('Root Category', $result['label']);
    }

    public function testCreateNewCategoryReturnsExistingCategory(): void
    {
        $parentId = 123;
        $name = 'Existing Cat';

        $groupMock = $this->createMock(\Magento\Store\Model\Group::class);
        $groupMock->method('getRootCategoryId')->willReturn(487);
        $groupMock->method('load')->with('ondemand', 'code')->willReturnSelf();
        $this->groupFactoryMock->method('create')->willReturn($groupMock);

        $rootCategoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->onlyMethods(['load', 'getId', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $rootCategoryMock->method('load')->with(487)->willReturnSelf();
        $rootCategoryMock->method('getId')->willReturn(487);
        $rootCategoryMock->method('getName')->willReturn('Root Category');

        $parentCategoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->onlyMethods(['load', 'getPath'])
            ->disableOriginalConstructor()
            ->getMock();
        $parentCategoryMock->method('load')->with($parentId)->willReturnSelf();
        $parentCategoryMock->method('getPath')->willReturn('1/2/123');

        $existingCategoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->onlyMethods(['getId', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $existingCategoryMock->method('getId')->willReturn(456);
        $existingCategoryMock->method('getName')->willReturn($name);

        $categoryCollectionMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category\Collection::class)
            ->onlyMethods(['addAttributeToFilter', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $categoryCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $categoryCollectionMock->method('getFirstItem')->willReturn($existingCategoryMock);

        $categoryModelForCollectionMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->onlyMethods(['getCollection'])
            ->disableOriginalConstructor()
            ->getMock();
        $categoryModelForCollectionMock->method('getCollection')->willReturn($categoryCollectionMock);

        $this->categoryFactoryMock->method('create')->willReturnOnConsecutiveCalls(
            $rootCategoryMock,
            $parentCategoryMock,
            $categoryModelForCollectionMock
        );

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with(
                [
                'success'     => true,
                'folder_id'   => 456,
                'folder_name' => $name,
                ]
            )->willReturnSelf();

        $result = $this->controller->createNewCategory($this->resultJsonMock, $name, $parentId);
        $this->assertSame($this->resultJsonMock, $result);
    }

    public function testCreateNewCategorySuccess(): void
    {
        $parentId = null;
        $name = 'New Category';

        $groupMock = $this->createMock(\Magento\Store\Model\Group::class);
        $groupMock->method('getRootCategoryId')->willReturn(487);
        $groupMock->method('load')->with('ondemand', 'code')->willReturnSelf();
        $this->groupFactoryMock->method('create')->willReturn($groupMock);

        $rootCategoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->onlyMethods(['load', 'getId', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $rootCategoryMock->method('load')->with(487)->willReturnSelf();
        $rootCategoryMock->method('getId')->willReturn(487);
        $rootCategoryMock->method('getName')->willReturn('Root Category');

        $parentCategoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->onlyMethods(['load', 'getPath'])
            ->disableOriginalConstructor()
            ->getMock();
        $parentCategoryMock->method('load')->with(487)->willReturnSelf();
        $parentCategoryMock->method('getPath')->willReturn('1/2/487');

        $existingCategoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $existingCategoryMock->method('getId')->willReturn(null);

        $categoryCollectionMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category\Collection::class)
            ->onlyMethods(['addAttributeToFilter', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $categoryCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $categoryCollectionMock->method('getFirstItem')->willReturn($existingCategoryMock);

        $categoryModelForCollectionMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->onlyMethods(['getCollection'])
            ->disableOriginalConstructor()
            ->getMock();
        $categoryModelForCollectionMock->method('getCollection')->willReturn($categoryCollectionMock);

        $methodsToPrepare = [
            'setName',
            'setParentId',
            'setPath',
            'setIsActive',
            'setIncludeInMenu',
            'setAttributeSetId',
            'getDefaultAttributeSetId',
            'save',
            'getId',
            'getName',
        ];
        $onlyMethods = [];
        $addMethods = [];
        foreach ($methodsToPrepare as $m) {
            if (method_exists(\Magento\Catalog\Model\Category::class, $m)) {
                $onlyMethods[] = $m;
            } else {
                $addMethods[] = $m;
            }
        }

        $builder = $this->getMockBuilder(\Magento\Catalog\Model\Category::class);
        if (!empty($onlyMethods)) {
            $builder->onlyMethods($onlyMethods);
        }
        if (!empty($addMethods)) {
            $builder->addMethods($addMethods);
        }
        $newCategoryMock = $builder->disableOriginalConstructor()->getMock();

        $setters = [
            'setName',
            'setParentId',
            'setPath',
            'setIsActive',
            'setIncludeInMenu',
            'setAttributeSetId',
            'save',
        ];

        foreach ($setters as $setter) {
            if (in_array($setter, $onlyMethods, true)
                || in_array($setter, $addMethods, true)
            ) {
                $newCategoryMock->method($setter)->willReturnSelf();
            }
        }

        $hasDefaultAttributeSetId = in_array('getDefaultAttributeSetId', $onlyMethods, true)
            || in_array('getDefaultAttributeSetId', $addMethods, true);

        if ($hasDefaultAttributeSetId) {
            $newCategoryMock->method('getDefaultAttributeSetId')->willReturn(7);
        }
        $newCategoryMock->method('getId')->willReturn(999);
        $newCategoryMock->method('getName')->willReturn($name);

        $this->categoryFactoryMock->method('create')->willReturnOnConsecutiveCalls(
            $rootCategoryMock,
            $parentCategoryMock,
            $categoryModelForCollectionMock,
            $newCategoryMock
        );

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with(
                [
                'success'     => true,
                'folder_id'   => 999,
                'folder_name' => $name,
                ]
            )->willReturnSelf();

        $result = $this->controller->createNewCategory($this->resultJsonMock, $name, $parentId);
        $this->assertSame($this->resultJsonMock, $result);
    }

    public function testCreateNewCategoryLogsAndReturnsErrorOnException(): void
    {
        $name = 'Example Test';
        $parentId = 222;

        $this->categoryFactoryMock->method('create')->willThrowException(new \Exception('DB Crash'));

        $this->loggerMock->expects($this->once())->method('error');
        $this->resultJsonMock->expects($this->once())->method('setData')->willReturnSelf();

        $result = $this->controller->createNewCategory($this->resultJsonMock, $name, $parentId);
        $this->assertSame($this->resultJsonMock, $result);
    }
}
