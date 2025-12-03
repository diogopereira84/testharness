<?php

declare(strict_types=1);

namespace Fedex\ProductBundle\Test\Unit\Controller\Cart;

use Fedex\ProductBundle\Controller\Cart\AddFromAls;
use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\ProductBundle\Model\Cart\AddBundleToCart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Registry;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\View\Result\Page;

class AddFromAlsTest extends TestCase
{
    private $context;
    private $request;
    private $pageFactory;
    private $addBundleToCart;
    private $logger;
    private $coreRegistry;
    private $productRepository;
    private $productBundleConfig;
    private $messageManager;
    private $redirect;
    private $controller;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->request = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->context->method('getRequest')->willReturn($this->request);
        $this->pageFactory = $this->createMock(PageFactory::class);
        $this->addBundleToCart = $this->createMock(AddBundleToCart::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->coreRegistry = $this->createMock(Registry::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->productBundleConfig = $this->createMock(ConfigInterface::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->redirect = $this->createMock(RedirectInterface::class);

        $this->context->method('getMessageManager')->willReturn($this->messageManager);
        $this->context->method('getRedirect')->willReturn($this->redirect);

        $this->controller = new AddFromAls(
            $this->context,
            $this->pageFactory,
            $this->addBundleToCart,
            $this->logger,
            $this->coreRegistry,
            $this->productRepository,
            $this->productBundleConfig
        );
    }

    public function testExecuteFeatureToggleDisabled()
    {
        $this->productBundleConfig->method('isTigerE468338ToggleEnabled')->willReturn(false);
        $this->redirect->method('getRefererUrl')->willReturn('referer-url');

        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with(__('Bundle Product feature is disabled.'));

        // Mock ResponseInterface and override getResponse()
        $responseMock = $this->createMock(\Magento\Framework\App\ResponseInterface::class);
        $controllerMock = $this->getMockBuilder(AddFromAls::class)
            ->setConstructorArgs([
                $this->context,
                $this->pageFactory,
                $this->addBundleToCart,
                $this->logger,
                $this->coreRegistry,
                $this->productRepository,
                $this->productBundleConfig
            ])
            ->onlyMethods(['getResponse'])
            ->getMock();
        $controllerMock->method('getResponse')->willReturn($responseMock);

        $result = $controllerMock->execute();
        $this->assertInstanceOf(\Magento\Framework\App\ResponseInterface::class, $result);
    }

    public function testExecuteFeatureToggleEnabledSuccess()
    {
        $this->productBundleConfig->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $page = $this->createMock(Page::class);
        $this->pageFactory->method('create')->willReturn($page);
        $this->coreRegistry->method('registry')->willReturn(null);
        $this->controller = $this->getMockBuilder(AddFromAls::class)
            ->setConstructorArgs([
                $this->context,
                $this->pageFactory,
                $this->addBundleToCart,
                $this->logger,
                $this->coreRegistry,
                $this->productRepository,
                $this->productBundleConfig
            ])
            ->onlyMethods(['getProduct'])
            ->getMock();
        $this->controller->expects($this->once())->method('getProduct');
        $result = $this->controller->execute();
        $this->assertSame($page, $result);
    }

    public function testExecuteThrowsException()
    {
        $this->productBundleConfig->method('isTigerE468338ToggleEnabled')->willReturn(true);
        // Mock ResponseInterface and override getResponse()
        $responseMock = $this->createMock(\Magento\Framework\App\ResponseInterface::class);
        $controllerMock = $this->getMockBuilder(AddFromAls::class)
            ->setConstructorArgs([
                $this->context,
                $this->pageFactory,
                $this->addBundleToCart,
                $this->logger,
                $this->coreRegistry,
                $this->productRepository,
                $this->productBundleConfig
            ])
            ->onlyMethods(['getProduct', 'getResponse'])
            ->getMock();
        $controllerMock->method('getProduct')->willThrowException(new \Exception('error'));
        $this->redirect->method('getRefererUrl')->willReturn('referer-url');
        $controllerMock->method('getResponse')->willReturn($responseMock);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error adding bundle product to cart: error'), $this->arrayHasKey('exception'));
        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with(__('The requested product is not a bundle product.'));

        $result = $controllerMock->execute();
        $this->assertInstanceOf(\Magento\Framework\App\ResponseInterface::class, $result);
    }

    public function testGetProductReturnsProductIfNotInRegistry()
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getTypeId')->willReturn('bundle');

        $this->request->expects($this->atMost(2))
            ->method('getParam')
            ->with('sku')
            ->willReturn('test-sku');

        $this->productRepository->expects($this->once())
            ->method('get')
            ->with('test-sku')
            ->willReturn($product);

        $this->coreRegistry->expects($this->exactly(2))
            ->method('register')
            ->withConsecutive(
                ['product', $product],
                ['current_product', $product]
            );
        $this->coreRegistry->expects($this->exactly(2))
            ->method('registry')
            ->with('product')
            ->willReturnOnConsecutiveCalls(null, $product);

        $result = $this->controller->getProduct();
        $this->assertSame($product, $result);
    }

    public function testGetProductReturnsProductIfNotInRegistryWithWrongTypeId()
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getTypeId')->willReturn('simple');

        $this->request->expects($this->atMost(2))
            ->method('getParam')
            ->with('sku')
            ->willReturn('test-sku');

        $this->productRepository->expects($this->once())
            ->method('get')
            ->with('test-sku')
            ->willReturn($product);

        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('The requested product is not a bundle product.');

        $result = $this->controller->getProduct();
        $this->assertNull($result);
    }

    public function testGetProductReturnsProductIfInRegistry()
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $this->coreRegistry->method('registry')
            ->with('product')
            ->willReturn($product);
        $result = $this->controller->getProduct();
        $this->assertSame($product, $result);
    }

    public function testGetProductReturnsNullIfNoSku()
    {
        $this->coreRegistry->method('registry')->with('product')->willReturn(null);
        $controller = $this->getMockBuilder(AddFromAls::class)
            ->setConstructorArgs([
                $this->context,
                $this->pageFactory,
                $this->addBundleToCart,
                $this->logger,
                $this->coreRegistry,
                $this->productRepository,
                $this->productBundleConfig
            ])
            ->onlyMethods(['getProductSku'])
            ->getMock();
        $controller->method('getProductSku')->willReturn(null);
        $result = $controller->getProduct();
        $this->assertNull($result);
    }

}
