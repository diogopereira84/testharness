<?php
namespace Fedex\MarketplacePunchout\Test\Controller\Index;

use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Fedex\MarketplacePunchout\Controller\Index\Punchout;
use Fedex\MarketplacePunchout\Model\Context as MarketplaceContext;
use Fedex\MarketplacePunchout\Model\Marketplace;
use Magento\Framework\App\Action\Context as ActionContext;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PunchoutTest extends TestCase
{
    /** @var JsonFactory|MockObject */
    private $resultJsonFactoryMock;

    /** @var Http|MockObject */
    private $requestMock;

    /** @var LoggerInterface|MockObject */
    private $loggerMock;

    /** @var MarketplaceContext|MockObject */
    private $marketplaceContextMock;

    /** @var Cbb|MockObject */
    private $nonCustomizableProductModelMock;

    /** @var Session|MockObject */
    private $customerSessionMock;

    /** @var Punchout */
    private $punchoutController;

    /** @var ActionContext|MockObject */
    private $actionContext;

    protected function setUp(): void
    {
        $this->resultJsonFactoryMock = $this->createMock(JsonFactory::class);

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setParam', 'isPost', 'getPost', 'getPostValue'])
            ->getMock();

        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->marketplaceContextMock = $this->createMock(MarketplaceContext::class);
        $this->nonCustomizableProductModelMock = $this->createMock(NonCustomizableProduct::class);
        $this->actionContext = $this->createMock(ActionContext::class);

        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->punchoutController = new Punchout(
            $this->actionContext,
            $this->resultJsonFactoryMock,
            $this->requestMock,
            $this->loggerMock,
            $this->marketplaceContextMock,
            $this->nonCustomizableProductModelMock,
            $this->customerSessionMock
        );
    }

    public function testExecuteWhenAllConditionsAreMet()
    {
        $configurationData = 'configData';
        $productConfigDataArray = [
            'sku' => 'test-sku',
            'offer_id' => 'test-offer-id',
            'seller_sku' => 'test-seller-sku',
        ];
        $productConfigData = json_encode($productConfigDataArray);
        $codeChallenge = 'test-code-challenger';

        $postData = [
            'configuration_data' => $configurationData,
            'product_config_data' => $productConfigData,
            'code_challenge' => $codeChallenge,
            'form_key' => 'form_key_value',
        ];

        $expectedResponse = ['success' => true];

        $this->nonCustomizableProductModelMock->method('isMktCbbEnabled')->willReturn(true);
        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);

        $this->requestMock->method('isPost')->willReturn(true);
        $this->requestMock->method('getPost')->willReturn($postData);

        $this->requestMock->method('getPostValue')->willReturnCallback(function ($key) use ($postData) {
            return $postData[$key] ?? null;
        });

        $this->requestMock->expects($this->exactly(3))
            ->method('setParam')
            ->withConsecutive(
                ['sku', 'test-sku'],
                ['offer_id', 'test-offer-id'],
                ['seller_sku', 'test-seller-sku']
            );

        $navitorMock = $this->createMock(Marketplace::class);

        $navitorMock->expects($this->once())
            ->method('punchout')
            ->with(
                'test-sku',
                false,
                [
                    'code_challenge' => $codeChallenge
                ]
            )
            ->willReturn($expectedResponse);

        $this->marketplaceContextMock->method('getMarketplace')->willReturn($navitorMock);

        $resultJsonMock = $this->createMock(Json::class);
        $this->resultJsonFactoryMock->method('create')->willReturn($resultJsonMock);

        $resultJsonMock->expects($this->once())
            ->method('setData')
            ->with($expectedResponse)
            ->willReturnSelf();

        $result = $this->punchoutController->execute();

        $this->assertSame($resultJsonMock, $result);
    }

    public function testExecuteWhenConditionsAreNotMet()
    {
        $this->nonCustomizableProductModelMock->method('isMktCbbEnabled')->willReturn(false);
        $this->customerSessionMock->method('isLoggedIn')->willReturn(false);

        $this->requestMock->method('isPost')->willReturn(false);
        $this->requestMock->method('getPost')->willReturn([]);

        $resultJsonMock = $this->createMock(Json::class);
        $this->resultJsonFactoryMock->method('create')->willReturn($resultJsonMock);

        $resultJsonMock->expects($this->once())
            ->method('setData')
            ->with([])
            ->willReturnSelf();

        $result = $this->punchoutController->execute();

        $this->assertSame($resultJsonMock, $result);
    }

    public function testExecuteWhenValidateRequestReturnsFalse()
    {
        $productConfigDataArray = [
            'sku' => 'test-sku',
            'offer_id' => 'test-offer-id',
            'seller_sku' => 'test-seller-sku',
        ];
        $productConfigData = json_encode($productConfigDataArray);
        $codeVerifier = 'test-code-verifier';
        $codeChallenge = 'test-code-challenger';

        $postData = [
            'product_config_data' => $productConfigData,
            'code_verifier' => $codeVerifier,
            'code_challenge' => $codeChallenge,
            'form_key' => 'form_key_value',
        ];

        $expectedResponse = [];

        $this->nonCustomizableProductModelMock->method('isMktCbbEnabled')->willReturn(true);
        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);

        $this->requestMock->method('isPost')->willReturn(true);
        $this->requestMock->method('getPost')->willReturn($postData);

        $this->requestMock->method('getPostValue')->willReturnCallback(function ($key) use ($postData) {
            return $postData[$key] ?? null;
        });

        $this->requestMock->expects($this->never())->method('setParam');

        $navitorMock = $this->createMock(Marketplace::class);

        $navitorMock->expects($this->never())->method('punchout');

        $this->marketplaceContextMock->method('getMarketplace')->willReturn($navitorMock);

        $resultJsonMock = $this->createMock(Json::class);
        $this->resultJsonFactoryMock->method('create')->willReturn($resultJsonMock);

        $resultJsonMock->expects($this->once())
            ->method('setData')
            ->with($expectedResponse)
            ->willReturnSelf();

        $this->loggerMock->expects($this->never())->method('error');

        $result = $this->punchoutController->execute();

        $this->assertSame($resultJsonMock, $result);
    }

    public function testExecuteWhenExceptionIsThrown()
    {
        $this->nonCustomizableProductModelMock->method('isMktCbbEnabled')->willThrowException(new \Exception('Test exception'));

        $resultJsonMock = $this->createMock(Json::class);
        $this->resultJsonFactoryMock->method('create')->willReturn($resultJsonMock);

        $resultJsonMock->expects($this->once())
            ->method('setData')
            ->with([])
            ->willReturnSelf();

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Exception during Login/Register process from Marketplace Configurator'));

        $result = $this->punchoutController->execute();

        $this->assertSame($resultJsonMock, $result);
    }
}
