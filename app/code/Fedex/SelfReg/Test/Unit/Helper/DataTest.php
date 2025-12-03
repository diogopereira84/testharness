<?php

namespace Fedex\SelfReg\Test\Unit\Helper;

use Fedex\Commercial\Helper\CommercialHelper as commercialHelper;
use Magento\Framework\App\Helper\Context;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\SelfReg\Helper\Data;
use Fedex\SelfReg\Model\EnhanceUserRolesFactory;
use Fedex\SelfReg\Model\EnhanceUserRoles;
use Fedex\SelfReg\Model\ResourceModel\EnhanceUserRoles\CollectionFactory as UserRoleCollectionFactory;
use Fedex\SelfReg\Model\ResourceModel\EnhanceUserRoles\Collection as UserRoleCollection;
use Fedex\SelfReg\Model\ResourceModel\EnhanceRolePermission\CollectionFactory as RolePermissionCollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Fedex\SelfReg\Model\ResourceModel\UserGroupsPermission\CollectionFactory as UserGroupPermissionCollectionFactory;
use Fedex\SelfReg\Model\ResourceModel\UserGroupsPermission\Collection as UserGroupPermissionCollection;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;

class DataTest extends TestCase{
    
    protected $userGroupsPermissionCollectionMockFactory;
    protected $userGroupsPermissionCollectionMock;
    protected $contextMock;
    protected $selfReg;
    protected $commercialHelper;
    protected $userRoleFactoryMock;
    protected $userRoleMock;
    protected $customerRepositoryMock;
    protected $loggerMock;
    protected $rolePermissionCollectionFacotryMock;
    protected $roleCollectionFacotryMock;
    protected $data;
    protected $customerInterFaceMock;
    protected $userRoleCollectionMock;
    /**
     * @var DeliveryDataHelper
     */
    protected $deliveryHelper;

    protected function setUp():void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
             ->disableOriginalConstructor()
             ->getMock();
        $this->selfReg = $this->getMockBuilder(SelfReg::class)
             ->disableOriginalConstructor()
             ->getMock();
        $this->commercialHelper = $this->getMockBuilder(CommercialHelper::class)
             ->disableOriginalConstructor()
             ->getMock();
        $this->userRoleFactoryMock = $this->getMockBuilder(EnhanceUserRolesFactory::class)
             ->disableOriginalConstructor()
             ->getMock();
        $this->roleCollectionFacotryMock = $this->getMockBuilder(UserRoleCollectionFactory::class)
             ->setMethods(['addFieldToFilter', 'create'])
             ->disableOriginalConstructor()
             ->getMock();
        $this->rolePermissionCollectionFacotryMock = $this->getMockBuilder(RolePermissionCollectionFactory::class)
             ->disableOriginalConstructor()
             ->setMethods(['create'])
             ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
             ->disableOriginalConstructor()
             ->getMockForAbstractClass();
        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
             ->disableOriginalConstructor()
             ->getMockForAbstractClass();
        $this->userRoleMock = $this->getMockBuilder(EnhanceUserRoles::class)
            ->setMethods(['addFieldToFilter', 'getData', 'save'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->customerInterFaceMock =  $this->getMockBuilder(CustomerInterface::class)
            ->setMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->userRoleCollectionMock =  $this->getMockBuilder(UserRoleCollection::class)
            ->setMethods(['getData', 'addFieldToFilter', 'getFirstItem', 'delete'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->userGroupsPermissionCollectionMockFactory =  $this->getMockBuilder(UserGroupPermissionCollectionFactory::class)
            ->setMethods(['create', 'addFieldToFilter', 'getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->userGroupsPermissionCollectionMock =  $this->getMockBuilder(UserGroupPermissionCollection::class)
            ->setMethods(['getData', 'addFieldToFilter', 'getFirstItem', 'delete'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->deliveryHelper =  $this->getMockBuilder(DeliveryDataHelper::class)
            ->setMethods(['isCommercialCustomer'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->data = $objectManagerHelper->getObject(
            Data::class,
            [
                'context' => $this->contextMock,
                'selfReg' => $this->selfReg,
                'commercialHelper' => $this->commercialHelper,
                'userRoleFactory' => $this->userRoleFactoryMock,
                'roleCollectionFacotry' => $this->roleCollectionFacotryMock,
                'rolePermissionCollectionFacotry' => $this->rolePermissionCollectionFacotryMock,
                'logger' => $this->loggerMock,
                'customerRepository' => $this->customerRepositoryMock,
                'userGroupsPermissionCollection' => $this->userGroupsPermissionCollectionMockFactory,
                'deliveryHelper' => $this->deliveryHelper

            ]
        );
    }
    
    public function testGetLabelTrue()
    {
         $this->commercialHelper->expects($this->any())
             ->method('isSelfRegAdminUpdates')
             ->willReturn(true);
        $label = $this->data->getLabel();
        $this->assertEquals("Manage Users",$label);
    }

    public function testGetLabelFalse()
    {
         $this->commercialHelper->expects($this->any())
             ->method('isSelfRegAdminUpdates')
             ->willReturn(false);
        $label = $this->data->getLabel();
        $this->assertEquals("Company Users",$label);
    }

    // Test case function getReview Order ID
    public function testgetReviewOrderPermissionID() {
        $value[0]['id'] = 45;
        $this->rolePermissionCollectionFacotryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->userRoleMock);
        $this->userRoleMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->userRoleMock
            ->expects($this->any())
            ->method('getData')
            ->willReturn($value);
        $this->assertNotNull($this->data->getReviewOrderPermissionID());
    }

    // Test case function to check customer exists
    public function testCheckCustomerIsExists() {
        $this->customerRepositoryMock
        ->expects($this->any())
        ->method('getById')
        ->willReturn($this->customerInterFaceMock);
        $this->data->checkCustomerIsExists(12);
        $this->assertNotNull($this->data->checkCustomerIsExists(12));
    }

    // Test case function to check customer exists catch
    public function testCheckCustomerIsExistsCatch() {
        $this->customerRepositoryMock
        ->expects($this->any())
        ->method('getById')
        ->willThrowException(new \Exception());

        $this->loggerMock
        ->expects($this->any())
        ->method('error')
        ->willReturnSelf();
        $this->data->checkCustomerIsExists(121);
        $this->assertEquals(false,$this->data->checkCustomerIsExists(12));
    }

    // Test case function to check ifvalueexist

    public function testCheckIfValueExist() {

        $value[0]['id'] = 20;

        $this->roleCollectionFacotryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->userRoleCollectionMock);
        $this->userRoleCollectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->userRoleCollectionMock
            ->expects($this->any())
            ->method('getData')
            ->willReturn($value);
        $this->assertNotNull($this->data->checkIfValueExist(1, 2, 3));
    }

    // Test case function to check ifvalueexist with 0 as return

    public function testCheckIfValueExistCountZero() {

        $value = [];

        $this->roleCollectionFacotryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->userRoleCollectionMock);
        $this->userRoleCollectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->userRoleCollectionMock
            ->expects($this->any())
            ->method('getData')
            ->willReturn($value);

        $this->assertNotNull($this->data->checkIfValueExist(1, 2, 3));
    }

    // Test case function for set permission in else
    public function testSetPermissions() {
        $this->testgetReviewOrderPermissionID();
        $this->testCheckIfValueExistCountZero();
        $this->testCheckCustomerIsExists();
        $this->userRoleFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->userRoleMock);
        $this->userRoleMock
            ->expects($this->any())
            ->method('save')
            ->willReturnSelf();
        $this->assertNull($this->data->setPermissions( 2, 3));
    }

    // Test case function for set permission in else
    public function testSetPermissionsSelse() {
        $this->testgetReviewOrderPermissionID();
        $this->testCheckIfValueExist();
        $this->loggerMock
        ->expects($this->any())
        ->method('info')
        ->willReturnSelf();
        $this->assertNull($this->data->setPermissions( 2, 3));
    }

    // Test case function for set permission with exception
    public function testSetPermissionsSException() {
        $this->testgetReviewOrderPermissionID();
        $this->testCheckIfValueExistCountZero();
        $this->testCheckCustomerIsExists();
        $this->userRoleFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->userRoleMock);
        $this->userRoleMock
            ->expects($this->any())
            ->method('save')
            ->willThrowException(new \Exception());
        $this->loggerMock
            ->expects($this->any())
            ->method('error')
            ->willReturnSelf();
        $this->assertNull($this->data->setPermissions( 2, 3));
    }

    // Test case function to delete permission
    public function testDeletePermission() {
        $this->testgetReviewOrderPermissionID();
        $value[0]['id'] = 45;
        $this->roleCollectionFacotryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->userRoleCollectionMock);
        $this->userRoleCollectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->userRoleCollectionMock
            ->expects($this->any())
            ->method('getFirstItem')
            ->willReturnSelf();
        $this->userRoleCollectionMock
            ->expects($this->any())
            ->method('getData')
            ->willReturn($value);
        $this->userRoleCollectionMock
            ->expects($this->any())
            ->method('delete')
            ->willReturn(1);
        $this->assertNull($this->data->deletePermission(1, 2, 3));
    }

    // Test case function to check ifvalueexist

    public function testcheckIfCustomerIsOrderApprovar() {

        $value[0]['id'] = 20;

        $this->userGroupsPermissionCollectionMockFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->userGroupsPermissionCollectionMock);
        $this->userGroupsPermissionCollectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->userGroupsPermissionCollectionMock
            ->expects($this->any())
            ->method('getData')
            ->willReturn($value);
        $this->assertNotNull($this->data->checkIfCustomerIsOrderApprovar(1, 2, 3));
    }

    // Test case function to check getLabelNameForAddressBook
    public function testGetLabelNameForAddressBook()
    {
         $this->deliveryHelper->expects($this->any())
             ->method('isCommercialCustomer')
             ->willReturn(true);
        $this->assertNotNull($this->data->getLabelNameForAddressBook());
    }

    // Test case function to check getLabelNameForAddressBook for Retail
    public function testGetLabelNameForAddressBookForRetail()
    {
            $this->deliveryHelper->expects($this->any())
                ->method('isCommercialCustomer')
                ->willReturn(false);
        $this->assertNotNull($this->data->getLabelNameForAddressBook());
    }


    // Test case function to check getLabelNameForAddressBook
    public function testGetSortOrderForAddressBook()
    {
         $this->deliveryHelper->expects($this->any())
             ->method('isCommercialCustomer')
             ->willReturn(true);
        $this->assertNotNull($this->data->getSortOrderForAddressBook());
    }

    // Test case function to check getLabelNameForAddressBook for Retail
    public function testGetSortOrderForAddressBookForRetail()
    {
            $this->deliveryHelper->expects($this->any())
                ->method('isCommercialCustomer')
                ->willReturn(false);
        $this->assertNotNull($this->data->getSortOrderForAddressBook());
    }
}
