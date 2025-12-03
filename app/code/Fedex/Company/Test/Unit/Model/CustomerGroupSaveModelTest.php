<?php

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Customer\Model\Session;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Customer\Model\GroupFactory;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Tax\Model\ClassModel;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Fedex\Company\Model\CustomerGroupSaveModel;

class CustomerGroupSaveModelTest extends TestCase
{
    private $customerGroupSaveModel;
    private $groupInterfaceFactoryMock;
    private $groupRepositoryInterfaceMock;
    private $companyRepositoryInterfaceMock;
    private $resourceConnectionMock;
    private $loggerMock;
    private $sessionMock;
    private $taxClassRepositoryMock;
    private $searchCriteriaBuilderMock;
    private $filterBuilderMock;
    private $customerGroupFactoryMock;

    protected function setUp(): void
    {
        $this->groupInterfaceFactoryMock = $this->createMock(GroupInterfaceFactory::class);
        $this->groupRepositoryInterfaceMock = $this->createMock(GroupRepositoryInterface::class);
        $this->companyRepositoryInterfaceMock = $this->createMock(CompanyRepositoryInterface::class);
        $this->resourceConnectionMock = $this->createMock(ResourceConnection::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->sessionMock = $this->createMock(Session::class);
        $this->taxClassRepositoryMock = $this->createMock(TaxClassRepositoryInterface::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->filterBuilderMock = $this->createMock(FilterBuilder::class);
        $this->customerGroupFactoryMock = $this->createMock(GroupFactory::class);

        $this->customerGroupSaveModel = new CustomerGroupSaveModel(
            $this->groupInterfaceFactoryMock,
            $this->groupRepositoryInterfaceMock,
            $this->companyRepositoryInterfaceMock,
            $this->resourceConnectionMock,
            $this->loggerMock,
            $this->sessionMock,
            $this->taxClassRepositoryMock,
            $this->searchCriteriaBuilderMock,
            $this->filterBuilderMock,
            $this->customerGroupFactoryMock
        );
    }

    public function testSaveInCustomerGroup()
    {
        $baseGroupName = 'Base Group';
        $groupName = 'New Group';
        $parentGroupId = 1;

        $groupMock = $this->createMock(GroupInterface::class);
        $this->groupInterfaceFactoryMock->method('create')->willReturn($groupMock);

        $groupMock->expects($this->any())->method('setCode')->with($groupName);
        $groupMock->expects($this->any())->method('setTaxClassId')->with($this->anything());

        $this->groupRepositoryInterfaceMock->method('save')->willReturn($groupMock);
        $groupMock->method('getId')->willReturn(1);

        $this->testIsBaseNameExisting();

        $result = $this->customerGroupSaveModel->saveInCustomerGroup($baseGroupName, $groupName, $parentGroupId);

        $this->assertNull($result);
    }

    public function testSaveInParentCustomerGroup()
    {
        $newCustomerGroupId = 1;
        $parentGroupId = 2;

        $connectionMock = $this->createMock(AdapterInterface::class);
        $this->resourceConnectionMock->method('getConnection')->willReturn($connectionMock);

        $connectionMock->expects($this->once())->method('insert')->with(
            $this->anything(),
            $this->arrayHasKey('customer_group_id')
        );

        $this->customerGroupSaveModel->saveInParentCustomerGroup($newCustomerGroupId, $parentGroupId);
    }

    public function testGetTaxClassId()
    {
        $filterMock = $this->createMock(\Magento\Framework\Api\Filter::class);
        $this->filterBuilderMock->method('setField')->willReturnSelf();
        $this->filterBuilderMock->method('setValue')->willReturnSelf();
        $this->filterBuilderMock->method('create')->willReturn($filterMock);

        $searchCriteriaMock = $this->createMock(\Magento\Framework\Api\SearchCriteria::class);
        $this->searchCriteriaBuilderMock->method('addFilters')->willReturnSelf();
        $this->searchCriteriaBuilderMock->method('create')->willReturn($searchCriteriaMock);

        $searchResultsMock = $this->createMock(SearchResultsInterface::class);
        $this->taxClassRepositoryMock->method('getList')->willReturn($searchResultsMock);

        $classModelMock = $this->createMock(ClassModel::class);
        $classModelMock->method('getClassId')->willReturn(1);

        $searchResultsMock->method('getItems')->willReturn([$classModelMock]);

        $result = $this->customerGroupSaveModel->getTaxClassId();

        $this->assertEquals(1, $result);
    }

    public function testIsBaseNameExisting()
    {
        $baseGroupName = 'Base Group';
        $parentGroupId = 1;

        $collectionMock = $this->getMockBuilder(\Magento\Customer\Model\ResourceModel\Group\Collection::class)
            ->onlyMethods([
                'getSelect', 'getSize', 'getTable'
            ])
            ->addMethods([
                'getCollection', 'joinInner', 'where'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerGroupFactoryMock->method('create')->willReturn($collectionMock);
        $collectionMock->method('getCollection')->willReturnSelf();

        $selectMock = $this->createMock(Select::class);
        $collectionMock->method('getSelect')->willReturn($selectMock);

        $selectMock->method('joinInner')->willReturnSelf();
        $collectionMock->method('getTable')->willReturnSelf();
        $selectMock->method('where')->willReturnSelf();

        $collectionMock->method('getSize')->willReturn(1);

        $result = $this->customerGroupSaveModel->isBaseNameExisting($baseGroupName, $parentGroupId);

        $this->assertTrue($result);
    }
}
