<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Test\Unit\Model\OrderHistory;

use Fedex\OrderApprovalB2b\Model\OrderHistory\GetAllOrders;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Api\SortOrder;
use Fedex\OrderApprovalB2b\Model\ResourceModel\OrderGrid\CollectionFactory as OrderCollectionFactory;
use Fedex\SelfReg\Model\ResourceModel\UserGroupsPermission\CollectionFactory as UserGroupPermissionCollection;
use Magento\Framework\App\ResourceConnection;
use Magento\Customer\Model\Customer;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Fedex\SelfReg\Model\ResourceModel\UserGroupsPermission\Collection as UserGroupCollection;
use Magento\Framework\DB\Select;
use Fedex\SelfReg\Helper\Data as SelfRegHelper;
use Fedex\OrderApprovalB2b\Helper\RevieworderHelper;
use Fedex\Delivery\Helper\Data as DeliveryHelper;

/**
 * Test class for GetAllOrders
 */
class GetAllOrdersTest extends TestCase
{
    /**
     * @var UserGroupCollection $userGroupMock
     */
    protected $userGroupMock;
    /**
     * @var Select $selectMock
     */
    protected $selectMock;
    /**
     * @var UserContextInterface $userContext
     */
    private $userContext;

    /**
     * @var RequestInterface $request
     */
    protected $request;

    /**
     * @var OrderCollectionFactory $orderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var GetAllOrders $getAllOrders
     */
    private $getAllOrders;

    /**
     * @var ToggleConfig $toggleConfig
     */
    private $toggleConfigMock;

    /**
     * @var Customer $customerMock
     */
    private $customerMock;
    
    /**
     * @var ResourceConnection $resourceConnectionMock
     */
    private $resourceConnectionMock;

    /**
     * @var UserGroupPermissionCollection $userGroupsPermissionCollectionMock
     */
    private $userGroupsPermissionCollectionMock;

    /**
     * @var AbstractCollection $abstracCollectionMock
     */
    private $abstracCollectionMock;

    /**
     * @var AdapterInterface $adapterInterface
     */
    private $adapterInterfaceMock;

    /**
     * @var RevieworderHelper $revieworderHelper
     */
    private $revieworderHelper;

    /**
     * @var DeliveryHelper $deliveryHelper
     */
    private $deliveryHelper;

    /**
     * @var SelfRegHelper $selfRegHelperMock
     */
    protected $selfRegHelperMock;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->userContext = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUserId'])
            ->getMockForAbstractClass();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();

        $this->orderCollectionFactory = $this->getMockBuilder(OrderCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'create',
                'addFieldToSelect',
                'addFieldToFilter',
                'setOrder',
                'setPageSize',
                'setCurPage',
                'getTable',
                'join'
                ])
            ->getMock();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
                ->disableOriginalConstructor()
                ->setMethods([
                    'getToggleConfigValue',
                    ])
                ->getMock();

        $this->customerMock = $this->getMockBuilder(Customer::class)
                ->disableOriginalConstructor()
                ->setMethods([
                    'getCollection',
                    ])
                ->getMock();

        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
                ->disableOriginalConstructor()
                ->setMethods([
                    'getConnection',
                    ])
                ->getMock();
        
        $this->userGroupsPermissionCollectionMock = $this->getMockBuilder(UserGroupPermissionCollection::class)
                ->disableOriginalConstructor()
                ->setMethods([
                    'create',
                    ])
                ->getMock();
        
        $this->abstracCollectionMock = $this->getMockBuilder(AbstractCollection::class)
        ->disableOriginalConstructor()
        ->setMethods([
            'getSelect',
            'getData',
            'addAttributeToSelect',
            'addFieldToFilter'
            ])
        ->getMockForAbstractClass();

        $this->adapterInterfaceMock = $this->getMockBuilder(AdapterInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->userGroupMock = $this->getMockBuilder(UserGroupCollection::class)
        ->disableOriginalConstructor()
        ->getMock();
        
        $this->selectMock = $this->getMockBuilder(Select::class)
        ->disableOriginalConstructor()
        ->setMethods(['join', 'getData','where'])
        ->getMock();

        $this->selfRegHelperMock = $this->getMockBuilder(SelfRegHelper::class)
        ->disableOriginalConstructor()
        ->setMethods(['getReviewOrderPermissionID', 'checkIfValueExist'])
        ->getMock();

        $this->revieworderHelper = $this->getMockBuilder(RevieworderHelper::class)
        ->disableOriginalConstructor()
        ->setMethods(['getCustomerId', 'getCompanyId'])
        ->getMock();

        $this->deliveryHelper = $this->getMockBuilder(DeliveryHelper::class)
        ->disableOriginalConstructor()
        ->setMethods(['isSelfRegCustomerAdminUser'])
        ->getMock();
        
        $objectManagerHelper = new ObjectManager($this);
        $this->getAllOrders = $objectManagerHelper->getObject(
            GetAllOrders::class,
            [
                'request' => $this->request,
                'orderCollectionFactory' => $this->orderCollectionFactory,
                'revieworderHelper' => $this->revieworderHelper,
                'deliveryDataHelper' => $this->deliveryHelper,
                'userGroupsPermissionCollection' => $this->userGroupsPermissionCollectionMock,
                'resourceConnection' => $this->resourceConnectionMock,
                'customer' => $this->customerMock,
                'toggleConfig' => $this->toggleConfigMock,
                'selfRegHelperMock' => $this->selfRegHelperMock
            ]
        );
    }

    /**
     * Test getCustomerId
     *
     * @return void
     */
    public function testGetCustomerId()
    {
        $this->request->expects($this->any())->method('getParam')->willReturn('ASC');
        $this->revieworderHelper->expects($this->any())->method('getCustomerId')->willReturn(12);
        $this->orderCollectionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->orderCollectionFactory->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->orderCollectionFactory->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->orderCollectionFactory->expects($this->any())->method('getTable')->willReturn('enhanced_user_roles');
        $this->orderCollectionFactory->expects($this->any())->method('join')->willReturnSelf();
        $this->testGetUserGroupOrderApproverToggel();
        $this->orderCollectionFactory->expects($this->any())->method('getTable')->willReturn('user_groups_permission');
        $this->selfRegHelperMock->expects($this->any())->method('getReviewOrderPermissionID')->willReturn('123');
        $this->selfRegHelperMock->expects($this->any())->method('checkIfValueExist')->willReturn(true);
        $this->testCheckIfCustomerIsOrderApprovar();
        $this->testGetCurrenctOrderApprovarUser();

        $this->orderCollectionFactory->expects($this->any())->method('setOrder')->willReturnSelf();
        $this->orderCollectionFactory->expects($this->any())->method('setPageSize')->willReturnSelf();
        $this->orderCollectionFactory->expects($this->any())->method('setCurPage')->willReturnSelf();
        $this->getAllOrders->getAllOrderHirory();
        $this->assertEquals($this->orderCollectionFactory, $this->getAllOrders->getAllOrderHirory());
    }

    /**
     * Test getCustomerId
     *
     * @return void
     */
    public function testGetCustomerIdWhenFalse()
    {
        $this->request->expects($this->any())->method('getParam')->willReturn('ASC');
        $this->revieworderHelper->expects($this->any())->method('getCustomerId')->willReturn(12);
        $this->orderCollectionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->orderCollectionFactory->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->orderCollectionFactory->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->orderCollectionFactory->expects($this->any())->method('getTable')->willReturn('enhanced_user_roles');
        $this->orderCollectionFactory->expects($this->any())->method('join')->willReturnSelf();
        $this->testGetUserGroupOrderApproverToggel();
        $this->orderCollectionFactory->expects($this->any())->method('getTable')->willReturn('user_groups_permission');
        $this->selfRegHelperMock->expects($this->any())->method('getReviewOrderPermissionID')->willReturn('123');
        $this->selfRegHelperMock->expects($this->any())->method('checkIfValueExist')->willReturn(false);
        $this->deliveryHelper->expects($this->any())->method('isSelfRegCustomerAdminUser')->willReturn(true);
        $this->testCheckIfCustomerIsOrderApprovarWhenCountIsZero();
        $this->testGetCurrenctOrderApprovarUser();

        $this->orderCollectionFactory->expects($this->any())->method('setOrder')->willReturnSelf();
        $this->orderCollectionFactory->expects($this->any())->method('setPageSize')->willReturnSelf();
        $this->orderCollectionFactory->expects($this->any())->method('setCurPage')->willReturnSelf();
        $result = $this->getAllOrders->getAllOrderHirory();
        $this->assertEquals($this->orderCollectionFactory, $result);
    }

    /**
     * Test getUserGroupOrderApproverToggel
     *
     */
    public function testGetUserGroupOrderApproverToggel()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertEquals(true, $this->getAllOrders->getUserGroupOrderApproverToggel());
    }

    /**
     * Test getCustomerConnection
     *
     */
    public function testGetCustomerConnection()
    {
        $this->customerMock->expects($this->any())->method('getCollection')->willReturn($this->abstracCollectionMock);
        $result = $this->getAllOrders->getCustomerConnection();
        $this->assertEquals($this->abstracCollectionMock, $result);
    }

    /**
     * Test getCompanyAdvanceCustomerEntityConnection
     *
     */
    public function testGetCompanyAdvanceCustomerEntityConnection()
    {
        $this->resourceConnectionMock->expects($this->any())
        ->method('getConnection')->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn('test');
        $result = $this->getAllOrders->getCompanyAdvanceCustomerEntityConnection();
        $this->assertEquals('test', $result);
    }

    /**
     * Test checkIfCustomerIsOrderApprovar
     *
     */
    public function testCheckIfCustomerIsOrderApprovar()
    {
        $customerId = 12;
        $mockCollectionData = [
            ['order_approval' => '12'],
            ['order_approval' => '456']
        ];

        $this->userGroupsPermissionCollectionMock->expects($this->any())
            ->method('create')
            ->willReturn($this->userGroupMock);

        $this->userGroupMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->userGroupMock->expects($this->any())
            ->method('getData')
            ->willReturn($mockCollectionData);

        $result = $this->getAllOrders->checkIfCustomerIsOrderApprovar($customerId);

        $this->assertEquals(2, $result);
    }

    /**
     * Test checkIfCustomerIsOrderApprovar
     *
     */
    public function testCheckIfCustomerIsOrderApprovarWhenCountIsZero()
    {
        $customerId = 12;
        $mockCollectionData = [];

        $this->userGroupsPermissionCollectionMock->expects($this->any())
            ->method('create')
            ->willReturn($this->userGroupMock);

        $this->userGroupMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->userGroupMock->expects($this->any())
            ->method('getData')
            ->willReturn($mockCollectionData);

        $result = $this->getAllOrders->checkIfCustomerIsOrderApprovar($customerId);

        $this->assertEquals(0, $result);
    }

    /**
     * Check if customer is order approver or not
     *
     * @param int|null $customerId
     */
    public function testCheckIfCompanyIsUser()
    {
        $customerId = 12;
        $mockCollectionData = [
            ['user_id' => '12'],
            ['user_id' => '456']
        ];
        $this->userGroupsPermissionCollectionMock->expects($this->any())
            ->method('create')
            ->willReturn($this->userGroupMock);

        $this->userGroupMock->expects($this->any())
            ->method('addFieldToFilter')
            ->with('user_id', ['finset' => $customerId])
            ->willReturnSelf();

        $this->userGroupMock->expects($this->any())
            ->method('getData')
            ->willReturn($mockCollectionData);

        $result = $this->getAllOrders->checkIfCompanyIsUser($customerId);

        $this->assertEquals(2, $result);
    }

    /**
     * Test getCurrenctOrderApprovarUser
     *
     */
    public function testGetCurrenctOrderApprovarUser()
    {
        $companyId = 123;
        $currentUserID = 90;
        $userGroupPermission = 'user_groups_permission';
        $allCustomerIds = [89]; // All customers passed into the method

        $mockCustomerData = [
        [
            'order_approval' => '88', // Does NOT include 90
            'customer_id' => 89
        ]
        ];

        $this->testGetCompanyAdvanceCustomerEntityConnection();
        $this->testGetCustomerConnection();

        $this->abstracCollectionMock->expects($this->any())->method('getSelect')->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())->method('join')->willReturnSelf();
        $this->selectMock->expects($this->any())->method('where')->willReturnSelf();
        $this->abstracCollectionMock->expects($this->any())->method('getData')->willReturn($mockCustomerData);

        $this->userGroupsPermissionCollectionMock->expects($this->any())
        ->method('create')->willReturn($this->userGroupMock);
        $this->userGroupMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->userGroupMock->expects($this->any())->method('getData')->willReturnSelf();

        $this->userGroupsPermissionCollectionMock->expects($this->any())
        ->method('create')->willReturn($this->userGroupMock);
        $this->userGroupMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->userGroupMock->expects($this->any())->method('getData')->willReturnSelf();

        $this->userGroupsPermissionCollectionMock->expects($this->any())
        ->method('create')->willReturn($this->userGroupMock);
        $this->userGroupMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->userGroupMock->expects($this->any())->method('getData')->willReturnSelf();

        $result = $this->getAllOrders->getCurrenctOrderApprovarUser(
            $companyId,
            $currentUserID,
            $userGroupPermission,
            $allCustomerIds
        );
        $this->assertSame([90], $result);
    }

    /**
     * Test getCurrenctOrderApprovarUser
     *
     */
    public function testGetCurrenctOrderApprovarUserIsZero()
    {
        $customer[0]['order_approval'] = '987,456';
        $customer[0]['customer_id'] = '798';
        $this->testGetCompanyAdvanceCustomerEntityConnection();
        $this->testGetCustomerConnection();

        $this->abstracCollectionMock->expects($this->any())->method('getSelect')->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())->method('join')->willReturnSelf();
        $this->selectMock->expects($this->any())->method('where')->willReturnSelf();
        $this->abstracCollectionMock->expects($this->any())->method('getData')->willReturn($customer);
        $this->userGroupsPermissionCollectionMock->expects($this->any())
        ->method('create')->willReturn($this->userGroupMock);
        $this->userGroupMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->userGroupMock->expects($this->any())->method('getData')->willReturnSelf();
        $result = $this->getAllOrders->getCurrenctOrderApprovarUser(123, 21, 'test', [47]);
        $this->assertEquals([47, 21], $result);
    }

    /**
     * Test getAllCusomterIds
     *
     */
    public function testGetAllCusomterIds()
    {
        $customer[0]['customer_id'] = 123;
        $customer[1]['customer_id'] = 456;
        $this->testGetCompanyAdvanceCustomerEntityConnection();
        $this->testGetCustomerConnection();
        $this->abstracCollectionMock->expects($this->any())
        ->method('addAttributeToSelect')->willReturn($this->selectMock);
        $this->abstracCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturn($this->selectMock);
        $this->abstracCollectionMock->expects($this->any())->method('getSelect')->willReturn($this->selectMock);

        $this->selectMock->expects($this->any())->method('join')->willReturn($this->abstracCollectionMock);

        $this->abstracCollectionMock->expects($this->any())->method('getData')->willReturn($customer);
        
        $result = $this->getAllOrders->getAllCusomterIds(123, 1);
        $this->assertEquals([123, 456], $result);
    }
}
