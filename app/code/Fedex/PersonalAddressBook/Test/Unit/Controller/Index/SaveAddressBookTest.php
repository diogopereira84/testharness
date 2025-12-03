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
use Fedex\PersonalAddressBook\Controller\Index\SaveAddressBook;
use PHPUnit\Framework\MockObject\MockObject;

class SaveAddressBookTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /** @var SaveAddressBook */
    private $saveAddressBook;

    /** @var MockObject|LoggerInterface */
    private $loggerMock;

    /** @var MockObject|RequestInterface */
    private $requestMock;

    /** @var MockObject|ToggleConfig */
    private $toggleConfigMock;

    /** @var MockObject|JsonFactory */
    private $jsonFactoryMock;

    /** @var MockObject|Parties */
    private $partiesHelperMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
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
        $this->jsonFactoryMock->method('create')->willReturn($resultJsonMock);
        $this->saveAddressBook = new SaveAddressBook(
            $this->loggerMock,
            $this->requestMock,
            $this->toggleConfigMock,
            $this->jsonFactoryMock,
            $this->partiesHelperMock
        );
    }

    public function testExecuteWithValidPostDataAndToggleEnabled()
    {
        $postData = [
            'contactId' => 123,
            'isSaveForEdit' => false
        ];
        $this->requestMock->method('getPostValue')->willReturn($postData);
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->partiesHelperMock->expects($this->any())
            ->method('callPostParties')
            ->with($postData)
            ->willReturn(['success' => true]);
        $result = $this->saveAddressBook->execute();
        $this->assertNull($result);
    }

    public function testExecuteWithValidPostDataAndSaveForEdit()
    {
        $postData = [
            'contactId' => 123,
            'isSaveForEdit' => true
        ];
        $this->requestMock->method('getPostValue')->willReturn($postData);
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->partiesHelperMock->expects($this->any())
            ->method('callPutParties')
            ->with($postData, 123)
            ->willReturn(['success' => true]);
        $result = $this->saveAddressBook->execute();
        $this->assertNull($result);
    }

    public function testExecuteWithToggleDisabled()
    {
        $postData = [
            'contactId' => 123,
            'isSaveForEdit' => false
        ];
        $this->requestMock->method('getPostValue')->willReturn($postData);
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(false);
        $result = $this->saveAddressBook->execute();
        $this->assertNull($result);
    }

    public function testExecuteWithException()
    {
        $postData = [
            'contactId' => 123,
            'isSaveForEdit' => false
        ];
        $this->requestMock->method('getPostValue')->willReturn($postData);
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->partiesHelperMock->method('callPostParties')->willThrowException(new \Exception('API error'));
        $resultJsonMock = $this->createMock(Json::class);
        $this->jsonFactoryMock->method('create')->willReturn($resultJsonMock);
        $result = $this->saveAddressBook->execute();
        $this->assertNull($result);
    }
}
