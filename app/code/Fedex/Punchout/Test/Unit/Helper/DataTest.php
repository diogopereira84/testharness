<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Punchout\Test\Unit\Helper;

use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\Punchout\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Company\Api\Data\CompanyInterfaceFactory;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\ResourceModel\Company;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\Company\Model\AdditionalData;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\Store;
use Fedex\Company\Model\ResourceModel\AdditionalData\Collection as AdditionalDataCollection;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\Base\Helper\Auth;

class DataTest extends TestCase
{
    protected $contextMock;
    protected $customerSessionMock;
    protected $customerInterfaceFactoryMock;
    protected $customerInterfaceMock;
    protected $customerExtensionInterfaceMock;
    protected $encryptorInterfaceMock;
    protected $customerRepoInterfaceMock;
    protected $customerFactoryMock;
    protected $customerMock;
    protected $authDynamicRowsFactoryMock;
    protected $authDynamicRowsMock;
    protected $authDynamicRowsCollectionMock;
    protected $companyInterfaceFactoryMock;
    protected $companyCustomerInterfaceMock;
    protected $companyInterfaceMock;
    /**
     * @var (\Magento\Company\Api\CompanyManagementInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyManagementInterfaceMock;
    protected $scopeConfigInterfaceMock;
    protected $scopeConfigInterfaceMockCons;
    protected $timezoneInterfaceMock;
    protected $dateTimeMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerInterfaceMock;
    protected $registryMock;
    protected $curlMock;
    protected $toggleConfig;
    protected $cookieManagerInterfaceMock;
    protected $cookieMetadataFactoryMock;
    protected $publicCookieMetadataMock;
    protected $compCustInterfaceMock;
    protected $requestQueryValidatorMock;
    protected $compCustMock;
    protected $additionalDataFactory;
    protected $additionalData;
    protected $additionalDataCollection;
    protected $storeFactory;
    protected $store;
    protected $storeManager;
    const CUSTOMER = 'customer';
    const EXTRINSIC = 'extrinsic';
    const BOTH = 'both';
    const CONTACT = 'contact';

    private const GATEWAY_TOKEN_API_URL_KEY = 'fedex/gateway_token/gateway_token_api_url';
    private const GATEWAY_CLIENT_ID_KEY = 'fedex/gateway_token/client_id';
    private const GATEWAY_CLIENT_SECRET_KEY = 'fedex/gateway_token/client_secret';
    private const GATEWAY_CLIENT_ID_ENCRYPTED = '84375djsfjsdhfysdufh789e0483659843irhtur';
    private const GATEWAY_CLIENT_ID_DECRYPTED = 'l7xx1cb26690fadd4b2789e0888a96b80ee2';
    private const GATEWAY_CLIENT_SECRET_ENCRYPTED = 'yndfu8dhfysdufh789e0483659843irhtur';
    private const GATEWAY_CLIENT_SECRET_DECRYPTED = '52804b3e664d4e43b4368468245983b1';
    private const GATEWAY_TOKEN_URL = 'https://apitest.fedex.com/auth/oauth/v2/token?grant_type=client_credentials&scope=oob';
    private const TAZ_TOKEN_API_URL_KEY = 'fedex/taz/taz_token_api_url';
    private const TAZ_CLIENT_ID_KEY = 'fedex/taz/client_id';
    private const TAZ_CLIENT_SECRET_KEY = 'fedex/taz/client_secret';
    private const TAZ_CLIENT_ID_ENCRYPTED = '7bfd3jsdhfysdufh789e0483659843irhtur';
    private const TAZ_CLIENT_ID_DECRYPTED = '3537131_MAGENTO_POD_SERVICE';
    private const TAZ_CLIENT_SECRET_ENCRYPTED = '7hd3jdufh789e0483659843irhtur';
    private const TAZ_CLIENT_SECRET_DECRYPTED = 'uJtXVkwqGI9xo11h544AHRZl69hqvOZv';


    /* Custom */
    private const SENDER_CREDENTIALS = 'AribaNetworkUserId';
    private const SENDER_IDENTITY = 'sysadmin@ariba.com';
    private const SENDER_SECRET = 'f3d3xs3rv1c3s';
    private const SENDER_USER_AGENT = 'Hubspan Translation Services';
    private const HEADER_TO_DOMAIN = 'privateid';
    private const HEADER_TO_IDENTITY = '999032669';
    private const TAZ_TOKEN_URL = 'privateid';
    private const TAZ_CLIENT_ID = null;
    private const TAZ_CLIENT_SECRET = null;

    /** @var Context|MockObject */
    protected $context;

    /** @var ConfigInterface|MockObject */
    protected $configInterface;

    /** @var EncryptorInterface|MockObject */
    protected $encryptorInterface;

    /** @var Curl|MockObject */
    protected $curl;

    /** @var ObjectManager|MockObject */
    protected $objectManager;

    /** @var HelperData|MockObject */
    protected $helperData;


    protected $verified = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm',
        'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '', 'store_id' => 2,
        'rule' => ['contact' => [0 => 'Name', 1 => 'Email'],
            'extrinsic' => [0 => 'UniqueName', 1 => 'UserEmail', 2 => 'Firstname']],
        'type' => ['0' => 'extrinsic', 1 => 'contact'],
        'extra_data' => ['redirect_url' => '', 'response_url' => 'https://shop-staging2.fedex.com',
            'cookie' => 24941604898076815], 'legacy_site_name' => 'testeprosite'];

    protected $customerData = ['email' => 'test@test.in', 'firstname' => 'fname', 'lastname' => 'lname'];

    protected $testCompanyId = 1;
    protected $testCustomerId = 1;
    protected $testDomainName = 'MAGENTO1';

    protected $xml = '<?xml version="1.0" encoding="UTF-8"?>
						<!DOCTYPE cXML SYSTEM "http://xml.cxml.org/schemas/cXML/1.1.007/cXML.dtd">
						<cXML xml:lang="en-US" payloadID="1591126611.9325364@stg1302app4.int.coupahost.com" timestamp="2020-06-02T14:36:51-05:00">
						<Header> <From><Credential domain="MAGENTO1"><Identity>NetworkId1</Identity></Credential></From><To><Credential domain="privateid">
						<Identity>999032669</Identity></Credential></To><Sender><Credential domain="AribaNetworkUserId"><Identity>sysadmin@ariba.com</Identity>
						<SharedSecret>f3d3xs3rv1c3s</SharedSecret></Credential><UserAgent>Hubspan Translation Services</UserAgent></Sender></Header><Request>
						<PunchOutSetupRequest operation="create"><BuyerCookie>24941604898076815</BuyerCookie><Extrinsic name="UniqueName">vivek</Extrinsic>
						<Extrinsic name="UserEmail">cu.16bcs1544@gmail.com</Extrinsic><Extrinsic name="Firstname">vivek</Extrinsic><BrowserFormPost>
						<URL>https://shop-staging2.fedex.com</URL></BrowserFormPost><SupplierSetup><URL>https://shop-staging2.fedex.com</URL></SupplierSetup><Contact><Name xml:lang="en-US">vicky</Name><Email>vivek2.singh@infogain.com</Email>
						</Contact><ShipTo></ShipTo></PunchOutSetupRequest></Request></cXML>';

    protected $type = 'customer';
    protected $testToken = null;

    protected Auth|MockObject $baseAuthMock;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->setMethods(['logout', 'setLastCustomerId', 'regenerateId', 'setCustomerAsLoggedIn',
                'setCustomerCompany', 'setBackUrl', 'setCommunicationUrl', 'setCommunicationCookie',
                'setCompanyName', 'setGatewayToken', 'setApiAccessToken', 'setApiAccessType', 'isLoggedIn', 'getTazTokenExpirationTime',
                'setTazToken', 'setTazTokenExpirationTime', 'getTazToken'
            ])
            ->addMethods(['getOnBehalfOf', 'getGatewayTokenExpirationTime', 'getGatewayToken', 'getAuthGatewayToken'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->customerInterfaceFactoryMock = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerInterfaceMock = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterface::class)
            ->setMethods(['setWebsiteId', 'getExtensionAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerExtensionInterfaceMock = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerExtensionInterface::class)
            ->setMethods(['getCompanyAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->encryptorInterfaceMock = $this->getMockBuilder(\Magento\Framework\Encryption\EncryptorInterface::class)
            ->setMethods(['decrypt'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerRepoInterfaceMock = $this->getMockBuilder(\Magento\Customer\Api\CustomerRepositoryInterface::class)
            ->setMethods(['save', 'getId', 'getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerFactoryMock = $this->getMockBuilder(\Magento\Customer\Model\CustomerFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerMock = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->setMethods(['setWebsiteId', 'loadByEmail', 'load', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->authDynamicRowsFactoryMock = $this->getMockBuilder(\Fedex\Company\Model\AuthDynamicRowsFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->authDynamicRowsMock = $this->getMockBuilder(\Fedex\Company\Model\AuthDynamicRows::class)
            ->setMethods(['getCollection', 'getRuleCode'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->authDynamicRowsCollectionMock = $this->getMockBuilder(\Fedex\Company\Model\ResourceModel\AuthDynamicRows\Collection::class)
            //~ ->setMethods(['addFieldToSelect', 'addFieldToFilter'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyInterfaceFactoryMock = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyCustomerInterfaceMock = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyCustomerInterface::class)
            ->setMethods(['getStatus', 'getCompanyId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyInterfaceMock = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyInterface::class)
            ->setMethods(['load', 'getId', 'getStatus', 'getDomainName', 'getCompanyUrl', 'getAcceptanceOption', 'getCompanyName', 'getSiteName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyManagementInterfaceMock = $this->getMockBuilder(\Magento\Company\Api\CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->scopeConfigInterfaceMock = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->scopeConfigInterfaceMockCons = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->timezoneInterfaceMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class)
            ->setMethods(['date'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->dateTimeMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\DateTime::class)
            ->setMethods(['gmtDate', 'format'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerInterfaceMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->registryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->setMethods(['registry'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->curlMock = $this->getMockBuilder(\Magento\Framework\HTTP\Client\Curl::class)
            ->setMethods(['getBody', 'post', 'getStatus'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        // B-1445896 - Improve code coverage
        $this->cookieManagerInterfaceMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->setMethods(['setPublicCookie'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cookieMetadataFactoryMock = $this->getMockBuilder(cookieMetadataFactory::class)
            ->setMethods(['createPublicCookieMetadata'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->publicCookieMetadataMock = $this->getMockBuilder(\Magento\Framework\Stdlib\Cookie\PublicCookieMetadata::class)
            ->setMethods(['setPath', 'setHttpOnly',
                'setSecure', 'setSameSite'])
            ->disableOriginalConstructor()
            ->getMock();

        //B-1320022 - WLGN integration for selfReg customer
        $this->compCustInterfaceMock = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyCustomerInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requestQueryValidatorMock = $this->getMockBuilder(RequestQueryValidator::class)
            ->onlyMethods(['isGraphQl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->compCustMock = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyCustomerInterface::class)
            ->setMethods(['setStatus'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock
            ->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfigInterfaceMockCons);

        $this->additionalDataFactory = $this->getMockBuilder(AdditionalDataFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->additionalData = $this->getMockBuilder(AdditionalData::class)
            ->setMethods(['getStoreViewId', 'getCollection', 'getNewStoreViewId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->additionalDataCollection = $this->getMockBuilder(AdditionalDataCollection::class)
            ->setMethods(['addFieldToSelect', 'addFieldToFilter', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeFactory = $this->getMockBuilder(StoreFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->store = $this->getMockBuilder(Store::class)
            ->setMethods(['load', 'getUrl', 'getBaseUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->helperData = $this->objectManager->getObject(
            Data::class,
            [
                'context' => $this->contextMock,
                'customerSession' => $this->customerSessionMock,
                'customerFactory' => $this->customerFactoryMock,
                'encryptorInterface' => $this->encryptorInterfaceMock,
                'customerInterfaceFactory' => $this->customerInterfaceFactoryMock,
                'customerRepository' => $this->customerRepoInterfaceMock,
                'companyFactory' => $this->companyInterfaceFactoryMock,
                'ruleFactory' => $this->authDynamicRowsFactoryMock,
                'data' => [],
                'extrinsicData' => [],
                'mappingContact' => ['firstname' => 'Name', 'unique_id' => 'Email', 'email' => 'Email'],
                'companyRepository' => $this->companyManagementInterfaceMock,
                'configInterface' => $this->scopeConfigInterfaceMock,
                'logger' => $this->loggerInterfaceMock,
                'registry' => $this->registryMock,
                'curl' => $this->curlMock,
                'timezone' => $this->timezoneInterfaceMock,
                'date' => $this->dateTimeMock,
                'toggleConfig' => $this->toggleConfig,
                'cookieManager' => $this->cookieManagerInterfaceMock,
                'cookieMetadataFactory' => $this->cookieMetadataFactoryMock,
                'compCustInterface' => $this->compCustInterfaceMock, //B-1320022 - WLGN integration for selfReg customer
                'requestQueryValidator' => $this->requestQueryValidatorMock,
                'additionalDataFactory' => $this->additionalDataFactory,
                'storeFactory' => $this->storeFactory,
                'storeManager' => $this->storeManager,
                'authHelper' => $this->baseAuthMock
            ]
        );
    }

    public function getToken()
    {
        $customerData = $this->customerData;
        $verified = $this->verified;

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);

        $this->customerMock->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('loadByEmail')->willReturnSelf();

        $this->customerMock->expects($this->any())->method('getId')->willReturn(false);

        $this->customerInterfaceFactoryMock->expects($this->any())->method('create')->willReturn($this->customerInterfaceMock);
        $this->customerRepoInterfaceMock->expects($this->any())->method('save')->willReturnSelf();

        $this->customerMock->expects($this->any())->method('load')->willReturnSelf();

        $result = $this->helperData->lookUpDetails($customerData, $verified);

        return $result['token'];
    }

    /**
     * Test autoLoginWithSuccessMsg
     */
    public function testAutoLoginWithSuccessMsg()
    {
        $token = $this->getToken();
        $expected = 'Customer Logged in Successfully';
        $testDomainName = "www.test.fedex.com";

        $this->customerSessionMock->expects($this->any())->method('logout')->willReturnSelf();
        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);

        $this->customerMock->expects($this->any())->method('load')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('regenerateId')->willReturnSelf();

        // B-1445896 - Improve code coverage
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->cookieMetadataFactoryMock->expects($this->any())->method('createPublicCookieMetadata')->willReturn($this->publicCookieMetadataMock);
        $this->publicCookieMetadataMock->expects($this->any())->method('setPath')->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->any())->method('setSameSite')->willReturnSelf();
        $this->cookieManagerInterfaceMock->expects($this->any())->method('setPublicCookie')->willReturnSelf();

        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->store);
        $this->store->expects($this->any())->method('getBaseUrl')->willReturn("https://staging3.office.fedex.com");
        $this->curlMock->expects($this->any())->method('post')->willReturnSelf();
        $this->curlMock->expects($this->any())->method('getBody')->willReturn('{"access_token":"test","token_type":"test"}');

        $this->customerSessionMock->expects($this->any())->method('getOnBehalfOf')->willReturn(null);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->customerSessionMock->expects($this->any())->method('getGatewayTokenExpirationTime')->willReturn(123345645645646);
        $this->encryptorInterfaceMock->expects($this->any())->method('decrypt')->willReturn($testDomainName);
        $this->customerSessionMock->expects($this->any())->method('getAuthGatewayToken')->willReturn('dsfdsfdsfsdfsf');
        $result = $this->helperData->autoLogin($token);
        $this->assertEquals($expected, $result['msg']);
    }

    /**
     * Test autoLoginWithExpiredToken
     */
    public function testAutoLoginWithExpiredToken()
    {
        $expiredToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjpmYWxzZSwidXJsIjoiaHR0cHM6XC9cL3Nob3Atc3RhZ2luZzIuZmVkZXguY29tXC9zdGF0ZWZhcm0iLCJjb21wYW55X25hbWUiOiJTdGF0ZUZhcm0iLCJwdW5jaG91dF9kYXRhIjp7ImNvbXBhbnlfaWQiOjEsImV4dHJhX2RhdGEiOnsicmVkaXJlY3RfdXJsIjoiIiwicmVzcG9uc2VfdXJsIjoiaHR0cHM6XC9cL3Nob3Atc3RhZ2luZzIuZmVkZXguY29tIiwiY29va2llIjoyNDk0MTYwNDg5ODA3NjgxNX19LCJhZGRyZXNzIjpbXSwiZXhwIjoxNjI2OTU3MTUzfQ.6DfPjNzUUQZzVeC7b3xlCKD6Vct8RKrzw01fzFw4Niw';
        $expected = 'Token Expired';

        $result = $this->helperData->autoLogin($expiredToken);
        $this->assertEquals($expected, $result['msg']);
    }

    /**
     * Test lookUpDetails
     */
    public function testLookUpDetails()
    {
        $customerData = $this->customerData;
        $verified = $this->verified;

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);

        $this->customerMock->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('loadByEmail')->willReturnSelf();

        $this->customerMock->expects($this->any())->method('getId')->willReturn(false);

        $this->customerInterfaceFactoryMock->expects($this->any())->method('create')->willReturn($this->customerInterfaceMock);
        $this->customerRepoInterfaceMock->expects($this->any())->method('save')->willReturnSelf();

        $this->customerMock->expects($this->any())->method('load')->willReturnSelf();

        $result = $this->helperData->lookUpDetails($customerData, $verified);
        $this->testToken = $result['token'];
        $this->assertIsArray($result);
    }

    /**
     * Test lookUpDetailsWithCustomerExist
     */
    public function testLookUpDetailsWithCustomerExist()
    {
        $expected = ['error' => 1, 'token' => '', 'msg' => 'Customer Email already exist'];

        $customerData = $this->customerData;
        $verified = $this->verified;

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);

        $this->customerMock->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('loadByEmail')->willReturnSelf();

        $this->customerMock->expects($this->any())->method('getId')->willReturn(true);
        $result = $this->helperData->lookUpDetails($customerData, $verified);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test autoRegisterWithException
     */
    public function testautoRegisterWithException()
    {
        $customerData = $this->customerData;
        $verified = $this->verified;

        $exception = new \Exception();

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);

        $this->customerInterfaceFactoryMock->expects($this->any())->method('create')->willReturn($this->customerInterfaceMock);
        $this->customerRepoInterfaceMock->expects($this->any())->method('save')->willReturnSelf();

        $this->customerMock->expects($this->any())->method('load')->willThrowException($exception);

        $result = $this->helperData->autoRegister($customerData, $verified);
        $this->assertIsArray($result);
    }

    /**
     * Test autoRegisterForSelfReg
     * B-1320022 - WLGN integration for selfReg customer
     */
    public function testAutoRegisterForSelfReg()
    {
        $customerData = $this->customerData;
        $customerData['status'] = 'inactive';
        $customerData['pending_approval_toggle'] = true;
        $verified = $this->verified;

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);

        $this->customerMock->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('loadByEmail')->willReturnSelf();

        $this->customerMock->expects($this->any())->method('getId')->willReturn(false);

        $this->customerInterfaceFactoryMock->expects($this->any())->method('create')->willReturn($this->customerInterfaceMock);
        $this->customerInterfaceMock->expects($this->any())->method('getExtensionAttributes')->willReturn($this->customerExtensionInterfaceMock);
        $this->customerExtensionInterfaceMock->expects($this->any())->method('getCompanyAttributes')->willReturn(false);

        $this->compCustInterfaceMock->expects($this->any())->method('create')->willReturn($this->compCustMock);
        $this->compCustMock->expects($this->any())->method('setStatus')->willReturn($this->compCustMock);

        $this->customerRepoInterfaceMock->expects($this->any())->method('save')->willReturnSelf();
        $result = $this->helperData->autoRegister($customerData, $verified, true);
        $this->assertNull($result);
    }

    /**
     *    verifyCompany
     */
    public function verifyCompany()
    {
        $this->getCompanyUrl();
        $testCompanyId = $this->testCompanyId;
        $testDomainName = $this->testDomainName;
        $type = $this->type;
        $testCustomerId = $this->testCustomerId;

        $cxml = simplexml_load_string($this->xml);

        $verified = $this->verified;

        $this->companyInterfaceFactoryMock->expects($this->any())->method('create')->willReturn($this->companyInterfaceMock);
        $this->companyInterfaceMock->expects($this->any())->method('getId')->willReturn($testCompanyId);
        $this->companyInterfaceMock->expects($this->any())->method('getDomainName')->willReturn($testDomainName);

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('load')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('getId')->willReturn($testCustomerId);

        $this->scopeConfigInterfaceMock->expects($this->exactly(6))->method('getValue')->withConsecutive(
            ['header_settings/general/header_to_domain'],
            ['header_settings/general/header_to_identity'],
            ['header_settings/general/sender_credential'],
            ['header_settings/general/sender_identity'],
            ['header_settings/general/sender_secret'],
            ['header_settings/general/sender_user_agent']
        )->willReturnOnConsecutiveCalls(
            self::HEADER_TO_DOMAIN,
            self::HEADER_TO_IDENTITY,
            self::SENDER_CREDENTIALS,
            self::SENDER_IDENTITY,
            self::SENDER_SECRET,
            self::SENDER_USER_AGENT);

        $this->authDynamicRowsFactoryMock->expects($this->any())->method('create')->willReturn($this->authDynamicRowsMock);
        $this->authDynamicRowsMock->expects($this->any())->method('getCollection')->willReturn($this->authDynamicRowsCollectionMock);

        $this->authDynamicRowsCollectionMock->expects($this->any())->method('addFieldToSelect')->with('*')->willReturnSelf();
        $this->authDynamicRowsCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();

        $iteratorRate = new \ArrayIterator([1 => $this->authDynamicRowsMock]);
        $this->authDynamicRowsCollectionMock->expects($this->any())->method('getIterator')->willReturn($iteratorRate);

        $this->authDynamicRowsMock->expects($this->any())->method('getRuleCode')->willReturn('test-email');
    }

    /**
     * Test verifyCompanyWithExtrinsicAcceptanceOption
     */
    public function testVerifyCompanyWithExtrinsicAcceptanceOption()
    {
        $this->verifyCompany();

        $type = $this->type;
        $cxml = simplexml_load_string($this->xml);
        $this->companyInterfaceMock->expects($this->any())->method('getAcceptanceOption')->willReturn(self::EXTRINSIC);

        $result = $this->helperData->verifyCompany($cxml, $type);
        $this->assertIsArray($result);
    }

    /**
     * Test verifyCompanyWithBothAcceptanceOption
     */
    public function testVerifyCompanyWithBothAcceptanceOption()
    {
        $type = $this->type;
        $cxml = simplexml_load_string($this->xml);

        $this->verifyCompany();

        $this->companyInterfaceMock->expects($this->any())->method('getAcceptanceOption')->willReturn(self::BOTH);

        $result = $this->helperData->verifyCompany($cxml, $type);
        $this->assertIsArray($result);
    }

    /**
     * Test verifyCompanyWithContactAcceptanceOption
     */
    public function testVerifyCompanyWithContactAcceptanceOption()
    {
        $type = $this->type;
        $cxml = simplexml_load_string($this->xml);

        $this->verifyCompany();

        $this->companyInterfaceMock->expects($this->any())->method('getAcceptanceOption')->willReturn(self::CONTACT);

        $result = $this->helperData->verifyCompany($cxml, $type);
        $this->assertIsArray($result);
    }

    /**
     * Test verifyCompanyWithWrongDomainName
     */
    public function testVerifyCompanyWithWrongDomainName()
    {
        $testCompanyId = $this->testCompanyId;
        $type = $this->type;
        $testDomainName = 'MAGENTO123';
        $this->getCompanyUrl();
        $cxml = simplexml_load_string($this->xml);

        $verified = $this->verified;

        $this->companyInterfaceFactoryMock->expects($this->any())->method('create')->willReturn($this->companyInterfaceMock);
        $this->companyInterfaceMock->expects($this->any())->method('getId')->willReturn($testCompanyId);
        $this->companyInterfaceMock->expects($this->any())->method('getDomainName')->willReturn($testDomainName);

        $result = $this->helperData->verifyCompany($cxml, $type);
        $this->assertIsArray($result);

    }

    /**
     * Test validateHeaderWithTypeNotCustomer
     * type != customer
     * Wrong Domain name
     */
    public function testValidateHeaderWithTypeNotCustomer()
    {
        $testCompanyId = $this->testCompanyId;
        $type = $this->type;
        $testDomainName = 'MAGENTO123';

        $cxml = simplexml_load_string($this->xml);
        $type = self::EXTRINSIC;

        $headerToDomain = $this->scopeConfigInterfaceMock->method('getValue')->withConsecutive(["header_settings/general/header_to_domain"])->willReturnOnConsecutiveCalls($testDomainName);

        $expected = ['result' => 0, 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => '']];
        $result = $this->helperData->validateHeader($cxml, $type);
        $this->assertEquals($expected, $result);

    }

    /**
     * Test validateXmlRuleData
     */
    public function testValidateXmlRuleData()
    {
        $xml = simplexml_load_string($this->xml);

        $type = self::EXTRINSIC;
        $rule = ['testRule'];
        $expected = 0;
        $result = $this->helperData->validateXmlRuleData($xml, $type, $rule);
        $this->assertEquals($expected, $result);

        $rule = ['UniqueName', 'UserEmail', 'Firstname'];
        $expected = 1;
        $result = $this->helperData->validateXmlRuleData($xml, $type, $rule);
        $this->assertEquals($expected, $result);

        $type = self::CONTACT;
        $rule = ['testRule'];
        $expected = 0;
        $result = $this->helperData->validateXmlRuleData($xml, $type, $rule);
        $this->assertEquals($expected, $result);

        $rule = ['Name', 'Email'];
        $expected = 1;
        $result = $this->helperData->validateXmlRuleData($xml, $type, $rule);
        $this->assertEquals($expected, $result);

        $type = self::BOTH;
        $rule = ['testRule'];
        $expected = 0;
        $result = $this->helperData->validateXmlRuleData($xml, $type, $rule);
    }

    /**
     * Test throwError
     */
    public function testThrowError()
    {
        $exceptionMessage = 'Test Exception Message';
        $this->timezoneInterfaceMock->expects($this->any())->method('date')->willReturn($this->dateTimeMock);
        $this->dateTimeMock->expects($this->any())->method('format')->willReturn('2022-02-22 07:10:00');

        $result = $this->helperData->throwError($exceptionMessage);
        $this->assertIsString($result);
    }

    /**
     * Test isActiveCustomerWithStatus
     */
    public function testIsActiveCustomerWithStatus()
    {
        $expected = 0;
        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getId')->willReturn($this->testCustomerId);

        $this->customerRepoInterfaceMock->expects($this->any())->method('getById')->willReturn($this->customerInterfaceMock);
        $this->customerInterfaceMock->expects($this->any())->method('getExtensionAttributes')->willReturn($this->customerExtensionInterfaceMock);
        $this->customerExtensionInterfaceMock->expects($this->any())->method('getCompanyAttributes')->willReturn($this->companyCustomerInterfaceMock);
        $this->companyCustomerInterfaceMock->expects($this->any())->method('getStatus')->willReturn(null);

        $this->assertEquals($expected, $this->helperData->isActiveCustomer($this->customerMock));
    }

    /**
     * Test isActiveCustomerWithoutStatus
     */
    public function testIsActiveCustomerWithoutStatus()
    {
        $expected = 0;
        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getId')->willReturn($this->testCustomerId);

        $this->customerRepoInterfaceMock->expects($this->any())->method('getById')->willReturn($this->customerInterfaceMock);
        $this->customerInterfaceMock->expects($this->any())->method('getExtensionAttributes')->willReturn($this->customerExtensionInterfaceMock);
        $this->customerExtensionInterfaceMock->expects($this->any())->method('getCompanyAttributes')->willReturn([]);

        $this->assertEquals($expected, $this->helperData->isActiveCustomer($this->customerMock));
    }

    /**
     * Test isActiveCustomerWithoutStatus
     */
    public function testSendToken()
    {
        $data = ['website_url' => ''];
        $token = 'TestToken';
        $this->assertIsString($this->helperData->sendToken($data, $token));
    }

    /**
     * Test getTazTokenWithException
     */
    public function testGetTazTokenWithException()
    {
        $exception = new \Exception();
        $this->curlMock->expects($this->any())->method('post')->willReturnSelf();
        $this->curlMock->expects($this->any())->method('getBody')->willThrowException($exception);

        $result = $this->helperData->getTazToken();
        $this->assertNull($result);
    }

    /**
     * Test testGetTazTokenInStore
     */
    public function testGetTazTokenInStore()
    {
        $this->customerSessionMock->expects($this->any())->method('getOnBehalfOf')->willReturn('onbehalf');

        $result = $this->helperData->getTazToken();
        $this->assertNull($result);
    }

    /**
     * Test method to Get Taz Token with true public flag
     */
    public function testGetTazTokenWithPublicFlag()
    {
        $tazTokenOutput = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDYyODQ4MDUsImF1dGhvcml0aWVzIjpbInRhei5zeXN0ZW0udXNlciJdLCJqdGkiOiIwYzFhZjFlYi1mOTk3LTQ5OTktYTc4NS0xYWFiZWE4Zjg4ZTciLCJjbGllbnRfaWQiOiIzNTM3MTMxX0ZYT19NQVJLRVRQTEFDRV9VSSJ9.VBSu5ZVHWmZFumikJb2ZwilexorKvFhLEThcdmRUYfWxhM4eJMD7mYPekNPf56O-_4G9arPxcIASYZahJ0vHFokibz7_iUZhF37njiAiaIbHaQvIicZ5ki4WdkJ8mpQHJENuv7Kr4t3cl_Z3esURC6SgagX14cF46jcFhFuj8cTxG16MTEeOXfokjtNPxVri2VA-BqNmoGE0mELTFfejdTrq7z5U9t5ebjQ0lM0780XIjqTxV1Us5U32jBp5u_9d9QFKdhdWozS5hvMj7yraRJHheVGx6hog01pgupIw0L2GKpn5yag2PC6OhfenbxooH3fX3VStQD6-_FY5JXwso10X5Yok7WkQH39djHUPPpY-hC_BiQTwRCo-Sfxx1j3NenHXCntUs8xmYstg8Z32bTa_Sbc6s9bpiT3UB57pkuhMkPhZg0M3c7q9Pjlv1AkciWL1-pnJGyrScy8qZRqBllE17FCtSnccSg1gc7h4P0BmlsdreblIdjYkkCHl9jF-sdIzQTzKZMQt0WbRR0_APeSaXThdv_BDJzlshtr5bH80vxUvEyMPeuAlvbmqI-0nhPmGThur75074vRiHUoqrFaHX7nJa9Dg3wnem4tvFWX6ow_r11fHyYPsflqBYjjhTlzxwLXiLqNtBVYgDbmwze13PyY-LoZ5NF3LRolD6YE","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"0c1af1eb-f997-4999-a785-1aabea8f88e7"}';
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->curlMock->expects($this->any())->method('post')->willReturnSelf();
        $this->curlMock->expects($this->any())->method('getBody')->willReturn($tazTokenOutput);

        $result = $this->helperData->getTazToken(true);
        $this->assertEquals(null, $result);
    }

    /**
     * Test getGatewayTokenWithException
     */
    public function testGetGatewayTokenWithException()
    {
        $exception = new \Exception();
        $this->curlMock->expects($this->any())->method('post')->willReturnSelf();
        $this->curlMock->expects($this->any())->method('getBody')->willThrowException($exception);

        $result = $this->helperData->getGatewayToken();
        $this->assertNull($result);
    }

    /**
     * Test validateCustomer
     */
    public function testvalidateCustomer()
    {
        $customer = $this->customerMock;
        $company = $this->testCustomerId;

        $this->customerRepoInterfaceMock->expects($this->any())->method('getById')->willReturn($this->customerInterfaceMock);
        $this->customerInterfaceMock->expects($this->any())->method('getExtensionAttributes')->willReturn($this->customerExtensionInterfaceMock);
        $this->customerExtensionInterfaceMock->expects($this->any())->method('getCompanyAttributes')->willReturn($this->companyCustomerInterfaceMock);

        $this->companyCustomerInterfaceMock->method('getCompanyId')->withConsecutive([], [])->willReturnOnConsecutiveCalls($this->testCustomerId, 2);

        $this->assertTrue($this->helperData->validateCustomer($customer, $company));
        $this->assertFalse($this->helperData->validateCustomer($customer, $company));
    }

    /**
     * Test getCustomerNewId
     */
    public function testGetCustomerNewId()
    {
        $this->registryMock->expects($this->any())->method('registry');
        $this->assertNull($this->helperData->getCustomerNewId());
    }

    /**
     * Test extractCustomerData
     */
    public function testExtractCustomerData()
    {
        $company_name = 'TestCompany';
        $this->helperData->_data = ['Email' => 'testcompany@test.com', 'Name' => 'TestCompany Lastname'];

        $this->helperData->ruleType = 'both';
        $expectedResultWithBoth = ['email' => 'testcompany@test.com', 'firstname' => 'TestCompany', 'lastname' => 'Lastname'];
        $this->assertEquals($expectedResultWithBoth, $this->helperData->extractCustomerData($company_name, false));


        $this->helperData->ruleType = 'extrinsic';
        $expectedResultWithExtrinsic = ['error' => 1, 'msg' => 'Invalid Email.'];
        $this->assertEquals($expectedResultWithExtrinsic, $this->helperData->extractCustomerData($company_name, false));

        $this->helperData->ruleType = 'contact';
        $this->assertEquals($expectedResultWithBoth, $this->helperData->extractCustomerData($company_name, false));

        $this->helperData->ruleType = 'NoValue';
        $this->assertEquals(null, $this->helperData->extractCustomerData($company_name, false));
    }

    /**
     * Test extractContactDataWithMultipleConditions
     */
    public function testExtractContactDataWithMultipleConditions()
    {
        $this->helperData->ruleType = 'contact';
        $company_name = 'TestCompany';

        // with email only
        $this->helperData->_data = ['Email' => 'testcompany@test.com'];
        $expectedResult = ['email' => 'testcompany@test.com', 'firstname' => 'testcompany', 'lastname' => 'User'];
        $this->assertEquals($expectedResult, $this->helperData->extractContactData($company_name, false));

        // with first and last name only
        $this->helperData->_data = ['Name' => 'TestCompany Lastname'];
        $expectedResult = ['email' => 'testcompany_testcompanylastname@notestcompany.com', 'firstname' => 'TestCompany', 'lastname' => 'Lastname'];
        $this->assertEquals($expectedResult, $this->helperData->extractContactData($company_name, false));

        // with first name only
        $this->helperData->_data = ['Name' => 'TestCompany'];
        $expectedResult = ['email' => 'testcompany_testcompany@notestcompany.com', 'firstname' => 'TestCompany', 'lastname' => 'User'];
        $this->assertEquals($expectedResult, $this->helperData->extractContactData($company_name, false));

        // with empty parameters
        $this->helperData->_data = [];
        $expectedResult = ['error' => 1, 'msg' => 'Invalid Email.'];
        $this->assertEquals($expectedResult, $this->helperData->extractContactData($company_name, false));
    }

    /**
     * Test extractCombinationWithMultipleConditions
     */
    public function testExtractCombinationWithMultipleConditions()
    {
        $this->helperData->ruleType = 'both';
        $this->helperData->emailCode = true;
        $this->helperData->_extrinsicData = [1 => 'testemail@test.com'];
        $company_name = 'TestCompany';

        // with first and last name
        $this->helperData->_data = ['Name' => 'TestCompany Lastname'];
        $expectedResult = ['email' => 'testemail@test.com', 'firstname' => 'TestCompany', 'lastname' => 'Lastname'];
        $this->assertEquals($expectedResult, $this->helperData->extractCombination($company_name, false));

        // with first name only
        $this->helperData->_data = ['Name' => 'TestCompany'];
        $expectedResult = ['email' => 'testemail@test.com', 'firstname' => 'TestCompany', 'lastname' => 'User'];
        $this->assertEquals($expectedResult, $this->helperData->extractCombination($company_name, false));

        // with full name and extrinsic data
        $this->helperData->_data = ['Name' => 'TestCompany Lastname'];
        $this->helperData->_extrinsicData = [1 => 'test extrinsic'];
        $expectedResult = ['email' => 'testcompany_testextrinsic@notestcompany.com', 'firstname' => 'TestCompany', 'lastname' => 'Lastname'];
        $this->assertEquals($expectedResult, $this->helperData->extractCombination($company_name, false));

        // with full name and blank extrinsic data
        $this->helperData->_data = ['Name' => 'TestCompany Lastname'];
        $this->helperData->_extrinsicData = [1 => ''];
        $expectedResult = ['error' => 1, 'msg' => 'Invalid Email.'];
        $this->assertEquals($expectedResult, $this->helperData->extractCombination($company_name, false));

        // with blank name and extrinsic data with multiple elements
        $this->helperData->_data = ['Name' => ''];
        $this->helperData->_extrinsicData = [1 => 'test', 2 => 'extrinsic'];
        $expectedResult = ['email' => 'testcompany_test_extrinsic@notestcompany.com', 'firstname' => 'test', 'lastname' => 'extrinsic'];
        $this->assertEquals($expectedResult, $this->helperData->extractCombination($company_name, false));

        // with blank name and extrinsic data with single elements
        $this->helperData->_data = ['Name' => ''];
        $this->helperData->_extrinsicData = [1 => 'test extrinsic'];
        $expectedResult = ['email' => 'testcompany_testextrinsic@notestcompany.com', 'firstname' => 'testextrinsic', 'lastname' => 'User'];
        $this->assertEquals($expectedResult, $this->helperData->extractCombination($company_name, false));

        // with blank name and extrinsic data with email
        $this->helperData->_data = ['Name' => ''];
        $this->helperData->_extrinsicData = [1 => 'testemail@test.com'];
        $expectedResult = ['email' => 'testemail@test.com', 'firstname' => 'testcompany', 'lastname' => 'User'];
        $this->assertEquals($expectedResult, $this->helperData->extractCombination($company_name, false));
    }

    /**
     * Test extractExtrinsicWithMultipleConditions
     */
    public function testExtractExtrinsicWithMultipleConditions()
    {
        $this->helperData->ruleType = 'extrinsic';
        $this->helperData->emailCode = true;
        $company_name = 'TestCompany';

        // with extrisic data having email
        $this->helperData->_extrinsicData = [1 => 'testemail@test.com'];
        $expectedResult = ['email' => 'testemail@test.com', 'firstname' => 'testcompany', 'lastname' => 'User'];
        $this->assertEquals($expectedResult, $this->helperData->extractExtrinsic($company_name, false));


        // with extrisic data having multiple elements
        $this->helperData->_extrinsicData = [1 => 'Extrinsic Data', 2 => 'MAGENTO1', 3 => 'NetworkId1', 4 => 'AribaNetworkUserId', 5 => 'Hubspan'];
        $expectedResult = [
            'email' => 'testcompany_extrinsicdata_magento1_networkid1_aribanetworkuserid@nodomain.com',
            'firstname' => 'extrinsicdatamagento1networkid',
            'lastname' => 'hubspan',
            'external_identifier' => 'testcompany_extrinsicdata_magento1_networkid1_aribanetworkuserid_hubspan@notestcompany.com'
        ];
        $this->assertEquals($expectedResult, $this->helperData->extractExtrinsic($company_name, false));
    }

    /**
     * Test getRetailUrl
     */
    public function testGetRetailUrl()
    {
        $testDomainName = "www.test.fedex.com";
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->with('is_retail_configuration')->willReturn(true);
        $headerToDomain = $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')->with("fedex/gateway_token/gateway_token_api_url", ScopeInterface::SCOPE_STORE)->willReturn($testDomainName);
        $this->assertEquals($testDomainName, $this->helperData->getRetailUrl());
    }

    /**
     * Test getRetailAClientID
     */
    public function testGetRetailAClientID()
    {
        $testDomainName = "www.test.fedex.com";
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->with('is_retail_configuration')->willReturn(true);
        $this->encryptorInterfaceMock->expects($this->any())->method('decrypt')->with($testDomainName)->willReturn($testDomainName);
        $headerToDomain = $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')->with("fedex/gateway_token/client_id", ScopeInterface::SCOPE_STORE)->willReturn($testDomainName);
        $this->assertEquals($testDomainName, $this->helperData->getRetailAClientID());
    }


    /**
     * Test getRetailAClientSecret
     */
    public function testGetRetailASecret()
    {
        $testDomainName = "www.test.fedex.com";
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->with('is_retail_configuration')->willReturn(false);
        $this->encryptorInterfaceMock->expects($this->any())->method('decrypt')->with($testDomainName)->willReturn($testDomainName);
        $headerToDomain = $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')->with("fedex/gateway_token/client_secret", ScopeInterface::SCOPE_STORE)->willReturn($testDomainName);
        $this->assertEquals($testDomainName, $this->helperData->getRetailAClientSecret());
    }

    /**
     * Test getRetailAGrantType
     */
    public function testGetRetailAGrantType()
    {
        $testDomainName = "www.test.fedex.com";
        $headerToDomain = $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')->with("fedex/retail_gtn_auth_token/retail_auth_grant_type", ScopeInterface::SCOPE_STORE)->willReturn($testDomainName);
        $this->assertEquals($testDomainName, $this->helperData->getRetailAGrantType());
    }

    /**
     * Test getRetailAScope
     */
    public function testGetRetailAScope()
    {
        $testDomainName = "www.test.fedex.com";
        $headerToDomain = $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')->with("fedex/retail_gtn_auth_token/retail_auth_scope", ScopeInterface::SCOPE_STORE)->willReturn($testDomainName);
        $this->assertEquals($testDomainName, $this->helperData->getRetailAScope());
    }

    /**
     * Test getRetailGTNUrl
     */
    public function testGetRetailGTNUrlwithToggleOn()
    {
        $testDomainName = "www.test.fedex.com";
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->with('is_optimize_configuration')->willReturn(true);
        $headerToDomain = $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')->with("fedex/general/gtn_post_api_url", ScopeInterface::SCOPE_STORE)->willReturn($testDomainName);
        $this->assertEquals($testDomainName, $this->helperData->getRetailGTNUrl());
    }

    /**
     * Test getRetailGTNUrl
     */
    public function testGetRetailGTNUrlwithToggleOff()
    {
        $testDomainName = "www.test.fedex.com";
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->with('is_optimize_configuration')->willReturn(false);
        $headerToDomain = $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')->with("fedex/gtn/gtn_post_api_url", ScopeInterface::SCOPE_STORE)->willReturn($testDomainName);
        $this->assertEquals($testDomainName, $this->helperData->getRetailGTNUrl());
    }

    public function getTokenInfo()
    {
        $testDomainName = "www.test.fedex.com";
        $this->encryptorInterfaceMock->expects($this->any())->method('decrypt')->with($testDomainName)->willReturn($testDomainName);
        $this->scopeConfigInterfaceMock->method('getValue')
            ->withConsecutive(
                ['fedex/gateway_token/gateway_token_api_url', ScopeInterface::SCOPE_STORE],
                ['fedex/gateway_token/client_id', ScopeInterface::SCOPE_STORE],
                ['fedex/gateway_token/client_secret', ScopeInterface::SCOPE_STORE],
                ['fedex/retail_gtn_auth_token/retail_auth_grant_type', ScopeInterface::SCOPE_STORE],
                ['fedex/retail_gtn_auth_token/retail_auth_scope', ScopeInterface::SCOPE_STORE]
            )
            ->willReturnOnConsecutiveCalls($testDomainName, $testDomainName, $testDomainName, $testDomainName, $testDomainName);
    }

    /**
     * Test getGTNNumber
     * B-1445896
     */
    public function testGetGTNNumber()
    {
        // $this->getTokenInfo();
        $this->customerSessionMock->expects($this->any())->method('getOnBehalfOf')->willReturn('onbehalf');

        $this->curlMock->expects($this->any())->method('post')->willReturnSelf();
        $this->curlMock->expects($this->any())->method('getBody')->willReturn('{"output":{"gtn": "123456"}}');
        $this->curlMock->expects($this->any())->method('getStatus')->willReturn(200);

        $expectedResult = '2010123456';
        $this->assertEquals($expectedResult, $this->helperData->getGTNNumber());
    }


    /**
     * Test getGTNNumber
     * B-1445896
     */
    public function testInStoreGetGTNNumber()
    {
        //  $this->getTokenInfo();
        $this->customerSessionMock->expects($this->any())->method('getOnBehalfOf')->willReturn('onbehalf');

        $this->curlMock->expects($this->any())->method('post')->willReturnSelf();
        $this->curlMock->expects($this->any())->method('getBody')->willReturn('{"output":{"gtn": "123456"}}');
        $this->curlMock->expects($this->any())->method('getStatus')->willReturn(200);

        $this->requestQueryValidatorMock->expects($this->once())->method('isGraphQl')
            ->willReturn(true);

        $expectedResult = '2020123456';
        $this->assertEquals($expectedResult, $this->helperData->getGTNNumber());
    }

    /**
     * Test getGTNNumber
     * B-1445896
     */
    public function testGetGTNNumberWithFalseCurlOutput()
    {
        //$this->getTokenInfo();
        $this->customerSessionMock->expects($this->any())->method('getOnBehalfOf')->willReturn('onbehalf');

        $this->curlMock->expects($this->any())->method('post')->willReturnSelf();
        $this->curlMock->expects($this->any())->method('getBody')->willReturn(false);

        $expectedResult = null;
        $this->assertEquals($expectedResult, $this->helperData->getGTNNumber());
    }

    /**
     * Test getGTNNumber
     * B-1445896
     */
    public function testGetGTNNumberWithBadCurlResponse()
    {
        //$this->getTokenInfo();
        $this->customerSessionMock->expects($this->any())->method('getOnBehalfOf')->willReturn('onbehalf');

        $this->curlMock->expects($this->any())->method('post')->willReturnSelf();
        $this->curlMock->expects($this->any())->method('getBody')->willReturn('{"output":{"gtn": "123456"}}');
        $this->curlMock->expects($this->any())->method('getStatus')->willReturn(400);

        $expectedResult = null;
        $this->assertEquals($expectedResult, $this->helperData->getGTNNumber());
    }

    /**
     * Test getGTNNumber
     * B-1445896
     */
    public function testGetRetailAuthToken()
    {
        $this->getTokenInfo();
        $this->curlMock->expects($this->any())->method('post')->willReturnSelf();
        $this->curlMock->expects($this->any())->method('getBody')->willReturn('{"access_token":"abcdefg"}');

        $expectedResult = 'abcdefg';
        $this->assertEquals($expectedResult, $this->helperData->getRetailAuthToken());
    }

    /**
     * Test getGTNNumber
     * B-1445896
     */
    public function testGetRetailAuthTokenWithError()
    {
        $testDomainName = "www.test.fedex.com";
        $this->encryptorInterfaceMock->expects($this->any())->method('decrypt')->with($testDomainName)->willReturn($testDomainName);
        $this->scopeConfigInterfaceMock->method('getValue')
            ->withConsecutive(
                ['fedex/gateway_token/gateway_token_api_url', ScopeInterface::SCOPE_STORE],
                ['fedex/gateway_token/client_id', ScopeInterface::SCOPE_STORE],
                ['fedex/gateway_token/client_secret', ScopeInterface::SCOPE_STORE],
                ['fedex/retail_gtn_auth_token/retail_auth_grant_type', ScopeInterface::SCOPE_STORE],
                ['fedex/retail_gtn_auth_token/retail_auth_scope', ScopeInterface::SCOPE_STORE]
            )
            ->willReturnOnConsecutiveCalls($testDomainName, $testDomainName, $testDomainName, $testDomainName, $testDomainName);

        $this->curlMock->expects($this->any())->method('post')->willReturnSelf();
        $this->curlMock->expects($this->any())->method('getBody')->willReturn('{"access_token":"abcdefg", "error":"ERROR MSG"}');

        $expectedResult = null;
        $this->assertEquals($expectedResult, $this->helperData->getRetailAuthToken());
    }

    //B-1445896
    public function testRemoveSpaceFromNameToggle()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertEquals(true, $this->helperData->removeSpaceFromNameToggle());
    }


    //B-1445896
    public function testRemoveSpaceFromNameToggleWithFalse()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->assertEquals(false, $this->helperData->removeSpaceFromNameToggle());
    }

    /**
     * Test method to Get Taz Token with true public flag
     * // B-1445896
     */
    public function testGetTazToken()
    {
        $this->customerSessionMock->expects($this->any())->method('getTazTokenExpirationTime')->willReturn(1234);

        $testDomainName = "www.test.fedex.com";
        $tazClientId = '123456';
        $tazClientSecret = 'tyutyutyu';
        $gatewayClientId = '123456';
        $this->encryptorInterfaceMock->expects($this->any())->method('decrypt')->willReturn($testDomainName);
        $this->scopeConfigInterfaceMock->method('getValue')
            ->withConsecutive(
                ['fedex/taz/service_tokens_api_url', ScopeInterface::SCOPE_STORE],
                ['fedex/taz/public_client_id', ScopeInterface::SCOPE_STORE],
                ['fedex/taz/public_client_secret', ScopeInterface::SCOPE_STORE],
                ['fedex/gateway_token/client_id', ScopeInterface::SCOPE_STORE]
            )
            ->willReturnOnConsecutiveCalls($testDomainName, $tazClientId, $tazClientSecret, $gatewayClientId);

        $this->customerSessionMock->expects($this->any())->method('getOnBehalfOf')->willReturn(null);

        $this->curlMock->expects($this->any())->method('getBody')->willReturn('{"access_token":"abcdefg", "expires_in":"999999"}');

        $this->customerSessionMock->expects($this->any())->method('setTazToken')->willReturn('TAZ_TOKEN');
        $this->customerSessionMock->expects($this->any())->method('setTazTokenExpirationTime')->willReturnSelf();

        $this->customerSessionMock->expects($this->any())->method('getTazToken')->willReturn('TAZ_TOKEN');
        $result = $this->helperData->getTazToken(true);
        $this->assertEquals('TAZ_TOKEN', $result);
    }

    /**
     * Test method to Get Taz Token with true public flag
     * // B-1445896
     */
    public function testGetGatewayToken()
    {
        $this->customerSessionMock->expects($this->any())->method('getGatewayTokenExpirationTime')->willReturn(1234);

        $gtwyTknUrl = "www.test.fedex.com";
        $gtwayClientId = '123456';
        $gtwayClientSecret = 'tyutyutyu';

        $this->encryptorInterfaceMock->expects($this->any())->method('decrypt')->willReturn($gtwyTknUrl);
        $this->scopeConfigInterfaceMock->method('getValue')
            ->withConsecutive(
                ['fedex/gateway_token/gateway_token_api_url', ScopeInterface::SCOPE_STORE],
                ['fedex/gateway_token/client_id', ScopeInterface::SCOPE_STORE],
                ['fedex/gateway_token/client_secret', ScopeInterface::SCOPE_STORE]
            )
            ->willReturnOnConsecutiveCalls($gtwyTknUrl, $gtwayClientId, $gtwayClientSecret);

        $this->customerSessionMock->expects($this->any())->method('getOnBehalfOf')->willReturn('onbehalf');
        $this->curlMock->expects($this->any())->method('post')->willReturnSelf();
        $this->curlMock->expects($this->any())->method('getBody')->willReturn('{"access_token":"abcdefg"}');

        $result = $this->helperData->getGatewayToken();
        $this->assertEquals(null, $result);
    }

    /**
     * Test extractExtrinsicWithMultiWordFirstName
     * // B-1445896
     */
    public function testExtractExtrinsicWithMultiWordFirstName()
    {
        $this->helperData->ruleType = 'extrinsic';
        $this->helperData->emailCode = false;
        $company_name = 'TestCompany';

        // with extrisic data having multiple elements
        $this->helperData->_extrinsicData = ['ExtrinsicData'];
        $expectedResult = [
            'email' => 'testcompany_extrinsicdata@nodomain.com',
            'firstname' => 'extrinsicdata',
            'lastname' => 'user',
            'external_identifier' => 'testcompany_extrinsicdata@notestcompany.com'
        ];
        $this->assertEquals($expectedResult, $this->helperData->extractExtrinsic($company_name, false));
    }

    /**
     * Test getNameIfCountLessThanOne
     * // B-1445896
     */
    public function testGetNameIfCountLessThanOne()
    {
        $nameList = ['Fname Lname'];
        $expectedResult = [
            'firstname' => 'Fname',
            'lastname' => 'Lname'
        ];
        $this->assertEquals($expectedResult, $this->helperData->getNameIfCountLessThanOne($nameList));
    }

    /**
     * Test getNameFromNameList
     * // B-1445896
     */
    public function testGetNameFromNameList()
    {
        $nameList = ['Fname1 Lname'];
        $expectedResult = [
            'firstname' => 'Fname1',
            'lastname' => 'Lname'
        ];
        $this->assertEquals($expectedResult, $this->helperData->getNameFromNameList($nameList));
    }

    /**
     * Test addCacheInfoInApiUrl
     * // B-1445896
     */
    public function testAddCacheInfoInApiUrl()
    {
        $apiUrl = 'https://www.shop.fedex.com&noCache=';
        $this->assertIsString($this->helperData->addCacheInfoInApiUrl($apiUrl));
    }

    /**
     * Test setTazTokenInfo
     * // B-1445896
     */
    public function testSetTazTokenInfo()
    {
        $token = 'TAZ_TOKEN';
        $responseData = ['expires_in' => 123];

        $this->customerSessionMock->expects($this->any())->method('setTazToken')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('setTazTokenExpirationTime')->willReturnSelf();
        $this->assertEquals(null, $this->helperData->setTazTokenInfo($token, $responseData));
    }

    public function getCompanyUrl()
    {
        $url = "https://staging3.office.fedex.com/ondemand";

        $this->companyInterfaceMock->expects($this->any())->method('getCompanyUrl')
            ->willReturn("https://test.com");
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->additionalDataFactory->expects($this->any())->method('create')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCollection')->willReturn($this->additionalDataCollection);
        $this->additionalDataCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('getFirstItem')->willReturn($this->additionalData);
        $this->storeFactory->expects($this->any())->method('create')->willReturn($this->store);
        $this->store->expects($this->any())->method('load')->willReturnSelf();
        $this->store->expects($this->any())->method('getUrl')->willReturn($url);
    }

    public function testGetCompanyUrl()
    {
        $url = "https://staging3.office.fedex.com/ondemand";
        $this->getCompanyUrl();
        $this->assertEquals($url, $this->helperData->getCompanyUrl($this->companyInterfaceMock));
    }
}
