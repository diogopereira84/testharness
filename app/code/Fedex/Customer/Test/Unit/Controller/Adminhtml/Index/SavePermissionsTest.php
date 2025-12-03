<?php
/**
 * Copyright © FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Customer\Test\Unit\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session;
use Magento\Backend\Model\View\Result\Forward;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\Address\Mapper;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\Customer\Controller\Adminhtml\Index\SavePermissions;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Fedex\SelfReg\Model\EnhanceUserRoles;
use Magento\Customer\Controller\Adminhtml\Index\Save;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Fedex\SelfReg\Model\EnhanceRolePermission;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class SavePermissionsTest extends TestCase
{
    /**
     * @var SavePermissions
     */
    protected $model;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var ForwardFactory|MockObject
     */
    protected $resultForwardFactoryMock;

    /**
     * @var Forward|MockObject
     */
    protected $resultForwardMock;

    /**
     * @var PageFactory|MockObject
     */
    protected $resultPageFactoryMock;

    /**
     * @var Page|MockObject
     */
    protected $resultPageMock;

    /**
     * @var Config|MockObject
     */
    protected $pageConfigMock;

    /**
     * @var Title|MockObject
     */
    protected $pageTitleMock;

    /**
     * @var Session|MockObject
     */
    protected $sessionMock;

    /**
     * @var FormFactory|MockObject
     */
    protected $formFactoryMock;

    /**
     * @var DataObjectFactory|MockObject
     */
    protected $objectFactoryMock;

    /**
     * @var CustomerInterfaceFactory|MockObject
     */
    protected $customerDataFactoryMock;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    protected $customerRepositoryMock;

    /**
     * @var \Magento\Customer\Model\Customer\Mapper|MockObject
     */
    protected $customerMapperMock;

    /**
     * @var DataObjectHelper|MockObject
     */
    protected $dataHelperMock;

    /**
     * @var AuthorizationInterface|MockObject
     */
    protected $authorizationMock;

    /**
     * @var SubscriberFactory|MockObject
     */
    protected $subscriberFactoryMock;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManagerMock;

    /**
     * @var RedirectFactory|MockObject
     */
    protected $redirectFactoryMock;

    /**
     * @var AccountManagement|MockObject
     */
    protected $managementMock;

    /**
     * @var AddressInterfaceFactory|MockObject
     */
    protected $addressDataFactoryMock;

    /**
     * @var EmailNotificationInterface|MockObject
     */
    protected $emailNotificationMock;

    /**
     * @var Mapper|MockObject
     */
    protected $customerAddressMapperMock;

    /**
     * @var AddressRepositoryInterface|MockObject
     */
    protected $customerAddressRepositoryMock;

    /**
     * @var SubscriptionManagerInterface|MockObject
     */
    private $subscriptionManager;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    protected $companyManagementMock;

    /**
     * @var ResourceConnection|MockObject
     */
    protected $resourceConnectionMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var EnhanceUserRoles|MockObject
     */
    protected $enhanceUserRolesMock;

    /**
     * @var EnhanceRolePermission|MockObject
     */
    protected $enhanceRolePermissionMock;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleMock;

    /**
     * @var Save|MockObject
     */
    protected $saveMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultForwardFactoryMock = $this->getMockBuilder(
            ForwardFactory::class
        )->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->resultForwardMock = $this->getMockBuilder(Forward::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultPageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultPageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->addMethods(['setActiveMenu', 'addBreadcrumb'])
            ->onlyMethods(['getConfig'])
            ->getMock();
        $this->pageConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageTitleMock = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->addMethods(['unsCustomerFormData', 'setCustomerFormData'])
            ->getMock();
        $this->formFactoryMock = $this->getMockBuilder(FormFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectFactoryMock = $this->getMockBuilder(DataObjectFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->customerDataFactoryMock = $this->getMockBuilder(
            CustomerInterfaceFactory::class
        )->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerAddressRepositoryMock = $this->getMockBuilder(
            AddressRepositoryInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->customerMapperMock = $this->getMockBuilder(
            \Magento\Customer\Model\Customer\Mapper::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->customerAddressMapperMock = $this->getMockBuilder(
            Mapper::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->dataHelperMock = $this->getMockBuilder(
            DataObjectHelper::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->authorizationMock = $this->getMockBuilder(AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->subscriberFactoryMock = $this->getMockBuilder(SubscriberFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->subscriptionManager = $this->getMockForAbstractClass(SubscriptionManagerInterface::class);
        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->redirectFactoryMock = $this->getMockBuilder(RedirectFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->managementMock = $this->getMockBuilder(AccountManagement::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createAccount', 'validateCustomerStoreIdByWebsiteId'])
            ->getMock();
        $this->addressDataFactoryMock = $this->getMockBuilder(AddressInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->emailNotificationMock = $this->getMockBuilder(EmailNotificationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyManagementMock = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getByCustomerId'])
            ->getMockForAbstractClass();
        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConnection'])
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['critical'])
            ->getMockForAbstractClass();
        $this->enhanceUserRolesMock = $this->getMockBuilder(EnhanceUserRoles::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection'])
            ->getMock();
        $this->enhanceRolePermissionMock = $this->getMockBuilder(EnhanceRolePermission::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection'])
            ->getMock();
        $this->toggleMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getToggleConfigValue'])
            ->getMock();
        $this->saveMock = $this->getMockBuilder(Save::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute'])
            ->getMock();
        $website = $this->createPartialMock(\Magento\Store\Model\Website::class, ['getStoreIds']);
        $website->method('getStoreIds')
            ->willReturn([1]);
        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMockForAbstractClass();
        $storeManager->method('getWebsite')
            ->willReturn($website);

        $objectManager = new ObjectManager($this);

        $this->model = $objectManager->getObject(
            SavePermissions::class,
            [
                'resultForwardFactory' => $this->resultForwardFactoryMock,
                'resultPageFactory' => $this->resultPageFactoryMock,
                'formFactory' => $this->formFactoryMock,
                'objectFactory' => $this->objectFactoryMock,
                'customerDataFactory' => $this->customerDataFactoryMock,
                'customerRepository' => $this->customerRepositoryMock,
                'customerMapper' => $this->customerMapperMock,
                'dataObjectHelper' => $this->dataHelperMock,
                'subscriberFactory' => $this->subscriberFactoryMock,
                'coreRegistry' => $this->registryMock,
                'customerAccountManagement' => $this->managementMock,
                'addressDataFactory' => $this->addressDataFactoryMock,
                'request' => $this->requestMock,
                'session' => $this->sessionMock,
                'authorization' => $this->authorizationMock,
                'messageManager' => $this->messageManagerMock,
                'resultRedirectFactory' => $this->redirectFactoryMock,
                'addressRepository' => $this->customerAddressRepositoryMock,
                'addressMapper' => $this->customerAddressMapperMock,
                'subscriptionManager' => $this->subscriptionManager,
                'companyRepository' => $this->companyManagementMock,
                'resourceConnection' => $this->resourceConnectionMock,
                'logger' => $this->loggerMock,
                'roleUser' => $this->enhanceUserRolesMock,
                'rolePermissions' => $this->enhanceRolePermissionMock,
                'storeManager' => $storeManager,
            ]
        );
    }

    public function testExecute()
    {
        $postValue = [
            'customer' => [
                'entity_id' => '123',
                'code' => 'value',
                'coolness' => false,
                'disable_auto_group_change' => 'false',
            ]
        ];

        $savedData = [
            'entity_id' => 1,
            'darkness' => true,
            'name' => 'Name',
            CustomerInterface::DEFAULT_BILLING => false,
            CustomerInterface::DEFAULT_SHIPPING => false,
        ];

        $mergedData = [
            'entity_id' => 1,
            'darkness' => true,
            'name' => 'Name',
            'code' => 'value',
            'disable_auto_group_change' => 0,
            'confirmation' => false,
            'sendemail_store_id' => '1',
            'id' => 1,
        ];

        $this->requestMock->expects($this->atLeastOnce())
            ->method('getPostValue')
            ->willReturnMap(
                [
                    [null, null, $postValue],
                    [CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, null, $postValue['customer']],
                ]
            );

        $customerMock = $this->getMockForAbstractClass(CustomerInterface::class);
        $customerMock->method('getId')->willReturn(1);
        $this->customerDataFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($customerMock);
        $this->customerRepositoryMock->method('getById')
            ->with(1)
            ->willReturn($customerMock);
        $this->customerMapperMock->expects($this->any())
            ->method('toFlatArray')
            ->with($customerMock)
            ->willReturn($savedData);
        $this->dataHelperMock->expects($this->any())
            ->method('populateWithArray')
            ->willReturnMap(
                [
                    [
                        $customerMock,
                        $mergedData, CustomerInterface::class,
                        $this->dataHelperMock
                    ],
                ]
            );

        $this->customerRepositoryMock->expects($this->any())
            ->method('save')
            ->with($customerMock)
            ->willReturnSelf();
        $customerMock->expects($this->any())->method('getEmail')->willReturn('testing@test.com');
        $customerMock->expects($this->any())
            ->method('getAddresses')
            ->willReturn([]);
        $redirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectFactoryMock->expects($this->any())
            ->method('create')
            ->with([])
            ->willReturn($redirectMock);

        $redirectMock->expects($this->any())
            ->method('setPath')
            ->willReturn(true);
        $result = $this->model->execute();

        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testSetPermissions()
    {
        $permissionIds = [1, 2, 3];
        $customerId = 123;

        $companyDataMock = $this->createMock(\Magento\Company\Api\Data\CompanyInterface::class);
        $connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->companyManagementMock->expects($this->any())
            ->method('getByCustomerId')
            ->willReturn($companyDataMock);

        $companyDataMock->expects($this->any())
            ->method('getId')->willReturn(789);

        $this->resourceConnectionMock->expects($this->any())
            ->method('getConnection')->willReturn($connectionMock);

        $connectionMock->expects($this->any())
            ->method('insertOnDuplicate')
            ->willReturn(1);

        $this->model->setPermissions($permissionIds, $customerId, null);
    }

    public function testGetManageUsersEmailPermissionIds()
    {
        $collectionMock = $this->createMock(AbstractCollection::class);
        $this->enhanceRolePermissionMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($collectionMock);
        $collectionMock->expects($this->any())
            ->method('getIterator')->willReturn(new \ArrayIterator([]));
        
        $this->model->getManageUsersEmailPermissionIds();
    }
}
