<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 
namespace Fedex\CIDPSG\Test\Unit\Controller\Index;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Fedex\CIDPSG\Helper\PegaHelper;
use Psr\Log\LoggerInterface;
use Fedex\CIDPSG\Controller\Index\AccountRequestSubmit;

/**
 * Test class for AccountRequestSubmit Controller
 */
class AccountRequestSubmitTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $accountRequestSubmit;
    /**
     * @var PegaHelper $pegaHelperMock
     */
    protected $pegaHelperMock;
    
    /**
     * @var ResultFactory $resultFactoryMock
     */
    protected $resultFactoryMock;

    /**
     * @var RequestInterface $requestMock
     */
    protected $requestMock;

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

        $this->pegaHelperMock = $this->getMockBuilder(PegaHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPegaApiResponse'])
            ->getMock();
        
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPostValue'])
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->accountRequestSubmit = $this->objectManager->getObject(
            AccountRequestSubmit::class,
            [
                'requestInterface' => $this->requestMock,
                'pegaHelper' => $this->pegaHelperMock,
                'resultFactory' => $this->resultFactoryMock,
                'logger' => $this->loggerMock
            ]
        );

        $this->accountRequestSubmit->formData = [
            'cid_psg_country' => 'US',
            'legal_company_name' => 'test',
            'pre_acc_name' => 'test',
            'contact_fname' => 'test',
            'contact_lname' => 'test'
        ];

        $this->accountRequestSubmit->errorData = [
            "errors" => [
                [
                    "code" => "Service_Error",
                    "message" => "Service Not Available"
                ]
            ]
        ];
    }

    /**
     * Test method for Execute function of AccountRequestSubmit Controller
     *
     * @return void
     */
    public function testExecute()
    {
        $this->requestMock->expects($this->once())->method('getPostValue')
        ->willReturn($this->accountRequestSubmit->formData);
        $this->pegaHelperMock->expects($this->once())->method('getPegaApiResponse')
        ->with($this->accountRequestSubmit->formData)->willReturn($this->accountRequestSubmit->formData);
        $this->resultFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->resultFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        
        $this->assertNotNull($this->accountRequestSubmit->execute());
    }

    /**
     * Test method for Execute function of AccountRequestSubmit Controller with error response
     *
     * @return void
     */
    public function testExecuteWithErrorResponse()
    {
        $this->requestMock->expects($this->once())->method('getPostValue')
        ->willReturn($this->accountRequestSubmit->formData);
        $this->pegaHelperMock->expects($this->once())->method('getPegaApiResponse')
        ->with($this->accountRequestSubmit->formData)->willReturn($this->accountRequestSubmit->errorData);
        $this->resultFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->resultFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertNotNull($this->accountRequestSubmit->execute());
    }
}
