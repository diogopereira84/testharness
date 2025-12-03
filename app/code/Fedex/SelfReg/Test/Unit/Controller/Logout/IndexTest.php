<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Test\Unit\Controller\Logout;

use Fedex\SelfReg\Controller\Logout\Index;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class IndexTest extends TestCase
{
    protected $customerSession;
    /**
     * @var (\Magento\Framework\Stdlib\Cookie\CookieMetadataFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cookieMetadataFactory;
    protected $urlInterfaceMock;
    protected $redirectFactoryMock;
    protected $redirectMock;
    protected $loggerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $selfRegLogoutMock;
    protected $contextMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->setMethods(['getId', 'unsFclFdxLogin','logout', 'setLastCustomerId','getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieMetadataFactory = $this->getMockBuilder(CookieMetadataFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();


        $this->urlInterfaceMock = $this->getMockBuilder(UrlInterface::class)
            ->setMethods(['getUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->redirectFactoryMock = $this->getMockBuilder(RedirectFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Redirect::class)
            ->setMethods(['setUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->selfRegLogoutMock = $this->objectManager->getObject(
            Index::class,
            [
                'url' => $this->urlInterfaceMock,
                'resultRedirectFactory' => $this->redirectFactoryMock,
                'cookieMetadataFactory' => $this->cookieMetadataFactory,
                'customerSession' => $this->customerSession,
                'logger' => $this->loggerMock
            ]
        );

    }

    /**
     * TestCase for  execute
     */
    public function testExecute()
    {
        $this->customerSession->expects($this->any())->method('getId')->willReturn(23);
        $this->customerSession->expects($this->any())->method('logout')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setLastCustomerId')->willReturnSelf();
        $this->urlInterfaceMock->expects($this->any())
        ->method('getUrl')->willReturn('https://staging3.office.fedex.com/selfreg/landing/');
        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($this->redirectMock);
        $this->redirectMock->expects($this->any())->method('setUrl')->willReturnSelf();

        $result = $this->selfRegLogoutMock->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }

    public function testExecuteWithException()
    {
        $exception = new Exception();
        $this->customerSession->expects($this->any())->method('getId')->willReturn(23);
        $this->customerSession->expects($this->any())->method('logout')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('logout')
        ->willThrowException($exception);
        $this->customerSession->expects($this->any())->method('getCustomerId')->willReturn(23);
        $this->urlInterfaceMock->expects($this->any())
        ->method('getUrl')->willReturn('https://staging3.office.fedex.com/selfreg/landing/');
        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($this->redirectMock);
        $this->redirectMock->expects($this->any())->method('setUrl')->willReturnSelf();

        $result = $this->selfRegLogoutMock->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }
}
