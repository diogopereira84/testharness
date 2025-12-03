<?php

namespace Fedex\SelfReg\Test\Unit\Block\User;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\SelfReg\Block\User\Search;
use Fedex\SelfReg\Model\EnhanceRolePermission;
use Fedex\CatalogDocumentUserSettings\Helper\Data as HelperData;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Data\Collection\AbstractDb;
use Fedex\Login\Helper\Login;
use Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel;
use Magento\Customer\Model\CustomerIdProvider;
use Fedex\SelfReg\Model\EnhanceUserRoles;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Company\Api\Data\CompanyInterface;

class SearchTest extends \PHPUnit\Framework\TestCase
{
    protected $objectManager;
    protected $searchBlock;
    protected $contextMock;
    protected $rolePermissionsMock;
    protected $helperDataMock;
    protected $collectionMock;
    protected $loginHelper;
    protected $orderApprovalViewModel;
    protected $customerIdProviderMock;
    protected $enhancedUserRolesMock;
    protected $companyRepositoryMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        // Mock Context
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Mock EnhanceRolePermission
        $this->rolePermissionsMock = $this->getMockBuilder(EnhanceRolePermission::class)
            ->setMethods(['getCollection'])
            ->disableOriginalConstructor()
            ->getMock();

        // Mock HelperData
        $this->helperDataMock = $this->getMockBuilder(HelperData::class)
            ->setMethods(['getCompanyConfiguration'])
            ->disableOriginalConstructor()
            ->getMock();

        // Mock Collection
        $this->collectionMock = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loginHelper = $this->getMockBuilder(Login::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCommercialFCLApprovalType'])
            ->getMock();

        $this->orderApprovalViewModel = $this->getMockBuilder(OrderApprovalViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['isOrderApprovalB2bEnabled'])
            ->getMock();

        $this->customerIdProviderMock = $this->getMockBuilder(CustomerIdProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomerId'])
            ->getMock();

        $this->enhancedUserRolesMock = $this->getMockBuilder(EnhanceUserRoles::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection'])
            ->getMock();

        $this->companyRepositoryMock = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getByCustomerId'])
            ->getMockForAbstractClass();

        // Create instance of the block with mocked dependencies
        $this->searchBlock = $this->objectManager->getObject(
            Search::class,
            [
                'context' => $this->contextMock,
                'rolePermissions' => $this->rolePermissionsMock,
                'helperData' => $this->helperDataMock,
                'loginHelper' => $this->loginHelper,
                'orderApprovalViewModel' => $this->orderApprovalViewModel,
                'customerIdProvider' => $this->customerIdProviderMock,
                'enhancedUserRoles' => $this->enhancedUserRolesMock,
                'companyRepository' => $this->companyRepositoryMock
            ]
        );
    }

    public function testIsAllowSharedCatalog()
    {
        $companyConfigurationMock = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['getAllowSharedCatalog', 'getCompanyConfiguration'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $companyConfigurationMock->expects($this->any())
            ->method('getAllowSharedCatalog')
            ->willReturn(true);

        $this->helperDataMock->expects($this->any())
            ->method('getCompanyConfiguration')
            ->willReturn($companyConfigurationMock);

        $this->assertTrue($this->searchBlock->isAllowSharedCatalog());
    }

    public function testGetRolePermission()
    {
        $this->rolePermissionsMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->with('sort_order', ['gteq' => '1'])
            ->willReturnSelf();

        $this->orderApprovalViewModel->expects($this->once())
            ->method('isOrderApprovalB2bEnabled')
            ->willReturn(true);

        $this->testIsAllowSharedCatalog();

        $this->assertSame($this->collectionMock, $this->searchBlock->getRolePermission());
    }

    /**
     * Test method for getRolePermission with review order permission
     */
    public function testGetRolePermissionWithReviewOrder()
    {
        $this->rolePermissionsMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->orderApprovalViewModel->expects($this->once())
            ->method('isOrderApprovalB2bEnabled')
            ->willReturn(false);

        $this->testIsAllowSharedCatalog();

        $this->assertEquals($this->collectionMock, $this->searchBlock->getRolePermission());
    }

    public function testGetAllRolePermission()
    {
        $this->rolePermissionsMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->collectionMock);

        $this->orderApprovalViewModel->expects($this->once())
            ->method('isOrderApprovalB2bEnabled')
            ->willReturn(false);

        $this->testIsAllowSharedCatalog();

        $this->assertSame($this->collectionMock, $this->searchBlock->getAllRolePermission());
    }

    public function testGetManageruserEmailAllow()
    {
        $permissionMock = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['getLabel', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $permissionMock->expects($this->once())
            ->method('getLabel')
            ->willReturn("Yes::email_allow::manage_users");
        $permissionMock->expects($this->any())
            ->method('getId')
            ->willReturn("Yes::email_allow::manage_users");

        $collection = [$permissionMock];

        $this->assertEquals($permissionMock->getId(), $this->searchBlock->getManageruserEmailAllow($collection));
    }

    public function testGetManageruserEmailDeny()
    {
        $permissionMock = $this->getMockBuilder(\stdClass::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLabel', 'getId'])
            ->getMock();
        $permissionMock->expects($this->once())
            ->method('getLabel')
            ->willReturn("No::email_deny::manage_users");
        $permissionMock->expects($this->any())
            ->method('getId')
            ->willReturn("Yes::email_allow::manage_users");

        $collection = [$permissionMock];

        $this->assertEquals($permissionMock->getId(), $this->searchBlock->getManageruserEmailDeny($collection));
    }

    /**
     * Test Case for isShowEmailSendingSection
     */
    public function testIsShowEmailSendingSection()
    {
        $returnData['login_method'] = "admin_approval";
        $this->loginHelper->expects($this->any())
            ->method('getCommercialFCLApprovalType')
            ->willReturn($returnData);
        $this->assertEquals(true, $this->searchBlock->isShowEmailSendingSection());
    }

    public function testGetCustomerPermissions()
    {
        $customerId = 1;
        $companyId = 2;
        $permissionId = 3;
        $permissionIdArr = [3 => true];
        
        $collectionMock = $this->createMock(AbstractCollection::class);
        $companyMock = $this->createMock(CompanyInterface::class);

        $this->customerIdProviderMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->companyRepositoryMock->expects($this->once())
            ->method('getByCustomerId')
            ->with($customerId)
            ->willReturn($companyMock);

        $companyMock->expects($this->once())
            ->method('getId')
            ->willReturn($companyId);
  

        $this->enhancedUserRolesMock->expects($this->once())
            ->method('getCollection')
            ->willReturn($collectionMock);

        $collectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $collectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $collectionMock->expects($this->any())
            ->method('getColumnValues')
            ->willReturn([$permissionId]);

        $this->assertEquals($permissionIdArr, $this->searchBlock->getCustomerPermissions());
    }
}
