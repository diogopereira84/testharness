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
use Fedex\CatalogMvp\Observer\ProductSaveBeforeObserver;
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
use Fedex\CustomerGroup\Model\FolderPermission;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ProductSaveBeforeObserverTest extends TestCase
{
    protected $catalogMvpHelperMock;
    protected $observerMock;
    protected $productMock;
    /**
     * @var (\Magento\Framework\Event & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $eventMock;
    protected $requestMock;
    protected $duncApiMock;
    /**
     * @var (\Magento\Catalog\Model\Product\Gallery\Processor & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $mediaGalleryProcessorMock;
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
    protected $documentrefapimock;
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
    /**
     * @var (\Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
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
    /**
     * @var (\Magento\SharedCatalog\Api\Data\SharedCatalogInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $sharedCatalogInterfaceMock;
    protected $folderPermissionMock;
    protected $productSaveBeforeObserver;
    protected $fileDriverMock;
    protected $toggleConfigMock;

    protected function setUp(): void
    {

        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isMvpCtcAdminEnable',
                'isDocumentPreviewApiEnable',
                'isAttributeSetPrintOnDemand',
                'convertTimeIntoPSTWithCustomerTimezone',
                'toggleD202288',
                'isDuplicateProductImageToggle',
                'isB2421984Enabled',
                'isD216406Enabled',
                'getCurrentTime',
                'getCurrentPSTDateAndTime'
            ])
            ->getMock();

        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEvent', 'setThumbnail', 'setImage', 'getProduct'])
            ->getMock();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEvent', 'setThumbnail', 'setImage', 'setSmallImage', 'getProduct', 'getSku', 'setVisibility'])
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

        $this->documentrefapimock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDocumentId','curlCallForPreviewApi','getPreviewImageUrl'])
            ->getMock();


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
                'setStartDatePod',
                'setEndDatePod',
                'getExternalProd',
                'getCategoryIds',
                'getCreatedAt',
                'getStartDatePod',
                'setPublished',
                'setData',
                'setProductCreatedDate',
                'setProductUpdatedDate',
                'setProductAttributeSetsId'
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

        $this->fileDriverMock = $this->getMockBuilder(FileDriver::class)
            ->disableOriginalConstructor()
            ->setMethods(['fileGetContents'])
            ->getMock();

        $this->folderPermissionMock = $this->getMockBuilder(FolderPermission::class)
            ->setMethods(['getCustomerGroupIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->productSaveBeforeObserver = $objectManagerHelper->getObject(
            ProductSaveBeforeObserver::class,
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
                'catalogdocumentrefapi' => $this->documentrefapimock,
                'folderPermission' => $this->folderPermissionMock,
                'toggleConfig' => $this->toggleConfigMock,
                'fileDriver' => $this->fileDriverMock
            ]
        );
    }

    /**
     * @test testExecute
     */
    public function testexecute()
    {


        $postData = [

            'product' =>
            [
                'external_prod' => '{"productionContentAssociations":[],"userProductName":"Screenshot from 2023-10-10 15-06-14","id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,"instanceId":1697456992981,"proofRequired":false,"isOutSourced":false,"minDPI":"150.0","features":[{"id":"1464882763509","name":"Product Type","choice":{"id":"1464884397179","name":"Canvas Prints","properties":[{"id":"1494365340946","name":"PREVIEW_TYPE","value":"STATIC"},{"id":"1514365340957","name":"VISUALIZATION_TYPE","value":"3D"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448989268401","name":"Canvas Paper","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"ROL05"}]}},{"id":"1448981549109","name":"Size","choice":{"id":"1449002054022","name":"24x36","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"36"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"24"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"36"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"24"}]}},{"id":"1448985622584","name":"Mounting","choice":{"id":"1466532051072","name":"1 1/2 Wooden Frame","properties":[{"id":"1518627861660","name":"MOUNTING_TYPE","value":"1.5_WOOD_FRAME"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168","name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"c4192b1a-6c19-11ee-9bc8-8f04b08aa025","contentReference":"c6af56a3-6c19-11ee-941e-e874604d0ea3","contentType":"IMAGE","fileName":"Screenshot from 2023-10-10 15-06-14.png","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":true},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454606828294","name":"SPC_TYPE_ID","value":"12"},{"id":"1454606860996","name":"SPC_MODEL_ID","value":"1"},{"id":"1454606876712","name":"SPC_VERSION_ID","value":"1"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order. DO NOT use the Production Instructions listed above. Specifications: 24 in. x 36 in. Canvas Print Package, SKU 2337, ROL05 Canvas Matte."},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"24"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"36"}]}',
                'sku' => 'ahdfjahdfjhadjfhadfadf','shared_catalog' => '23'
            ],
            'extraconfiguratorvalue' =>
            ['customertimezone' => 'Asia/Calcutta','custom_start_date' => '2023-08-08 03:00:00','custom_end_date' => '2023-08-08 03:00:00']
        ];
        $postData['product']['name'] = "Product Name Test";
        $duncResponse = [
            'successful' => true,
            'output' => [
                'imageByteStream' => 'shdfjahdfjkhasdfjhasdfjhajsdhfajsdhfjahdfsjahdsfjhasdjfhasjdfh'
            ]
        ];

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isD216406Enabled')
            ->willReturn(true);
        $this->observerMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn($postData);

        $this->productMock
        ->expects($this->any())
        ->method('getCategoryIds')
        ->willReturn([1,2]);

        $this->folderPermissionMock
            ->expects($this->any())
            ->method('getCustomerGroupIds')
            ->willReturn([2,3]);

        $this->catalogMvpHelperMock->expects($this->any())->method('isDocumentPreviewApiEnable')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())
        ->method('isB2421984Enabled')
        ->willReturn(true);
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn('123123123123');
        $this->documentrefapimock->expects($this->any())
        ->method('getPreviewImageUrl')
        ->willReturn('imageurl');
        $this->duncApiMock->expects($this->any())
            ->method('callDuncApi')
            ->willReturn($duncResponse);
        // $this->saveImageToMediaFoldertestwithException();
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystemMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn('https://staging3.office.fedex.com/media/catalog/product/n/o/noerror.png');

        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willReturnSelf();

	$this->catalogMvpHelperMock->expects($this->any())
            ->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");

        $this->productMock->expects($this->any())
        ->method('setStartDatePod')->willReturn($this->productMock);

        $this->productMock->expects($this->any())
            ->method('getCreatedAt')->willReturn('2024-04-04 12:12:12');

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");

        $this->productMock->expects($this->any())
            ->method('setEndDatePod')->willReturn($this->productMock);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isDuplicateProductImageToggle')
            ->willReturn(true);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isAttributeSetPrintOnDemand')
            ->willReturn(true);

        $this->productMock->expects($this->any())
            ->method('setVisibility')
            ->willReturnSelf();
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('toggleD202288')->willReturn(true);

        // Mock toggle config calls
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);

        $this->assertNotNull($this->productSaveBeforeObserver->execute($this->observerMock));
    }

    /**
     * @test testExecute
     */
    public function testexecuteToggleOff()
    {


        $postData = [

            'product' =>
            [
                'external_prod' => '{"productionContentAssociations":[],"userProductName":"Screenshot from 2023-10-10 15-06-14","id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,"instanceId":1697456992981,"proofRequired":false,"isOutSourced":false,"minDPI":"150.0","features":[{"id":"1464882763509","name":"Product Type","choice":{"id":"1464884397179","name":"Canvas Prints","properties":[{"id":"1494365340946","name":"PREVIEW_TYPE","value":"STATIC"},{"id":"1514365340957","name":"VISUALIZATION_TYPE","value":"3D"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448989268401","name":"Canvas Paper","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"ROL05"}]}},{"id":"1448981549109","name":"Size","choice":{"id":"1449002054022","name":"24x36","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"36"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"24"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"36"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"24"}]}},{"id":"1448985622584","name":"Mounting","choice":{"id":"1466532051072","name":"1 1/2 Wooden Frame","properties":[{"id":"1518627861660","name":"MOUNTING_TYPE","value":"1.5_WOOD_FRAME"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168","name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"c4192b1a-6c19-11ee-9bc8-8f04b08aa025","contentReference":"c6af56a3-6c19-11ee-941e-e874604d0ea3","contentType":"IMAGE","fileName":"Screenshot from 2023-10-10 15-06-14.png","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":true},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454606828294","name":"SPC_TYPE_ID","value":"12"},{"id":"1454606860996","name":"SPC_MODEL_ID","value":"1"},{"id":"1454606876712","name":"SPC_VERSION_ID","value":"1"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order. DO NOT use the Production Instructions listed above. Specifications: 24 in. x 36 in. Canvas Print Package, SKU 2337, ROL05 Canvas Matte."},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"24"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"36"}]}',
                'sku' => 'ahdfjahdfjhadjfhadfadf',
            ],
            'extraconfiguratorvalue' =>
            ['customertimezone' => 'Asia/Calcutta','custom_start_date' => '2023-08-08 03:00:00','custom_end_date' => '2023-08-08 03:00:00']
        ];

        $duncResponse = [
            'successful' => true,
            'output' => [
                'imageByteStream' => 'shdfjahdfjkhasdfjhasdfjhajsdhfajsdhfjahdfsjahdsfjhasdjfhasjdfh'
            ]
        ];

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);

        $this->observerMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn($postData);

        $this->catalogMvpHelperMock->expects($this->any())->method('isDocumentPreviewApiEnable')->willReturn(false);
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isB2421984Enabled')
            ->willReturn(false);
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn('123123123123');
        $this->documentrefapimock->expects($this->any())
        ->method('getPreviewImageUrl')
        ->willReturn('imageurl');
        $this->duncApiMock->expects($this->any())
            ->method('callDuncApi')
            ->willReturn($duncResponse);
        // $this->saveImageToMediaFoldertestwithException();
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystemMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn('https://staging3.office.fedex.com/media/catalog/product/n/o/noerror.png');

        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willReturnSelf();

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");

        $this->productMock->expects($this->any())
        ->method('setStartDatePod')->willReturn($this->productMock);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");

        $this->productMock->expects($this->any())
            ->method('setEndDatePod')->willReturn($this->productMock);

        $this->assertNotNull($this->productSaveBeforeObserver->execute($this->observerMock));
    }


    /**
     * @test testExecute
     * @doesNotPerformAssertions
     */

    public function testexecutewithnull()
    {
        $postData = [
            'product' =>
            ['external_prod' => '[{"parentContentReference":"13284872036123432471801882290520602401471","contentReference":"13284872037059946631120801710221854566831","contentType":"IMAGE","fileName":"noerror.pdf","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}]'],
            'extraconfiguratorvalue' =>
            ['customertimezone' => 'Asia/Calcutta','custom_start_date' => '2023-08-08 03:00:00','custom_end_date' => '2023-08-08 03:00:00']
        ];

        $duncResponse = [
            'successful' => true,
            'output' => [
                'imageByteStream' => 'shdfjahdfjkhasdfjhasdfjhajsdhfajsdhfjahdfsjahdsfjhasdjfhasjdfh'
            ]
        ];
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);

        $this->observerMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn($postData);

            $this->catalogMvpHelperMock->expects($this->any())->method('isDocumentPreviewApiEnable')->willReturn(true);
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn('123123123123');
        $this->documentrefapimock->expects($this->any())
        ->method('curlCallForPreviewApi')
        ->willReturn('raw_imagedata');
        $this->duncApiMock->expects($this->any())
            ->method('callDuncApi')
            ->willReturn($duncResponse);
        //$this->saveImageToMediaFoldertestwithException();
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystemMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn('https://staging3.office.fedex.com/media/catalog/product/n/o/noerror.png');

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");

        $this->productMock->expects($this->any())
        ->method('setStartDatePod')->willReturn($this->productMock);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");

        $this->productMock->expects($this->any())
            ->method('setEndDatePod')->willReturn($this->productMock);

        $this->assertNotNull($this->productSaveBeforeObserver->execute($this->observerMock));
    }

    /**
     * @test testExecute
     * @doesNotPerformAssertions
     */

     public function testexecutewithnullToggleoff()
     {
         $postData = [
             'product' =>
             ['external_prod' => '[{"parentContentReference":"13284872036123432471801882290520602401471","contentReference":"13284872037059946631120801710221854566831","contentType":"IMAGE","fileName":"noerror.pdf","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}]'],
             'extraconfiguratorvalue' =>
             ['customertimezone' => 'Asia/Calcutta','custom_start_date' => '2023-08-08 03:00:00','custom_end_date' => '2023-08-08 03:00:00']
         ];

         $duncResponse = [
             'successful' => true,
             'output' => [
                 'imageByteStream' => 'shdfjahdfjkhasdfjhasdfjhajsdhfajsdhfjahdfsjahdsfjhasdjfhasjdfh'
             ]
         ];
         $this->catalogMvpHelperMock->expects($this->any())
             ->method('isMvpCtcAdminEnable')
             ->willReturn(true);

         $this->observerMock
             ->expects($this->once())
             ->method('getProduct')
             ->willReturn($this->productMock);
         $this->requestMock->expects($this->any())
             ->method('getPostValue')
             ->willReturn($postData);

             $this->catalogMvpHelperMock->expects($this->any())->method('isDocumentPreviewApiEnable')->willReturn(false);
             $this->catalogMvpHelperMock->expects($this->any())
             ->method('isB2421984Enabled')
             ->willReturn(false);
         $this->documentrefapimock->expects($this->any())
             ->method('getDocumentId')
             ->willReturn('123123123123');
         $this->documentrefapimock->expects($this->any())
         ->method('curlCallForPreviewApi')
         ->willReturn('raw_imagedata');
         $this->duncApiMock->expects($this->any())
             ->method('callDuncApi')
             ->willReturn($duncResponse);
         //$this->saveImageToMediaFoldertestwithException();
         $this->filesystemMock->expects($this->any())
             ->method('getDirectoryWrite')
             ->willReturnSelf();

         $this->filesystemMock->expects($this->any())
             ->method('getAbsolutePath')
             ->willReturn('https://staging3.office.fedex.com/media/catalog/product/n/o/noerror.png');

         $this->catalogMvpHelperMock->expects($this->any())
             ->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");

         $this->productMock->expects($this->any())
         ->method('setStartDatePod')->willReturn($this->productMock);

         $this->catalogMvpHelperMock->expects($this->any())
             ->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");

         $this->productMock->expects($this->any())
             ->method('setEndDatePod')->willReturn($this->productMock);

         $this->assertNotNull($this->productSaveBeforeObserver->execute($this->observerMock));
     }

    /**
     * @test testExecute
     */
    public function testexecutepath()
    {
        $postData = [
            'product' =>
            ['external_prod' => '{"productionContentAssociations":[],"userProductName":"Screenshot from 2023-10-10 15-06-14","id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,"instanceId":1697456992981,"proofRequired":false,"isOutSourced":false,"minDPI":"150.0","features":[{"id":"1464882763509","name":"Product Type","choice":{"id":"1464884397179","name":"Canvas Prints","properties":[{"id":"1494365340946","name":"PREVIEW_TYPE","value":"STATIC"},{"id":"1514365340957","name":"VISUALIZATION_TYPE","value":"3D"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448989268401","name":"Canvas Paper","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"ROL05"}]}},{"id":"1448981549109","name":"Size","choice":{"id":"1449002054022","name":"24x36","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"36"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"24"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"36"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"24"}]}},{"id":"1448985622584","name":"Mounting","choice":{"id":"1466532051072","name":"1 1/2 Wooden Frame","properties":[{"id":"1518627861660","name":"MOUNTING_TYPE","value":"1.5_WOOD_FRAME"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168","name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"c4192b1a-6c19-11ee-9bc8-8f04b08aa025","contentReference":"c6af56a3-6c19-11ee-941e-e874604d0ea3","contentType":"IMAGE","fileName":"Screenshot from 2023-10-10 15-06-14.png","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":true},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454606828294","name":"SPC_TYPE_ID","value":"12"},{"id":"1454606860996","name":"SPC_MODEL_ID","value":"1"},{"id":"1454606876712","name":"SPC_VERSION_ID","value":"1"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order. DO NOT use the Production Instructions listed above. Specifications: 24 in. x 36 in. Canvas Print Package, SKU 2337, ROL05 Canvas Matte."},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"24"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"36"}]}'],
            'extraconfiguratorvalue' =>
            ['customertimezone' => 'Asia/Calcutta','custom_start_date' => '2023-08-08 03:00:00']
        ];

        $duncResponse = [
            'successful' => true,
            'output' => [
                'imageByteStream' => 'shdfjahdfjkhasdfjhasdfjhajsdhfajsdhfjahdfsjahdsfjhasdjfhasjdfh'
            ]
        ];
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);

        $this->observerMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn($postData);

            $this->catalogMvpHelperMock->expects($this->any())->method('isDocumentPreviewApiEnable')->willReturn(true);
            $this->catalogMvpHelperMock->expects($this->any())
            ->method('isB2421984Enabled')
            ->willReturn(true);
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn('123123123123');
        $this->documentrefapimock->expects($this->any())
        ->method('getPreviewImageUrl')
        ->willReturn('imageurl');
        $this->duncApiMock->expects($this->any())
            ->method('callDuncApi')
            ->willReturn($duncResponse);

        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystemMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn('file:///var/www/html/staging3.office.fedex.com/pub/media/catalog/product/mvpHelper.png');

        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willReturnSelf();

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");

        $this->productMock->expects($this->any())
        ->method('setStartDatePod')->willReturn($this->productMock);

        $this->assertNotNull($this->productSaveBeforeObserver->execute($this->observerMock));
    }

    /**
     * @test testExecute
     */
    public function testexecutepathToggleOff()
    {
        $postData = [
            'product' =>
            ['external_prod' => '{"fxoProductInstance": {"productConfig": {
                            "product":{"contentAssociations":[{"parentContentReference":"13284872036123432471801882290520602401471","contentReference":"13284872037059946631120801710221854566831","contentType":"IMAGE","fileName":"noerror.png","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}]}
                        } }}'],
            'extraconfiguratorvalue' =>
            ['customertimezone' => 'Asia/Calcutta','custom_start_date' => '2023-08-08 03:00:00','custom_end_date' => '2023-08-08 03:00:00']
        ];

        $duncResponse = [
            'successful' => true,
            'output' => [
                'imageByteStream' => 'shdfjahdfjkhasdfjhasdfjhajsdhfajsdhfjahdfsjahdsfjhasdjfhasjdfh'
            ]
        ];
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);

        $this->observerMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn($postData);

            $this->catalogMvpHelperMock->expects($this->any())->method('isDocumentPreviewApiEnable')->willReturn(false);
        
            $this->catalogMvpHelperMock->expects($this->any())
            ->method('isB2421984Enabled')
            ->willReturn(false);
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn('123123123123');
        $this->documentrefapimock->expects($this->any())
        ->method('getPreviewImageUrl')
        ->willReturn('imageurl');
        $this->duncApiMock->expects($this->any())
            ->method('callDuncApi')
            ->willReturn($duncResponse);

        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystemMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn('file:///var/www/html/staging3.office.fedex.com/pub/media/catalog/product/mvpHelper.png');

        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willReturnSelf();

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");

        $this->productMock->expects($this->any())
        ->method('setStartDatePod')->willReturn($this->productMock);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");

        $this->productMock->expects($this->any())
            ->method('setEndDatePod')->willReturn($this->productMock);

        $this->assertNotNull($this->productSaveBeforeObserver->execute($this->observerMock));
    }

    /**
     * @test testExecute
     */
    public function testexecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $postData = [
            'product' =>
            ['external_prod' => '{"productionContentAssociations":[],"userProductName":"Screenshot from 2023-10-10 15-06-14","id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,"instanceId":1697456992981,"proofRequired":false,"isOutSourced":false,"minDPI":"150.0","features":[{"id":"1464882763509","name":"Product Type","choice":{"id":"1464884397179","name":"Canvas Prints","properties":[{"id":"1494365340946","name":"PREVIEW_TYPE","value":"STATIC"},{"id":"1514365340957","name":"VISUALIZATION_TYPE","value":"3D"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448989268401","name":"Canvas Paper","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"ROL05"}]}},{"id":"1448981549109","name":"Size","choice":{"id":"1449002054022","name":"24x36","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"36"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"24"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"36"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"24"}]}},{"id":"1448985622584","name":"Mounting","choice":{"id":"1466532051072","name":"1 1/2 Wooden Frame","properties":[{"id":"1518627861660","name":"MOUNTING_TYPE","value":"1.5_WOOD_FRAME"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168","name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"c4192b1a-6c19-11ee-9bc8-8f04b08aa025","contentReference":"c6af56a3-6c19-11ee-941e-e874604d0ea3","contentType":"IMAGE","fileName":"Screenshot from 2023-10-10 15-06-14.png","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":true},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454606828294","name":"SPC_TYPE_ID","value":"12"},{"id":"1454606860996","name":"SPC_MODEL_ID","value":"1"},{"id":"1454606876712","name":"SPC_VERSION_ID","value":"1"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order. DO NOT use the Production Instructions listed above. Specifications: 24 in. x 36 in. Canvas Print Package, SKU 2337, ROL05 Canvas Matte."},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"24"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"36"}]}']
        ];

        $duncResponse = [
            'successful' => true,
            'output' => [
                'imageByteStream' => 'shdfjahdfjkhasdfjhasdfjhajsdhfajsdhfjahdfsjahdsfjhasdjfhasjdfh'
            ]
        ];
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);

        $this->observerMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->requestMock->expects($this->any())
            ->method('getPostValue')->willThrowException($exception);

            $this->catalogMvpHelperMock->expects($this->any())->method('isDocumentPreviewApiEnable')->willReturn(true);
            $this->documentrefapimock->expects($this->any())
                ->method('getDocumentId')
                ->willReturn('123123123123');
            $this->documentrefapimock->expects($this->any())
            ->method('curlCallForPreviewApi')
            ->willReturn('raw_imagedata');
        $this->duncApiMock->expects($this->any())
            ->method('callDuncApi')
            ->willReturn($duncResponse);

        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystemMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn(true);

        $this->filesystemMock->expects($this->any())
            ->method('writeFile')->willThrowException($exception);

        $this->assertNotNull($this->productSaveBeforeObserver->execute($this->observerMock));
    }
    /**
     * @test testExecute
     */
    public function testexecuteWithExceptionToggleOff()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $postData = [
            'product' =>
            ['external_prod' => '{"productionContentAssociations":[],"userProductName":"Screenshot from 2023-10-10 15-06-14","id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,"instanceId":1697456992981,"proofRequired":false,"isOutSourced":false,"minDPI":"150.0","features":[{"id":"1464882763509","name":"Product Type","choice":{"id":"1464884397179","name":"Canvas Prints","properties":[{"id":"1494365340946","name":"PREVIEW_TYPE","value":"STATIC"},{"id":"1514365340957","name":"VISUALIZATION_TYPE","value":"3D"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448989268401","name":"Canvas Paper","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"ROL05"}]}},{"id":"1448981549109","name":"Size","choice":{"id":"1449002054022","name":"24x36","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"36"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"24"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"36"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"24"}]}},{"id":"1448985622584","name":"Mounting","choice":{"id":"1466532051072","name":"1 1/2 Wooden Frame","properties":[{"id":"1518627861660","name":"MOUNTING_TYPE","value":"1.5_WOOD_FRAME"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168","name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"c4192b1a-6c19-11ee-9bc8-8f04b08aa025","contentReference":"c6af56a3-6c19-11ee-941e-e874604d0ea3","contentType":"IMAGE","fileName":"Screenshot from 2023-10-10 15-06-14.png","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":true},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454606828294","name":"SPC_TYPE_ID","value":"12"},{"id":"1454606860996","name":"SPC_MODEL_ID","value":"1"},{"id":"1454606876712","name":"SPC_VERSION_ID","value":"1"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order. DO NOT use the Production Instructions listed above. Specifications: 24 in. x 36 in. Canvas Print Package, SKU 2337, ROL05 Canvas Matte."},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"24"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"36"}]}']
        ];

        $duncResponse = [
            'successful' => true,
            'output' => [
                'imageByteStream' => 'shdfjahdfjkhasdfjhasdfjhajsdhfajsdhfjahdfsjahdsfjhasdjfhasjdfh'
            ]
        ];
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);

        $this->observerMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->requestMock->expects($this->any())
            ->method('getPostValue')->willThrowException($exception);

            $this->catalogMvpHelperMock->expects($this->any())->method('isDocumentPreviewApiEnable')->willReturn(false);
            $this->catalogMvpHelperMock->expects($this->any())
            ->method('isB2421984Enabled')
            ->willReturn(false);
            $this->documentrefapimock->expects($this->any())
                ->method('getDocumentId')
                ->willReturn('123123123123');
            $this->documentrefapimock->expects($this->any())
            ->method('getPreviewImageUrl')
            ->willReturn('imageurl');
        $this->duncApiMock->expects($this->any())
            ->method('callDuncApi')
            ->willReturn($duncResponse);

        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystemMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn(true);

        $this->filesystemMock->expects($this->any())
            ->method('writeFile')->willThrowException($exception);

        $this->assertNotNull($this->productSaveBeforeObserver->execute($this->observerMock));
    }


    /**
     * @test testExecute
     */
    public function testexecuteWithSaveException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $postData = [
            'product' =>
            ['external_prod' => '{"productionContentAssociations":[],"userProductName":"Screenshot from 2023-10-10 15-06-14","id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,"instanceId":1697456992981,"proofRequired":false,"isOutSourced":false,"minDPI":"150.0","features":[{"id":"1464882763509","name":"Product Type","choice":{"id":"1464884397179","name":"Canvas Prints","properties":[{"id":"1494365340946","name":"PREVIEW_TYPE","value":"STATIC"},{"id":"1514365340957","name":"VISUALIZATION_TYPE","value":"3D"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448989268401","name":"Canvas Paper","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"ROL05"}]}},{"id":"1448981549109","name":"Size","choice":{"id":"1449002054022","name":"24x36","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"36"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"24"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"36"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"24"}]}},{"id":"1448985622584","name":"Mounting","choice":{"id":"1466532051072","name":"1 1/2 Wooden Frame","properties":[{"id":"1518627861660","name":"MOUNTING_TYPE","value":"1.5_WOOD_FRAME"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168","name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"c4192b1a-6c19-11ee-9bc8-8f04b08aa025","contentReference":"c6af56a3-6c19-11ee-941e-e874604d0ea3","contentType":"IMAGE","fileName":"Screenshot from 2023-10-10 15-06-14.png","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":true},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454606828294","name":"SPC_TYPE_ID","value":"12"},{"id":"1454606860996","name":"SPC_MODEL_ID","value":"1"},{"id":"1454606876712","name":"SPC_VERSION_ID","value":"1"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order. DO NOT use the Production Instructions listed above. Specifications: 24 in. x 36 in. Canvas Print Package, SKU 2337, ROL05 Canvas Matte."},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"24"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"36"}]}'],
            'extraconfiguratorvalue' =>
            ['customertimezone' => 'Asia/Calcutta','custom_start_date' => '2023-08-08 03:00:00','custom_end_date' => '2023-08-08 03:00:00']
        ];

        $duncResponse = [
            'successful' => true,
            'output' => [
                'imageByteStream' => 'shdfjahdfjkhasdfjhasdfjhajsdhfajsdhfjahdfsjahdsfjhasdjfhasjdfh'
            ]
        ];

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);

        $this->observerMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn($postData);
            $this->catalogMvpHelperMock->expects($this->any())->method('isDocumentPreviewApiEnable')->willReturn(true);
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn('123123123123');
        $this->documentrefapimock->expects($this->any())
        ->method('curlCallForPreviewApi')
        ->willReturn('raw_imagedata');
        $this->duncApiMock->expects($this->any())
            ->method('callDuncApi')
            ->willReturn($duncResponse);

        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystemMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn('https://staging3.office.fedex.com/media/catalog/product/n/o/noerror.png');

        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willReturnSelf();

        $this->saveImageToMediaFoldertestwithException();

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");

        $this->productMock->expects($this->any())
        ->method('setStartDatePod')->willReturn($this->productMock);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");

        $this->productMock->expects($this->any())
            ->method('setEndDatePod')->willReturn($this->productMock);

        $this->assertNotNull($this->productSaveBeforeObserver->execute($this->observerMock));
    }

    /**
     * @test testExecute
     */
    public function testexecuteWithSaveExceptionToggleOff()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $postData = [
            'product' =>
            ['external_prod' => '{"productionContentAssociations":[],"userProductName":"Screenshot from 2023-10-10 15-06-14","id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,"instanceId":1697456992981,"proofRequired":false,"isOutSourced":false,"minDPI":"150.0","features":[{"id":"1464882763509","name":"Product Type","choice":{"id":"1464884397179","name":"Canvas Prints","properties":[{"id":"1494365340946","name":"PREVIEW_TYPE","value":"STATIC"},{"id":"1514365340957","name":"VISUALIZATION_TYPE","value":"3D"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448989268401","name":"Canvas Paper","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"ROL05"}]}},{"id":"1448981549109","name":"Size","choice":{"id":"1449002054022","name":"24x36","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"36"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"24"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"36"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"24"}]}},{"id":"1448985622584","name":"Mounting","choice":{"id":"1466532051072","name":"1 1/2 Wooden Frame","properties":[{"id":"1518627861660","name":"MOUNTING_TYPE","value":"1.5_WOOD_FRAME"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168","name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"c4192b1a-6c19-11ee-9bc8-8f04b08aa025","contentReference":"c6af56a3-6c19-11ee-941e-e874604d0ea3","contentType":"IMAGE","fileName":"Screenshot from 2023-10-10 15-06-14.png","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":true},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454606828294","name":"SPC_TYPE_ID","value":"12"},{"id":"1454606860996","name":"SPC_MODEL_ID","value":"1"},{"id":"1454606876712","name":"SPC_VERSION_ID","value":"1"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order. DO NOT use the Production Instructions listed above. Specifications: 24 in. x 36 in. Canvas Print Package, SKU 2337, ROL05 Canvas Matte."},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"24"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"36"}]}'],
            'extraconfiguratorvalue' =>
            ['customertimezone' => 'Asia/Calcutta','custom_start_date' => '2023-08-08 03:00:00','custom_end_date' => '2023-08-08 03:00:00']
        ];

        $duncResponse = [
            'successful' => true,
            'output' => [
                'imageByteStream' => 'shdfjahdfjkhasdfjhasdfjhajsdhfajsdhfjahdfsjahdsfjhasdjfhasjdfh'
            ]
        ];

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);

        $this->observerMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn($postData);
            $this->catalogMvpHelperMock->expects($this->any())->method('isDocumentPreviewApiEnable')->willReturn(false);
            $this->catalogMvpHelperMock->expects($this->any())
            ->method('isB2421984Enabled')
            ->willReturn(true);
        $this->documentrefapimock->expects($this->any())
            ->method('getDocumentId')
            ->willReturn('123123123123');
        $this->documentrefapimock->expects($this->any())
        ->method('getPreviewImageUrl')
        ->willReturn('imageurl');
        $this->duncApiMock->expects($this->any())
            ->method('callDuncApi')
            ->willReturn($duncResponse);

        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystemMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn('https://staging3.office.fedex.com/media/catalog/product/n/o/noerror.png');

        $this->filesystemMock->expects($this->any())
            ->method('writeFile')
            ->willReturnSelf();

        $this->saveImageToMediaFoldertestwithException();

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");

        $this->productMock->expects($this->any())
        ->method('setStartDatePod')->willReturn($this->productMock);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('convertTimeIntoPSTWithCustomerTimezone')->willReturn("2023-08-08 12:00:00");

        $this->productMock->expects($this->any())
            ->method('setEndDatePod')->willReturn($this->productMock);

        $this->assertNotNull($this->productSaveBeforeObserver->execute($this->observerMock));
    }

    /* toggle off case */
    public function testexecuteWithToggleOff()
    {
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(false);

        $this->assertNotNull($this->productSaveBeforeObserver->execute($this->observerMock));
    }

    /*save image to directory with exception */

    public function saveImageToMediaFoldertestwithException()
    {

        $imageData  = 'shdfjahdfjkhasdfjhasdfjhajsdhfajsdhfjahdfsjahdsfjhasdjfhasjdfh';
        $imageName = 'logo.png';

        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->catalogMvpHelperMock->expects($this->any())
        ->method('isB2421984Enabled')
        ->willReturn(true);

        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystemMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn('https://staging3.office.fedex.com/media/catalog/product/n/o/noerror.png');

        $this->fileDriverMock->expects($this->any())
            ->method('fileGetContents')
            ->willReturn(true);

        $this->filesystemMock->expects($this->any())
            ->method('writeFile')->willThrowException($exception);

    }

    /*save image to directory with exception */

    public function saveImageToMediaFoldertest()
    {

        $imageData  = 'shdfjahdfjkhasdfjhasdfjhajsdhfajsdhfjahdfsjahdsfjhasdjfhasjdfh';
        $imageName = 'logo.png';

        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystemMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn('https://staging3.office.fedex.com/media/catalog/product/n/o/noerror.png');

        $this->filesystemMock->expects($this->any())
            ->method('writeFile')->willThrowException($exception);

        $this->assertNull($this->productSaveBeforeObserver->saveImageToMediaFolder($imageData, $imageName));
    }

    public function saveImageToMediaFoldertestToggleOff()
    {

        $imageData  = 'shdfjahdfjkhasdfjhasdfjhajsdhfajsdhfjahdfsjahdsfjhasdjfhasjdfh';
        $imageName = 'logo.png';

        $this->catalogMvpHelperMock->expects($this->any())
        ->method('isB2421984Enabled')
        ->willReturn(false);

        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystemMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn('https://staging3.office.fedex.com/media/catalog/product/n/o/noerror.png');

        $this->filesystemMock->expects($this->any())
            ->method('writeFile')->willThrowException($exception);

        $this->assertNull($this->productSaveBeforeObserver->saveImageToMediaFolder($imageData, $imageName));
    }

    /**
     * Test that published attribute is set to 1 when both toggles are enabled and start date <= current time
     */
    public function testExecuteWithPublishedTogglesBothEnabled()
    {
        $postData = [
            'product' => [
                'sku' => 'test-product',
                'name' => 'Test Product',
                'shared_catalog' => '1'
            ]
        ];

        $this->catalogMvpHelperMock->expects($this->once())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);

        $this->observerMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn($postData);

        $this->productMock->expects($this->once())
            ->method('getCategoryIds')
            ->willReturn([1, 2]);

        $this->folderPermissionMock->expects($this->once())
            ->method('getCustomerGroupIds')
            ->willReturn([]);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isD216406Enabled')
            ->willReturn(false);

        $this->productMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->productMock->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn('2023-12-01 10:00:00');

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('toggleD202288')
            ->willReturn(false);

        $this->toggleConfigMock->expects($this->exactly(2))
            ->method('getToggleConfigValue')
            ->withConsecutive(
                ['hawks_published_flag_indexing'],
                ['TechTitans_D_238087_fix_for_Publish_Toggle_Start_Date']
            )
            ->willReturnOnConsecutiveCalls(true, true);

        $currentTime = '2023-12-01 10:00:00';
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('getCurrentPSTDateAndTime')
            ->willReturn($currentTime);

        $this->productMock->expects($this->once())
            ->method('getStartDatePod')
            ->willReturn('2023-11-30 10:00:00'); // Past date

        $this->productMock->expects($this->once())
            ->method('setPublished')
            ->with(1);

        $result = $this->productSaveBeforeObserver->execute($this->observerMock);
        
        $this->assertEquals($this->productSaveBeforeObserver, $result);
        $this->assertInstanceOf(
            \Fedex\CatalogMvp\Observer\ProductSaveBeforeObserver::class, 
            $result
        );
        
        $this->assertNotNull($result);
        $this->assertTrue(method_exists($result, 'execute'));
        $this->assertTrue(method_exists($result, 'saveImageToMediaFolder'));
    }

    /**
     * Test that published attribute is set to 1 when both toggles are enabled and start date is null
     */
    public function testExecuteWithPublishedTogglesEnabledNullStartDate()
    {
        $postData = [
            'product' => [
                'sku' => 'test-product',
                'name' => 'Test Product',
                'shared_catalog' => '1'
            ]
        ];

        $this->catalogMvpHelperMock->expects($this->once())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);

        $this->observerMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn($postData);

        $this->productMock->expects($this->once())
            ->method('getCategoryIds')
            ->willReturn([1, 2]);

        $this->folderPermissionMock->expects($this->once())
            ->method('getCustomerGroupIds')
            ->willReturn([]);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isD216406Enabled')
            ->willReturn(false);

        $this->productMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->productMock->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn('2023-12-01 10:00:00');

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('toggleD202288')
            ->willReturn(false);

        $this->toggleConfigMock->expects($this->exactly(2))
            ->method('getToggleConfigValue')
            ->withConsecutive(
                ['hawks_published_flag_indexing'],
                ['TechTitans_D_238087_fix_for_Publish_Toggle_Start_Date']
            )
            ->willReturnOnConsecutiveCalls(true, true);

        $currentTime = '2023-12-01 10:00:00';
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('getCurrentPSTDateAndTime')
            ->willReturn($currentTime);

        $this->productMock->expects($this->once())
            ->method('getStartDatePod')
            ->willReturn(null); 

        $this->productMock->expects($this->once())
            ->method('setPublished')
            ->with(1);

        $result = $this->productSaveBeforeObserver->execute($this->observerMock);
        $this->assertNotNull($result);
    }

    /**
     * Test that published attribute is NOT set when toggles are disabled
     */
    public function testExecuteWithPublishedTogglesDisabled()
    {
        $postData = [
            'product' => [
                'sku' => 'test-product',
                'name' => 'Test Product',
                'shared_catalog' => '1'
            ]
        ];

        $this->catalogMvpHelperMock->expects($this->once())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);

        $this->observerMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn($postData);

        $this->productMock->expects($this->once())
            ->method('getCategoryIds')
            ->willReturn([1, 2]);

        $this->folderPermissionMock->expects($this->once())
            ->method('getCustomerGroupIds')
            ->willReturn([]);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isD216406Enabled')
            ->willReturn(false);

        $this->productMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->productMock->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn('2023-12-01 10:00:00');

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('toggleD202288')
            ->willReturn(false);

        // Mock toggles disabled
        $this->toggleConfigMock->expects($this->exactly(2))
            ->method('getToggleConfigValue')
            ->withConsecutive(
                ['hawks_published_flag_indexing'],
                ['TechTitans_D_238087_fix_for_Publish_Toggle_Start_Date']
            )
            ->willReturnOnConsecutiveCalls(false, false);

        $this->productMock->expects($this->never())
            ->method('setPublished');

        $result = $this->productSaveBeforeObserver->execute($this->observerMock);
        $this->assertNotNull($result);
    }

    /**
     * Test that published attribute is NOT set when start date is in future
     */
    public function testExecuteWithPublishedTogglesFutureStartDate()
    {
        $postData = [
            'product' => [
                'sku' => 'test-product',
                'name' => 'Test Product',
                'shared_catalog' => '1'
            ]
        ];

        $this->catalogMvpHelperMock->expects($this->once())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);

        $this->observerMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn($postData);

        $this->productMock->expects($this->once())
            ->method('getCategoryIds')
            ->willReturn([1, 2]);

        $this->folderPermissionMock->expects($this->once())
            ->method('getCustomerGroupIds')
            ->willReturn([]);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isD216406Enabled')
            ->willReturn(false);

        $this->productMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->productMock->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn('2023-12-01 10:00:00');

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('toggleD202288')
            ->willReturn(false);

        $this->toggleConfigMock->expects($this->exactly(2))
            ->method('getToggleConfigValue')
            ->withConsecutive(
                ['hawks_published_flag_indexing'],
                ['TechTitans_D_238087_fix_for_Publish_Toggle_Start_Date']
            )
            ->willReturnOnConsecutiveCalls(true, true);

        $currentTime = '2023-12-01 10:00:00';
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('getCurrentPSTDateAndTime')
            ->willReturn($currentTime);

        $this->productMock->expects($this->once())
            ->method('getStartDatePod')
            ->willReturn('2023-12-02 10:00:00'); // Future date

        $this->productMock->expects($this->never())
            ->method('setPublished');

        $result = $this->productSaveBeforeObserver->execute($this->observerMock);
        $this->assertNotNull($result);
    }
}
