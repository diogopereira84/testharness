<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Controller\Index\BulkDelete;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Product;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Class BulkDelete
 * Handle the BulkDelete test cases of the CatalogMvp controller
 */
class BulkDeleteTest extends TestCase
{

    protected $productMock;
    /**
     * @var (\Magento\Framework\Registry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $registryMock;
    protected $requestMock;
    protected $productRepository;
    protected $loggerMock;
    protected $contextMock;
    protected $jsonFactoryMock;
    protected $jsonResultMock;
    protected $helperMock;
    protected $documentApiMock;
    protected $catalogMvp;
    const ID = [1947,1];

    protected $registry;
    protected $product;
    protected $context;
    protected $logger;
    protected $helper;
    protected $catalogDocumentRefranceApi;
    protected $toggleConfigMock;


    protected function setUp(): void
    {
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getName', 'delete', 'getExternalProd'])
            ->getMock();

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['registry'])
            ->getMockForAbstractClass();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->jsonResultMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData'])
            ->getMock();    

        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->jsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->jsonResultMock);    

        $this->helperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpSharedCatalogEnable', 'deleteCategory', 'isSharedCatalogPermissionEnabled', 'insertProductActivity'])
            ->getMock();

        $this->documentApiMock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDocumentId','deleteProductRef'])
            ->getMock();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->catalogMvp = $objectManagerHelper->getObject(
            BulkDelete::class,
            [

                'registry' => $this->registryMock,
                'product' => $this->productMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'logger' => $this->loggerMock,
                'context' => $this->contextMock,
                'helper' => $this->helperMock,
                'catalogDocumentRefranceApi' => $this->documentApiMock,
                'productRepository' => $this->productRepository,
                'request' => $this->requestMock,
                'toggleConfig'=>$this->toggleConfigMock
            ]
        );
    }

    /**
     * @test Execute try case
     */
    public function testExecuteTryCase()
    {
        $this->helperMock->expects($this->any())
        ->method('isMvpSharedCatalogEnable')->willReturn(true);

        $this->helperMock->expects($this->any())
        ->method('isSharedCatalogPermissionEnabled')->willReturn(true);

        $this->productRepository->expects($this->any())
        ->method('getById')->willReturn($this->productMock);

        $this->productMock->expects($this->any())
        ->method('getName')->willReturn('Sample Product Name');

        $this->helperMock->expects($this->any())
        ->method('insertProductActivity')->willReturn(null);

        $this->prepareRequestMock();

        $this->jsonFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->any())
            ->method('setData')->willReturnSelf();

        $this->productMock->expects($this->any())->method('load')->willReturnSelf();

        $this->productMock->expects($this->any())->method('getExternalProd')->willReturn('adasdasd');

        $docId = [123,456];

        $this->documentApiMock->expects($this->any())->method('getDocumentId')->willReturn($docId);

        $this->documentApiMock->expects($this->any())->method('deleteProductRef')->willReturn(null);

        $this->productMock->expects($this->any())->method('delete')->willReturnSelf();
    
        $this->assertEquals($this->jsonResultMock, $this->catalogMvp->execute());
    }

    /**
     * @test Execute try case
     */
    public function testExecuteTryCaseWithToggle()
    {
        $this->helperMock->expects($this->any())
        ->method('isMvpSharedCatalogEnable')->willReturn(true);

        $this->helperMock->expects($this->any())
        ->method('isSharedCatalogPermissionEnabled')->willReturn(true);

        $this->productRepository->expects($this->any())
        ->method('getById')->willReturn($this->productMock);

        $this->productMock->expects($this->any())
        ->method('getName')->willReturnSelf();

        $this->helperMock->expects($this->any())
        ->method('insertProductActivity')->willReturnSelf();

        $this->prepareRequestMock();

        $this->jsonFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->jsonResultMock);

        $this->productMock->expects($this->any())->method('load')->willReturnSelf();

        $this->productMock->expects($this->any())->method('getExternalProd')->willReturn('adasdasd');

        $docId = [123,456];

        $this->documentApiMock->expects($this->any())->method('getDocumentId')->willReturn($docId);

        $this->documentApiMock->expects($this->any())->method('deleteProductRef')->willReturn(null);

        $this->productMock->expects($this->any())->method('delete')->willReturnSelf();

        $this->jsonResultMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->assertEquals($this->jsonResultMock, $this->catalogMvp->execute());
    }

    /**
     * @test Execute try case
     */
    public function testExecuteCatchCase()
    {
        $phrase = new Phrase(__('Exception message'));

        $e = new \Exception($phrase);

        $this->helperMock->expects($this->any())
        ->method('isMvpSharedCatalogEnable')->willReturn(true);

        $this->helperMock->expects($this->any())
        ->method('isSharedCatalogPermissionEnabled')->willReturn(true);

        $this->productRepository->expects($this->any())
        ->method('getById')->willReturn($this->productMock);

        $this->prepareRequestMock();

        $this->jsonFactoryMock->expects($this->any())
        ->method('create')->willReturnSelf();

        $this->jsonResultMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->productMock->expects($this->any())->method('load')->willThrowException($e);

        $this->assertEquals($this->jsonResultMock, $this->catalogMvp->execute());
    }

    /**
     * @test response test case when folder is deleted
     */

    public function testResponsForProductDelete()
    {
        $phrase = new Phrase(__('Folders have been deleted from shared catalog.'));
        $this->assertEquals(['delete' => 1,'message' => $phrase], $this->catalogMvp->response(0, 1));
    }

    /**
     * @test response test case when folder and item both deleted
     */
    public function testResponsForBothDelete()
    {
        $phrase = new Phrase(__('Items/Folders have been deleted from shared catalog.'));
        $this->assertEquals(['delete' => 1,'message' => $phrase], $this->catalogMvp->response(1, 1));
    }



    /**
     * Prepare Request Mock.
     *
     * @return void
     */
    private function prepareRequestMock()
    {
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn(static::ID);

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn(static::ID);
    }

}
