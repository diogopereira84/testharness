<?php

namespace Fedex\CatalogMvp\Test\Unit\Observer;


use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\Cart\Controller\Dunc\Index;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\CatalogMvp\Observer\ProductSaveAfterObserver;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Magento\Framework\HTTP\Client\Curl;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\Company\Model\CompanyFactory;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\Company\Model\Company;
use Magento\Company\Model\ResourceModel\Company\Collection as CompanyCollection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Fedex\CatalogMvp\Model\ProductActivity;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\User\Model\User;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ProductSaveAfterObserverTest extends TestCase
{
    protected $catalogMvpHelperMock;
    protected $observerMock;
    protected $productMock;
    /**
     * @var (\Magento\Framework\Event & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $eventMock;
    protected $requestMock;
    /**
     * @var (\Fedex\Cart\Controller\Dunc\Index & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $duncApiMock;
    /**
     * @var (\Magento\Catalog\Model\Product\Gallery\Processor & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $mediaGalleryProcessorMock;
    /**
     * @var (\Magento\Framework\Filesystem & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $filesystemMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var (\Magento\Company\Model\Company & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyMock;
    /**
     * @var (\Magento\Company\Model\ResourceModel\Company\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyCollectionMock;
    /**
     * @var (\Magento\Store\Model\StoreManagerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $storeManagerInterfaceMock;
    /**
     * @var (\Magento\Store\Api\Data\WebsiteInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $websiteInterfaceMock;
    /**
     * @var (\Fedex\Punchout\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $punchoutHelperMock;
    /**
     * @var (\Fedex\Cart\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cartDataHelperMock;
    /**
     * @var (\Magento\Framework\HTTP\Client\Curl & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $curlMock;
    protected $sharedcatalogRepoInterfaceMock;
    /**
     * @var (\Magento\Company\Model\CompanyFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyFactoryMock;
    /**
     * @var (\Fedex\Company\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyHelperMock;
    /**
     * @var (\Fedex\FXOPricing\Model\FXORateQuote & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fxorateQuoteMock;
    protected $sharedCatalogInterfaceMock;
    protected $catalogdocrefapiMock;
    protected $categoryFactoryMock;
    protected $categoryMock;
    protected $productSaveAfterObserver;
    protected $productActivity;
    protected $adminSession;
    protected $adminUser;
     protected $categoryRepositoryInterfaceMock;
    protected  $toggleConfigMock;

    protected function setUp(): void
    {

        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isMvpCtcAdminEnable',
                'isProductPodEditAbleById',
                'setProductVisibilityValue',
                'getOndemandStoreId',
                'isD216406Enabled'
            ])
            ->getMock();

        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEvent', 'setThumbnail', 'setImage', 'getProduct'])
            ->getMock();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEvent', 'setThumbnail', 'setImage', 'setSmallImage', 'getProduct', 'getSku'])
            ->getMock();


        $this->eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getCollection'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPostValue'])
            ->getMockForAbstractClass();


        $this->duncApiMock = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->setMethods(['callDuncApi'])
            ->getMock();

        $this->mediaGalleryProcessorMock = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->setMethods(['addImage', 'saveImageToMediaFolder'])
            ->getMock();

        $this->filesystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDirectoryWrite', 'getAbsolutePath', 'writeFile'])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical'])
            ->getMockForAbstractClass();

        $this->companyMock = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection'])
            ->getMock();

        $this->companyCollectionMock = $this->getMockBuilder(CompanyCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getIterator', 'getData'])
            ->getMock();

        $this->storeManagerInterfaceMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsite'])
            ->getMockForAbstractClass();

        $this->websiteInterfaceMock = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();


        $this->productMock = $this
            ->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'addImage',
                'getMediaGalleryEntries',
                'getMediaGalleryImages',
                'setName',
                'setTypeId',
                'setAttributeSetId',
                'setSku',
                'setShortDescription',
                'setCategoryIds',
                'setStatus',
                'setPrice',
                'setWebsiteIds',
                'setVisibility',
                'setCustomizable',
                'setUrlKey',
                'load',
                'getSku',
                'getSharedCatalog',
                'getExternalProd',
                'getId',
                'getCategoryIds',
                'getAttributeSetId',
                'setStoreId',
                'setData',
                'save',
                'getData',
                'getName',
                'getPrice'
            ])->getMock();

        $this->punchoutHelperMock = $this->getMockBuilder(PunchoutHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartDataHelperMock = $this->getMockBuilder(CartDataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->curlMock = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedcatalogRepoInterfaceMock = $this->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->companyFactoryMock = $this->getMockBuilder(CompanyFactory::class)
            ->setMethods(['create', 'getCollection', 'addFieldToFilter', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyHelperMock = $this->getMockBuilder(CompanyHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFedexAccountNumber'])
            ->getMock();

        $this->fxorateQuoteMock = $this->getMockBuilder(FXORateQuote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogInterfaceMock = $this->getMockBuilder(SharedCatalogInterface::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->catalogdocrefapiMock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDocumentId', 'addRefernce'])
            ->getMock();

        $this->categoryFactoryMock = $this->getMockBuilder(CategoryFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create'])
        ->getMock();

        $this->categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCustomAttributes', 'load'])
            ->getMock();

        $this->categoryRepositoryInterfaceMock = $this->getMockBuilder(CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get','save'])
            ->getMockForAbstractClass();


        $this->adminSession = $this->getMockBuilder(AdminSession::class)
            ->setMethods(['isLoggedIn','getUser'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productActivity = $this->getMockBuilder(ProductActivity::class)
            ->setMethods(['setData','save'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->adminUser = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','getName'])
            ->getMock();
         $this->toggleConfigMock= $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->productSaveAfterObserver = $objectManagerHelper->getObject(
            ProductSaveAfterObserver::class,
            [
                'catalogMvpHelper' => $this->catalogMvpHelperMock,
                'request' => $this->requestMock,
                'duncApi' => $this->duncApiMock,
                'mediaGalleryProcessor' => $this->mediaGalleryProcessorMock,
                'filesystem' => $this->filesystemMock,
                'logger' => $this->loggerMock,
                'product' => $this->productMock,
                'punchoutHelper' => $this->punchoutHelperMock,
                'cartDataHelper' => $this->cartDataHelperMock,
                'curl' => $this->curlMock,
                'sharedCataloginterface' => $this->sharedcatalogRepoInterfaceMock,
                'companyFactory' => $this->companyFactoryMock,
                'companyHelper' => $this->companyHelperMock,
                'fxoratequot' => $this->fxorateQuoteMock,
                'storeManager' => $this->storeManagerInterfaceMock,
                'catalogdocumentrefapi' => $this->catalogdocrefapiMock,
                'categoryFactory' => $this->categoryFactoryMock,
                'categoryRepositoryInterface' => $this->categoryRepositoryInterfaceMock,
                'productActivity' => $this->productActivity,
                'adminSession' => $this->adminSession,
                'toggleConfig'=>$this->toggleConfigMock
            ]
        );
    }

    /**
     * @test testExecute
     */
    public function testexecute()
    {
        $docID = [123,456];

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);
        $postValue['product']['external_prod'] = '{"fxoMenuId":"1582146604697-4","fxoProductInstance":{"id":"1697444251870","name":"images","productConfig":{"product":{}}}}';
        $postValue['extraconfiguratorvalue']['fxo_menu_id'] = "";
        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn($postValue);
        $this->observerMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isProductPodEditAbleById')
            ->willReturn(1);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isProductPodEditAbleById')
            ->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isD216406Enabled')
            ->willReturn(true);


        $this->productMock
            ->expects($this->any())
            ->method('getData')
            ->willReturn('2,3');

        $this->productMock
            ->expects($this->any())
            ->method('getSharedCatalog')
            ->willReturn([]);

        $this->productMock
            ->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->catalogdocrefapiMock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn($docID);

        $this->catalogdocrefapiMock->expects($this->any())
            ->method('addRefernce')
            ->willReturn(null);

        $this->adminSession->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->adminSession->expects($this->any())
            ->method('getUser')
            ->willReturn($this->adminUser);
        $this->adminUser->expects($this->any())
            ->method('getId')
            ->willReturn(2);
        $this->adminUser->expects($this->any())
            ->method('getName')
            ->willReturn('Test Test');
        $this->productActivity->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $this->productActivity->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->assertNotNull($this->productSaveAfterObserver->execute($this->observerMock));
    }

    /**
     * @test testexecuteWithFxoMenuId
     */
    public function testexecuteWithFxoMenuId()
    {
        $docID = [123,456];

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);
        $postValue['product']['external_prod'] = '{}';
        $postValue['extraconfiguratorvalue']['fxo_menu_id'] = "1582146604697-4";
        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn($postValue);
        $this->observerMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isProductPodEditAbleById')
            ->willReturn(1);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isProductPodEditAbleById')
            ->willReturn(true);

        $this->catalogdocrefapiMock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn($docID);

        $this->catalogdocrefapiMock->expects($this->any())
            ->method('addRefernce')
            ->willReturn(null);

        $this->productMock->expects($this->any())
            ->method('getAttributeSetId')
            ->willReturn(8);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('setProductVisibilityValue')
            ->willReturnSelf();

        $this->productMock
            ->expects($this->any())
            ->method('getSharedCatalog')
            ->willReturn([]);

        $this->productMock
            ->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->assertNotNull($this->productSaveAfterObserver->execute($this->observerMock));
    }

    public function testexecuteToggleOff()
    {
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(false);
        $this->assertNotNull($this->productSaveAfterObserver->execute($this->observerMock));
    }

    public function testupdateProductPriceFromRateApi(){
        $this->productMock
        ->expects($this->any())
        ->method('getSharedCatalog')
        ->willReturn([9,26,29,32]);

        $this->sharedcatalogRepoInterfaceMock
        ->expects($this->any())
        ->method('get')
        ->willReturn($this->sharedCatalogInterfaceMock);

        $this->sharedCatalogInterfaceMock
        ->expects($this->any())
        ->method('getData')
        ->willReturn(23);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('getOndemandStoreId')
            ->willReturn(285);

        $this->productMock
            ->expects($this->any())
            ->method('getPrice')
            ->willReturn(123.00);
        $this->assertNotNull($this->productSaveAfterObserver->updateProductPriceFromRateApi($this->productMock));
    }

    public function testUpdateCategory() {
        $this->catalogMvpHelperMock->expects($this->any())
        ->method('isProductPodEditAbleById')
        ->willReturn(true);
        $this->productMock
        ->expects($this->any())
        ->method('getId')
        ->willReturn(1);
        $this->productMock
        ->expects($this->any())
        ->method('getCategoryIds')
        ->willReturn([1,2]);
        $this->categoryFactoryMock->expects($this->any())->method('create')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->categoryMock->expects($this->any())->method('setCustomAttributes')->willReturnSelf();
        $this->categoryRepositoryInterfaceMock->expects($this->any())->method('save')->willReturnSelf();
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue') ->willReturn(true);
        $this->categoryRepositoryInterfaceMock->expects($this->any())->method('get')->willReturn($this->categoryMock);
        $this->productSaveAfterObserver->updateCategory($this->productMock, true);
    }

}
