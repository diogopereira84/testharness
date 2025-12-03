<?php

declare(strict_types=1);

namespace Fedex\SelfReg\Test\Unit\Controller\Users;

use Fedex\SelfReg\Api\UserGroupsPermissionRepositoryInterface;
use Fedex\SelfReg\Controller\Users\CheckDupes;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Framework\Api\Filter;
use Fedex\SelfReg\Api\UserGroupsRepositoryInterface;
use Fedex\SelfReg\Api\Data\UserGroupsInterface;

class CheckDupesTest extends TestCase
{
    /**
     * @var RequestInterface
     */
    private $requestMock;

    /**
     * @var JSON
     */
    private $jsonMock;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactoryMock;

    /**
     * @var Session
     */
    private $sessionMock;

    /**
     * @var UserGroupsPermissionRepositoryInterface
     */
    private $userGroupsPermissionRepositoryMock;

    /**
     * @var CheckDupes
     */
    private $checkDupesController;

    /**
     * @var FilterBuilder
     */
    private $filterBuilderMock;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var CustomerRepository
     */
    private $customerRepositoryMock;

    /**
     * @var Filter
     */
    private $filterMock;

    /**
     * @var UserGroupsRepositoryInterface
     */
    private $userGroupsInterface;

    /**
     * @var UserGroupsInterface
     */
    private $userGroupsInterfaceMock;

    protected function setUp(): void
    {
        $this->sessionMock = $this->getMockBuilder(Session::class)
                                    ->setMethods([
                                        'getCustomer',
                                        'getOndemandCompanyInfo',
                                    ])
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
                                    ->setMethods([
                                        'getParam',
                                    ])
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();

        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
                                            ->setMethods([
                                                'create'
                                            ])
                                            ->disableOriginalConstructor()
                                            ->getMock();

        $this->userGroupsPermissionRepositoryMock = $this->getMockBuilder(UserGroupsPermissionRepositoryInterface::class)
                                                        ->setMethods([
                                                            'checkDuplicateUsersInGroup'
                                                        ])
                                                        ->disableOriginalConstructor()
                                                        ->getMockForAbstractClass();

        $this->jsonMock = $this->getMockBuilder(Json::class)
                            ->setMethods([
                                'setData',
                            ])
                            ->disableOriginalConstructor()
                            ->getMock();

        $this->filterBuilderMock = $this->getMockBuilder(FilterBuilder::class)
            ->onlyMethods([
                'setField', 'setValue', 'setConditionType', 'create'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->onlyMethods([
                'addFilters', 'create'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepository::class)
            ->onlyMethods([
                'getList'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->filterMock = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userGroupsInterface = $this->getMockBuilder(UserGroupsRepositoryInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->userGroupsInterfaceMock = $this->getMockBuilder(UserGroupsInterface::class)
            ->setMethods(['getGroupName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->checkDupesController = new CheckDupes(
            $this->requestMock,
            $this->resultJsonFactoryMock,
            $this->sessionMock,
            $this->userGroupsPermissionRepositoryMock,
            $this->filterBuilderMock,
            $this->searchCriteriaBuilderMock,
            $this->customerRepositoryMock,
            $this->userGroupsInterface
        );
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute($params)
    {
        $companyId = 123;
        $groupId = 123;
        $userIds = '123';
        $groupType = 'order_approval';
        $isFolderPermissionGroup = $params;

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturn($groupId);

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturn($userIds);

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturn($groupType);

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturn($isFolderPermissionGroup);

        $this->sessionMock
            ->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn([
                'company_id' => $companyId
            ]);

        $this->testCheckDuplicateCustomerGroups();
        $this->userGroupsPermissionRepositoryMock
            ->expects($this->any())
            ->method('checkDuplicateUsersInGroup')
            ->willReturn([['group_id' => $groupId, 'duplicate_count' => 1, 'group_name' => 'Test Group']]);
        $this->userGroupsInterface
            ->expects($this->any())
            ->method('get')
            ->with($groupId)
            ->willReturn($this->userGroupsInterfaceMock);
        $this->userGroupsInterfaceMock
            ->expects($this->any())
            ->method('getGroupName')
            ->willReturn('Test Group');
        

        $this->jsonMock
            ->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->resultJsonFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->jsonMock);

        $result = $this->checkDupesController->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteWithInvalidParameters()
    {
        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturn(null);

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturn('');

        $this->sessionMock
            ->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn(null);

        $this->jsonMock
            ->expects($this->any())
            ->method('setData')
            ->with([
                'success' => false,
                'message' => __('Invalid parameters.')
            ])
            ->willReturnSelf();

        $this->resultJsonFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->jsonMock);

        $result = $this->checkDupesController->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testCheckDuplicateCustomerGroups()
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

        $this->customerRepositoryMock->expects($this->any())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($groupListMock);

        $groupListMock->expects($this->any())->method('getTotalCount')
            ->willReturn(1);

        $this->assertFalse((bool) $this->checkDupesController->checkDuplicateCustomerGroups('1', 1, '1'));
    }

    public function executeDataProvider()
    {
        return [
            [true],
            [false]
        ];
    }
}