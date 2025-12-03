<?php
declare(strict_types=1);

namespace Fedex\Recaptcha\Test\Unit\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Recaptcha\Logger\RecaptchaLogger;
use Fedex\Recaptcha\Model\ErrorProcessor;
use Fedex\Recaptcha\Model\Validator;
use Fedex\Recaptcha\Model\PrintfulRecaptcha;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\ReCaptchaUi\Model\CaptchaResponseResolverInterface;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\ValidationConfigResolverInterface;
use Magento\ReCaptchaValidation\Model\ReCaptchaFactory;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigExtensionInterface;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;
use Magento\ReCaptchaValidationApi\Model\ErrorMessagesProvider;
use PHPUnit\Framework\TestCase;
use ReCaptcha\ReCaptcha;
use ReCaptcha\Response;

class ValidatorTest extends TestCase
{
    private $captchaResponseResolver;
    private $validationConfigResolver;
    private $isCaptchaEnabled;
    private $requestInterface;
    private $errorProcessor;
    private $validationResultFactory;
    private $errorMessagesProvider;
    private $reCaptchaFactory;
    private $recaptchaLogger;
    private $scopeConfig;
    private $toggleConfig;
    private $sessionManagerInterface;
    private $printfulRecaptcha;
    private $validator;

    protected function setUp(): void
    {
        $this->captchaResponseResolver = $this->createMock(CaptchaResponseResolverInterface::class);
        $this->validationConfigResolver = $this->createMock(ValidationConfigResolverInterface::class);
        $this->isCaptchaEnabled = $this->createMock(IsCaptchaEnabledInterface::class);
        $this->requestInterface = $this->createMock(RequestInterface::class);
        $this->errorProcessor = $this->createMock(ErrorProcessor::class);
        $this->validationResultFactory = $this->createMock(ValidationResultFactory::class);
        $this->errorMessagesProvider = $this->createMock(ErrorMessagesProvider::class);
        $this->reCaptchaFactory = $this->createMock(ReCaptchaFactory::class);
        $this->recaptchaLogger = $this->createMock(RecaptchaLogger::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->sessionManagerInterface = $this->createMock(SessionManagerInterface::class);
        $this->printfulRecaptcha = $this->getMockBuilder(PrintfulRecaptcha::class)
            ->onlyMethods(['checkIfQuoteIsEligibleForPrintfulTransactionBlock', 'isPrintfulRecaptchaTransactionBlockThreshold'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new Validator(
            $this->captchaResponseResolver,
            $this->validationConfigResolver,
            $this->isCaptchaEnabled,
            $this->requestInterface,
            $this->errorProcessor,
            $this->validationResultFactory,
            $this->errorMessagesProvider,
            $this->reCaptchaFactory,
            $this->recaptchaLogger,
            $this->scopeConfig,
            $this->toggleConfig,
            $this->sessionManagerInterface,
            $this->printfulRecaptcha
        );
    }

    public function testIsRecaptchaEnabled()
    {
        $captchaFormName = 'test_form';
        $this->isCaptchaEnabled->method('isCaptchaEnabledFor')->with($captchaFormName)->willReturn(true);

        $this->assertTrue($this->validator->isRecaptchaEnabled($captchaFormName));
    }

    public function testIsRecaptchaEnabledFalse()
    {
        $captchaFormName = 'test_form';
        $this->isCaptchaEnabled->method('isCaptchaEnabledFor')->with($captchaFormName)->willReturn(false);

        $this->assertFalse($this->validator->isRecaptchaEnabled($captchaFormName));
    }

    public function testValidateRecaptcha()
    {
        $captchaFormName = 'test_form';
        $validationConfig = $this->createMock(ValidationConfigInterface::class);
        $this->validationConfigResolver->method('get')->with($captchaFormName)->willReturn($validationConfig);

        $reCaptchaResponse = 'response';
        $this->captchaResponseResolver->method('resolve')->with($this->requestInterface)->willReturn($reCaptchaResponse);

        $validationConfig = $this->createMock(ValidationConfigInterface::class);
        $validationConfig->method('getPrivateKey')->willReturn('private_key');
        $validationConfig->method('getRemoteIp')->willReturn('127.0.0.1');

        $reCaptcha = $this->createMock(ReCaptcha::class);
        $this->reCaptchaFactory->method('create')->willReturn($reCaptcha);

        $result = $this->createMock(Response::class);
        $result->method('isSuccess')->willReturn(true);
        $reCaptcha->method('verify')->willReturn($result);

        $validationResult = $this->createMock(ValidationResult::class);
        $validationResult->method('isValid')->willReturn(true);

        $this->validationResultFactory->method('create')->willReturn($validationResult);

        $this->assertTrue($this->validator->validateRecaptcha($captchaFormName));
    }

    public function testValidateRecaptchaWithErrors()
    {
        $captchaFormName = 'test_form';
        $validationConfig = $this->createMock(ValidationConfigInterface::class);
        $validationConfig->method('getPrivateKey')->willReturn('private_key');
        $validationConfig->method('getRemoteIp')->willReturn('127.0.0.1');
        $this->validationConfigResolver->method('get')->with($captchaFormName)->willReturn($validationConfig);

        $reCaptchaResponse = 'response';
        $this->captchaResponseResolver->method('resolve')->with($this->requestInterface)->willReturn($reCaptchaResponse);

        $validationResult = $this->createMock(ValidationResult::class);
        $validationResult->method('isValid')->willReturn(false);
        $validationResult->method('getErrors')->willReturn(['error1', 'error2']);

        $reCaptcha = $this->createMock(ReCaptcha::class);
        $this->reCaptchaFactory->method('create')->willReturn($reCaptcha);

        $result = $this->createMock(Response::class);
        $result->method('isSuccess')->willReturn(false);
        $result->method('getErrorCodes')->willReturn(['error1', 'error2']);
        $reCaptcha->method('verify')->willReturn($result);

        $this->validationResultFactory->method('create')->willReturn($validationResult);

        $this->errorProcessor->method('processError')->with(['error1', 'error2'], $captchaFormName)->willReturn(['processed_error']);

        $this->assertEquals(['processed_error'], $this->validator->validateRecaptcha($captchaFormName));
    }

    public function testValidateRecaptchaWithException()
    {
        $captchaFormName = 'test_form';
        $this->validationConfigResolver->method('get')->with($captchaFormName)->willReturn($this->createMock(ValidationConfigInterface::class));

        $this->captchaResponseResolver->method('resolve')->with($this->requestInterface)->willThrowException(new InputException(__('Error')));

        $this->recaptchaLogger->expects($this->once())->method('error');
        $this->errorProcessor->method('processError')->willReturn([]);

        $this->assertEquals([], $this->validator->validateRecaptcha($captchaFormName));
    }

    public function testIsValid()
    {
        $reCaptchaResponse = 'response';
        $captchaFormName = 'test_form';
        $validationConfig = $this->createMock(ValidationConfigInterface::class);
        $validationConfig->method('getPrivateKey')->willReturn('private_key');
        $validationConfig->method('getRemoteIp')->willReturn('127.0.0.1');
        $extensionAttributes = $this->getMockBuilder(ValidationConfigExtensionInterface::class)
            ->setMethods(['getScoreThreshold'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes->method('getScoreThreshold')->willReturn(0.5);
        $validationConfig->method('getExtensionAttributes')->willReturn($extensionAttributes);

        $reCaptcha = $this->createMock(ReCaptcha::class);
        $reCaptcha->method('setExpectedAction')->with($captchaFormName)->willReturn($reCaptcha);
        $this->reCaptchaFactory->method('create')->willReturn($reCaptcha);

        $result = $this->createMock(Response::class);
        $result->method('isSuccess')->willReturn(true);
        $reCaptcha->method('verify')->willReturn($result);

        $validationResult = $this->createMock(ValidationResult::class);
        $this->validationResultFactory->method('create')->willReturn($validationResult);

        $this->assertSame($validationResult, $this->validator->isValid($reCaptchaResponse, $validationConfig, $captchaFormName));
    }

    public function testIsValidEligibleForPrintful()
    {
        $this->printfulRecaptcha->method('checkIfQuoteIsEligibleForPrintfulTransactionBlock')->willReturn(true);
        $this->printfulRecaptcha->method('isPrintfulRecaptchaTransactionBlockThreshold')->willReturn(0.7);
        $this->resetMockConfiguration();

        $reCaptchaResponse = 'response';
        $captchaFormName = 'test_form';
        $validationConfig = $this->createMock(ValidationConfigInterface::class);
        $validationConfig->method('getPrivateKey')->willReturn('private_key');
        $validationConfig->method('getRemoteIp')->willReturn('127.0.0.1');
        $extensionAttributes = $this->getMockBuilder(ValidationConfigExtensionInterface::class)
            ->setMethods(['getScoreThreshold', 'setScoreThreshold'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes->method('getScoreThreshold')->willReturn(0.5);
        $validationConfig->method('getExtensionAttributes')->willReturn($extensionAttributes);

        $reCaptcha = $this->createMock(ReCaptcha::class);
        $this->reCaptchaFactory->method('create')->willReturn($reCaptcha);

        $result = $this->createMock(Response::class);
        $result->method('isSuccess')->willReturn(true);
        $reCaptcha->method('verify')->willReturn($result);

        $validationResult = $this->createMock(ValidationResult::class);
        $this->validationResultFactory->method('create')->willReturn($validationResult);

        $this->assertSame($validationResult, $this->validator->isValid($reCaptchaResponse, $validationConfig, $captchaFormName));
    }

    public function testIsValidWithErrors()
    {
        $reCaptchaResponse = 'response';
        $captchaFormName = 'test_form';
        $validationConfig = $this->createMock(ValidationConfigInterface::class);
        $validationConfig->method('getPrivateKey')->willReturn('private_key');
        $validationConfig->method('getRemoteIp')->willReturn('127.0.0.1');

        $reCaptcha = $this->createMock(ReCaptcha::class);
        $this->reCaptchaFactory->method('create')->willReturn($reCaptcha);

        $result = $this->createMock(Response::class);
        $result->method('isSuccess')->willReturn(false);
        $result->method('getErrorCodes')->willReturn(['error-code']);
        $reCaptcha->method('verify')->willReturn($result);

        $this->errorMessagesProvider->method('getErrorMessage')->with('error-code')->willReturn('Error message');

        $validationResult = $this->createMock(ValidationResult::class);
        $this->validationResultFactory->method('create')->with(['errors' => ['error-code' => 'Error message']])->willReturn($validationResult);

        $this->assertSame($validationResult, $this->validator->isValid($reCaptchaResponse, $validationConfig, $captchaFormName));
    }

    public function testLogRecaptchaResult()
    {
        $captchaFormName = 'test_form';
        $result = $this->createMock(Response::class);
        $result->method('isSuccess')->willReturn(true);
        $result->method('getScore')->willReturn(0.9);
        $result->method('getAction')->willReturn('action');
        $result->method('getHostname')->willReturn('hostname');
        $result->method('getChallengeTs')->willReturn('timestamp');
        $result->method('getErrorCodes')->willReturn([]);

        $this->scopeConfig->method('getValue')->with('recaptcha_frontend/type_recaptcha_v3/debug')->willReturn('test_form');

        $this->sessionManagerInterface->expects($this->once())->method('getSessionId')->willReturn('session_id');
        $this->recaptchaLogger->expects($this->once())->method('info')->with($this->stringContains('Recaptcha Result'));

        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('logRecaptchaResult');
        $method->setAccessible(true);
        $method->invokeArgs($this->validator, [$captchaFormName, $result, '127.0.0.1']);
    }

    public function testLogRecaptchaResultWithErrors()
    {
        $captchaFormName = 'test_form';
        $result = $this->createMock(Response::class);
        $result->method('isSuccess')->willReturn(false);
        $result->method('getErrorCodes')->willReturn(['error-code']);

        $this->scopeConfig->method('getValue')->with('recaptcha_frontend/type_recaptcha_v3/debug')->willReturn('test_form');

        $this->sessionManagerInterface->expects($this->once())->method('getSessionId')->willReturn('session_id');
        $this->recaptchaLogger->expects($this->once())->method('error')->with($this->stringContains('Recaptcha Result Error'));

        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('logRecaptchaResult');
        $method->setAccessible(true);
        $method->invokeArgs($this->validator, [$captchaFormName, $result, '127.0.0.1']);
    }

    public function testIsDebugModeEnabledWithEmptyDebugList()
    {
        $captchaFormName = 'test_form';
        $isSuccess = true;

        $this->scopeConfig->method('getValue')->willReturn(null);

        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('isDebugModeEnabled');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->validator, [$captchaFormName, $isSuccess]);

        $this->assertFalse($result);
    }

    public function testIsDebugModeEnabledWithDebugList()
    {
        $captchaFormName = 'test_form';
        $isSuccess = true;

        $this->scopeConfig->method('getValue')->willReturn('test_form');

        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('isDebugModeEnabled');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->validator, [$captchaFormName, $isSuccess]);

        $this->assertTrue($result);
    }

    public function testIsDebugModeEnabledWithBypassDebug()
    {
        $captchaFormName = 'checkout_order';
        $isSuccess = false;

        $this->scopeConfig->method('getValue')->willReturn(null);

        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('isDebugModeEnabled');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->validator, [$captchaFormName, $isSuccess]);

        $this->assertTrue($result);
    }

    public function testIsDebugModeDisabledWithoutBypassDebugAndFalse()
    {
        $captchaFormName = 'profile_cc';
        $isSuccess = false;

        $this->scopeConfig->method('getValue')->willReturn(null);

        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('isDebugModeEnabled');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->validator, [$captchaFormName, $isSuccess]);

        $this->assertFalse($result);
    }

    public function testIsDebugModeEnabledWithoutBypassDebugAndFalse()
    {
        $captchaFormName = 'profile_cc';
        $isSuccess = false;

        $this->scopeConfig->method('getValue')->with('recaptcha_frontend/type_recaptcha_v3/debug')->willReturn('test_form');

        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('isDebugModeEnabled');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->validator, [$captchaFormName, $isSuccess]);

        $this->assertFalse($result);
    }
    public function resetMockConfiguration()
    {
        $this->validator = new Validator(
            $this->captchaResponseResolver,
            $this->validationConfigResolver,
            $this->isCaptchaEnabled,
            $this->requestInterface,
            $this->errorProcessor,
            $this->validationResultFactory,
            $this->errorMessagesProvider,
            $this->reCaptchaFactory,
            $this->recaptchaLogger,
            $this->scopeConfig,
            $this->toggleConfig,
            $this->sessionManagerInterface,
            $this->printfulRecaptcha
        );
    }
}
