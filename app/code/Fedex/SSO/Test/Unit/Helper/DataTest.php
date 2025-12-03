<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Test\Unit\Helper;

use Exception;
use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\Company\Model\ResourceModel\AdditionalData\Collection as AdditionalDataCollection;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SSO\Helper\Data;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Math\Random;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\Cookie\CookieMetadata;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Eav\Api\AttributeRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\Base\Helper\Auth;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataTest extends TestCase
{
    protected $storeManager;
    protected $_cookieMetadata;
    protected $publicCookieMetadataMock;
    protected $mathRandom;
    protected $customerCollection;
    protected $resourceConnection;
    protected $attributeRepositoryInterface;
    protected $curl;
    protected $customerInterface;
    protected $customerRepositoryInterface;
    protected $regionFactory;
    protected $region;
    protected $addressDataFactory;
    protected $purpleGatewayTokenMock;
    protected $ssoConfigMock;
    protected $address;
    /**
     * @var (\Magento\Customer\Api\Data\AddressInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $addressInterface;
    protected $customerFactory;
    protected $customerModel;
    protected $cookieMetadataFactory;
    protected $punchoutHelper;
    protected $customerSession;
    protected $toggleConfig;
    protected $sdeHelper;
    protected $additionalDataFactory;
    protected $companyRepository;
    protected $companyManagement;
    protected $customerInterfaceFactory;
    protected $storeInterface;
    protected $additionalData;
    protected $additionalDataCollection;
    protected $companyItem;
    public const CONFIG_BASE_PATH = 'sso/general/';

    /**
     * CookieManagerInterface Variable
     *
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $_cookieManager;

    /**
     * @var ObjectManager
     */
    protected $_objectManager;

    /**
     * SSO Helper variable
     *
     * @var \Fedex\SSO\Helper\Data
     */
    protected $_ssoHelperData;

    /**
     * @var ScopeConfigInterface $scopeConfigMock
     */
    protected $scopeConfigMock;

    protected Auth|MockObject $baseAuthMock;
    private Data|MockObject $helperData;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(
                [
                    'getId',
                    'getStore',
                    'getBaseUrl',
                    'getWebsite',
                    'getWebsiteId',
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->_cookieManager = $this->getMockBuilder(CookieManagerInterface::class)
            ->setMethods(
                [
                    'deleteCookie',
                    'getCookie',
                    'setPublicCookie'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->_cookieMetadata = $this->getMockBuilder(CookieMetadata::class)
            ->setMethods(
                [
                    'createPublicCookieMetadata',
                    'setDomain',
                    'setPath',
                    'setHttpOnly',
                    'setSecure',
                    'setSameSite',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->publicCookieMetadataMock = $this->getMockBuilder(PublicCookieMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mathRandom = $this->getMockBuilder(Random::class)
            ->setMethods(
                [
                    'getRandomString',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerCollection = $this->getMockBuilder(Collection::class)
            ->setMethods(
                [
                    'addAttributeToSelect',
                    'addAttributeToFilter',
                    'load',
                    'getData',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->setMethods(
                [
                    'getConnection',
                    'select',
                    'from',
                    'where',
                    'fetchRow',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributeRepositoryInterface = $this->getMockBuilder(AttributeRepositoryInterface::class)
            ->setMethods(['get','getAttributeId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->curl = $this->getMockBuilder(Curl::class)
            ->setMethods(
                [
                    'setHeaders',
                    'setOption',
                    'post',
                    'getBody',
                    'getStatus',
                    'setOptions'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerInterface = $this->getMockBuilder(CustomerInterface::class)
            ->setMethods(['setWebsiteId','setFirstname','setLastname', 'setData', 'getCustomerCanvaId', 'save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerRepositoryInterface = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->setMethods(
                [
                    'get',
                    'save',
                    'setFirstname',
                    'setLastname',
                    'setCustomAttribute',
                    'setEmail',
                    'setPassword',
                    'getDefaultShipping',
                    'getDefaultBilling',
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->regionFactory = $this->getMockBuilder(RegionFactory::class)
            ->setMethods(
                [
                    'create',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->region = $this->getMockBuilder(Region::class)
            ->setMethods(
                [
                    'loadByCode',
                    'getId',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();


        $this->addressDataFactory = $this->getMockBuilder(AddressFactory::class)
            ->setMethods(
                [
                    'create',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

          $this->purpleGatewayTokenMock = $this->getMockBuilder(\Fedex\MarketplaceRates\Helper\Data::class)
            ->setMethods(['getFedexRatesToken'])
            ->disableOriginalConstructor()
            ->getMock();
          $this->ssoConfigMock = $this->getMockBuilder(Config::class)
            ->setMethods(['getFclLogoutApiUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->address = $this->getMockBuilder(Address::class)
            ->setMethods(
                [
                    'save',
                    'setCustomerId',
                    'load',
                    'setEmailId',
                    'setFirstname',
                    'setLastname',
                    'setCountryId',
                    'setRegionId',
                    'setCity',
                    'setPostcode',
                    'setExt',
                    'setTelephone',
                    'setCompany',
                    'setStreet',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressInterface = $this->getMockBuilder(AddressInterface::class)
            ->setMethods([
                'setCustomerId',
                'setIsDefaultShipping',
                'setFirstname',
                'setLastname',
                'setCountryId',
                'setRegionId',
                'setCity',
                'setPostcode',
                'setStreet',
                'setTelephone',
                'setCustomAttribute',
                'setCompany',
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerFactory = $this->getMockBuilder(CustomerFactory::class)
            ->setMethods([
                'create',
                'setWebsiteId',
                'setFirstname',
                'setLastname',
                'setEmail',
                'save',
                'loadByEmail',
                'load',
                'getId',
                'getHash',
                'setData',
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerModel = $this->getMockBuilder(Customer::class)
            ->setMethods([
                'setWebsiteId',
                'setFirstname',
                'setLastname',
                'setEmail',
                'save',
                'loadByEmail',
                'load',
                'getId',
                'getHash',
                'setData',
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieMetadataFactory = $this->getMockBuilder(CookieMetadataFactory::class)
            ->setMethods([
                'createPublicCookieMetadata',
                'setDomain',
                'setPath',
                'setHttpOnly',
                'setSecure',
                'setSameSite',
                'setDuration',
                'setPublicCookie'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->punchoutHelper = $this->getMockBuilder(PunchoutHelper::class)
            ->setMethods([
                'getTazToken',
                'getRetailAuthToken',
                'getAuthGatewayToken'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->setMethods([
                'setCustomerAsLoggedIn',
                'isLoggedIn',
                'unsFclFdxLogin',
                'getFclFdxLogin',
                'setFclFdxLogin',
                'setProfileSession',
                'setCreditCardList',
                'setFedexAccountsList',
                'getCustomerId',
                'setCustomerCompany',
                'setCustomerCanvaId',
                'getCustomerCanvaId',
                'getUserProfileId',
                'setUserProfileId',
                'getOndemandCompanyInfo',
                'getCustomer',
                'getProfileSession'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn','getCompanyAuthenticationMethod'])
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->setMethods([
                'getIsSdeStore',
                'setCustomerActiveSessionCookie',
                'getIsRequestFromSdeStoreFclLogin'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->additionalDataFactory = $this->getMockBuilder(AdditionalDataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyManagement = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerInterfaceFactory = $this->getMockBuilder(CustomerInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeInterface = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getStoreId'])
            ->getMockForAbstractClass();

        $this->additionalData = $this->getMockBuilder(AdditionalData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'addFieldToSelect', 'addFieldToFilter', 'getFirstItem', 'getCompanyId'])
            ->getMock();

        $this->additionalDataCollection = $this->getMockBuilder(AdditionalDataCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToSelect', 'addFieldToFilter', 'getFirstItem', 'getCompanyId'])
            ->getMock();

        $this->companyItem = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->_objectManager = new ObjectManager($this);

        $this->_ssoHelperData = $this->_objectManager->getObject(
            Data::class,
            [
                'cookieManager' => $this->_cookieManager,
                '_cookieMetadata' => $this->_cookieMetadata,
                'storeManager' => $this->storeManager,
                'mathRandom' => $this->mathRandom,
                'customerCollection' => $this->customerCollection,
                'curl' => $this->curl,
                'customerInterface' => $this->customerInterface,
                'customerRepositoryInterface' => $this->customerRepositoryInterface,
                'regionFactory' => $this->regionFactory,
                'region' => $this->region,
                'addressDataFactory' => $this->addressDataFactory,
                'address' => $this->address,
                'customerFactory' => $this->customerFactory,
                'customerModel' => $this->customerModel,
                'addressInterface' => $this->addressInterface,
                'cookieMetadataFactory' => $this->cookieMetadataFactory,
                'punchoutHelper' => $this->punchoutHelper,
                'customerSession' => $this->customerSession,
                'toggleConfig' => $this->toggleConfig,
                'sdeHelper' => $this->sdeHelper,
                'additionalDataFactory' => $this->additionalDataFactory,
                'companyRepository' => $this->companyRepository,
                'companyManagement' => $this->companyManagement,
                'customerInterfaceFactory' => $this->customerInterfaceFactory,
                'resourceConnection' => $this->resourceConnection,
                'attributeRepositoryInterface' => $this->attributeRepositoryInterface,
                'ssoConfigMock' => $this->ssoConfigMock,
                'purpleGatewayTokenMock' => $this->purpleGatewayTokenMock,
                'scopeConfig' => $this->scopeConfigMock,
                'authHelper' => $this->baseAuthMock

            ]
        );
    }

    /**
     * Get storefront url
     */
    public function testGetBaseUrl()
    {
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeManager);

        $storeUrl = 'https://staging3.office.fedex.com/';
        $this->storeManager->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn($storeUrl);

        $this->assertEquals($storeUrl, $this->_ssoHelperData->getBaseUrl());
    }

    /**
     * Test getFCLProfile with customer id and expired cookie response
     */
    public function testGetFCLProfileWithCustomerIdExpiredResponse()
    {
        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setPath')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSameSite')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getFclFdxLogin')->willReturn(false);
        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturnSelf();

        $this->storeManager->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->customerSession->expects($this->any())->method('setFclFdxLogin')->willReturn(true);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->curl->expects($this->any())->method('getStatus')->willReturn(401);
        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';

        $this->assertEquals(false, $this->_ssoHelperData->getFCLProfile($endUrl, $fclCookies));
    }

    /**
     * Test getFCLProfile with customer id and expired cookie response
     */
    public function testGetFCLProfileWithCustomerIdExpiredResponseUnauthorize()
    {
        $responseData = '{
            "transactionId":"e6fe1e3e-8ec0-4719-9763-78a85937e7f1",
            "errors":[
                {"code":"REQUEST.UNAUTHORIZED","message":"Request unauthorized"}
            ]
        }';
        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($responseData);

        $this->_cookieMetadata->expects($this->any())
            ->method('setDomain')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setPath')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setHttpOnly')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setSecure')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setSameSite')
            ->willReturnSelf();

        $this->cookieMetadataFactory->expects($this->any())
            ->method('createPublicCookieMetadata')
            ->willReturn($this->_cookieMetadata);

        $this->_cookieManager->expects($this->any())
            ->method('deleteCookie')
            ->willReturnSelf();

        $this->customerSession->expects($this->any())
            ->method('getFclFdxLogin')
            ->willReturn(false);

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturnSelf();

        $this->storeManager->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7badd';

        $this->assertNotEquals($responseData, $this->_ssoHelperData->getFCLProfile($endUrl, $fclCookies));
    }

    /**
     * Test getFCLProfile with customer id
     */
    public function testGetFCLProfileWithCustomerId()
    {
        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setPath')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSameSite')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getFclFdxLogin')->willReturn(false);
        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->customerSession->expects($this->any())->method('setFclFdxLogin')->willReturn(true);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->curl->expects($this->any())->method('getStatus')->willReturn(200);
        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = '{
            "transactionId": "8f94c41e-c182-43a2-9ee6-449b02652406",
            "output": {
              "profile": {
                "userProfileId": "8f94c41e-c182-43a2-9ee6-449b02652406",
                "uuId": "gC68Zgn6xH",
                "contact": {
                  "personName": {
                    "firstName": "Nidhi",
                    "lastName": "Singh"
                  },
                  "company": {
                    "name": "FedEx"
                  },
                  "emailDetail": {
                    "emailAddress": "nidhi.singhtest@infogain.com"
                  },
                  "phoneNumberDetails": [
                    {
                      "phoneNumber": {
                        "number": "8770598600",
                        "extension": "91"
                      }
                    },
                    {
                      "phoneNumber": {}
                    },
                    {
                      "phoneNumber": {}
                    }
                  ],
                  "address": {
                    "streetLines": [
                      "Legacy D",
                      ""
                    ],
                    "city": "Plano",
                    "stateOrProvinceCode": "TX",
                    "postalCode": "75024",
                    "countryCode": "US"
                  }
                },
                "emailSubscription": false,
                "marketingEmails": false,
                "accounts": [
                  {
                    "profileAccountId": "2246613a-0b26-42ce-80d6-5fc6f8a0c6e9",
                    "accountNumber": "0653243286",
                    "maskedAccountNumber": "*3286",
                    "accountLabel": "FedEx Account 3286",
                    "accountType": "PRINTING",
                    "billingReference": "NULL",
                    "primary": false
                  }
                ],
                "creditCards": [
                  {
                    "profileCreditCardId": "6cac3d7b-0cee-48c1-9348-8155844562af",
                    "creditCardLabel": "VISA_3001",
                    "creditCardType": "VISA",
                    "maskedCreditCardNumber": "2601",
                    "cardHolderName": "JOHN DAN",
                    "expirationMonth": "06",
                    "tokenExpirationDate": "Sun Jun 26 00:00:00 GMT 2022",
                    "expirationYear": "2023",
                    "billingAddress": {
                      "company": {
                        "name": "FedEx"
                      },
                      "streetLines": [
                        "7900 Legacy Dr"
                      ],
                      "city": "Plano",
                      "stateOrProvinceCode": "TX",
                      "postalCode": "75024",
                      "countryCode": "US"
                    },
                    "primary": false
                  }
                ]
              }
            }
          }';
        // @codingStandardsIgnoreEnd
        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->customerCollection->expects($this->any())->method('addAttributeToSelect')->with('*')->willReturnSelf();
        $fclUuid = 'gC68Zgn6xH';
        $this->customerCollection->expects($this->any())->method('addAttributeToFilter')
            ->with('customer_uuid_value', $fclUuid)->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('load')->willReturnSelf();
        $customerArr = [["entity_id" => 1]];
        $this->customerCollection->expects($this->any())->method('getData')->willReturn($customerArr);

        $this->customerRepositoryInterface->expects($this->any())->method('get')->willReturn($this->customerInterface);

        $this->customerInterface->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerRepositoryInterface->expects($this->any())->method('setCustomAttribute')
            ->willReturn($this->customerRepositoryInterface);

        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);

        $this->addressDataFactory->expects($this->any())->method('create')->willReturn($this->address);
        $this->address->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->address->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->address->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->address->expects($this->any())->method('setEmailId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCountryId')->willReturnSelf();
        $this->address->expects($this->any())->method('setRegionId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCity')->willReturnSelf();
        $this->address->expects($this->any())->method('setPostcode')->willReturnSelf();
        $this->address->expects($this->any())->method('setExt')->willReturnSelf();
        $this->address->expects($this->any())->method('setTelephone')->willReturnSelf();
        $this->address->expects($this->any())->method('setCompany')->willReturnSelf();
        $this->address->expects($this->any())->method('setStreet')->willReturnSelf();

        $this->customerFactory->expects($this->any())->method('create')->willReturn($this->customerModel);
        $this->customerModel->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setEmail')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setData')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('loadByEmail')->willReturnSelf();

        $this->customerSession->expects($this->any())->method('isLoggedIn')->willReturnSelf(1);
        $this->customerSession->expects($this->any())->method('unsFclFdxLogin')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setProfileSession')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setCreditCardList')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setFedexAccountsList')->willReturnSelf();
        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $returnValue = true;
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';
        $this->assertNotNull($this->_ssoHelperData->getFCLProfile($endUrl, $fclCookies));
    }

    /**
     * Test getFCLProfile with email integrity
     */
    public function testGetFCLProfileWithEmailIntegrity()
    {
        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setPath')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSameSite')->willReturnSelf();

        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = '{"transactionId":"8f94c41e-c182-43a2-9ee6-449b02652406","output":{"profile":{"uuId":"gC68Zgn6xH","contact":{"personName":{"firstName":"Nidhi","lastName":"Singh"},"company":{},"emailDetail":{"emailAddress":"nidhi.singhtest@infogain.com"},"phoneNumberDetails":[{"phoneNumber":{"number":"877059860"}},{"phoneNumber":{}},{"phoneNumber":{}}],"address":{"streetLines":["Legacy D",""],"city":"Plano","stateOrProvinceCode":"TX","postalCode":"75024","countryCode":"US"}},"emailSubscription":false,"marketingEmails":false}}}';
        // @codingStandardsIgnoreEnd
        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->customerCollection->expects($this->any())->method('addAttributeToSelect')->with('*')->willReturnSelf();
        $fclUuid = 'gC68Zgn6xH';
        $this->customerCollection->expects($this->any())->method('addAttributeToFilter')
            ->with('customer_uuid_value', $fclUuid)->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('load')->willReturnSelf();
        $customerArr = [["entity_id" => 0]];
        $this->customerCollection->expects($this->any())->method('getData')->willReturn($customerArr);

        $this->customerRepositoryInterface->expects($this->any())->method('get')->willReturn($this->customerInterface);

        $this->customerInterface->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerRepositoryInterface->expects($this->any())->method('setCustomAttribute')
            ->willReturn($this->customerRepositoryInterface);

        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);

        $this->addressDataFactory->expects($this->any())->method('create')->willReturn($this->address);
        $this->address->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->address->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->address->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->address->expects($this->any())->method('setEmailId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCountryId')->willReturnSelf();
        $this->address->expects($this->any())->method('setRegionId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCity')->willReturnSelf();
        $this->address->expects($this->any())->method('setPostcode')->willReturnSelf();
        $this->address->expects($this->any())->method('setExt')->willReturnSelf();
        $this->address->expects($this->any())->method('setTelephone')->willReturnSelf();
        $this->address->expects($this->any())->method('setCompany')->willReturnSelf();
        $this->address->expects($this->any())->method('setStreet')->willReturnSelf();

        $this->customerFactory->expects($this->any())->method('create')->willReturn($this->customerModel);
        $this->customerModel->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setEmail')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setData')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('loadByEmail')->willReturnSelf();

        $this->customerSession->expects($this->any())->method('isLoggedIn')->willReturnSelf(0);
        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $returnValue = true;
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';
        $this->assertEquals(false, $this->_ssoHelperData->getFCLProfile($endUrl, $fclCookies));
    }

    /**
     * Test getFCLProfile without customer id
     */
    public function testGetFCLProfileWithoutCustomerId()
    {
        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setPath')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSameSite')->willReturnSelf();

        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = '{"transactionId":"8f94c41e-c182-43a2-9ee6-449b02652406","output":{"profile":{"uuId":"gC68Zgn6xH","contact":{"personName":{"firstName":"Nidhi","lastName":"Singh"},"company":{"name":"Infogain"},"emailDetail":{"emailAddress":"nidhi.singhtest@infogain.com"},"phoneNumberDetails":[{"phoneNumber":{"number":"8770598600","extension" : "91"}},{"phoneNumber":{}},{"phoneNumber":{}}],"address":{"streetLines":["Legacy D",""],"city":"Plano","stateOrProvinceCode":"TX","postalCode":"75024","countryCode":"US"}},"emailSubscription":false,"marketingEmails":false}}}';
        // @codingStandardsIgnoreEnd
        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);
        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->customerCollection->expects($this->any())->method('load')->willReturnSelf();
        $customerArr = [];
        $this->customerCollection->expects($this->any())->method('getData')->willReturn($customerArr);
        $this->customerRepositoryInterface->expects($this->any())->method('get')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('setEmail')->willReturnSelf();
        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);

        $this->customerFactory->expects($this->any())->method('create')->willReturn($this->customerModel);
        $this->customerModel->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setEmail')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('save')->willReturnSelf();
        $this->helperData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateUniqueCanvaId'])
            ->getMock();
        $this->helperData->expects($this->any())->method('generateUniqueCanvaId')->willReturn("yxJ7fGF23I");
        $this->customerCollection->expects($this->any())->method('addAttributeToSelect')->with('*')->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('load')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('getDefaultBilling')->willReturnSelf('1');
        $this->customerInterface->expects($this->any())->method('getDefaultShipping')->willReturnSelf('1');
        $this->addressDataFactory->expects($this->any())->method('create')->willReturn($this->address);
        $this->address->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->address->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->address->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->address->expects($this->any())->method('setEmailId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCountryId')->willReturnSelf();
        $this->address->expects($this->any())->method('setRegionId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCity')->willReturnSelf();
        $this->address->expects($this->any())->method('setPostcode')->willReturnSelf();
        $this->address->expects($this->any())->method('setExt')->willReturnSelf();
        $this->address->expects($this->any())->method('setTelephone')->willReturnSelf();
        $this->address->expects($this->any())->method('setCompany')->willReturnSelf();
        $this->address->expects($this->any())->method('setStreet')->willReturnSelf();

        $this->customerRepositoryInterface->expects($this->any())->method('setCustomAttribute')
            ->willReturn($this->customerRepositoryInterface);
        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';
        $returnValue = false;
        $this->assertEquals($returnValue, $this->_ssoHelperData->getFCLProfile($endUrl, $fclCookies));
    }

    /**
     * Test getFCLProfile with exception if part
     */
    public function testGetFCLProfileWithExceptionIfPart()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setPath')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSameSite')->willReturnSelf();

        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = '{"transactionId":"8f94c41e-c182-43a2-9ee6-449b02652406","output":{"profile":{"uuId":"gC68Zgn6xH","contact":{"personName":{"firstName":"Nidhi","lastName":"Singh"},"company":{},"emailDetail":{"emailAddress":"nidhi.singhtest@infogain.com"},"phoneNumberDetails":[{"phoneNumber":{"number":"8770598600"}},{"phoneNumber":{}},{"phoneNumber":{}}],"address":{"streetLines":["Legacy D",""],"city":"Plano","stateOrProvinceCode":"TX","postalCode":"75024","countryCode":"US"}},"emailSubscription":false,"marketingEmails":false}}}';
        // @codingStandardsIgnoreEnd

        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willThrowException($exception);

        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);

        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';
        $returnValue = false;
        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willThrowException($exception);
        $this->assertEquals($returnValue, $this->_ssoHelperData->getFCLProfile($endUrl, $fclCookies));
    }

    /**
     * Test getFCLProfile with exception else part
     */
    public function testGetFCLProfileWithExceptionElsePart()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setPath')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSameSite')->willReturnSelf();

        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = '{"transactionId":"8f94c41e-c182-43a2-9ee6-449b02652406","output":{"profile":{"uuId":"gC68Zgn6xH","contact":{"personName":{"firstName":"Nidhi","lastName":"Singh"},"company":{},"emailDetail":{"emailAddress":"nidhi.singhtest@infogain.com"},"phoneNumberDetails":[{"phoneNumber":{"number":"8770598600"}},{"phoneNumber":{}},{"phoneNumber":{}}],"address":{"streetLines":["Legacy D",""],"city":"Plano","stateOrProvinceCode":"TX","postalCode":"75024","countryCode":"US"}},"emailSubscription":false,"marketingEmails":false}}}';
        // @codingStandardsIgnoreEnd

        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willThrowException($exception);

        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);

        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';
        $returnValue = false;
        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willThrowException($exception);
        $this->assertEquals($returnValue, $this->_ssoHelperData->getFCLProfile($endUrl, $fclCookies));
    }

    /**
     * Test Address with id
     */
    public function testGetFCLProfileWithAddressId()
    {
        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setPath')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSameSite')->willReturnSelf();

        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = '{"transactionId":"8f94c41e-c182-43a2-9ee6-449b02652406","output":{"profile":{"uuId":"gC68Zgn6xH","contact":{"personName":{"firstName":"Nidhi","lastName":"Singh"},"company":{},"emailDetail":{"emailAddress":"nidhi.singhtest@infogain.com"},"phoneNumberDetails":[{"phoneNumber":{"number":"8770598600"}},{"phoneNumber":{}},{"phoneNumber":{}}],"address":{"streetLines":["Legacy D",""],"city":"Plano","stateOrProvinceCode":"TX","postalCode":"75024","countryCode":"US"}},"emailSubscription":false,"marketingEmails":false}}}';
        // @codingStandardsIgnoreEnd

        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->customerCollection->expects($this->any())->method('addAttributeToSelect')->with('*')->willReturnSelf();
        $fclUuid = 'gC68Zgn6xH';
        $this->customerCollection->expects($this->any())->method('addAttributeToFilter')
            ->with('customer_uuid_value', $fclUuid)->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('load')->willReturnSelf();
        $customerArr = [["entity_id" => 1]];
        $this->customerCollection->expects($this->any())->method('getData')->willReturn($customerArr);

        $this->customerRepositoryInterface->expects($this->any())->method('get')->willReturn($this->customerInterface);

        $this->customerInterface->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerRepositoryInterface->expects($this->any())->method('setCustomAttribute')
            ->willReturn($this->customerRepositoryInterface);

        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);

        $this->addressDataFactory->expects($this->any())->method('create')->willReturn($this->address);
        $this->address->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->address->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->address->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->address->expects($this->any())->method('setEmailId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCountryId')->willReturnSelf();
        $this->address->expects($this->any())->method('setRegionId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCity')->willReturnSelf();
        $this->address->expects($this->any())->method('setPostcode')->willReturnSelf();
        $this->address->expects($this->any())->method('setExt')->willReturnSelf();
        $this->address->expects($this->any())->method('setTelephone')->willReturnSelf();
        $this->address->expects($this->any())->method('setCompany')->willReturnSelf();
        $this->address->expects($this->any())->method('setStreet')->willReturnSelf();

        $this->customerInterface->expects($this->any())->method('getDefaultShipping')->willReturnSelf('1');
        $this->customerFactory->expects($this->any())->method('create')->willReturn($this->customerModel);
        $this->customerModel->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setEmail')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setData')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('loadByEmail')->willReturnSelf();

        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $returnValue = false;
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';
        $this->assertEquals($returnValue, $this->_ssoHelperData->getFCLProfile($endUrl, $fclCookies));
    }

    /**
     * Test address exception
     */
    public function testGetFCLProfileWithAddressException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setPath')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSameSite')->willReturnSelf();

        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = '{"transactionId":"8f94c41e-c182-43a2-9ee6-449b02652406","output":{"profile":{"uuId":"gC68Zgn6xH","contact":{"personName":{"firstName":"Nidhi","lastName":"Singh"},"company":{},"emailDetail":{"emailAddress":"nidhi.singhtest@infogain.com"},"phoneNumberDetails":[{"phoneNumber":{"number":"8770598600"}},{"phoneNumber":{}},{"phoneNumber":{}}],"address":{"streetLines":["Legacy D",""],"city":"Plano","stateOrProvinceCode":"TX","postalCode":"75024","countryCode":"US"}},"emailSubscription":false,"marketingEmails":false}}}';
        // @codingStandardsIgnoreEnd

        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->customerCollection->expects($this->any())->method('addAttributeToSelect')->with('*')->willReturnSelf();
        $fclUuid = 'gC68Zgn6xH';
        $this->customerCollection->expects($this->any())->method('addAttributeToFilter')
            ->with('customer_uuid_value', $fclUuid)->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('load')->willReturnSelf();
        $customerArr = [["entity_id" => 1]];
        $this->customerCollection->expects($this->any())->method('getData')->willReturn($customerArr);

        $this->customerRepositoryInterface->expects($this->any())->method('get')->willReturn($this->customerInterface);

        $this->customerInterface->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerRepositoryInterface->expects($this->any())->method('setCustomAttribute')
            ->willReturn($this->customerRepositoryInterface);

        $this->addressDataFactory->expects($this->any())->method('create')->willReturn($this->address);
        $this->address->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->address->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->address->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->address->expects($this->any())->method('setEmailId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCountryId')->willReturnSelf();
        $this->address->expects($this->any())->method('setRegionId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCity')->willReturnSelf();
        $this->address->expects($this->any())->method('setPostcode')->willReturnSelf();
        $this->address->expects($this->any())->method('setExt')->willReturnSelf();
        $this->address->expects($this->any())->method('setTelephone')->willReturnSelf();
        $this->address->expects($this->any())->method('setCompany')->willReturnSelf();
        $this->address->expects($this->any())->method('setStreet')->willReturnSelf();

        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);

        $this->customerInterface->expects($this->any())->method('getDefaultShipping')->willThrowException($exception);

        $this->customerFactory->expects($this->any())->method('create')->willReturn($this->customerModel);
        $this->customerModel->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setEmail')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setData')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('loadByEmail')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getCustomerId')->willReturn(1);
        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $returnValue = false;
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';
        $this->assertEquals($returnValue, $this->_ssoHelperData->getFCLProfile($endUrl, $fclCookies));
    }

    /**
     * Test profile api exception
     */
    public function testGetFCLProfileWithProfileApiException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willThrowException($exception);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';
        $returnValue = false;
        $this->customerCollection->expects($this->any())->method('load')->willThrowException($exception);
        $this->assertEquals($returnValue, $this->_ssoHelperData->getFCLProfile($endUrl, $fclCookies));
    }

    /**
     * @test testGetCustomerProfileWithRefactorToggleEnabled
     */
    public function testGetCustomerProfileWithRefactorToggleEnabled()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);

        $this->sdeHelper->expects($this->any())
            ->method('getIsRequestFromSdeStoreFclLogin')
            ->willReturn(false);

        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setPath')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSameSite')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getFclFdxLogin')->willReturn(false);

        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->customerSession->expects($this->any())->method('setFclFdxLogin')->willReturn(true);

        $this->curl->expects($this->any())->method('getStatus')->willReturn(402);
        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = '{
            "transactionId": "8f94c41e-c182-43a2-9ee6-449b02652406",
            "output": {
              "profile": {
                "uuId": "gC68Zgn6xH",
                "contact": {
                  "personName": {
                    "firstName": "Nidhi",
                    "lastName": "Singh"
                  },
                  "company": {
                    "name": "FedEx"
                  },
                  "emailDetail": {
                    "emailAddress": "nidhi.singhtest@infogain.com"
                  },
                  "phoneNumberDetails": [
                    {
                      "phoneNumber": {
                        "number": "",
                        "extension": "91"
                      }
                    },
                    {
                      "phoneNumber": {}
                    },
                    {
                      "phoneNumber": {}
                    }
                  ],
                  "address": {
                    "streetLines": [
                      "Legacy D",
                      ""
                    ],
                    "city": "Plano",
                    "stateOrProvinceCode": "TX",
                    "postalCode": "75024",
                    "countryCode": "US"
                  }
                },
                "emailSubscription": false,
                "marketingEmails": false,
                "accounts": [
                  {
                    "profileAccountId": "2246613a-0b26-42ce-80d6-5fc6f8a0c6e9",
                    "accountNumber": "0653243286",
                    "maskedAccountNumber": "*3286",
                    "accountLabel": "FedEx Account 3286",
                    "accountType": "PRINTING",
                    "billingReference": "NULL",
                    "primary": false
                  }
                ],
                "creditCards": [
                  {
                    "profileCreditCardId": "6cac3d7b-0cee-48c1-9348-8155844562af",
                    "creditCardLabel": "VISA_3001",
                    "creditCardType": "VISA",
                    "maskedCreditCardNumber": "2601",
                    "cardHolderName": "JOHN DAN",
                    "expirationMonth": "06",
                    "tokenExpirationDate": "Sun Jun 26 00:00:00 GMT 2022",
                    "expirationYear": "2023",
                    "billingAddress": {
                      "company": {
                        "name": "FedEx"
                      },
                      "streetLines": [
                        "7900 Legacy Dr"
                      ],
                      "city": "Plano",
                      "stateOrProvinceCode": "TX",
                      "postalCode": "75024",
                      "countryCode": "US"
                    },
                    "primary": false
                  }
                ]
              }
            }
          }';
        // @codingStandardsIgnoreEnd
        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $ondemandCompanyInfo = ['company_data'=>['storefront_login_method_option'=>'commercial_store_sso']];

        $this->customerSession->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandCompanyInfo);

        $this->customerCollection->expects($this->any())->method('addAttributeToSelect')->with('*')->willReturnSelf();
        $this->helperData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateUniqueCanvaId'])
            ->getMock();
        $this->helperData->expects($this->any())->method('generateUniqueCanvaId')->willReturn("yxJ7fGF23I");
        $fclUuid = 'gC68Zgn6xH';
        $this->customerCollection->expects($this->any())->method('addAttributeToFilter')
            ->with('customer_uuid_value', $fclUuid)->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('load')->willReturnSelf();
        $customerArr = [["entity_id" => 1]];
        $this->customerCollection->expects($this->any())->method('getData')->willReturn($customerArr);

        $this->customerRepositoryInterface->expects($this->any())->method('get')->willReturn($this->customerInterface);

        $this->customerInterface->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerRepositoryInterface->expects($this->any())->method('setCustomAttribute')
            ->willReturn($this->customerRepositoryInterface);

        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);

        $this->addressDataFactory->expects($this->any())->method('create')->willReturn($this->address);
        $this->address->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->address->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->address->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->address->expects($this->any())->method('setEmailId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCountryId')->willReturnSelf();
        $this->address->expects($this->any())->method('setRegionId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCity')->willReturnSelf();
        $this->address->expects($this->any())->method('setPostcode')->willReturnSelf();
        $this->address->expects($this->any())->method('setExt')->willReturnSelf();
        $this->address->expects($this->any())->method('setTelephone')->willReturnSelf();
        $this->address->expects($this->any())->method('setCompany')->willReturnSelf();
        $this->address->expects($this->any())->method('setStreet')->willReturnSelf();

        $this->customerFactory->expects($this->any())->method('create')->willReturn($this->customerModel);
        $this->customerModel->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setEmail')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setData')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('loadByEmail')->willReturnSelf();

        $this->customerSession->expects($this->any())->method('isLoggedIn')->willReturnSelf(1);
        $this->customerSession->expects($this->any())->method('unsFclFdxLogin')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setProfileSession')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setCreditCardList')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setFedexAccountsList')->willReturnSelf();
        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $returnValue = true;
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';
        $this->assertNotNull($this->_ssoHelperData->getCustomerProfile($endUrl, $fclCookies));
    }
    /**
     * @test testGetCustomerProfileWithRefactorToggleEnabledWithoutCustomerId
     */
    public function testGetCustomerProfileWithRefactorToggleEnabledWithoutCustomerId()
    {

           $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);

        $this->sdeHelper->expects($this->any())
            ->method('getIsRequestFromSdeStoreFclLogin')
            ->willReturn(false);

        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setPath')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSameSite')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getFclFdxLogin')->willReturn(false);

        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->customerSession->expects($this->any())->method('setFclFdxLogin')->willReturn(true);

        $this->curl->expects($this->any())->method('getStatus')->willReturn(402);
        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = '{
            "transactionId": "8f94c41e-c182-43a2-9ee6-449b02652406",
            "output": {
              "profile": {
                "uuId": "gC68Zgn6xH",
                "contact": {
                  "personName": {
                    "firstName": "Nidhi",
                    "lastName": "Singh"
                  },
                  "company": {
                    "name": "FedEx"
                  },
                  "emailDetail": {
                    "emailAddress": "nidhi.singhtest@infogain.com"
                  },
                  "phoneNumberDetails": [
                    {
                      "phoneNumber": {
                        "number": "8770598600",
                        "extension": "91"
                      }
                    },
                    {
                      "phoneNumber": {}
                    },
                    {
                      "phoneNumber": {}
                    }
                  ],
                  "address": {
                    "streetLines": [
                      "Legacy D",
                      ""
                    ],
                    "city": "Plano",
                    "stateOrProvinceCode": "TX",
                    "postalCode": "75024",
                    "countryCode": "US"
                  }
                },
                "emailSubscription": false,
                "marketingEmails": false,
                "accounts": [
                  {
                    "profileAccountId": "2246613a-0b26-42ce-80d6-5fc6f8a0c6e9",
                    "accountNumber": "0653243286",
                    "maskedAccountNumber": "*3286",
                    "accountLabel": "FedEx Account 3286",
                    "accountType": "PRINTING",
                    "billingReference": "NULL",
                    "primary": false
                  }
                ],
                "creditCards": [
                  {
                    "profileCreditCardId": "6cac3d7b-0cee-48c1-9348-8155844562af",
                    "creditCardLabel": "VISA_3001",
                    "creditCardType": "VISA",
                    "maskedCreditCardNumber": "2601",
                    "cardHolderName": "JOHN DAN",
                    "expirationMonth": "06",
                    "tokenExpirationDate": "Sun Jun 26 00:00:00 GMT 2022",
                    "expirationYear": "2023",
                    "billingAddress": {
                      "company": {
                        "name": "FedEx"
                      },
                      "streetLines": [
                        "7900 Legacy Dr"
                      ],
                      "city": "Plano",
                      "stateOrProvinceCode": "TX",
                      "postalCode": "75024",
                      "countryCode": "US"
                    },
                    "primary": false
                  }
                ]
              }
            }
          }';
        // @codingStandardsIgnoreEnd
        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $ondemandCompanyInfo = ['company_data'=>['storefront_login_method_option'=>'commercial_store_sso']];

        $this->customerSession->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandCompanyInfo);

        $this->customerCollection->expects($this->any())->method('addAttributeToSelect')->with('*')->willReturnSelf();
        $this->helperData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateUniqueCanvaId'])
            ->getMock();
        $this->helperData->expects($this->any())->method('generateUniqueCanvaId')->willReturn("yxJ7fGF23I");
        $fclUuid = 'gC68Zgn6xH';
        $this->customerCollection->expects($this->any())->method('addAttributeToFilter')
            ->with('customer_uuid_value', $fclUuid)->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('load')->willReturnSelf();
        $customerArr = [["entity_id" => 0]];
        $this->customerCollection->expects($this->any())->method('getData')->willReturn($customerArr);

        $this->customerRepositoryInterface->expects($this->any())->method('get')->willReturn($this->customerInterface);

        $this->customerInterface->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerRepositoryInterface->expects($this->any())->method('setCustomAttribute')
            ->willReturn($this->customerRepositoryInterface);

        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);

        $this->addressDataFactory->expects($this->any())->method('create')->willReturn($this->address);
        $this->address->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->address->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->address->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->address->expects($this->any())->method('setEmailId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCountryId')->willReturnSelf();
        $this->address->expects($this->any())->method('setRegionId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCity')->willReturnSelf();
        $this->address->expects($this->any())->method('setPostcode')->willReturnSelf();
        $this->address->expects($this->any())->method('setExt')->willReturnSelf();
        $this->address->expects($this->any())->method('setTelephone')->willReturnSelf();
        $this->address->expects($this->any())->method('setCompany')->willReturnSelf();
        $this->address->expects($this->any())->method('setStreet')->willReturnSelf();

        $this->customerFactory->expects($this->any())->method('create')->willReturn($this->customerModel);
        $this->customerModel->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setEmail')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setData')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('loadByEmail')->willReturnSelf();

        $this->customerSession->expects($this->any())->method('isLoggedIn')->willReturnSelf(1);
        $this->customerSession->expects($this->any())->method('unsFclFdxLogin')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setProfileSession')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setCreditCardList')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setFedexAccountsList')->willReturnSelf();
        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $returnValue = true;
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';
        $this->assertNotNull($this->_ssoHelperData->getCustomerProfile($endUrl, $fclCookies));
    }

    /**
     * @test testGetCustomerProfileWithRefactorToggleDisabled
     */
    public function testGetCustomerProfileWithRefactorToggleDisabled()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setPath')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSameSite')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getFclFdxLogin')->willReturn(false);
        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->customerSession->expects($this->any())->method('setFclFdxLogin')->willReturn(true);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->curl->expects($this->any())->method('getStatus')->willReturn(402);
        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = '{
                "transactionId": "8f94c41e-c182-43a2-9ee6-449b02652406",
                "output": {
                    "profile": {
                    "userProfileId": "8f94c41e-c182-43a2-9ee6-449b02652406",
                    "uuId": "gC68Zgn6xH",
                    "contact": {
                        "personName": {
                        "firstName": "Nidhi",
                        "lastName": "Singh"
                        },
                        "company": {
                        "name": "FedEx"
                        },
                        "emailDetail": {
                        "emailAddress": "nidhi.singhtest@infogain.com"
                        },
                        "phoneNumberDetails": [
                        {
                            "phoneNumber": {
                            "number": "877059860",
                            "extension": "91"
                            }
                        },
                        {
                            "phoneNumber": {}
                        },
                        {
                            "phoneNumber": {}
                        }
                        ],
                        "address": {
                        "streetLines": [
                            "Legacy D",
                            ""
                        ],
                        "city": "Plano",
                        "stateOrProvinceCode": "TX",
                        "postalCode": "75024",
                        "countryCode": "US"
                        }
                    },
                    "emailSubscription": false,
                    "marketingEmails": false,
                    "accounts": [
                        {
                        "profileAccountId": "2246613a-0b26-42ce-80d6-5fc6f8a0c6e9",
                        "accountNumber": "0653243286",
                        "maskedAccountNumber": "*3286",
                        "accountLabel": "FedEx Account 3286",
                        "accountType": "PRINTING",
                        "billingReference": "NULL",
                        "primary": false
                        }
                    ],
                    "creditCards": [
                        {
                        "profileCreditCardId": "6cac3d7b-0cee-48c1-9348-8155844562af",
                        "creditCardLabel": "VISA_3001",
                        "creditCardType": "VISA",
                        "maskedCreditCardNumber": "2601",
                        "cardHolderName": "JOHN DAN",
                        "expirationMonth": "06",
                        "tokenExpirationDate": "Sun Jun 26 00:00:00 GMT 2022",
                        "expirationYear": "2023",
                        "billingAddress": {
                            "company": {
                            "name": "FedEx"
                            },
                            "streetLines": [
                            "7900 Legacy Dr"
                            ],
                            "city": "Plano",
                            "stateOrProvinceCode": "TX",
                            "postalCode": "75024",
                            "countryCode": "US"
                        },
                        "primary": false
                        }
                    ]
                    }
                }
                }';
        // @codingStandardsIgnoreEnd
        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('xmen_enable_fcl_cookie_name')
            ->willReturn(false);

        $ondemandCompanyInfo = ['company_data'=>['storefront_login_method_option'=>'commercial_store_sso']];

        $this->customerSession->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandCompanyInfo);

        $this->customerCollection->expects($this->any())->method('addAttributeToSelect')->with('*')->willReturnSelf();
        $fclUuid = 'gC68Zgn6xH';
        $this->customerCollection->expects($this->any())->method('addAttributeToFilter')
            ->with('customer_uuid_value', $fclUuid)->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('load')->willReturnSelf();
        $customerArr = [["entity_id" => 1]];
        $this->customerCollection->expects($this->any())->method('getData')->willReturn($customerArr);

        $this->customerRepositoryInterface->expects($this->any())->method('get')->willReturn($this->customerInterface);

        $this->customerInterface->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerRepositoryInterface->expects($this->any())->method('setCustomAttribute')
            ->willReturn($this->customerRepositoryInterface);

        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);

        $this->addressDataFactory->expects($this->any())->method('create')->willReturn($this->address);
        $this->address->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->address->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->address->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->address->expects($this->any())->method('setEmailId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCountryId')->willReturnSelf();
        $this->address->expects($this->any())->method('setRegionId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCity')->willReturnSelf();
        $this->address->expects($this->any())->method('setPostcode')->willReturnSelf();
        $this->address->expects($this->any())->method('setExt')->willReturnSelf();
        $this->address->expects($this->any())->method('setTelephone')->willReturnSelf();
        $this->address->expects($this->any())->method('setCompany')->willReturnSelf();
        $this->address->expects($this->any())->method('setStreet')->willReturnSelf();

        $this->customerFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerFactory->expects($this->any())->method('load')->willReturn($this->customerModel);
        $this->customerModel->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setEmail')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setData')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('loadByEmail')->willReturnSelf();

        $this->customerSession->expects($this->any())->method('isLoggedIn')->willReturnSelf(1);
        $this->customerSession->expects($this->any())->method('unsFclFdxLogin')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setProfileSession')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setCreditCardList')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setFedexAccountsList')->willReturnSelf();
        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $returnValue = true;
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';
        $this->assertNotNull($this->_ssoHelperData->getCustomerProfile($endUrl, $fclCookies));
    }

    /**
     * @test testGetCustomerProfileWithProfileApiSdeStore
     */
    public function testGetCustomerProfileWithProfileApiSdeStore()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);

        $this->sdeHelper->expects($this->any())
            ->method('getIsRequestFromSdeStoreFclLogin')
            ->willReturn(false);

        $this->customerSession->expects($this->any())->method('getFclFdxLogin')->willReturn(false);

        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getAuthGatewayToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->customerSession->expects($this->any())->method('setFclFdxLogin')->willReturn(true);

        $this->curl->expects($this->any())->method('getStatus')->willReturn(402);
        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = '{
            "transactionId": "8f94c41e-c182-43a2-9ee6-449b02652406",
            "output": {
                "profile": {
                "uuId": "gC68Zgn6xH",
                "contact": {
                    "personName": {
                    "firstName": "Nidhi",
                    "lastName": "Singh"
                    },
                    "company": {
                    "name": "FedEx"
                    },
                    "emailDetail": {
                    "emailAddress": "nidhi.singhtest@infogain.com"
                    },
                    "phoneNumberDetails": [
                    {
                        "phoneNumber": {
                        "number": "8770598600",
                        "extension": "91"
                        }
                    },
                    {
                        "phoneNumber": {}
                    },
                    {
                        "phoneNumber": {}
                    }
                    ],
                    "address": {
                    "streetLines": [
                        "Legacy D",
                        ""
                    ],
                    "city": "Plano",
                    "stateOrProvinceCode": "TX",
                    "postalCode": "75024",
                    "countryCode": "US"
                    }
                },
                "emailSubscription": false,
                "marketingEmails": false
                }
            }
            }';
        // @codingStandardsIgnoreEnd
        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $ondemandCompanyInfo = ['company_data'=>['storefront_login_method_option'=>'commercial_store_sso']];

        $this->customerSession->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandCompanyInfo);


        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);

        $this->customerInterfaceFactory->expects($this->any())->method('create')->willReturn($this->customerInterface);
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->expects($this->any())->method('getStoreId')->willReturn(65);
        $this->additionalDataFactory->expects($this->any())->method('create')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCollection')->willReturn($this->additionalDataCollection);
        $this->additionalDataCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('getFirstItem')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCompanyId')->willReturn(30);
        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyItem);

        $this->companyItem->expects($this->any())
            ->method('getCustomerGroupId')
            ->willReturn(4);

        $this->customerRepositoryInterface->expects($this->any())->method('save')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn(10);
        $this->companyManagement->expects($this->any())->method('assignCustomer')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setCustomerCompany')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getCustomerCanvaId')->willReturnSelf('cbedb0b7-6fe9-5c95-a486-248b487511ef');
        $this->customerSession->expects($this->any())->method('getUserProfileId')->willReturn('badca9a6-5ed8-4b84-b597-139a376499de');
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->_cookieMetadata->expects($this->any())
            ->method('setDomain')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setPath')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setHttpOnly')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setSecure')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setSameSite')
            ->willReturnSelf();

        $this->cookieMetadataFactory->expects($this->any())
            ->method('createPublicCookieMetadata')
            ->willReturn($this->_cookieMetadata);
        $this->_cookieManager->expects($this->any())
            ->method('deleteCookie')
            ->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('loadByEmail')->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('addAttributeToSelect')->with('*')->willReturnSelf();
        $fclUuid = 'gC68Zgn6xH';
        $this->customerCollection->expects($this->any())->method('addAttributeToFilter')
            ->with('customer_uuid_value', $fclUuid)->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('load')->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('getData')->willReturn('1634-190f-a4b9-45');
        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';

        $this->assertEquals('', $this->_ssoHelperData->getCustomerProfile($endUrl, $fclCookies));
    }

    public function testGetCustomerProfileWithProfileApiSdeStoreAuthToggleOn()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);

        $this->sdeHelper->expects($this->any())
            ->method('getIsRequestFromSdeStoreFclLogin')
            ->willReturn(false);

        $this->customerSession->expects($this->any())->method('getFclFdxLogin')->willReturn(false);

        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getAuthGatewayToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->customerSession->expects($this->any())->method('setFclFdxLogin')->willReturn(true);

        $this->curl->expects($this->any())->method('getStatus')->willReturn(402);
        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = '{
            "transactionId": "8f94c41e-c182-43a2-9ee6-449b02652406",
            "output": {
                "profile": {
                "uuId": "gC68Zgn6xH",
                "contact": {
                    "personName": {
                    "firstName": "Nidhi",
                    "lastName": "Singh"
                    },
                    "company": {
                    "name": "FedEx"
                    },
                    "emailDetail": {
                    "emailAddress": "nidhi.singhtest@infogain.com"
                    },
                    "phoneNumberDetails": [
                    {
                        "phoneNumber": {
                        "number": "8770598600",
                        "extension": "91"
                        }
                    },
                    {
                        "phoneNumber": {}
                    },
                    {
                        "phoneNumber": {}
                    }
                    ],
                    "address": {
                    "streetLines": [
                        "Legacy D",
                        ""
                    ],
                    "city": "Plano",
                    "stateOrProvinceCode": "TX",
                    "postalCode": "75024",
                    "countryCode": "US"
                    }
                },
                "emailSubscription": false,
                "marketingEmails": false
                }
            }
            }';
        // @codingStandardsIgnoreEnd
        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $ondemandCompanyInfo = ['company_data'=>['storefront_login_method_option'=>'commercial_store_sso']];

        $this->customerSession->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandCompanyInfo);


        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);

        $this->customerInterfaceFactory->expects($this->any())->method('create')->willReturn($this->customerInterface);
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->expects($this->any())->method('getStoreId')->willReturn(65);
        $this->additionalDataFactory->expects($this->any())->method('create')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCollection')->willReturn($this->additionalDataCollection);
        $this->additionalDataCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('getFirstItem')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCompanyId')->willReturn(30);
        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyItem);

        $this->companyItem->expects($this->any())
            ->method('getCustomerGroupId')
            ->willReturn(4);

        $this->customerRepositoryInterface->expects($this->any())->method('save')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn(10);
        $this->companyManagement->expects($this->any())->method('assignCustomer')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setCustomerCompany')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getCustomerCanvaId')->willReturnSelf('cbedb0b7-6fe9-5c95-a486-248b487511ef');
        $this->customerSession->expects($this->any())->method('getUserProfileId')->willReturn('badca9a6-5ed8-4b84-b597-139a376499de');
        $this->baseAuthMock->method('isLoggedIn')->willReturn(true);
        $this->_cookieMetadata->expects($this->any())
            ->method('setDomain')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setPath')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setHttpOnly')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setSecure')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setSameSite')
            ->willReturnSelf();

        $this->cookieMetadataFactory->expects($this->any())
            ->method('createPublicCookieMetadata')
            ->willReturn($this->_cookieMetadata);
        $this->_cookieManager->expects($this->any())
            ->method('deleteCookie')
            ->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('loadByEmail')->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('addAttributeToSelect')->with('*')->willReturnSelf();
        $fclUuid = 'gC68Zgn6xH';
        $this->customerCollection->expects($this->any())->method('addAttributeToFilter')
            ->with('customer_uuid_value', $fclUuid)->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('load')->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('getData')->willReturn('1634-190f-a4b9-45');
        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';

        $this->assertEquals('', $this->_ssoHelperData->getCustomerProfile($endUrl, $fclCookies));
    }

    /**
     * @test testGetCustomerProfileWithProfileApiSdeStoreReturnFalse
     */
    public function testGetCustomerProfileWithProfileApiSdeStoreReturnFalse()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);

        $this->sdeHelper->expects($this->any())
            ->method('getIsRequestFromSdeStoreFclLogin')
            ->willReturn(false);

        $this->customerSession->expects($this->any())->method('getFclFdxLogin')->willReturn(false);

        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->customerSession->expects($this->any())->method('setFclFdxLogin')->willReturn(true);
        $this->curl->expects($this->any())->method('getStatus')->willReturn(402);
        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = '{
            "transactionId": "8f94c41e-c182-43a2-9ee6-449b02652406",
            "output": {
                "profile": {
                "uuId": "gC68Zgn6xH",
                "contact": {
                    "personName": {
                    "firstName": "Nidhi",
                    "lastName": "Singh"
                    },
                    "company": {
                    "name": "FedEx"
                    },
                    "emailDetail": {
                    "emailAddress": "nidhi.singhtest@infogain.com"
                    },
                    "phoneNumberDetails": [
                    {
                        "phoneNumber": {
                        "number": "8770598600",
                        "extension": "91"
                        }
                    },
                    {
                        "phoneNumber": {}
                    },
                    {
                        "phoneNumber": {}
                    }
                    ],
                    "address": {
                    "streetLines": [
                        "Legacy D",
                        ""
                    ],
                    "city": "Plano",
                    "stateOrProvinceCode": "TX",
                    "postalCode": "75024",
                    "countryCode": "US"
                    }
                },
                "emailSubscription": false,
                "marketingEmails": false
                }
            }
            }';
        // @codingStandardsIgnoreEnd
        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);


        $ondemandCompanyInfo = ['company_data'=>['storefront_login_method_option'=>'commercial_store_wlgn']];

        $this->customerSession->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandCompanyInfo);

        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);

        $this->customerInterfaceFactory->expects($this->any())->method('create')->willReturn($this->customerInterface);
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->expects($this->any())->method('getStoreId')->willReturn(65);
        $this->additionalDataFactory->expects($this->any())->method('create')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCollection')->willReturn($this->additionalDataCollection);
        $this->additionalDataCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('getFirstItem')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCompanyId')->willReturn(30);
        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyItem);

        $this->companyItem->expects($this->any())
            ->method('getCustomerGroupId')
            ->willReturn(4);

        $this->customerRepositoryInterface->expects($this->any())->method('save')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn(10);
        $this->companyManagement->expects($this->any())->method('assignCustomer')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setCustomerCompany')->willReturnSelf();
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(false);
        $this->_cookieMetadata->expects($this->any())
            ->method('setDomain')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setPath')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setHttpOnly')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setSecure')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setSameSite')
            ->willReturnSelf();

        $this->cookieMetadataFactory->expects($this->any())
            ->method('createPublicCookieMetadata')
            ->willReturn($this->_cookieMetadata);
        $this->_cookieManager->expects($this->any())
            ->method('deleteCookie')
            ->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('loadByEmail')->willReturnSelf();
        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';

        $this->assertEquals(false, $this->_ssoHelperData->getCustomerProfile($endUrl, $fclCookies));
    }

    /**
     * @test testGetCustomerProfileWithProfileApiIssue
     */
    public function testGetCustomerProfileWithProfileApiIssue()
    {
       $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);

        $this->sdeHelper->expects($this->any())
            ->method('getIsRequestFromSdeStoreFclLogin')
            ->willReturn(false);

        $ondemandCompanyInfo = ['company_data'=>['storefront_login_method_option'=>'commercial_store_sso']];

        $this->customerSession->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandCompanyInfo);

        $this->customerSession->expects($this->any())->method('getFclFdxLogin')->willReturn(false);

        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->customerSession->expects($this->any())->method('setFclFdxLogin')->willReturn(true);
        $this->curl->expects($this->any())->method('getStatus')->willReturn(402);
        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = '{
            "transactionId": "8f94c41e-c182-43a2-9ee6-449b02652406",
            "output": {
                "profile": {
                "userProfileId": "8f94c41e-c182-43a2-9ee6-449b02652406",
                "uuId": "gC68Zgn6xH",
                "contact": {
                    "personName": {
                    "firstName": "Nidhi",
                    "lastName": "Singh"
                    },
                    "company": {
                    "name": "FedEx"
                    },
                    "emailDetail": {
                    "emailAddress": "nidhi.singhtest@infogain.com"
                    },
                    "phoneNumberDetails": [
                    {
                        "phoneNumber": {
                        "number": "8770598600",
                        "extension": "91"
                        }
                    },
                    {
                        "phoneNumber": {}
                    },
                    {
                        "phoneNumber": {}
                    }
                    ],
                    "address": {
                    "streetLines": [
                        "Legacy D",
                        ""
                    ],
                    "city": "Plano",
                    "stateOrProvinceCode": "TX",
                    "postalCode": "75024",
                    "countryCode": "US"
                    }
                },
                "emailSubscription": false,
                "marketingEmails": false
                }
            }
            }';
        // @codingStandardsIgnoreEnd
        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $websiteId = 0;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);

        $this->customerInterfaceFactory->expects($this->any())->method('create')->willReturn($this->customerInterface);
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->expects($this->any())->method('getStoreId')->willReturn(65);
        $this->additionalDataFactory->expects($this->any())->method('create')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCollection')->willReturn($this->additionalDataCollection);
        $this->additionalDataCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('getFirstItem')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCompanyId')->willReturn(30);
        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyItem);

        $this->companyItem->expects($this->any())
            ->method('getCustomerGroupId')
            ->willReturn(4);

        $this->customerRepositoryInterface->expects($this->any())->method('save')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn(10);
        $this->companyManagement->expects($this->any())->method('assignCustomer')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setCustomerCompany')->willReturnSelf();
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(false);
        $this->customerSession->expects($this->any())->method('unsFclFdxLogin')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setUserProfileId')->willReturnSelf();
        $this->_cookieMetadata->expects($this->any())
            ->method('setDomain')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setPath')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setHttpOnly')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setSecure')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setSameSite')
            ->willReturnSelf();

        $this->cookieMetadataFactory->expects($this->any())
            ->method('createPublicCookieMetadata')
            ->willReturn($this->_cookieMetadata);
        $this->_cookieManager->expects($this->any())
            ->method('deleteCookie')
            ->willReturnSelf();
        $this->customerModel->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('loadByEmail')->willReturnSelf();
        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';

        $this->assertEquals(false, $this->_ssoHelperData->getCustomerProfile($endUrl, $fclCookies));
    }

    /**
     * @test testGetCustomerProfileWithException
     */
    public function testGetCustomerProfileWithException()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);

        $this->sdeHelper->expects($this->any())
            ->method('getIsRequestFromSdeStoreFclLogin')
            ->willReturn(false);

        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setPath')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSameSite')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getFclFdxLogin')->willReturn(false);

        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->customerSession->expects($this->any())->method('setFclFdxLogin')->willReturn(true);
        $this->curl->expects($this->any())->method('getStatus')->willReturn(402);
        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = '{
            "transactionId": "8f94c41e-c182-43a2-9ee6-449b02652406",
            "output": {
                "profile": {
                "uuId": "",
                "contact": {
                    "personName": {
                    "firstName": "Nidhi",
                    "lastName": "Singh"
                    },
                    "company": {
                    "name": "FedEx"
                    },
                    "emailDetail": {
                    "emailAddress": "nidhi.singhtest@infogain.com"
                    },
                    "phoneNumberDetails": [
                    {
                        "phoneNumber": {
                        "number": "8770598600",
                        "extension": "91"
                        }
                    },
                    {
                        "phoneNumber": {}
                    },
                    {
                        "phoneNumber": {}
                    }
                    ],
                    "address": {
                    "streetLines": [
                        "Legacy D",
                        ""
                    ],
                    "city": "Plano",
                    "stateOrProvinceCode": "TX",
                    "postalCode": "75024",
                    "countryCode": "US"
                    }
                },
                "emailSubscription": false,
                "marketingEmails": false
                }
            }
            }';
        // @codingStandardsIgnoreEnd
        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);

        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';

        $this->assertEquals(false, $this->_ssoHelperData->getCustomerProfile($endUrl, $fclCookies));
    }
    /**
     * @test testGetCustomerProfileWithExceptionPhoneNumberToggleOff
     */
    public function testGetCustomerProfileWithExceptionPhoneNumberToggleOff()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(false);

        $this->sdeHelper->expects($this->any())
            ->method('getIsRequestFromSdeStoreFclLogin')
            ->willReturn(true);

        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setPath')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSameSite')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getFclFdxLogin')->willReturn(false);

        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->customerSession->expects($this->any())->method('setFclFdxLogin')->willReturn(true);

        $this->curl->expects($this->any())->method('getStatus')->willReturn(402);
        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = '{
            "transactionId": "8f94c41e-c182-43a2-9ee6-449b02652406",
            "output": {
                "profile": {
                "uuId": "",
                "contact": {
                    "personName": {
                    "firstName": "Nidhi",
                    "lastName": "Singh"
                    },
                    "company": {
                    "name": "FedEx"
                    },
                    "emailDetail": {
                    "emailAddress": "nidhi.singhtest@infogain.com"
                    },
                    "phoneNumberDetails": [
                    {
                        "phoneNumber": {
                        "number": "8770598600",
                        "extension": "91"
                        }
                    },
                    {
                        "phoneNumber": {}
                    },
                    {
                        "phoneNumber": {}
                    }
                    ],
                    "address": {
                    "streetLines": [
                        "Legacy D",
                        ""
                    ],
                    "city": "Plano",
                    "stateOrProvinceCode": "TX",
                    "postalCode": "75024",
                    "countryCode": "US"
                    }
                },
                "emailSubscription": false,
                "marketingEmails": false
                }
            }
            }';
        // @codingStandardsIgnoreEnd
        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);

        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';

        $this->assertEquals(false, $this->_ssoHelperData->getCustomerProfile($endUrl, $fclCookies));
    }
    /**
     * @test  testGetCustomerProfileWith401

     */
    public function testGetCustomerProfileWith401()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->withConsecutive(
            )
            ->willReturnOnConsecutiveCalls(true);

        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);

        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setPath')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSameSite')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getFclFdxLogin')->willReturn(false);

        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->customerSession->expects($this->any())->method('setFclFdxLogin')->willReturn(true);

        $this->curl->expects($this->any())->method('getStatus')->willReturn(401);
        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = '{
            "transactionId": "8f94c41e-c182-43a2-9ee6-449b02652406",
            "output": {
                "profile": {
                "uuId": "",
                "contact": {
                    "personName": {
                    "firstName": "Nidhi",
                    "lastName": "Singh"
                    },
                    "company": {
                    "name": "FedEx"
                    },
                    "emailDetail": {
                    "emailAddress": "nidhi.singhtest@infogain.com"
                    },
                    "phoneNumberDetails": [
                    {
                        "phoneNumber": {
                        "number": "8770598600",
                        "extension": "91"
                        }
                    },
                    {
                        "phoneNumber": {}
                    },
                    {
                        "phoneNumber": {}
                    }
                    ],
                    "address": {
                    "streetLines": [
                        "Legacy D",
                        ""
                    ],
                    "city": "Plano",
                    "stateOrProvinceCode": "TX",
                    "postalCode": "75024",
                    "countryCode": "US"
                    }
                },
                "emailSubscription": false,
                "marketingEmails": false
                }
            }
            }';
        // @codingStandardsIgnoreEnd
        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);

        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';

        $this->assertNotNull(false, $this->_ssoHelperData->getCustomerProfile($endUrl, $fclCookies));
    }

    /**
     * Generate unique canva id
     */
    public function testGenerateUniqueCanvaId()
    {
        $canvaId = 'FSEFGWR768RFSD';
        $this->mathRandom->expects($this->any())
            ->method('getRandomString')
            ->willReturn($canvaId);

        $this->customerCollection->expects($this->any())->method('addAttributeToSelect')->with('*')->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('addAttributeToFilter')
            ->with('customer_canva_id', $canvaId)->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('load')->willReturnSelf();
        $customerArr = [['entity_id' => 'test']];
        $this->customerCollection->expects($this->any())->method('getData')->willReturn($customerArr);
        $this->assertNotEquals($canvaId, $this->_ssoHelperData->generateUniqueCanvaId());
    }
    /**
     * testGetCustomerIdByCanvaId
     */
    public function testGetCustomerIdByCanvaId()
    {
        $canvaId = 'FSEFGWR768RFSD';
        $this->customerCollection->expects($this->any())->method('addAttributeToSelect')->with('*')->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('addAttributeToFilter')
            ->with('customer_canva_id', $canvaId)->willReturnSelf();
        $this->customerCollection->expects($this->any())->method('load')->willReturnSelf();
        $customerArr = [['entity_id' => 'test']];
        $this->customerCollection->expects($this->any())->method('getData')->willReturn($customerArr);
        $this->assertNotNull($this->_ssoHelperData->getCustomerIdByCanvaId($canvaId));
    }

    /**
     * Generate unique canva id with exception
     */
    public function testGenerateUniqueCanvaIdWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $canvaId = 'FSEFGWR768RFSD';
        $this->mathRandom->expects($this->any())
            ->method('getRandomString')
            ->willReturn($canvaId);

        $this->customerCollection->expects($this->any())
            ->method('addAttributeToSelect')->with('*')->willThrowException($exception);

        $this->assertNotEquals($canvaId, $this->_ssoHelperData->generateUniqueCanvaId());
    }

    /**
     * Get customer id by uuid with exception
     */
    public function testGetCustomerIdByUuidWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->customerCollection->expects($this->any())
            ->method('addAttributeToSelect')->with('*')->willThrowException($exception);
        $this->customerRepositoryInterface->expects($this->any())
            ->method('get')
            ->willThrowException($exception);

        $this->assertEquals(null, $this->_ssoHelperData->getCustomerIdByUuid("12345678"));
    }

    /**
     * testGgetCustomerCanvaIdByUuid
     */
    public function testGetCustomerCanvaIdByUuid()
    {
        $fclUuid = 'FSEFGWR768RFSD';

        $this->customerRepositoryInterface->expects($this->any())
            ->method('get')
            ->willReturn($this->customerInterface);

        $this->customerFactory->expects($this->any())->method('create')->willReturn($this->customerModel);
        $this->customerModel->expects($this->any())->method('load')->willReturnSelf();
        $this->resourceConnection->expects($this->any())->method('getConnection')->willReturnSelf();
        $this->resourceConnection->expects($this->any())->method('select')->willReturnSelf();
        $this->resourceConnection->expects($this->any())->method('from')->willReturnSelf();
        $this->resourceConnection->expects($this->any())->method('where')->willReturnSelf();
        $this->attributeRepositoryInterface->expects($this->any())->method('get')->willReturnSelf();
        $this->attributeRepositoryInterface->expects($this->any())->method('getAttributeId')->willReturnSelf();
        $this->resourceConnection->expects($this->any())->method('fetchRow')->willReturn(['value'=>'test']);

        $this->assertEquals('test', $this->_ssoHelperData->getCustomerCanvaIdByUuid($fclUuid));
    }

    /**
     * testSetCustomerCanvaIdAfterMigration
     */
    public function testSetCustomerCanvaIdAfterMigrationEmail()
    {
        $fclUuid = 'FSEFGWR768RFSD';

        $this->customerRepositoryInterface->expects($this->any())
            ->method('get')
            ->willReturn($this->customerInterface);

        $this->customerFactory->expects($this->any())->method('create')->willReturn($this->customerModel);
        $this->customerModel->expects($this->any())->method('load')->willReturnSelf();

        $this->customerInterface->expects($this->any())->method('setData')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('save')->willReturnSelf();

        $this->assertNotNull($this->_ssoHelperData->setCustomerCanvaIdAfterMigration($fclUuid));
    }

    /**
     * @test testGetCustomerByEmail
     */
    public function testGetCustomerByEmail()
    {
        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturnSelf();

        $this->storeManager->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->customerRepositoryInterface->expects($this->any())
            ->method('get')
            ->willReturn($this->customerInterface);

        $this->assertEquals($this->customerInterface, $this->_ssoHelperData->getCustomerByEmail('user@gmail.com'));
    }

    /**
     * @test testGetCustomerByEmailWithWebsiteId
     */
    public function testGetCustomerByEmailWithWebsiteId()
    {
        $this->customerRepositoryInterface->expects($this->any())
            ->method('get')
            ->willReturn($this->customerInterface);

        $this->assertEquals($this->customerInterface, $this->_ssoHelperData->getCustomerByEmail('user@gmail.com', 1));
    }

    /**
     * @test testGetCustomerByEmailWithException
     */
    public function testGetCustomerByEmailWithException()
    {
        //throw exception
        $phrase = new Phrase(__('Exception message'));
        $exception = new Exception($phrase);
        $this->customerRepositoryInterface->expects($this->any())
            ->method('get')
            ->willThrowException($exception);

        $this->assertEquals(null, $this->_ssoHelperData->getCustomerByEmail('user@gmail.com', 1));
    }

    /**
     * @test testGetCustomerCompanyIdByStore
     */
    public function testGetCustomerCompanyIdByStore()
    {
        $ondemandCompanyInfo = ['company_data' => ['entity_id' => 30]];

        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->expects($this->any())->method('getStoreId')->willReturn(65);
        $this->additionalDataFactory->expects($this->any())->method('create')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCollection')->willReturn($this->additionalDataCollection);
        $this->additionalDataCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('getFirstItem')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCompanyId')->willReturn(30);

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->customerSession->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandCompanyInfo);

        $this->assertEquals(30, $this->_ssoHelperData->getCustomerCompanyIdByStore());
    }

    /**
     * @test testGetCompanyCustomerGroupId
     */
    public function testGetCompanyCustomerGroupId()
    {
        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyItem);

        $this->companyItem->expects($this->any())
            ->method('getCustomerGroupId')
            ->willReturn(4);

        $this->assertEquals(4, $this->_ssoHelperData->getCompanyCustomerGroupId(1));
    }

    /**
     * @test testSetCanvaIdByProfileApi
     */
    public function testSetCanvaIdByProfileApi()
    {
        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setPath')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSameSite')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getFclFdxLogin')->willReturn(true);
        $this->customerSession->expects($this->any())->method('getUserProfileId')->willReturn('6adce44a-6e98-4770-8690-d84a83d0a837');
        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd


        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->customerSession->expects($this->any())->method('setFclFdxLogin')->willReturn(true);
        $this->curl->expects($this->any())->method('getStatus')->willReturn(200);
        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = ' {
            "transactionId":"ad47fc66-6983-4347-8687-9630b5e57b1e",
            "output":{"canvaId":"badca9a6-5ed8-4b84-b597-139a376499de"}
        }';
        // @codingStandardsIgnoreEnd
        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        // $this->storeManager->expects($this->any())
        //     ->method('getWebsite')
        //     ->willReturn($this->storeManager);

        $profileId = '6adce44a-6e98-4770-8690-d84a83d0a837';
        $canvaId = 'badca9a6-5ed8-4b84-b597-139a376499de';
        $this->_cookieMetadata->expects($this->any())
            ->method('setDomain')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setPath')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setHttpOnly')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setSecure')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setSameSite')
            ->willReturnSelf();

        $this->cookieMetadataFactory->expects($this->any())
            ->method('createPublicCookieMetadata')
            ->willReturn($this->_cookieMetadata);
        $this->_cookieManager->expects($this->any())
            ->method('deleteCookie')
            ->willReturnSelf();
        // $this->storeManager->expects($this->any())
        //     ->method('getWebsiteId')
        //     ->willReturn($websiteId);

        // $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        // $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        // $this->region->expects($this->any())->method('getId')->willReturn(1);

        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';

        $this->assertEquals(true, $this->_ssoHelperData->setCanvaIdByProfileApi($endUrl, $fclCookies, $profileId, $canvaId));
    }


    /**
     * @test testSetCanvaIdByProfileApi
     */
    public function testSetCanvaIdByProfileApiFailure()
    {
        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setPath')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setSameSite')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getFclFdxLogin')->willReturn(true);
        $this->customerSession->expects($this->any())->method('getUserProfileId')->willReturn('6adce44a-6e98-4770-8690-d84a83d0a837');
        // @codingStandardsIgnoreStart
        $taztokenData = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDU4MTY3NDksImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6ImI4NmIwZDJkLWNhYTgtNDNiNy1hNWM0LWVhZTE3ZjU3MThmNSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.DIrkcIeio--kfvB7wM5mQsqz56IlmnXAR0vLxoimkwhN-7sh85_Qi-cUYQiMofTHSk3E0V1HKuHcZ-ZlwrYOmO60Lky5smD7Gboe-6U3BPZdIhHyH8RlSNtGGWmEj_0pbU7PEilc3F2iy6aJ6NFfh7FptlHBYNv8XPF5Io6jN5dK99SeybuhCQnX2a28a0xsfthk3hl8qdmmi_vPNmdet7loioAefBjEzjADLpCFHSlRaye-S3XfcjA8Ff6L2Q7WEoFH70o_wQ4Lnlq-uGO5BlYE82Ci1J2CoPzBDh2C_l9z3HlUcQeaacu4SZUs9WDC9GcrwWjOyZeQIOhQdmwtcas2DgHnP99ZC-Q2zXBaGvFrJLcXbb1oeCFRrUd6GuIKddMD8Mq6Qnt9TSW2b8YpwIm6MWs1y_IEwJXP6R-uSoww_8A_8lMdd0AOO5lTLIqgNpa9iwbk7w6WbEbqP32NiyB09NZtO12GNQVJhYtJ9XD34rXkTJOHOnEcmuPvKPyH2DivfujpSKJ6outpMfhDga0pVupeioeuCFEzqmcFNOGDxsGOpAO8iPbGMDmO7jIsnpDjq6SUCEyBY0WUnYhUNrXMJ5zq6d78mTDePBB9llTe-FrCDTBY8dI9DJkev5MNA62NiVdx6-qE042rz5KmV6Ft5PvT7wkNcRGrtFF2Lpo","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"b86b0d2d-caa8-43b7-a5c4-eae17f5718f5"}';
        // @codingStandardsIgnoreEnd

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($taztokenData);

        $this->punchoutHelper->expects($this->any())
            ->method('getRetailAuthToken')->willReturn('e3043759-734c-4881-aad6-6d78cb4c9ec6');

        $this->curl->expects($this->any())
            ->method('setOption')
            ->willReturnSelf();

        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->customerSession->expects($this->any())->method('setFclFdxLogin')->willReturn(true);
        $this->curl->expects($this->any())->method('getStatus')->willReturn(402);
        // @codingStandardsIgnoreStart
        $jsonEncodeProfileData = ' {
            "transactionId":"ad47fc66-6983-4347-8687-9630b5e57b1e",
            "output":{"canvaId":"badca9a6-5ed8-4b84-b597-139a376499de"}
        }';
        // @codingStandardsIgnoreEnd
        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($jsonEncodeProfileData);

        $profileId = '6adce44a-6e98-4770-8690-d84a83d0a837';
        $canvaId = 'badca9a6-5ed8-4b84-b597-139a376499de';
        $this->_cookieMetadata->expects($this->any())
            ->method('setDomain')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setPath')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setHttpOnly')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setSecure')
            ->willReturnSelf();

        $this->_cookieMetadata->expects($this->any())
            ->method('setSameSite')
            ->willReturnSelf();

        $this->cookieMetadataFactory->expects($this->any())
            ->method('createPublicCookieMetadata')
            ->willReturn($this->_cookieMetadata);
        $this->_cookieManager->expects($this->any())
            ->method('deleteCookie')
            ->willReturnSelf();

        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';

        $this->assertEquals(false, $this->_ssoHelperData->setCanvaIdByProfileApi($endUrl, $fclCookies, $profileId, $canvaId));
    }

    /**
     * @test testSetCanvaIdByProfileApi
     */
    public function testSetCanvaIdByProfileApiWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->cookieMetadataFactory->expects($this->any())->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactory->expects($this->any())->method('setDomain')->willThrowException($exception);

        $endUrl = 'https://staging3.office.fedex.com/default/rest/V1/fedexoffice/profile/mock/service';
        $fclCookies = 'ssotest-cos1.a206.354165ff785b3a2b8724bb39a1b3a7ba';
        $profileId = '6adce44a-6e98-4770-8690-d84a83d0a837';
        $canvaId = 'badca9a6-5ed8-4b84-b597-139a376499de';
        $this->customerCollection->expects($this->any())->method('load')->willThrowException($exception);
        $this->assertEquals(false, $this->_ssoHelperData->setCanvaIdByProfileApi($endUrl, $fclCookies, $profileId, $canvaId));
    }

    /**
     * @test testGetCustomerCanvaIdByUuidWithException
     */
    public function testGetCustomerCanvaIdByUuidWithException()
    {
        //throw exception
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->customerCollection->expects($this->any())
            ->method('addAttributeToSelect')->with('*')->willThrowException($exception);
        $this->customerRepositoryInterface->expects($this->any())
            ->method('get')
            ->willThrowException($exception);

        $this->assertEquals(null, $this->_ssoHelperData->getCustomerCanvaIdByUuid('12345678'));
    }

        /**
     * @test testSetCustomerCanvaIdAfterMigration
     */
    public function testSetCustomerCanvaIdAfterMigration()
    {
        //throw exception
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->customerCollection->expects($this->any())
            ->method('addAttributeToSelect')->with('*')->willThrowException($exception);
        $this->customerRepositoryInterface->expects($this->any())
            ->method('get')
            ->willThrowException($exception);

        $this->assertEquals(null, $this->_ssoHelperData->setCustomerCanvaIdAfterMigration('12345678'));
    }

    /**
     * @test testisCanvaIdMigrationEnabledWithFalse
     */
    public function testisCanvaIdMigrationEnabledWithFalse()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->with(Data::XML_PATH_ENABLE_CanvaId_Migration)
            ->willReturn(false);

        $this->assertEquals(false, $this->_ssoHelperData->isCanvaIdMigrationEnabled());
    }

    /**
     * @test testisCanvaIdMigrationEnabledWithTrue
     */
    public function testisCanvaIdMigrationEnabledWithTrue()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->with(Data::XML_PATH_ENABLE_CanvaId_Migration)
            ->willReturn(true);

        $this->assertEquals(true, $this->_ssoHelperData->isCanvaIdMigrationEnabled());
    }

    /**
     * @test testGetSSOLoginCustomer
     */
    public function testGetSSOLoginCustomer()
    {
        $profileDetails['address'] = ['firstName'=>'Test','lastName'=>'Test','email'=>'test@test.com'];
        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);


        $ondemandCompanyInfo = ['company_data' => ['entity_id' => 30]];

        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->expects($this->any())->method('getStoreId')->willReturn(65);
        $this->additionalDataFactory->expects($this->any())->method('create')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCollection')->willReturn($this->additionalDataCollection);
        $this->additionalDataCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('getFirstItem')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCompanyId')->willReturn(30);

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->customerSession->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandCompanyInfo);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturnSelf();

        $this->storeManager->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        // $this->customerRepositoryInterface->expects($this->any())
        //     ->method('get')
        //     ->willReturn($this->customerInterface);

        $this->customerInterfaceFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerInterface);

        $this->customerInterface->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('setLastname')->willReturnSelf();

        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyItem);

        $this->companyItem->expects($this->any())
            ->method('getCustomerGroupId')
            ->willReturn(4);

         // $this->customerRepositoryInterface->expects($this->any())
         //    ->method('get')
         //    ->willReturn($this->customerInterface);

        $this->customerRepositoryInterface->expects($this->any())->method('save')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn(10);

        $this->customerModel->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('loadByEmail')->willReturnSelf();

        $this->customerSession->expects($this->any())->method('setCustomerCompany')->willReturnSelf();
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);

        $this->sdeHelper->expects($this->any())
            ->method('setCustomerActiveSessionCookie')
            ->willReturnSelf();

        $this->assertEquals(true, $this->_ssoHelperData->getSSOLoginCustomer($profileDetails,true));
    }

    /**
     * @test testGetSSOLoginCustomerwithId
     */
    public function testGetSSOLoginCustomerwithId()
    {
        $profileDetails['address'] = ['firstName'=>'Test','lastName'=>'Test','email'=>'test@test.com'];
        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);


        $ondemandCompanyInfo = ['company_data' => ['entity_id' => 30]];

        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->expects($this->any())->method('getStoreId')->willReturn(65);
        $this->additionalDataFactory->expects($this->any())->method('create')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCollection')->willReturn($this->additionalDataCollection);
        $this->additionalDataCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('getFirstItem')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCompanyId')->willReturn(30);

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->customerSession->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandCompanyInfo);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturnSelf();

        $this->storeManager->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->customerRepositoryInterface->expects($this->any())
            ->method('get')
            ->willReturn($this->customerInterface);

        $this->customerInterfaceFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerInterface);

        $this->customerInterface->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('setLastname')->willReturnSelf();

        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyItem);

        $this->companyItem->expects($this->any())
            ->method('getCustomerGroupId')
            ->willReturn(4);

         // $this->customerRepositoryInterface->expects($this->any())
         //    ->method('get')
         //    ->willReturn($this->customerInterface);

        $this->customerRepositoryInterface->expects($this->any())->method('save')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn(10);

        $this->customerModel->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('loadByEmail')->willReturnSelf();

        $this->customerSession->expects($this->any())->method('setCustomerCompany')->willReturnSelf();
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);

        $this->sdeHelper->expects($this->any())
            ->method('setCustomerActiveSessionCookie')
            ->willReturnSelf();

        $this->assertEquals(true, $this->_ssoHelperData->getSSOLoginCustomer($profileDetails,true));
    }

    /**
     * @test testUpdateCustomerCanvaId
     */
    public function testUpdateCustomerCanvaId()
    {
        $this->testGetCustomerCanvaIdByUuid();
        $this->assertNull($this->_ssoHelperData->updateCustomerCanvaId(
            '1',
            '12test',
            $this->customerModel
        ));
    }

    /**
     * @test testUpdateCustomerCanvaId\
     */
    public function testUpdateCustomerCanvaIdWithNew()
    {
        $this->assertNull($this->_ssoHelperData->updateCustomerCanvaId(
            '',
            '12test',
            $this->customerModel
        ));
    }

    /**
     * @test testGetSSOLoginCustomerwithException
     */
    public function testGetSSOLoginCustomerwithException()
    {
        $profileDetails['address'] = ['firstName'=>'Test','lastName'=>'Test','email'=>'test@test.com'];
        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);


        $ondemandCompanyInfo = ['company_data' => ['entity_id' => 30]];

        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->expects($this->any())->method('getStoreId')->willReturn(65);
        $this->additionalDataFactory->expects($this->any())->method('create')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCollection')->willReturn($this->additionalDataCollection);
        $this->additionalDataCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('getFirstItem')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCompanyId')->willReturn(30);

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->customerSession->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandCompanyInfo);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturnSelf();

        $this->storeManager->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $phrase = new Phrase(__('Exception message'));
        $exception = new Exception($phrase);


        $this->customerInterfaceFactory->expects($this->any())
            ->method('create')
            ->willThrowException($exception);

        $this->customerInterface->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('setLastname')->willReturnSelf();

        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyItem);

        $this->companyItem->expects($this->any())
            ->method('getCustomerGroupId')
            ->willReturn(4);

        $this->customerRepositoryInterface->expects($this->any())->method('save')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn(10);

        $this->customerModel->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('loadByEmail')->willReturnSelf();

        $this->customerSession->expects($this->any())->method('setCustomerCompany')->willReturnSelf();
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);

        $this->sdeHelper->expects($this->any())
            ->method('setCustomerActiveSessionCookie')
            ->willReturnSelf();

        $this->assertEquals(false, $this->_ssoHelperData->getSSOLoginCustomer($profileDetails,true));
    }

    /**
     * @test testGetSSOLoginCustomerwithoutLogin
     */
    public function testGetSSOLoginCustomerwithoutLogin()
    {
        $profileDetails['address'] = ['firstName'=>'Test','lastName'=>'Test','email'=>'test@test.com'];
        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);

        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);


        $ondemandCompanyInfo = ['company_data' => ['entity_id' => 30]];

        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->expects($this->any())->method('getStoreId')->willReturn(65);
        $this->additionalDataFactory->expects($this->any())->method('create')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCollection')->willReturn($this->additionalDataCollection);
        $this->additionalDataCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('getFirstItem')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCompanyId')->willReturn(30);

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->customerSession->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($ondemandCompanyInfo);

        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturnSelf();

        $this->storeManager->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->customerInterfaceFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerInterface);

        $this->customerInterface->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerInterface->expects($this->any())->method('setLastname')->willReturnSelf();

        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyItem);

        $this->companyItem->expects($this->any())
            ->method('getCustomerGroupId')
            ->willReturn(4);

        $this->customerRepositoryInterface->expects($this->any())->method('save')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn(10);

        $this->customerModel->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerModel->expects($this->any())->method('loadByEmail')->willReturnSelf();

        $this->customerSession->expects($this->any())->method('setCustomerCompany')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('isLoggedIn')->willReturn(false);

        $this->sdeHelper->expects($this->any())
            ->method('setCustomerActiveSessionCookie')
            ->willReturnSelf();

        $this->assertEquals(false, $this->_ssoHelperData->getSSOLoginCustomer($profileDetails,true));
    }

    public function testSetFclMetaDataCookies()
    {
        $this->publicCookieMetadataMock->expects($this->any())
            ->method('setDomain')
            ->willReturnSelf();

        $this->publicCookieMetadataMock->expects($this->any())
            ->method('setPath')
            ->willReturnSelf();

        $this->publicCookieMetadataMock->expects($this->any())
            ->method('setHttpOnly')
            ->willReturnSelf();

        $this->publicCookieMetadataMock->expects($this->any())
            ->method('setSecure')
            ->willReturnSelf();

        $this->publicCookieMetadataMock->expects($this->any())
            ->method('setSameSite')
            ->willReturnSelf();

        $this->cookieMetadataFactory->expects($this->any())
            ->method('createPublicCookieMetadata')
            ->willReturn($this->publicCookieMetadataMock);

        $this->_cookieManager->expects($this->exactly(2))
            ->method('setPublicCookie')
            ->withConsecutive(
                ['fcl_customer_login', true, $this->publicCookieMetadataMock],
                ['fcl_customer_login_success', true, $this->publicCookieMetadataMock]
            );

        $this->_ssoHelperData->setFclMetaDataCookies();
    }
    public function testCallFclLogoutApi(){
        $result= '{"transactionId":"adda8df9-a5cb-4718-a65f-c9e61ef06f09","errors":[{"code":"LOGIN.REAUTHENTICATE.ERROR","message":"GENERIC.ERROR"}]}';
        $this->purpleGatewayTokenMock->expects($this->any())
                    ->method('getFedexRatesToken')
                    ->willReturn('l7a8119691017e419fbf1411c982c0c732');
        $this->_cookieManager->expects($this->any())
                    ->method('getCookie')
                    ->with('fdx_login')
                    ->willReturn('ssotest-cos2.95bd.5a71458f4ad25cfd7374477254bd878f');
        $this->ssoConfigMock->expects($this->any())
                    ->method('getFclLogoutApiUrl')
                    ->willReturn('https://apitest.fedex.com/user/v3/logout');
        $this->curl->expects($this->any())
                        ->method('setOptions')
                        ->willReturnSelf();
        $this->curl->expects($this->any())
                    ->method('post')
                    ->willReturnSelf();
        $this->curl->expects($this->any())
                ->method('getBody')
                ->willReturn($result);
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn('fdx_login');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->_ssoHelperData->callFclLogoutApi();
    }

    /**
     * test method for callFclLogoutApi
     */
    public function testCallFclLogoutApiWithFCLToogleOff() {
        $result= '{
            "transactionId":"adda8df9-a5cb-4718-a65f-c9e61ef06f09",
            "errors":[
                {"code":"LOGIN.REAUTHENTICATE.ERROR","message":"GENERIC.ERROR"}
                ]
            }';
        $this->purpleGatewayTokenMock->expects($this->any())
            ->method('getFedexRatesToken')
            ->willReturn('l7a8119691017e419fbf1411c982c0c732');
        $this->_cookieManager->expects($this->any())
            ->method('getCookie')
            ->with('fdx_login')
            ->willReturn('ssotest-cos2.95bd.5a71458f4ad25cfd7374477254bd878f');
        $this->ssoConfigMock->expects($this->any())
            ->method('getFclLogoutApiUrl')
            ->willReturn('https://apitest.fedex.com/user/v3/logout');
        $this->curl->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();
        $this->curl->expects($this->any())
            ->method('post')
            ->willReturnSelf();
        $this->curl->expects($this->any())
            ->method('getBody')
            ->willReturn($result);
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);

        $this->assertNull($this->_ssoHelperData->callFclLogoutApi());
    }

    /**
     * Test isFCLCookieNameToggle
     *
     * @return void
     */
    public function testGetFCLCookieNameToggle()
    {
        $this->toggleConfig->expects($this->once())->method('getToggleConfigValue')->willReturn(true);

        $this->assertEquals(true, $this->_ssoHelperData->getFCLCookieNameToggle());
    }

    /**
     * Test GetConfigValue
     *
     * @return void
     */
    public function testGetFCLCookieConfigValue()
    {
        $expectedResult = 'fdx_login';
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn('fdx_login');

        $this->assertEquals($expectedResult, $this->_ssoHelperData->getFCLCookieConfigValue());
    }

    public function testIsProfileHasInvoiceValid()
    {
        $preferences = [
            (object)[
                'name' => 'INVOICE_NUMBER',
                'values' => [(object)[
                    'name' => 'defaultValue',
                    'value' => '12345'
                ]]
            ]
        ];

        $this->testGetAccountSummary();

        $this->assertNotNull($this->_ssoHelperData->isProfileHasInvoiceValid($preferences));
    }

    public function testGetAccountNumber()
    {
        $preferences = [
            (object)[
                'name' => 'INVOICE_NUMBER',
                'values' => [(object)[
                    'name' => 'defaultValue',
                    'value' => '12345'
                ]]
            ]
        ];
        $this->testGetAccountSummary();
        $this->assertNotNull($this->_ssoHelperData->getAccountNumber($preferences));
    }

    public function testGetFedexAccounts()
    {
        $profile = (object) [
            'preferences' => [
                (object) [
                    'name' => 'INVOICE_NUMBER',
                    'values' => [(object)[
                        'name' => 'defaultValue',
                        'value' => '12345'
                        ]
                    ]
                ]
            ],
            'accounts' => [
                (object)[
                    'accountNumber' => '12345',
                    'maskedAccountNumber' => '*0324'
                    ]
            ]
        ];
        $this->testGetAccountNumber();
        $this->testGetAccountSummary();

        $accounts = $this->_ssoHelperData->getFedexAccounts($profile);

        $this->assertNotEmpty($accounts);
    }

    public function testGetAccountSummary()
    {
        $response = '{
            "transactionId": "a6dd9fc8-5516-4f50-95d6-0c54d447d554",
            "output": {
                "accounts": [
                    {
                        "accountNumber": "630493021",
                        "accountDisplayNumber": "0630493021",
                        "accountType": "BUS",
                        "maskedAccountNumber": "*3021",
                        "accountName": "LULULEMON ATHLETICG",
                        "contact": {
                            "person": {
                                "firstName": "LAURA",
                                "lastName": "HARPER"
                            },
                            "email": null,
                            "phone": {
                                "country": "1",
                                "lineNumber": "7326124",
                                "areaCode": "604"
                            }
                        },
                        "address": {
                            "type": null,
                            "street": "2201 140TH AVE E",
                            "city": "SUMNER",
                            "state": "WA",
                            "zipcode": "983909711"
                        },
                        "accountUsage": {
                            "print": {
                                "legalCompanyName": "LULULEMON ATHLETICD",
                                "enabled": null,
                                "payment": {
                                    "type": "invoice",
                                    "allowed": "Y"
                                },
                                "pricing": {
                                    "type": null,
                                    "groupId": null,
                                    "cplId": "5599",
                                    "discountPercentage": "20",
                                    "groupName": null
                                },
                                "status": "Inactive",
                                "type": null,
                                "source": null,
                                "taxCertificates": "TAX",
                                "futurePricingVo": null
                            },
                            "ship": {
                                "status": "false"
                            },
                            "originatingOpco": "FXK",
                            "batchId": "CAELuluAth091715_01"
                        }
                    }
                ]
            }
        }';
        $this->curl->method('getBody')->willReturn($response);

        $result = $this->_ssoHelperData->getAccountSummary('12345');
        $this->assertNotNull($result);
    }

    /**
     * Test method for getSSOWithFCLToggle
     *
     * @return void
     */
    public function testGetSSOWithFCLToggle()
    {
        $this->toggleConfig->expects($this->once())->method('getToggleConfigValue')->willReturn(true);

        $this->assertEquals(true, $this->_ssoHelperData->getSSOWithFCLToggle());
    }

    /**
     * Test for getCustomerIdByUuid method
     */
    public function testGetCustomerIdByUuid()
    {
        $fclUuid = 'test-uuid';
        $fclUuidEmail = $fclUuid . '@fedex.com';
        $customerId = 123;

        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customerMock->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);

        $this->customerRepositoryInterface->expects($this->once())
            ->method('get')
            ->with($fclUuidEmail)
            ->willReturn($customerMock);

        $this->assertEquals($customerId, $this->_ssoHelperData->getCustomerIdByUuid($fclUuid));
    }

    /**
     * Test for updateLoginUserId method
     */
    public function testUpdateLoginUserId()
    {
        $loginUserId = 'new-login-id';
        $uuId = 'test-uuid';
        $customerId = 123;

        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerMock->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);

        $this->customerRepositoryInterface->expects($this->once())
            ->method('get')
            ->with($uuId . '@fedex.com')
            ->willReturn($customerMock);

        $customerMock2 = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getExternalUserId', 'setExternalUserId', 'save', 'load'])
            ->getMockForAbstractClass();
        $customerMock2->expects($this->once())
            ->method('getExternalUserId')
            ->willReturn('old-login-id');
        $customerMock2->expects($this->once())
            ->method('setExternalUserId')
            ->with($loginUserId)
            ->willReturnSelf();
        $customerMock2->expects($this->once())
            ->method('save');
        $customerMock2->expects($this->once())
            ->method('load')
            ->willReturnSelf();

        $this->customerFactory->expects($this->once())
            ->method('create')
            ->willReturn($customerMock2);

        $this->_ssoHelperData->updateLoginUserId($loginUserId, $uuId);
    }

    /**
     * Get customer id by uuid with exception
     */
    public function testUpdateLoginUserIdWithException()
    {
        $loginUserId = 'new-login-id';
        $uuId = 'test-uuid';
        $customerId = 123;

        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerMock->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);

        $this->customerRepositoryInterface->expects($this->once())
            ->method('get')
            ->with($uuId . '@fedex.com')
            ->willReturn($customerMock);

        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->customerFactory->expects($this->once())
            ->method('create')
            ->willThrowException($exception);

        $this->assertEquals(null, $this->_ssoHelperData->updateLoginUserId($loginUserId, $uuId));
    }

}
