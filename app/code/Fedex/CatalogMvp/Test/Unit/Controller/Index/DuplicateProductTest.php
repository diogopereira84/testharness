<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Controller\Index\DuplicateProduct;
use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Helper\Image;
use Fedex\CatalogMvp\Model\Duplicate;
use Magento\Customer\Model\SessionFactory;
use Fedex\CatalogMvp\Api\WebhookInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Customer;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;

/**
 * Class DuplicateProduct
 * Handle the BulkDelete test cases of the CatalogMvp controller
 */
class DuplicateProductTest extends TestCase
{

    protected $requestMock;
    protected $contextMock;
    protected $jsonFactoryMock;
    protected $helperMock;
    protected $productMock;
    protected $duplicateMock;
    protected $imageMock;
    protected $sessionFactory;
    protected $session;
    protected $webhookInterfaceMock;
    protected $customer;
    protected $documentrefapimock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $productResourceModelMock;
    protected $catalogMvp;
    const ID = '1947';
    const VIEWMODE = 'grid';

    protected $registry;
    protected $context;
    protected $logger;
    protected $helper;
    protected $catalogdocumentrefapi;
    protected $toogleConfigMock;
    protected $productRepositoryMock;
    protected $resourceModelMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParams'])
            ->getMock();

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

        $this->helperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpSharedCatalogEnable', 'deleteCategory', 'isSharedCatalogPermissionEnabled', 'isProductPodEditAbleById', 'insertProductActivity', 'getCustomerSessionId'])
            ->getMock();
        
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'setStockData', 'save', 'getSku', 'getId',
             'getName', 'getUpdatedAt', 'getPrice', 'getPublished','getResource'])
            ->getMock();
        
        $this->duplicateMock = $this->getMockBuilder(Duplicate::class)
            ->disableOriginalConstructor()
            ->setMethods(['copy'])
            ->getMock();

        $this->imageMock = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->setMethods(['init', 'getUrl'])
            ->getMock();
        
        $this->sessionFactory = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer'])
            ->getMock();

        $this->webhookInterfaceMock = $this->getMockBuilder(WebhookInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['addProductToRM'])
            ->getMockForAbstractClass();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGroupId'])
            ->getMock();
        
        $this->documentrefapimock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDocumentId', 'addRefernce','updateProductDocumentEndDate'])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById','save'])
            ->getMock();
        $this->toogleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();    
        $this->productResourceModelMock = $this->getMockBuilder(ProductResourceModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMock();        
        $objectManagerHelper = new ObjectManager($this);
        $this->catalogMvp = $objectManagerHelper->getObject(
            DuplicateProduct::class,
            [
                'jsonFactory' => $this->jsonFactoryMock,
                'helper' => $this->helperMock,
                'imageHelper' => $this->imageMock,
                'product' => $this->productMock,
                'duplicate' =>$this->duplicateMock,
                'context' => $this->contextMock,
                'sessionFactory' => $this->sessionFactory,
                'webhookInterface' => $this->webhookInterfaceMock,
                'catalogdocumentrefapi' => $this->documentrefapimock,
                'logger' => $this->loggerMock,
                'productRepository'=> $this->productRepositoryMock,
                'toggleConfig'=>$this->toogleConfigMock
            ]
        );
    }

    /**
     * @test Execute If case
     */
    public function testExecuteIf()
    {
        $this->helperMock->expects($this->any())
        ->method('isMvpSharedCatalogEnable')->willReturn(true);

        $this->helperMock->expects($this->any())
        ->method('getCustomerSessionId')->willReturn(null);

        $this->helperMock->expects($this->any())
        ->method('isSharedCatalogPermissionEnabled')->willReturn(true);

        $this->prepareRequestMock('grid');

        $this->sessionFactory->expects($this->any())->method('create')->willReturn($this->session);

        $this->session->expects($this->any())->method('getCustomer')->willReturn($this->customer);

        $this->customer->expects($this->any())->method('getGroupId')->willReturn(89);

        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();

        $this->productRepositoryMock->expects($this->any())
        ->method('getById')
        ->willReturn($this->productMock);

        $this->duplicateMock->expects($this->any())->method('copy')->willReturn($this->productMock);

        $this->productMock->expects($this->any())->method('setStockData')->willReturnSelf();
       
        $this->productMock->expects($this->any())->method('getResource')->willReturn($this->productResourceModelMock);
       
        $this->productResourceModelMock->expects($this->any())->method('save')->willReturnSelf();

        $this->helperMock->expects($this->any())
        ->method('insertProductActivity')->willReturnSelf();

        $docID = [123,456];
        
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn($docID);
        
        $this->documentrefapimock->expects($this->any())
            ->method('addRefernce')
            ->willReturn(null);

        $this->documentrefapimock->expects($this->any())
            ->method('updateProductDocumentEndDate')
            ->willReturn(true);

        $this->webhookInterfaceMock->expects($this->any())->method('addProductToRM')->willReturn(true);

        $this->imageMock->expects($this->any())->method('init')->willReturnSelf();

        $this->imageMock->expects($this->any())->method('getUrl')->willReturn('a/b/abc.jpg');

        $this->productMock->expects($this->any())->method('getPrice')->willReturn(76);

        $this->productMock->expects($this->any())->method('getUpdatedAt')->willReturn(76);

        $this->productMock->expects($this->any())->method('getPublished')->willReturn(true);

        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals($this->jsonFactoryMock, $this->catalogMvp->execute());
    }

    /**
     * @test Execute If case for non published
     */
    public function testExecuteIfNotPublished()
    {
        $this->helperMock->expects($this->any())
        ->method('isMvpSharedCatalogEnable')->willReturn(true);

        $this->helperMock->expects($this->any())
        ->method('isSharedCatalogPermissionEnabled')->willReturn(true);

        $this->prepareRequestMock('grid');

        $this->sessionFactory->expects($this->any())->method('create')->willReturn($this->session);

        $this->session->expects($this->any())->method('getCustomer')->willReturn($this->customer);

        $this->customer->expects($this->any())->method('getGroupId')->willReturn(89);

        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();

        $this->productRepositoryMock->expects($this->any())
        ->method('getById')
        ->willReturn($this->productMock);

        $this->duplicateMock->expects($this->any())->method('copy')->willReturn($this->productMock);

        $this->productMock->expects($this->any())->method('setStockData')->willReturnSelf();

        $this->productMock->expects($this->any())->method('getResource')->willReturn($this->productResourceModelMock);
       
        $this->productResourceModelMock->expects($this->any())->method('save')->willReturnSelf();

        $docID = [123,456];
        
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn($docID);
        
        $this->documentrefapimock->expects($this->any())
            ->method('addRefernce')
            ->willReturn(null);

        $this->documentrefapimock->expects($this->any())
            ->method('updateProductDocumentEndDate')
            ->willReturn(true);


        $this->webhookInterfaceMock->expects($this->any())->method('addProductToRM')->willReturn(true);

        $this->imageMock->expects($this->any())->method('init')->willReturnSelf();

        $this->imageMock->expects($this->any())->method('getUrl')->willReturn('a/b/abc.jpg');

        $this->productMock->expects($this->any())->method('getPrice')->willReturn(76);

        $this->productMock->expects($this->any())->method('getUpdatedAt')->willReturn(76);

        $this->productMock->expects($this->any())->method('getPublished')->willReturn(false);

        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        
        $this->assertEquals($this->jsonFactoryMock, $this->catalogMvp->execute());
    }

    /**
     * @test Execute Else case
     */
    public function testExecuteElse()
    {
        $this->helperMock->expects($this->any())
        ->method('isMvpSharedCatalogEnable')->willReturn(true);

        $this->helperMock->expects($this->any())
        ->method('isSharedCatalogPermissionEnabled')->willReturn(true);

        $this->prepareRequestMock('list');

        $this->sessionFactory->expects($this->any())->method('create')->willReturn($this->session);

        $this->session->expects($this->any())->method('getCustomer')->willReturn($this->customer);

        $this->customer->expects($this->any())->method('getGroupId')->willReturn(89);

        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();

        $this->productRepositoryMock->expects($this->any())
        ->method('getById')
        ->willReturn($this->productMock);

        $this->duplicateMock->expects($this->any())->method('copy')->willReturn($this->productMock);

        $this->productMock->expects($this->any())->method('setStockData')->willReturnSelf();
       
        $this->productMock->expects($this->any())->method('save')->willReturnSelf();

        $docID = [123,456];
        
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn($docID);
        
        $this->documentrefapimock->expects($this->any())
            ->method('addRefernce')
            ->willReturn(null);

        $this->documentrefapimock->expects($this->any())
            ->method('updateProductDocumentEndDate')
            ->willReturn(true);

        $this->webhookInterfaceMock->expects($this->any())->method('addProductToRM')->willReturn(true);

        $this->imageMock->expects($this->any())->method('init')->willReturnSelf();

        $this->imageMock->expects($this->any())->method('getUrl')->willReturn('a/b/abc.jpg');

        $this->productMock->expects($this->any())->method('getPrice')->willReturn(76);

        $this->productMock->expects($this->any())->method('getUpdatedAt')->willReturn(76);
        $this->toogleConfigMock->expects($this->any())
        ->method('getToggleConfigValue')
        ->willReturn(true);
        $this->productRepositoryMock->expects($this->any())
        ->method('getById')
        ->willReturn($this->productMock);
        $this->productMock->expects($this->any())->method('getResource')->willReturn($this->productResourceModelMock);
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals($this->jsonFactoryMock, $this->catalogMvp->execute());
    }

    /**
     * Prepare Request Mock.
     *
     * @return void
     */
    private function prepareRequestMock($view)
    {
        $postData['pid'] = 123;
        $postData['viewMode'] = $view;
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn($postData);
    }
}
