<?php
namespace Fedex\MarketplacePunchout\Test\Controller\Index;

use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Fedex\MarketplacePunchout\Controller\Index\Ajax;
use Fedex\MarketplacePunchout\Model\FclLogin;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AjaxTest extends TestCase
{
    /** @var Context|MockObject */
    private $contextMock;

    /** @var Cbb|MockObject */
    private $nonCustomizableProductModelMock;

    /** @var Session|MockObject */
    private $customerSessionMock;

    /** @var PageFactory|MockObject */
    private $resultPageFactoryMock;

    /** @var FclLogin|MockObject */
    private $fclLoginMock;

    /** @var SsoConfiguration|MockObject */
    private $ssoConfigurationMock;

    /** @var FormKey|MockObject */
    private $formKeyMock;

    /** @var LoggerInterface|MockObject */
    private $loggerMock;

    /** @var Ajax */
    private $ajaxController;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->nonCustomizableProductModelMock = $this->createMock(NonCustomizableProduct::class);
        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->addMethods(['getSellerConfigurationData', 'getProductConfigData'])
            ->getMock();        $this->resultPageFactoryMock = $this->createMock(PageFactory::class);
        $this->fclLoginMock = $this->createMock(FclLogin::class);
        $this->ssoConfigurationMock = $this->createMock(SsoConfiguration::class);
        $this->formKeyMock = $this->createMock(FormKey::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->ajaxController = new Ajax(
            $this->contextMock,
            $this->nonCustomizableProductModelMock,
            $this->customerSessionMock,
            $this->resultPageFactoryMock,
            $this->fclLoginMock,
            $this->ssoConfigurationMock,
            $this->formKeyMock,
            $this->loggerMock
        );
    }

    public function testExecuteWhenCbbEnabledAndCustomerLoggedIn()
    {
        $configurationData = ['config_key' => 'config_value'];
        $productConfigData = ['product_key' => 'product_value'];
        $homeUrl = 'http://example.com/';
        $formKey = 'form_key_value';

        $this->nonCustomizableProductModelMock->method('isMktCbbEnabled')->willReturn(true);
        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->customerSessionMock->method('getSellerConfigurationData')->willReturn($configurationData);
        $this->customerSessionMock->method('getProductConfigData')->willReturn($productConfigData);
        $this->ssoConfigurationMock->method('getHomeUrl')->willReturn($homeUrl);
        $this->formKeyMock->method('getFormKey')->willReturn($formKey);

        $resultPageMock = $this->createMock(Page::class);
        $this->resultPageFactoryMock->method('create')->willReturn($resultPageMock);

        $expectedData = [
            'product_config_data' => $productConfigData,
            'configuration_data' => $configurationData,
            'request_url' => $homeUrl . Ajax::REQUEST_URL,
            'error_url' => $homeUrl,
            'form_key' => $formKey,
            'success' => true
        ];

        $this->fclLoginMock->expects($this->once())->method('setData')->with($expectedData);

        $result = $this->ajaxController->execute();

        $this->assertSame($resultPageMock, $result);
    }

    public function testExecuteWhenCbbDisabled()
    {
        $homeUrl = 'http://example.com/';

        $this->nonCustomizableProductModelMock->method('isMktCbbEnabled')->willReturn(false);
        $this->ssoConfigurationMock->method('getHomeUrl')->willReturn($homeUrl);

        $resultPageMock = $this->createMock(Page::class);
        $this->resultPageFactoryMock->method('create')->willReturn($resultPageMock);

        $expectedData = [
            'error_url' => $homeUrl,
            'success' => false
        ];

        $this->fclLoginMock->expects($this->once())->method('setData')->with($expectedData);

        $result = $this->ajaxController->execute();

        $this->assertSame($resultPageMock, $result);
    }

    public function testExecuteWhenExceptionThrown()
    {
        $homeUrl = 'http://example.com/';

        $this->nonCustomizableProductModelMock->method('isMktCbbEnabled')->willThrowException(new \Exception('Test exception'));
        $this->ssoConfigurationMock->method('getHomeUrl')->willReturn($homeUrl);

        $resultPageMock = $this->createMock(Page::class);
        $this->resultPageFactoryMock->method('create')->willReturn($resultPageMock);

        $expectedData = [
            'error_url' => $homeUrl,
            'success' => false
        ];

        $this->fclLoginMock->expects($this->once())->method('setData')->with($expectedData);
        $this->loggerMock->expects($this->once())->method('error')
            ->with($this->stringContains('Exception during Login/Register process from Marketplace Configurator'));

        $result = $this->ajaxController->execute();

        $this->assertSame($resultPageMock, $result);
    }
}
