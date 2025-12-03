<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpressCheckout\Test\Unit\Controller\CreditCard;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\ExpressCheckout\Controller\CreditCard\SaveInfo;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ResponseInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\Region;
use Fedex\Base\Helper\Auth;
use Fedex\Recaptcha\Model\Validator;


/**
 * Unit test for save credit card
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveInfoTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\ResponseInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $appResponseInterfaceMock;
    /**
     * @var \Magento\Framework\App\RequestInterface & \PHPUnit\Framework\MockObject\MockObject
     */
    protected $headersMock;
    /**
     * @var \Magento\Framework\App\ResponseInterface & \PHPUnit\Framework\MockObject\MockObject
     */
    protected $regionFactoryMock;
    /**
     * @var \Magento\Framework\App\ResponseInterface & \PHPUnit\Framework\MockObject\MockObject
     */
    protected $regionMock;
    /**
     * @var \Magento\Framework\App\ResponseInterface & \PHPUnit\Framework\MockObject\MockObject
     */
    protected $recaptchaValidatorMock;
    public const LOGIN_VALIDATION_KEY = '12345';
    public const PREPARE_CREDIT_CARD_DATA = '{
        "profileCreditCardId": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
        "cardHolderName": "Ravi Kant Kumar",
        "maskedCreditCardNumber": "4111111111111111",
        "creditCardLabel": "VISA",
        "creditCardType": "VISA",
        "expirationMonth": "10",
        "expirationYear": "2029",
        "company": "Infogain",
        "streetLines": [
            "6146 Honey Bluff Parkway Calder"
            ],
        "city": "Plano",
        "stateOrProvinceCode": "TX",
        "postalCode": "75024",
        "countryCode": "US",
        "primary": true,
        "saveStatus": true
    }';

    public const ENCRYPTION_CREDIT_CARD_DATA = '{
        "creditCardTokenRequest": {
            "requestId": "1213331111111111",
            "creditCard": {
                "encryptedData": "jCUtsBfhXT6sydLGrBPG9vKds21V",
                "nameOnCard": "Ravi Kant Kumar"
            }
        }
    }';

    public const ENCRYPTION_CREDIT_CARD_RESPONSE_DATA = '{
        "output": {
            "creditCardToken": {
                "token": "jCUtsBfhXT6sydLGrBPG9vKds21V",
                "expirationDateTime": "2022-01-25T09:50:35Z"
            }
        }
    }';

    public const PREPARE_CREDIT_CARD_JSON_DATA = '{
        "creditCard": {
          "creditCardToken": "string",
          "tokenExpirationDate": "2022-06-23",
          "cardHolderName": "string",
          "maskedCreditCardNumber": "strin",
          "creditCardLabel": "string",
          "creditCardType": "string",
          "expirationMonth": "02",
          "expirationYear": "9835",
          "billingAddress": {
            "company": "string",
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
        }
    }';

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var EnhancedProfile|MockObject
     */
    protected $enhancedProfileMock;

    /**
     * @var JsonFactory|MockObject
     */
    protected $jsonFactoryMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var SaveInfo|MockObject
     */
    protected $saveInfoMock;

    /**
     * @var Session|MockObject
     */
    protected $customerSessionMock;

    /**
     * @var RegionFactory|MockObject
     */
    protected $regionFactory;

    /**
     * @var Auth|MockObject
     */
    protected Auth|MockObject $baseAuthMock;

    protected function setUp(): void
    {
        $this->recaptchaValidatorMock = $this->getMockBuilder(Validator::class)
            ->disableOriginalConstructor()
            ->getMock();


        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->enhancedProfileMock = $this->getMockBuilder(EnhancedProfile::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'saveCreditCard',
                    'prepareAddCerditCardJson',
                    'getConfigValue',
                    'setProfileSession',
                    'apiCall',
                    'prepareCreditCardTokensJson',
                    'isLoggedIn'
                ]
            )
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->addMethods(['toggleEnabled'])
            ->getMock();

        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setData'])
            ->getMock();

        $this->jsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->jsonFactoryMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLoginValidationKey'])
            ->getMock();
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->appResponseInterfaceMock = $this->getMockBuilder(ResponseInterface::class)
            ->setMethods(['setHttpResponseCode', 'sendHeaders', 'setBody', 'sendResponse'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->headersMock = $this->getMockBuilder(\Laminas\Http\Headers::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->regionMock = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCode'])
            ->onlyMethods(['load', 'getId'])
            ->getMock();

        $this->regionMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->regionMock->expects($this->any())
            ->method('getCode')
            ->willReturn('TX');

        $this->regionFactoryMock = $this->getMockBuilder(RegionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->regionMock->expects($this->any())->method('getCode')->with('169')->willReturn('TX');

        $this->regionFactoryMock->expects($this->any())->method('create')->willReturn($this->regionMock);

        $this->requestMock->expects($this->any())
            ->method('getHeaders')->willReturn($this->headersMock);

        $this->objectManager = new ObjectManager($this);

        $this->saveInfoMock = $this->objectManager->getObject(
            SaveInfo::class,
            [
                'context' => $this->contextMock,
                'enhancedProfile' => $this->enhancedProfileMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'logger' => $this->loggerMock,
                'customerSession' => $this->customerSessionMock,
                'response' => $this->appResponseInterfaceMock,
                'authHelper' => $this->baseAuthMock,
                'recaptchaValidator' => $this->recaptchaValidatorMock,
                'regionFactory' => $this->regionFactoryMock
            ]
        );
    }

    /**
     * Test execute with update Card With Toggle on for Logged In
     *
     * @return void
     */
    public function testExecuteWithToggleForLoggedIn()
    {
        $this->baseAuthMock->expects($this->once())
            ->method('isLoggedIn')->willReturn(true);
        $headersMock = $this->getMockBuilder(\Laminas\Http\Headers::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock->expects($this->any())
            ->method('getHeaders')->willReturn($headersMock);
        $headersMock->expects($this->any())
            ->method('toString')
            ->willReturn('Your expected headers string');
        $this->requestMock->expects($this->any())
            ->method('getParam')->with('loginValidationKey')->willReturn(self::LOGIN_VALIDATION_KEY);
        $this->customerSessionMock->expects($this->any())
            ->method('getLoginValidationKey')->willReturn((string)self::LOGIN_VALIDATION_KEY);
        $this->apiCall();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Test execute with update Card With Toggle on for Non Logged In
     *
     * @return void
     */
    public function testExecuteWithToggleForNonLoggedIn()
    {
        $this->requestMock->expects($this->any())
            ->method('getParam')->with('loginValidationKey')->willReturn(self::LOGIN_VALIDATION_KEY);
        $this->customerSessionMock->expects($this->any())
            ->method('getLoginValidationKey')->willReturn((string)self::LOGIN_VALIDATION_KEY);
        $this->apiCall();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Test execute with update Card
     *
     * @return void
     */
    public function testExecute()
    {
        $this->apiCall();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    public function testExecuteRecaptchaError(): void
    {
        // NEW: Configure recaptcha to be enabled and return an error array.
        $errorData = ['error' => 'Recaptcha failed'];
        $this->recaptchaValidatorMock->method('isRecaptchaEnabled')->willReturn(true);
        $this->recaptchaValidatorMock->method('validateRecaptcha')
            ->with(SaveInfo::CHECKOUT_CC_RECAPTCHA)
            ->willReturn($errorData);

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->jsonFactoryMock->expects($this->once())
            ->method('setData')
            ->with($errorData)
            ->willReturnSelf();

        $result = $this->saveInfoMock->execute();
        $this->assertSame($this->jsonFactoryMock, $result);
    }

    public function testExecuteWithRegionLookup(): void
    {
        // Disable recaptcha.
        $this->recaptchaValidatorMock->method('isRecaptchaEnabled')->willReturn(false);

        // Setup request params with regionId and empty stateOrProvinceCode.
        $params = [
            'loginValidationKey' => self::LOGIN_VALIDATION_KEY,
            'regionId' => '169',
            'stateOrProvinceCode' => ''
        ];
        $this->requestMock->method('getParams')->willReturn($params);
        $this->requestMock->method('getParam')->with('loginValidationKey')->willReturn(self::LOGIN_VALIDATION_KEY);
        $this->customerSessionMock->method('getLoginValidationKey')->willReturn(self::LOGIN_VALIDATION_KEY);
        $this->baseAuthMock->method('toggleEnabled')->willReturn(false);
        $this->enhancedProfileMock->method('isLoggedIn')->willReturn(1);

        // Simulate an error response.
        $this->jsonFactoryMock->expects($this->once())
            ->method('setData')
            ->with([
                'status' => 'error',
                'message' => 'System error, Please try again.'
            ])
            ->willReturnSelf();

        $result = $this->saveInfoMock->execute();
        $this->assertSame($this->jsonFactoryMock, $result);
    }
    public function testExecuteApiErrorResponse(): void
    {
        // NEW: Disable recaptcha.
        $this->recaptchaValidatorMock->method('isRecaptchaEnabled')->willReturn(false);

        // NEW: Configure valid request, auth, and session.
        $params = [
            'loginValidationKey' => self::LOGIN_VALIDATION_KEY,
            // additional credit card data as needed
        ];
        $this->requestMock->method('getParams')->willReturn($params);
        $this->requestMock->method('getParam')->with('loginValidationKey')->willReturn(self::LOGIN_VALIDATION_KEY);
        $this->customerSessionMock->method('getLoginValidationKey')->willReturn(self::LOGIN_VALIDATION_KEY);
        $this->baseAuthMock->method('toggleEnabled')->willReturn(false);
        $this->enhancedProfileMock->method('isLoggedIn')->willReturn(1);

        // NEW: Simulate an API error response.
        $errorResponse = json_decode('{"errors": [{"message": "system error"}]}');
        $this->enhancedProfileMock->method('apiCall')->willReturn($errorResponse);

        $this->jsonFactoryMock->expects($this->once())
            ->method('setData')
            ->with($this->callback(function ($data) use ($errorResponse) {
                return isset($data['status']) && $data['status'] === SaveInfo::ERROR;
            }))
            ->willReturnSelf();

        $result = $this->saveInfoMock->execute();
        $this->assertSame($this->jsonFactoryMock, $result);
    }

    public function testExecuteException(): void
    {
        // NEW: Disable recaptcha.
        $this->recaptchaValidatorMock->method('isRecaptchaEnabled')->willReturn(false);

        // NEW: Configure valid request and session.
        $params = ['loginValidationKey' => self::LOGIN_VALIDATION_KEY];
        $this->requestMock->method('getParams')->willReturn($params);
        $this->requestMock->method('getParam')->with('loginValidationKey')->willReturn(self::LOGIN_VALIDATION_KEY);
        $this->customerSessionMock->method('getLoginValidationKey')->willReturn(self::LOGIN_VALIDATION_KEY);
        $this->baseAuthMock->method('toggleEnabled')->willReturn(false);
        $this->enhancedProfileMock->method('isLoggedIn')->willReturn(1);

        // NEW: Force an exception when preparing credit card tokens.
        $this->enhancedProfileMock->method('prepareCreditCardTokensJson')
            ->will($this->throwException(new \Exception('Test exception')));

        $this->jsonFactoryMock->expects($this->once())
            ->method('setData')
            ->with($this->callback(function ($data) {
                return isset($data['status']) && $data['status'] === SaveInfo::ERROR;
            }))
            ->willReturnSelf();

        $result = $this->saveInfoMock->execute();
        $this->assertSame($this->jsonFactoryMock, $result);
    }

    /**
     * Common test logic for api Call
     */
    public function apiCall()
    {
        $saveCreditCardResponse = '{
            "output": {
              "profile": {
                "userProfileId": "3933d6a8-fd00-4519-ad15-fbc17fe606ff"
              }
            }
        }';

        $prepareCerditCard = json_decode(self::PREPARE_CREDIT_CARD_DATA, true);
        $encryptionCreditCard = json_decode(self::ENCRYPTION_CREDIT_CARD_DATA);
        $saveCreditCardResponse = json_decode($saveCreditCardResponse);
        $encryptionCreditCardResponse = json_decode(self::ENCRYPTION_CREDIT_CARD_RESPONSE_DATA);
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn($prepareCerditCard);
        $this->enhancedProfileMock->expects($this->any())
            ->method('prepareCreditCardTokensJson')
            ->willReturn($encryptionCreditCard);
        $this->enhancedProfileMock->expects($this->any())
            ->method('getConfigValue')
            ->willReturn("www.google.com");
        $this->enhancedProfileMock->expects($this->any())
            ->method('apiCall')
            ->willReturn($encryptionCreditCardResponse);
        $this->enhancedProfileMock->expects($this->any())
            ->method('prepareAddCerditCardJson')
            ->willReturn(self::PREPARE_CREDIT_CARD_JSON_DATA);
        $this->enhancedProfileMock->expects($this->any())
            ->method('saveCreditCard')
            ->willReturn($saveCreditCardResponse);
        $this->enhancedProfileMock->expects($this->any())
            ->method('setProfileSession')
            ->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
    }

    /**
     * Test execute with empty credit card tokens jsonWith toggle on
     *
     * @return void
     */
    public function testExecuteWithEmptyCreditCardTokensJsonWithToggleOn()
    {
        $this->baseAuthMock->expects($this->once())
            ->method('isLoggedIn')->willReturn(true);
        $headersMock = $this->getMockBuilder(\Laminas\Http\Headers::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock->expects($this->any())
            ->method('getHeaders')->willReturn($headersMock);

        $headersMock->expects($this->any())
            ->method('toString')
            ->willReturn('Your expected headers string');
        $this->requestMock->expects($this->any())
            ->method('getParam')->with('loginValidationKey')->willReturn(self::LOGIN_VALIDATION_KEY);
        $this->customerSessionMock->expects($this->any())
            ->method('getLoginValidationKey')->willReturn((string)self::LOGIN_VALIDATION_KEY);
        $this->emptyTokenResponse();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Test execute with empty credit card tokens json
     *
     * @return void
     */
    public function testExecuteWithEmptyCreditCardTokensJson()
    {
        $this->emptyTokenResponse();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Common Code for Empty Token Response
     */
    public function emptyTokenResponse()
    {
        $prepareCerditCard = json_decode(self::PREPARE_CREDIT_CARD_DATA, true);
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn($prepareCerditCard);
        $this->enhancedProfileMock->expects($this->any())
            ->method('prepareCreditCardTokensJson')
            ->willReturn('');
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
    }

    /**
     * Test execute with error API response with toggle on
     *
     * @return void
     */
    public function testExecuteWithErrorApiResponseWithToggleOn()
    {
        $this->baseAuthMock->expects($this->once())
            ->method('isLoggedIn')->willReturn(true);
        $headersMock = $this->getMockBuilder(\Laminas\Http\Headers::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock->expects($this->any())
            ->method('getHeaders')->willReturn($headersMock);

        $headersMock->expects($this->any())
            ->method('toString')
            ->willReturn('Your expected headers string');
        $this->requestMock->expects($this->any())
            ->method('getParam')->with('loginValidationKey')->willReturn(self::LOGIN_VALIDATION_KEY);
        $this->customerSessionMock->expects($this->any())
            ->method('getLoginValidationKey')->willReturn((string)self::LOGIN_VALIDATION_KEY);
        $this->apiErrorCall();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Test execute with error API response
     *
     * @return void
     */
    public function testExecuteWithErrorApiResponse()
    {
        $this->apiErrorCall();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Common code for api error
     */
    public function apiErrorCall()
    {
        $encryptionCreditCardError = '{
            "errors": [
                {
                    "message": "system error"
                }
            ]
        }';
        $encryptionCreditCardError = json_decode($encryptionCreditCardError);
        $prepareCerditCard = json_decode(self::PREPARE_CREDIT_CARD_DATA, true);
        $encryptionCreditCard = json_decode(self::ENCRYPTION_CREDIT_CARD_DATA);
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn($prepareCerditCard);
        $this->enhancedProfileMock->expects($this->any())
            ->method('prepareCreditCardTokensJson')
            ->willReturn($encryptionCreditCard);
        $this->enhancedProfileMock->expects($this->any())
            ->method('getConfigValue')
            ->willReturn("www.google.com");
        $this->enhancedProfileMock->expects($this->any())
            ->method('apiCall')
            ->willReturn($encryptionCreditCardError);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
    }

    /**
     * Test execute with empty API response with toggle on
     *
     * @return void
     */
    public function testExecuteWithEmptyApiResponseWithToggleOn()
    {
        $this->baseAuthMock->expects($this->once())
            ->method('isLoggedIn')->willReturn(true);
        $headersMock = $this->getMockBuilder(\Laminas\Http\Headers::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock->expects($this->any())
            ->method('getHeaders')->willReturn($headersMock);

        $headersMock->expects($this->any())
            ->method('toString')
            ->willReturn('Your expected headers string');
        $this->requestMock->expects($this->any())
            ->method('getParam')->with('loginValidationKey')->willReturn(self::LOGIN_VALIDATION_KEY);
        $this->customerSessionMock->expects($this->any())
            ->method('getLoginValidationKey')->willReturn((string)self::LOGIN_VALIDATION_KEY);
        $this->emptyResponse();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Test execute with empty API response
     *
     * @return void
     */
    public function testExecuteWithEmptyApiResponse()
    {
        $this->emptyResponse();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Common code for empty Api Response
     */
    public function emptyResponse()
    {
        $prepareCerditCard = json_decode(self::PREPARE_CREDIT_CARD_DATA, true);
        $encryptionCreditCard = json_decode(self::ENCRYPTION_CREDIT_CARD_DATA);
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn($prepareCerditCard);
        $this->enhancedProfileMock->expects($this->any())
            ->method('prepareCreditCardTokensJson')
            ->willReturn($encryptionCreditCard);
        $this->enhancedProfileMock->expects($this->any())
            ->method('getConfigValue')
            ->willReturn("www.google.com");
        $this->enhancedProfileMock->expects($this->any())
            ->method('apiCall')
            ->willReturn('');
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
    }

    /**
     * Test execute with error response in save credit card with Toggle On
     *
     * @return void
     */
    public function testExecuteWithErrorSaveCreditCardWithToggleOn()
    {
        $this->baseAuthMock->expects($this->once())
            ->method('isLoggedIn')->willReturn(true);
        $headersMock = $this->getMockBuilder(\Laminas\Http\Headers::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock->expects($this->any())
            ->method('getHeaders')->willReturn($headersMock);

        $headersMock->expects($this->any())
            ->method('toString')
            ->willReturn('Your expected headers string');
        $this->requestMock->expects($this->any())
            ->method('getParam')->with('loginValidationKey')->willReturn(self::LOGIN_VALIDATION_KEY);
        $this->customerSessionMock->expects($this->any())
            ->method('getLoginValidationKey')->willReturn((string)self::LOGIN_VALIDATION_KEY);
        $this->saveCard();
        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Test execute with error response in save credit card
     *
     * @return void
     */
    public function testExecuteWithErrorSaveCreditCard()
    {
        $this->saveCard();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Common Logic code for save card
     */
    public function saveCard()
    {
        $saveCreditCardErrorResponse = '{
            "errors": [
                {
                    "message": "system error"
                }
            ]
        }';
        $prepareCerditCard = json_decode(self::PREPARE_CREDIT_CARD_DATA, true);
        $encryptionCreditCard = json_decode(self::ENCRYPTION_CREDIT_CARD_DATA);
        $saveCreditCardErrorResponse = json_decode($saveCreditCardErrorResponse);
        $encryptionCreditCardResponse = json_decode(self::ENCRYPTION_CREDIT_CARD_RESPONSE_DATA);
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn($prepareCerditCard);
        $this->enhancedProfileMock->expects($this->any())
            ->method('prepareCreditCardTokensJson')
            ->willReturn($encryptionCreditCard);
        $this->enhancedProfileMock->expects($this->any())
            ->method('getConfigValue')
            ->willReturn("www.google.com");
        $this->enhancedProfileMock->expects($this->any())
            ->method('apiCall')
            ->willReturn($encryptionCreditCardResponse);
        $this->enhancedProfileMock->expects($this->any())
            ->method('prepareAddCerditCardJson')
            ->willReturn(self::PREPARE_CREDIT_CARD_JSON_DATA);
        $this->enhancedProfileMock->expects($this->any())
            ->method('saveCreditCard')
            ->willReturn($saveCreditCardErrorResponse);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
    }

    /**
     * Test execute with exception With Toggle On
     *
     * @return void
     */
    public function testExecuteWithExceptionWithToggleOn()
    {
        $this->baseAuthMock->expects($this->once())
            ->method('isLoggedIn')->willReturn(true);
        $this->requestMock->expects($this->any())
            ->method('getParam')->with('loginValidationKey')->willReturn(self::LOGIN_VALIDATION_KEY);
        $this->customerSessionMock->expects($this->any())
            ->method('getLoginValidationKey')->willReturn((string)self::LOGIN_VALIDATION_KEY);
        $prepareCerditCard = '{
            "saveStatus": false
        }';
        $headersMock = $this->getMockBuilder(\Laminas\Http\Headers::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock->expects($this->any())
            ->method('getHeaders')->willReturn($headersMock);

        $headersMock->expects($this->any())
            ->method('toString')
            ->willReturn('Your expected headers string');
        $prepareCerditCard = json_decode($prepareCerditCard, true);
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn($prepareCerditCard);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Test execute with exception
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $prepareCerditCard = '{
            "saveStatus": false
        }';
        $prepareCerditCard = json_decode($prepareCerditCard, true);
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn($prepareCerditCard);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    public function testRegionCodeLookupWithValidRegionId(): void
    {
        // Disable recaptcha validation
        $this->recaptchaValidatorMock->method('isRecaptchaEnabled')->willReturn(false);

        // Configure a successful login validation
        $this->baseAuthMock->method('isLoggedIn')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(function ($param) {
                if ($param === 'loginValidationKey') {
                    return self::LOGIN_VALIDATION_KEY;
                }
                return null;
            });
        $this->customerSessionMock->method('getLoginValidationKey')
            ->willReturn(self::LOGIN_VALIDATION_KEY);

        // Setup request params with empty stateOrProvinceCode but valid regionId
        $params = [
            'loginValidationKey' => self::LOGIN_VALIDATION_KEY,
            'regionId' => '169',
            'stateOrProvinceCode' => '',
            'requestId' => 'test123',
            'cardHolderName' => 'Test User',
            'nameOnCard' => 'Test User'
        ];

        $this->requestMock->method('getParams')->willReturn($params);
        $this->requestMock->method('getHeaders')->willReturn($this->headersMock);

        $regionMock = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCode'])
            ->onlyMethods(['load', 'getId'])
            ->getMock();

        // Set up the region mock with correct expectations
        $regionMock->expects($this->once())
            ->method('load')
            ->with('169')
            ->willReturnSelf();

        $regionMock->expects($this->once())
            ->method('getId')
            ->willReturn(true);

        $regionMock->expects($this->once())
            ->method('getCode')  // No parameters expected here
            ->willReturn('TX');

        // Configure regionFactory to use our specific mock for this test
        $this->regionFactoryMock = $this->getMockBuilder(RegionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->regionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($regionMock);

        // Mock the rest of the successful credit card flow
        $encryptionCreditCard = json_decode(self::ENCRYPTION_CREDIT_CARD_DATA);
        $encryptionCreditCardResponse = json_decode(self::ENCRYPTION_CREDIT_CARD_RESPONSE_DATA);
        $successResponse = json_decode('{"output":{"profile":{"userProfileId":"test-id"}}}');

        $this->enhancedProfileMock->method('prepareCreditCardTokensJson')
            ->willReturn($encryptionCreditCard);

        $this->enhancedProfileMock->method('getConfigValue')
            ->willReturn("https://api.example.com/tokens");

        $this->enhancedProfileMock->method('apiCall')
            ->willReturn($encryptionCreditCardResponse);

        $this->enhancedProfileMock->method('prepareAddCerditCardJson')
            ->willReturn(self::PREPARE_CREDIT_CARD_JSON_DATA);

        $this->enhancedProfileMock->method('saveCreditCard')
            ->willReturn($successResponse);

        $this->enhancedProfileMock->method('setProfileSession')
            ->willReturnSelf();

        // Re-create the saveInfoMock with our updated dependencies
        $this->saveInfoMock = $this->objectManager->getObject(
            SaveInfo::class,
            [
                'context' => $this->contextMock,
                'enhancedProfile' => $this->enhancedProfileMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'logger' => $this->loggerMock,
                'customerSession' => $this->customerSessionMock,
                'response' => $this->appResponseInterfaceMock,
                'authHelper' => $this->baseAuthMock,
                'recaptchaValidator' => $this->recaptchaValidatorMock,
                'regionFactory' => $this->regionFactoryMock
            ]
        );

        // Verify that the response is successful
        $this->jsonFactoryMock->expects($this->once())
            ->method('setData')
            ->with($this->callback(function ($data) {
                return isset($data['status']) && $data['status'] === SaveInfo::SUCCESS;
            }))
            ->willReturnSelf();

        $result = $this->saveInfoMock->execute();
        $this->assertSame($this->jsonFactoryMock, $result);
    }

    public function testRegionCodeLookupWithInvalidRegionId(): void
    {
        // Disable recaptcha validation
        $this->recaptchaValidatorMock->method('isRecaptchaEnabled')->willReturn(false);

        // Configure a successful login validation
        $this->baseAuthMock->method('isLoggedIn')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(function ($param) {
                if ($param === 'loginValidationKey') {
                    return self::LOGIN_VALIDATION_KEY;
                }
                return null;
            });
        $this->customerSessionMock->method('getLoginValidationKey')
            ->willReturn(self::LOGIN_VALIDATION_KEY);

        // Setup request params with empty stateOrProvinceCode but invalid regionId
        $params = [
            'loginValidationKey' => self::LOGIN_VALIDATION_KEY,
            'regionId' => '999', // Using an invalid region ID
            'stateOrProvinceCode' => '',
            'requestId' => 'test123',
            'cardHolderName' => 'Test User',
            'nameOnCard' => 'Test User'
        ];

        $this->requestMock->method('getParams')->willReturn($params);
        $this->requestMock->method('getHeaders')->willReturn($this->headersMock);

        // Create a separate region mock for this test
        $invalidRegionMock = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCode'])
            ->onlyMethods(['load', 'getId'])
            ->getMock();


        $invalidRegionMock->expects($this->once())
            ->method('load')
            ->with('999')
            ->willReturnSelf();

        $invalidRegionMock->expects($this->once())
            ->method('getId')
            ->willReturn(false); // Region not found

        $invalidRegionMock->expects($this->never())
            ->method('getCode'); // Should not be called

        // Configure regionFactory specifically for this test
        $this->regionFactoryMock = $this->getMockBuilder(RegionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->regionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($invalidRegionMock);

        // Mock API flow to ensure proper error handling
        $encryptionCreditCard = json_decode(self::ENCRYPTION_CREDIT_CARD_DATA);
        $errorResponse = json_decode('{"errors":[{"message":"Invalid credit card data"}]}');

        $this->enhancedProfileMock->method('prepareCreditCardTokensJson')
            ->willReturn($encryptionCreditCard);

        $this->enhancedProfileMock->method('getConfigValue')
            ->willReturn("https://api.example.com/tokens");

        $this->enhancedProfileMock->method('apiCall')
            ->willReturn($errorResponse);

        // Re-create the saveInfoMock with our updated dependencies
        $this->saveInfoMock = $this->objectManager->getObject(
            SaveInfo::class,
            [
                'context' => $this->contextMock,
                'enhancedProfile' => $this->enhancedProfileMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'logger' => $this->loggerMock,
                'customerSession' => $this->customerSessionMock,
                'response' => $this->appResponseInterfaceMock,
                'authHelper' => $this->baseAuthMock,
                'recaptchaValidator' => $this->recaptchaValidatorMock,
                'regionFactory' => $this->regionFactoryMock
            ]
        );

        // Expect an error response
        $this->jsonFactoryMock->expects($this->once())
            ->method('setData')
            ->with($this->callback(function ($data) {
                return isset($data['status']) && $data['status'] === SaveInfo::ERROR;
            }))
            ->willReturnSelf();

        $result = $this->saveInfoMock->execute();
        $this->assertSame($this->jsonFactoryMock, $result);
    }
}
