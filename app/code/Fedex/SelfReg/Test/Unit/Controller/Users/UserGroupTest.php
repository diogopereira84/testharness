<?php

namespace Fedex\SelfReg\Test\Unit\Controller\Users;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\SelfReg\Controller\Users\UserGroup;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\SelfReg\Model\UserGroupsFactory;
use Fedex\SelfReg\Model\UserGroups;
use Fedex\SelfReg\Model\UserGroupsPermissionFactory;
use Fedex\SelfReg\Model\UserGroupsPermission;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteria;

class UserGroupTest extends TestCase
{

    protected $context;
    protected $jsonFactory;
    protected $userGroupsPermissionFactoryMock;
    protected $userGroupsPermissionMock;
    protected $userGroupsFactoryMock;
    protected $userGroupsMock;
    protected $customerRepositoryInterfaceMock;
    protected $loggerInterfaceMock;
    protected $requestInterFaceMock;
    protected $customerInterFaceMock;
    protected $jsonMock;
    protected $userGroup;
    protected $groupRepositoryInterfaceMock;
    protected $customerFactoryMock;
    protected $customerMock;
    protected $customerCollectionMock;
    protected $filterBuilderMock;
    protected $searchCriteriaBuilderMock;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->userGroupsPermissionFactoryMock = $this->getMockBuilder(UserGroupsPermissionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->userGroupsPermissionMock = $this->getMockBuilder(UserGroupsPermission::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'addFieldToFilter', 'getFirstItem', 'getOrderApproval'])
            ->getMock();
        $this->userGroupsFactoryMock = $this->getMockBuilder(UserGroupsFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->userGroupsMock = $this->getMockBuilder(UserGroups::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getId', 'getGroupName', 'getGroupType'])
            ->getMock();
        $this->customerRepositoryInterfaceMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMockForAbstractClass();
        $this->loggerInterfaceMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMockForAbstractClass();

        $this->requestInterFaceMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMockForAbstractClass();
        $this->customerInterFaceMock =  $this->getMockBuilder(CustomerInterface::class)
            ->setMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->jsonMock =  $this->getMockBuilder(Json::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->groupRepositoryInterfaceMock = $this->getMockBuilder(GroupRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getById'])
            ->addMethods(['getCode', 'getId'])
            ->getMockForAbstractClass();
        $this->customerFactoryMock = $this->getMockBuilder(CustomerFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCollection', 'addFieldToFilter'])
            ->getMock();
        $this->customerCollectionMock = $this->createMock(Collection::class);
        $this->filterBuilderMock = $this->getMockBuilder(FilterBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setField', 'setValue', 'setConditionType', 'create'])
            ->getMock();
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFilters', 'create'])
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->userGroup = $objectManager->getObject(
            UserGroup::class,
            [
                'context' => $this->context,
                'resultJsonFactory' => $this->jsonFactory,
                'userGroupsPermissionFactory' => $this->userGroupsPermissionFactoryMock,
                'usergroupFactory' => $this->userGroupsFactoryMock,
                'customerRepository' => $this->customerRepositoryInterfaceMock,
                'logger' => $this->loggerInterfaceMock,
                'groupRepositoryInterface' => $this->groupRepositoryInterfaceMock,
                'customerFactory' => $this->customerFactoryMock,
                'filterBuilder' => $this->filterBuilderMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock
            ]
        );
    }

    /**
     * Test Case for Exectue Method With Post Data
     * 
     * @dataProvider executeDataProvider
    */
    public function testExecute($params)
    {
        $data = ["output" => $params];
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->context->expects($this->any())->method('getRequest')->willReturn($this->requestInterFaceMock);
        $this->requestInterFaceMock->expects($this->any())->method('getParam')->willReturn($params);

        $this->testEditUserGroup();
        $this->testEditCustomerGroup();

        $this->jsonMock->expects($this->any())->method('setData')->willReturn($data);
        $this->userGroup->execute();
        $this->assertNotNull($this->userGroup->execute());

    }

    public function testEditUserGroup()
    {
        $orderApprover = '1, 2';
        $filterMock = $this->createMock(Filter::class);
        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $searchResultMock = $this->createMock(\Magento\Customer\Api\Data\CustomerSearchResultsInterface::class);

        $this->userGroupsFactoryMock->expects($this->any())->method('create')->willReturn($this->userGroupsMock);
        $this->userGroupsMock->expects($this->any())->method('load')->willReturnSelf();
        $this->userGroupsMock->expects($this->any())->method('getId')->willReturn(123);
        $this->userGroupsMock->expects($this->any())->method('getGroupName')->willReturn('test');
        $this->userGroupsMock->expects($this->any())->method('getGroupType')->willReturn('test');
        $this->userGroupsPermissionFactoryMock->expects($this->any())->method('create')->willReturn($this->userGroupsPermissionMock);
        $this->userGroupsPermissionMock->expects($this->any())->method('getCollection')->willReturnSelf();
        $this->userGroupsPermissionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->userGroupsPermissionMock->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->userGroupsPermissionMock->expects($this->any())->method('getOrderApproval')->willReturn($orderApprover);
        $this->testCheckCustomerIsExists();
        $this->customerInterFaceMock->expects($this->any())->method('getFirstname')->willReturn('Test');
        $this->customerInterFaceMock->expects($this->any())->method('getLastname')->willReturn('User');
        $this->filterBuilderMock->expects($this->any())
            ->method('setField')
            ->willReturnSelf();
        $this->filterBuilderMock->expects($this->any())
            ->method('setValue')
            ->willReturnSelf();
        $this->filterBuilderMock->expects($this->any())
            ->method('setConditionType')
            ->willReturnSelf();
        $this->filterBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($filterMock);
            
        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('addFilters')
            ->with([$filterMock])
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($searchCriteriaMock);
        $this->customerRepositoryInterfaceMock->expects($this->any())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($searchResultMock);
        $searchResultMock->expects($this->any())
            ->method('getItems')
            ->willReturn([]);

        $this->assertNotNull($this->userGroup->editUserGroup('123'));
    }

    public function testEditCustomerGroup()
    {
        $this->groupRepositoryInterfaceMock->expects($this->any())->method('getById')->willReturnSelf();
        $this->groupRepositoryInterfaceMock->expects($this->any())->method('getCode')->willReturn('123');

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getCollection')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->groupRepositoryInterfaceMock->expects($this->any())->method('getId')->willReturn(12);

        $this->assertNotNull($this->userGroup->editCustomerGroup('123'));
    }

    /**
     * Test Case for Exectue Method With Catch
    */
    public function testExecuteCatch()
    {
        $orderApprover = '1, 2';
        $data = ["output" => "123"];
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->context->expects($this->any())->method('getRequest')->willReturn($this->requestInterFaceMock);
        $this->requestInterFaceMock->expects($this->any())->method('getParam')->willReturn(12);
        $this->userGroupsFactoryMock->expects($this->any())->method('create')->willReturn($this->userGroupsMock);
        $this->userGroupsMock->expects($this->any())->method('load')->willThrowException(new \Exception());
        $this->userGroup->execute();
        $this->assertNull($this->userGroup->execute());

    }

    /**
     * Test Case for Exectue Method Else
    */
    public function testExecuteElse()
    {
        $data = ["output" => "123"];
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->context->expects($this->any())->method('getRequest')->willReturn($this->requestInterFaceMock);
        $this->requestInterFaceMock->expects($this->any())->method('getParam')->willReturn(0);
        $this->jsonMock->expects($this->any())->method('setData')->willReturn($data);
        $this->userGroup->execute();
        $this->assertNotNull($this->userGroup->execute());

    }

    // Test case function to check customer exists
    public function testCheckCustomerIsExists() {
        $this->customerRepositoryInterfaceMock
        ->expects($this->any())
        ->method('getById')
        ->willReturn($this->customerInterFaceMock);
        $this->assertNotNull($this->userGroup->checkCustomerIsExists(12));
    }

    // Test case function to check customer exists catch
    public function testCheckCustomerIsExistsCatch() {
        $this->customerRepositoryInterfaceMock
        ->expects($this->any())
        ->method('getById')
        ->willThrowException(new \Exception());

        $this->loggerInterfaceMock
        ->expects($this->any())
        ->method('error')
        ->willReturnSelf();
        $this->assertEquals(false,$this->userGroup->checkCustomerIsExists(12));
    }

    public function executeDataProvider()
    {
        return [
            ['user_groups-123'],
            ['customer_group-123']
        ];
    }
}
