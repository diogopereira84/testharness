<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Fedex\SelfReg\Test\Unit\Controller\Customer;
use Fedex\SelfReg\Controller\Customer\Save;
use Magento\Company\Model\Action\SaveCustomer;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Backend\App\Action\Context;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Framework\Api\AttributeValue;
use Fedex\Commercial\Helper\CommercialHelper;
use Magento\Framework\App\ResourceConnection;
use Fedex\SelfReg\Model\EnhanceUserRoles;
use Fedex\SelfReg\Model\ResourceModel\EnhanceUserRoles\Collection as EnhanceUserRolesCollection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\CustomerGroup\Controller\Adminhtml\Options\Save as CustomerGroupSaveHelper;


/**
 * Unit test for customer save controller.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends TestCase
{

    /**
     * @var (\Magento\Backend\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $customer;
    protected $attributeValueMock;
    protected $customerData;
    protected $storeManager;
    protected $companyRepository;
    protected $companyInterface;
    protected $storeMock;
    protected $companyAttributes;
    protected $selfregHelper;
    protected $deliveryHelper;
    protected $customerGroupSaveHelperMock;
    /**
     * @var SaveCustomer|MockObject
     */
    private $customerAction;

    /**
     * @var Structure|MockObject
     */
    private $structureManager;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ResultJson|MockObject
     */
    private $resultJson;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var CompanyContext|MockObject
     */
    private $companyContext;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var Save
     */
    private $save;

    /**
     * @var JsonFactory|MockObject
     */
    protected $resultJsonFactory;

    /**
     * @var CommercialHelper|MockObject
     */
    private $commercialHelper;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    protected $resourceConnection;
    protected $roleUser;
    protected $adapterInterface;
    protected $roleUserCollection;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->customerAction = $this->getMockBuilder(SaveCustomer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->structureManager = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyContext = $this->getMockBuilder(CompanyContext::class)
        ->setMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resultJson = $this->getMockBuilder(ResultJson::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->commercialHelper = $this->getMockBuilder(CommercialHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isRolePermissionToggleEnable'])
            ->getMock();

        $this->customer = $this->getMockForAbstractClass(CustomerInterface::class);

        $this->attributeValueMock = $this->getMockBuilder(\Magento\Customer\Model\AttributeValue::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();

        $this->customerData = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
        ->disableOriginalConstructor()
        ->setMethods(['getData','load','save','setCustomAttribute'])
        ->getMock();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
        ->disableOriginalConstructor()
        ->setMethods(['getStore'])
        ->getMockForAbstractClass();

        $this->companyRepository = $this->getMockBuilder(CompanyManagementInterface::class)
        ->disableOriginalConstructor()
        ->setMethods(['getByCustomerId'])
        ->getMockForAbstractClass();
        

        $this->companyInterface = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
        ->setMethods(['getBaseUrl'])
        ->getMockForAbstractClass();

        $this->companyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->selfregHelper = $this->getMockBuilder(SelfReg::class)
        ->setMethods(['isSelfRegCustomer','isSelfRegCustomerAdmin'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection'])
            ->getMock();
        $this->roleUser = $this->getMockBuilder(EnhanceUserRoles::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection','delete','load','getId'])
            ->getMock();
        $this->roleUserCollection = $this->getMockBuilder(EnhanceUserRolesCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter','getIterator'])
            ->getMock();
        $this->adapterInterface = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['insertOnDuplicate'])
            ->getMockForAbstractClass();
        $this->deliveryHelper = $this->getMockBuilder(DeliveryHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigurationValue','checkPermission'])
            ->getMock();
        $this->customerGroupSaveHelperMock = $this->getMockBuilder(CustomerGroupSaveHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateCustomerAttribute'])
            ->getMockForAbstractClass();
        $this->customerRepository = $this->getMockForAbstractClass(CustomerRepositoryInterface::class);
        $objectManagerHelper = new ObjectManager($this);
        $this->save = $objectManagerHelper->getObject(
            Save::class,
            [
                'customerAction' => $this->customerAction,
                'structureManager' => $this->structureManager,
                '_request' => $this->request,
                'resultFactory' => $this->resultFactory,
                'logger' => $this->logger,
                'companyContext' => $this->companyContext,
                'resultJsonFactory' => $this->resultJsonFactory,
                'storeManager' => $this->storeManager,
                'companyAttributes'=>$this->companyAttributes,
                'resultJson'=>$this->resultJson,
                'selfregHelper' => $this->selfregHelper,
                'customerData' => $this->customerData,
                'customerRepository' => $this->customerRepository,
                'companyRepository' => $this->companyRepository,
                'commercialHelper' => $this->commercialHelper,
                'resourceConnection' => $this->resourceConnection,
                'roleUser' => $this->roleUser,
                'deliveryHelper'=> $this->deliveryHelper,
                'customerGroupSaveHelper' => $this->customerGroupSaveHelperMock
            ]
        );
    }

 
    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecuteNew()
    {
        $success = ['status' => 'error', 'message' => 'You cannot update yourself.'];
        $customerId = 1;
        $baseUrl = 'base-url';
        $data = ['customer_id' => '1',
                 'firstname' => 'Sanchit',
                 'lastname' => 'Bhatia' ,
                 'status' => '1' ,
                 'email' => 'sanchit@gmail.com',
                 'group' => '1'
                ];
        $data['rolePermissions']['shared_order_permission'] = 2;
        $data['emailApproval'] = 5;
        $this->testIsDeleteCustomerIfNull();
        $this->selfregHelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->selfregHelper->expects($this->any())->method('isSelfRegCustomerAdmin')->willReturn(true);
        $this->request->expects($this->any())->method('getParams')->willReturn($data);
         $this->customerData->expects($this->any())->method('load')->willReturnSelf();
         $this->customerRepository->expects($this->any())->method('getById')->willReturn($this->customer);
         $this->customer->expects($this->any())->method('getCustomAttribute')->willReturn($this->attributeValueMock);
         $this->attributeValueMock
         ->expects($this->any())->method('getValue')->willReturn('neeraj2.gupta@infogain.com');
         
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')->willReturn($baseUrl);
        $this->companyRepository->expects($this->any())->method('getByCustomerId')->willReturn($this->companyInterface);
        $customerExtension = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setCompanyAttributes', 'getCompanyAttributes']
        );
        
        $this->resourceConnection->expects($this->any())->method('getConnection')->willReturn($this->adapterInterface);
        $this->adapterInterface->expects($this->any())->method('insertOnDuplicate')->willReturn(2);
        $this->roleUser->expects($this->any())->method('getCollection')->willReturn($this->roleUserCollection);
        $this->roleUserCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->roleUserCollection->expects($this->any())->method('getIterator')->willReturnSelf(new \ArrayIterator([$this->roleUser]));
        $this->roleUser->expects($this->any())->method('getId')->willReturn(23);
        $this->roleUser->expects($this->any())->method('load')->willReturnSelf();
        $this->roleUser->expects($this->any())->method('delete')->willReturnSelf();

        $customerExtension->expects($this->any())
            ->method('getCompanyAttributes')->willReturn($this->companyAttributes);
        $this->customer->expects($this->any())
            ->method('getExtensionAttributes')->willReturn($customerExtension);
            $this->companyAttributes->expects($this->any())
            ->method('getStatus')->willReturn('0');
        $this->commercialHelper->expects($this->any())
            ->method('isRolePermissionToggleEnable')->willReturn(false);
        $this->customerGroupSaveHelperMock->expects($this->any())
            ->method('updateCustomerAttribute')->willReturnSelf();

        $this->customerData->expects($this->any())->method('save')->willReturnSelf();

        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setData')->willReturnSelf();
        $this->deliveryHelper->expects($this->any())->method('getToggleConfigurationValue')->willReturn(true);
        $this->deliveryHelper->expects($this->any())->method('checkPermission')->willReturn(true);
        $this->assertEquals($this->resultJson, $this->save->execute());
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecuteNew2()
    {
        $success = ['status' => 'error', 'message' => 'You cannot update yourself.'];
        $customerId = 1;
        $baseUrl = 'base-url';
        $data = ['customer_id' => '1',
                    'firstname' => 'Sanchit',
                    'lastname' => 'Bhatia' ,
                    'status' => '0' ,
                    'email' => 'sanchit@gmail.com'
                ];
        $data['rolePermissions']['shared_order_permission'] = 2;
        $data['emailApproval'] = 5;
        $this->selfregHelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->request->expects($this->any())->method('getParams')->willReturn($data);
        $this->customerData->expects($this->any())->method('load')->willReturnSelf();
        $this->customerRepository->expects($this->any())->method('getById')->willReturn($this->customer);
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')->willReturn($baseUrl);
        $this->companyRepository->expects($this->any())->method('getByCustomerId')->willReturn($this->companyInterface);
        $customerExtension = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setCompanyAttributes', 'getCompanyAttributes']
        );
        $customerExtension->expects($this->any())
            ->method('getCompanyAttributes')->willReturn($this->companyAttributes);
        $this->customer->expects($this->any())
            ->method('getExtensionAttributes')->willReturn($customerExtension);
            $this->customer->expects($this->any())->method('getCustomAttribute')->willReturn($this->attributeValueMock);
            $this->attributeValueMock
            ->expects($this->any())->method('getValue')->willReturn('neeraj2.gupta@infogain.com');
            $this->companyAttributes->expects($this->any())
            ->method('getStatus')->willReturn('0');
            $this->commercialHelper->expects($this->any())
            ->method('isRolePermissionToggleEnable')->willReturn(true);

            $this->resourceConnection->expects($this->any())->method('getConnection')->willReturn($this->adapterInterface);
            $this->adapterInterface->expects($this->any())->method('insertOnDuplicate')->willReturn(2);
            $this->roleUser->expects($this->any())->method('getCollection')->willReturn($this->roleUserCollection);
            $this->roleUserCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
            $this->roleUserCollection->expects($this->any())->method('getIterator')->willReturnSelf(new \ArrayIterator([$this->roleUser]));
            $this->roleUser->expects($this->any())->method('getId')->willReturn(23);
            $this->roleUser->expects($this->any())->method('load')->willReturnSelf();
            $this->roleUser->expects($this->any())->method('delete')->willReturnSelf();

            $this->customer->expects($this->any())->method('setCustomAttribute')->willReturnSelf();
            $this->customerGroupSaveHelperMock->expects($this->any())
                ->method('updateCustomerAttribute')->willReturnSelf();

            $this->customerData->expects($this->any())->method('save')->willReturnSelf();

            $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setData')->willReturnSelf();
        
        $this->assertEquals($this->resultJson, $this->save->execute());
    }
    /**
     * testIsDeleteCustomer.
     *
     * @return void
     */
    public function testIsDeleteCustomer()
    {

        $this->companyContext->expects($this->any())->method('getCustomerId')->willReturn(1);
        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setData')->willReturnSelf();
        $expect = $this->save->customerFaceErrorMsg(1, $this->resultJson);
       $this->assertNotNull($expect);
    }
    /**
     * testIsDeleteCustomerIfNull.
     *
     * @return void
     */
    public function testIsDeleteCustomerIfNull()
    {

        $this->companyContext->expects($this->any())->method('getCustomerId')->willReturn(null);
        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setData')->willReturnSelf();
        $expect = $this->save->customerFaceErrorMsg(null, $this->resultJson);
       $this->assertNull($expect);
    }

     /**
     * testDeleteAllPermission.
     *
     */
    public function testDeleteAllPermission()
    {
        $this->roleUser->expects($this->once())->method('getCollection')->willReturn($this->roleUserCollection);
        $this->roleUserCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->roleUserCollection->expects($this->once())->method('getIterator')->willReturn(new \ArrayIterator([$this->roleUser]));
        $this->roleUser->expects($this->once())->method('getId')->willReturn(0);
        $this->roleUser->expects($this->once())->method('load')->willReturnSelf();
        $this->roleUser->expects($this->once())->method('delete')->willReturnSelf();
        $this->save->deleteAllPermission(0,0);
    }
}
