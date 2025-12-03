<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Test\Unit\Controller;

use Fedex\Cart\Controller\Dunc\Index;
use Fedex\CatalogMvp\Controller\Index\SaveProduct;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CatalogMvp\Helper\EmailHelper;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Filesystem;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SearchResultsInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Api\WebhookInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Product\Action as productAction;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Model\Config as SelfRegConfig;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValue;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Class SaveProductTest
 * Handle the SaveProductTest test cases of the CatalogMvp controller
 */
class SaveProductTest extends TestCase
{

    protected $contextMock;
    protected $catalogMvpHelper;
    protected $searchCriteriaBuilder;
    protected $searchCriteria;
    protected $sessionFactory;
    protected $session;
    protected $customer;
    protected $sharedCatalogRepository;
    protected $searchResultsInterface;
    protected $sharedCatalogInterface;
    /**
     * @var (\Magento\SharedCatalog\Api\ProductManagementInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $productSharedCatalogManagement;
    /**
     * @var (\Magento\Catalog\Api\CategoryLinkManagementInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $categoryLink;
    protected $requestMock;
    /**
     * @var (\Magento\Framework\Data\Form\FormKey & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $formKeyMock;
    /**
     * @var (\Magento\Checkout\Model\Cart & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cartMock;
    protected $productFactoryMock;
    protected $attributeSetMock;
    protected $attributeSetModelMock;
    protected $attributeSetCollectionMock;
    protected $json;
    /**
     * @var (\Magento\Catalog\Model\Product\Gallery\Processor & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $mediaGalleryProcessorMock;
    protected $filesystemMock;
    protected $categoryMock;
    protected $webhookInterfaceMock;
    protected $ProductRepositoryMock;
    protected $productActionMock;
    protected $documentrefapimock;
    protected $toggleConfigMock;
    /**
     * @var SelfRegConfig|MockObject
     */
    protected $selfRegConfigMock;
    /**
     * @var (\Fedex\CatalogMvp\Helper\EmailHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $catalogMvpEmailHelperMock;
    protected $saveProduct;
    const ID = [1947, 1];

    protected $formKey;
    protected $cart;
    protected $product;
    protected $arrayIteratorMock;
    protected $loggerMock;
    protected $jsonFactoryMock;
    protected $toggleConfig;
    protected $categoryRepositoryMock;
    /**
     * @var Context
     */
    protected Context $registryMock;
    protected $requestInterMock;
    protected $fileDriverMock;
    
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogMvpHelper = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpSharedCatalogEnable', 'isSharedCatalogPermissionEnabled', 'convertTimeIntoPST', 'convertTimeIntoPSTWithCustomerTimezone','isProductPodEditAbleById','isDocumentPreviewApiEnable', 'setProductVisibilityValue','insertProductActivity', 'getCustomerSessionId','isB2421984Enabled'])
            ->getMock();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFilter', 'create'])
            ->getMock();
        $this->searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestInterMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sessionFactory = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['setFromMvpProductCreate',
            'unsFromMvpProductCreate',
            'getCustomer',
            'getCustomerCompany',
            'getName'])
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGroupId','getName'])
            ->getMock();

        $this->sharedCatalogRepository = $this->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getList'])
            ->getMockForAbstractClass();

        $this->searchResultsInterface = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItems'])
            ->getMockForAbstractClass();

        $this->sharedCatalogInterface = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();

        $this->productSharedCatalogManagement = $this->getMockBuilder(ProductManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['assignProducts'])
            ->getMockForAbstractClass();

        $this->categoryLink = $this->getMockBuilder(CategoryLinkManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['assignProductToCategories'])
            ->getMockForAbstractClass();

        $this->requestMock = $this->getMockBuilder(Http::class)
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
            ->setMethods(['addProduct', 'save'])
            ->getMock();

        $this->productFactoryMock = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','load', 'getExternalProd', 'setStoreId', 'setData',
                'setSku', 'setName', 'setCatalogDescription', 'setRelatedKeywords',
                'setAttributeSetId', 'setStatus', 'setVisibility', 'setUrlKey',
                'setTaxClassId', 'setTypeId', 'setPrice', 'setExternalProd',
                'setPublished', 'setWebsiteIds', 'setStockData', 'addImageToMediaGallery',
                'getImage','getSku','save','getCreatedAt','getUpdatedAt','getAttributeSetId',
                'setProductCreatedDate','setProductUpdatedDate','setProductAttributeSetsId'])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeSetMock = $this->getMockBuilder(SetFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->attributeSetModelMock = $this->getMockBuilder(\Magento\Eav\Model\Entity\Attribute\Set::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'getId'])
            ->getMock();
        $this->attributeSetCollectionMock = $this->getMockBuilder(\Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'setPageSize', 'getFirstItem', 'getId'])
            ->getMock();

        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->mediaGalleryProcessorMock = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->setMethods(['addImage', 'saveImageToMediaFolder'])
            ->getMock();

        $this->filesystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDirectoryWrite', 'getAbsolutePath', 'writeFile'])
            ->getMock();

        $this->categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMock();

        $this->webhookInterfaceMock = $this->getMockBuilder(WebhookInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['addProductToRM'])
            ->getMockForAbstractClass();

        $this->ProductRepositoryMock = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->productActionMock = $this->getMockBuilder(productAction::class)
            ->disableOriginalConstructor()
            ->setMethods(['updateAttributes'])
            ->getMockForAbstractClass();

        $this->documentrefapimock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getDocumentId',
                'addRefernce',
                'updateProductDocumentEndDate',
                'curlCallForPreviewApi',
                'documentLifeExtendApiCall',
                'getPreviewImageUrl'
            ])->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->selfRegConfigMock = $this->getMockBuilder(SelfRegConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAddCatalogItemMessage'])
            ->getMock();

        $this->catalogMvpEmailHelperMock = $this->getMockBuilder(EmailHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendReadyForReviewEmail'])
            ->getMock();

        $this->categoryRepositoryMock = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'getUrl'])
            ->getMock();

        $this->fileDriverMock = $this->getMockBuilder(FileDriver::class)
            ->disableOriginalConstructor()
            ->setMethods(['fileGetContents'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->saveProduct = $objectManagerHelper->getObject(
            SaveProduct::class,
            [
                'context' => $this->contextMock,
                'attributeSetFactory' => $this->attributeSetMock,
                'productFactory' => $this->productFactoryMock,
                'logger' => $this->loggerMock,
                'resultJsonFactory' => $this->jsonFactoryMock,
                'mediaGalleryProcessor' => $this->mediaGalleryProcessorMock,
                'filesystem' => $this->filesystemMock,
                'catalogMvpHelper' => $this->catalogMvpHelper,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'sessionFactory' => $this->sessionFactory,
                'productSharedCatalogManagement' => $this->productSharedCatalogManagement,
                'categoryLink' => $this->categoryLink,
                'webhookInterface' => $this->webhookInterfaceMock,
                'productRepository' => $this->ProductRepositoryMock,
                'productAction' => $this->productActionMock,
                'catalogdocumentrefapi' => $this->documentrefapimock,
                'toggleConfig' => $this->toggleConfigMock,
                'catalogMvpEmailHelper' => $this->catalogMvpEmailHelperMock,
                'categoryRepository'=>$this->categoryRepositoryMock,
                'requestInterface' => $this->requestInterMock,
                'fileDriver' => $this->fileDriverMock,
                'selfRegConfig' => $this->selfRegConfigMock
            ]
        );
    }

    /**
     * @test Execute
     */
    public function testExecute()
    {
        $postData = $this->getPostData();
        $this->toggleConfigMock->expects($this->any())
        ->method('getToggleConfigValue')->willReturn(true);
        $this->requestInterMock->expects($this->any())
            ->method('getParams')->willReturn($postData);
        $this->session->expects($this->any())
            ->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())
            ->method('getName')->willReturnSelf();
        $this->session->expects($this->any())
            ->method('getCustomerCompany')->willReturnSelf();
        $this->catalogMvpHelper->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvpHelper->expects($this->any())->method('getCustomerSessionId')->willReturn(null);
        $this->catalogMvpHelper->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->ProductRepositoryMock->expects($this->any())
        ->method('getById')->willReturn($this->product);
        $this->commonExecute();
        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willReturnSelf();
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();
        $this->catalogMvpHelper->expects($this->any())->method('setProductVisibilityValue')->willReturn(true);
        $this->product->expects($this->any())
        ->method('setStoreId')->willReturnSelf();
        $this->product->expects($this->any())
        ->method('setData')->willReturnSelf();
        $this->product->expects($this->any())
        ->method('save')->willReturnSelf();
        $this->catalogMvpHelper->expects($this->any())->method('insertProductActivity')->willReturnSelf();
        $this->testExecuteAscCall();
        $this->webhookInterfaceMock->expects($this->any())->method('addProductToRM')->willReturn(true);
        $this->assertEquals($this->json, $this->saveProduct->execute());
    }
    /**
     * @test testExecuteAscCall
     */
    public function testExecuteAscCall() {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->documentrefapimock->expects($this->any())
            ->method('updateProductDocumentEndDate')
            ->willReturn(true);
    }

    /**
     * @test Execute
     */
    public function testExecuteToggleOff()
    {
        $postData = $this->getPostData();
        $this->requestInterMock->expects($this->any())
            ->method('getParams')->willReturn($postData);
        $this->catalogMvpHelper->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvpHelper->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->ProductRepositoryMock->expects($this->any())
        ->method('getById')->willReturn($this->product);
        $this->commonExecuteToggleOff();
        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willReturnSelf();
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();
        $this->webhookInterfaceMock->expects($this->any())->method('addProductToRM')->willReturn(true);
        $this->assertEquals($this->json, $this->saveProduct->execute());
    }

     /**
     * @test Execute
     */
    public function testExecuteWithNormalFlow()
    {
        $postData = $this->getPostDataEditFalse();
        $this->requestInterMock->expects($this->any())
            ->method('getParams')->willReturn($postData);
        $this->catalogMvpHelper->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvpHelper->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->ProductRepositoryMock->expects($this->any())
        ->method('getById')->willReturn($this->product);
        $this->commonExecute();
        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willReturnSelf();
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();
        $this->webhookInterfaceMock->expects($this->any())->method('addProductToRM')->willReturn(true);
        $this->assertEquals($this->json, $this->saveProduct->execute());
    }

    /**
     * @test Execute
     */
    public function testExecuteWithNormalFlowToggleOff()
    {
        $postData = $this->getPostDataEditFalse();
        $this->requestInterMock->expects($this->any())
            ->method('getParams')->willReturn($postData);
        $this->catalogMvpHelper->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvpHelper->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->ProductRepositoryMock->expects($this->any())
        ->method('getById')->willReturn($this->product);
        $this->commonExecuteToggleOff();
        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willReturnSelf();
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();
        $this->webhookInterfaceMock->expects($this->any())->method('addProductToRM')->willReturn(true);
        $this->assertEquals($this->json, $this->saveProduct->execute());
    }

    public function testExecuteWithNormalFlowWithExceptin()
    {
        $postData = $this->getPostDataEditFalse();
        $this->requestInterMock->expects($this->any())
            ->method('getParams')->willReturn($postData);
        $this->catalogMvpHelper->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvpHelper->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->ProductRepositoryMock->expects($this->any())
        ->method('getById')->willReturn($this->product);
        $this->commonExecute();
        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willReturnSelf();
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();
        $this->webhookInterfaceMock->expects($this->any())->method('addProductToRM')->willThrowException(new \Exception());
        $this->assertEquals($this->json, $this->saveProduct->execute());
    }
    public function testExecuteWithNormalFlowWithExceptinToggleeOff()
    {
        $postData = $this->getPostDataEditFalse();
        $this->requestInterMock->expects($this->any())
            ->method('getParams')->willReturn($postData);
        $this->catalogMvpHelper->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvpHelper->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->ProductRepositoryMock->expects($this->any())
        ->method('getById')->willReturn($this->product);
        $this->commonExecuteToggleOff();
        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willReturnSelf();
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();
        $this->webhookInterfaceMock->expects($this->any())->method('addProductToRM')->willThrowException(new \Exception());
        $this->assertEquals($this->json, $this->saveProduct->execute());
    }

    public function testExecuteWithToggleOff()
    {
        $postData = $this->getPostData();
        $this->requestInterMock->expects($this->any())
            ->method('getParams')->willReturn($postData);
        $this->catalogMvpHelper->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(false);
        $this->catalogMvpHelper->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(false);
        $this->commonExecute();
        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willReturnSelf();
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();
        $this->assertEquals($this->json, $this->saveProduct->execute());
    }

    public function testExecuteWithToggleOffToggleOff()
    {
        $postData = $this->getPostData();
        $this->requestInterMock->expects($this->any())
            ->method('getParams')->willReturn($postData);
        $this->catalogMvpHelper->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(false);
        $this->catalogMvpHelper->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(false);
        $this->commonExecuteToggleOff();
        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willReturnSelf();
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();
        $this->assertEquals($this->json, $this->saveProduct->execute());
    }

    public function testExecuteWithWriteFileException()
    {
       $postData = $this->getPostData();
        $this->requestInterMock->expects($this->any())
            ->method('getParams')->willReturn($postData);
        $this->catalogMvpHelper->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(false);
        $this->catalogMvpHelper->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(false);
        $this->commonExecute();
        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willThrowException(new \Exception());
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willThrowException(new \Exception());
        $this->assertEquals($this->json, $this->saveProduct->execute());
    }
    public function testExecuteWithWriteFileExceptionToggleOff()
    {
       $postData = $this->getPostData();
        $this->requestInterMock->expects($this->any())
            ->method('getParams')->willReturn($postData);
        $this->catalogMvpHelper->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(false);
        $this->catalogMvpHelper->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(false);
        $this->commonExecuteToggleOff();
        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willThrowException(new \Exception());
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willThrowException(new \Exception());
        $this->assertEquals($this->json, $this->saveProduct->execute());
    }


    public function getPostData()
    {
        $postData = [];
        $postData['productName'] = '1_Page_85_x_11';
        $postData['productStartDate'] = '08/16/2023 7:00 PM';
        $postData['productEndDate'] = '08/23/2023 8:00 PM';
        $postData['noStartAnEndDate'] = false;
        $postData['productDescription'] = 'test';
        $postData['productTag'] = "test1,test2";
        $postData['externalProd'] = '{"productionContentAssociations":[],"userProductName":"Screenshot from 2023-10-10 15-06-14","id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,"instanceId":1697456992981,"proofRequired":false,"isOutSourced":false,"minDPI":"150.0","features":[{"id":"1464882763509","name":"Product Type","choice":{"id":"1464884397179","name":"Canvas Prints","properties":[{"id":"1494365340946","name":"PREVIEW_TYPE","value":"STATIC"},{"id":"1514365340957","name":"VISUALIZATION_TYPE","value":"3D"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448989268401","name":"Canvas Paper","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"ROL05"}]}},{"id":"1448981549109","name":"Size","choice":{"id":"1449002054022","name":"24x36","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"36"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"24"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"36"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"24"}]}},{"id":"1448985622584","name":"Mounting","choice":{"id":"1466532051072","name":"1 1/2 Wooden Frame","properties":[{"id":"1518627861660","name":"MOUNTING_TYPE","value":"1.5_WOOD_FRAME"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168","name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"c4192b1a-6c19-11ee-9bc8-8f04b08aa025","contentReference":"c6af56a3-6c19-11ee-941e-e874604d0ea3","contentType":"IMAGE","fileName":"Screenshot from 2023-10-10 15-06-14.png","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":true},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454606828294","name":"SPC_TYPE_ID","value":"12"},{"id":"1454606860996","name":"SPC_MODEL_ID","value":"1"},{"id":"1454606876712","name":"SPC_VERSION_ID","value":"1"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order. DO NOT use the Production Instructions listed above. Specifications: 24 in. x 36 in. Canvas Print Package, SKU 2337, ROL05 Canvas Matte."},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"24"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"36"}]}';
        $postData['productPrice'] = "$0.61";
        $postData['productCategory'] = '["1681","1804","9"]';
        $postData['productId'] = '3350';
        $postData['customizable'] = 'false';
        return $postData;
    }

    public function getPostDataEditFalse(){
        $postData = [];
        $postData['productName'] = '1_Page_85_x_11';
        $postData['productStartDate'] = '08/16/2023 7:00 PM';
        $postData['productEndDate'] = '08/23/2023 8:00 PM';
        $postData['noStartAnEndDate'] = false;
        $postData['productDescription'] = 'test';
        $postData['productTag'] = "test1,test2";
        $postData['externalProd'] = '{"productionContentAssociations":[],"userProductName":"Screenshot from 2023-10-10 15-06-14","id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,"instanceId":1697456992981,"proofRequired":false,"isOutSourced":false,"minDPI":"150.0","features":[{"id":"1464882763509","name":"Product Type","choice":{"id":"1464884397179","name":"Canvas Prints","properties":[{"id":"1494365340946","name":"PREVIEW_TYPE","value":"STATIC"},{"id":"1514365340957","name":"VISUALIZATION_TYPE","value":"3D"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448989268401","name":"Canvas Paper","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"ROL05"}]}},{"id":"1448981549109","name":"Size","choice":{"id":"1449002054022","name":"24x36","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"36"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"24"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"36"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"24"}]}},{"id":"1448985622584","name":"Mounting","choice":{"id":"1466532051072","name":"1 1/2 Wooden Frame","properties":[{"id":"1518627861660","name":"MOUNTING_TYPE","value":"1.5_WOOD_FRAME"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168","name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"c4192b1a-6c19-11ee-9bc8-8f04b08aa025","contentReference":"c6af56a3-6c19-11ee-941e-e874604d0ea3","contentType":"IMAGE","fileName":"Screenshot from 2023-10-10 15-06-14.png","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":true},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454606828294","name":"SPC_TYPE_ID","value":"12"},{"id":"1454606860996","name":"SPC_MODEL_ID","value":"1"},{"id":"1454606876712","name":"SPC_VERSION_ID","value":"1"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order. DO NOT use the Production Instructions listed above. Specifications: 24 in. x 36 in. Canvas Print Package, SKU 2337, ROL05 Canvas Matte."},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"24"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"36"}]}';
        $postData['productPrice'] = "$0.61";
        $postData['productCategory'] = '["1681","1804","9"]';
        $postData['productId'] = null;
        $postData['customizable'] = false;
        return $postData;
    }

    public function commonExecute()
    {

        $this->catalogMvpHelper->expects($this->any())->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");
        $this->attributeSetMock->expects($this->any())->method('create')->willReturn($this->attributeSetModelMock);
        $this->attributeSetModelMock->expects($this->any())
            ->method('getCollection')->willReturn($this->attributeSetCollectionMock);
        $this->attributeSetCollectionMock->expects($this->any())
            ->method('addFieldToFilter')->willReturnSelf();
        $this->attributeSetCollectionMock->expects($this->any())
            ->method('setPageSize')->willReturnSelf();
        $this->attributeSetCollectionMock->expects($this->any())
            ->method('getFirstItem')->willReturn($this->attributeSetModelMock);
        $this->attributeSetModelMock->expects($this->any())
            ->method('getId')->willReturn('12');
        $this->sessionFactory->expects($this->any())
            ->method('create')->willReturn($this->session);
        $this->session->expects($this->any())
            ->method('setFromMvpProductCreate')->willReturnSelf();
        $this->session->expects($this->any())
            ->method('unsFromMvpProductCreate')->willReturnSelf();
        $this->session->expects($this->any())
            ->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())
            ->method('getGroupId')->willReturn(89);
        $this->productFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->product);
        $this->ProductRepositoryMock->expects($this->any())
            ->method('getById')->willReturn($this->product);
        $this->product->expects($this->any())
            ->method('setData')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('getImage')->willReturn('fkajsfd-sdfkasd-ksdlfkd-sldf3-dfkasdffkajsfd-sdfkasd-ksdlfkd-sldf3-dfkasdf');
        $this->product->expects($this->any())
            ->method('getSku')->willReturn('fkajsfd-sdfkasd-ksdlfkd-sldf3-dfkasdf');

        $this->product->expects($this->any())
            ->method('setSku')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setName')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setStoreId')->willReturnSelf();
        $this->productActionMock->expects($this->any())
            ->method('updateAttributes')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setCatalogDescription')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setRelatedKeywords')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setAttributeSetId')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setStatus')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setVisibility')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setUrlKey')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setTaxClassId')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setPrice')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setExternalProd')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setPublished')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setWebsiteIds')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('save')->willReturnSelf();
        $this->product
            ->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn('26/06/1990 12:00:00');

        $this->product
            ->expects($this->any())
            ->method('getUpdatedAt')
            ->willReturn('26/06/1990 12:00:00');

        $this->product
            ->expects($this->any())
            ->method('getAttributeSetId')
            ->willReturn('123');
        $this->product->expects($this->any())
            ->method('setStoreId')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setName')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setPrice')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setExternalProd')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setPublished')->willReturnSelf();
        $this->product
            ->expects($this->any())
            ->method('setProductCreatedDate')
            ->willReturnSelf();
        $this->product
            ->expects($this->any())
            ->method('setProductUpdatedDate')
            ->willReturnSelf();
        $this->product
            ->expects($this->any())
            ->method('setProductAttributeSetsId')
            ->willReturnSelf();
        $this->product->expects($this->any())
            ->method('save')->willReturnSelf();

        $this->catalogMvpHelper->expects($this->any())
            ->method('isProductPodEditAbleById')
            ->willReturn(1);

        $docId = [123,456];
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn($docId);

        $this->documentrefapimock->expects($this->any())
            ->method('addRefernce')
            ->willReturn(null);

        $this->documentrefapimock->expects($this->any())
            ->method('updateProductDocumentEndDate')
            ->willReturn(true);

        $this->categoryRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->categoryMock);

        $this->categoryMock->expects($this->any())
            ->method('getUrl')->willReturn("https://staging3.office.fedex.com/brwoser-products.html");
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('create')->willReturn($this->searchCriteria);
        $this->sharedCatalogRepository->expects($this->any())
            ->method('getList')->willReturn($this->searchResultsInterface);
        $this->searchResultsInterface->expects($this->any())
            ->method('getItems')->willReturn([$this->sharedCatalogInterface]);
        $this->sharedCatalogInterface->expects($this->any())
            ->method('getId')->willReturn(23);
        $this->jsonFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->json);
        $this->json->expects($this->any())
            ->method('setData')->willReturnSelf();
        $duncResponse = [
            'successful' => true,
            'output' => [
                'imageByteStream' => 'shdfjahdfjkhasdfjhasdfjhajsdhfajsdhfjahdfsjahdsfjhasdjfhasjdfh',
            ],
        ];
        $this->catalogMvpHelper->expects($this->any())->method('isDocumentPreviewApiEnable')->willReturn(true);
        $this->catalogMvpHelper->expects($this->any())
        ->method('isB2421984Enabled')
        ->willReturn(true);
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn('123123123123');
        $this->documentrefapimock->expects($this->any())
        ->method('getPreviewImageUrl')
        ->willReturn('imageurl');
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn('123123123123');
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystemMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn(true);
        $this->categoryRepositoryMock->expects($this->any())
             ->method('get')
             ->willReturn($this->categoryMock);

        $this->assertEquals($this->json, $this->saveProduct->execute());
    }

    public function commonExecuteToggleOff()
    {
        $this->catalogMvpHelper->expects($this->any())->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");
        $this->attributeSetMock->expects($this->any())->method('create')->willReturn($this->attributeSetModelMock);
        $this->attributeSetModelMock->expects($this->any())
            ->method('getCollection')->willReturn($this->attributeSetCollectionMock);
        $this->attributeSetCollectionMock->expects($this->any())
            ->method('addFieldToFilter')->willReturnSelf();
        $this->attributeSetCollectionMock->expects($this->any())
            ->method('setPageSize')->willReturnSelf();
        $this->attributeSetCollectionMock->expects($this->any())
            ->method('getFirstItem')->willReturn($this->attributeSetModelMock);
        $this->attributeSetModelMock->expects($this->any())
            ->method('getId')->willReturn('12');
        $this->sessionFactory->expects($this->any())
            ->method('create')->willReturn($this->session);
        $this->session->expects($this->any())
            ->method('setFromMvpProductCreate')->willReturnSelf();
        $this->session->expects($this->any())
            ->method('unsFromMvpProductCreate')->willReturnSelf();
        $this->session->expects($this->any())
            ->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())
            ->method('getGroupId')->willReturn(89);
        $this->productFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->product);
        $this->ProductRepositoryMock->expects($this->any())
            ->method('getById')->willReturn($this->product);
        $this->product->expects($this->any())
            ->method('setData')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('getImage')->willReturn(
                'fkajsfd-sdfkasd-ksdlfkd-sldf3-dfkasdffkajsfd-sdfkasd-ksdlfkd-sldf3-dfkasdf'
            );
        $this->product->expects($this->any())
            ->method('getSku')->willReturn('fkajsfd-sdfkasd-ksdlfkd-sldf3-dfkasdf');

        $this->product->expects($this->any())
            ->method('setSku')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setName')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setStoreId')->willReturnSelf();
        $this->productActionMock->expects($this->any())
            ->method('updateAttributes')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setCatalogDescription')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setRelatedKeywords')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setAttributeSetId')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setStatus')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setVisibility')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setUrlKey')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setTaxClassId')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setPrice')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setExternalProd')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setPublished')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setWebsiteIds')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('save')->willReturnSelf();
            $this->product->expects($this->any())
            ->method('setStoreId')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setName')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setPrice')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setExternalProd')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setPublished')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('save')->willReturnSelf();
        $this->catalogMvpHelper->expects($this->any())
            ->method('isProductPodEditAbleById')
            ->willReturn(1);

        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn('123123123123');

        $this->documentrefapimock->expects($this->any())
            ->method('addRefernce')
            ->willReturn(null);

        $this->documentrefapimock->expects($this->any())
            ->method('updateProductDocumentEndDate')
            ->willReturn(true);

        $this->categoryRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->categoryMock);

        $this->categoryMock->expects($this->any())
            ->method('getUrl')->willReturn("https://staging3.office.fedex.com/brwoser-products.html");

        $this->searchCriteriaBuilder->expects($this->any())
            ->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('create')->willReturn($this->searchCriteria);
        $this->sharedCatalogRepository->expects($this->any())
            ->method('getList')->willReturn($this->searchResultsInterface);
        $this->searchResultsInterface->expects($this->any())
            ->method('getItems')->willReturn([$this->sharedCatalogInterface]);
        $this->sharedCatalogInterface->expects($this->any())
            ->method('getId')->willReturn(23);
        $this->jsonFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->json);
        $this->json->expects($this->any())
            ->method('setData')->willReturnSelf();
        $duncResponse = [
            'successful' => true,
            'output' => [
                'imageByteStream' => 'shdfjahdfjkhasdfjhasdfjhajsdhfajsdhfjahdfsjahdsfjhasdjfhasjdfh',
            ],
        ];
        $this->catalogMvpHelper->expects($this->any())->method('isDocumentPreviewApiEnable')->willReturn(false);
        $this->catalogMvpHelper->expects($this->any())
            ->method('isB2421984Enabled')
            ->willReturn(false);
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn('123123123123');
        $this->documentrefapimock->expects($this->any())
        ->method('curlCallForPreviewApi')
        ->willReturn('raw_imagedata');
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn('123123123123');
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystemMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn(true);

            $this->assertEquals($this->json, $this->saveProduct->execute());
    }

    public function commonExecuteWithException()
    {

        $this->catalogMvpHelper->expects($this->any())->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");
        $this->attributeSetMock->expects($this->any())->method('create')->willReturn($this->attributeSetModelMock);
        $this->attributeSetModelMock->expects($this->any())
            ->method('getCollection')->willReturn($this->attributeSetCollectionMock);
        $this->attributeSetCollectionMock->expects($this->any())
            ->method('addFieldToFilter')->willReturnSelf();
        $this->attributeSetCollectionMock->expects($this->any())
            ->method('setPageSize')->willReturnSelf();
        $this->attributeSetCollectionMock->expects($this->any())
            ->method('getFirstItem')->willReturn($this->attributeSetModelMock);
        $this->attributeSetModelMock->expects($this->any())
            ->method('getId')->willReturn('12');
        $this->sessionFactory->expects($this->any())
            ->method('create')->willReturn($this->session);
        $this->session->expects($this->any())
            ->method('setFromMvpProductCreate')->willReturnSelf();
        $this->session->expects($this->any())
            ->method('unsFromMvpProductCreate')->willReturnSelf();
        $this->session->expects($this->any())
            ->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())
            ->method('getGroupId')->willReturn(89);
        $this->productFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->product);
        $this->ProductRepositoryMock->expects($this->any())
            ->method('getById')->willReturn($this->product);
        $this->product->expects($this->any())
            ->method('setData')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('getImage')->willReturn('fkajsfd-sdfkasd-ksdlfkd-sldf3-dfkasdffkajsfd-sdfkasd-ksdlfkd-sldf3-dfkasdf');
        $this->product->expects($this->any())
            ->method('getSku')->willReturn('fkajsfd-sdfkasd-ksdlfkd-sldf3-dfkasdf');

        $this->product->expects($this->any())
            ->method('setSku')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setName')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setStoreId')->willReturnSelf();
        $this->productActionMock->expects($this->any())
            ->method('updateAttributes')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setCatalogDescription')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setRelatedKeywords')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setAttributeSetId')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setStatus')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setVisibility')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setUrlKey')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setTaxClassId')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setPrice')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setExternalProd')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setPublished')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setWebsiteIds')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('save')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setStoreId')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setName')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setPrice')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setExternalProd')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setPublished')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('save')->willReturnSelf();
        $this->catalogMvpHelper->expects($this->any())
            ->method('isProductPodEditAbleById')
            ->willReturn(1);

        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn('123123123123');

        $this->documentrefapimock->expects($this->any())
            ->method('addRefernce')
            ->willReturn(null);

        $this->documentrefapimock->expects($this->any())
            ->method('updateProductDocumentEndDate')
            ->willReturn(true);

        $this->categoryRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->categoryMock);

        $this->categoryMock->expects($this->any())
            ->method('getUrl')->willReturn("https://staging3.office.fedex.com/brwoser-products.html");
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('create')->willReturn($this->searchCriteria);
        $this->sharedCatalogRepository->expects($this->any())
            ->method('getList')->willReturn($this->searchResultsInterface);
        $this->searchResultsInterface->expects($this->any())
            ->method('getItems')->willReturn([$this->sharedCatalogInterface]);
        $this->sharedCatalogInterface->expects($this->any())
            ->method('getId')->willReturn(23);
        $this->jsonFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->json);
        $this->json->expects($this->any())
            ->method('setData')->willReturnSelf();
        $duncResponse = [
            'successful' => true,
            'output' => [
                'imageByteStream' => 'shdfjahdfjkhasdfjhasdfjhajsdhfajsdhfjahdfsjahdsfjhasdjfhasjdfh',
            ],
        ];
        $this->catalogMvpHelper->expects($this->any())->method('isDocumentPreviewApiEnable')->willReturn(true);
        $this->catalogMvpHelper->expects($this->any())
        ->method('isB2421984Enabled')
        ->willReturn(true);
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn('123123123123');
        $this->documentrefapimock->expects($this->any())
        ->method('getPreviewImageUrl')
        ->willReturn('imageurl');
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn('123123123123');
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystemMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn(true);

            $this->assertEquals($this->json, $this->saveProduct->execute());
    }

    public function commonExecuteWithExceptionToggleoOff()
    {

        $this->catalogMvpHelper->expects($this->any())->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");
        $this->attributeSetMock->expects($this->any())->method('create')->willReturn($this->attributeSetModelMock);
        $this->attributeSetModelMock->expects($this->any())
            ->method('getCollection')->willReturn($this->attributeSetCollectionMock);
        $this->attributeSetCollectionMock->expects($this->any())
            ->method('addFieldToFilter')->willReturnSelf();
        $this->attributeSetCollectionMock->expects($this->any())
            ->method('setPageSize')->willReturnSelf();
        $this->attributeSetCollectionMock->expects($this->any())
            ->method('getFirstItem')->willReturn($this->attributeSetModelMock);
        $this->attributeSetModelMock->expects($this->any())
            ->method('getId')->willReturn('12');
        $this->sessionFactory->expects($this->any())
            ->method('create')->willReturn($this->session);
        $this->session->expects($this->any())
            ->method('setFromMvpProductCreate')->willReturnSelf();
        $this->session->expects($this->any())
            ->method('unsFromMvpProductCreate')->willReturnSelf();
        $this->session->expects($this->any())
            ->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())
            ->method('getGroupId')->willReturn(89);
        $this->productFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->product);
        $this->ProductRepositoryMock->expects($this->any())
            ->method('getById')->willReturn($this->product);
        $this->product->expects($this->any())
            ->method('setData')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('getImage')->willReturn('fkajsfd-sdfkasd-ksdlfkd-sldf3-dfkasdffkajsfd-sdfkasd-ksdlfkd-sldf3-dfkasdf');
        $this->product->expects($this->any())
            ->method('getSku')->willReturn('fkajsfd-sdfkasd-ksdlfkd-sldf3-dfkasdf');

        $this->product->expects($this->any())
            ->method('setSku')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setName')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setStoreId')->willReturnSelf();
        $this->productActionMock->expects($this->any())
            ->method('updateAttributes')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setCatalogDescription')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setRelatedKeywords')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setAttributeSetId')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setStatus')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setVisibility')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setUrlKey')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setTaxClassId')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setPrice')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setExternalProd')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setPublished')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setWebsiteIds')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('save')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setStoreId')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setName')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setPrice')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setExternalProd')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setPublished')->willReturnSelf();
        $this->product->expects($this->any())
            ->method('save')->willReturnSelf();
        $this->catalogMvpHelper->expects($this->any())
            ->method('isProductPodEditAbleById')
            ->willReturn(1);

        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn('123123123123');

        $this->documentrefapimock->expects($this->any())
            ->method('addRefernce')
            ->willReturn(null);

        $this->documentrefapimock->expects($this->any())
            ->method('updateProductDocumentEndDate')
            ->willReturn(true);

        $this->categoryRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->categoryMock);

        $this->categoryMock->expects($this->any())
            ->method('getUrl')->willReturn("https://staging3.office.fedex.com/brwoser-products.html");

        $this->searchCriteriaBuilder->expects($this->any())
            ->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('create')->willReturn($this->searchCriteria);
        $this->sharedCatalogRepository->expects($this->any())
            ->method('getList')->willReturn($this->searchResultsInterface);
        $this->searchResultsInterface->expects($this->any())
            ->method('getItems')->willReturn([$this->sharedCatalogInterface]);
        $this->sharedCatalogInterface->expects($this->any())
            ->method('getId')->willReturn(23);
        $this->jsonFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->json);
        $this->json->expects($this->any())
            ->method('setData')->willReturnSelf();
        $duncResponse = [
            'successful' => true,
            'output' => [
                'imageByteStream' => 'shdfjahdfjkhasdfjhasdfjhajsdhfajsdhfjahdfsjahdsfjhasdjfhasjdfh',
            ],
        ];
        $this->catalogMvpHelper->expects($this->any())->method('isDocumentPreviewApiEnable')->willReturn(false);
        $this->catalogMvpHelper->expects($this->any())
        ->method('isB2421984Enabled')
        ->willReturn(false);
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn('123123123123');
        $this->documentrefapimock->expects($this->any())
        ->method('curlCallForPreviewApi')
        ->willReturn('raw_imagedata');
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystemMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willThrowException(new \Exception());

        $this->assertEquals($this->json, $this->saveProduct->execute());
    }

    /**
     * @test Execute with toggle enabled and custom add message
     */
    public function testExecuteWithToggleEnabledAndCustomAddMessage()
    {
        $postData = $this->getPostData();
        $customMessage = 'Custom add catalog item message from configuration';
        
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')->willReturn(true);
        $this->selfRegConfigMock->expects($this->any())
            ->method('getAddCatalogItemMessage')->willReturn($customMessage);
        
        $this->requestInterMock->expects($this->any())
            ->method('getParams')->willReturn($postData);
        $this->catalogMvpHelper->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvpHelper->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->ProductRepositoryMock->expects($this->any())
            ->method('getById')->willReturn($this->product);
        
        $this->commonExecute();
        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willReturnSelf();
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();
        $this->webhookInterfaceMock->expects($this->any())->method('addProductToRM')->willReturn(true);
        $this->assertEquals($this->json, $this->saveProduct->execute());
    }

    /**
     * @test Execute with toggle enabled but empty custom add message - should use default
     */
    public function testExecuteWithToggleEnabledButEmptyCustomAddMessage()
    {
        $postData = $this->getPostData();
        
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')->willReturn(true);
        $this->selfRegConfigMock->expects($this->any())
            ->method('getAddCatalogItemMessage')->willReturn('');
        
        $this->requestInterMock->expects($this->any())
            ->method('getParams')->willReturn($postData);
        $this->catalogMvpHelper->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvpHelper->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->ProductRepositoryMock->expects($this->any())
            ->method('getById')->willReturn($this->product);
        
        $this->commonExecute();
        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willReturnSelf();
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();
        $this->webhookInterfaceMock->expects($this->any())->method('addProductToRM')->willReturn(true);
        $this->assertEquals($this->json, $this->saveProduct->execute());
    }

    /**
     * @test Execute with toggle disabled - should use default message
     */
    public function testExecuteWithToggleDisabledForAddMessage()
    {
        $postData = $this->getPostData();
        
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')->willReturn(false);
        
        $this->requestInterMock->expects($this->any())
            ->method('getParams')->willReturn($postData);
        $this->catalogMvpHelper->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvpHelper->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->ProductRepositoryMock->expects($this->any())
            ->method('getById')->willReturn($this->product);
        
        $this->commonExecute();
        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willReturnSelf();
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();
        $this->webhookInterfaceMock->expects($this->any())->method('addProductToRM')->willReturn(true);
        $this->assertEquals($this->json, $this->saveProduct->execute());
    }
}
