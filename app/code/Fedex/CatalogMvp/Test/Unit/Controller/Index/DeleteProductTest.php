<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Controller\Index\DeleteProduct;

use Exception;
// use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Product;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Phrase;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\ProductRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Model\Config as SelfRegConfig;
use PHPUnit\Framework\MockObject\MockObject;
/**
 * Class UpdateProductTest
 * Handle the UpdateProduct test cases of the CatalogMvp controller
 */
class DeleteProductTest extends TestCase
{

    protected $productMock;
    /**
     * @var (\Magento\Framework\Registry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $registryMock;
    protected $requestMock;
    protected $catalogMvpMock;
    protected $loggerMock;
    protected $contextMock;
    protected $jsonFactoryMock;
    protected $documentApiMock;
    protected $catalogMvp;
    const ID = 123;

    protected $registry;
    protected $product;
    protected $context;
    protected $logger;
    protected $catalogDocumentRefranceApi;
    protected $productRepositoryMock;
    protected $toogleConfigMock;
    /**
     * @var SelfRegConfig|MockObject
     */
    protected $selfRegConfigMock;


    protected function setUp(): void
    {
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'delete', 'getExternalProd', 'getName'])
            ->getMock();

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['registry'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogMvpMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['insertProductActivity'])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','setData'])
            ->getMock();

        $this->documentApiMock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDocumentId','deleteProductRef'])
            ->getMock();
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById','delete'])
            ->getMock();
        $this->toogleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->selfRegConfigMock = $this->getMockBuilder(SelfRegConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDeleteCatalogItemMessage'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->catalogMvp = $objectManagerHelper->getObject(
            DeleteProduct::class,
            [

                'registry' => $this->registryMock,
                'product' => $this->productMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'logger' => $this->loggerMock,
                'context' => $this->contextMock,
                'catalogDocumentRefranceApi' => $this->documentApiMock,
                'catalogMvp' => $this->catalogMvpMock,
                'productRepository'=>$this->productRepositoryMock,
                'toggleConfig'=> $this->toogleConfigMock,
                'selfRegConfig' => $this->selfRegConfigMock
            ]
        );
    }

    /**
     * @test Execute if case
     */
    public function testExecuteTryCase()
    {
        $this->prepareRequestMock();

        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();

        $this->productRepositoryMock->expects($this->any())
        ->method('getById')->willReturn($this->productMock);

        $this->productMock->expects($this->any())->method('getExternalProd')->willReturn('adasdasd');
        $this->productMock->expects($this->any())->method('getName')->willReturn('Test Product Name');

        $docId = [123,456];

        $this->documentApiMock->expects($this->any())->method('getDocumentId')->willReturn($docId);

        $this->productRepositoryMock->expects($this->any())->method('delete')->willReturnSelf();

        $this->documentApiMock->expects($this->any())->method('deleteProductRef')->willReturn(null);

        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->catalogMvpMock->expects($this->any())->method('insertProductActivity')->willReturnSelf();
        $this->toogleConfigMock->expects($this->any())
        ->method('getToggleConfigValue')->willReturn(true);
        $this->assertEquals($this->jsonFactoryMock, $this->catalogMvp->execute());
    }

     /**
     * @test Execute if case
     */
    public function testExecuteCatchCase()
    {


        $this->prepareRequestMock();

        $phrase = new Phrase(__('Exception message'));

        $e = new \Exception($phrase);

        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();

        $this->productRepositoryMock->expects($this->any())
        ->method('getById')->willReturn($this->productMock);
        // $this->catalogMvp->execute();
        $this->assertEquals($this->jsonFactoryMock, $this->catalogMvp->execute());
    }

    /**
     * @test Execute with toggle enabled and custom delete message
     */
    public function testExecuteWithToggleEnabledAndCustomDeleteMessage()
    {
        $customMessage = 'Custom delete catalog item message from configuration';
        
        $this->prepareRequestMock();
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->productRepositoryMock->expects($this->any())
            ->method('getById')->willReturn($this->productMock);
        $this->productMock->expects($this->any())->method('getExternalProd')->willReturn('adasdasd');
        $this->productMock->expects($this->any())->method('getName')->willReturn('Test Product Name');
        
        $docId = [123,456];
        $this->documentApiMock->expects($this->any())->method('getDocumentId')->willReturn($docId);
        $this->productRepositoryMock->expects($this->any())->method('delete')->willReturnSelf();
        $this->documentApiMock->expects($this->any())->method('deleteProductRef')->willReturn(null);
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->catalogMvpMock->expects($this->any())->method('insertProductActivity')->willReturnSelf();
        
        $this->toogleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')->willReturn(true);
        $this->selfRegConfigMock->expects($this->any())
            ->method('getDeleteCatalogItemMessage')->willReturn($customMessage);
        
        $this->assertEquals($this->jsonFactoryMock, $this->catalogMvp->execute());
    }

    /**
     * @test Execute with toggle enabled but empty custom delete message - should use default
     */
    public function testExecuteWithToggleEnabledButEmptyCustomDeleteMessage()
    {
        $this->prepareRequestMock();
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->productRepositoryMock->expects($this->any())
            ->method('getById')->willReturn($this->productMock);
        $this->productMock->expects($this->any())->method('getExternalProd')->willReturn('adasdasd');
        $this->productMock->expects($this->any())->method('getName')->willReturn('Test Product Name');
        
        $docId = [123,456];
        $this->documentApiMock->expects($this->any())->method('getDocumentId')->willReturn($docId);
        $this->productRepositoryMock->expects($this->any())->method('delete')->willReturnSelf();
        $this->documentApiMock->expects($this->any())->method('deleteProductRef')->willReturn(null);
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->catalogMvpMock->expects($this->any())->method('insertProductActivity')->willReturnSelf();
        
        $this->toogleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')->willReturn(true);
        $this->selfRegConfigMock->expects($this->any())
            ->method('getDeleteCatalogItemMessage')->willReturn('');
        
        $this->assertEquals($this->jsonFactoryMock, $this->catalogMvp->execute());
    }

    /**
     * @test Execute with toggle disabled - should use default message
     */
    public function testExecuteWithToggleDisabledForDeleteMessage()
    {
        $this->prepareRequestMock();
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->productRepositoryMock->expects($this->any())
            ->method('getById')->willReturn($this->productMock);
        $this->productMock->expects($this->any())->method('getExternalProd')->willReturn('adasdasd');
        $this->productMock->expects($this->any())->method('getName')->willReturn('Test Product Name');
        
        $docId = [123,456];
        $this->documentApiMock->expects($this->any())->method('getDocumentId')->willReturn($docId);
        $this->productRepositoryMock->expects($this->any())->method('delete')->willReturnSelf();
        $this->documentApiMock->expects($this->any())->method('deleteProductRef')->willReturn(null);
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->catalogMvpMock->expects($this->any())->method('insertProductActivity')->willReturnSelf();
        
        $this->toogleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')->willReturn(false);
        
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
