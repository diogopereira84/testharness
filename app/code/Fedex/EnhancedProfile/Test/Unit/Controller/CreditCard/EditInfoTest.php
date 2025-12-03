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
use Fedex\EnhancedProfile\Controller\CreditCard\EditInfo;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Unit test for get credit card info
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditInfoTest extends TestCase
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
     * @var EditInfo|MockObject
     */
    protected $editInfoMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
                            ->disableOriginalConstructor()
                            ->getMock();
            
        $this->enhancedProfileMock = $this->getMockBuilder(EnhancedProfile::class)
                                    ->disableOriginalConstructor()
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
        
        $this->editInfoMock = $this->objectManager->getObject(
            EditInfo::class,
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
                    "profileCreditCardId": "3fa85f64-5717-4562-b3fc-2c963f66afa61",
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
        $responseData = json_decode($requestDataJson);
        $this->requestMock->expects($this->any())->method('getParam')
                            ->willReturn("3fa85f64-5717-4562-b3fc-2c963f66afa61");
        $this->enhancedProfileMock->expects($this->any())->method('getLoggedInProfileInfo')->willReturn($responseData);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonFactoryMock, $this->editInfoMock->execute());
    }

    /**
     * Test execute with empty
     *
     * @return void
     */
    public function testExecuteWithEmptyResponse()
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
        $responseData = json_decode($requestDataJson);
        $this->requestMock->expects($this->any())->method('getParam')
                            ->willReturn("3fa85f64-5717-4562-b3fc-2c963f66afa62");
        $this->enhancedProfileMock->expects($this->any())->method('getLoggedInProfileInfo')->willReturn($responseData);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonFactoryMock, $this->editInfoMock->execute());
    }

    /**
     * Test execute with empty card id
     *
     * @return void
     */
    public function testExecuteWithEmptyCardId()
    {
        $this->requestMock->expects($this->any())->method('getParam')->willReturn('');
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals($this->jsonFactoryMock, $this->editInfoMock->execute());
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
        $this->requestMock->expects($this->any())->method('getParam')
                            ->willReturn("3fa85f64-5717-4562-b3fc-2c963f66afa6");
        $this->enhancedProfileMock->expects($this->any())->method('getLoggedInProfileInfo')->willReturn($responseData);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonFactoryMock, $this->editInfoMock->execute());
    }

    /**
     * Test execute with Exception
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
        $this->requestMock->expects($this->any())->method('getParam')
                            ->willReturn("3fa85f64-5717-4562-b3fc-2c963f66afa6");
        $this->enhancedProfileMock->expects($this->any())->method('getLoggedInProfileInfo')->willReturn($responseData);
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->jsonFactoryMock->expects($this->any())
                                ->method('create')
                                ->willThrowException($exception);

        $this->assertEquals(null, $this->editInfoMock->execute());
    }
}