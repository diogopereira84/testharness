<?php

namespace Fedex\PersonalAddressBook\Test\Unit\Controller\Index;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Fedex\PersonalAddressBook\Helper\Parties;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use PHPUnit\Framework\TestCase;
use Fedex\PersonalAddressBook\Controller\Index\DeletePersonalBookData;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Customer\Model\Session;

class DeletePersonalBookDataTest extends TestCase
{
    /** @var DeletePersonalBookData */
    private $deletePersonalBookData;

    /** @var MockObject|LoggerInterface */
    private $loggerMock;

    /** @var MockObject|RequestInterface */
    private $requestMock;

    /** @var MockObject|ToggleConfig */
    private $toggleConfigMock;

    /** @var MockObject|JsonFactory */
    private $jsonFactoryMock;

    /** @var MockObject|Session */
    private $sessionMock;

    /** @var MockObject|Parties */
    private $partiesHelperMock;

    protected ObjectManager $objectManager;

    protected function setUp(): void
    {
        //$this->objectManager = new ObjectManager($this);
        $this->partiesHelperMock = $this->createMock(Parties::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
        ->setMethods(['info'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->createMock(LoggerInterface::class);
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPostValue', 'json_decode', 'json_encode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->createMock(RequestInterface::class);
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultJsonMock = $this->createMock(Json::class);
        $this->sessionMock = $this->createMock(Session::class);
        $this->jsonFactoryMock->method('create')->willReturn($resultJsonMock);
        $this->deletePersonalBookData = new DeletePersonalBookData(
            $this->loggerMock,
            $this->requestMock,
            $this->toggleConfigMock,
            $this->jsonFactoryMock,
            $this->partiesHelperMock,
            $this->sessionMock
        );
    }

    public function testExecuteWithValidPostDataAndToggleEnabled()
    {
        $postData = [
            'contactId' => 123
        ];
        $this->requestMock->method('getPostValue')->willReturn($postData);
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->partiesHelperMock->expects($this->any())
            ->method('callDeletePartyFromAddressBookById')
            ->with($postData)
            ->willReturn(['success' => true]);
        $result = $this->deletePersonalBookData->execute();
        $this->assertNull($result);
    }
}
