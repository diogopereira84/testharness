<?php
namespace Fedex\MarketplacePunchout\Test\Controller\Index;

use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Fedex\MarketplacePunchout\Controller\Index\Login;
use Fedex\MarketplacePunchout\Model\FclLogin;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoginTest extends TestCase
{
    /** @var Context|MockObject */
    private $contextMock;

    /** @var Cbb|MockObject */
    private $nonCustomizableProductModelMock;

    /** @var LoggerInterface|MockObject */
    private $loggerMock;

    /** @var Session|MockObject */
    private $customerSessionMock;

    /** @var SsoConfiguration|MockObject */
    private $ssoConfigurationMock;

    /** @var FclLogin|MockObject */
    private $fclLoginMock;

    /** @var Login */
    private $loginController;

    /** @var RedirectFactory|MockObject */
    private $resultRedirectFactoryMock;

    /** @var Http|MockObject */
    private $requestMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(Http::class);
        $this->contextMock = $this->createMock(Context::class);
        $this->nonCustomizableProductModelMock = $this->createMock(NonCustomizableProduct::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->addMethods(['setSellerConfigurationData', 'setProductConfigData'])
            ->getMock();

        $this->ssoConfigurationMock = $this->getMockBuilder(SsoConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHomeUrl', 'getGeneralConfig'])
            ->getMock();

        $this->fclLoginMock = $this->createMock(FclLogin::class);
        $this->resultRedirectFactoryMock = $this->createMock(RedirectFactory::class);

        $this->contextMock->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->method('getResultRedirectFactory')->willReturn($this->resultRedirectFactoryMock);

        $this->loginController = new Login(
            $this->contextMock,
            $this->nonCustomizableProductModelMock,
            $this->loggerMock,
            $this->customerSessionMock,
            $this->ssoConfigurationMock,
            $this->fclLoginMock
        );
    }

    public function testExecuteWhenCbbEnabledAndValidPostRequestAndCustomerNotLoggedIn()
    {
        $homeUrl = 'http://example.com/';
        $configurationData = 'configData';
        $productConfigData = 'productConfigData';
        $actionType = Login::LOGIN_ACTION;
        $formKey = 'some_form_key_value';

        $this->nonCustomizableProductModelMock->method('isMktCbbEnabled')->willReturn(true);

        $this->requestMock->method('isPost')->willReturn(true);
        $this->requestMock->method('getPost')->willReturn([
            'configuration_data' => $configurationData,
            'product_config_data' => $productConfigData,
            'action_type' => $actionType,
            'form_key' => $formKey
        ]);

        $this->requestMock->method('getPostValue')->willReturnCallback(function ($key) use ($configurationData, $productConfigData, $actionType, $formKey) {
            $values = [
                'configuration_data' => $configurationData,
                'product_config_data' => $productConfigData,
                'action_type' => $actionType,
                'form_key' => $formKey
            ];
            return $values[$key] ?? null;
        });

        $this->customerSessionMock->method('isLoggedIn')->willReturn(false);

        $this->ssoConfigurationMock->method('getGeneralConfig')->willReturnCallback(function ($key) use ($homeUrl) {
            $configValues = [
                'wlgn_login_page_url' => $homeUrl . 'login',
                'query_parameter' => 'return_url',
            ];
            return $configValues[$key] ?? null;
        });

        $this->ssoConfigurationMock->method('getHomeUrl')->willReturn($homeUrl);

        $timestamp = '1234567890';
        $this->fclLoginMock->method('getTimeStamp')->willReturn($timestamp);

        $expectedCurrentUrl = $homeUrl . Login::REDIRECT_URL . '?t=' . $timestamp;
        $encodedCurrentUrl = base64_encode($expectedCurrentUrl);

        $expectedLoginUrl = $homeUrl . 'login' . '?' . 'return_url' . '=' . $homeUrl . Login::AUTH_URL . $encodedCurrentUrl;

        $resultRedirectMock = $this->createMock(Redirect::class);
        $this->resultRedirectFactoryMock->method('create')->willReturn($resultRedirectMock);

        $resultRedirectMock->expects($this->exactly(2))->method('setUrl')
            ->withConsecutive(
                [$homeUrl],
                [$expectedLoginUrl]
            );

        $this->customerSessionMock->expects($this->once())->method('setSellerConfigurationData')->with($configurationData);
        $this->customerSessionMock->expects($this->once())->method('setProductConfigData')->with($productConfigData);

        $this->loggerMock->expects($this->never())->method('error');

        $result = $this->loginController->execute();

        $this->assertSame($resultRedirectMock, $result);
    }

    public function testExecuteWhenCbbEnabledAndValidPostRequestAndCustomerLoggedIn()
    {
        $homeUrl = 'http://example.com/';
        $configurationData = 'configData';
        $productConfigData = 'productConfigData';
        $actionType = Login::LOGIN_ACTION;

        $this->nonCustomizableProductModelMock->method('isMktCbbEnabled')->willReturn(true);

        $this->requestMock->method('isPost')->willReturn(true);
        $this->requestMock->method('getPost')->willReturn([
            'configuration_data' => $configurationData,
            'product_config_data' => $productConfigData,
            'action_type' => $actionType
        ]);

        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);

        $this->ssoConfigurationMock->method('getHomeUrl')->willReturn($homeUrl);

        $resultRedirectMock = $this->createMock(Redirect::class);
        $this->resultRedirectFactoryMock->method('create')->willReturn($resultRedirectMock);

        $resultRedirectMock->expects($this->once())->method('setUrl')->with($homeUrl);

        $this->customerSessionMock->expects($this->never())->method('setSellerConfigurationData');
        $this->customerSessionMock->expects($this->never())->method('setProductConfigData');

        $result = $this->loginController->execute();

        $this->assertSame($resultRedirectMock, $result);
    }

    public function testExecuteWhenCbbEnabledAndValidPostRequestAndCustomerNotLoggedInRegisterAction()
    {
        $homeUrl = 'http://example.com/';
        $configurationData = 'configData';
        $productConfigData = 'productConfigData';
        $actionType = Login::REGISTER_ACTION;
        $formKey = 'some_form_key_value';

        $this->nonCustomizableProductModelMock->method('isMktCbbEnabled')->willReturn(true);

        $this->requestMock->method('isPost')->willReturn(true);
        $this->requestMock->method('getPost')->willReturn([
            'configuration_data' => $configurationData,
            'product_config_data' => $productConfigData,
            'action_type' => $actionType,
            'form_key' => $formKey
        ]);

        $this->requestMock->method('getPostValue')->willReturnCallback(function ($key) use ($configurationData, $productConfigData, $actionType, $formKey) {
            $values = [
                'configuration_data' => $configurationData,
                'product_config_data' => $productConfigData,
                'action_type' => $actionType,
                'form_key' => $formKey
            ];
            return $values[$key] ?? null;
        });

        $this->customerSessionMock->method('isLoggedIn')->willReturn(false);

        $this->ssoConfigurationMock->method('getHomeUrl')->willReturn($homeUrl);

        $this->ssoConfigurationMock->method('getGeneralConfig')->willReturnCallback(function ($key) use ($homeUrl) {
            $configValues = [
                'register_url' => $homeUrl . 'register/',
                'register_url_param' => 'return_url',
            ];
            return $configValues[$key] ?? null;
        });

        $timestamp = '1234567890';
        $this->fclLoginMock->method('getTimeStamp')->willReturn($timestamp);

        $expectedCurrentUrl = $homeUrl . Login::REDIRECT_URL . '?t=' . $timestamp;
        $encodedCurrentUrl = base64_encode($expectedCurrentUrl);

        $expectedRegisterUrl = $homeUrl . 'register/' . 'return_url/' . Login::AUTH_URL . $encodedCurrentUrl;

        $resultRedirectMock = $this->createMock(Redirect::class);
        $this->resultRedirectFactoryMock->method('create')->willReturn($resultRedirectMock);

        $resultRedirectMock->expects($this->exactly(2))->method('setUrl')
            ->withConsecutive(
                [$homeUrl],
                [$expectedRegisterUrl]
            );

        $this->customerSessionMock->expects($this->once())->method('setSellerConfigurationData')->with($configurationData);
        $this->customerSessionMock->expects($this->once())->method('setProductConfigData')->with($productConfigData);

        $this->loggerMock->expects($this->never())->method('error');

        $result = $this->loginController->execute();

        $this->assertSame($resultRedirectMock, $result);
    }

    public function testExecuteWhenCbbDisabled()
    {
        $homeUrl = 'http://example.com/';

        $this->nonCustomizableProductModelMock->method('isMktCbbEnabled')->willReturn(false);

        $this->ssoConfigurationMock->method('getHomeUrl')->willReturn($homeUrl);

        $resultRedirectMock = $this->createMock(Redirect::class);
        $this->resultRedirectFactoryMock->method('create')->willReturn($resultRedirectMock);

        $resultRedirectMock->expects($this->once())->method('setUrl')->with($homeUrl);

        $result = $this->loginController->execute();

        $this->assertSame($resultRedirectMock, $result);
    }

    public function testExecuteWhenRequestIsNotPost()
    {
        $homeUrl = 'http://example.com/';

        $this->nonCustomizableProductModelMock->method('isMktCbbEnabled')->willReturn(true);

        $this->requestMock->method('isPost')->willReturn(false);

        $this->ssoConfigurationMock->method('getHomeUrl')->willReturn($homeUrl);

        $resultRedirectMock = $this->createMock(Redirect::class);
        $this->resultRedirectFactoryMock->method('create')->willReturn($resultRedirectMock);

        $resultRedirectMock->expects($this->once())->method('setUrl')->with($homeUrl);

        $result = $this->loginController->execute();

        $this->assertSame($resultRedirectMock, $result);
    }

    public function testExecuteWhenRequestIsInvalid()
    {
        $homeUrl = 'http://example.com/';

        $this->nonCustomizableProductModelMock->method('isMktCbbEnabled')->willReturn(true);

        $this->requestMock->method('isPost')->willReturn(true);
        $this->requestMock->method('getPost')->willReturn([]);
        $this->requestMock->method('getPostValue')->willReturn(null);

        $this->ssoConfigurationMock->method('getHomeUrl')->willReturn($homeUrl);

        $resultRedirectMock = $this->createMock(Redirect::class);
        $this->resultRedirectFactoryMock->method('create')->willReturn($resultRedirectMock);

        $resultRedirectMock->expects($this->once())->method('setUrl')->with($homeUrl);

        $result = $this->loginController->execute();

        $this->assertSame($resultRedirectMock, $result);
    }

    public function testExecuteWhenExceptionThrown()
    {
        $homeUrl = 'http://example.com/';

        $this->nonCustomizableProductModelMock->method('isMktCbbEnabled')->willThrowException(new \Exception('Test exception'));

        $this->ssoConfigurationMock->method('getHomeUrl')->willReturn($homeUrl);

        $resultRedirectMock = $this->createMock(Redirect::class);
        $this->resultRedirectFactoryMock->method('create')->willReturn($resultRedirectMock);

        $resultRedirectMock->expects($this->once())->method('setUrl')->with($homeUrl);

        $this->loggerMock->expects($this->once())->method('error')
            ->with($this->stringContains('Exception during Login/Register process from Marketplace Configurator'));

        $result = $this->loginController->execute();

        $this->assertSame($resultRedirectMock, $result);
    }
}
