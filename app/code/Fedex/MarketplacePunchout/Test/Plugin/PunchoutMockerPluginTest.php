<?php
declare(strict_types=1);

use Fedex\Cart\Model\Quote\Product\Add;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplacePunchout\Plugin\PunchoutMockerPlugin;
use Fedex\MarketplacePunchout\Controller\Index\Index as PunchoutController;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\MarketplaceProduct\Model\AddToCartContext;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\UrlInterface;

class PunchoutMockerPluginTest extends TestCase
{
    private PunchoutMockerPlugin $plugin;
    private $contextMock;
    private $scopeConfigMock;
    private $marketplaceConfigMock;
    private $formKeyMock;
    private $requestMock;
    private $redirectFactoryMock;
    private $urlBuilderMock;
    private $quoteProductAddMock;
    private $redirectMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(AddToCartContext::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->marketplaceConfigMock = $this->createMock(MarketplaceConfig::class);
        $this->formKeyMock = $this->createMock(FormKey::class);
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->redirectFactoryMock = $this->createMock(RedirectFactory::class);
        $this->urlBuilderMock = $this->createMock(UrlInterface::class);
        $this->quoteProductAddMock = $this->createMock(Add::class);
        $this->redirectMock = $this->createMock(Redirect::class);

        $this->contextMock->method('getRequestInterface')->willReturn($this->requestMock);
        $this->contextMock->method('getQuoteProductAdd')->willReturn($this->quoteProductAddMock);
        $this->contextMock->method('getRedirectFactory')->willReturn($this->redirectFactoryMock);

        $this->plugin = new PunchoutMockerPlugin(
            $this->contextMock,
            $this->scopeConfigMock,
            $this->marketplaceConfigMock,
            $this->formKeyMock
        );
    }

    public function testAroundExecuteWithMockerEnabledAndProductInSkusList(): void
    {
        $subjectMock = $this->createMock(PunchoutController::class);
        $proceedMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock();
        $proceedMock->expects($this->never())->method('__invoke');

        $this->scopeConfigMock->method('isSetFlag')->with(PunchoutMockerPlugin::XML_PATH_PUNCHOUT_MOCKER_ENABLED)->willReturn(true);
        $this->scopeConfigMock->method('getValue')->withConsecutive(
            [PunchoutMockerPlugin::XML_PATH_PUNCHOUT_MOCKER_SKUS],
            [PunchoutMockerPlugin::XML_PATH_PUNCHOUT_MOCK_PAYLOAD]
        )->willReturnOnConsecutiveCalls('test_sku', 'mock_payload');

        $this->requestMock->method('getParam')->withConsecutive(
            ['sku'],['offer_id'],['seller_sku'])->willReturn('test_sku', 2646, 'test_sku');

        $this->formKeyMock->method('getFormKey')->willReturn('form_key');

        $this->quoteProductAddMock->expects($this->once())->method('addItemToCart')->with([
            'sku' => 'test_sku',
            'qty' => 1,
            'isMarketplaceProduct' => true
        ]);

        $this->redirectFactoryMock->method('create')->willReturn($this->redirectMock);
        $this->redirectMock->expects($this->once())->method('setPath')->with('checkout/cart')->willReturnSelf();

        $result = $this->plugin->aroundExecute($subjectMock, $proceedMock);
        $this->assertSame($this->redirectMock, $result);
    }

    public function testAroundExecuteWithMockerDisabled(): void
    {
        $subjectMock = $this->createMock(PunchoutController::class);
        $proceedMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock();
        $proceedMock->expects($this->once())->method('__invoke')->willReturn('proceed_result');

        $this->scopeConfigMock->method('isSetFlag')->with(PunchoutMockerPlugin::XML_PATH_PUNCHOUT_MOCKER_ENABLED)->willReturn(false);

        $result = $this->plugin->aroundExecute($subjectMock, $proceedMock);
        $this->assertSame('proceed_result', $result);
    }

    public function testIsPunchoutMockerEnabled(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->with(PunchoutMockerPlugin::XML_PATH_PUNCHOUT_MOCKER_ENABLED)->willReturn(true);
        $this->assertTrue($this->plugin->isPunchoutMockerEnabled());
    }

    public function testIsProductInSkusList(): void
    {
        $this->scopeConfigMock->method('getValue')->with(PunchoutMockerPlugin::XML_PATH_PUNCHOUT_MOCKER_SKUS)->willReturn('sku1,sku2');
        $this->assertTrue($this->plugin->isProductInSkusList('sku1'));
        $this->assertFalse($this->plugin->isProductInSkusList('sku3'));
    }

    public function testIsProductInSkusListEmptyConfig(): void
    {
        $this->scopeConfigMock->method('getValue')->with(PunchoutMockerPlugin::XML_PATH_PUNCHOUT_MOCKER_SKUS)->willReturn(null);
        $this->assertFalse($this->plugin->isProductInSkusList('sku3'));
    }

    public function testGetMockPayload(): void
    {
        $this->scopeConfigMock->method('getValue')->with(PunchoutMockerPlugin::XML_PATH_PUNCHOUT_MOCK_PAYLOAD)->willReturn('mock_payload');
        $this->assertSame('mock_payload', $this->plugin->getMockPayload());
    }

    public function testGetB2bPrintProductsCategory(): void
    {
        $this->scopeConfigMock->method('getValue')->with(PunchoutMockerPlugin::XML_PATH_B2B_PRINT_PRODUCT_CATEGORY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)->willReturn('category');
        $this->assertSame('category', $this->plugin->getB2bPrintProductsCategory());
    }
}
