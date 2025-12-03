<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\Test\Unit\Controller\Cart;

use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\ProductBundle\Controller\Cart\Add;
use Fedex\ProductBundle\Model\Cart\AddBundleToCart;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AddTest extends TestCase
{
    private $requestMock;
    private $formKeyValidatorMock;
    private $loggerMock;
    private $resultJsonFactoryMock;
    private $resultJsonMock;
    private $addBundleToCartMock;
    private $urlMock;
    private $productBundleConfigMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->formKeyValidatorMock = $this->createMock(Validator::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->resultJsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->resultJsonMock = $this->createMock(Json::class);
        $this->addBundleToCartMock = $this->createMock(AddBundleToCart::class);
        $this->urlMock = $this->createMock(UrlInterface::class);
        $this->productBundleConfigMock = $this->createMock(ConfigInterface::class);

        $this->resultJsonFactoryMock->method('create')->willReturn($this->resultJsonMock);
    }

    private function createController(): Add
    {
        return new Add(
            $this->requestMock,
            $this->formKeyValidatorMock,
            $this->loggerMock,
            $this->resultJsonFactoryMock,
            $this->addBundleToCartMock,
            $this->urlMock,
            $this->productBundleConfigMock
        );
    }

    public function testExecuteWithToggleDisabled(): void
    {
        $this->productBundleConfigMock->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(false);
        $this->formKeyValidatorMock->method('validate')->willReturn(false);

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with([
                'success' => false,
                'message' => __('Product Bundle feature is disabled.')
            ])
            ->willReturnSelf();

        $controller = $this->createController();

        $result = $controller->execute();
        $this->assertSame($this->resultJsonMock, $result);
    }

    public function testExecuteWithInvalidFormKey(): void
    {
        $this->productBundleConfigMock->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(true);
        $this->formKeyValidatorMock->method('validate')->willReturn(false);

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with([
                'success' => false,
                'message' => __('Invalid form key.')
            ])
            ->willReturnSelf();

        $controller = $this->createController();

        $result = $controller->execute();
        $this->assertSame($this->resultJsonMock, $result);
    }

    public function testExecuteWithMissingParams(): void
    {
        $this->productBundleConfigMock->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(true);
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnMap([
                ['product', null, 0],
                ['bundle_option', [], []]
            ]);

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with([
                'success' => false,
                'message' => __('Product and bundle options are required.')
            ])
            ->willReturnSelf();

        $controller = $this->createController();

        $result = $controller->execute();
        $this->assertSame($this->resultJsonMock, $result);
    }

    public function testExecuteSuccess(): void
    {
        $this->productBundleConfigMock->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(true);
        $this->formKeyValidatorMock->method('validate')->willReturn(true);

        $this->requestMock->method('getParam')
            ->willReturnMap([
                ['product', null, 123],
                ['bundle_option', [], ['bundle' => 'option']],
                ['qty', 1, 2],
            ]);

        $this->addBundleToCartMock->expects($this->once())
            ->method('execute')
            ->with(123, ['bundle' => 'option'], 2);

        $this->urlMock->method('getUrl')
            ->with('checkout/cart')
            ->willReturn('https://example.com/checkout/cart');

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with([
                'success' => true,
                'message' => __('Bundle product added to cart.'),
                'backUrl' => 'https://example.com/checkout/cart'
            ])
            ->willReturnSelf();

        $controller = $this->createController();

        $result = $controller->execute();
        $this->assertSame($this->resultJsonMock, $result);
    }

    public function testExecuteWithLocalizedException(): void
    {
        $this->productBundleConfigMock->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(true);
        $this->formKeyValidatorMock->method('validate')->willReturn(true);

        $this->requestMock->method('getParam')
            ->willReturnMap([
                ['product', null, 123],
                ['bundle_option', [], ['bundle' => 'option']],
                ['qty', 1, 1],
            ]);

        $this->addBundleToCartMock->method('execute')
            ->willThrowException(new LocalizedException(__('Custom error')));

        $this->loggerMock->expects($this->once())->method('critical');

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with([
                'success' => false,
                'message' => 'Custom error'
            ])
            ->willReturnSelf();

        $controller = $this->createController();

        $result = $controller->execute();
        $this->assertSame($this->resultJsonMock, $result);
    }

    public function testExecuteWithGenericException(): void
    {
        $this->productBundleConfigMock->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(true);
        $this->formKeyValidatorMock->method('validate')->willReturn(true);

        $this->requestMock->method('getParam')
            ->willReturnMap([
                ['product', null, 123],
                ['bundle_option', [], ['bundle' => 'option']],
                ['qty', 1, 1],
            ]);

        $this->addBundleToCartMock->method('execute')
            ->willThrowException(new \Exception('Something went wrong'));

        $this->loggerMock->expects($this->once())->method('critical');

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with([
                'success' => false,
                'message' => __('Unable to add product to cart.')
            ])
            ->willReturnSelf();

        $controller = $this->createController();

        $result = $controller->execute();
        $this->assertSame($this->resultJsonMock, $result);
    }
}
