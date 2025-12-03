<?php

namespace Fedex\PersonalAddressBook\Test\Unit\Controller\Index;

use PHPUnit\Framework\TestCase;
use Fedex\PersonalAddressBook\Controller\Index\AddressBookPage;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Controller\Result\Json as ResultJson;

class AddressBookPageTest extends TestCase
{
    /** @var MockObject */
    private $context;

    /** @var MockObject */
    private $partiesHelper;

    /** @var MockObject */
    private $customerSession;

    /** @var MockObject */
    private $resultJsonFactory;

    /** @var MockObject */
    private $logger;

    /** @var MockObject */
    private $request;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->partiesHelper = $this->createMock(\Fedex\PersonalAddressBook\Helper\Parties::class);
        $this->customerSession = $this->getMockBuilder(Session::class)
                                     ->setMethods(['getPartiesList','setAddressBookPageSize'])
                                     ->disableOriginalConstructor()
                                     ->getMockForAbstractClass();
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPost'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    public function testExecuteSuccess()
    {
        $this->request->method('getPost')->willReturn(1);

        $partiesList = json_encode(['party1', 'party2', 'party3']);
        $this->customerSession->method('getPartiesList')->willReturn($partiesList);

        $resultJson = $this->createMock(ResultJson::class);
        $this->resultJsonFactory->method('create')->willReturn($resultJson);

        $controller = new AddressBookPage(
            $this->context,
            $this->partiesHelper,
            $this->customerSession,
            $this->resultJsonFactory,
            $this->logger,
            $this->request
        );

        $controller->execute();
    }

    public function testExecuteWithException()
    {
        $this->request->expects($this->any())
            ->method('getPost')
            ->willReturn('A');

        $this->customerSession->method('getPartiesList')
            ->willThrowException(new \Exception("Exception error"));

        $resultJson = $this->createMock(ResultJson::class);
        $this->resultJsonFactory->method('create')->willReturn($resultJson);

        $controller = new AddressBookPage(
            $this->context,
            $this->partiesHelper,
            $this->customerSession,
            $this->resultJsonFactory,
            $this->logger,
            $this->request
        );

        $controller->execute();
    }

}
