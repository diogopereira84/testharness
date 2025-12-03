<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SDE\Test\Unit\Model;

use Exception;
use Fedex\SDE\Model\ForgeRock;
use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\Company\Model\ResourceModel\AdditionalData\Collection as AdditionalDataCollection;
use Fedex\CustomerDetails\Helper\Data as CustomerDetailsHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Punchout\Helper\Data as TokenHelper;
use Fedex\SDE\Model\Customer as SdeCustomerModel;
use Fedex\SSO\Helper\Data as SSOHelper;
use Fedex\SSO\Model\Config as SSOConfig;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\Base\Helper\Auth;

class CustomerTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Model\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Magento\Framework\Registry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $registryMock;
    protected $cookieManagerMock;
    protected $customerSessionMock;
    protected $customerInterfaceFactoryMock;
    protected $customerRepositoryMock;
    protected $companyManagementMock;
    protected $storeManagerMock;
    protected $tokenHelperMock;
    protected $curlMock;
    protected $jsonMock;
    /**
     * @var (\Magento\Company\Api\CompanyRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyRepositoryMock;
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfigMock;
    protected $accountManagementInterfaceMock;
    /**
     * @var (\Fedex\Company\Model\AdditionalDataFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $additionalDataFactoryMock;
    protected $urlInterfaceMock;
    protected $responseFactoryMock;
    /**
     * @var (\Magento\Framework\Model\ResourceModel\AbstractResource & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $resourceMock;
    /**
     * @var (\Magento\Framework\Data\Collection\AbstractDb & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $resourceCollectionMock;
    protected $websiteInterfaceMock;
    protected $customerInterfaceMock;
    protected $customerExtensionInterfaceMock;
    protected $companyCustomerInterfaceMock;
    protected $storeInterfaceMock;
    protected $loggerMock;
    /**
     * @var (\Magento\Company\Api\Data\CompanyInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyItemMock;
    /**
     * @var (\Fedex\Company\Model\AdditionalData & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $additionalDataMock;
    /**
     * @var (\Fedex\Company\Model\ResourceModel\AdditionalData\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $additionalDataCollectionMock;
    protected $ssoHelperMock;
    protected $ssoConfigMock;
    protected $customerDetailsHelperMock;
    /**
     * @var (\Fedex\SDE\Model\ForgeRock & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $forgeRock;
    protected $data;
    /**
     * SDE cookie test value
     */
    const SDE_COOKIE_VALUE = 'kS2xiY6ILMSrXCZbNaeTkSd1AS1x3oZSkivDkh63aY7XTIBXq0UNaXoBPAT986SZawRVoi3ooqWq9Rw';

    /**
     * Gateway token test value
     */
    const GATEWAY_TOKEN = '10178fc0-9f9f-45d3-938c-5533aadec548';

    /**
     * TAZ token test data
     */
    const TAZ_TOKEN_DATA =
    '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXo", "token_type":"bearer"}';

    /**
     * TAZ token test data decoded
     */
    const TAZ_TOKEN_DECODED = ["access_token" =>
    "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXo", "token_type" => "bearer"];

    /**
     * TAZ token test value
     */
    const TAZ_TOKEN = self::TAZ_TOKEN_DECODED['access_token'];

    /**
     * Profile API test URL
     */
    const PROFILE_API_URL = 'https://api.test.office.fedex.com/customer/fedexoffice/v1/profiles';

    /**
     * Page not found URL
     */
    const No_ROUTE_URL = 'https://staging3.office.fedex.com/sde_default/404/';

    /**
     * Customer email test value
     */
    const CUSTOMER_EMAIL = 'email@domain.com';

    /**
     * Customer Id test value
     */
    const CUSTOMER_ID = 1;

    /**
     * Customer group id for test
     */
    const CUSTOMER_GROUP_ID = 6;

    /**
     * Company id for test
     */
    const COMPANY_ID = 6;

    /**
     * Company name for test
     */
    const COMPANY_NAME = 'walmart';

    /**
     * Website test value
     */
    const WEBSITE_ID = 1;

    /**
     * Store id test value
     */
    const STORE_ID = 65;

    /**
     * Test customer data array
     */
    const CUSTOMER_DATA = [
        'email' => self::CUSTOMER_EMAIL,
        'firstname' => 'first_name',
        'lastname' => 'last_name',
    ];

    /**
     * Decryption API response test data
     */
    const DECRYPTION_API_RESPONSE = '{
        "transactionId": "b8393f75-3f9d-411e-a90b-1d49274ccd84",
        "output": {
            "profile": {
                "uuId": "http://www.okta.com/exk4nxc64iOnpbKxi5d7_email@domain.com",
                "contact": {
                    "personName": {
                        "firstName": "first_name",
                        "lastName": "last_name"
                    },
                    "company": {
                        "name": "walmart"
                    },
                    "emailDetail": {
                        "emailAddress": "email@domain.com"
                    }
                },
                "emailSubscription": false,
                "marketingEmails": false
            }
        }
    }';

    /**
     * Decryption API response test data decoded
     */
    const DECRYPTION_API_RESPONSE_DECODED = [
        "transactionId" => "b8393f75-3f9d-411e-a90b-1d49274ccd84",
        "output" => [
            "profile" => [
                "uuId" => "http://www.okta.com/exk4nxc64iOnpbKxi5d7_email@domain.com",
                "contact" => [
                    "personName" => [
                        "firstName" => "first_name",
                        "lastName" => "last_name",
                    ],
                    "company" => [
                        "name" => "walmart",
                    ],
                    "emailDetail" => [
                        "emailAddress" => "email@domain.com",
                    ],
                ],
                "emailSubscription" => false,
                "marketingEmails" => false,
            ],
        ],
    ];

    /**
     * Decryption API response test data with error
     */
    const DECRYPTION_API_RESPONSE_WITH_ERROR_DECODED = ["error" => true, "message" => "Invalid Request"];
    protected Auth|MockObject $baseAuthMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieManagerMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCookie'])
            ->getMockForAbstractClass();

        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'logout',
                'setLastCustomerId',
                'setCustomerDataAsLoggedIn',
                'setCustomerCompany',
                'getCustomer',
                'regenerateId',
                'isLoggedIn'
            ])
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->customerInterfaceFactoryMock = $this->getMockBuilder(CustomerInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById', 'get', 'save'])
            ->getMockForAbstractClass();

        $this->companyManagementMock = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenHelperMock = $this->getMockBuilder(TokenHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTazToken', 'getAuthGatewayToken'])
            ->getMockForAbstractClass();

        $this->curlMock = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBody', 'setOptions'])
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyRepositoryMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfig'])
            ->getMock();

        $this->accountManagementInterfaceMock = $this->getMockBuilder(AccountManagementInterface::class)
            ->setMethods(['isEmailAvailable'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->additionalDataFactoryMock = $this->getMockBuilder(AdditionalDataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlInterfaceMock = $this->getMockBuilder(UrlInterface::class)
            ->setMethods(['getUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->responseFactoryMock = $this->getMockBuilder(ResponseFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','setRedirect', 'sendResponse'])
            ->getMock();

        $this->resourceMock = $this->getMockBuilder(AbstractResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceCollectionMock = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteInterfaceMock = $this->getMockBuilder(WebsiteInterface::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerInterfaceMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerExtensionInterfaceMock = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();

        $this->companyCustomerInterfaceMock = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyId'])
            ->getMockForAbstractClass();

        $this->storeInterfaceMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getStoreId'])
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyItemMock = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->additionalDataMock = $this->getMockBuilder(AdditionalData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'addFieldToSelect', 'addFieldToFilter', 'getFirstItem', 'getCompanyId'])
            ->getMock();

        $this->additionalDataCollectionMock = $this->getMockBuilder(AdditionalDataCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToSelect', 'addFieldToFilter', 'getFirstItem', 'getCompanyId'])
            ->getMock();

        $this->ssoHelperMock = $this->getMockBuilder(SSOHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->ssoConfigMock = $this->getMockBuilder(SSOConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerDetailsHelperMock = $this->getMockBuilder(CustomerDetailsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->forgeRock = $this->createMock(ForgeRock::class);

        $objectManager = new ObjectManager($this);
        $this->data = $objectManager->getObject(
            SdeCustomerModel::class,
            [
                'context' => $this->contextMock,
                'registry' => $this->registryMock,
                'cookieManager' => $this->cookieManagerMock,
                'customerSession' => $this->customerSessionMock,
                'customerInterfaceFactory' => $this->customerInterfaceFactoryMock,
                'customerRepository' => $this->customerRepositoryMock,
                'companyManagement' => $this->companyManagementMock,
                'storeManager' => $this->storeManagerMock,
                'tokenHelper' => $this->tokenHelperMock,
                'curl' => $this->curlMock,
                'json' => $this->jsonMock,
                'companyRepository' => $this->companyRepositoryMock,
                'toggleConfig' => $this->toggleConfigMock,
                'customerAccountManagement' => $this->accountManagementInterfaceMock,
                'additionalDataFactory' => $this->additionalDataFactoryMock,
                'url' => $this->urlInterfaceMock,
                'responseFactory' => $this->responseFactoryMock,
                'resource' => $this->resourceMock,
                'resourceCollection' => $this->resourceCollectionMock,
                '_logger' => $this->loggerMock,
                'ssoHelper' => $this->ssoHelperMock,
                'ssoConfig' => $this->ssoConfigMock,
                'customerDetailsHelper' => $this->customerDetailsHelperMock,
                'authHelper' => $this->baseAuthMock,
                'forgeRock' => $this->forgeRock,
                'data' => [],
            ]
        );
    }

    /**
     * @test testRedirectCustomerToSsoWithCustomerLoggedIn
     */
    public function testRedirectCustomerToSsoWithCustomerLoggedIn()
    {
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->assertEquals(false, $this->data->redirectCustomerToSso());
    }

    /**
     * @test testRedirectCustomerToSsoWithCustomerNotLoggedIn
     */
    public function testRedirectCustomerToSsoWithCustomerCustomerProfileReturnFalse()
    {
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->cookieManagerMock->expects($this->any())
            ->method('getCookie')
            ->with(SSOHelper::SDE_COOKIE_NAME)->willReturn('key');

        $this->ssoConfigMock->expects($this->any())
            ->method('getProfileApiUrl')
            ->willReturn(self::PROFILE_API_URL);

        $this->ssoHelperMock->expects($this->any())
            ->method('getCustomerProfile')
            ->willReturn(false);

        $this->assertEquals(true, $this->data->redirectCustomerToSso());
    }

    /**
     * @test testRedirectCustomerToSsoWithCustomerNotLoggedIn
     */
    public function testRedirectCustomerToSsoWithCustomerCustomerProfileReturnTrue()
    {
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->cookieManagerMock->expects($this->any())
            ->method('getCookie')
            ->with(SSOHelper::SDE_COOKIE_NAME)->willReturn('key');

        $this->ssoConfigMock->expects($this->any())
            ->method('getProfileApiUrl')
            ->willReturn(self::PROFILE_API_URL);

        $this->ssoHelperMock->expects($this->any())
            ->method('getCustomerProfile')
            ->willReturn(true);

        $this->assertEquals(false, $this->data->redirectCustomerToSso());
    }
    /**
     * @test testReadSmeCookieAndSetCustomerWithRefactorEnabled
     */
    public function testReadSmeCookieAndSetCustomerWithRefactorEnabled()
    {
        $this->cookieManagerMock->expects($this->any())
            ->method('getCookie')
            ->with(SSOHelper::SDE_COOKIE_NAME)->willReturn('key');

        $this->ssoConfigMock->expects($this->any())
            ->method('getProfileApiUrl')
            ->willReturn(self::PROFILE_API_URL);

        $this->ssoHelperMock->expects($this->any())
            ->method('getCustomerProfile')
            ->willReturn(true);

        $this->assertEquals(true, $this->data->readSmeCookieAndSetCustomer());
    }

    /**
     * @test testReadSmeCookieAndSetCustomerWithRefactorEnabledAnd401
     */
    public function testReadSmeCookieAndSetCustomerWithRefactorEnabledAnd401()
    {
        $this->cookieManagerMock->expects($this->any())
            ->method('getCookie')
            ->with(SSOHelper::SDE_COOKIE_NAME)->willReturn('key');

        $this->ssoConfigMock->expects($this->any())
            ->method('getProfileApiUrl')
            ->willReturn(self::PROFILE_API_URL);

        $this->ssoHelperMock->expects($this->any())
            ->method('getCustomerProfile')
            ->willReturn(401);

        $this->assertEquals(false, $this->data->readSmeCookieAndSetCustomer());
    }

    /**
     * @test testReadSmeCookieAndSetCustomerWithExistingCustomer
     */
    public function testReadSmeCookieAndSetCustomerWithExistingCustomer()
    {
        $this->cookieManagerMock->expects($this->any())
            ->method('getCookie')
            ->with(SSOHelper::SDE_COOKIE_NAME)->willReturn('key');

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //decryptCookie

        $this->ssoConfigMock->expects($this->any())
            ->method('getProfileApiUrl')
            ->willReturn(self::PROFILE_API_URL);

        $this->tokenHelperMock->expects($this->any())
            ->method('getTazToken')
            ->willReturn(self::TAZ_TOKEN_DATA);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturn(self::TAZ_TOKEN_DECODED);

        $this->tokenHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(self::GATEWAY_TOKEN);

        //callDecryptionApi
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "client_id: " . self::GATEWAY_TOKEN,
            "Cookie: " . self::TAZ_TOKEN,
            "Cookie: " . SSOHelper::SDE_COOKIE_NAME . "=" . self::SDE_COOKIE_VALUE,
        ];
        $options = [
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
        ];
        $this->curlMock->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willreturn(self::DECRYPTION_API_RESPONSE);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturn(self::DECRYPTION_API_RESPONSE_DECODED);

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //checkIfCustomerAlreadyExists
        $this->storeManagerMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->websiteInterfaceMock);

        $this->websiteInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(self::STORE_ID);

        $this->accountManagementInterfaceMock->expects($this->any())
            ->method('isEmailAvailable')
            ->willReturn(false);

        $this->testUpdateCustomer();

        //createSession
        $this->customerRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(self::CUSTOMER_ID);

        $this->customerSessionMock->expects($this->any())
            ->method('logout')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setLastCustomerId')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('regenerateId')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setCustomerCompany')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setCustomerDataAsLoggedIn')
            ->willReturnSelf();

        $this->loggerMock->expects($this->any())
            ->method('critical')
            ->willReturnSelf();

        //getCustomerCompanyId
        $this->customerRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->customerExtensionInterfaceMock);

        $this->customerExtensionInterfaceMock->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturn($this->companyCustomerInterfaceMock);

        $this->companyCustomerInterfaceMock->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(self::COMPANY_ID);

        $this->responseFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->responseFactoryMock->expects($this->any())
            ->method('sendResponse')
            ->willReturnSelf();

        $this->assertEquals(false, $this->data->readSmeCookieAndSetCustomer());
    }

    /**
     * @test testReadSmeCookieAndSetCustomerWithExistingCustomerAndTazToggleEnabled
     */
    public function testReadSmeCookieAndSetCustomerWithExistingCustomerAndTazToggleEnabled()
    {
        $this->cookieManagerMock->expects($this->any())
            ->method('getCookie')
            ->with(SSOHelper::SDE_COOKIE_NAME)->willReturn('key');

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //decryptCookie

        $this->ssoConfigMock->expects($this->any())
            ->method('getProfileApiUrl')
            ->willReturn(self::PROFILE_API_URL);

        $this->tokenHelperMock->expects($this->any())
            ->method('getTazToken')
            ->willReturn(self::TAZ_TOKEN);

        $this->tokenHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(self::GATEWAY_TOKEN);

        //callDecryptionApi
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "client_id: " . self::GATEWAY_TOKEN,
            "Cookie: " . self::TAZ_TOKEN,
            "Cookie: " . SSOHelper::SDE_COOKIE_NAME . "=" . self::SDE_COOKIE_VALUE,
        ];
        $options = [
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
        ];
        $this->curlMock->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willreturn(self::DECRYPTION_API_RESPONSE);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturn(self::DECRYPTION_API_RESPONSE_DECODED);

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //checkIfCustomerAlreadyExists
        $this->storeManagerMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->websiteInterfaceMock);

        $this->websiteInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(self::STORE_ID);

        $this->accountManagementInterfaceMock->expects($this->any())
            ->method('isEmailAvailable')
            ->willReturn(false);

        $this->testUpdateCustomer();

        //createSession
        $this->customerRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(self::CUSTOMER_ID);

        $this->customerSessionMock->expects($this->any())
            ->method('logout')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setLastCustomerId')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('regenerateId')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setCustomerCompany')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setCustomerDataAsLoggedIn')
            ->willReturnSelf();

        $this->loggerMock->expects($this->any())
            ->method('critical')
            ->willReturnSelf();

        //getCustomerCompanyId
        $this->customerRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->customerExtensionInterfaceMock);

        $this->customerExtensionInterfaceMock->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturn($this->companyCustomerInterfaceMock);

        $this->companyCustomerInterfaceMock->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(self::COMPANY_ID);

        $this->assertEquals(null, $this->data->readSmeCookieAndSetCustomer());
    }

    /**
     * @test testReadSmeCookieAndSetCustomerWithExistingCustomerAndTazToggleEnabledWithCustomerCompanyIdNull
     */
    public function testReadSmeCookieAndSetCustomerWithExistingCustomerAndTazToggleEnabledWithCustomerCompanyIdNull()
    {
        $this->cookieManagerMock->expects($this->any())
            ->method('getCookie')
            ->with(SSOHelper::SDE_COOKIE_NAME)->willReturn('key');

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();
        //decryptCookie

        $this->ssoConfigMock->expects($this->any())
            ->method('getProfileApiUrl')
            ->willReturn(self::PROFILE_API_URL);

        $this->tokenHelperMock->expects($this->any())
            ->method('getTazToken')
            ->willReturn(self::TAZ_TOKEN);

        $this->tokenHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(self::GATEWAY_TOKEN);

        //callDecryptionApi
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "client_id: " . self::GATEWAY_TOKEN,
            "Cookie: " . self::TAZ_TOKEN,
            "Cookie: " . SSOHelper::SDE_COOKIE_NAME . "=" . self::SDE_COOKIE_VALUE,
        ];
        $options = [
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
        ];
        $this->curlMock->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willreturn(self::DECRYPTION_API_RESPONSE);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturn(self::DECRYPTION_API_RESPONSE_DECODED);

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //checkIfCustomerAlreadyExists
        $this->storeManagerMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->websiteInterfaceMock);

        $this->websiteInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(self::STORE_ID);

        $this->accountManagementInterfaceMock->expects($this->any())
            ->method('isEmailAvailable')
            ->willReturn(false);

        $this->testUpdateCustomer();

        //createSession
        $this->customerRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(self::CUSTOMER_ID);

        $this->customerSessionMock->expects($this->any())
            ->method('logout')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setLastCustomerId')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('regenerateId')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setCustomerCompany')
            ->willReturnSelf();

        $phrase = new Phrase(__('Exception message'));
        $exception = new Exception($phrase);
        $this->customerSessionMock->expects($this->any())
            ->method('setCustomerDataAsLoggedIn')
            ->willThrowException($exception);

        $this->loggerMock->expects($this->any())
            ->method('critical')
            ->willReturnSelf();

        //getCustomerCompanyId
        $this->customerRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->customerExtensionInterfaceMock);

        $this->customerExtensionInterfaceMock->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturn(null);

        $this->companyCustomerInterfaceMock->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(null);

        $this->assertEquals(false, $this->data->readSmeCookieAndSetCustomer());
    }

    /**
     * @test testReadSmeCookieAndSetCustomerWithExistingCustomerAndTazToggleEnabledWithException
     */
    public function testReadSmeCookieAndSetCustomerWithExistingCustomerAndTazToggleEnabledWithException()
    {
        $this->cookieManagerMock->expects($this->any())
            ->method('getCookie')
            ->with(SSOHelper::SDE_COOKIE_NAME)->willReturn('key');

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //decryptCookie

        $this->ssoConfigMock->expects($this->any())
            ->method('getProfileApiUrl')
            ->willReturn(self::PROFILE_API_URL);

        $this->tokenHelperMock->expects($this->any())
            ->method('getTazToken')
            ->willReturn(self::TAZ_TOKEN);

        $this->tokenHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(self::GATEWAY_TOKEN);

        //callDecryptionApi
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "client_id: " . self::GATEWAY_TOKEN,
            "Cookie: " . self::TAZ_TOKEN,
            "Cookie: " . SSOHelper::SDE_COOKIE_NAME . "=" . self::SDE_COOKIE_VALUE,
        ];
        $options = [
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
        ];
        $this->curlMock->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willreturn(self::DECRYPTION_API_RESPONSE);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturn(self::DECRYPTION_API_RESPONSE_DECODED);

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //checkIfCustomerAlreadyExists
        $this->storeManagerMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->websiteInterfaceMock);

        $this->websiteInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(self::STORE_ID);

        $this->accountManagementInterfaceMock->expects($this->any())
            ->method('isEmailAvailable')
            ->willReturn(false);

        $this->testUpdateCustomer();

        //createSession
        $this->customerRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(self::CUSTOMER_ID);

        $this->customerSessionMock->expects($this->any())
            ->method('logout')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setLastCustomerId')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('regenerateId')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setCustomerCompany')
            ->willReturnSelf();

        $phrase = new Phrase(__('Exception message'));
        $exception = new Exception($phrase);
        $this->customerSessionMock->expects($this->any())
            ->method('setCustomerDataAsLoggedIn')
            ->willThrowException($exception);

        $this->loggerMock->expects($this->any())
            ->method('critical')
            ->willReturnSelf();

        //getCustomerCompanyId
        $this->customerRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->customerExtensionInterfaceMock);

        $this->customerExtensionInterfaceMock->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturn($this->companyCustomerInterfaceMock);

        $this->companyCustomerInterfaceMock->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(self::COMPANY_ID);

        $this->assertEquals(false, $this->data->readSmeCookieAndSetCustomer());
    }

    /**
     * @test testReadSmeCookieAndSetCustomerWithNewCustomer
     */
    public function testReadSmeCookieAndSetCustomerWithNewCustomer()
    {
        $this->cookieManagerMock->expects($this->any())
            ->method('getCookie')
            ->with(SSOHelper::SDE_COOKIE_NAME)->willReturn('key');

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //decryptCookie

        $this->ssoConfigMock->expects($this->any())
            ->method('getProfileApiUrl')
            ->willReturn(self::PROFILE_API_URL);

        $this->tokenHelperMock->expects($this->any())
            ->method('getTazToken')
            ->willReturn(self::TAZ_TOKEN);

        $this->tokenHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(self::GATEWAY_TOKEN);

        //callDecryptionApi
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "client_id: " . self::GATEWAY_TOKEN,
            "Cookie: " . self::TAZ_TOKEN,
            "Cookie: " . SSOHelper::SDE_COOKIE_NAME . "=" . self::SDE_COOKIE_VALUE,
        ];
        $options = [
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
        ];
        $this->curlMock->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willreturn(self::DECRYPTION_API_RESPONSE);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturn(self::DECRYPTION_API_RESPONSE_DECODED);

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //checkIfCustomerAlreadyExists
        $this->storeManagerMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->websiteInterfaceMock);

        $this->websiteInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(self::WEBSITE_ID);

        $this->accountManagementInterfaceMock->expects($this->any())
            ->method('isEmailAvailable')
            ->willReturn(true);

        //getCustomerCompanyIdByStore
        $this->ssoHelperMock->expects($this->any())
            ->method('getCustomerCompanyIdByStore')
            ->willReturn(self::COMPANY_ID);

        //getCompanyCustomerGroupId
        $this->ssoHelperMock->expects($this->any())
            ->method('getCompanyCustomerGroupId')
            ->willReturn(self::CUSTOMER_GROUP_ID);

        //registerCustomer
        $this->customerInterfaceFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerInterfaceMock);

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeInterfaceMock);

        $this->storeInterfaceMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(self::STORE_ID);

        $this->customerInterfaceMock->expects($this->any())
            ->method('setWebsiteId')
            ->willReturnSelf();

        $this->customerInterfaceMock->expects($this->any())
            ->method('setStoreId')
            ->willReturnSelf();

        $this->customerRepositoryMock->expects($this->any())
            ->method('save')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(true);

        $this->companyManagementMock->expects($this->any())
            ->method('assignCustomer')
            ->willReturn('');

        //createSession
        $this->customerRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(self::CUSTOMER_ID);

        $this->customerSessionMock->expects($this->any())
            ->method('logout')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setLastCustomerId')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setCustomerCompany')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setCustomerDataAsLoggedIn')
            ->willReturnSelf();

        $this->loggerMock->expects($this->any())
            ->method('critical')
            ->willReturnSelf();

        //getCustomerCompanyId
        $this->customerRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->customerExtensionInterfaceMock);

        $this->customerExtensionInterfaceMock->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturn($this->companyCustomerInterfaceMock);

        $this->companyCustomerInterfaceMock->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(self::COMPANY_ID);

        $this->assertEquals(null, $this->data->readSmeCookieAndSetCustomer());
    }

    /**
     * @test testReadSmeCookieAndSetCustomerWithExistingCustomerException
     */
    public function testReadSmeCookieAndSetCustomerWithExistingCustomerException()
    {
        //throw exception
        $phrase = new Phrase(__('Exception message'));
        $exception = new Exception($phrase);
        $this->cookieManagerMock->expects($this->any())
            ->method('getCookie')
            ->willThrowException($exception);

        $this->assertEquals(false, $this->data->readSmeCookieAndSetCustomer());
    }

    /**
     * @test testReadSmeCookieAndSetCustomerWithExceptionWhileCheckIfCustomerAlreadyExists
     */
    public function testReadSmeCookieAndSetCustomerWithExceptionWhileCheckIfCustomerAlreadyExists()
    {
        $this->cookieManagerMock->expects($this->any())
            ->method('getCookie')
            ->with(SSOHelper::SDE_COOKIE_NAME)->willReturn('key');

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //decryptCookie

        $this->ssoConfigMock->expects($this->any())
            ->method('getProfileApiUrl')
            ->willReturn(self::PROFILE_API_URL);

        $this->tokenHelperMock->expects($this->any())
            ->method('getTazToken')
            ->willReturn(self::TAZ_TOKEN);

        $this->tokenHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(self::GATEWAY_TOKEN);

        //callDecryptionApi
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "client_id: " . self::GATEWAY_TOKEN,
            "Cookie: " . self::TAZ_TOKEN,
            "Cookie: " . SSOHelper::SDE_COOKIE_NAME . "=" . self::SDE_COOKIE_VALUE,
        ];
        $options = [
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
        ];
        $this->curlMock->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willreturn(self::DECRYPTION_API_RESPONSE);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturn(self::DECRYPTION_API_RESPONSE_DECODED);

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //checkIfCustomerAlreadyExists
        $this->storeManagerMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->websiteInterfaceMock);

        //throw exception
        $phrase = new Phrase(__('Exception message'));
        $exception = new Exception($phrase);
        $this->websiteInterfaceMock->expects($this->any())
            ->method('getId')
            ->willThrowException($exception);

        //getCustomerCompanyIdByStore
        $this->ssoHelperMock->expects($this->any())
            ->method('getCustomerCompanyIdByStore')
            ->willReturn(self::COMPANY_ID);

        //getCompanyCustomerGroupId
        $this->ssoHelperMock->expects($this->any())
            ->method('getCompanyCustomerGroupId')
            ->willReturn(self::CUSTOMER_GROUP_ID);

        //registerCustomer
        $this->customerInterfaceFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerInterfaceMock);

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeInterfaceMock);

        $this->storeInterfaceMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(self::STORE_ID);

        $this->customerInterfaceMock->expects($this->any())
            ->method('setWebsiteId')
            ->willReturnSelf();

        $this->customerInterfaceMock->expects($this->any())
            ->method('setStoreId')
            ->willReturnSelf();

        $this->customerRepositoryMock->expects($this->any())
            ->method('save')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(true);

        $this->companyManagementMock->expects($this->any())
            ->method('assignCustomer')
            ->willReturn('');

        //createSession
        $this->customerRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(self::CUSTOMER_ID);

        $this->customerSessionMock->expects($this->any())
            ->method('logout')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setLastCustomerId')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setCustomerCompany')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setCustomerDataAsLoggedIn')
            ->willReturnSelf();

        $this->loggerMock->expects($this->any())
            ->method('critical')
            ->willReturnSelf();

        //getCustomerCompanyId
        $this->customerRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->customerExtensionInterfaceMock);

        $this->customerExtensionInterfaceMock->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturn($this->companyCustomerInterfaceMock);

        $this->companyCustomerInterfaceMock->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(self::COMPANY_ID);

        $this->assertEquals(false, $this->data->readSmeCookieAndSetCustomer());
    }

    /**
     * @test testReadSmeCookieAndSetCustomerWithExceptionWhileCreatingCustomerSession
     */
    public function testReadSmeCookieAndSetCustomerWithExceptionWhileCreatingCustomerSession()
    {
        $this->cookieManagerMock->expects($this->any())
            ->method('getCookie')
            ->with(SSOHelper::SDE_COOKIE_NAME)->willReturn('key');

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //decryptCookie

        $this->ssoConfigMock->expects($this->any())
            ->method('getProfileApiUrl')
            ->willReturn(self::PROFILE_API_URL);

        $this->tokenHelperMock->expects($this->any())
            ->method('getTazToken')
            ->willReturn(self::TAZ_TOKEN_DATA);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturn(self::TAZ_TOKEN_DECODED);

        $this->tokenHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(self::GATEWAY_TOKEN);

        //callDecryptionApi
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "client_id: " . self::GATEWAY_TOKEN,
            "Cookie: " . self::TAZ_TOKEN,
            "Cookie: " . SSOHelper::SDE_COOKIE_NAME . "=" . self::SDE_COOKIE_VALUE,
        ];
        $options = [
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
        ];
        $this->curlMock->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willreturn(self::DECRYPTION_API_RESPONSE);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturn(self::DECRYPTION_API_RESPONSE_DECODED);

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //checkIfCustomerAlreadyExists
        $this->storeManagerMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->websiteInterfaceMock);

        $this->websiteInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(self::STORE_ID);

        $this->accountManagementInterfaceMock->expects($this->any())
            ->method('isEmailAvailable')
            ->willReturn(false);

        $this->testUpdateCustomer();

        //createSession
        $this->customerRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->customerInterfaceMock);

        //throw exception
        $phrase = new Phrase(__('Exception message'));
        $exception = new Exception($phrase);
        $this->customerRepositoryMock->expects($this->any())
            ->method('get')
            ->willThrowException($exception);

        $this->responseFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->responseFactoryMock->expects($this->any())
            ->method('sendResponse')
            ->willReturnSelf();

        $this->assertEquals(false, $this->data->readSmeCookieAndSetCustomer());
    }

    /**
     * @test testReadSmeCookieAndSetCustomerWithExceptionWhileCallingDecryptionApi
     */
    public function testReadSmeCookieAndSetCustomerWithExceptionWhileCallingDecryptionApi()
    {
        $this->cookieManagerMock->expects($this->any())
            ->method('getCookie')
            ->with(SSOHelper::SDE_COOKIE_NAME)->willReturn('key');

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //decryptCookie

        $this->ssoConfigMock->expects($this->any())
            ->method('getProfileApiUrl')
            ->willReturn(self::PROFILE_API_URL);

        $this->tokenHelperMock->expects($this->any())
            ->method('getTazToken')
            ->willReturn(self::TAZ_TOKEN_DATA);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturn(self::TAZ_TOKEN_DECODED);

        $this->tokenHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(self::GATEWAY_TOKEN);

        //callDecryptionApi
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "client_id: " . self::GATEWAY_TOKEN,
            "Cookie: " . self::TAZ_TOKEN,
            "Cookie: " . SSOHelper::SDE_COOKIE_NAME . "=" . self::SDE_COOKIE_VALUE,
        ];
        $options = [
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
        ];
        $this->curlMock->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willreturn(self::DECRYPTION_API_RESPONSE);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturn(null);

        //redirectToPageNotFound
        $this->urlInterfaceMock->expects($this->any())
            ->method('getUrl')->with('noroute')
            ->willReturn(self::No_ROUTE_URL);

        $this->responseFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->responseFactoryMock->expects($this->any())
            ->method('sendResponse')
            ->willReturnSelf();

        $this->assertEquals(false, $this->data->readSmeCookieAndSetCustomer());
    }

    /**
     * @test testReadSmeCookieAndSetCustomerWithExceptionWhileDecryptCookie
     */
    public function testReadSmeCookieAndSetCustomerWithExceptionWhileDecryptCookie()
    {
        $this->cookieManagerMock->expects($this->any())
            ->method('getCookie')
            ->with(SSOHelper::SDE_COOKIE_NAME)->willReturn('key');

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //decryptCookie

        $this->ssoConfigMock->expects($this->any())
            ->method('getProfileApiUrl')
            ->willReturn(self::PROFILE_API_URL);

        $this->tokenHelperMock->expects($this->any())
            ->method('getTazToken')
            ->willReturn(self::TAZ_TOKEN_DATA);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturn(self::TAZ_TOKEN_DECODED);

        //throw exception
        $phrase = new Phrase(__('Exception message'));
        $exception = new Exception($phrase);
        $this->tokenHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willThrowException($exception);

        $this->assertEquals(false, $this->data->readSmeCookieAndSetCustomer());
    }

    /**
     * @test testReadSmeCookieAndSetCustomerWithExceptionWhileCallDecryptionApi
     */
    public function testReadSmeCookieAndSetCustomerWithExceptionWhileCallDecryptionApi()
    {
        $this->cookieManagerMock->expects($this->any())
            ->method('getCookie')
            ->with(SSOHelper::SDE_COOKIE_NAME)->willReturn('key');

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //decryptCookie

        $this->ssoConfigMock->expects($this->any())
            ->method('getProfileApiUrl')
            ->willReturn(self::PROFILE_API_URL);

        $this->tokenHelperMock->expects($this->any())
            ->method('getTazToken')
            ->willReturn(self::TAZ_TOKEN_DATA);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturn(self::TAZ_TOKEN_DECODED);

        $this->tokenHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(self::GATEWAY_TOKEN);

        //callDecryptionApi
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "client_id: " . self::GATEWAY_TOKEN,
            "Cookie: " . self::TAZ_TOKEN,
            "Cookie: " . SSOHelper::SDE_COOKIE_NAME . "=" . self::SDE_COOKIE_VALUE,
        ];
        $options = [
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
        ];
        $this->curlMock->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willreturn(self::DECRYPTION_API_RESPONSE);

        //throw exception
        $phrase = new Phrase(__('Exception message'));
        $exception = new Exception($phrase);
        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willThrowException($exception);

        //redirectToPageNotFound
        $this->urlInterfaceMock->expects($this->any())
            ->method('getUrl')
            ->with('noroute')
            ->willReturn(self::No_ROUTE_URL);

        $this->responseFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->responseFactoryMock->expects($this->any())
            ->method('setRedirect')
            ->willReturnSelf();

        $this->responseFactoryMock->expects($this->any())
            ->method('sendResponse')
            ->willReturnSelf();

        $this->assertEquals(false, $this->data->readSmeCookieAndSetCustomer());
    }

    /**
     * @test testReadSmeCookieAndSetCustomerWithExistingCustomerExceptionWhileGetCustomerCompanyId
     */
    public function testReadSmeCookieAndSetCustomerWithExistingCustomerExceptionWhileGetCustomerCompanyId()
    {
        $this->cookieManagerMock->expects($this->any())
            ->method('getCookie')
            ->with(SSOHelper::SDE_COOKIE_NAME)->willReturn('key');

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //decryptCookie

        $this->ssoConfigMock->expects($this->any())
            ->method('getProfileApiUrl')
            ->willReturn(self::PROFILE_API_URL);

        $this->tokenHelperMock->expects($this->any())
            ->method('getTazToken')
            ->willReturn(self::TAZ_TOKEN_DATA);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturn(self::TAZ_TOKEN_DECODED);

        $this->tokenHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(self::GATEWAY_TOKEN);

        //callDecryptionApi
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "client_id: " . self::GATEWAY_TOKEN,
            "Cookie: " . self::TAZ_TOKEN,
            "Cookie: " . SSOHelper::SDE_COOKIE_NAME . "=" . self::SDE_COOKIE_VALUE,
        ];
        $options = [
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
        ];
        $this->curlMock->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willreturn(self::DECRYPTION_API_RESPONSE);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturn(self::DECRYPTION_API_RESPONSE_DECODED);

        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        //checkIfCustomerAlreadyExists
        $this->storeManagerMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->websiteInterfaceMock);

        $this->websiteInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(self::STORE_ID);

        $this->accountManagementInterfaceMock->expects($this->any())
            ->method('isEmailAvailable')
            ->willReturn(false);

        $this->testUpdateCustomer();

        //createSession
        $this->customerRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(self::CUSTOMER_ID);

        $this->customerSessionMock->expects($this->any())
            ->method('logout')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setLastCustomerId')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('regenerateId')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setCustomerCompany')
            ->willReturnSelf();

        $this->customerSessionMock->expects($this->any())
            ->method('setCustomerDataAsLoggedIn')
            ->willReturnSelf();

        $this->loggerMock->expects($this->any())
            ->method('critical')
            ->willReturnSelf();

        //getCustomerCompanyId
        $this->customerRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn(null);

        $this->responseFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->responseFactoryMock->expects($this->any())
            ->method('sendResponse')
            ->willReturnSelf();

        $this->assertEquals(false, $this->data->readSmeCookieAndSetCustomer());
    }

    /**
     * @test updateCustomer
     */
    public function testUpdateCustomer()
    {
        $this->customerRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->customerInterfaceMock);

        $this->customerRepositoryMock->expects($this->any())
            ->method('save')
            ->willReturn($this->customerInterfaceMock);

        $this->data->updateCustomer(self::CUSTOMER_DATA);
    }

    /**
     * @test updateCustomer with exeption thrown
     */
    public function testUpdateCustomerWithException()
    {
        //throw exception
        $phrase = new Phrase(__('Exception message'));
        $exception = new Exception($phrase);
        $this->customerRepositoryMock->expects($this->any())
            ->method('get')
            ->willThrowException($exception);

        $this->data->updateCustomer(self::CUSTOMER_DATA);
    }
}
