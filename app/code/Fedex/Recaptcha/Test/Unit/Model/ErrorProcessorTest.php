<?php
namespace Fedex\Recaptcha\Test\Unit\Model;

use Fedex\Recaptcha\Logger\RecaptchaLogger;
use Fedex\Recaptcha\Model\ErrorProcessor;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\ReCaptchaUi\Model\ErrorMessageConfigInterface;
use Magento\ReCaptchaValidationApi\Model\ValidationErrorMessagesProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ErrorProcessorTest extends TestCase
{
    protected $actionFlagMock;
    /**
     * @var (\Magento\Framework\Serialize\SerializerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $serializerMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & RecaptchaLogger)
     */
    protected $recaptchaLoggerMock;
    protected $errorMessageConfigMock;
    protected $validationErrorMessagesProviderMock;
    protected $getIsEnableForPocMock;
    protected $sessionManagerInterface;
    protected function setUp(): void
    {
        $this->actionFlagMock = $this->getMockBuilder(ActionFlag::class)
            ->setMethods(['set'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->setMethods(['serialize'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->recaptchaLoggerMock = $this->getMockBuilder(RecaptchaLogger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->errorMessageConfigMock = $this->getMockBuilder(ErrorMessageConfigInterface::class)
            ->setMethods(['getValidationFailureMessage', 'getTechnicalFailureMessage'])
            ->getMockForAbstractClass();

        $this->validationErrorMessagesProviderMock = $this->getMockBuilder(ValidationErrorMessagesProvider::class)
            ->setMethods(['getErrorMessage'])
            ->setConstructorArgs([
                ['score-threshold-not-met' => 'Score threshold not met.']
            ])
            ->getMock();

        $this->sessionManagerInterface = $this->createMock(SessionManagerInterface::class);

        $this->getIsEnableForPocMock = new ErrorProcessor(
            $this->actionFlagMock,
            $this->serializerMock,
            $this->recaptchaLoggerMock,
            $this->errorMessageConfigMock,
            $this->validationErrorMessagesProviderMock,
            $this->sessionManagerInterface
        );
    }

    public function testProcessError()
    {
        $expectedReturn = [
            'status' => 'recaptcha_error',
            'message' => 'reCAPTCHA verification failed.'
        ];

        $this->errorMessageConfigMock->expects($this->once())->method('getValidationFailureMessage')
            ->willReturn('reCAPTCHA verification failed.');
        $this->errorMessageConfigMock->expects($this->once())->method('getTechnicalFailureMessage')
            ->willReturn('Something went wrong with reCAPTCHA. Please contact the store owner.');

        $this->validationErrorMessagesProviderMock->expects($this->once())->method('getErrorMessage')
            ->with('score-threshold-not-met')->willReturn('Score threshold not met.');

        $this->actionFlagMock->expects($this->once())->method('set')
            ->with('', Action::FLAG_NO_DISPATCH, true)->willReturnSelf();

        $errorMessagesMock['score-threshold-not-met'] = 'Score threshold not met.';
        $sourceKeyMock = 'checkout_fedex_account';

        $return = $this->getIsEnableForPocMock->processError($errorMessagesMock, $sourceKeyMock);
        $this->assertIsArray($return);
        $this->assertEquals($expectedReturn, $return);
    }

    public function testProcessErrorInexistentError()
    {
        $expectedReturn = [
            'status' => 'recaptcha_error',
            'message' => 'Something went wrong with reCAPTCHA. Please contact the store owner.'
        ];

        $this->errorMessageConfigMock->expects($this->once())->method('getValidationFailureMessage')
            ->willReturn('reCAPTCHA verification failed.');
        $this->errorMessageConfigMock->expects($this->once())->method('getTechnicalFailureMessage')
            ->willReturn('Something went wrong with reCAPTCHA. Please contact the store owner.');

        $this->validationErrorMessagesProviderMock->expects($this->once())->method('getErrorMessage')
            ->with('inexistent-error')->willReturn('inexistent-error');

        $this->actionFlagMock->expects($this->once())->method('set')
            ->with('', Action::FLAG_NO_DISPATCH, true)->willReturnSelf();

        $errorMessagesMock['inexistent-error'] = 'inexistent-error';
        $sourceKeyMock = 'checkout_fedex_account';
        $return = $this->getIsEnableForPocMock->processError($errorMessagesMock, $sourceKeyMock);
        $this->assertIsArray($return);
        $this->assertEquals($expectedReturn, $return);
    }
}
