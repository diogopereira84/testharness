<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\ProductFactory;
use Fedex\CatalogMvp\Controller\Index\AddtocartSingle;
use Magento\Framework\App\RequestInterface;
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
class AddtocartSingleTest extends TestCase
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
    protected $productRepositoryMock;
    protected $toogleConfig;
    /**
     * @var (\Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cartPerformancetoogleConfig;
    protected $catalogMvp;
    const ID = 1947;

    protected $formKey;
    protected $cart;
    protected $product;
    protected $arrayIteratorMock;
    protected $logger;
    protected $helper;
    protected $jsonFactory;
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
            ->setMethods(['addProduct', 'save',"getQuote"])
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
            
        $this->cartPerformancetoogleConfig = $this->getMockBuilder(AddToCartPerformanceOptimizationToggle::class)
            ->disableOriginalConstructor()
            ->setMethods(['isActive'])
            ->getMock();

        $this->quoteMock= $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();       
        $objectManagerHelper = new ObjectManager($this);
        $this->catalogMvp = $objectManagerHelper->getObject(
            AddtocartSingle::class,
            [
                'context' => $this->contextMock,
                'formKey' => $this->formKeyMock,
                'cart' => $this->cartMock,
                'product' => $this->productFactoryMock,
                'logger'  => $this->loggerMock,
                'helper' => $this->helperMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'productRepository'=> $this->productRepositoryMock,
                'toggleConfig'=>$this->toogleConfig,
                'request' => $this->requestMock,
                'addToCartPerformanceOptimizationToggle' => $this->cartPerformancetoogleConfig
            ]
        );
    }

    /**
     * @test Execute
     */
    public function testExecute()
    {
        
        $this->helperMock->expects($this->any())
        ->method('isMvpSharedCatalogEnable')->willReturn(true);

        $this->jsonFactoryMock->expects($this->any())
        ->method('create')->willReturnSelf();

        $this->prepareRequestMock();
        
        $this->productFactoryMock->expects($this->any())
        ->method('create')->willReturnSelf();

        $this->productFactoryMock->expects($this->any())
        ->method('load')->with(1947)->willReturnSelf();

        $this->cartMock->expects($this->any())
        ->method('addProduct')->willReturnSelf();

        $this->cartMock->expects($this->any())
        ->method('save')->willReturnSelf();

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
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        
        $this->helperMock->expects($this->any())
        ->method('isMvpSharedCatalogEnable')->willReturn(true);

        $this->jsonFactoryMock->expects($this->any())
        ->method('create')->willReturnSelf();

        $this->prepareRequestMock();
        
        $this->productFactoryMock->expects($this->any())
        ->method('create')->willReturnSelf();

        $this->productRepositoryMock->expects($this->any())
        ->method('getById')->willReturn($this->product);

        $this->productFactoryMock->expects($this->any())
        ->method('getExternalProd')->willReturn($this->geProductData());

        $this->cartMock->expects($this->any())
        ->method('addProduct')->willThrowException($exception);

        $this->jsonFactoryMock->expects($this->any())
        ->method('setData')->willReturnSelf();
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        // $this->assertEquals(false, $this->catalogMvp->execute());
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
        /**
     * Function to geProductData
     */
    public function geProductData()
    {

        $postData = '{"productionContentAssociations":[],"userProductName":"download","id":"1591958939309","version":1,"name":"Menu","qty":100,"priceable":true,"instanceId":1697453632992,"proofRequired":false,"isOutSourced":false,"minDPI":"150.0","features":[{"id":"1531980104656","name":"Product Type","choice":{"id":"1591961360619","name":"Menu","properties":[]}},{"id":"1448981549109","name":"Paper Size","choice":{"id":"1448986650332","name":"8.5x11","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"11"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"8.5"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"11"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"8.5"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1535620925780","name":"CC4 100lb Matte","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"CC4"},{"id":"1453234015081","name":"PAPER_COLOR","value":"#FFFFFF"},{"id":"1470166630346","name":"MEDIA_NAME","value":"100# Matte"}]}},{"id":"1448981549581","name":"Print Color","choice":{"id":"1448988600611","name":"Full Color","properties":[{"id":"1453242778807","name":"PRINT_COLOR","value":"COLOR"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168","name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}},{"id":"1679607670330","name":"Offset Stacking","choice":{"id":"1679607706803","name":"Off","properties":[]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"e48e02ea-6c11-11ee-9bc8-e96035ac8e20","contentReference":"e8c71ca2-6c11-11ee-ad0c-d2893db794b5","contentType":"IMAGE","fileName":"download.jpeg","contentReqId":"1455709847200","name":"Front_Side","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":11,"height":8.5,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":true},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"8.5"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"11"},{"id":"1464709502522","name":"PRODUCT_QTY_SET","value":"100"},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: DO NOT use the Production Instructions listed on the Job Ticket. Use the following instructions to produce this Quick Menu order. From PPA,set the file up 2-up on 11 x 17.  Print color. Paper: 100lb Matte (CC45). Reset print qty to 50 sheets. Cut in half at 8.5 x 11. Yield quantity: 100"},{"id":"1494365340946","name":"PREVIEW_TYPE","value":"DYNAMIC"},{"id":"1470151737965","name":"TEMPLATE_AVAILABLE","value":"YES"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null}]}';

        return $postData;
    }
}
