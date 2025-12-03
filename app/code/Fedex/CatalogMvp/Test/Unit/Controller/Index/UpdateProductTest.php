<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product;
use Fedex\CatalogMvp\Controller\Index\UpdateProduct;
use \Magento\Framework\App\RequestInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\ProductRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class UpdateProductTest
 * Handle the UpdateProduct test cases of the CatalogMvp controller
 */
class UpdateProductTest extends TestCase
{

    protected $productMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $request;
    /**
     * @var (\Magento\Framework\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $catalogMvpHelper;
    protected $jsonFactory;
    protected $catalogMvp;
    protected $context;
    protected $product;
    protected $logger;
    protected $productRepositoryMock;
    protected $toggleConfigMock;

    /**
     * @var Context
     */
    protected Context $registryMock;

    protected function setUp(): void
    {
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['load','create','getPublished','setPublished'])
            ->getMock();
        
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogMvpHelper = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['setProductVisibilityValue', 'insertProductActivity'])
            ->getMock();

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setData'])
            ->getMock();
        
        $objectManagerHelper = new ObjectManager($this);
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepository::class)
        ->disableOriginalConstructor()
        ->setMethods(['getById','save'])
        ->getMock();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
        ->disableOriginalConstructor()
        ->setMethods(['getToggleConfigValue'])
        ->getMock();         
        $this->catalogMvp = $objectManagerHelper->getObject(
            UpdateProduct::class,
            [
                'context' => $this->contextMock,
                'product' => $this->productMock,
                'logger'  => $this->loggerMock,
                'catalogMvp'  => $this->catalogMvpHelper,
                '_request' => $this->request,
                'productRepository' => $this->productRepositoryMock,
                'toggleConfig'=>$this->toggleConfigMock,
                'jsonFactory' => $this->jsonFactory
            ]
        );
    }

    /**
     * @test Execute if case
     */
    public function testExecuteIfCase()
    {
        $this->prepareRequestMock();
        
        $this->productMock->expects($this->any())->method('create')->willReturnSelf();

        $this->productRepositoryMock->expects($this->any())
        ->method('getById')
        ->willReturn($this->productMock);

        $this->productMock->expects($this->any())->method('getPublished')->willReturn(1);

        $this->jsonFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactory->expects($this->any())->method('setData')->willReturnSelf();

        $this->catalogMvpHelper->expects($this->any())->method('setProductVisibilityValue')->willReturnSelf();

        $this->catalogMvpHelper->expects($this->any())->method('insertProductActivity')->willReturnSelf();
        $this->toggleConfigMock->expects($this->any())
        ->method('getToggleConfigValue')
        ->willReturn(true);
        $this->productRepositoryMock->expects($this->any())
        ->method('getById')
        ->willReturn($this->productMock);     
        $this->assertNotNull($this->catalogMvp->execute());
    }

    /**
     * @test Execute if case
     */
    public function testExecuteElseCase()
    {
        $this->prepareRequestMock();
        
        $this->productMock->expects($this->any())->method('create')->willReturnSelf();

        $this->productRepositoryMock->expects($this->any())
        ->method('getById')
        ->willReturn($this->productMock);
        $this->jsonFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactory->expects($this->any())->method('setData')->willReturnSelf();
        $this->productMock->expects($this->any())->method('getPublished')->willReturn(0);

        $this->assertNotNull($this->catalogMvp->execute());
    }


    /**
     * Prepare Request Mock.
     *
     * @return void
     */
    private function prepareRequestMock()
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturn(123);
    }
}
