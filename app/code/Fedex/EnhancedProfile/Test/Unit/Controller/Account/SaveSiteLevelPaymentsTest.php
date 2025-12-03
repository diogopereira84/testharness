<?php

namespace Fedex\EnhancedProfile\Test\Unit\Controller\Account;

use Magento\Framework\Controller\Result\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnhancedProfile\ViewModel\CompanyPaymentData;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Fedex\EnhancedProfile\Controller\Account\SaveSiteLevelPayments;
use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\Company\Model\ResourceModel\AdditionalData\Collection as AdditionalDataCollection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Exception;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;


class SaveSiteLevelPaymentsTest extends TestCase
{
    protected $jsonFactoryMock;
    protected $enhancedProfileMock;
    protected $additionalDataFactoryMock;
    protected $additionalDataMock;
    protected $additionalDataCollectionMock;
    protected $requestMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $saveInfoMock;
    /** @var SaveSiteLevelPayments */
    private $controller;

    /** @var JsonFactory|MockObject */
    private $jsonFactory;

    /** @var EnhancedProfile|MockObject */
    private $enhancedProfile;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var CompanyPaymentData|MockObject */
    private $companyPaymentData;

    /** @var JsonSerializer|MockObject */
    private $jsonMock;


    public const PREPARE_CREDIT_CARD_DATA = '{
    "creditCardToggle": "1",
    "fedexAccountToggle": "1",
        "creditCardDataParams": {
            "profileCreditCardId": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
            "cardHolderName": "Ravi Kant Kumar",
            "maskedCreditCardNumber": "4111111111111111",
            "creditCardLabel": "VISA",
            "creditCardType": "VISA",
            "expirationMonth": "10",
            "expirationYear": "2029",
            "company": "Infogain",
            "streetLines": "6146 Honey Bluff Parkway Calder",
            "city": "Plano",
            "stateOrProvinceCode": "Texas",
            "postalCode": "75024",
            "countryCode": "US",
            "primary": true,
            "saveStatus": true,
            "isNickName": false
        }
    }';

    public const PREPARE_CREDIT_CARD_SAVE_DATA = '{
        "requestId": "1213331111111111",
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

    public const PREPARE_CREDIT_CARD_JSON_DATA = '{
        "creditCard": {
          "requestId": "1213331111111111",
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

    public const CC_FORM_DATA = [
        'cardHolderName' => 'John Doe',
        'maskedCreditCardNumber' => '*1111',
        'creditCardType' => 'Visa',
        'expirationMonth' => '11',
        'expirationYear' => '2025',
        'streetLines' => 'Plano || City',
        'stateOrProvinceCode' => 'TX',
        'city' => 'plano',
        'countryCode' => 'US',
        'postalCode' => '75024',
        'creditCardToken' => '4111110A001DUR0ZOTHQL3DH1111',
        'tokenExpirationDate' => '2026-01-17 12:00:00',
        'nonEditableCcPayment' => '1'
    ];

    protected function setUp(): void
    {
        $context = $this->createMock(Context::class);
        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setData'])
            ->getMock();
        $this->enhancedProfileMock = $this->createMock(EnhancedProfile::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->companyPaymentData = $this->createMock(CompanyPaymentData::class);
        $this->additionalDataFactoryMock = $this->getMockBuilder(AdditionalDataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->additionalDataMock = $this->getMockBuilder(AdditionalData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->additionalDataCollectionMock = $this
            ->getMockBuilder(AdditionalDataCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->jsonMock = $this
            ->getMockBuilder(JsonSerializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->saveInfoMock = $this->objectManager->getObject(
            SaveSiteLevelPayments::class,
            [
                'context' => $context,
                'jsonFactory' => $this->jsonFactoryMock,
                'enhancedProfile' => $this->enhancedProfileMock,
                'logger' => $this->logger,
                'companyPaymentData' => $this->companyPaymentData,
                'request' => $this->requestMock,
                'json' => $this->jsonMock
            ]
        );
    }


    /**
     * Test execute with Card
     *
     * @return void
     */
    public function testExecute()
    {
        $prepareCerditCard = json_decode(self::PREPARE_CREDIT_CARD_DATA, true);
        $encryptionCreditCard = json_decode(self::ENCRYPTION_CREDIT_CARD_DATA);
        $getLoggedInProfileInfo = json_decode(self::SAVE_CREDIT_CARD_RESPONSE);
        $encryptionCreditCardResponse = json_decode(self::ENCRYPTION_CREDIT_CARD_RESPONSE_DATA);
        $this->testSaveCreditDetailsForCompany();
        $this->testAddCreditCart();

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
        $this->jsonMock->expects($this->any())->method('serialize')->willReturnSelf();

        $this->assertNotNull($this->saveInfoMock->execute());
    }

    /**
     * Test execute testCreditCardDataParams
     *
     * @return void
     */
    public function testCreditCardDataParams()
    {
        $prepareCerditCard = json_decode(self::PREPARE_CREDIT_CARD_DATA, true);
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn($prepareCerditCard);
        $this->enhancedProfileMock->expects($this->any())
            ->method('prepareCreditCardTokensJson')
            ->willReturn('CreditCardDataParams');
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->testSaveCreditDetailsForCompany();
        $this->testAddCreditCart();

        $this->assertNotNull($this->saveInfoMock->execute());
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

        $this->testSaveCreditDetailsForCompany();
        $this->testAddCreditCart();

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

        $this->assertNotNull($this->saveInfoMock->execute());
    }

    /**
     * Test execute with empty credit card tokens json
     *
     * @return void
     */
    public function testExecuteWithEmptyCreditCardTokensJson()
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
        $this->testSaveCreditDetailsForCompany();
        $this->testAddCreditCart();

        $this->assertNotNull($this->saveInfoMock->execute());
    }

    /**
     * Test execute with error API response
     *
     * @return void
     */
    public function testExecuteWithErrorApiResponse()
    {
        $encryptionCreditCardError = json_decode(self::ERROR_RESPONSE);
        $prepareCerditCard = json_decode(self::PREPARE_CREDIT_CARD_DATA, true);
        $encryptionCreditCard = json_decode(self::ENCRYPTION_CREDIT_CARD_DATA);
        $this->testSaveCreditDetailsForCompany();
        $this->testAddCreditCart();
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

        $this->assertNotNull($this->saveInfoMock->execute());
    }

    /**
     * Test execute with empty API response
     *
     * @return void
     */
    public function testExecuteWithEmptyApiResponse()
    {
        $prepareCerditCard = json_decode(self::PREPARE_CREDIT_CARD_DATA, true);
        $encryptionCreditCard = json_decode(self::ENCRYPTION_CREDIT_CARD_DATA);
        $this->testSaveCreditDetailsForCompany();
        $this->testAddCreditCart();
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

        $this->assertNotNull($this->saveInfoMock->execute());
    }

    /**
     * Test execute with error response in save credit card
     *
     * @return void
     */
    public function testExecuteWithErrorSaveCreditCard()
    {
        $getLoggedInProfileInfo = json_decode(self::ERROR_RESPONSE);
        $prepareCerditCard = json_decode(self::PREPARE_CREDIT_CARD_DATA, true);
        $encryptionCreditCard = json_decode(self::ENCRYPTION_CREDIT_CARD_DATA);
        $encryptionCreditCardResponse = json_decode(self::ENCRYPTION_CREDIT_CARD_RESPONSE_DATA);
        $this->testSaveCreditDetailsForCompany();
        $this->testAddCreditCart();
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

        $this->assertNotNull($this->saveInfoMock->execute());
    }

    /**
     * Test execute with exception company toggle
     *
     * @return void
     */
    public function testExecuteToggleSave()
    {
        $prepareCerditCard = json_decode(self::PREPARE_CREDIT_CARD_DATA, true);
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn($prepareCerditCard);
        $this->testSaveCreditDetailsForCompany();
        $this->testAddCreditCart();
        $this->companyPaymentData->expects($this->any())->method('getCompanyDataById')
            ->willReturn($this->additionalDataMock);
        $this->additionalDataMock->expects($this->any())->method('setCompanyPaymentOptions')->willReturn(['test']);
        $this->additionalDataMock->expects($this->any())->method('save')->willReturnSelf();

        $this->assertNotNull($this->saveInfoMock->execute());
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

        $this->assertNotNull($this->saveInfoMock->execute());
    }

    public function testAddCreditCart()
    {
        $htmlData = "<div><span>Fedex Credit Card</span></div>";
        $this->testSaveCreditDetailsForCompany();
        $this->companyPaymentData->expects($this->any())
            ->method('makeCreditCardViewHtml')
            ->willReturn($htmlData);

        $this->assertNotNull($this->saveInfoMock->addCreditCard(self::CC_FORM_DATA));
    }

    public function testAddCreditCartError()
    {
        $this->testSaveCreditDetailsForCompanyException();

        $this->assertNotNull($this->saveInfoMock->addCreditCard(self::CC_FORM_DATA));
    }

    public function testSaveCreditDetailsForCompany()
    {
        $this->additionalDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('isEmpty')
            ->willReturn(false);

        $this->additionalDataMock->expects($this->any())
            ->method('setIsNonEditableCcPaymentMethod')
            ->willReturn(0);

        $this->additionalDataMock->expects($this->any())
            ->method('setCcToken')
            ->willReturnSelf();

        $this->additionalDataMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->companyPaymentData->expects($this->any())
            ->method('getCompanyDataById')
            ->willReturn($this->additionalDataMock);

        $jsonResult = $this->createMock(Json::class);
        $this->jsonFactoryMock->method('create')->willReturn($jsonResult);

        $this->assertNotNull($this->saveInfoMock->saveCreditDetailsforCompany(self::CC_FORM_DATA));
    }

    public function testSaveCreditDetailsForCompanyException()
    {
        $ccFormData = [];
        $exception = new Exception;
        $this->companyPaymentData->expects($this->any())
            ->method('getCompanyDataById')
            ->willThrowException($exception);

        $jsonResult = $this->createMock(Json::class);
        $this->jsonFactoryMock->method('create')->willReturn($jsonResult);

        $this->assertNotNull($this->saveInfoMock->saveCreditDetailsforCompany($ccFormData));
    }
}
