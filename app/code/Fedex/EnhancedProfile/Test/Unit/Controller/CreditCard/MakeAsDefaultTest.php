<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Test\Unit\Controller\CreditCard;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnhancedProfile\Controller\CreditCard\MakeAsDefault;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Unit test for set default credit card
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MakeAsDefaultTest extends TestCase
{
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
     * @var MakeAsDefault|MockObject
     */
    protected $makeAsDefaultMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
                            ->disableOriginalConstructor()
                            ->getMock();
            
        $this->enhancedProfileMock = $this->getMockBuilder(EnhancedProfile::class)
                            ->disableOriginalConstructor()
                            ->setMethods(
                                [
                                    'updateCreditCard',
                                    'getLoggedInProfileInfo',
                                    'getConfigValue',
                                    'setProfileSession',
                                    'apiCall',
                                    'makeArrayForCerditCardJson',
                                    'prepareUpdateCerditCardJson'
                                ]
                            )
                            ->getMock();
            
        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['create', 'setData'])
                                ->getMock();
            
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
                                ->disableOriginalConstructor()
                                ->getMockForAbstractClass();
            
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
                                ->disableOriginalConstructor()
                                ->getMockForAbstractClass();
            
        $this->objectManager = new ObjectManager($this);
        
        $this->makeAsDefaultMock = $this->objectManager->getObject(
            MakeAsDefault::class,
            [
                'context' => $this->contextMock,
                'enhancedProfile' => $this->enhancedProfileMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'request' => $this->requestMock,
                'logger' => $this->loggerMock
            ]
        );
    }
    
    /**
     * Test execute with card id
     *
     * @return void
     */
    public function testExecute()
    {
        $requestDataJson = '{
            "output": {
                "profile": {
                "creditCards": [
                    {
                    "profileCreditCardId": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
                    "cardHolderName": "Ravi Kant Kumar",
                    "maskedCreditCardNumber": "4111111111111111",
                    "creditCardLabel": "VISA",
                    "creditCardType": "VISA",
                    "expirationMonth": "10",
                    "expirationYear": "2029",
                    "billingAddress": {
                        "company": "Infogain",
                        "streetLines": [
                        "6146 Honey Bluff Parkway Calder"
                        ],
                        "city": "Plano",
                        "stateOrProvinceCode": "Texas",
                        "postalCode": "75024",
                        "countryCode": "United States",
                        "addressClassification": "HOME"
                    },
                    "primary": true
                    }
                ]
                }
            }
        }';

        $updateeCreditCard = '{
            "transactionId": "161a510f-b9d0-4c8a-b9a2-a0920dcb9d67",
            "output": {
             "message": "Success",
              "creditCard": {
                "profileCreditCardId": "01468b11-8cf6-49ec-8755-e79a1a0f870b",
                "creditCardLabel": "MASTERCARD_54444",
                "creditCardType": "MASTERCARD",
                "maskedCreditCardNumber": "54444",
                "creditCardToken": "12345678",
                "cardHolderName": "Test",
                "expirationMonth": "08",
                "tokenExpirationDate": "Sun Jun 26 00:00:00 GMT 2022",
                "expirationYear": "2024",
                "billingAddress": {
                  "company": {},
                  "streetLines": [
                    "Legacy"
                  ],
                  "city": "Plano",
                  "stateOrProvinceCode": "TX",
                  "postalCode": "75024",
                  "countryCode": "US"
                },
                "primary": false
              }
            }
        }';

        $creditCardInfo = '{
            "profileCreditCardId": "9837bd02-a0dd-4d00-a3e0-1de365134419",
            "creditCardLabel": "VISA_11111",
            "creditCardType": "VISA",
            "maskedCreditCardNumber": "11111",
            "cardHolderName": "Ravi Kant Kumar",
            "expirationMonth": "04",
            "tokenExpirationDate": "2023-10-17T11:16:35Z",
            "expirationYear": "2024",
            "billingAddress": {
                "company": {},
                "streetLines": [
                "7900 Legacy"
                ],
                "city": "Plano",
                "stateOrProvinceCode": "TX",
                "postalCode": "75024",
                "countryCode": "US"
            },
            "primary": true,
            "creditCardToken": "3411110A00165XNHSMLT34YN1000"
        }';

        $responseData = json_decode($requestDataJson);
        $updateeCreditCard = json_decode($updateeCreditCard);
        $this->requestMock->expects($this->any())->method('getParam')
                            ->willReturn("3fa85f64-5717-4562-b3fc-2c963f66afa6");
        $this->enhancedProfileMock->expects($this->once())->method('getLoggedInProfileInfo')->willReturn($responseData);
        $this->enhancedProfileMock->expects($this->once())->method('makeArrayForCerditCardJson')
                                    ->willReturn($creditCardInfo);
        $this->enhancedProfileMock->expects($this->once())->method('prepareUpdateCerditCardJson')
                                    ->willReturn($creditCardInfo);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('updateCreditCard')
                                    ->willReturn($updateeCreditCard);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertNotNull($this->makeAsDefaultMock->execute());
    }

    /**
     * Test execute with empty card id
     *
     * @return void
     */
    public function testExecuteWithEmptyCardId()
    {
        $this->requestMock->expects($this->any())->method('getParam')->willReturn(null);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        
        $this->assertEquals($this->jsonFactoryMock, $this->makeAsDefaultMock->execute());
    }

    /**
     * Test execute with empty card card info
     *
     * @return void
     */
    public function testExecuteWithEmptyCardInfo()
    {
        $requestDataJson = '{
            "output": {
                "profile": {
                }
            }
        }';
        $responseData = json_decode($requestDataJson);
        $this->requestMock->expects($this->any())->method('getParam')->willReturn("4111111111111111");
        $this->enhancedProfileMock->expects($this->any())->method('getLoggedInProfileInfo')->willReturn($responseData);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonFactoryMock, $this->makeAsDefaultMock->execute());
    }

    /**
     * Test execute with exception
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $requestDataJson = '{
            "output": {
                "profile": {
                }
            }
        }';
        $responseData = json_decode($requestDataJson);
        $this->requestMock->expects($this->any())->method('getParam')->willReturn("4111111111111111");
        $this->enhancedProfileMock->expects($this->any())->method('getLoggedInProfileInfo')->willReturn($responseData);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->jsonFactoryMock->expects($this->any())
                                ->method('create')
                                ->willThrowException($exception);

        $this->assertEquals(null, $this->makeAsDefaultMock->execute());
    }

    /**
     * Test execute with error response
     *
     * @return void
     */
    public function testExecuteWithError()
    {
        $requestDataJson = '{
            "output": {
                "profile": {
                "creditCards": [
                    {
                    "profileCreditCardId": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
                    "cardHolderName": "Ravi Kant Kumar",
                    "maskedCreditCardNumber": "4111111111111111",
                    "creditCardLabel": "VISA",
                    "creditCardType": "VISA",
                    "expirationMonth": "10",
                    "expirationYear": "2029",
                    "billingAddress": {
                        "company": "Infogain",
                        "streetLines": [
                        "6146 Honey Bluff Parkway Calder"
                        ],
                        "city": "Plano",
                        "stateOrProvinceCode": "Texas",
                        "postalCode": "75024",
                        "countryCode": "United States",
                        "addressClassification": "HOME"
                    },
                    "primary": true
                    }
                ]
                }
            }
        }';
        $updateeCreditCard = '{
            "errors": [
                {
                    "message": "system error"
                }
            ]
        }';
        $responseData = json_decode($requestDataJson);
        $updateeCreditCard = json_decode($updateeCreditCard);
        $this->requestMock->expects($this->any())->method('getParam')
                            ->willReturn("3fa85f64-5717-4562-b3fc-2c963f66afa6");
        $this->enhancedProfileMock->expects($this->any())->method('getLoggedInProfileInfo')->willReturn($responseData);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('updateCreditCard')
                                    ->willReturn($updateeCreditCard);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertNotNull($this->makeAsDefaultMock->execute());
    }
}
