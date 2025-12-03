<?php
declare(strict_types=1);

namespace Fedex\AccountValidation\Test\Unit\Controller\Adminhtml\Account;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Fedex\AccountValidation\Controller\Adminhtml\Account\Validateaccount;
use Fedex\AccountValidation\Model\AccountValidation;

class ValidateaccountTest extends TestCase
{
    private $jsonFactoryMock;
    private $loggerMock;
    private $requestMock;
    private $accountValidationMock;
    private Validateaccount $controller;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->jsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->accountValidationMock = $this->createMock(AccountValidation::class);

        $this->controller = $objectManager->getObject(
            Validateaccount::class,
            [
                'jsonFactory' => $this->jsonFactoryMock,
                'logger' => $this->loggerMock,
                'request' => $this->requestMock,
                'accountValidation' => $this->accountValidationMock
            ]
        );
    }

    public function testExecuteHandlesException(): void
    {
        $responseMock = $this->createMock(Json::class);
        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($responseMock);

        $this->requestMock->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(
                ['fxo-account-number'],
                ['fxo-discount-account-number'],
                ['fxo-shipping-account-number']
            )
            ->willReturnOnConsecutiveCalls('12345', '67890', '54321');

        $this->accountValidationMock->expects($this->once())
            ->method('validateAccount')
            ->with('12345', '67890', '54321')
            ->willThrowException(new \Exception('An error occurred while validating the account.'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Error during account validation: An error occurred while validating the account.', $this->anything());

        $responseMock->expects($this->once())
            ->method('setData')
            ->with(['status' => false, 'error' => 'An error occurred while validating the account.']);

        $result = $this->controller->execute();
        $this->assertEquals($responseMock, $result);
    }
}
