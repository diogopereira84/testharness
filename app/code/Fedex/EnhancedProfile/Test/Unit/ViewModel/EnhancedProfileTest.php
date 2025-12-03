<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Test\Unit\ViewModel;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Magento\Directory\Model\Country;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\HTTP\Client\Curl;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Psr\Log\LoggerInterface;
use Fedex\Shipto\Helper\Data;
use Fedex\SSO\Helper\Data as SsoHelper;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\Base\Helper\Auth;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EnhancedProfileTest extends TestCase
{
    /**
     * @var (\Fedex\UploadToQuote\Helper\AdminConfigHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $adminConfigHelperMock;
    protected $cookieManager;
    protected $toggleConfig;
    protected $shipToHelperMock;
    protected $storeManagerInterfaceMock;
    protected $companyRepository;
    protected $companyInterface;
    protected $enhancedProfileData;
    public const TAZ_TOKEN = '{
        "access_token": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
        "token_type": "taz"
    }';

    /**
     * FCL My Profile Id
     */
    public const FCL_MY_PROFILE_URL = 'sso/general/fcl_my_profile_url';

    public const SHARED_CC_TOOLTIP ='web/shared_cc_Tooltip/Message_cc';

    /**
     * @var Country
     */
    protected $shipToHelper;

    /**
     * @var Country
     */
    protected $country;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Repository|MockObject
     */
    protected $repositoryMock;

    /**
     * @var Curl|MockObject
     */
    protected $curlMock;

    /**
     * @var PunchoutHelper|MockObject
     */
    protected $punchoutHelperMock;

    /**
     * @var DateTimeFactory|MockObject
     */
    protected $dateTimeFactoryMock;

    /**
     * @var DateTime|MockObject
     */
    protected $dateTimeMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerInterfaceMock;

    /**
     * @var Data|MockObject
     */
    protected $shipToDataMock;

    /**
     * @var SsoHelper|MockObject
     */
    protected $ssoHelperMock;

    /**
     * @var ArgumentInterface|MockObject
     */
    protected $argumentInterfaceMock;

    protected Auth|MockObject $baseAuthMock;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->country = $this->getMockBuilder(Country::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadByCode', 'getRegions', 'loadData', 'toOptionArray'])
            ->getMock();

        $this->adminConfigHelperMock = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isEnableGeocodeApi', 'checkoutQuotePriceisDashable'])
            ->getMock();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->customerSession = $this->getMockBuilder(Session::class)
                            ->disableOriginalConstructor()
                            ->setMethods(
                                [
                                    'getCreditCardList',
                                    'getFedexAccountsList',
                                    'getProfileSession',
                                    'isLoggedIn',
                                    'getEmail',
                                    'getCustomer',
                                    'setProfileSession',
                                    'getLoginValidationKey',
                                    'getId',
                                    'getOndemandCompanyInfo',
                                    'getFdxLogin'
                                ]
                            )
                            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->cookieManager = $this->getMockBuilder(CookieManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCookie'])
            ->getMockForAbstractClass();

        $this->repositoryMock = $this->getMockBuilder(Repository::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['getUrl'])
                                ->getMock();

        $this->curlMock = $this->getMockBuilder(Curl::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['setOptions', 'post', 'getBody'])
                                ->getMock();

        $this->punchoutHelperMock = $this->getMockBuilder(PunchoutHelper::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['getTazToken', 'getAuthGatewayToken'])
                                ->getMock();

        $this->dateTimeFactoryMock = $this->getMockBuilder(DateTimeFactory::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['create', 'gmtDate'])
                                ->getMock();

        $this->dateTimeMock = $this->getMockBuilder(DateTime::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['gmtDate'])
                                ->getMock();

        $this->toggleConfig = $this->getMockBuilder(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class)
                                ->setMethods(['getToggleConfigValue'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->loggerInterfaceMock = $this->getMockBuilder(LoggerInterface::class)
                                ->disableOriginalConstructor()
                                ->getMockForAbstractClass();

        $this->shipToHelperMock = $this->getMockBuilder(Data::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['getAddressByLocationId'])
                                ->getMock();

        $this->ssoHelperMock = $this->getMockBuilder(SsoHelper::class)
                                ->disableOriginalConstructor()
                                ->setMethods([
                                    'getFCLCookieConfigValue',
                                    'getFCLCookieNameToggle'
                                ])->getMock();

        $this->argumentInterfaceMock = $this->getMockBuilder(ArgumentInterface::class)
                                ->disableOriginalConstructor()
                                ->getMockForAbstractClass();

        $this->storeManagerInterfaceMock = $this->getMockBuilder(StoreManagerInterface::class)
                                        ->disableOriginalConstructor()
                                        ->setMethods(['getStore','getBaseUrl'])
                                        ->getMockForAbstractClass();
        $this->companyRepository = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyInterface = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getByCustomerId','getStorefrontLoginMethodOption'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->enhancedProfileData = $this->objectManager->getObject(
            EnhancedProfile::class,
            [
                'country' => $this->country,
                'scopeConfig' => $this->scopeConfig,
                'customerSession' => $this->customerSession,
                'cookieManager' => $this->cookieManager,
                'assetRepo' => $this->repositoryMock,
                'curl' => $this->curlMock,
                'punchoutHelper' => $this->punchoutHelperMock,
                'dateTimeFactory' => $this->dateTimeFactoryMock,
                'toggleConfig' => $this->toggleConfig,
                'logger' => $this->loggerInterfaceMock,
                'shipToHelper' => $this->shipToHelperMock,
                'storeManager' => $this->storeManagerInterfaceMock,
                'companyRepository' => $this->companyRepository,
                'authHelper' => $this->baseAuthMock
            ]
        );
    }

    /**
     * Test getPreferredDelivery
     */
    public function testGetPreferredDelivery()
    {
        $this->shipToHelperMock->expects($this->any())->method('getAddressByLocationId')->willReturnSelf();
        $this->assertEquals($this->shipToHelperMock, $this->enhancedProfileData->getPreferredDelivery('4027'));
    }

    /**
     * Test getRegionsOfCountry
     */
    public function testGetRegionsOfCountry()
    {
        $this->country->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->country->expects($this->any())->method('getRegions')->willReturnSelf();
        $this->country->expects($this->any())->method('loadData')->willReturnSelf();
        $this->country->expects($this->any())->method('toOptionArray')->willReturnSelf();
        $this->assertNotEquals(null, $this->enhancedProfileData->getRegionsOfCountry('usa'));
    }

    /**
     * Test getRegionsOfCountry
     */
    public function testGetConfigValue()
    {
        $code = 'enhancedprofile/enhancedprofile_group/terms_and_condition_url';
        $url = 'https://www.magento.com';
        $this->scopeConfig->expects($this->any())->method('getValue')->willReturn($url);
        $this->assertEquals($url, $this->enhancedProfileData->getConfigValue($code));
    }

    /**
     * Test getFclMyProfileUrl
     *
     * @return void
     */
    public function testGetFclMyProfileUrl()
    {
        $FclUrl = 'https://www.fedex.com/profile/';
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->with(self::FCL_MY_PROFILE_URL, ScopeInterface::SCOPE_STORE)
            ->willReturn($FclUrl);

        $this->assertEquals($FclUrl, $this->enhancedProfileData->getFclMyProfileUrl());
    }

    /**
     * Test GetCreditCardListWithData
     */
    public function testGetCreditCardListWithData()
    {
        $this->customerSession->expects($this->any())->method('getCreditCardList')->willReturn(true);
        $this->assertEquals(true, $this->enhancedProfileData->getCreditCardList());
    }

    /**
     * Test GetCreditCardListWitouthData
     */
    public function testGetCreditCardListWithoutData()
    {
        $this->customerSession->expects($this->any())->method('getCreditCardList')->willReturn(false);
        $this->assertEquals([], $this->enhancedProfileData->getCreditCardList());
    }

    /**
     * Test GetFedexAccountsListWithData
     */
    public function testGetFedexAccountsListWithData()
    {
        $this->customerSession->expects($this->any())->method('getFedexAccountsList')->willReturn(true);
        $this->assertEquals(true, $this->enhancedProfileData->getFedexAccountsList());
    }

    /**
     * Test GetFedexAccountsListWitouthData
     */
    public function testGetFedexAccountsListWitouthData()
    {
        $this->customerSession->expects($this->any())->method('getFedexAccountsList')->willReturn(false);
        $this->assertEquals([], $this->enhancedProfileData->getFedexAccountsList());
    }

    /**
     * Test testGetLoggedInProfileInfoWithData
     */
    public function testGetLoggedInProfileInfoWithData()
    {
        $this->customerSession->expects($this->any())->method('getProfileSession')->willReturn(true);
        $this->assertEquals(true, $this->enhancedProfileData->getLoggedInProfileInfo());
    }

    /**
     * Test GetLoggedInProfileInfoWithoutData
     */
    public function testGetLoggedInProfileInfoWithoutData()
    {
        $this->customerSession->expects($this->any())->method('getProfileSession')->willReturn(false);
        $this->assertEquals([], $this->enhancedProfileData->getLoggedInProfileInfo());
    }

    /**
     * Test SetProfileSession
     */
    public function testSetProfileSession()
    {
        $endUrl = 'https://mage.url';
        $fdxLogin = '3435125141321';
        $this->cookieManager->expects($this->any())->method('getCookie')->willReturn($fdxLogin);
        $this->ssoHelperMock->expects($this->any())->method('getFCLCookieNameToggle')->willReturn(true);
        $this->ssoHelperMock->expects($this->any())->method('getFCLCookieConfigValue')->willReturn('b2387423');

        $this->assertEquals(null, $this->enhancedProfileData->setProfileSession($endUrl, $fdxLogin));
    }

    /**
     * Test GetOpeningHours
     */
    public function testGetOpeningHours()
    {

        $workingHours[0] = (object) [
                            'date' => '27-06-2021',
                            'day' => 'MONDAY',
                            'schedule' => '27-06-2021',
                            'openTime' => '9:01 AM',
                            'closeTime' => '6:02 PM'
                        ];

        $workingHours[1] = (object) [
                            'date' => '27-06-2022',
                            'day' => 'TUESDAY',
                            'schedule' => 'Open',
                            'openTime' => '9:02 AM',
                            'closeTime' => '6:02 PM'
                        ];

        $workingHours[2] = (object) [
                            'date' => '27-06-2023',
                            'day' => 'WEDNESDAY',
                            'schedule' => 'Open',
                            'openTime' => '9:03 AM',
                            'closeTime' => '6:03 PM'
                        ];

        $workingHours[3] = (object) [
                            'date' => '27-06-2022',
                            'day' => 'THURSDAY',
                            'schedule' => 'Open',
                            'openTime' => '9:04 AM',
                            'closeTime' => '6:04 PM'
                        ];

        $workingHours[4] = (object) [
                            'date' => '01-07-2022',
                            'day' => 'FRIDAY',
                            'schedule' => 'Open',
                            'openTime' => '9:05 AM',
                            'closeTime' => '6:05 PM'
                        ];

        $workingHours[5] = (object) [
                            'date' => '02-07-2022',
                            'day' => 'SATURDAY',
                            'schedule' => 'Open',
                            'openTime' => '9:06 AM',
                            'closeTime' => '6:06 PM'
                        ];

        $workingHours[6] = (object) [
                            'date' => '03-07-2022',
                            'day' => 'SUNDAY',
                            'schedule' => 'Closed',
                            'openTime' => '',
                            'closeTime' => '',
                        ];

        $this->assertNotEquals(null, $this->enhancedProfileData->getOpeningHours($workingHours));
    }

    /**
     * Test Make Array For Cerdit Card in Json
     *
     * @return void
     */
    public function testMakeArrayForCerditCardJson()
    {
        $prepareCerditCardJson = '{
            "creditCardToken": "string",
            "tokenExpirationDate": "2022-06-23",
            "cardHolderName": "string",
            "maskedCreditCardNumber": "strin",
            "creditCardLabel": "string",
            "creditCardType": "string",
            "expirationMonth": "02",
            "expirationYear": "9835",
            "billingAddress": {
            "company": {
                "name": "string"
            },
            "streetLines": [
                "string"
            ],
            "city": "string",
            "stateOrProvinceCode": "string",
            "postalCode": "string",
            "countryCode": "string",
            "addressClassification": "HOME"
            },
            "primary": true
        }';
        $prepareCerditCardJson = json_decode($prepareCerditCardJson);

        $this->assertNotNull($this->enhancedProfileData->makeArrayForCerditCardJson($prepareCerditCardJson));
    }

    /**
     * Test Prepare array For Add Cerdit Card in Json
     *
     * @return void
     */
    public function testPrepareAddCerditCardJson()
    {
        $prepareCerditCardJson = '{
            "creditCardToken": "string",
            "tokenExpirationDate": "2022-06-23",
            "cardHolderName": "string",
            "maskedCreditCardNumber": "strin",
            "creditCardLabel": "string",
            "creditCardType": "string",
            "expirationMonth": "02",
            "expirationYear": "9835",
            "streetLines": "string",
            "city": "string",
            "stateOrProvinceCode": "string",
            "postalCode": "string",
            "countryCode": "string",
            "addressClassification": "HOME",
            "primary": true,
            "company": "string"
        }';
        $prepareCerditCardJson = json_decode($prepareCerditCardJson, true);
        $this->assertNotNull($this->enhancedProfileData->prepareAddCerditCardJson($prepareCerditCardJson));
    }

    /**
     * Test Prepare array For Add Cerdit Card in Json
     *
     * @return void
     */
    public function testPrepareUpdateCerditCardJson()
    {
        $prepareCerditCardJson = '{
            "creditCardToken": "string",
            "tokenExpirationDate": "2022-06-23",
            "cardHolderName": "string",
            "maskedCreditCardNumber": "strin",
            "creditCardLabel": "string",
            "creditCardType": "string",
            "expirationMonth": "02",
            "expirationYear": "9835",
            "streetLines": "string",
            "city": "string",
            "stateOrProvinceCode": "string",
            "postalCode": "string",
            "countryCode": "string",
            "addressClassification": "HOME",
            "primary": true,
            "company": "string"
        }';
        $prepareCerditCardJson = json_decode($prepareCerditCardJson, true);
        $this->assertNotNull($this->enhancedProfileData->prepareUpdateCerditCardJson($prepareCerditCardJson));
    }

    /**
     * Test make html for New Account
     *
     * @return void
     */
    public function testmakeNewAccountHtml()
    {
        $prepareNewAccountJson = '{
            "accounts": [
                {
                    "profileAccountId": "bb06efe9-d317-4e42-815b-a91177d1892b",
                    "accountNumber": "7888489333333",
                    "maskedAccountNumber": "*3333",
                    "accountLabel": "MasterCard998886333333",
                    "accountType": "PRINTING",
                    "billingReference": "9220",
                    "primary": true
                }
            ]
        }';

        $bodyResponse = '{
            "transactionId": "a6dd9fc8-5516-4f50-95d6-0c54d447d554",
            "output": {
                "accounts": [
                    {
                        "accountNumber": "630493021",
                        "accountDisplayNumber": "0630493021",
                        "accountType": "BUS",
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
                                    "allowed": "N"
                                },
                                "pricing": {
                                    "type": null,
                                    "groupId": null,
                                    "cplId": "5599",
                                    "discountPercentage": "20",
                                    "groupName": null
                                },
                                "status": "Active",
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
        $this->punchoutHelperMock->expects($this->any())->method('getTazToken')->willReturn(self::TAZ_TOKEN);
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn("tokengetway");
        $this->curlMock->expects($this->any())->method('getBody')->willReturn($bodyResponse);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManagerInterfaceMock->expects($this->any())->method('getBaseUrl')->willReturn('media');

        $prepareNewAccountJson = json_decode($prepareNewAccountJson);
        $this->assertNotNull($this->enhancedProfileData->makeNewAccountHtml($prepareNewAccountJson, '0'));
    }

    /**
     * Test make html for New Account With Payment
     *
     * @return void
     */
    public function testmakeNewAccountHtmlWithPayment()
    {
        $prepareNewAccountJson = '{
            "accounts": [
                {
                    "profileAccountId": "bb06efe9-d317-4e42-815b-a91177d1892b",
                    "accountNumber": "7888489333333",
                    "maskedAccountNumber": "*3333",
                    "accountLabel": "MasterCard998886333333",
                    "accountType": "PRINTING",
                    "billingReference": "9220",
                    "primary": false
                }
            ]
        }';

        $bodyResponse = '{
            "transactionId": "a6dd9fc8-5516-4f50-95d6-0c54d447d554",
            "output": {
                "accounts": [
                    {
                        "accountNumber": "630493021",
                        "accountDisplayNumber": "0630493021",
                        "accountType": "BUS",
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
                                "status": "Active",
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
        $this->punchoutHelperMock->expects($this->any())->method('getTazToken')->willReturn(self::TAZ_TOKEN);
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn("tokengetway");
        $this->curlMock->expects($this->any())->method('getBody')->willReturn($bodyResponse);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManagerInterfaceMock->expects($this->any())->method('getBaseUrl')->willReturn('media');

        $prepareNewAccountJson = json_decode($prepareNewAccountJson);
        $this->assertNotNull($this->enhancedProfileData->makeNewAccountHtml($prepareNewAccountJson, '0'));
    }

    /**
     * Test make html with New Primary Account
     *
     * @return void
     */
    public function testmakeNewPrimaryAccountHtml()
    {
        $prepareNewAccountJson = '{
            "accounts": [
                {
                    "profileAccountId": "bb06efe9-d317-4e42-815b-a91177d1892b",
                    "accountNumber": "7888489333333",
                    "maskedAccountNumber": "*3333",
                    "accountLabel": "MasterCard998886333333",
                    "accountType": "PRINTING",
                    "billingReference": "9220",
                    "primary": true
                }
            ]
        }';

        $bodyResponse = '{
            "transactionId": "a6dd9fc8-5516-4f50-95d6-0c54d447d554",
            "output": {
                "accounts": [
                    {
                        "accountNumber": "630493021",
                        "accountDisplayNumber": "0630493021",
                        "accountType": "BUS",
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
                                    "allowed": "N"
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
        $this->punchoutHelperMock->expects($this->any())->method('getTazToken')->willReturn(self::TAZ_TOKEN);
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn("tokengetway");
        $this->curlMock->expects($this->any())->method('getBody')->willReturn($bodyResponse);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManagerInterfaceMock->expects($this->any())->method('getBaseUrl')->willReturn('media');

        $prepareNewAccountJson = json_decode($prepareNewAccountJson);
        $this->assertNotNull($this->enhancedProfileData->makeNewAccountHtml($prepareNewAccountJson, '0'));
    }

    /**
     * Test make html for credit card
     *
     * @return void
     */
    public function testMakeCreditCardHtml()
    {
        $prepareCerditCardJson = '{
            "creditCardList": [
                {
                    "billingAddress": {
                        "city": "PLANO",
                        "company": {
                            "name": "Fedex"
                        },
                        "countryCode": "US",
                        "postalCode": "75024",
                        "stateOrProvinceCode": "TX",
                        "streetLines": [
                            "StreetLine1"
                        ]
                    },
                    "cardHolderName": "STUART BROAD",
                    "creditCardLabel": "VISA_00010",
                    "creditCardToken": "12345678",
                    "creditCardType": "VISA",
                    "expirationMonth": "08",
                    "expirationYear": "2025",
                    "maskedCreditCardNumber": "00010",
                    "primary": true,
                    "tokenExpirationDate": "2000-06-26T09:50:35Z",
                    "profileCreditCardId": "121ert"
                }
            ]
        }';
        $prepareCerditCardJson = json_decode($prepareCerditCardJson);
        $this->dateTimeFactoryMock->expects($this->once())->method('create')->willReturn($this->dateTimeMock);
        $this->dateTimeMock->expects($this->once())->method('gmtDate')->willReturn("2018-01-25T09:50:36Z");
        $this->country->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->country->expects($this->any())->method('getRegions')->willReturnSelf();
        $this->country->expects($this->any())->method('loadData')->willReturnSelf();
        $this->country->expects($this->any())->method('toOptionArray')
                        ->willReturn(json_decode('[{"label": "TX","title": "TX"}]', true));
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManagerInterfaceMock->expects($this->any())->method('getBaseUrl')->willReturn('media');

        $this->assertNotNull($this->enhancedProfileData->makeCreditCardHtml($prepareCerditCardJson, '0'));
    }

    /**
     * Test make html for credit card for update
     *
     * @return void
     */
    public function testMakeCreditCardHtmlForUpdate()
    {
        $prepareCerditCardJson = '{
            "creditCard":
            {
                "cardHolderName": "PIYUSH RATHI ",
                "maskedCreditCardNumber": "12345",
                "creditCardLabel":"VISA_2605",
                "creditCardType": "VISA",
                "creditCardToken": "12345678",
                "tokenExpirationDate": "2023-01-01",
                "expirationMonth": "09",
                "expirationYear": "2027",
                "billingAddress": {
                    "streetLines": [
                        "StreetLine2"
                    ],
                    "city": "PLANO",
                    "stateOrProvinceCode": "TX",
                    "postalCode": "72345",
                    "countryCode": "DEF",
                    "company": {
                        "name": "Infogain"
                    }
                },
                "primary": true,
                "profileCreditCardId": "121ert"
            }
        }';
        $prepareCerditCardJson = json_decode($prepareCerditCardJson);
        $this->dateTimeFactoryMock->expects($this->once())->method('create')->willReturn($this->dateTimeMock);
        $this->dateTimeMock->expects($this->once())->method('gmtDate')->willReturn("2018-01-25T09:50:35Z");
        $this->country->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->country->expects($this->any())->method('getRegions')->willReturnSelf();
        $this->country->expects($this->any())->method('loadData')->willReturnSelf();
        $this->country->expects($this->any())->method('toOptionArray')->willReturnSelf();
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManagerInterfaceMock->expects($this->any())->method('getBaseUrl')->willReturn('media');

        $this->assertNotNull($this->enhancedProfileData->makeCreditCardHtml($prepareCerditCardJson, '1'));
    }

    /**
     * Test make html for credit card make as default
     *
     * @return void
     */
    public function testMakeCreditCardHtmlWithMakeAsDefault()
    {
        $prepareCerditCardJson = '{
            "creditCardList": [
                {
                    "billingAddress": {
                        "city": "PLANO",
                        "company": {
                            "name": "Fedex"
                        },
                        "countryCode": "US",
                        "postalCode": "75024",
                        "stateOrProvinceCode": "TX",
                        "streetLines": [
                            "StreetLine1"
                        ]
                    },
                    "cardHolderName": "STUART BROAD",
                    "creditCardLabel": "VISA_00010",
                    "creditCardToken": "12345678",
                    "creditCardType": "VISA",
                    "expirationMonth": "08",
                    "expirationYear": "2025",
                    "maskedCreditCardNumber": "00010",
                    "primary": false,
                    "tokenExpirationDate": "2022-06-26T09:50:35Z",
                    "profileCreditCardId": "121ert"
                }
            ]
        }';
        $this->dateTimeFactoryMock->expects($this->once())->method('create')->willReturn($this->dateTimeMock);
        $this->dateTimeMock->expects($this->once())->method('gmtDate')->willReturn("2022-01-25T09:50:35Z");
        $prepareCerditCardJson = json_decode($prepareCerditCardJson);
        $this->country->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->country->expects($this->any())->method('getRegions')->willReturnSelf();
        $this->country->expects($this->any())->method('loadData')->willReturnSelf();
        $this->country->expects($this->any())->method('toOptionArray')->willReturnSelf();
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManagerInterfaceMock->expects($this->any())->method('getBaseUrl')->willReturn('media');

        $this->assertNotNull($this->enhancedProfileData->makeCreditCardHtml($prepareCerditCardJson, "0"));
    }

    /**
     * Test for save credit card
     *
     * @return void
     */
    public function testSaveCreditCard()
    {
        $prepareCerditCardJson = '{
            "output": {
                "profile": {
                    "userProfileId": "3933d6a8-fd00-4519-ad15-fbc17fe606ff"
                }
            }
        }';
        $prepareCerditCardJson = json_decode($prepareCerditCardJson);
        $this->customerSession->expects($this->any())->method('getProfileSession')->willReturn($prepareCerditCardJson);

        $this->assertNull($this->enhancedProfileData->saveCreditCard('POST', $prepareCerditCardJson));
    }

    /**
     * Test for update credit card
     *
     * @return void
     */
    public function testUpdateCreditCard()
    {
        $prepareCerditCardJson = '{
            "output": {
                "profile": {
                    "userProfileId": "3933d6a8-fd00-4519-ad15-fbc17fe606ff"
                }
            }
        }';
        $prepareCerditCardJson = json_decode($prepareCerditCardJson);
        $this->customerSession->expects($this->any())->method('getProfileSession')->willReturn($prepareCerditCardJson);

        $this->assertNull($this->enhancedProfileData->updateCreditCard('POST', 'ert-1213-prd', $prepareCerditCardJson));
    }

    /**
     * Test for Prepare credit card Token JSON
     *
     * @return void
     */
    public function testPrepareCreditCardTokensJson()
    {
        $creditCardInfo = [
            'requestId' => '1212121',
            'encryptedData' => '+UI^&%$#12331',
            'nameOnCard' => 'Ravi Kant Kumar',
            'streetLines' => 'Titu Clonoy, Dehhraj Nagar Part 2',
            'city' => 'Faridabad',
            'stateOrProvinceCode' => 'Haryana',
            'postalCode' => '121003',
            'countryCode' => 'IND',
        ];
        $this->assertNotNull($this->enhancedProfileData->prepareCreditCardTokensJson($creditCardInfo));
    }

    /**
     * Test for API response
     *
     * @return void
     */
    public function testApiCall()
    {
        $this->punchoutHelperMock->expects($this->any())->method('getTazToken')->willReturn(self::TAZ_TOKEN);
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn("tokengetway");
        $this->assertNull($this->enhancedProfileData->apiCall('POST', 'ert-1213', "ert45435"));
    }

    /**
     * Test for API response without post data
     *
     * @return void
     */
    public function testApiCallWithoutPostData()
    {
        $this->punchoutHelperMock->expects($this->any())->method('getTazToken')->willReturn(self::TAZ_TOKEN);
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn("tokengetway");
        $this->assertNull($this->enhancedProfileData->apiCall('POST', 'ert-1213', ""));
    }

    /**
     * Test API response for account summary
     *
     * @return void
     */
    public function testGetAccountSummary()
    {
        $bodyResponse = '{
            "transactionId": "a6dd9fc8-5516-4f50-95d6-0c54d447d554",
            "output": {
                "accounts": [
                    {
                        "accountNumber": "630493021",
                        "accountDisplayNumber": "0630493021",
                        "accountType": "BUS",
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
                                    "allowed": "N"
                                },
                                "pricing": {
                                    "type": null,
                                    "groupId": null,
                                    "cplId": "5599",
                                    "discountPercentage": "20",
                                    "groupName": null
                                },
                                "status": "Active",
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
        $this->punchoutHelperMock->expects($this->any())->method('getTazToken')->willReturn(self::TAZ_TOKEN);
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn("tokengetway");
        $this->curlMock->expects($this->any())->method('getBody')->willReturn($bodyResponse);
        $this->assertNotNull($this->enhancedProfileData->getAccountSummary("ert45435"));
    }

    /**
     * Test API response for account summary with payment type
     *
     * @return void
     */
    public function testGetAccountSummaryWithPaymentType()
    {
        $bodyResponse = '{
            "transactionId": "a6dd9fc8-5516-4f50-95d6-0c54d447d554",
            "output": {
                "accounts": [
                    {
                        "accountNumber": "630493021",
                        "accountDisplayNumber": "0630493021",
                        "accountType": "BUS",
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
        $this->punchoutHelperMock->expects($this->any())->method('getTazToken')->willReturn(self::TAZ_TOKEN);
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn("tokengetway");
        $this->curlMock->expects($this->any())->method('getBody')->willReturn($bodyResponse);
        $this->assertNotNull($this->enhancedProfileData->getAccountSummary("ert45435"));
    }

    /**
     * Test API response for account summary with exception
     *
     * @return void
     */
    public function testGetAccountSummaryWithException()
    {
        $phrase = new Phrase(__('Exception messages'));
        $exception = new LocalizedException($phrase);

        $this->punchoutHelperMock->expects($this->any())->method('getTazToken')->willReturn(self::TAZ_TOKEN);
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn("tokengetway");
        $this->curlMock->expects($this->any())->method('post')->willThrowException($exception);

        $this->assertNotNull($this->enhancedProfileData->getAccountSummary("ert45435"));
    }

    /**
     * Validate credit card token expairation
     *
     * @return void
     */
    public function testGetTokenIsExpired()
    {
        $this->dateTimeFactoryMock->expects($this->once())->method('create')->willReturn($this->dateTimeMock);
        $this->dateTimeMock->expects($this->once())->method('gmtDate')->willReturn("2018-01-25T09:50:35Z");

        $this->assertNotNull($this->enhancedProfileData->getTokenIsExpired("2018-01-25T08:50:35Z"));
    }

    /**
     * Test Set Default Shipping Method
     */
    public function testSetDefaultShippingMethod()
    {
        $getLoggedInProfileInfo = '{
            "transactionId": "106f4966-f8aa-4fd2-a34a-a6a5b74e473f",
            "output": {
              "profile": {
                "userProfileId": "raviertyuiop967uyhtret",
                "accounts": [
                  {
                    "accountNumber": "610977553",
                    "maskedAccountNumber": "*7553",
                    "accountLabel": "My Account-553",
                    "accountType": "SHIPPING",
                    "primary": false,
                    "accountValid": false
                  }
                ]
              }
            }
        }';
        $getLoggedInProfileInfo = json_decode($getLoggedInProfileInfo);

        $this->customerSession->expects($this->any())->method('getProfileSession')->willReturn($getLoggedInProfileInfo);
        $this->assertNull($this->enhancedProfileData->setDefaultShippingMethod());
    }

    /**
     * Test Set Default Shipping Method Without Primary
     */
    public function testSetDefaultShippingMethodWithoutPrimary()
    {
        $getLoggedInProfileInfo = '{
            "transactionId": "106f4966-f8aa-4fd2-a34a-a6a5b74e473f",
            "output": {
              "profile": {
                "userProfileId": "raviertyuiop967uyhtret",
                "accounts": [
                  {
                    "accountNumber": "610977553",
                    "maskedAccountNumber": "*7553",
                    "accountLabel": "My Account-553",
                    "accountType": "SHIPPING",
                    "primary": true,
                    "accountValid": false
                  }
                ]
              }
            }
        }';
        $getLoggedInProfileInfo = json_decode($getLoggedInProfileInfo);

        $this->customerSession->expects($this->any())->method('getProfileSession')->willReturn($getLoggedInProfileInfo);
        $this->assertNull($this->enhancedProfileData->setDefaultShippingMethod());
    }

    /**
     * Test Set Default Shipping Method With Exception
     */
    public function testSetDefaultShippingMethodWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->customerSession->expects($this->any())->method('getProfileSession')->willThrowException($exception);
        $this->assertNull($this->enhancedProfileData->setDefaultShippingMethod());
    }

    /**
     * Test Set Preferred Payment Method
     */
    public function testsetPreferredPaymentMethod()
    {
        $getLoggedInProfileInfo = '{
            "transactionId": "106f4966-f8aa-4fd2-a34a-a6a5b74e473f",
            "output": {
                "profile": {
                    "userProfileId": "raviertyuiop967uyhtret",
                    "creditCards": {
                        "0": {
                          "profileCreditCardId": "80addce1-1d40-454e-ac98-8ec36196777a",
                          "creditCardLabel": "VISA_11111",
                          "creditCardType": "VISA",
                          "maskedCreditCardNumber": "11111",
                          "cardHolderName": "ravi",
                          "expirationMonth": "10",
                          "tokenExpirationDate": "2026-01-17T00:00:00Z",
                          "expirationYear": "2029",
                          "billingAddress": {
                            "company": {},
                            "streetLines": {
                              "0": "9400 WADE BLVD,1539"
                            },
                            "city": "FRISCO",
                            "stateOrProvinceCode": "TX",
                            "postalCode": "75035",
                            "countryCode": "US"
                          },
                          "primary": "",
                          "tokenExpired": "false"
                        }
                    }
                }
            }
        }';
        $getLoggedInProfileInfo = json_decode($getLoggedInProfileInfo);

        $this->customerSession->expects($this->any())->method('getProfileSession')->willReturn($getLoggedInProfileInfo);
        $this->assertNull($this->enhancedProfileData->setPreferredPaymentMethod());
    }

    /**
     * Test Set Preferred Payment Method With Account
     */
    public function testsetPreferredPaymentMethodWithAccount()
    {
        $bodyResponse = '{
            "transactionId": "a6dd9fc8-5516-4f50-95d6-0c54d447d554",
            "output": {
                "profile": {
                    "userProfileId": "raviertyuiop967uyhtret",
                    "accounts": [
                      {
                        "accountNumber": "610977553",
                        "maskedAccountNumber": "*7553",
                        "accountLabel": "My Account-553",
                        "accountType": "PRINTING",
                        "primary": true,
                        "accountValid": false
                      }
                    ]
                },
                "accounts": [
                    {
                        "accountNumber": "630493021",
                        "accountDisplayNumber": "0630493021",
                        "accountType": "BUS",
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
                                    "allowed": "N"
                                },
                                "pricing": {
                                    "type": null,
                                    "groupId": null,
                                    "cplId": "5599",
                                    "discountPercentage": "20",
                                    "groupName": null
                                },
                                "status": "Active",
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

        $getLoggedInProfileInfo = json_decode($bodyResponse);
        $this->curlMock->expects($this->any())->method('getBody')->willReturn($bodyResponse);
        $this->customerSession->expects($this->any())->method('getProfileSession')->willReturn($getLoggedInProfileInfo);
        $this->assertNull($this->enhancedProfileData->setPreferredPaymentMethod());
    }

    /**
     * Test Set Preferred Payment Method With Exception
     */
    public function testPreferredPaymentMethodWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->customerSession->expects($this->any())->method('getProfileSession')->willThrowException($exception);
        $this->assertNull($this->enhancedProfileData->setPreferredPaymentMethod());
    }

    /**
     * Test validate Fdx Login
     */
    public function testValidateFdxLogin()
    {
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getEmail')->willReturn("ravi5.umar@infogain.com");
        $this->customerSession->expects($this->any())->method('setProfileSession')->willReturn([]);
        $this->customerSession->expects($this->any())->method('getId')->willReturn(2);
        $this->ssoHelperMock->expects($this->any())->method('getFCLCookieNameToggle')->willReturn(true);
        $this->ssoHelperMock->expects($this->any())->method('getFCLCookieConfigValue')->willReturn('b2387423');
        $this->companyRepository->expects($this->any())
            ->method('getByCustomerId')
            ->willReturn($this->companyInterface);

        $this->companyInterface->expects($this->any())
            ->method('getStorefrontLoginMethodOption')
            ->willReturn('commercial_store_epro');

        $this->assertNotNull($this->enhancedProfileData->validateFdxLogin());
    }

    /**
     * Test validate Fdx Login with toggle off
     */
    public function testValidateFdxLoginwithToggleOff()
    {
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getEmail')->willReturn("ravi5.umar@infogain.com");
        $this->customerSession->expects($this->any())->method('setProfileSession')->willReturn([]);
        $this->customerSession->expects($this->any())->method('getId')->willReturn(2);

        $this->companyRepository->expects($this->any())
            ->method('getByCustomerId')
            ->willReturn($this->companyInterface);

        $this->companyInterface->expects($this->any())
            ->method('getStorefrontLoginMethodOption')
            ->willReturn('commercial_store_epro');

        $this->assertNotNull($this->enhancedProfileData->validateFdxLogin());
    }

    public function testGetMediaUrl(){
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManagerInterfaceMock->expects($this->any())->method('getBaseUrl')->willReturn('media');
        $this->assertNotNull($this->enhancedProfileData->getMediaUrl());
    }

    /**
     * Test IsEproLogin
     * @return void
     */
    public function testIsEproLogin() {

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->customerSession->expects($this->any())->method('getId')->willReturn(2);

        $this->companyRepository->expects($this->any())
            ->method('getByCustomerId')
            ->willReturn($this->companyInterface);

        $this->companyInterface->expects($this->any())
            ->method('getStorefrontLoginMethodOption')
            ->willReturn('commercial_store_epro');

        $this->assertNotNull($this->enhancedProfileData->isEproLogin());
    }

    /**
     * Test IsEproLogin
     * @return void
     */
    public function testIsEproLoginwithoutCompany() {
        $this->customerSession->expects($this->any())->method('getId')->willReturn(2);
        $this->companyRepository->expects($this->any())
            ->method('getByCustomerId')
            ->willReturn(false);
        $this->companyInterface->expects($this->any())
            ->method('getStorefrontLoginMethodOption')
            ->willReturn('commercial_store_epro');

        $this->assertNotNull($this->enhancedProfileData->isEproLogin());
    }

    /**
     * Test IsEproLogin
     * @return void
     */
    public function testIsEproLoginwithToggleOff() {

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(false);

        $this->customerSession->expects($this->any())->method('getId')->willReturn(2);

        $this->companyRepository->expects($this->any())
            ->method('getByCustomerId')
            ->willReturn($this->companyInterface);

        $this->companyInterface->expects($this->any())
            ->method('getStorefrontLoginMethodOption')
            ->willReturn('commercial_store_epro');

        $this->assertNotNull($this->enhancedProfileData->isEproLogin());
    }

     /**
     * Test getTooltipMessage
     *
     * @return void
     */
    public function testGetTooltipMessage()
    {
        $Message = 'TooltipMessage';
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->with(self::SHARED_CC_TOOLTIP, ScopeInterface::SCOPE_STORE)
            ->willReturn($Message);

        $this->assertEquals($Message, $this->enhancedProfileData->getTooltipMessage());
    }

    /**
     * Test testIsLoggedIn
     *
     * @return void
     */
    public function testIsLoggedIn()
    {
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->assertTrue($this->enhancedProfileData->isLoggedIn());
    }

    /**
     * Test getLoginValidationKey
     */
    public function testGetLoginValidationKey()
    {
        $this->customerSession->expects($this->any())->method('getLoginValidationKey')->willReturn(true);
        $this->assertTrue($this->enhancedProfileData->getLoginValidationKey());
    }

    /**
     * Test isCompanySettingToggleEnabled
     */
    public function testIsCompanySettingToggleEnabled()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->with(EnhancedProfile::TOGGLE_KEY)
            ->willReturn(true);
        $result = $this->enhancedProfileData->isCompanySettingToggleEnabled();
        $this->assertTrue($result);
    }

    /**
     * Test isCompanySettingToggleEnabled
     */
    public function testIsCompanySettingToggleDisabled()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->with(EnhancedProfile::TOGGLE_KEY)
            ->willReturn(false);
        $result = $this->enhancedProfileData->isCompanySettingToggleEnabled();
        $this->assertFalse($result);
    }

    /**
     * Test make html for New Account
     *
     * @return void
     */
    public function testSiteLevelMakeNewAccountHtml()
    {
        $bodyResponse = '{
            "transactionId": "a6dd9fc8-5516-4f50-95d6-0c54d447d554",
            "output": {
                "accounts": [
                    {
                        "accountNumber": "630493021",
                        "accountDisplayNumber": "0630493021",
                        "accountType": "BUS",
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
                                    "allowed": "N"
                                },
                                "pricing": {
                                    "type": null,
                                    "groupId": null,
                                    "cplId": "5599",
                                    "discountPercentage": "20",
                                    "groupName": null
                                },
                                "status": "Active",
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
        $this->punchoutHelperMock->expects($this->any())->method('getTazToken')->willReturn(self::TAZ_TOKEN);
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn("tokengetway");
        $this->curlMock->expects($this->any())->method('getBody')->willReturn($bodyResponse);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManagerInterfaceMock->expects($this->any())->method('getBaseUrl')->willReturn('media');

        $this->assertNotNull($this->enhancedProfileData->siteLevelMakeNewAccountHtml("601410800", "print", 1));
    }

    /**
     * Test make html for New Account
     *
     * @return void
     */
    public function testSiteLevelMakeNewAccountHtmlShip()
    {
        $bodyResponse = '{
            "transactionId": "a6dd9fc8-5516-4f50-95d6-0c54d447d554",
            "output": {
                "accounts": [
                    {
                        "accountNumber": "630493021",
                        "accountDisplayNumber": "0630493021",
                        "accountType": "BUS",
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
                                    "allowed": "N"
                                },
                                "pricing": {
                                    "type": null,
                                    "groupId": null,
                                    "cplId": "5599",
                                    "discountPercentage": "20",
                                    "groupName": null
                                },
                                "status": "Active",
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
        $this->punchoutHelperMock->expects($this->any())->method('getTazToken')->willReturn(self::TAZ_TOKEN);
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn("tokengetway");
        $this->curlMock->expects($this->any())->method('getBody')->willReturn($bodyResponse);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManagerInterfaceMock->expects($this->any())->method('getBaseUrl')->willReturn('media');

        $this->assertNotNull($this->enhancedProfileData->siteLevelMakeNewAccountHtml("150067600", "ship", 0));
    }

    public function testIsTigerE486666Enabled()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->with(EnhancedProfile::TIGER_E486666)
            ->willReturn(true);

        $this->assertTrue($this->enhancedProfileData->isTigerE486666Enabled());
    }

    public function testIsTigerE486666Disabled()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->with(EnhancedProfile::TIGER_E486666)
            ->willReturn(false);

        $this->assertFalse($this->enhancedProfileData->isTigerE486666Enabled());
    }
}
