<?php

declare(strict_types=1);

namespace Fedex\Company\Test\Unit\Controller\User;

use Exception;
use Fedex\Company\Controller\User\Save;
use Fedex\SelfReg\Api\UserGroupsRepositoryInterface;
use Fedex\SelfReg\Api\UserGroupsPermissionRepositoryInterface;
use Fedex\SelfReg\Api\Data\UserGroupsPermissionInterface;
use Fedex\SelfReg\Helper\Data;
use Fedex\SelfReg\Model\UserGroups;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Fedex\SelfReg\Model\UserGroupsFactory;
use Fedex\SelfReg\Model\UserGroupsPermission;
use Fedex\SelfReg\Model\UserGroupsPermissionFactory;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Fedex\Company\Model\CustomerGroupSaveModel;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\Filter;
use Fedex\CustomerGroup\Model\FolderPermission;

class SaveTest extends TestCase
{
    protected $usergroupMock;
    /**
     * @var Session
     */
    private $sessionMock;

    /**
     * @var JSON
     */
    private $jsonMock;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactoryMock;

    /**
     * @var RequestInterface
     */
    private $requestMock;

    /**
     * @var CustomerGroupSaveModel
     */
    private $customerGroupSaveModelMock;

    /**
     * @var CustomerInterface
     */
    private $customerMock;

    /**
     * @var CompanyInterface
     */
    private $companyDataMock;

    /**
     * @var CompanyManagementInterface
     */
    private $companyManagementMock;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepositoryInterfaceMock;

    /**
     * @var CustomerFactory
     */
    private $customerFactoryMock;

    /**
     * @var UserGroupsFactory
     */
    private $userGroupsFactoryMock;

    /**
     * @var UserGroupsPermission
     */
    private $usergroupPermissionMock;

    /**
     * @var UserGroupsPermissionFactory
     */
    private $userGroupsPermissionFactoryMock;

    /**
     * @var UserGroupsRepositoryInterface
     */
    private $userGroupsRepositoryMock;

    /**
     * @var UserGroupsPermissionRepositoryInterface
     */
    private $userGroupsPermissionRepositoryMock;

    /**
     * @var Data
     */
    private $dataMock;

    /**
     * @var LoggerInterface
     */
    private $loggerMock;

    /**
     * @var Save
     */
    private $saveController;

    /**
     * @var Customer
     */
    private $customerEntityMock;

    /**
     * @var FilterBuilder
     */
    private $filterBuilderMock;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var FolderPermission
     */
    private $folderPermissionMock;

    /**
     * @var Filter
     */
    private $filterMock;

    protected function setUp(): void
    {
        $this->sessionMock = $this->getMockBuilder(Session::class)
                                ->setMethods([
                                    'getCustomer',
                                ])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
                                ->setMethods([
                                    'getParams',
                                ])
                                ->disableOriginalConstructor()
                                ->getMockForAbstractClass();

        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
                                            ->setMethods([
                                                'create',
                                                'setData',
                                            ])
                                            ->disableOriginalConstructor()
                                            ->getMock();

        $this->companyManagementMock = $this->getMockBuilder(CompanyManagementInterface::class)
                                            ->setMethods([
                                                'getByCustomerId',
                                            ])
                                            ->disableOriginalConstructor()
                                            ->getMockForAbstractClass();

        $this->userGroupsFactoryMock = $this->getMockBuilder(UserGroupsFactory::class)
                                        ->setMethods([
                                            'create',
                                            'getId',
                                            'getGroupName',
                                            'getGroupType',
                                            'setGroupName',
                                            'setGroupType',
                                        ])
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->customerRepositoryInterfaceMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
                                        ->setMethods([
                                            'getById'
                                        ])
                                        ->disableOriginalConstructor()
                                        ->getMockForAbstractClass();

        $this->customerFactoryMock = $this->getMockBuilder(CustomerFactory::class)
                                        ->setMethods([
                                            'create',
                                            'setWebsiteId',
                                            'setGroupId',
                                            'save'
                                        ])
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->userGroupsRepositoryMock = $this->getMockBuilder(UserGroupsRepositoryInterface::class)
                                            ->setMethods([
                                                'get',
                                                'save',
                                                'setGroupName',
                                                'setGroupType',
                                                'getList'
                                            ])
                                            ->disableOriginalConstructor()
                                            ->getMockForAbstractClass();

        $this->userGroupsPermissionFactoryMock = $this->getMockBuilder(UserGroupsPermissionFactory::class)
                                                    ->setMethods([
                                                        'create',
                                                        'getCollection',
                                                        'addFieldToFilter',
                                                        'getFirstItem',
                                                    ])
                                                    ->disableOriginalConstructor()
                                                    ->getMock();

        $this->userGroupsPermissionRepositoryMock = $this->getMockBuilder(UserGroupsPermissionRepositoryInterface::class)
                                                    ->setMethods([
                                                        'save',
                                                        'getByGroupId',
                                                        'delete',
                                                        'deleteByUserGroupInfo'
                                                    ])
                                                    ->disableOriginalConstructor()
                                                    ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
                                ->setMethods([
                                    'error',
                                ])
                                ->disableOriginalConstructor()
                                ->getMockForAbstractClass();

        $this->usergroupMock = $this->getMockBuilder(UserGroups::class)
                                    ->setMethods([
                                        'getGroupName',
                                        'getGroupType',
                                        'getId',
                                    ])
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->usergroupPermissionMock = $this->getMockBuilder(UserGroupsPermission::class)
                                            ->setMethods([
                                                'setGroupId',
                                                'getOrderApproval',
                                            ])
                                            ->disableOriginalConstructor()
                                            ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
                            ->setMethods([
                                'setData',
                            ])
                            ->disableOriginalConstructor()
                            ->getMock();

        $this->customerMock = $this->getMockBuilder(CustomerInterface::class)
                                ->setMethods([
                                    'getId',
                                ])
                                ->disableOriginalConstructor()
                                ->getMockForAbstractClass();

        $this->companyDataMock = $this->getMockBuilder(CompanyInterface::class)
                                    ->setMethods([
                                        'getId', 'getCompanyUrlExtention'
                                    ])
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();

        $this->dataMock = $this->getMockBuilder(Data::class)
                            ->setMethods([
                                'setPermissions',
                                'deletePermission',
                                'checkIfCustomerIsOrderApprovar',
                            ])
                            ->disableOriginalConstructor()
                            ->getMock();

        $this->customerGroupSaveModelMock = $this->getMockBuilder(CustomerGroupSaveModel::class)
                            ->disableOriginalConstructor()
                            ->getMock();
                            
        $this->customerEntityMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filterBuilderMock = $this->getMockBuilder(FilterBuilder::class)
            ->onlyMethods([
                'setField', 'setConditionType', 'setValue', 'create'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->folderPermissionMock = $this->getMockBuilder(FolderPermission::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filterMock = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->saveController = new Save(
            $this->sessionMock,
            $this->resultJsonFactoryMock,
            $this->requestMock,
            $this->companyManagementMock,
            $this->userGroupsFactoryMock,
            $this->userGroupsPermissionFactoryMock,
            $this->customerRepositoryInterfaceMock,
            $this->customerFactoryMock,
            $this->userGroupsRepositoryMock,
            $this->userGroupsPermissionRepositoryMock,
            $this->customerGroupSaveModelMock,
            $this->loggerMock,
            $this->dataMock,
            $this->customerEntityMock,
            $this->filterBuilderMock,
            $this->searchCriteriaBuilderMock,
            $this->folderPermissionMock
        );
    }

    public function testExecute()
    {
        $customerId = 1;
        $companyId = 123;
        $postData = [
            'groupType' => UserGroupsPermissionInterface::ORDER_APPROVAL,
        ];
        $groupType = 'order_approval';
        $companyUrlExt = '<jjstest> ';
        $parentGroupId = 1;
    
        $this->customerMock
            ->method('getId')
            ->willReturn($customerId);

        $this->companyManagementMock
            ->method('getByCustomerId')
            ->willReturn($this->companyDataMock);

        $this->companyDataMock
            ->method('getId')
            ->willReturn($companyId);

        $this->companyDataMock
            ->method('getCompanyUrlExtention')
            ->willReturn('jjstest');

        $this->requestMock
            ->method('getParams')
            ->willReturn($postData);

        $this->jsonMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->resultJsonFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->jsonMock);

        $this->sessionMock
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->saveController
            ->save($postData, $companyId, $groupType, $companyUrlExt, $parentGroupId);

        $result = $this->saveController->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteFolderPermission()
    {
        $customerId = 1;
        $companyId = 123;
        $postData = [
            'groupType' => UserGroupsPermissionInterface::FOLDER_PERMISSIONS,
        ];
        $groupType = 'order_approval';
        $companyUrlExt = '<jjstest> ';
        $parentGroupId = 1;
    
        $this->customerMock
            ->method('getId')
            ->willReturn($customerId);

        $this->companyManagementMock
            ->method('getByCustomerId')
            ->willReturn($this->companyDataMock);

        $this->companyDataMock
            ->method('getId')
            ->willReturn($companyId);

        $this->companyDataMock
            ->method('getCompanyUrlExtention')
            ->willReturn('jjstest');

        $this->requestMock
            ->method('getParams')
            ->willReturn($postData);

        $this->jsonMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->resultJsonFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->jsonMock);

        $this->sessionMock
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->saveController
            ->save($postData, $companyId, $groupType, $companyUrlExt, $parentGroupId);

        $result = $this->saveController->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteCompanyIdNotFound()
    {
        $customerId = 1;
        $companyId = 123;
        $postData = [
            'groupType' => UserGroupsPermissionInterface::ORDER_APPROVAL,
        ];

        $groupType = 'order_approval';
        $companyUrlExt = '<jjstest> ';
        $parentGroupId = 1;
    
        $this->customerMock
            ->method('getId')
            ->willReturn($customerId);
    
        $this->companyManagementMock
            ->method('getByCustomerId')
            ->willReturn($this->companyDataMock);

       $this->companyDataMock
            ->method('getId')
            ->willThrowException(new Exception());

        $this->loggerMock
            ->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        $this->jsonMock
            ->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->resultJsonFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->jsonMock);

        $this->sessionMock
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->saveController
            ->save($postData, $companyId, $groupType, $companyUrlExt, $parentGroupId);

        $result = $this->saveController->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteCompanyNotFound()
    {
        $customerId = 1;
        $companyId = 123;
        $postData = [
            'groupType' => UserGroupsPermissionInterface::ORDER_APPROVAL,
        ];
    
        $this->customerMock
            ->method('getId')
            ->willReturn($customerId);

       $this->companyDataMock
            ->method('getId')
            ->willReturn($companyId);

        $this->jsonMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->resultJsonFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->jsonMock);

        $result = $this->saveController->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testSaveOrderApproval()
    {
        $postData = [
            'groupId' => 1,
            'groupName' => 'Test Group',
            'groupType' => UserGroupsPermissionInterface::ORDER_APPROVAL,
            'userIds' => '1,2,3',
            'orderApprovers' => '4,5,6'
        ];
        $companyId = 123;
        $groupType = 'order_approval';
        $companyUrlExt = '<jjstest> ';
        $parentGroupId = 1;

        $this->usergroupMock
            ->expects($this->any())
            ->method('getGroupName')
            ->willReturn('Test Group');

        $this->usergroupMock
            ->expects($this->any())
            ->method('getGroupType')
            ->willReturn(UserGroupsPermissionInterface::ORDER_APPROVAL);

        $this->usergroupMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->userGroupsFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->usergroupMock);

        $this->userGroupsRepositoryMock
            ->expects($this->any())
            ->method('get')
            ->willReturn($this->usergroupMock);

        $this->userGroupsRepositoryMock
            ->expects($this->any())
            ->method('save')
            ->willReturn($this->usergroupMock);
    
        $this->userGroupsPermissionRepositoryMock
            ->expects($this->any())
            ->method('getByGroupId')
            ->willReturn([$this->usergroupPermissionMock]);

        $this->userGroupsPermissionRepositoryMock
            ->expects($this->any())
            ->method('delete')
            ->willReturn(1);

        $this->userGroupsPermissionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->usergroupPermissionMock);
        
        $this->userGroupsPermissionFactoryMock
            ->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->usergroupPermissionMock);
        
        $this->userGroupsPermissionFactoryMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->usergroupPermissionMock);
        
        $this->userGroupsPermissionFactoryMock
            ->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->usergroupPermissionMock);

        $this->usergroupPermissionMock
            ->expects($this->any())
            ->method('getOrderApproval')
            ->willReturn('1,2,3');

        $this->userGroupsPermissionRepositoryMock
            ->expects($this->any())
            ->method('deleteByUserGroupInfo');

        $this->userGroupsPermissionRepositoryMock
            ->expects($this->any())
            ->method('save')
            ->willReturn($this->usergroupPermissionMock);

        $this->dataMock
            ->expects($this->any())
            ->method('checkIfCustomerIsOrderApprovar')
            ->willReturn(0);

        $this->dataMock
            ->expects($this->any())
            ->method('deletePermission');

        $this->jsonMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->resultJsonFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->jsonMock);

        $this->testIsUserGroupUnique();
    
        $result = $this->saveController
                ->save($postData, $companyId, $groupType, $companyUrlExt, $parentGroupId);
    
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testSaveOrderApprovalCatch()
    {
        $postData = [
            'groupId' => 1,
            'groupName' => 'Test Group',
            'groupType' => UserGroupsPermissionInterface::ORDER_APPROVAL,
            'userIds' => '1,2,3',
            'orderApprovers' => '4,5,6'
        ];
        $companyId = 123;
        $groupType = 'order_approval';
        $companyUrlExt = '<jjstest> ';
        $parentGroupId = 1;

        $this->testIsUserGroupUnique();

        $this->usergroupMock
            ->expects($this->any())
            ->method('getGroupName')
            ->willReturn('Test Group');

        $this->usergroupMock
            ->expects($this->any())
            ->method('getGroupType')
            ->willReturn(UserGroupsPermissionInterface::ORDER_APPROVAL);

        $this->usergroupMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->userGroupsFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->usergroupMock);

        $this->userGroupsRepositoryMock
            ->expects($this->any())
            ->method('get')
            ->willReturn($this->usergroupMock);

        $this->userGroupsRepositoryMock
            ->expects($this->any())
            ->method('save')
            ->willReturn($this->usergroupMock);
    
        $this->userGroupsPermissionRepositoryMock
            ->expects($this->any())
            ->method('getByGroupId')
            ->willReturn([]);

        $this->userGroupsPermissionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->usergroupPermissionMock);

        $this->userGroupsPermissionRepositoryMock
            ->expects($this->any())
            ->method('save')
            ->willThrowException(new Exception());

        $this->loggerMock
            ->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        $this->resultJsonFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->jsonMock);
    
        $result = $this->saveController
                ->save($postData, $companyId, $groupType, $companyUrlExt, $parentGroupId);
    
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testSaveValueInEnhanceUserRole() {
        $orderApprover = [1,2];
        $this->dataMock->expects($this->any())
        ->method('setPermissions')
        ->willReturnSelf();
        $this->saveController->saveValueInEnhanceUserRole($orderApprover,3);
    }

    public function testIsUserGroupUnique()
    {
        $this->filterBuilderMock->expects($this->any())
            ->method('setField')
            ->willReturnSelf();
        $this->filterBuilderMock->expects($this->any())
            ->method('setConditionType')
            ->willReturnSelf();
        $this->filterBuilderMock->expects($this->any())
            ->method('setValue')
            ->willReturnSelf();
        $this->filterBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($this->filterMock);

        $searchCriteriaMock = $this->createMock(\Magento\Framework\Api\SearchCriteria::class);
        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('addFilters')
            ->with([$this->filterMock])
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($searchCriteriaMock);
            
        $groupListMock = $this->getMockBuilder(\Fedex\SelfReg\Api\Data\UserGroupsSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTotalCount'])
            ->getMockForAbstractClass();

        $this->userGroupsRepositoryMock->expects($this->any())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($groupListMock);

        $groupListMock->expects($this->any())->method('getTotalCount')
            ->willReturn(1);

        $this->assertFalse($this->saveController->isUserGroupUnique('Test Group'));
    }
}
