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
use Fedex\EnhancedProfile\Controller\CreditCard\RemoveInfo;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Unit test for remove credit card
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RemoveInfoTest extends TestCase
{
    public const REQUEST_DATA_WITH_CARD_ID = '{
        "output": {
            "profile": {
            }
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
     * @var RemoveInfo|MockObject
     */
    protected $removeInfoMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
                            ->disableOriginalConstructor()
                            ->getMock();
            
        $this->enhancedProfileMock = $this->getMockBuilder(EnhancedProfile::class)
                                    ->disableOriginalConstructor()
                                    ->setMethods(
                                        [
                                            'getLoggedInProfileInfo',
                                            'updateCreditCard',
                                            'getConfigValue',
                                            'apiCall',
                                            'setProfileSession'
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
        
        $this->removeInfoMock = $this->objectManager->getObject(
            RemoveInfo::class,
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
            "output": {
                "message": "Success"
            }
        }';
        $responseData = json_decode($requestDataJson);
        $updateeCreditCard = json_decode($updateeCreditCard);
        $requestDataJsonWithoutCardId = json_decode(self::REQUEST_DATA_WITH_CARD_ID);
        $this->requestMock->expects($this->any())
                            ->method('getParam')
                            ->willReturn("3fa85f64-5717-4562-b3fc-2c963f66afa6");
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('updateCreditCard')
                                    ->willReturn($updateeCreditCard);
        $this->enhancedProfileMock->expects($this->any())
                                ->method('getLoggedInProfileInfo')
                                ->willReturnOnConsecutiveCalls($responseData, $requestDataJsonWithoutCardId);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('getConfigValue')
                                    ->willReturn("www.google.com");
        $this->enhancedProfileMock->expects($this->any())->method('apiCall')
                                    ->willReturn($updateeCreditCard);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('setProfileSession')
                                    ->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertNotNull($this->removeInfoMock->execute());
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
        $this->requestMock->expects($this->any())
                            ->method('getParam')
                            ->willReturn("3fa85f64-5717-4562-b3fc-2c963f66afa6");
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('updateCreditCard')
                                    ->willReturn($updateeCreditCard);
        $this->enhancedProfileMock->expects($this->any())
                                ->method('getLoggedInProfileInfo')
                                ->willReturn($responseData);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertNotNull($this->removeInfoMock->execute());
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
        
        $this->assertEquals($this->jsonFactoryMock, $this->removeInfoMock->execute());
    }

    /**
     * Test execute with empty card card info
     *
     * @return void
     */
    public function testExecuteWithEmptyCardInfo()
    {
        $responseData = json_decode(self::REQUEST_DATA_WITH_CARD_ID);
        $this->requestMock->expects($this->any())->method('getParam')->willReturn("4111111111111111");
        $this->enhancedProfileMock->expects($this->any())->method('getLoggedInProfileInfo')->willReturn($responseData);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonFactoryMock, $this->removeInfoMock->execute());
    }

    /**
     * Test execute with exception
     *
     * @return void
     */
    public function testExecuteWithEexception()
    {
        $responseData = json_decode(self::REQUEST_DATA_WITH_CARD_ID);
        $this->requestMock->expects($this->any())->method('getParam')->willReturn("4111111111111111");
        $this->enhancedProfileMock->expects($this->any())->method('getLoggedInProfileInfo')->willReturn($responseData);
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->jsonFactoryMock->expects($this->any())
                                ->method('create')
                                ->willThrowException($exception);

        $this->assertEquals(null, $this->removeInfoMock->execute());
    }
}
