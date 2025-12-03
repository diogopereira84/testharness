<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Test\Unit\Controller\Adminhtml\CreditCard;

use Fedex\EnhancedProfile\Controller\Adminhtml\CreditCard\Encryption;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EncryptionTest extends TestCase
{
    /**
     * Credit card form submitted data
     */
    public const CC_DATA = [
        "encryptedData" => "SP3zBlzoLLT02aeaIwgoOa2NIDFK8uCavv13PKrq4pVFmt5ITk",
        "nameOnCard" => "Walmart",
        "streetLines" => "7900 Legacy Dr",
        "postalCode" => "75024",
        "city" => "Plano",
        "stateOrProvinceCode" => "TX",
        "countryCode" => "US"
    ];

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
                    'prepareCreditCardTokensJson',
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
                'logger' => $this->loggerMock,
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
        $updateCreditCard = '{
            "transactionId": "4c8d9aa2-19f7-4ec9-882e-f4c273e289b7",
            "output": {
              "creditCardToken": {
                "token": "4111110A001DUR0ZOTHQL3DH1111"
              }
            }
        }';
        $updateCreditCard = json_decode($updateCreditCard);
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn(self::CC_DATA);
        $this->enhancedProfileMock->expects($this->any())
            ->method('getConfigValue')
            ->willReturn("1");
        $this->enhancedProfileMock->expects($this->any())
            ->method('prepareCreditCardTokensJson')
            ->willReturn($updateCreditCard);
        $this->enhancedProfileMock->expects($this->any())->method('apiCall')
            ->willReturn($updateCreditCard);
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
        $updateCreditCard = '{
            "transactionId": "1213331111111111",
            "errors": [
                {
                    "code": "Invalid_Request",
                    "message": "Validation Error"
                }
            ]
        }';

        $updateCreditCard = json_decode($updateCreditCard);
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn(self::CC_DATA);
        $this->enhancedProfileMock->expects($this->any())
            ->method('getConfigValue')
            ->willReturn("1");
        $this->enhancedProfileMock->expects($this->any())
            ->method('prepareCreditCardTokensJson')
            ->willReturn($updateCreditCard);
        $this->enhancedProfileMock->expects($this->any())->method('apiCall')
            ->willReturn($updateCreditCard);
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
            ->willReturn(self::CC_DATA);
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

    /**
     * Test execute method with alerts
     */
    public function testExecuteWithAlerts()
    {
        $updateCreditCard = json_encode(
            [
                "transactionId" => '12344567890987654',
                "output" => [
                    "alerts" => [
                        0 => [
                            'message' => "Service Not Available",
                        ]
                    ]
                ]
            ]
        );

        $updateCreditCard = json_decode($updateCreditCard);
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn(self::CC_DATA);
        $this->enhancedProfileMock->expects($this->any())
            ->method('getConfigValue')
            ->willReturn("1");
        $this->enhancedProfileMock->expects($this->any())
            ->method('prepareCreditCardTokensJson')
            ->willReturn($updateCreditCard);
        $this->enhancedProfileMock->expects($this->any())->method('apiCall')
            ->willReturn($updateCreditCard);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonFactoryMock, $this->encryptionMock->execute());
    }

    /**
     * Test execute method with alerts
     */
    public function testExecuteWithEmptyResponse()
    {
        $updateCreditCard = '{
            "transactionId": "1233456789098"
        }';

        $updateCreditCard = json_decode($updateCreditCard);
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn(self::CC_DATA);
        $this->enhancedProfileMock->expects($this->any())
            ->method('getConfigValue')
            ->willReturn("1");
        $this->enhancedProfileMock->expects($this->any())
            ->method('prepareCreditCardTokensJson')
            ->willReturn($updateCreditCard);
        $this->enhancedProfileMock->expects($this->any())->method('apiCall')
            ->willReturn($updateCreditCard);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonFactoryMock, $this->encryptionMock->execute());
    }
}
