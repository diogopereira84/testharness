<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Test\Unit\Controller\CreditCard;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnhancedProfile\Controller\CreditCard\SaveInfo;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ResponseInterface;
use Fedex\Base\Helper\Auth;
use Magento\Framework\App\RequestInterface;

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
    protected $headersMock;
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
        "stateOrProvinceCode": "Texas",
        "postalCode": "75024",
        "countryCode": "US",
        "primary": true,
        "saveStatus": true,
        "isNickName": false
    }';

    public const PREPARE_CREDIT_CARD_SAVE_DATA = '{
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
        "stateOrProvinceCode": "Texas",
        "postalCode": "75024",
        "countryCode": "US",
        "primary": true,
        "saveStatus": false,
        "isNickName": false
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

    public const SAVE_CREDIT_CARD_RESPONSE = '{
        "output": {
          "profile": {
            "userProfileId": "3933d6a8-fd00-4519-ad15-fbc17fe606ff"
          }
        }
    }';

    public const ERROR_RESPONSE = '{
        "errors": [
            {
                "message": "system error"
            }
        ]
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
    protected Auth|MockObject $baseAuthMock;
    protected function setUp(): void
    {
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
                                            'prepareUpdateCerditCardJson',
                                            'updateCreditCard',
                                            'getLoggedInProfileInfo',
                                            'makeCreditCardHtml',
                                            'isLoggedIn'
                                        ]
                                    )
                                    ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['create', 'setData'])
                                ->getMock();
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
                'request' => $this->requestMock,
            ]
        );
    }

    /**
     * Test execute with Card ForLoggedIn with toggle on
     *
     * @return void
     */
    public function testExecuteWithToggleOnForLoggedIn()
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
     * Test execute with Card For Non Logged In with toggle on
     *
     * @return void
     */
    public function testExecuteWithToggleOnForNonLoggedIn()
    {

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
     * Test execute with Card
     *
     * @return void
     */
    public function testExecute()
    {
        $this->saveCard();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Common Code for Save Card
     */
    public function saveCard()
    {
        $this->headersMock->expects($this->any())
            ->method('toString')
            ->willReturn('Your expected headers string');
        $prepareCerditCard = json_decode(self::PREPARE_CREDIT_CARD_DATA, true);
        $encryptionCreditCard = json_decode(self::ENCRYPTION_CREDIT_CARD_DATA);
        $getLoggedInProfileInfo = json_decode(self::SAVE_CREDIT_CARD_RESPONSE);
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
                                    ->method('setProfileSession')
                                    ->willReturnSelf();
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('prepareUpdateCerditCardJson')
                                    ->willReturn(self::PREPARE_CREDIT_CARD_JSON_DATA);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('updateCreditCard')
                                    ->willReturn($getLoggedInProfileInfo);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('getLoggedInProfileInfo')
                                    ->willReturn($getLoggedInProfileInfo);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('makeCreditCardHtml')
                                    ->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
    }

    /**
     * Test execute without update Card
     *
     * @return void
     */
    public function testExecuteWithoutUpdateCreditCard()
    {
        $prepareCerditCard = json_decode(self::PREPARE_CREDIT_CARD_DATA, true);
        $encryptionCreditCard = json_decode(self::ENCRYPTION_CREDIT_CARD_DATA);
        $getLoggedInProfileInfo = json_decode(self::ERROR_RESPONSE);
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
                                    ->method('setProfileSession')
                                    ->willReturnSelf();
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('prepareUpdateCerditCardJson')
                                    ->willReturn(self::PREPARE_CREDIT_CARD_JSON_DATA);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('updateCreditCard')
                                    ->willReturn($getLoggedInProfileInfo);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('getLoggedInProfileInfo')
                                    ->willReturn($getLoggedInProfileInfo);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('makeCreditCardHtml')
                                    ->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Test execute with save Card With Toggle On
     *
     * @return void
     */
    public function testExecuteWithSaveWithToggleOn()
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
        $this->saveCardData();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Test execute with save Card
     *
     * @return void
     */
    public function testExecuteWithSave()
    {
        $this->saveCardData();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Common code for save Card
     */
    public function saveCardData()
    {
        $prepareCerditCard = json_decode(self::PREPARE_CREDIT_CARD_SAVE_DATA, true);
        $encryptionCreditCard = json_decode(self::ENCRYPTION_CREDIT_CARD_DATA);
        $getLoggedInProfileInfo = json_decode(self::SAVE_CREDIT_CARD_RESPONSE);
        $encryptionCreditCardResponse = json_decode(self::ENCRYPTION_CREDIT_CARD_RESPONSE_DATA);
        $this->requestMock->expects($this->any())
                            ->method('getParams')
                            ->willReturn($prepareCerditCard);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('prepareCreditCardTokensJson')
                                    ->willReturn($encryptionCreditCard);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('saveCreditCard')
                                    ->willReturn($getLoggedInProfileInfo);
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
                                    ->method('setProfileSession')
                                    ->willReturnSelf();
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('prepareUpdateCerditCardJson')
                                    ->willReturn(self::PREPARE_CREDIT_CARD_JSON_DATA);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('getLoggedInProfileInfo')
                                    ->willReturn($getLoggedInProfileInfo);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('makeCreditCardHtml')
                                    ->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
    }

    /**
     * Test execute without save update card
     *
     * @return void
     */
    public function testExecuteWithSaveWithoutUpdateCreditCard()
    {
        $prepareCerditCard = json_decode(self::PREPARE_CREDIT_CARD_SAVE_DATA, true);
        $encryptionCreditCard = json_decode(self::ENCRYPTION_CREDIT_CARD_DATA);
        $getLoggedInProfileInfo = json_decode(self::ERROR_RESPONSE);
        $encryptionCreditCardResponse = json_decode(self::ENCRYPTION_CREDIT_CARD_RESPONSE_DATA);
        $this->requestMock->expects($this->any())
                            ->method('getParams')
                            ->willReturn($prepareCerditCard);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('prepareCreditCardTokensJson')
                                    ->willReturn($encryptionCreditCard);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('saveCreditCard')
                                    ->willReturn($getLoggedInProfileInfo);
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
                                    ->method('setProfileSession')
                                    ->willReturnSelf();
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('prepareUpdateCerditCardJson')
                                    ->willReturn(self::PREPARE_CREDIT_CARD_JSON_DATA);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('getLoggedInProfileInfo')
                                    ->willReturn($getLoggedInProfileInfo);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('makeCreditCardHtml')
                                    ->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Test execute with empty credit card tokens json With Toggle On
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

        $this->emptyResponse();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Test execute with empty credit card tokens json
     *
     * @return void
     */
    public function testExecuteWithEmptyCreditCardTokensJson()
    {
        $this->emptyResponse();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Common code for empty response
     */
    public function emptyResponse()
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
     * Test execute with error API response With Toggle On
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
        $this->errorResponse();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Test execute with error API response
     *
     * @return void
     */
    public function testExecuteWithErrorApiResponse()
    {
        $this->errorResponse();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Common code for error response
     */
    public function errorResponse()
    {
        $encryptionCreditCardError = json_decode(self::ERROR_RESPONSE);
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
     * Test execute with empty API response With Toggle On
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
        $this->emptyApiResponse();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Test execute with empty API response
     *
     * @return void
     */
    public function testExecuteWithEmptyApiResponse()
    {
        $this->emptyApiResponse();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Common Code for Empty Response
     */
    public function emptyApiResponse()
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
     * Test execute with error response in save credit card With Toggle On
     *
     * @return void
     */
    public function testExecuteWithErrorSaveCreditCardToggleOn()
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
        $this->errorApiResponse();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Test execute with error response in save credit card
     *
     * @return void
     */
    public function testExecuteWithErrorSaveCreditCard()
    {
        $this->errorApiResponse();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Cmmon code for Error API Response
     */
    public function errorApiResponse()
    {
        $getLoggedInProfileInfo = json_decode(self::ERROR_RESPONSE);
        $prepareCerditCard = json_decode(self::PREPARE_CREDIT_CARD_DATA, true);
        $encryptionCreditCard = json_decode(self::ENCRYPTION_CREDIT_CARD_DATA);
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
                                    ->method('updateCreditCard')
                                    ->willReturn($getLoggedInProfileInfo);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
    }

    /**
     * Test execute with nick name With Toggle On
     *
     * @return void
     */
    public function testExecuteWithNickNameWithToggleOn()
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
        $this->nickName();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Test execute with nick name
     *
     * @return void
     */
    public function testExecuteWithNickName()
    {
        $this->nickName();

        $this->assertEquals($this->jsonFactoryMock, $this->saveInfoMock->execute());
    }

    /**
     * Common Code for Nick Name
     */
    public function nickName()
    {
        $prepareCerditCard = '{
            "profileCreditCardId": "80addce1-1d40-454e-ac98-8ec36196777b",
            "cardHolderName": "Ravi Kant Kumar",
            "maskedCreditCardNumber": "4111111111111111",
            "creditCardLabel": "VISA_11111",
            "creditCardType": "VISA",
            "maskedCreditCardNumber": "11111",
            "expirationMonth": "10",
            "expirationYear": "2029",
            "company": "Infogain",
            "streetLines": [
                "6146 Honey Bluff Parkway Calder"
                ],
            "city": "Plano",
            "stateOrProvinceCode": "Texas",
            "postalCode": "75024",
            "countryCode": "US",
            "primary": true,
            "saveStatus": true,
            "isNickName": true
        }';

        $getLoggedInProfileInfo = '{
            "output": {
              "profile": {
                "userProfileId": "3933d6a8-fd00-4519-ad15-fbc17fe606ff",
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

        $prepareCerditCard = json_decode($prepareCerditCard, true);
        $encryptionCreditCard = json_decode(self::ENCRYPTION_CREDIT_CARD_DATA);
        $getLoggedInProfileInfo = json_decode($getLoggedInProfileInfo);
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
                                    ->method('setProfileSession')
                                    ->willReturnSelf();
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('prepareUpdateCerditCardJson')
                                    ->willReturn(self::PREPARE_CREDIT_CARD_JSON_DATA);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('updateCreditCard')
                                    ->willReturn($getLoggedInProfileInfo);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('getLoggedInProfileInfo')
                                    ->willReturn($getLoggedInProfileInfo);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('makeCreditCardHtml')
                                    ->willReturnSelf();
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
}
