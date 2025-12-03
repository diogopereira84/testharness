<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 
namespace Fedex\CIDPSG\Test\Unit\Controller\Index;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CIDPSG\Controller\Index\SendSupportEmail;
use Magento\Framework\Controller\ResultFactory;
use Fedex\CIDPSG\Helper\AdminConfigHelper;
use Fedex\CIDPSG\Helper\Email;
use Psr\Log\LoggerInterface;

/**
 * Test class for SendSupportEmail Controller
 */
class SendSupportEmailTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $sendSupportEmail;
    public const PEGA_API_REQUEST = 'pega_api_request';
    public const PEGA_API_RESPONSE = 'pega_api_response';

    /**
     * @var AdminConfigHelper $adminConfigHelperMock
     */
    protected $adminConfigHelperMock;
    
    /**
     * @var ResultFactory $resultFactoryMock
     */
    protected $resultFactoryMock;

    /**
     * @var Email $cidEmailHelper
     */
    protected $cidEmailHelper;

    /**
     * Set up method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setData'])
            ->getMock();

        $this->adminConfigHelperMock = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getPegaApiSupportEmail',
                'getPegaAccountCreateApiUrl',
                'setValue',
                'getValue',
                'clearValue',
                'getFromEmail'
            ])->getMock();
        
        $this->cidEmailHelper = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendEmail'])
            ->getMock();
        
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->sendSupportEmail = $this->objectManager->getObject(
            SendSupportEmail::class,
            [
                'adminConfigHelper' => $this->adminConfigHelperMock,
                'cidEmailHelper' => $this->cidEmailHelper,
                'resultFactory' => $this->resultFactoryMock,
                'logger' => $this->loggerMock
            ]
        );

        $this->sendSupportEmail->reqJson = '{
                "name": "test",
                "countryCode": "US",
                "contact":[{
                    "firstName":"test",
                    "lastName":" test"
                }]
            }';

        $this->sendSupportEmail->errorRespJson = '{
            "errors":{
                "code":"Service_Error",
                "message":"Service Not Available"
            }
        }';

        $this->sendSupportEmail->successRespJson = '{
            "status": "Success",
            "message": "Account Creation case created successfully AR-798372"
        }';

        $this->sendSupportEmail->failureData =
        '<div><h3>PEGA API EndPoint URL:</h3><p></p><h3>Request:</h3><p style=\\"word-break:break-all;\\">{
                \\"name\\": \\"test\\",
                \\"countryCode\\": \\"US\\",
                \\"contact\\":[{
                    \\"firstName\\":\\"test\\",
                    \\"lastName\\":\\" test\\"
                }]
            }</p><h3>Response:</h3><p style=\\"word-break:break-all;\\">{
            \\"errors\\":{
                \\"code\\":\\"Service_Error\\",
                \\"message\\":\\"Service Not Available\\"
            }
        }</p></div>';

        $this->sendSupportEmail->successData =
        '<div><h3>PEGA API EndPoint URL:</h3><p></p><h3>Request:</h3><p style=\\"word-break:break-all;\\">{
                \\"name\\": \\"test\\",
                \\"countryCode\\": \\"US\\",
                \\"contact\\":[{
                    \\"firstName\\":\\"test\\",
                    \\"lastName\\":\\" test\\"
                }]
            }</p><h3>Response:</h3><p style=\\"word-break:break-all;\\">{
            \\"status\\": \\"Success\\",
            \\"message\\": \\"Account Creation case created successfully AR-798372\\"
        }</p></div>';
    }

    /**
     * Test method for Execute function with Failure
     *
     * @return void
     */
    public function testExecuteWithFailure()
    {
        $emailSub = 'FXO Account Creation - test test - Email Routing - Backend not reachable';
        $emailData['fromEmailId'] = 'no-reply@fedex.com';
        $emailData['toEmailId'] = 'avneesh.maurya@infogain.com';
        $emailData['templateSubject'] = $emailSub;

        $this->adminConfigHelperMock->expects($this->any())->method('getPegaAccountCreateApiUrl')->willReturn('');

        $this->adminConfigHelperMock->expects($this->any())->method('getFromEmail')
        ->willReturn($emailData['fromEmailId']);

        $this->adminConfigHelperMock->expects($this->any())->method('getPegaApiSupportEmail')
        ->willReturn($emailData['toEmailId']);

        $this->adminConfigHelperMock->expects($this->exactly(2))->method('getValue')
        ->withConsecutive([self::PEGA_API_REQUEST], [self::PEGA_API_RESPONSE])
        ->willReturnOnConsecutiveCalls(
            $this->sendSupportEmail->reqJson,
            $this->sendSupportEmail->errorRespJson
        );

        $this->cidEmailHelper->expects($this->any())->method('sendEmail')->willReturn(false);
        $this->resultFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->resultFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertNotNull($this->sendSupportEmail->execute());
    }

    /**
     * Test method for Execute function with success
     *
     * @return void
     */
    public function testExecuteWithSuccess()
    {
        $emailSub = 'FXO Account Creation - test test - Email Routing - Backend not reachable';
        $emailData['fromEmailId'] = 'no-reply@fedex.com';
        $emailData['toEmailId'] = 'avneesh.maurya.osv@fedex.com';
        $emailData['templateSubject'] = $emailSub;

        $this->adminConfigHelperMock->expects($this->any())->method('getPegaAccountCreateApiUrl')
        ->willReturn('');

        $this->adminConfigHelperMock->expects($this->any())->method('getFromEmail')
        ->willReturn($emailData['fromEmailId']);

        $this->adminConfigHelperMock->expects($this->any())->method('getPegaApiSupportEmail')
        ->willReturn($emailData['toEmailId']);

        $this->adminConfigHelperMock->expects($this->exactly(2))->method('getValue')
        ->withConsecutive([self::PEGA_API_REQUEST], [self::PEGA_API_RESPONSE])
        ->willReturnOnConsecutiveCalls(
            $this->sendSupportEmail->reqJson,
            $this->sendSupportEmail->successRespJson
        );

        $this->cidEmailHelper->expects($this->any())->method('sendEmail')->willReturn(true);

        $this->resultFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->resultFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertNotNull($this->sendSupportEmail->execute());
    }
}
