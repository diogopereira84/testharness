<?php
namespace Fedex\SelfReg\Test\Unit\Controller\Customer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TestFramework\TestCase\AbstractController;
use Fedex\SelfReg\Controller\Customer\BulkSave;
use PHPUnit\Framework\TestCase;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Fedex\SelfReg\Model\EnhanceUserRoles;
use Fedex\SelfReg\Model\ResourceModel\EnhanceUserRoles\Collection as RolesCollection;
use Fedex\SelfReg\Model\ResourceModel\EnhanceRolePermission\Collection as PermissionCollection;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Fedex\SelfReg\Model\EnhanceRolePermission;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Fedex\CustomerGroup\Controller\Adminhtml\Options\Save as CustomerGroupSaveHelper;

class BulkSaveTest extends TestCase
{
    protected $companyAttributes;
    protected $storeMock;
    protected $storeManager;
    protected $customerExtension;
    protected $request;
    protected $companyRepository;
    protected $companyInterface;
    protected $customerGroupSaveHelperMock;
    /**
     * @var BulkSave
     */
    private $bulkSaveController;

    /**
     * @var JsonFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resultJsonFactoryMock;

    /**
     * @var CustomerRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var EnhanceUserRoles|\PHPUnit\Framework\MockObject\MockObject
     */
    private $enhanceUserRolesFactoryMock;
    
    /**
     * @var EnhanceRolePermission|\PHPUnit\Framework\MockObject\MockObject
     */
    private $enhanceRolePermissionMock;
    
    /**
     * @var RolesCollection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $collectionMock;

    /**
     * @var PermissionCollection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $permissionCollectionMock;

    /**
     * @var SelfReg|\PHPUnit\Framework\MockObject\MockObject
     */
    private $selfRegMock;

    protected $resourceConnection;
    protected $adapterInterface;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->getMock();
        
        $this->enhanceUserRolesFactoryMock = $this->getMockBuilder(EnhanceUserRoles::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','getCollection','setCustomerId','setCompanyId','setPermissionId','save','getData','delete'])
            ->getMock();

        $this->enhanceRolePermissionMock = $this->getMockBuilder(EnhanceRolePermission::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection','getId'])
            ->getMock();

        $this->collectionMock = $this->getMockBuilder(RolesCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getFirstItem'])
            ->getMockForAbstractClass();

        $this->permissionCollectionMock = $this->getMockBuilder(PermissionCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getFirstItem'])
            ->getMock();
        
        $this->companyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->setMethods(['getBaseUrl'])
            ->getMockForAbstractClass();
            
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->customerExtension = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->selfRegMock = $this->getMockBuilder(SelfReg::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyRepository = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getByCustomerId'])
            ->getMockForAbstractClass();

        $this->companyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->companyInterface = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection'])
            ->getMock();
        $this->adapterInterface = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['insertOnDuplicate'])
            ->getMockForAbstractClass();

        $this->customerGroupSaveHelperMock = $this->getMockBuilder(CustomerGroupSaveHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateCustomerAttribute'])
            ->getMockForAbstractClass();

        $this->bulkSaveController = $objectManager->getObject(
            BulkSave::class,
            [
                'customerRepository' => $this->customerRepositoryMock,
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'roleUser' => $this->enhanceUserRolesFactoryMock,
                'collection' => $this->collectionMock,
                'permissionCollection' => $this->permissionCollectionMock,
                'storeManager' => $this->storeManager,
                'enhanceRolePermission' => $this->enhanceRolePermissionMock,
                'companyRepository' => $this->companyRepository,
                'selfReg' => $this->selfRegMock,
                'resourceConnection' => $this->resourceConnection,
                '_request' => $this->request,
                'customerGroupSaveHelper' => $this->customerGroupSaveHelperMock
            ]
        );
    }

    public function testExecute()
    {
        $baseUrl = 'base-url';
        $requestParams = [
            'customerIds' => '1,2,3',
            'status' => 1,
            'emailApproval' => 1,
            'rolePermissions' => [
                'edit_users_shared_orders' => 1,
                'edit_users_shared_credit_cards' => 2,
                'edit_users_manage_users' => 3,
                'edit_users_manage_catalog' => 4
            ],
            'group' => 1
        ];

        $this->request->expects($this->any())->method('getParams')->willReturn($requestParams);

        $customerMock = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterface::class)
            ->getMock();

        $this->customerRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($customerMock);
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')->willReturn($baseUrl);
        $this->customerExtension->expects($this->any())
            ->method('getCompanyAttributes')->willReturn($this->companyAttributes);
        $customerMock->expects($this->any())
            ->method('getExtensionAttributes')->willReturn($this->customerExtension);
        $this->companyAttributes->expects($this->any())
            ->method('setStatus')->willReturnSelf();
        $this->customerExtension->expects($this->any())
            ->method('setCompanyAttributes')->willReturn($this->companyAttributes);
        $customerMock->expects($this->any())
            ->method('setExtensionAttributes')->willReturnSelf();
        $this->customerRepositoryMock->expects($this->any())
            ->method('save')
            ->with($customerMock);
        $this->customerGroupSaveHelperMock->expects($this->any())
            ->method('updateCustomerAttribute')
            ->willReturnSelf();
        

        $resultJsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultJsonMock);

        $resultJsonMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $this->testSetPermission();
        $this->assertNotNull($this->bulkSaveController->execute());
    }

    public function testDeletePermission()
    {
        $customerId = 1;
        $permissionId = 2;
        $companyId = 3;

        $data = [
            "id" => 22,
            "company_id" => 98,
            "customer_id" => 4827,
            "permission_id" => 1
        ];
        
        $this->enhanceUserRolesFactoryMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->collectionMock);
        $this->collectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->collectionMock);
        $this->collectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->enhanceUserRolesFactoryMock);
        $this->enhanceUserRolesFactoryMock->expects($this->any())
            ->method('getData')
            ->willReturn($data);
        $this->enhanceUserRolesFactoryMock->expects($this->any())
            ->method('delete')
            ->willReturn($this->enhanceUserRolesFactoryMock);

        $this->bulkSaveController->deletePermission($customerId, $permissionId, $companyId);
    }


    public function testGetEmailYesPermissions()
    {
        $this->enhanceRolePermissionMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->permissionCollectionMock);
        $this->permissionCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->permissionCollectionMock);
        $this->permissionCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->enhanceRolePermissionMock);
        $this->enhanceRolePermissionMock->expects($this->any())
            ->method('getId')
            ->willReturn(5);
        $this->bulkSaveController->getEmailYesPermissions();
    }

    public function testGetEmailNoPermissions()
    {
        $this->enhanceRolePermissionMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->permissionCollectionMock);
        $this->permissionCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->permissionCollectionMock);
        $this->permissionCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->enhanceRolePermissionMock);
        $this->enhanceRolePermissionMock->expects($this->any())
            ->method('getId')
            ->willReturn(6);
        $this->bulkSaveController->getEmailNoPermissions();
    }

    public function testGetCompanyNameByCustomerId()
    {
        $this->companyRepository->expects($this->any())
        ->method('getByCustomerId')
        ->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())
        ->method('getCompanyName')
        ->willReturn('test');
        $this->assertEquals('test', $this->bulkSaveController->getCompanyNameByCustomerId(2));
    }

    public function testSetPermission()
    {
        $this->companyRepository->expects($this->any())
        ->method('getByCustomerId')
        ->willReturn($this->companyInterface);
        $this->testGetEmailYesPermissions();
        $this->testGetEmailNoPermissions();
        $this->companyInterface->expects($this->any())
        ->method('getId')
        ->willReturn(2);
        $this->testDeletePermission();
        $this->resourceConnection->expects($this->any())->method('getConnection')->willReturn($this->adapterInterface);
        $this->adapterInterface->expects($this->any())->method('insertOnDuplicate')->willReturn(2);
        $this->bulkSaveController->setPermissions(['1','2','3'],2);
    }


}
