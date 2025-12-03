<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\ProductFactory;
use Fedex\CatalogMvp\Controller\Index\Addtocart;
use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Model\ProductRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use \Magento\Quote\Model\Quote;
use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
/**
 * Class AddtocartTest
 * Handle the addToCart test cases of the CatalogMvp controller
 */
class AddtocartTest extends TestCase
{

    protected $contextMock;
    protected $requestMock;
    /**
     * @var (\Magento\Framework\Data\Form\FormKey & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $formKeyMock;
    protected $cartMock;
    protected $productFactoryMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $helperMock;
    protected $jsonFactoryMock;
    protected $toogleConfig;
    /**
     * @var (\Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cartPerformancetoogleConfig;
    protected $catalogMvp;
    const ID = [1947,1];

    protected $formKey;
    protected $cart;
    protected $product;
    protected $arrayIteratorMock;
    protected $logger;
    protected $helper;
    protected $jsonFactory;
    protected $productRepositoryMock;
    protected $toogleConfigMock;
    protected $quoteMock;
    /**
     * @var Context
     */
    protected Context $registryMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->formKeyMock = $this->getMockBuilder(FormKey::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartMock = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->setMethods(['addProduct', 'save','getQuote'])
            ->getMock();

        $this->productFactoryMock = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','load','getExternalProd'])
            ->getMock();

        
        $this->product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['load','getExternalProd'])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpSharedCatalogEnable'])
            ->getMock();

        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','setData'])
            ->getMock();
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMock();
        $this->toogleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();    
        $this->quoteMock= $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->cartPerformancetoogleConfig = $this->getMockBuilder(AddToCartPerformanceOptimizationToggle::class)
            ->disableOriginalConstructor()
            ->setMethods(['isActive'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->catalogMvp = $objectManagerHelper->getObject(
            Addtocart::class,
            [
                'context' => $this->contextMock,
                'formKey' => $this->formKeyMock,
                'cart' => $this->cartMock,
                'product' => $this->productFactoryMock,
                'logger'  => $this->loggerMock,
                'helper' => $this->helperMock,
		        'request' => $this->requestMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'productRepository'=>$this->productRepositoryMock,
                'toggleConfig'=> $this->toogleConfig,
                'addToCartPerformanceOptimizationToggle' => $this->cartPerformancetoogleConfig
            ]
        );
    }

    /**
     * @test Execute
     */
    public function testExecute()
    {
        $getExternalProd = '{
                            "userProductName":"Indoor Banners",
                            "id":"1445348490823",
                            "version":1,
                            "name":"Banners",
                            "qty":1,
                            "priceable":true,
                            "instanceId":1612338831441,
                            "proofRequired":false,
                            "isOutSourced":false,
                            "features":""
                        }';
        $this->helperMock->expects($this->any())
        ->method('isMvpSharedCatalogEnable')->willReturn(true);

        $this->jsonFactoryMock->expects($this->any())
        ->method('create')->willReturnSelf();

        $this->prepareRequestMock();
        
        $this->productFactoryMock->expects($this->any())->method('create')->willReturnSelf();

        $this->productFactoryMock->expects($this->any())->method('load')->with(1947)->willReturnSelf();

        $this->productFactoryMock->expects($this->any())->method('getExternalProd')->willReturn($getExternalProd);

        $this->cartMock->expects($this->any())->method('addProduct')->willReturnSelf();

        $this->cartMock->expects($this->any())->method('save')->willReturnSelf();

        $this->jsonFactoryMock->expects($this->any())
        ->method('setData')->willReturnSelf();
        $this->toogleConfig->expects($this->any())
        ->method('getToggleConfigValue')->willReturn(true);
        $this->productRepositoryMock->expects($this->any())
        ->method('getById')->willReturn($this->product);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->assertEquals($this->jsonFactoryMock, $this->catalogMvp->execute());
    }

    /**
     * Test case for Execute with Exception
     */
    public function testExecuteWithException()
    {
        $this->helperMock->expects($this->any())
        ->method('isMvpSharedCatalogEnable')->willReturn(true);

        $this->jsonFactoryMock->expects($this->any())
        ->method('create')->willReturnSelf();

        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->productFactoryMock->expects($this->any())->method('create')->willThrowException($exception);

        $this->assertEquals($this->jsonFactoryMock, $this->catalogMvp->execute());
    }

    /**
     * Prepare Request Mock.
     *
     * @return void
     */
    private function prepareRequestMock()
    {
        $id = static::ID;
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['id', null, static::ID]
                ]
            );
    }
}