<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Test\Unit\Controller\Account;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;
use Fedex\EnhancedProfile\Controller\Account\AddNewAccount;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Fedex\EnhancedProfile\Test\Unit\Controller\Account\SampleData;
use Fedex\Base\Helper\Auth;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test class for Fedex\EnhancedProfile\Controller\Account\AddNewAccount
 */
class AddNewAccountTest extends TestCase
{
    protected $addNewAccount;
    /**
     * @var JsonFactory|MockObject
     */
    protected $jsonFactory;

    /**
     * @var EnhancedProfile|MockObject
     */
    protected $enhancedProfile;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManagerHelper;

    protected Auth|MockObject $baseAuthMock;

    protected MockObject|Json $jsonMock;

    /**
     * Test setUp
     */
    public function setUp(): void
    {

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->enhancedProfile = $this->getMockBuilder(EnhancedProfile::class)
            ->setMethods(
                [
                    'getConfigValue',
                    'apiCall',
                    'setProfileSession',
                    'makeNewAccountHtml',
                    'getLoggedInProfileInfo',
                    'isLoggedIn'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPost'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->addNewAccount = $this->objectManagerHelper->getObject(
            AddNewAccount::class,
            [
                'jsonFactory' => $this->jsonFactory,
                'enhancedProfile' => $this->enhancedProfile,
                'request' => $this->requestMock,
                'authHelper' => $this->baseAuthMock
            ]
        );
    }

    /**
     * Test execute method WithToggleOnForLoggedIn
     *
     * @return void
     */
    public function testExecuteWithToggleOnForLoggedIn()
    {
        $this->baseAuthMock->expects($this->once())
            ->method('isLoggedIn')->willReturn(true);
        $this->apiCall();
        $this->assertNotNull($this->addNewAccount->execute());
    }

    /**
     * Test execute method WithToggleOnForNonLoggedIn
     *
     * @return void
     */
    public function testExecuteWithToggleOnForNonLoggedIn()
    {

        $this->apiCall();
        $this->assertNotNull($this->addNewAccount->execute());
    }

    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecute()
    {
        $this->apiCall();
        $this->assertNotNull($this->addNewAccount->execute());
    }

    /**
     * Test execute method with exception with Toggle on
     *
     * @return void
     */
    public function testExecuteWithExceptionWithToggleOn()
    {
        $this->baseAuthMock->expects($this->once())
            ->method('isLoggedIn')->willReturn(true);
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->requestMock->expects($this->any())->method('getPost')->willReturn('ACCOUNT');
        $this->jsonFactory->expects($this->any())->method('create')->willReturnSelf();

        $this->enhancedProfile->expects($this->any())->method('getConfigValue')->willReturn(
            'https://fxo-retailprofile-service-development.app.clwdev1.paas.fedex.com/'
        );

        $this->enhancedProfile->expects($this->any())->method('apiCall')->willThrowException($exception);

        $this->assertNotNull($this->addNewAccount->execute());
    }

    /**
     * Test execute method with exception
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->requestMock->expects($this->any())->method('getPost')->willReturn('ACCOUNT');
        $this->jsonFactory->expects($this->any())->method('create')->willReturnSelf();

        $this->enhancedProfile->expects($this->any())->method('getConfigValue')->willReturn(
            'https://fxo-retailprofile-service-development.app.clwdev1.paas.fedex.com/'
        );

        $this->enhancedProfile->expects($this->any())->method('apiCall')->willThrowException($exception);

        $this->assertNotNull($this->addNewAccount->execute());
    }

    /**
     * Common test logic for api Call
     */
    public function apiCall()
    {
        $this->requestMock->expects($this->any())->method('getPost')->willReturn('ACCOUNT');
        $this->jsonFactory->expects($this->any())->method('create')->willReturnSelf();

        $this->enhancedProfile->expects($this->any())->method('getConfigValue')->willReturn(
            'https://fxo-retailprofile-service-development.fedex.com'
        );

        $apiOutPut = '{
            "accounts": [
                {
                   "profileAccountId": "",
                    "accountNumber": "7888489334679",
                    "accountLabel": "MasterCard998886943679",
                    "billingReference": "9220",
                    "accountType": "PRINTING"
                }
            ]
        }';

        $this->enhancedProfile->expects($this->any())->method('apiCall')->willReturn($apiOutPut);

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->jsonFactory->expects($this->any())->method('setData')->willReturnSelf();
    }

    /**
     * Test execute method with API ResponseWith ToggleOn
     *
     * @return void
     */
    public function testWithApiResponseWithToggleOn()
    {
        $this->baseAuthMock->expects($this->once())
            ->method('isLoggedIn')->willReturn(true);
        $dummyJsonData = '{
            "transactionId": "106f4966-f8aa-4fd2-a34a-a6a5b74e473f",
            "output": {
                "profile": {
                    "userProfileId": "3933d6a8-fd00-4519-ad15-fbc17fe606ff"
                }
            }
        }';

        $this->requestMock->expects($this->any())->method('getPost')->willReturn('true');
        $this->jsonFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->enhancedProfile->expects($this->any())->method('setProfileSession')->willReturnSelf();

        $this->enhancedProfile->expects($this->any())->method('getConfigValue')->willReturn(
            'https://fxo-retailprofile-service-development.app.clwdev1.paas.fedex.com'
        );

        $sampleData = new SampleData('John Doe');

        $this->enhancedProfile->expects($this->any())->method('apiCall')->willReturn($sampleData);
        $this->enhancedProfile->expects($this->any())->method('getLoggedInProfileInfo')
                            ->willReturn(json_decode($dummyJsonData));

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->jsonFactory->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertNotNull($this->addNewAccount->execute());
    }

    /**
     * Test execute method with API Response
     *
     * @return void
     */
    public function testWithApiResponse()
    {
        $dummyJsonData = '{
            "transactionId": "106f4966-f8aa-4fd2-a34a-a6a5b74e473f",
            "output": {
                "profile": {
                    "userProfileId": "3933d6a8-fd00-4519-ad15-fbc17fe606ff"
                }
            }
        }';

        $this->requestMock->expects($this->any())->method('getPost')->willReturn('true');
        $this->jsonFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->enhancedProfile->expects($this->any())->method('setProfileSession')->willReturnSelf();

        $this->enhancedProfile->expects($this->any())->method('getConfigValue')->willReturn(
            'https://fxo-retailprofile-service-development.app.clwdev1.paas.fedex.com'
        );

        $sampleData = new SampleData('John Doe');

        $this->enhancedProfile->expects($this->any())->method('apiCall')->willReturn($sampleData);
        $this->enhancedProfile->expects($this->any())->method('getLoggedInProfileInfo')
                            ->willReturn(json_decode($dummyJsonData));

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->jsonFactory->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertNotNull($this->addNewAccount->execute());
    }

    /**
     * Test execute with same nick name
     *
     * @return void
     */
    public function testExecuteWithNickName()
    {
        $dummyJsonData = '{
            "transactionId": "106f4966-f8aa-4fd2-a34a-a6a5b74e473f",
            "output": {
                "profile": {
                    "userProfileId": "3933d6a8-fd00-4519-ad15-fbc17fe606ff",
                    "accounts": [
                        {
                           "profileAccountId": "",
                            "accountNumber": "7888489334679",
                            "accountLabel": "true",
                            "billingReference": "9220",
                            "accountType": "PRINTING"
                        }
                    ]
                }
            }
        }';

        $this->requestMock->expects($this->any())->method('getPost')->willReturn('true');
        $this->jsonFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->enhancedProfile->expects($this->any())->method('setProfileSession')->willReturnSelf();

        $this->enhancedProfile->expects($this->any())->method('getConfigValue')->willReturn(
            'https://fxo-retailprofile-service-development.app.clwdev1.paas.fedex.com'
        );

        $sampleData = new SampleData('John Doe');

        $this->enhancedProfile->expects($this->any())->method('apiCall')->willReturn($sampleData);
        $this->enhancedProfile->expects($this->any())->method('getLoggedInProfileInfo')
                            ->willReturn(json_decode($dummyJsonData));

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->jsonFactory->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertNotNull($this->addNewAccount->execute());
    }

    /**
     * Test Set Preferred Payment Method
     *
     * @return void
     */
    public function testSetPreferredPaymentMethod()
    {
        $dummyJsonData = '{
            "transactionId": "106f4966-f8aa-4fd2-a34a-a6a5b74e473f",
            "output": {
                "profile": {
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
        $this->enhancedProfile->expects($this->any())->method('getLoggedInProfileInfo')
                            ->willReturn(json_decode($dummyJsonData));

        $this->assertNull($this->addNewAccount->setPreferredPaymentMethod());
    }

    /**
     * Test execute method with exception
     *
     * @return void
     */
    public function testSetPreferredPaymentMethodWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->enhancedProfile->expects($this->any())->method('getLoggedInProfileInfo')->willThrowException($exception);

        $this->assertNull($this->addNewAccount->setPreferredPaymentMethod());
    }
}
