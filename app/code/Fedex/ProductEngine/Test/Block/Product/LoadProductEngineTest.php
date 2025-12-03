<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Test\Block\Product;

use Fedex\ProductEngine\Block\Product\LoadProductEngine;
use Fedex\ProductEngine\Model\Config\Backend as PeBackendConfig;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoadProductEngineTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected LoadProductEngine $loadProductEngineMock;
    protected Template\Context|MockObject $contextMock;
    protected RequestInterface|MockObject $requestMock;
    protected ProductInterface|MockObject $productInterfaceMock;
    protected PeBackendConfig|MockObject $peBackendConfigMock;
    protected ProductRepositoryInterface|MockObject $productRepositoryMock;
    protected LoggerInterface|MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Template\Context::class)
            ->onlyMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->contextMock->expects($this->once())->method('getRequest')->willReturn($this->requestMock);

        $this->productInterfaceMock = $this->getMockBuilder(ProductInterface::class)
            ->addMethods(['getPeProductId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->peBackendConfigMock = $this->getMockBuilder(PeBackendConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->loadProductEngineMock = $this->objectManager->getObject(LoadProductEngine::class,
            [
                'context' => $this->contextMock,
                'peBackendConfig' => $this->peBackendConfigMock,
                'productRepository' => $this->productRepositoryMock,
                'logger' => $this->loggerMock,
                'data' => []
            ]
        );
    }

    public function testIsValidProduct(): void
    {
        $this->requestMock->expects($this->atMost(2))->method('getParam')
            ->withConsecutive(['id'], ['access_token'])->willReturnOnConsecutiveCalls('sku', null);

        $this->productInterfaceMock->expects($this->once())->method('getPeProductId')->willReturn('1');
        $this->productRepositoryMock->expects($this->once())->method('get')
            ->with('sku')->willReturn($this->productInterfaceMock);

        $this->assertTrue($this->loadProductEngineMock->isValidProduct());
    }

    public function testIsValidProductNotExist(): void
    {
        $this->requestMock->expects($this->atMost(2))->method('getParam')
            ->withConsecutive(['id'], ['access_token'])->willReturnOnConsecutiveCalls('sku', null);

        $exception = new NoSuchEntityException(__("The product that was requested doesn't exist. Verify the product and try again."));
        $this->productRepositoryMock->expects($this->once())->method('get')
            ->with('sku')->willThrowException($exception);

        $loggerMessage = 'Fedex\ProductEngine\Block\Product\LoadProductEngine::isValidProduct:45 The product that was requested doesn\'t exist. Verify the product and try again.';
        $this->loggerMock->expects($this->once())->method('error')->with($loggerMessage);

        $this->assertFalse($this->loadProductEngineMock->isValidProduct());
    }

    public function testGetProduct(): void
    {
        $this->assertEquals(null, $this->loadProductEngineMock->getProduct());
    }

    /**
     * @return void
     */
    public function testGetProductEngineUrl(): void
    {
        $peUrl = 'https://wwwtest.fedex.com/templates/components/apps/easyprint/content/staticProducts';
        $this->peBackendConfigMock->expects($this->once())->method('getProductEngineUrl')
            ->willReturn($peUrl);

        $peUrlResult = $this->loadProductEngineMock->getProductEngineUrl();
        $this->assertIsString($peUrlResult);
        $this->assertEquals($peUrl, $peUrlResult);
    }
}
