<?php

namespace Fedex\SelfReg\Test\Unit\Helper;

use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\SelfReg\Model\CompanySelfRegData;
use Fedex\SelfReg\Model\ResourceModel\CompanySelfRegData\Collection;
use Magento\Company\Model\Company;
use Magento\Company\Model\CompanyFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Fedex\SelfReg\Model\EnhanceUserRoles;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SelfReg\Model\EnhanceRolePermission;
use Fedex\SelfReg\Model\EnhanceRolePermissionFactory;
use Fedex\SelfReg\Model\EnhanceUserRolesFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Data\Customer as CustomerRepo;
use Fedex\Base\Helper\Auth;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;

class SelfRegTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $toggleConfigMock;
    protected $customerSessionMock;
    protected $customerFactoryMock;
    protected $customerMock;
    protected $_customerSessionFactoryMock;
    protected $selfRegMock;
    protected $selfRegCollectionMock;
    protected $companyFactory;
    protected $companyMock;
    protected $companyCollection;
    protected $storeManagerInterface;
    protected $websiteInterface;
    protected $storeMock;
    protected $punchoutHelperMock;
    protected $ssoHelper;
    protected $companyRepository;
    protected $companyInterface;
    protected $selfRegEmailMock;
    protected $enhanceUserRoles;
    protected $sdeHelperMock;
    /**
     * @var (\Fedex\SelfReg\Model\EnhanceRolePermissionFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $enhancRolePermissionFactory;
    /**
     * @var (\Fedex\Company\Api\Data\ConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $configInterfaceMock;
    /**
     * @var (\Fedex\SelfReg\Model\EnhanceUserRolesFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $enhanceUserRolesFactory;
    /**
     * @var (\Fedex\SelfReg\Model\EnhanceRolePermission & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $enhancRolePermission;
    protected $data;
    /**
     * @var profileData
     */
    protected $profileData = [
        'address' => [
            'uuId' => 'YY12135', 'firstName' => 'Test Fname', 'lastName' => 'Test Lname',
            'email' => 'test@gmail.com',
            'countryCode' => 'US',
            'postalCode' => '75024',
            'city' => 'Plano',
            'stateOrProvinceCode' => '169',
            'contactNumber' => '999999',
            'ext' => '',
            'company' => 'TEST COMP',
            'streetLines' => [
                1 => 'Legacy Honey',
            ],
        ],
    ];

    protected $customerRepositoryInterface;
    protected $customerRepo;
    protected $attributeValue;
    protected $searchCriteriaBuilderMock;
    protected $searchCriteriaMock;
    protected Auth|MockObject $baseAuthMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->customerRepositoryInterface = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();
        $this->customerRepo = $this->getMockBuilder(CustomerRepo::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFirstname','getLastname','getCustomAttribute'])
            ->getMock();
        $this->attributeValue = $this->getMockBuilder(\Magento\Framework\Api\AttributeValue::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        
        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setCompanyName',
                'setCommunicationUrl',
                'setCustomerAsLoggedIn',
                'setBackUrl',
                'getCustomer',
                'getCustomerCompany',
                'isLoggedIn',
                'setLastCustomerId',
                'logout',
                'setCustomerCompany',
                'setSelfRegLoginError',
                'getId',
                'getOndemandCompanyInfo'
            ])
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->customerFactoryMock = $this->getMockBuilder(CustomerFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getId', 'setCustomerUuidValue',
                'setCustomerCanvaId', 'load', 'setWebsiteId',
                'setFirstname', 'setLastname', 'setSecondaryEmail',
                'getSecondaryEmail', 'setFclProfileContactNumber',
                'setContactNumber', 'setContactExt', 'save', 'loadByEmail',
                'getFirstName', 'getLastName', 'getName', 'getEmail', 'getData'
            ])
            ->getMock();

        $this->_customerSessionFactoryMock = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->selfRegMock = $this->getMockBuilder(CompanySelfRegData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'getData'])
            ->getMock();

        $this->selfRegCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getFirstItem'])
            ->getMock();

        $this->companyFactory = $this->getMockBuilder(CompanyFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','getStorefrontLoginMethodOption'])
            ->getMock();

        $this->companyMock = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getCollection', 'getCompanyName', 'getSuperUserId', 'load', 'getData'])
            ->getMock();

        $this->companyCollection = $this->getMockBuilder(\Magento\Company\Model\ResourceModel\Company\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIterator', 'addFieldToFilter', 'getFirstItem'])
            ->getMock();

        $this->storeManagerInterface = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore', 'getWebsite'])
            ->getMockForAbstractClass();

        $this->websiteInterface = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsiteId'])
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode', 'getBaseUrl', 'getUrl'])
            ->getMock();

        $this->punchoutHelperMock = $this->getMockBuilder(\Fedex\Punchout\Helper\Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['autoRegister', 'isActiveCustomer'])
            ->getMock();

        $this->ssoHelper = $this->getMockBuilder(\Fedex\SSO\Helper\Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProfileByProfileApi', 'generateUniqueCanvaId', 'getCustomerIdByUuid', 'saveAddress'])
            ->getMock();

        $this->companyRepository = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getByCustomerId','getStorefrontLoginMethodOption'])
            ->getMockForAbstractClass();

        $this->companyInterface = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','getStorefrontLoginMethodOption', 'getData'])
            ->getMockForAbstractClass();

        $this->selfRegEmailMock = $this->getMockBuilder(\Fedex\SelfReg\Helper\Email::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendPendingEmail'])
            ->getMock();

        $this->enhanceUserRoles = $this->getMockBuilder(EnhanceUserRoles::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'addFieldToFilter', 'getSelect', 'join', 'getColumnValues','getData','where','distinct'])
            ->getMock();

        $this->sdeHelperMock = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(['getIsSdeStore'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->enhancRolePermissionFactory = $this->getMockBuilder(EnhanceRolePermissionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','getCollection', 'addFieldToFilter', 'getSelect', 'join', 'getColumnValues'])
            ->getMock();

        $this->configInterfaceMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','getCollection', 'addFieldToFilter', 'getSelect', 'join', 'getColumnValues'])
            ->getMockForAbstractClass();

        $this->enhanceUserRolesFactory = $this->getMockBuilder(EnhanceUserRolesFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','getCollection', 'addFieldToFilter', 'getSelect', 'join', 'getColumnValues'])
            ->getMock();

        $this->enhancRolePermission = $this->getMockBuilder(EnhanceRolePermission::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'addFieldToFilter', 'getSelect', 'join', 'getColumnValues','getData'])
            ->getMock();
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','addFilter'])
            ->getMock();
        $this->searchCriteriaMock = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilderMock->expects($this->any())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->any())->method('create')->willReturn($this->searchCriteriaMock);

        $objectManagerHelper = new ObjectManager($this);
        $this->data = $objectManagerHelper->getObject(
            SelfReg::class,
            [
                'context' => $this->contextMock,
                'toggleConfig' => $this->toggleConfigMock,
                'selfReg' => $this->selfRegMock,
                'customerSessionFactory' => $this->_customerSessionFactoryMock,
                'customerSession' => $this->customerSessionMock,
                'storeManagerInterface' => $this->storeManagerInterface,
                'companyFactory' => $this->companyFactory,
                'ssoHelper' => $this->ssoHelper,
                'punchoutHelper' => $this->punchoutHelperMock,
                'customerModelFactory' => $this->customerFactoryMock,
                'companyRepository' => $this->companyRepository,
                'selfRegEmail' => $this->selfRegEmailMock,
                'sdeHelper' => $this->sdeHelperMock,
                'configInterface' => $this->configInterfaceMock,
                'authHelper' => $this->baseAuthMock,
                'rolePermissionsFactory'=>$this->enhancRolePermissionFactory,
                'enhanceUserRolesFactory'=>$this->enhanceUserRolesFactory,
                'enhanceUserRoles' => $this->enhanceUserRoles,
                'customerRepositoryInterface' => $this->customerRepositoryInterface,
                'searchCriteriaBuilder'=>$this->searchCriteriaBuilderMock
            ]
        );
    }

    /**
     * @test testGetCompanyUserPermission
     */
    public function testGetCompanyUserPermission()
    {
        $companyId = 8;
        $permission = ['manage_user'];
   
        $this->expectException(LocalizedException::class);

        $this->enhanceUserRoles->expects($this->any())
            ->method('getCollection')
            ->willReturnSelf();

        $this->enhanceUserRoles->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->enhanceUserRoles->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();

        $this->enhanceUserRoles->expects($this->any())
            ->method('join')
            ->willReturnSelf();

        $this->enhanceUserRoles->expects($this->any())
            ->method('getColumnValues')
            ->willReturnSelf();

        $this->enhanceUserRoles->expects($this->any())
            ->method('where')
            ->willReturnSelf();

        $this->assertNotNull($this->data->getCompanyUserPermission($companyId, $permission));
    }

    public function isSelfRegCustomer($selfRegData)
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->_customerSessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(23);

        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->selfRegMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->selfRegCollectionMock);

        $this->selfRegCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->selfRegCollectionMock);

        $this->selfRegCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->selfRegMock);

        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/me/');

        $this->companyFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->companyMock);

        $this->companyMock->expects($this->any())
            ->method('load')
            ->with(23)
            ->willReturnSelf();

        $this->companyMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->companyCollection);

        $this->companyCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->companyCollection->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->companyMock);

        $this->companyMock->expects($this->any())
            ->method('getId')
            ->willReturn(23);

        $this->selfRegMock->expects($this->any())
            ->method('getData')
            ->willReturn($selfRegData);

        $this->companyRepository->expects($this->any())
            ->method('getByCustomerId')
            ->willReturn($this->companyInterface);

        $this->companyInterface->expects($this->any())
            ->method('getId')
            ->willReturn(23);

        $this->companyMock->expects($this->any())
            ->method('getData')
            ->with('self_reg_data')
            ->willReturn($selfRegData);
    }

    /**
     * @test testIsSelfRegCustomer
     */
    public function testIsSelfRegCustomer()
    {

        $selfRegData = '{"error_message":"",
            "domains":"",
                "self_reg_login_method":"registered_user",
                    "enable_selfreg":"1"}';
        $this->isSelfRegCustomer($selfRegData);
        $this->assertEquals(true, $this->data->isSelfRegCustomer());
    }

    /**
     * @test testIsSelfRegCompanywithWlgn
     */
    public function testIsSelfRegCompanywithWlgn()
    {
        $selfRegData = '{"error_message":"",
                            "domains":"",
                                "self_reg_login_method":"registered_user",
                                    "enable_selfreg":"1"}';
        // B-1515570
        $ondemandCompanyInfo = [
            'url_extension' => true,
            'company_type' => 'sde',
            'company_data' => ['storefront_login_method_option'=>'commercial_store_wlgn']
        ];

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->_customerSessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(23);

        $this->customerSessionMock->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandCompanyInfo);

        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->selfRegMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->selfRegCollectionMock);

        $this->selfRegCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->selfRegCollectionMock);

        $this->selfRegCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->selfRegMock);

        $this->selfRegMock->expects($this->any())
            ->method('getData')
            ->willReturn($selfRegData);

        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/me/');

        $this->companyFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->companyMock);

        $this->companyMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->companyCollection);

        $this->companyCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->companyCollection->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->companyMock);

        $this->companyMock->expects($this->any())
            ->method('getId')
            ->willReturn(6);

        $this->companyMock->expects($this->any())
            ->method('load')
            ->with(6)
            ->willReturnSelf();

        $this->companyMock->expects($this->any())
            ->method('getData')
            ->with('self_reg_data')
            ->willReturn($selfRegData);

        $this->assertEquals(true, $this->data->isSelfRegCompany());
    }

    /**
     * @test testIsSelfRegCompany
     */
    public function testIsSelfRegCompany()
    {
        $selfRegData = '{"error_message":"",
							"domains":"",
								"self_reg_login_method":"registered_user",
									"enable_selfreg":"1"}';
		// B-1515570
		$ondemandCompanyInfo = [
            'url_extension' => true,
            'company_type' => 'selfreg'
        ];

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->_customerSessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(23);

		$this->customerSessionMock->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandCompanyInfo);


        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->selfRegMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->selfRegCollectionMock);

        $this->selfRegCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->selfRegCollectionMock);

        $this->selfRegCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->selfRegMock);

        $this->selfRegMock->expects($this->any())
            ->method('getData')
            ->willReturn($selfRegData);

        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/me/');

        $this->companyFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->companyMock);

        $this->companyMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->companyCollection);

        $this->companyCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->companyCollection->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->companyMock);

        $this->companyMock->expects($this->any())
            ->method('getId')
            ->willReturn(6);

        $this->companyMock->expects($this->any())
            ->method('load')
            ->with(6)
            ->willReturnSelf();

        $this->companyMock->expects($this->any())
            ->method('getData')
            ->with('self_reg_data')
            ->willReturn($selfRegData);

        $this->assertEquals(true, $this->data->isSelfRegCompany());
    }
    /**
     * @test testIsSelfRegCompanyWihoutCompanyData
     */
    public function testIsSelfRegCompanyWihoutCompanyData()
    {
        $selfRegData = '';

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->_customerSessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(23);

        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->selfRegMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->selfRegCollectionMock);

        $this->selfRegCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->selfRegCollectionMock);

        $this->selfRegCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->selfRegMock);

        $this->selfRegMock->expects($this->any())
            ->method('getData')
            ->willReturn($selfRegData);

        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/me/');

        $this->companyFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->companyMock);

        $this->companyMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->companyCollection);

        $this->companyCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->companyCollection->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->companyMock);

        $this->companyMock->expects($this->any())
            ->method('getId')
            ->willReturn(6);

        $this->companyMock->expects($this->any())
            ->method('load')
            ->with(6)
            ->willReturnSelf();

        $this->companyMock->expects($this->any())
            ->method('getData')
            ->with('self_reg_data')
            ->willReturn($selfRegData);

        $this->assertEquals(false, $this->data->isSelfRegCompany());
    }

    /**
     * @test testToggleUserRolePermissionEnable
     */
    public function testToggleUserRolePermissionEnable()
    {
         $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->assertEquals(false, $this->data->toggleUserRolePermissionEnable());
    }

    /**
     * @test testSelfRegWlgnLogin
     */

    public function testSelfRegWlgnLoginAuthToggleOn()
    {
        $testCompanyId = 6;
        $endUrl = 'https://api.test.office.fedex.com/customer/fedexoffice/v1/profiles';
        $fdxLogin = 'ssotest-cos2.a342.5086d6d70b8139b248d0bee7b9715755';
        $profileDetail = $this->profileData;

        $customerData = [
            'entity_id' => '1',
            'firstname' => 'Test Fname',
            'lastname' => 'Test Lname',
            'email' => 'test@gmail.com',
        ];

        $ondemandCompanyInfo = [
            'url_extension' => true,
            'company_type' => 'selfreg',
            'company_data' => ['entity_id' => 5,'storefront_login_method_option'=>'commercial_store_wlgn']
        ];

        $selfRegData = '{"error_message":"",
							"domains":"",
								"self_reg_login_method":"admin_approval",
									"enable_selfreg":"1"}';

        $this->expectException(LocalizedException::class);
        
        $this->_customerSessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->ssoHelper->expects($this->any())->method('getProfileByProfileApi')->willReturn($profileDetail);

        $this->storeManagerInterface->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/me/');

        $this->storeManagerInterface->expects($this->any())->method('getWebsite')->willReturn($this->websiteInterface);
        $this->websiteInterface->expects($this->any())->method('getWebsiteId')->willReturn(1);

        $this->ssoHelper->expects($this->any())->method('getCustomerIdByUuid')->willReturn(false);

        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        //getCompanyIdByWebsiteUrl
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->customerSessionMock->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandCompanyInfo);
        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getCollection')->willReturn($this->companyCollection);
        $this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('getFirstItem')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getId')->willReturn($testCompanyId);
        $this->companyMock->expects($this->any())->method('load')->with($testCompanyId)->willReturnSelf();
        $this->companyMock->expects($this->any())->method('getData')->with('self_reg_data')->willReturn($selfRegData);

        // getSelfRegSettingByCompanyId
        $this->selfRegMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->selfRegCollectionMock);

        $this->selfRegCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->selfRegCollectionMock);

        $this->selfRegCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->selfRegMock);

        $this->selfRegMock->expects($this->any())
            ->method('getData')
            ->willReturn($selfRegData);

        $this->baseAuthMock->method('isLoggedIn')
            ->willReturn(true);
        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())
            ->method('getId')
            ->willReturn(243);
        $this->customerSessionMock->expects($this->any())
            ->method('logout')
            ->willReturnSelf();
        $this->customerSessionMock->expects($this->any())
            ->method('setLastCustomerId')
            ->willReturnSelf();

        $this->punchoutHelperMock->expects($this->any())
            ->method('autoRegister')
            ->willReturn($this->customerMock);

        $this->ssoHelper->expects($this->any())->method('generateUniqueCanvaId')->willReturn('UNIQUECANVAID');

        $this->customerMock->expects($this->any())->method('load')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setCustomerCanvaId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setCustomerUuidValue')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setSecondaryEmail')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setFclProfileContactNumber')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setContactNumber')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setContactExt')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('save')->willReturnSelf();

        $this->ssoHelper->expects($this->any())->method('saveAddress')->willReturn(null);


        $this->punchoutHelperMock
            ->expects($this->any())
            ->method('isActiveCustomer')
            ->withConsecutive([], [], [])
            ->willReturnOnConsecutiveCalls(true, false, false);

        $this->customerMock->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('loadByEmail')->willReturnSelf();
        $this->companyMock->expects($this->any())->method('getCompanyName')->willReturn('TEST COMPANY');

        $this->customerSessionMock->expects($this->any())->method('setCustomerCompany')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('setBackUrl')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('setCommunicationUrl')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('setCompanyName')->willReturnSelf();
        $this->baseAuthMock->method('isLoggedIn')->willReturn(true);

        $this->customerMock->expects($this->any())->method('getData')->willReturn($customerData);

        // send email notification to admin
        $this->companyMock->expects($this->any())->method('getSuperUserId')->willReturn('63');

        $this->customerMock->expects($this->any())->method('getId')->willReturn(23);
        $this->customerMock->expects($this->any())->method('getName')->willReturn('TEST CUSTOMER');
        $this->customerMock->expects($this->any())->method('getEmail')->willReturn('test@gmail.com');

        $this->selfRegEmailMock->expects($this->any())->method('sendPendingEmail')->willReturn(null);

        $companyId = 8;
        $permission = ['manage_user'];

        $this->enhanceUserRoles->expects($this->any())
            ->method('getCollection')
            ->willReturnSelf();

        $this->enhanceUserRoles->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->enhanceUserRoles->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();

        $this->enhanceUserRoles->expects($this->any())
            ->method('join')
            ->willReturnSelf();

        $this->enhanceUserRoles->expects($this->any())
            ->method('where')
            ->willReturnSelf();

        $this->enhanceUserRoles->expects($this->any())
            ->method('getColumnValues')
            ->willReturn([23,45]);

        $this->customerRepositoryInterface->expects($this->any())
            ->method('getById')
            ->willReturn($this->customerRepo);
        $this->customerRepo->expects($this->any())
            ->method('getFirstname')
            ->willReturn("Test");
        $this->customerRepo->expects($this->any())
            ->method('getLastname')
            ->willReturn("Test");
        $this->customerRepo->expects($this->any())
            ->method('getCustomAttribute')
            ->willReturn($this->attributeValue);
        $this->attributeValue->expects($this->any())
            ->method('getValue')
            ->willReturn('test@test.com');


        $this->assertNotNull($this->data->getCompanyUserPermission($companyId, $permission));

        $response = $this->data->selfRegWlgnLogin($endUrl, $fdxLogin);
        $this->assertNotNull($response);

        $response = $this->data->selfRegWlgnLogin($endUrl, $fdxLogin);
        $this->assertNotNull($response);

        $response = $this->data->selfRegWlgnLogin($endUrl, $fdxLogin);
        $this->assertNotNull($response);
    }

    /**
     * @test testSelfRegWlgnLogin for company admin
     */

    public function testSelfRegLoginForCompanyAdmin()
    {
        $testCompanyId = 6;
        $testCustId = 85;
        $endUrl = 'https://api.test.office.fedex.com/customer/fedexoffice/v1/profiles';
        $fdxLogin = 'ssotest-cos2.a342.5086d6d70b8139b248d0bee7b9715755';
        $profileDetail = $this->profileData;

        $customerData = [
            'entity_id' => '1',
            'firstname' => 'Test Fname',
            'lastname' => 'Test Lname',
            'email' => 'test@gmail.com',
        ];

		$ondemandCompanyInfo = [
            'url_extension' => true,
            'company_type' => 'selfreg',
            'company_data' => ['entity_id' => 5,'storefront_login_method_option'=>'commercial_store_wlgn']
        ];

        $selfRegData = '{"error_message":"",
							"domains":"",
								"self_reg_login_method":"admin_approval",
									"enable_selfreg":"1"}';

        $this->_customerSessionFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->customerSessionMock);

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->ssoHelper->expects($this->any())->method('getProfileByProfileApi')->willReturn($profileDetail);

        $this->storeManagerInterface->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/me/');

        $this->storeManagerInterface->expects($this->any())->method('getWebsite')
            ->willReturn($this->websiteInterface);
        $this->websiteInterface->expects($this->any())->method('getWebsiteId')->willReturn(1);

        $this->ssoHelper->expects($this->any())->method('getCustomerIdByUuid')->willReturn(false);

        //getCompanyIdByWebsiteUrl
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->customerSessionMock->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandCompanyInfo);

        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getCollection')->willReturn($this->companyCollection);
        $this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('getFirstItem')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getId')->willReturn($testCompanyId);
        $this->companyMock->expects($this->any())->method('load')->with($testCompanyId)->willReturnSelf();
        $this->companyMock->expects($this->any())->method('getData')->with('self_reg_data')->willReturn($selfRegData);

        //checkCustomerIsCompanyAdmin
        $this->customerMock->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('loadByEmail')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('getId')->willReturn($testCustId);
        $this->companyMock->expects($this->any())->method('getSuperUserId')->willReturn($testCustId);

        //customer login
        $this->customerSessionMock->expects($this->any())->method('setCustomerAsLoggedIn')->willReturn($this->customerMock);
        $this->customerSessionMock->expects($this->any())->method('setCustomerCompany')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('setBackUrl')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('setCommunicationUrl')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('setCompanyName')->willReturnSelf();
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->customerSessionMock->expects($this->any())->method('isLoggedIn')->willReturn(true);

        $response = $this->data->selfRegWlgnLogin($endUrl, $fdxLogin);
        $expectedResult = 'Customer logged-in successfully.';
        $this->assertNotEquals($expectedResult, $response['msg']);
    }

    /**
     * @test testSelfRegRegistrationFail
     */

    public function testSelfRegRegistrationFail()
    {
        $testCompanyId = 6;
        $endUrl = 'https://api.test.office.fedex.com/customer/fedexoffice/v1/profiles';
        $fdxLogin = 'ssotest-cos2.a342.5086d6d70b8139b248d0bee7b9715755';
        $profileDetail = $this->profileData;

		$ondemandCompanyInfo = [
            'url_extension' => true,
            'company_type' => 'selfreg',
            'company_data' => ['entity_id' => 5]
        ];

        $regError = ['error' => 1, 'token' => '', 'msg' => 'Registration Error'];
        $selfRegData = '{"error_message":"","domains":"",
							"self_reg_login_method":"admin_approval",
								"enable_selfreg":"1"}';

        $this->_customerSessionFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->customerSessionMock);

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->ssoHelper->expects($this->any())->method('getProfileByProfileApi')->willReturn($profileDetail);

        $this->storeManagerInterface->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/me/');

        $this->storeManagerInterface->expects($this->any())->method('getWebsite')->willReturn($this->websiteInterface);
        $this->websiteInterface->expects($this->any())->method('getWebsiteId')->willReturn(1);

        $this->ssoHelper->expects($this->any())->method('getCustomerIdByUuid')->willReturn(false);
        $this->customerMock->expects($this->any())->method('setCustomerUuidValue')->willReturnSelf();

        //getCompanyIdByWebsiteUrl
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);

        $this->customerSessionMock->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandCompanyInfo);

        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getCollection')
            ->willReturn($this->companyCollection);
        $this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('getFirstItem')
            ->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getId')->willReturn($testCompanyId);
        $this->companyMock->expects($this->any())->method('load')->with($testCompanyId)->willReturnSelf();
        $this->companyMock->expects($this->any())->method('getData')->with('self_reg_data')->willReturn($selfRegData);
        $this->companyMock->expects($this->any())->method('getSuperUserId')->willReturn('23');

        //checkCustomerIsCompanyAdmin
        $this->customerMock->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('loadByEmail')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('getId')->willReturn('234');

        // getSettingByCompanyId
        $this->selfRegMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->selfRegCollectionMock);

        $this->selfRegCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->selfRegCollectionMock);

        $this->selfRegCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->selfRegMock);

        $this->selfRegMock->expects($this->any())
            ->method('getData')
            ->willReturn($selfRegData);

        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->punchoutHelperMock->expects($this->any())
            ->method('autoRegister')
            ->willReturn($regError);

        $response = $this->data->selfRegWlgnLogin($endUrl, $fdxLogin);
        $this->assertEquals(1, $response['error']);
    }

    /**
     * @test testCompanyAdminSuperUserId
     */
    public function testCompanyAdminSuperUserId()
    {
        $this->_customerSessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);
        $this->customerSessionMock->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(23);
        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getCollection')
            ->willReturn($this->companyCollection);
        $this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('getFirstItem')
            ->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getSuperUserId')->willReturn('8');
        $this->assertEquals('8', $this->data->companyAdminSuperUserId());
    }

    /**
     * @test testIsSelfRegCustomerAdmin
     */

    public function testIsSelfRegCustomerAdmin()
    {
        $selfRegData = '{"error_message":"",
            "domains":"",
                "self_reg_login_method":"registered_user",
                    "enable_selfreg":"1"}';
        $this->isSelfRegCustomer($selfRegData);
        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->_customerSessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())->method('getId')->willReturn('23');

        $this->customerSessionMock->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(23);

        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getCollection')
            ->willReturn($this->companyCollection);

        $this->companyRepository->expects($this->any())
            ->method('getByCustomerId')
            ->willReturn($this->companyInterface);

        $this->companyInterface->expects($this->any())
            ->method('getStorefrontLoginMethodOption')
            ->willReturn('commercial_store_epro');

        $this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('getFirstItem')
            ->willReturn($this->companyMock);

        $this->companyMock->expects($this->any())->method('getSuperUserId')->willReturn('23');

        $response = $this->data->isSelfRegCustomerAdmin();
        $this->assertEquals(true, $response);
    }


    /**
     * @test testIsAdminApprovedEnabled
     */

    public function testIsAdminApprovedEnabled()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $selfRegData = '{"error_message":"",
            "domains":"",
                "self_reg_login_method":"admin_approval",
                    "enable_selfreg":"1"}';

        $this->isSelfRegCustomer($selfRegData);

        $response = $this->data->isAdminApprovedEnabled(23);
        $this->assertEquals(true, $response);
    }

    /**
     * @test testIsSelfRegCustomerAdminNegative
     */

    public function testIsSelfRegCustomerAdminNegative()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);

        $this->companyRepository->expects($this->any())
            ->method('getByCustomerId')
            ->willReturn($this->companyInterface);

        $this->companyInterface->expects($this->any())
            ->method('getStorefrontLoginMethodOption')
            ->willReturn('commercial_store_epros');

        $selfRegData = '{"error_message":"",
            "domains":"",
                "self_reg_login_method":"admin_approval",
                    "enable_selfreg":"1"}';

        $this->isSelfRegCustomer($selfRegData);

        $response = $this->data->isSelfRegCustomerAdmin();
        $this->assertEquals(true, $response);
    }

    /**
     * @test testSelfRegDomainRegistrationFail
     */

    public function testSelfRegDomainRegistrationFail()
    {
        $testCompanyId = 6;
        $endUrl = 'https://api.test.office.fedex.com/customer/fedexoffice/v1/profiles';
        $fdxLogin = 'ssotest-cos2.a342.5086d6d70b8139b248d0bee7b9715755';
        $profileDetail = $this->profileData;

        $selfRegData = '{"error_message":"","domains":"www.google.com,www.amazom.com",
							"self_reg_login_method":"domain_registration",
								"enable_selfreg":"1"}';

        $this->_customerSessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->ssoHelper->expects($this->any())->method('getProfileByProfileApi')->willReturn($profileDetail);

        $this->storeManagerInterface->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/me/');

        $this->storeManagerInterface->expects($this->any())->method('getWebsite')
            ->willReturn($this->websiteInterface);
        $this->websiteInterface->expects($this->any())->method('getWebsiteId')->willReturn(1);

        $this->ssoHelper->expects($this->any())->method('getCustomerIdByUuid')->willReturn(true);

        //getCompanyIdByWebsiteUrl
        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getCollection')->willReturn($this->companyCollection);
        $this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('getFirstItem')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getId')->willReturn($testCompanyId);
        $this->companyMock->expects($this->any())->method('load')->with($testCompanyId)->willReturnSelf();
        $this->companyMock->expects($this->any())->method('getData')->with('self_reg_data')->willReturn($selfRegData);

        // getSettingByCompanyId
        $this->selfRegMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->selfRegCollectionMock);

        $this->selfRegCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->selfRegCollectionMock);

        $this->selfRegCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->selfRegMock);

        $this->selfRegMock->expects($this->any())
            ->method('getData')
            ->willReturn($selfRegData);

        $this->ssoHelper->expects($this->any())->method('generateUniqueCanvaId')
            ->willReturn('UNIQUECANVAID');

        $this->customerMock->expects($this->any())->method('load')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setCustomerCanvaId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setCustomerUuidValue')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setSecondaryEmail')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setFclProfileContactNumber')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setContactNumber')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setContactExt')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('save')->willReturnSelf();

        $this->ssoHelper->expects($this->any())->method('saveAddress')->willReturn(null);

        $this->customerMock->expects($this->any())->method('getSecondaryEmail')->willReturn('abc@gmail.com');

        $response = $this->data->selfRegWlgnLogin($endUrl, $fdxLogin);
        $this->assertEquals(1, $response['error']);
    }

    /**
     * @test testSelfRegErrorInLogin
     */

    public function testSelfRegErrorInLogin()
    {
        $testCompanyId = 6;
        $endUrl = 'https://api.test.office.fedex.com/customer/fedexoffice/v1/profiles';
        $fdxLogin = 'ssotest-cos2.a342.5086d6d70b8139b248d0bee7b9715755';
        $profileDetail = $this->profileData;

        $selfRegData = '{"error_message":"","domains":"",
				"self_reg_login_method":"admin_approval","enable_selfreg":"1"}';

        $this->_customerSessionFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->customerSessionMock);

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->ssoHelper->expects($this->any())->method('getProfileByProfileApi')->willReturn($profileDetail);

        $this->storeManagerInterface->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/me/');

        $this->storeManagerInterface->expects($this->any())
            ->method('getWebsite')->willReturn($this->websiteInterface);
        $this->websiteInterface->expects($this->any())->method('getWebsiteId')->willReturn(1);

        $this->ssoHelper->expects($this->any())->method('getCustomerIdByUuid')->willReturn(true);

        //getCompanyIdByWebsiteUrl
        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getCollection')
            ->willReturn($this->companyCollection);
        $this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('getFirstItem')
            ->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getId')->willReturn($testCompanyId);
        $this->companyMock->expects($this->any())->method('load')->with($testCompanyId)->willReturnSelf();
        $this->companyMock->expects($this->any())->method('getData')->with('self_reg_data')->willReturn($selfRegData);

        // getSettingByCompanyId
        $this->selfRegMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->selfRegCollectionMock);

        $this->selfRegCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->selfRegCollectionMock);

        $this->selfRegCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->selfRegMock);

        $this->selfRegMock->expects($this->any())
            ->method('getData')
            ->willReturn($selfRegData);

        $this->ssoHelper->expects($this->any())->method('generateUniqueCanvaId')->willReturn('UNIQUECANVAID');

        $this->customerMock->expects($this->any())->method('load')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setCustomerCanvaId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setCustomerUuidValue')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setSecondaryEmail')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setFclProfileContactNumber')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setContactNumber')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setContactExt')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('save')->willReturnSelf();

        $this->ssoHelper->expects($this->any())->method('saveAddress')->willReturn(null);
        $this->punchoutHelperMock->expects($this->any())
            ->method('isActiveCustomer')
            ->willReturn(true);

        $this->customerMock->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('loadByEmail')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('getId')->willReturn(234);
        $this->companyMock->expects($this->any())->method('getCompanyName')->willReturn('TEST COMPANY');

        $this->customerSessionMock->expects($this->any())->method('setCustomerCompany')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('setBackUrl')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('setCommunicationUrl')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('setCompanyName')->willReturnSelf();

        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(false);
        $response = $this->data->selfRegWlgnLogin($endUrl, $fdxLogin);

        $this->assertEquals(1, $response['error']);
    }

    /**
     * @test testSelfRegProfileApiIssue
     */

    public function testSelfRegProfileApiIssue()
    {
        $testCompanyId = 6;
        $endUrl = 'https://api.test.office.fedex.com/customer/fedexoffice/v1/profiles';
        $fdxLogin = 'ssotest-cos2.a342.5086d6d70b8139b248d0bee7b9715755';
        $profileDetail = ['address' => []];

        $this->_customerSessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->ssoHelper->expects($this->any())->method('getProfileByProfileApi')->willReturn($profileDetail);
        $this->storeManagerInterface->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/me/');

        $this->storeManagerInterface->expects($this->any())->method('getWebsite')->willReturn($this->websiteInterface);
        $this->websiteInterface->expects($this->any())->method('getWebsiteId')->willReturn(1);

        $response = $this->data->selfRegWlgnLogin($endUrl, $fdxLogin);
       $this->assertNotNull($response);
    }

    /**
     * @test testSelfRegException
     */

    public function testSelfRegException()
    {
        $exception = new \Exception();
        $testCompanyId = 6;
        $endUrl = 'https://api.test.office.fedex.com/customer/fedexoffice/v1/profiles';
        $fdxLogin = 'ssotest-cos2.a342.5086d6d70b8139b248d0bee7b9715755';
        $profileDetail = ['address' => [
            'uuId' => 'YY12135',
            'email' => 'test@gmail.com',
        ]];

        $this->_customerSessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);
        $this->customerFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerMock);
        $this->ssoHelper->expects($this->any())->method('getProfileByProfileApi')
            ->willReturn($profileDetail)->willThrowException($exception);

        $response = $this->data->selfRegWlgnLogin($endUrl, $fdxLogin);
        $this->assertNotNull($response);
    }

    /**
     * @test testValidateDomain
     */

    public function testValidateDomain()
    {
        $customerEmail = 'test@gmail.com';
        $allowedDomains = 'gmail.com,osv.com,abc.com';
        $response = $this->data->validateDomain($customerEmail, $allowedDomains);
        $this->assertEquals(true, $response);
    }

    /**
     * @test testValidateDomainWithNoValidation
     */

    public function testValidateDomainWithNoValidation()
    {
        $customerEmail = 'test@infogain.com';
        $allowedDomains = '';
        $response = $this->data->validateDomain($customerEmail, $allowedDomains);
        $this->assertEquals(true, $response);
    }

    /**
     * @test testValidateDomainWithInvalidCustomer
     */

    public function testValidateDomainWithInvalidCustomer()
    {
        $customerEmail = 'test';
        $allowedDomains = 'gmail.com,osv.com,abc.com';
        $response = $this->data->validateDomain($customerEmail, $allowedDomains);
        $this->assertEquals(false, $response);
    }

    /**
     * @test testCheckPermission
     */
     public function testCheckPermission()
     {
         $permissionData[]=['label'=>'Shared Credit Cards::shared_credit_cards'];
         $this->customerSessionMock->expects($this->any())
        ->method('getCustomer')
        ->willReturn($this->customerMock);
        $this->enhanceUserRoles->expects($this->any())->method('getCollection')->willReturnSelf();
        $this->enhanceUserRoles->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->enhanceUserRoles->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->enhanceUserRoles->expects($this->any())->method('join')->willReturnSelf();
        $this->enhanceUserRoles->expects($this->any())->method('getData')->willReturn($permissionData);
        $response = $this->data->checkPermission('0');
        $this->assertIsArray($response);
     }

    /**
     * testGetOrCreateCustomerSession
     * @return void
     */
    public function testGetOrCreateCustomerSession()
    {
        $this->customerSessionMock->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);
        $result = $this->data->getOrCreateCustomerSession();
        $this->assertSame($this->customerSessionMock, $result);
    }

    /**
     * testGetToggleStatusForPerformanceImprovmentPhasetwo
     * @return void
     */
    public function testGetToggleStatusForPerformanceImprovmentPhasetwo()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->data->getToggleStatusForPerformanceImprovmentPhasetwo());
    }

    public function testGetEmailNotificationAllowUserList()
    {
        $companyId = 1;
        $mockCollection = $this->createMock(Collection::class);
        $mockCollection->method('addFieldToFilter')
            ->willReturnSelf();
        $mockCollection->method('getSelect')
            ->willReturnSelf();
        $mockCollection->method('join')
            ->willReturnSelf();
        $mockCollection->method('getColumnValues')
            ->willReturn([1, 2]);
        $this->enhanceUserRoles->method('getCollection')
            ->willReturn($mockCollection);

        $customerMock1 = $this->createMock(CustomerInterface::class);
        $customerMock1->method('getId')->willReturn(1);
        $customerMock1->method('getFirstname')->willReturn('John');
        $customerMock1->method('getLastname')->willReturn('Doe');
        $customerMock1->method('getEmail')->willReturn('john.doe@example.com');

        $secondaryEmailAttribute1 = $this->createMock(\Magento\Framework\Api\AttributeInterface::class);
        $secondaryEmailAttribute1->method('getValue')->willReturn('john.secondary@example.com');
        $customerMock1->method('getCustomAttribute')->willReturn($secondaryEmailAttribute1);

        $customerMock2 = $this->createMock(CustomerInterface::class);
        $customerMock2->method('getId')->willReturn(2);
        $customerMock2->method('getFirstname')->willReturn('Jane');
        $customerMock2->method('getLastname')->willReturn('Smith');
        $customerMock2->method('getEmail')->willReturn('jane.smith@example.com');

        $secondaryEmailAttribute2 = null;
        $customerMock2->method('getCustomAttribute')->willReturn($secondaryEmailAttribute2);
        $this->customerRepositoryInterface->method('getById')
            ->willReturnMap([
                [1, $customerMock1],
                [2, $customerMock2]
            ]);
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $result = $this->data->getEmailNotificationAllowUserList($companyId);


        $expectedResult = [
            ['name' => 'John Doe', 'address' => 'john.secondary@example.com'],
            ['name' => 'Jane Smith', 'address' => 'jane.smith@example.com']
        ];
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests that isSelfRegCustomerWithSSOEnabled returns false when the user is not logged in.
     *
     * This test verifies the behavior of the helper method when SSO is enabled,
     * ensuring that it correctly identifies a non-logged-in user as not being a self-registration customer.
     */
    public function testIsSelfRegCustomerWithSSOEnabledReturnsFalseWhenNotLoggedIn()
{
    $this->toggleConfigMock->expects($this->any())
        ->method('getToggleConfigValue')
        ->willReturn(true);
        
    $this->_customerSessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);

    $this->customerSessionMock->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(23);

    $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

    $this->baseAuthMock->expects($this->any())
        ->method('isLoggedIn')
        ->willReturn(false);

    $result = $this->data->isSelfRegCustomerWithSSOEnabled();
    $this->assertFalse($result);
}

/**
 * Tests that isSelfRegCustomerWithSSOEnabled returns false when the company data is null.
 *
 * This test verifies the behavior of the isSelfRegCustomerWithSSOEnabled method
 * in scenarios where no company data is provided, ensuring that the method
 * correctly returns false as expected.
 */
public function testIsSelfRegCustomerWithSSOEnabledReturnsFalseWhenCompanyDataIsNull()
{
    $this->toggleConfigMock->expects($this->any())
        ->method('getToggleConfigValue')
        ->willReturn(true);
    
    $this->_customerSessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);

    $this->customerSessionMock->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(23);

    $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

    $this->baseAuthMock->expects($this->any())
        ->method('isLoggedIn')
        ->willReturn(true);

    $customerSessionMock = $this->customerSessionMock;
    $customerSessionMock->expects($this->any())
        ->method('getId')
        ->willReturn(123);

    $this->companyRepository->expects($this->any())
        ->method('getByCustomerId')
        ->willReturn(null);
        
    $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

    $this->storeMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/me/');
    $this->companyFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->companyMock);

    $this->companyMock->expects($this->any())
        ->method('getCollection')
        ->willReturn($this->companyCollection);

    $this->companyCollection->expects($this->any())
        ->method('addFieldToFilter')
        ->willReturnSelf();

    $this->companyCollection->expects($this->any())
        ->method('getFirstItem')
        ->willReturn($this->companyMock);

    $this->companyMock->expects($this->any())
        ->method('getId')
        ->willReturn(6);

    $result = $this->data->isSelfRegCustomerWithSSOEnabled();
    $this->assertFalse($result);
}

/**
 * Tests the isSelfRegCustomer method when SSO is enabled and company data is not null.
 *
 * This test verifies that the method correctly identifies a self-registration customer
 * under the condition where Single Sign-On (SSO) is enabled and valid company data is provided.
 */
public function testIsSelfRegCustomerWithSSOEnabledFalse()
{
    $this->toggleConfigMock->expects($this->any())
        ->method('getToggleConfigValue')
        ->willReturn(false);
    
    $this->_customerSessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);

    $this->customerSessionMock->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(23);

    $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

    $this->baseAuthMock->expects($this->any())
        ->method('isLoggedIn')
        ->willReturn(true);

    $customerSessionMock = $this->customerSessionMock;
    $customerSessionMock->expects($this->any())
        ->method('getId')
        ->willReturn(123);

    $this->companyRepository->expects($this->any())
        ->method('getByCustomerId')
        ->willReturn($this->companyInterface);

    $this->companyInterface->expects($this->any())
        ->method('getId')
        ->willReturn(1);

    $this->companyInterface->expects($this->any())
        ->method('getData')
        ->with('storefront_login_method_option')
        ->willReturn(SelfReg::COMMERCIAL_STORE_SSO);

    $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

    $this->storeMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/me/');
    $this->companyFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->companyMock);

    $this->companyMock->expects($this->any())
        ->method('getCollection')
        ->willReturn($this->companyCollection);

    $this->companyCollection->expects($this->any())
        ->method('addFieldToFilter')
        ->willReturnSelf();

    $this->companyCollection->expects($this->any())
        ->method('getFirstItem')
        ->willReturn($this->companyMock);

    $this->companyMock->expects($this->any())
        ->method('getId')
        ->willReturn(1);
    
    $this->companyFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->companyMock);

    $this->companyMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

    $result = $this->data->isSelfRegCustomerWithSSOEnabled();
    $this->assertEquals(false, $result);
}

}

