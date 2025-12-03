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
use Fedex\EnhancedProfile\Controller\CreditCard\Encryption;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit test for remove credit card
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EncryptionTest extends TestCase
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
     * @var Encryption|MockObject
     */
    protected $encryptionMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
                            ->disableOriginalConstructor()
                            ->getMock();
            
        $this->enhancedProfileMock = $this->getMockBuilder(EnhancedProfile::class)
                                    ->disableOriginalConstructor()
                                    ->setMethods(
                                        [
                                            'getConfigValue',
                                            'apiCall',
                                            'prepareCreditCardTokensJson'
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
        
        $this->encryptionMock = $this->objectManager->getObject(
            Encryption::class,
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
     * Test execute with valid API response
     *
     * @return void
     */
    public function testExecute()
    {
        $updateeCreditCard = '{
            "creditCardTokenRequest": {
                "requestId": "1213331111111111",
                "creditCard": {
                    "encryptedData": "jCUtsBfhXT6sydLGrBPG9vKds21V",
                    "nameOnCard": "Ravi Kant Kumar"
                }
            }
        }';
        $updateeCreditCard = json_decode($updateeCreditCard);
        $this->requestMock->expects($this->any())
                            ->method('getParams')
                            ->willReturn(["4111111111111112"]);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('getConfigValue')
                                    ->willReturn("1");
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('prepareCreditCardTokensJson')
                                    ->willReturn($updateeCreditCard);
        $this->enhancedProfileMock->expects($this->any())->method('apiCall')
                                    ->willReturn($updateeCreditCard);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonFactoryMock, $this->encryptionMock->execute());
    }

    /**
     * Test execute with error API response
     *
     * @return void
     */
    public function testExecuteWithErrorApiResponse()
    {
        $updateeCreditCard = '{
            "transactionId": "1213331111111111",
            "errors": [
                {
                    "code": "Invalid_Request",
                    "message": "Validation Error"
                }
            ]
        }';

        $updateeCreditCard = json_decode($updateeCreditCard);
        $this->requestMock->expects($this->any())
                            ->method('getParams')
                            ->willReturn(["4111111111111111"]);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('getConfigValue')
                                    ->willReturn("1");
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('prepareCreditCardTokensJson')
                                    ->willReturn($updateeCreditCard);
        $this->enhancedProfileMock->expects($this->any())->method('apiCall')
                                    ->willReturn($updateeCreditCard);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonFactoryMock, $this->encryptionMock->execute());
    }

    /**
     * Test execute without json
     *
     * @return void
     */
    public function testExecuteWithPostFiled()
    {
        $this->requestMock->expects($this->any())
                            ->method('getParams')
                            ->willReturn(["4111111111111111"]);
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('getConfigValue')
                                    ->willReturn("1");
        $this->enhancedProfileMock->expects($this->any())
                                    ->method('prepareCreditCardTokensJson')
                                    ->willReturn('');
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonFactoryMock, $this->encryptionMock->execute());
    }

    /**
     * Test execute without post fieldrequest
     *
     * @return void
     */
    public function testExecuteWithPostRequest()
    {
        $this->requestMock->expects($this->any())
                            ->method('getParams')
                            ->willReturn("");
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonFactoryMock, $this->encryptionMock->execute());
    }
}
