<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 
namespace Fedex\CIDPSG\Test\Unit\Controller\Index;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CIDPSG\Controller\Index\AccountRequest;
use Magento\Framework\Controller\ResultFactory;
use Fedex\CIDPSG\Helper\AdminConfigHelper;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Test class for AccountRequest Controller
 */
class AccountRequestTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $accountRequest;
    /**
     * @var AdminConfigHelper $adminConfigHelperMock
     */
    protected $adminConfigHelperMock;
    
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

        $this->adminConfigHelperMock = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllStates'])
            ->getMock();
        
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPost'])
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->accountRequest = $this->objectManager->getObject(
            AccountRequest::class,
            [
                'requestInterface' => $this->requestMock,
                'adminConfigHelper' => $this->adminConfigHelperMock,
                'logger' => $this->loggerMock,
                'resultFactory' => $this->resultFactoryMock
            ]
        );

        $this->accountRequest->canadaStates = [
            ['label' => 'AB', 'title'=>'Alberta'],
            ['label' => 'MB', 'title'=>'Manitoba'],
            ['label' => 'NB', 'title'=>'New Brunswick']
        ];
    }

    /**
     * Test method for Execute function of AccountRequest Controller
     *
     * @return void
     */
    public function testExecute()
    {
        $this->requestMock->expects($this->any())->method('getPost')->with('country_code')->willReturn('CA');
        $this->adminConfigHelperMock->expects($this->any())->method('getAllStates')->with('CA')
        ->willReturn($this->accountRequest->canadaStates);
        $this->resultFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->resultFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertNotNull($this->accountRequest->execute());
    }
}
