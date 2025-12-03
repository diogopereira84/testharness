<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Helper;

use Fedex\SharedCatalogCustomization\Helper\ManageCatalogItems;
use Magento\Company\Model\ResourceModel\Company\Collection;
use Magento\Company\Model\ResourceModel\Company\CollectionFactory;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterfaceFactory;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Registry;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Quote\Model\Quote\Item;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueCleanupProcess;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueCleanupProcessFactory;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Api\Data\ProductInterface;
use Fedex\CatalogMvp\Model\DocRefMessage;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Fedex\FXOCMConfigurator\ViewModel\FXOCMHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\Filesystem\Directory\Write;

class ManageCatalogItemsTest extends TestCase
{
    protected $attributeSetCollection;
    protected $storeMock;
    protected $productMock;
    protected $fileSystemMock;
    /**
     * @var (\Magento\Framework\Filesystem\DriverInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fileDriverMock;
    protected $fileMimeMock;
    protected $catalogSyncQueueProcessMock;
    protected $catalogSyncQueueCleanupProcessMock;
    protected $catalogSyncQueueCleanupProcessFactoryMock;
    protected $categoryLinkManagementInterfaceMock;
    /**
     * @var (\Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $itemDeleterMock;
    /**
     * @var (\Magento\Catalog\Model\ResourceModel\Product\Gallery & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $galleryMock;
    protected $curlMock;
    protected $punchoutHelperData;
    /**
     * @var (\Fedex\CatalogMvp\Model\DocRefMessage & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $docRefMessageMock;
    /**
     * @var (\Fedex\SharedCatalogCustomization\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $dataMock;
    /**
     * @var (\Fedex\Punchout\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $punchoutHelperMock;
    /**
     * @var (\Magento\Company\Model\ResourceModel\Company\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $sharedCatalogCollection;
    /**
     * @var (\Magento\Company\Model\ResourceModel\Company\CollectionFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $sharedCatalogCollectionFactory;
    /**
     * @var (\Fedex\SharedCatalogCustomization\Test\Unit\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $catalogSyncQueueHelperMock;
    /**
     * @var (\Magento\Eav\Api\Data\AttributeSetInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $attributeSetMock;
    /**
     * @var (\Magento\Framework\Registry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $registryMock;
    protected $selectMock;
    protected $connectionMock;
    /**
     * @var (\Magento\Catalog\Model\Product\Gallery\Processor & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $processorMock;
    protected $fXOCMHelperMock;
    protected $productInterfaceMock;
    protected $catalogMvpMock;
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_FAILED = 'failed';
    public const STATUS_COMPLETED = 'completed';
    public const TAZ_TOKEN = '{
        "access_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2MzM0Njc5MzAsImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6IjAyOTk4ODY3LWYyNDgtNGFjOC1hYjNhLWQwMjcwZjg5MzE0YSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.U0nBIQIB0QvTYTdqYBle7kfwApRciOn3nKEDiB_bk_y327jYHduPT0Jo2rKODZgvc7jYRNh1n9d8oxtrA-VaZjYtoE3WM4dN7gNBFC2Ch1Xx2B62iZ23IC3UfheQ00DsnjaUbCqL6LEyOaW79sbf_rT8icqBYgboyZ_z9E1ODduMV9aQfkpqPOKw9CqFiXnNdgXhui0BTKMRhKToYD7mHS_fVolklFSVVOJBa8T5Q__5AvmGmTdvijwtUxIg874eiXy-3HJJ6hrrMsJkJVSWvf5JCKW4MfTJxW2WFl514GLdkQsHCd_SI_8ceTZd_wPdoGZVGVx71N3svro0zXPh5986R0l7pzD4U0fAWfxM8VmmB8NRE6Ix-vGKXeJPyUYckMfA29XR87Tj7gaVXB1tDaCWNSj7EyshFk-UPEsxtCT0Z1JxWo-SwQy1gUpsu8PJM6UdzpqYSh-fNE8dRb_lt145fAxlhzZNtjAMGLlTQ2blKbioi0_yecumEPZZJHmFUXc6BOWHSWj9m-ViP_WMtRVFjSaXb8YT0tFlK5pzKudYLFI8lKI0hdK5L0afT1-kEMLxScVYrPD3c10n1zK4tOGH0NqnEVxXtxjbm1Z50HYtpK_GOOLAF_a9vEpKmif5DU6kgdMBc9IXDWhZbowXai9hQ1m3NBBM5GZKuiK0kv0",
        "token_type": "bearer",
        "expires_in": 43199,
        "scope": "taz.clients:write",
        "iss": "taz",
        "jti": "02998867-f248-4ac8-ab3a-d0270f89314a"
    }';

    public const ATTRIBUTE_SET_NAME = 'PrintOnDemand';
    public const NEW_FILE_NAME = 'staging3.office.fedex.com/pub/media/tmp/PreviewServletgetImage=SLP_20210717_12588730764_1.jpeg';
    public const MIME_INFO = 'image/jpeg';
    public const FILE_INFO = ['basename' => 'PreviewServletgetImage=SLP_20210717_12588730764_1.jpeg'];
    public const UPDATE_PRODUCT_JSON = '{
        "id":"e9dc8dad-f438-4ff1-866c-4177a3055f1d",
        "version":"SLP_20210717_12588730764_1",
        "name":"Poster Prints",
        "description":"This is Test doc by Attri",
        "createdBy":"Admin Admin",
        "creationTime":"2021-07-17T20:59:41Z",
        "modifiedBy":"Admin Admin",
        "modifiedTime":"2021-07-17T20:59:41Z",
        "links":[
            {
                "href":"...\/v2\/catalogProducts\/e9dc8dad-f438-4ff1-866c-4177a3055f1d",
                "rel":"detail"
            },
            {
                "href":"https://printonline6.dmz.fedex.com/SLP_20210717_12588730764_1.jpeg",
                "rel":"thumbnail"
            }
        ],
        "type":"PRODUCT",
        "catalogProductSummary":{
            "productRateTotal":{
                "currency":"USD",
                "price":"73.50"
            },
            "customizable":false,
            "availability":{
                "available":true,
                "dateRange":{
                    "startDateTime":"2021-07-17T20:53:17Z"
                }
            },
            "editable":true
        },
        "existingjsonData":{
            "id":1508784838900,
            "version":0,
            "name":"Legacy Catalog",
            "qty":1,"priceable":true,
            "proofRequired":false,
            "catalogReference":{
                "catalogProductId":"e9dc8dad-f438-4ff1-866c-4177a3055f1d",
                "version":"SLP_20210717_12588730764_2"
            },
            "isOutSourced":false,
            "instanceId":"0"
        },
        "productId":"801"
    }';

    public const EXTERNAL_PROD = [
        'id' => 1508784838900,
        'version' => 0,
        'name' => 'Legacy Catalog',
        'qty' => 1,
        'priceable' => true,
        'proofRequired' => false,
        'isOutSourced' => false,
        'instanceId' => '0',
    ];

    /**
     * @var CollectionFactory|MockObject
     */
    protected $attributeSetCollectionFactoryMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $configInterfaceMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var EncryptorInterface|MockObject
     */
    protected $encryptorMock;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    protected $productRepositoryInterfaceMock;

    /**
     * @var ProductFactory|MockObject
     */
    protected $productFactoryMock;

    /**
     * @var ResourceConnection|MockObject
     */
    protected $resourceConnection;

    /**
     * @var Mysql|MockObject
     */
    protected $mysqlAdapter;

    /**
     * @var DirectoryList|MockObject
     */
    protected $dirMock;

    /**
     * @var \Magento\Framework\Filesystem\Io\File|MockObject
     */
    protected $fileMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var CatalogSyncQueueProcessFactory|MockObject
     */
    protected $catalogSyncQueueProcessFactoryMock;

    /**
     * @var CatalogSyncQueueCleanupProcessFactory
     */
    protected $catalogSyncQueueCleanupProcessFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory|MockObject
     */
    protected $productCollectionFactoryMock;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection|MockObject
     */
    protected $productCollectionMock;

    /**
     * @var \Magento\Catalog\Model\CategoryLinkRepository|MockObject
     */
    protected $categoryLinkRepositoryMock;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $itemCollectionFactoryMock;

    /**
     * @var \Magento\Quote\Api\Data\CartInterfaceFactory|MockObject
     */
    protected $cartFactoryMock;

    /**
     * @var \Magento\Quote\Model\Quote|MockObject
     */
    protected $cartMock;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Item\Collection|MockObject
     */
    protected $itemCollectionMock;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product|MockObject
     */
    protected $productResourceModel;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface|MockObject
     */
    protected $publisherMock;

    /**
     * @var \Fedex\SharedCatalogCustomization\Api\MessageInterface|MockObject
     */
    protected $messageMock;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var helperData|MockObject
     */
    protected $helperData;

    /**
     * @var ManageCatalogItems|MockObject
     */
    protected $helperManageCatalogItems;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfigMock;

    /**
     * @var CategoryLinkManagementInterface $categoryLinkManagementInterface
     */
    protected $categoryLinkManagementInterface;
    private MockObject|Product $product;
    private DataObject|MockObject $productModel;
    private Write|MockObject $dirWriteMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->attributeSetCollection = $this
            ->getMockBuilder(\Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'addFieldToSelect', 'getSize', 'getFirstItem'])
            ->getMock();
        $this->attributeSetCollectionFactoryMock = $this
            ->getMockBuilder(\Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();
        $this->configInterfaceMock = $this
            ->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this
            ->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();
        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseUrl'])
            ->getMock();
        $this->encryptorMock = $this
            ->getMockBuilder(\Magento\Framework\Encryption\EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRepositoryInterfaceMock = $this
            ->getMockBuilder(\Magento\Catalog\Api\ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productFactoryMock = $this
            ->getMockBuilder(\Magento\Catalog\Model\ProductFactory::class)
            ->setMethods(['create',
                'load',
                'getMediaGalleryImages',
                'getMediaGalleryEntries',
                'addImageToMediaGallery',
                'setName',
                'setTypeId',
                'setAttributeSetId',
                'setSku',
                'setProductLocationBranchNumber',
                'setShortDescription',
                'setCategoryIds',
                'setStatus',
                'setCustomizationFields',
                'setStockData',
                'setPrice',
                'setDltThreshold',
                'setWebsiteIds',
                'setVisibility',
                'setCustomizable',
                'setUrlKey',
                'getSku',
                'setExternalProd',
                'setStoreId',
                'save'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productMock = $this
            ->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'addImageToMediaGallery',
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
                'save'
            ])->getMock();
        $this->resourceConnection = $this
            ->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->setMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->mysqlAdapter = $this->getMockBuilder(\Magento\Framework\DB\Adapter\Pdo\Mysql::class)
            ->setMethods(['from', 'join', 'where', 'fetchOne', 'fetchAll'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->dirMock = $this
            ->getMockBuilder(\Magento\Framework\App\Filesystem\DirectoryList::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPath'])
            ->getMockForAbstractClass();
        $this->fileMock = $this
            ->getMockBuilder(\Magento\Framework\Filesystem\Io\File::class)
            ->setMethods(['getPathInfo', 'fileExists', 'rm'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->fileSystemMock = $this
            ->getMockBuilder(\Magento\Framework\Filesystem::class)
            ->setMethods(['getDirectoryWrite', 'writeFile', 'stat'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->fileDriverMock = $this
            ->getMockBuilder(\Magento\Framework\Filesystem\DriverInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fileMimeMock = $this
            ->getMockBuilder(\Magento\Framework\File\Mime::class)
            ->setMethods(['getMimeType'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this
            ->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogSyncQueueProcessMock = $this
            ->getMockBuilder(\Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueProcess::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setId',
                'getId',
                'setCatalogSyncQueueId',
                'setSharedCatalogId',
                'setCategoryId',
                'setJsonData',
                'setCatalogType',
                'setActionType',
                'setErrorMsg',
                'setStatus',
                'save'
            ])->getMock();
        $this->catalogSyncQueueProcessFactoryMock = $this
            ->getMockBuilder(\Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueProcessFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $this->catalogSyncQueueCleanupProcessMock = $this
            ->getMockBuilder(CatalogSyncQueueCleanupProcess::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setCatalogSyncQueueId',
                'setSharedCatalogId',
                'setCategoryId',
                'setJsonData',
                'setProductId',
                'setSku',
                'setCatalogType',
                'setErrorMsg',
                'setStatus',
                'save'
            ])->getMock();

        $this->catalogSyncQueueCleanupProcessFactoryMock = $this
            ->getMockBuilder(CatalogSyncQueueCleanupProcessFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $this->productCollectionFactoryMock = $this
            ->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();
        $this->productCollectionMock = $this
            ->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'load',
                    'getStoreId',
                    'addAttributeToSelect',
                    'addAttributeToFilter',
                    'addCategoriesFilter',
                    'getSize'
                ]
            )->getMockForAbstractClass();
        $this->categoryLinkRepositoryMock = $this
            ->getMockBuilder(\Magento\Catalog\Model\CategoryLinkRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryLinkManagementInterfaceMock = $this->getMockBuilder(CategoryLinkManagementInterface::class)
            ->setMethods(['assignProductToCategories'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->itemCollectionFactoryMock = $this
            ->getMockBuilder(\Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();
        $this->itemCollectionMock = $this
            ->getMockBuilder(\Magento\Quote\Model\ResourceModel\Quote\Item\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'setQuote', 'getItems'])
            ->getMock();
        $this->cartFactoryMock = $this
            ->getMockBuilder(\Magento\Quote\Api\Data\CartInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();
        $this->cartMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemDeleterMock = $this
            ->getMockBuilder(\Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->galleryMock = $this
            ->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Gallery::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productResourceModel = $this
            ->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product::class)
            ->setMethods(['save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->curlMock = $this
            ->getMockBuilder(\Magento\Framework\HTTP\Client\Curl::class)
            ->setMethods(['getBody', 'SetOptions', 'post'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->punchoutHelperData = $this
            ->getMockBuilder(\Fedex\Punchout\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->publisherMock = $this
            ->getMockBuilder(\Magento\Framework\MessageQueue\PublisherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageMock = $this
            ->getMockBuilder(\Fedex\SharedCatalogCustomization\Api\MessageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->docRefMessageMock = $this->getMockBuilder(DocRefMessage::class)->disableOriginalConstructor()->getMock();
        $this->dataMock = $this->getMockBuilder(\Fedex\SharedCatalogCustomization\Helper\Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProductApiUrl', 'getCategoryDetails', 'getCompanyId'])
            ->getMockForAbstractClass();
        $this->punchoutHelperMock = $this->createMock(\Fedex\Punchout\Helper\Data::class);
        $this->sharedCatalogCollection = $this->createPartialMock(
            Collection::class,
            ['load', 'addFieldToFilter']
        );
        $this->sharedCatalogCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->catalogSyncQueueHelperMock = $this
            ->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributeSetMock = $this->getMockBuilder(\Magento\Eav\Api\Data\AttributeSetInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExternalProd'])
            ->getMockForAbstractClass();
        $this->registryMock = $this
            ->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue', 'getToggleConfig'])
            ->getMock();

        $this->processorMock = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->setMethods(['addImage'])
            ->getMock();
        $this->fXOCMHelperMock = $this->getMockBuilder(FXOCMHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productInterfaceMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getExternalProd',
                'getId',
                'getSku',
                'getCategoryIds',
                'get',
                'setStoreId',
                'setStatus',
                'save',
                'setVisibility',
                'getIsDocumentExpire'
            ])
            ->getMockForAbstractClass();

            $this->catalogMvpMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isProductPodEditAbleById',
            ])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->helperManageCatalogItems = $this->objectManager->getObject(
            ManageCatalogItems::class,
            [
                'attributeSetCollectionFactory' => $this->attributeSetCollectionFactoryMock,
                'configInterface' => $this->configInterfaceMock,
                'storeManager' => $this->storeManagerMock,
                'encryptor' => $this->encryptorMock,
                'productRepositoryInterface' => $this->productRepositoryInterfaceMock,
                'productFactory' => $this->productFactoryMock,
                'connection' => $this->resourceConnection,
                'dir' => $this->dirMock,
                'file' => $this->fileMock,
                'fileSystem' => $this->fileSystemMock,
                'fileDriver' => $this->fileDriverMock,
                'logger' => $this->loggerMock,
                'catalogSyncQueueProcessFactory' => $this->catalogSyncQueueProcessFactoryMock,
                'catalogSyncQueueCleanupProcessFactory' => $this->catalogSyncQueueCleanupProcessFactoryMock,
                'productCollectionFactory' => $this->productCollectionFactoryMock,
                'categoryLinkRepository' => $this->categoryLinkRepositoryMock,
                'itemCollectionFactory' => $this->itemCollectionFactoryMock,
                'cartFactory' => $this->cartFactoryMock,
                'itemDeleter' => $this->itemDeleterMock,
                'productGallery' => $this->galleryMock,
                'productResourceModel' => $this->productResourceModel,
                'curl' => $this->curlMock,
                'punchoutHelperData' => $this->punchoutHelperData,
                'fileMime' => $this->fileMimeMock,
                'publisher' => $this->publisherMock,
                'message' => $this->messageMock,
                'toggleConfig' => $this->toggleConfigMock,
                'registry' => $this->registryMock,
                'categoryLinkManagementInterface' => $this->categoryLinkManagementInterfaceMock,
                'catalogMvpHelper' => $this->catalogMvpMock,
                'docRefMessage' => $this->docRefMessageMock,
                'mediaGalleryProcessor' => $this->processorMock,
                'fxoCMHelper' => $this->fXOCMHelperMock
            ]
        );
    }

    /**
     * @test createQueues method
     *
     * @return void
     */
    public function testCreateQueuesWithException1()
    {
        $catalogSyncQueueId = 1;
        $rootParentCateId = 1;
        $sharedCatalogId = 1;
        $storeId = 8;
        $lastInsertedId = 1;
        $versionSku = 12588730764;
        $itemSummaryId = 'e9dc8dad-f438-4ff1-866c-4177a3055f1d';
        $responseDatas = [
            'transactionId' => 'dec05fe5-9a0d-4bf4-8ace-2a8614a996d4',
            'output' => [
                'folder' => [
                    'id' => 'e4de4027-9379-4186-a939-282de593776a',
                    'name' => 'ALL FOLDERS',
                    'description' => 'ROOT FOLDER',
                    'accessGroups' => ['administrators', 'everyone'],
                    'folderSummaries' => [
                        [
                            'id' => 'b10be3ab-6cc2-4e98-b525-990c4ea61ad1',
                            'name' => '__ImageLibrary',
                            'description' => 'Image Library Folder'
                        ],
                        [
                            'id' => '9c48a810-754b-4d60-8d56-847d65e69a8e',
                            'name' => 'Documents'
                        ],
                        [
                            'id' => '2d3e11f6-61e3-4757-aa36-0ada4f0f8954',
                            'name' => 'Party Events',
                            'description' => 'Party Events'
                        ],
                        [
                            'id' => 'ceac1d27-a075-4fc6-96ca-e3162761deda',
                            'name' => 'Charity Checks'
                        ],
                        [
                            'id' => '5c1c730d-aa00-423c-b0ef-84bb98bdbdbc',
                            'name' => 'Empty Folder',
                            'description' => 'Empty Folder'
                        ]
                    ],
                    'itemSummaries' => [
                        [
                            'id' => 'e9dc8dad-f438-4ff1-866c-4177a3055f1d',
                            'version' => 'SLP_20210717_12588730764_1',
                            'name' => 'Poster Prints',
                            'description' => 'This is Test doc by Shivani Kanswal Inf',
                            'createdBy' => 'Admin Admin',
                            'creationTime' => '2021-07-17T20:59:41Z',
                            'modifiedBy' => 'Admin Admin',
                            'modifiedTime' => '2021-10-04T18:40:26Z',
                            'links' => [
                                [
                                    'href' => '.../v2/catalogProducts/e9dc8dad-f438-4ff1-866c-4177a3055f1d',
                                    'rel' => 'detail'
                                ],
                                [
                                    'href' => 'https://printonline6.dmz.fedex.com/v3.8.0_s3/PreviewServletgetImage=SLP_20210717_12588730764_1.jpeg',
                                    'rel' => 'thumbnail'
                                ]
                            ],
                            'type' => 'PRODUCT',
                            'catalogProductSummary' => [
                                'productRateTotal' => [
                                    'currency' => 'USD',
                                    'price' => '73.50'
                                ],
                                'customizable' => 'false',
                                'availability' => [
                                    'available' => 1,
                                    'dateRange' => ['startDateTime' => '2021-07-17T20:53:17Z']
                                ],
                                'editable' => 1
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $existingjsonData = '{
            "id":1508784838900,
            "version":0,
            "name":"Legacy Catalog",
            "qty":1,
            "priceable":true,
            "proofRequired":false,
            "catalogReference":{
                "catalogProductId":"0202e6b7-8621-420a-a428-90858827722b",
                "version":"DOC_20201029_12539418566_1"
            },
            "isOutSourced":false,
            "instanceId":"0"
        }';

        $e = new \Magento\Framework\Exception\NoSuchEntityException();
        $this->productRepositoryInterfaceMock
            ->method('get')
            ->withConsecutive(
                [$versionSku],
                [$itemSummaryId]
            )
            ->willReturnOnConsecutiveCalls(
                $this->throwException($e),
                $this->throwException($e)
            );

        $this->productInterfaceMock->expects($this->any())->method('getExternalProd')->willReturn($existingjsonData);

        $this->catalogSyncQueueProcessFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueProcessMock);
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('setCatalogSyncQueueId')
            ->with($catalogSyncQueueId)
            ->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('setSharedCatalogId')->with($sharedCatalogId)
            ->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('setCategoryId')->with($rootParentCateId)
            ->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('setStatus')->with(self::STATUS_PENDING)
            ->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('setJsonData')->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('setCatalogType')->with('product')
            ->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->exactly(1))->method('setActionType')
            ->withConsecutive(['new'])
            ->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('save')->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('getId')->willReturn($lastInsertedId);
        $this->messageMock->expects($this->any())->method('setMessage')->with($lastInsertedId)->willReturnSelf();
        $this->publisherMock->expects($this->any())->method('publish')
            ->withConsecutive(['product'], [$this->messageMock])
            ->willThrowException(new \Exception());

        $this->testGetProductCollectionByCategories();
        $this->testCleanUpCatalogProductQueue();
        $this->helperManageCatalogItems->createQueues(
            $responseDatas,
            $catalogSyncQueueId,
            $rootParentCateId,
            $sharedCatalogId,
            $storeId
        );
    }

    /**
     * @test createQueues method
     *
     * @return void
     */
    public function testCreateQueuesWithException2()
    {
        $catalogSyncQueueId = 1;
        $rootParentCateId = 1;
        $sharedCatalogId = 1;
        $storeId = 8;
        $productId = 452;
        $lastInsertedId = 1;
        $versionSku = 12588730764;
        $itemSummaryId = 'e9dc8dad-f438-4ff1-866c-4177a3055f1d';
        $responseDatas = [
            'transactionId' => 'dec05fe5-9a0d-4bf4-8ace-2a8614a996d4',
            'output' => [
                'folder' => [
                    'id' => 'e4de4027-9379-4186-a939-282de593776a',
                    'name' => 'ALL FOLDERS',
                    'description' => 'ROOT FOLDER',
                    'accessGroups' => ['administrators', 'everyone'],
                    'folderSummaries' => [
                        [
                            'id' => 'b10be3ab-6cc2-4e98-b525-990c4ea61ad1',
                            'name' => '__ImageLibrary',
                            'description' => 'Image Library Folder'
                        ],
                        [
                            'id' => '9c48a810-754b-4d60-8d56-847d65e69a8e',
                            'name' => 'Documents'
                        ],
                        [
                            'id' => '2d3e11f6-61e3-4757-aa36-0ada4f0f8954',
                            'name' => 'Party Events',
                            'description' => 'Party Events'
                        ],
                        [
                            'id' => 'ceac1d27-a075-4fc6-96ca-e3162761deda',
                            'name' => 'Charity Checks'
                        ],
                        [
                            'id' => '5c1c730d-aa00-423c-b0ef-84bb98bdbdbc',
                            'name' => 'Empty Folder',
                            'description' => 'Empty Folder'
                        ]
                    ],
                    'itemSummaries' => [
                        [
                            'id' => 'e9dc8dad-f438-4ff1-866c-4177a3055f1d',
                            'version' => 'SLP_20210717_12588730764_1',
                            'name' => 'Poster Prints',
                            'description' => 'This is Test doc by Shivani Kanswal Inf',
                            'createdBy' => 'Admin Admin',
                            'creationTime' => '2021-07-17T20:59:41Z',
                            'modifiedBy' => 'Admin Admin',
                            'modifiedTime' => '2021-10-04T18:40:26Z',
                            'links' => [
                                [
                                    'href' => '.../v2/catalogProducts/e9dc8dad-f438-4ff1-866c-4177a3055f1d',
                                    'rel' => 'detail'
                                ],
                                [
                                    'href' => 'https://printonline6.dmz.fedex.com/v3.8.0_s3/PreviewServletgetImage=SLP_20210717_12588730764_1.jpeg',
                                    'rel' => 'thumbnail'
                                ]
                            ],
                            'type' => 'PRODUCT',
                            'catalogProductSummary' => [
                                'productRateTotal' => [
                                    'currency' => 'USD',
                                    'price' => '73.50'
                                ],
                                'customizable' => 'false',
                                'availability' => [
                                    'available' => 1,
                                    'dateRange' => ['startDateTime' => '2021-07-17T20:53:17Z']
                                ],
                                'editable' => 1
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $existingjsonData = '{
            "id":1508784838900,
            "version":0,
            "name":"Legacy Catalog",
            "qty":1,
            "priceable":true,
            "proofRequired":false,
            "catalogReference":{
                "catalogProductId":"0202e6b7-8621-420a-a428-90858827722b",
                "version":"DOC_20201029_12539418566_1"
            },
            "isOutSourced":false,
            "instanceId":"0"
        }';

        $e = new \Magento\Framework\Exception\NoSuchEntityException();
        $this->productRepositoryInterfaceMock
            ->method('get')
            ->withConsecutive(
                [$versionSku],
                [$itemSummaryId]
            )
            ->willReturnOnConsecutiveCalls(
                $this->throwException($e),
                $this->productInterfaceMock
            );

        $this->productInterfaceMock->expects($this->any())->method('getCategoryIds')->willReturn([15, 20]);
        $this->categoryLinkManagementInterfaceMock->expects($this->any())->method('assignProductToCategories')
            ->willReturnSelf();
        $this->productInterfaceMock->expects($this->any())->method('getExternalProd')->willReturn($existingjsonData);
        $this->productInterfaceMock->expects($this->any())->method('getId')->willReturn($productId);
        $this->catalogSyncQueueProcessFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueProcessMock);
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('setCatalogSyncQueueId')
            ->with($catalogSyncQueueId)->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('setSharedCatalogId')->with($sharedCatalogId)
            ->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('setCategoryId')->with($rootParentCateId)
            ->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('setStatus')->with(self::STATUS_PENDING)
            ->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('setJsonData')->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('setCatalogType')->with('product')
            ->willReturnSelf();

        $this->catalogSyncQueueProcessMock->expects($this->exactly(1))->method('setActionType')
            ->withConsecutive(['update'])
            ->willReturnSelf();

        $this->catalogSyncQueueProcessMock->expects($this->any())->method('save')->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('getId')->willReturn($lastInsertedId);
        $this->messageMock->expects($this->any())->method('setMessage')->with($lastInsertedId)->willReturnSelf();
        $this->publisherMock->expects($this->any())->method('publish')
            ->withConsecutive(['product'], [$this->messageMock])
            ->willThrowException(new \Exception());

        $this->testGetProductCollectionByCategories();
        $this->testCleanUpCatalogProductQueue();
        $this->helperManageCatalogItems->createQueues(
            $responseDatas,
            $catalogSyncQueueId,
            $rootParentCateId,
            $sharedCatalogId,
            $storeId
        );
    }

    /**
     * @test getAttrSetId method
     *
     * @return void
     */
    public function testGetAttrSetId()
    {
        $attributeSetName = 'PrintOnDemand';
        $attributeSetId = 1;

        $this->getAttrSetId();

        $this->assertEquals($attributeSetId, $this->helperManageCatalogItems->getAttrSetId($attributeSetName));
    }

    /**
     * test publishCatalogEnableStoreQueue
     *
     * @return void
     */
    public function testPublishCatalogEnableStoreQueue()
    {
        $message = '{"catalogSyncQueueProcessId":2,"productSku":"test","storeId":1}';
        $this->messageMock->expects($this->any())->method('setMessage')->with($message)->willReturnSelf();
        $this->publisherMock->expects($this->any())->method('publish')->willReturnSelf();
        $this->loggerMock->expects($this->any())
            ->method('info')
            ->willReturnSelf();

        $this->assertNull($this->helperManageCatalogItems->publishCatalogEnableStoreQueue(2, 'test', 1));
    }

    /**
     * test itemEnableStore
     *
     * @return void
     */
    public function testItemEnableStore()
    {
        $message = '{"catalogSyncQueueProcessId":2,"productSku":"test","storeId":1}';
        $this->productRepositoryInterfaceMock->expects($this->any())->method('get')
            ->with("test")
            ->willReturn($this->productInterfaceMock);
        $this->productInterfaceMock->expects($this->any())->method('setStoreId')
            ->with("1")
            ->willReturnSelf();
        $this->productInterfaceMock->expects($this->any())->method('setVisibility')
            ->with("4")
            ->willReturnSelf();
        $this->productInterfaceMock->expects($this->any())->method('save')
            ->willReturnSelf();
        $this->setQueueStatus();
        $this->loggerMock->expects($this->any())
            ->method('info')
            ->willReturnSelf();

        $this->assertNull($this->helperManageCatalogItems->itemEnableStore(2, 'test', 1));
    }

    /**
     * test itemEnableStore with exception
     *
     * @return void
     */
    public function testItemEnableStoreWithException()
    {
        $message = '{"catalogSyncQueueProcessId":2,"productSku":"test","storeId":1}';
        $this->productRepositoryInterfaceMock->expects($this->any())->method('get')
            ->with("test")
            ->willThrowException(new \Exception());
        $this->setQueueStatus();
        $this->loggerMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();

        $this->assertNull($this->helperManageCatalogItems->itemEnableStore(2, 'test', 1));
    }

    /**
     * getAttrSetId method
     *
     * @return void
     */
    public function getAttrSetId()
    {
        $attributeSetName = 'PrintOnDemand';
        $size = 1;
        $attributeSetId = 1;

        $this->attributeSetCollectionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->attributeSetCollection);

        $this->attributeSetCollection
            ->expects($this->any())
            ->method('addFieldToSelect')
            ->with('*')
            ->willReturn($this->attributeSetCollection);

        $this->attributeSetCollection
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->with('attribute_set_name', $attributeSetName)
            ->willReturn($this->attributeSetCollection);

        $collectionItem = $this->getMockBuilder(
            DataObject::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId'])
            ->getMock();

        $this->attributeSetCollection
            ->expects($this->atLeastOnce())
            ->method('getSize')
            ->willReturn($size);

        $this->attributeSetCollection
            ->expects($this->atLeastOnce())
            ->method('getFirstItem')
            ->willReturn($collectionItem);

        $collectionItem->expects($this->any())->method('getAttributeSetId')->willReturn($attributeSetId);
    }

    /**
     * @test setQueueStatus method
     *
     * @return void
     */
    public function testSetQueueStatus()
    {
        $catalogSyncQueueProcessId = 1;
        $status = 'processing';

        $this->setQueueStatus();

        $this->assertEquals(null, $this->helperManageCatalogItems->setQueueStatus($catalogSyncQueueProcessId, $status));
    }

    /**
     * @test uploadImage method
     */
    public function testUploadImage()
    {
        $imageLink = 'https://printonline6.dmz.fedex.com/v3.8.0_s3/PreviewServletgetImage=SLP_20210717_12588730764_1.jpeg';
        $imageName = 'PreviewServletgetImage=SLP_20210717_12588730764_1.jpeg';
        $apiOutput = 'Image file text';
        $files = ['size' => 88212];

        $this->dirWriteMock = $this
            ->getMockBuilder(\Magento\Framework\Filesystem\Directory\Write::class)
            ->setMethods(['writeFile', 'stat'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->punchoutHelperData
            ->expects($this->any())
            ->method('getTazToken')
            ->willReturn(self::TAZ_TOKEN);

        $this->curlMock->expects($this->any())->method('getBody')->willReturn($apiOutput);

          $this->fileSystemMock->expects($this->any())->method('getDirectoryWrite')->willReturn($this->dirWriteMock);

        $this->dirWriteMock
            ->expects($this->any())
            ->method('stat')
            ->with("tmp/" . $imageName)
            ->willReturn($files);

        //     $this->assertNotNull(
        //         $this->helperManageCatalogItems->uploadImage(
        //             $imageLink,
        //             $imageName,
        //             self::NEW_FILE_NAME
        //         )
        //     );
    }

    /**
     * @test removeProductImages method
     */
    public function testRemoveProductImages()
    {
        $gallery = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValueId'])
            ->getMock();

        $mediaEntries = [
            'images' => [
                [
                    'value_id' => 1,
                    'file' => 'imageFile.jpg',
                    'media_type' => 'image',
                ],
                [
                    'value_id' => 2,
                    'file' => 'smallImageFile.jpg',
                    'media_type' => 'image',
                ],
            ]
        ];
        $this->productMock
            ->expects($this->any())
            ->method('getMediaGalleryImages')
            ->willReturn([$gallery]);

        $this->productMock
            ->expects($this->any())
            ->method('getMediaGalleryEntries')
            ->willReturn($mediaEntries);

        $this->assertIsObject($this->helperManageCatalogItems->removeProductImages($this->productMock));
    }


     /**
     * @test removeProductImagesCatalogMigrationToggle method
     */
    public function testRemoveProductImagesCatalogMigrationToggle()
    {
        $gallery = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValueId'])
            ->getMock();

        $mediaEntries = [
            'images' => [
                [
                    'value_id' => 1,
                    'file' => 'imageFile.jpg',
                    'media_type' => 'image',
                ],
                [
                    'value_id' => 2,
                    'file' => 'smallImageFile.jpg',
                    'media_type' => 'image',
                ],
            ]
        ];

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->productMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->productMock
            ->expects($this->any())
            ->method('getMediaGalleryImages')
            ->willReturn([$gallery]);

        $this->productMock
            ->expects($this->any())
            ->method('getMediaGalleryEntries')
            ->willReturn($mediaEntries);

        $this->assertIsObject($this->helperManageCatalogItems->removeProductImages($this->productMock));
    }

    /**
     * @test testRemoveProductImagesWithException method
     */
    public function testRemoveProductImagesWithException()
    {
        $gallery = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValueId'])
            ->getMock();

        $mediaEntries = [
            'images' => [
                [
                    'value_id' => 1,
                    'file' => 'imageFile.jpg',
                    'media_type' => 'image',
                ],
                [
                    'value_id' => 2,
                    'file' => 'smallImageFile.jpg',
                    'media_type' => 'image',
                ],
            ]
        ];

        $this->productMock
            ->expects($this->any())
            ->method('getMediaGalleryImages')
            ->willReturn([$gallery]);

        $this->productMock
            ->expects($this->any())
            ->method('getMediaGalleryEntries')
            ->willThrowException(new \Exception());

        $this->assertNotNull($this->helperManageCatalogItems->removeProductImages($this->productMock));
    }

    /**
     * setQueueStatus method
     *
     * @return void
     */
    public function setQueueStatus()
    {
        $catalogSyncQueueProcessId = 1;
        $status = 'processing';

        $this->catalogSyncQueueProcessFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->catalogSyncQueueProcessMock);

        $this->catalogSyncQueueProcessMock
            ->expects($this->any())
            ->method('setId')
            ->with($catalogSyncQueueProcessId)
            ->willReturnSelf();

        $this->catalogSyncQueueProcessMock
            ->expects($this->any())
            ->method('setErrorMsg')
            ->willReturnSelf();

        $this->catalogSyncQueueProcessMock
            ->expects($this->any())
            ->method('setStatus')
            ->with($status)
            ->willReturnSelf();

        $this->catalogSyncQueueProcessMock
            ->expects($this->any())
            ->method('save')
            ->willThrowException(new \Exception());
    }


    /**
     * testsetQueueStatus method
     *
     * @return void
     */
    public function testsetQueueStatusLogger()
    {
        $catalogSyncQueueProcessId = 1;
        $status = 'processing';

        $this->catalogSyncQueueProcessFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->catalogSyncQueueProcessMock);

        $this->catalogSyncQueueProcessMock
            ->expects($this->any())
            ->method('setId')
            ->with($catalogSyncQueueProcessId)
            ->willReturnSelf();

        $this->catalogSyncQueueProcessMock
            ->expects($this->any())
            ->method('setErrorMsg')
            ->willReturnSelf();

        $this->catalogSyncQueueProcessMock
            ->expects($this->any())
            ->method('setStatus')
            ->with($status)
            ->willReturnSelf();

        $this->catalogSyncQueueProcessMock
            ->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->loggerMock->expects($this->any())
            ->method('info')
            ->willReturnSelf();

        $this->assertNull($this->helperManageCatalogItems->setQueueStatus($catalogSyncQueueProcessId, $status));
    }
    /**
     *  getExternalProdData method
     *
     * @return void
     */
    public function testGetExternalProdData()
    {
        $id = 1508784838900;
        $version = 0;
        $extProdData = [
            'id' => 1508784838900,
            'version' => 0,
            'name' => 'Legacy Catalog',
            'qty' => 1,
            'priceable' => true,
            'proofRequired' => false,
            'catalogReference' => [
                'catalogProductId' => $id,
                'version' => $version,
            ],
            'isOutSourced' => false,
            'instanceId' => '0',
        ];

        $this->assertEquals(
            json_encode($extProdData),
            $this->helperManageCatalogItems->getExternalProdData($id, $version)
        );
    }

    /**
     * @test getMediaDirTmpDir method
     *
     * @return void
     */
    public function testGetMediaDirTmpDir()
    {
        $mediaPath = 'staging3.office.fedex.com/pub/media';
        $this->dirMock
            ->expects($this->any())
            ->method('getPath')
            ->with('media')
            ->willReturn($mediaPath);
        $tmpPath = $mediaPath . '/tmp/';

        $this->assertEquals($tmpPath, $this->helperManageCatalogItems->getMediaDirTmpDir());
    }

    /**
     * @test getBaseUrl method
     *
     * @return void.
     */
    public function testGetBaseUrl()
    {
        $baseUrl = 'https://magento2/';

        $this->getBaseUrl();

        $this->assertEquals($baseUrl, $this->helperManageCatalogItems->getBaseUrl());
    }

    /**
     * getBaseUrl method
     *
     * @return void.
     */
    protected function getBaseUrl()
    {
        $baseUrl = 'https://magento2/';

        $this->storeMock->expects($this->atLeastOnce())
            ->method('getBaseUrl')
            ->with(\Magento\Framework\UrlInterface::URL_TYPE_WEB)
            ->willReturn($baseUrl);

        $this->storeManagerMock->expects($this->atLeastOnce())
            ->method('getStore')
            ->willReturn($this->storeMock);
    }

    /**
     * @test getApiUserCredentials
     */
    public function testGetApiUserCredentials()
    {
        $username = null; //'Admin Token Error';
        $password = null; //'0:3:57b1KnIoivsjoETztgLcO39loK87yHi/ItNANgjH2idUh2Gv';
        $apiUser = [
            'username' => $username,
            'password' => $password,
        ];

        $this->assertEquals($apiUser, $this->helperManageCatalogItems->getApiUserCredentials());
    }

    /**
     * @test testSharedCatalogUnAssignProduct
     *
     * @return void
     */
    public function testSharedCatalogAssignProduct()
    {
        $sku = '296acbf8-7f59-4434-92f3-6d8b2590e689';
        $sharedCatId = 7;
        $this->getBaseUrl();
        $output = ['error' => ['error'], 'fileFormats' => []];
        $this->curlMock->expects($this->any())->method('setOptions')->willReturn(true);
        $this->curlMock->expects($this->any())->method('post')->willReturn(true);
        $this->curlMock->expects($this->any())->method('getBody')->willReturn(json_encode($output));
        $this->helperManageCatalogItems->sharedCatalogAssignProduct($sku, $sharedCatId);
    }


    /**
     * @test testSharedCatalogAssignProductWithException
     *
     * @return void
     */
    public function testSharedCatalogAssignProductWithException()
    {
        $sku = '296acbf8-7f59-4434-92f3-6d8b2590e689';
        $sharedCatId = 7;
        $this->getBaseUrl();
        $output = ['error' => ['error'], 'fileFormats' => []];
        $this->curlMock->expects($this->any())->method('setOptions')->willReturn(true);
        $this->curlMock->expects($this->any())->method('post')->willReturn(true);
        $this->curlMock->expects($this->any())->method('getBody')->willThrowException(new \Exception());

        $this->assertNull($this->helperManageCatalogItems->sharedCatalogAssignProduct($sku, $sharedCatId));
    }

    /**
     * @test testSharedCatalogUnAssignProduct
     *
     * @return void
     */
    public function testSharedCatalogUnAssignProduct()
    {
        $sku = '296acbf8-7f59-4434-92f3-6d8b2590e689';
        $sharedCatId = 7;
        $this->getBaseUrl();
        $output = ['error' => ['error'], 'fileFormats' => []];
        $this->curlMock->expects($this->any())->method('setOptions')->willReturn(true);
        $this->curlMock->expects($this->any())->method('post')->willReturn(true);
        $this->curlMock->expects($this->any())->method('getBody')->willReturn(json_encode($output));
        $this->helperManageCatalogItems->sharedCatalogUnAssignProduct($sku, $sharedCatId);
    }

    /**
     * @test testSharedCatalogUnAssignProduct
     *
     * @return void
     */
    public function testSharedCatalogUnAssignProductWithException()
    {
        $sku = '296acbf8-7f59-4434-92f3-6d8b2590e689';
        $sharedCatId = 7;
        $this->getBaseUrl();
        $output = ['error' => ['error'], 'fileFormats' => []];
        $this->curlMock->expects($this->any())->method('setOptions')->willReturn(true);
        $this->curlMock->expects($this->any())->method('post')->willReturn(true);
        $this->curlMock->expects($this->any())->method('getBody')->willThrowException(new \Exception());


        $this->assertNull($this->helperManageCatalogItems->sharedCatalogUnAssignProduct($sku, $sharedCatId));
    }

    /**
     * @test testCheckNegotiableQuote
     *
     * @return void
     */
    public function testCheckNegotiableQuote()
    {
        $productId = 1;
        $mainTableName = 'quote_item';
        $tableName = 'negotiable_quote';

        $this->resourceConnection->expects($this->atLeastOnce())->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->once())->method('select')->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())
            ->method('from')
            ->with(['qi' => $mainTableName])
            ->willReturnSelf();
        $this->selectMock->expects($this->any())
            ->method('join')
            ->with(['nq' => $tableName], 'nq.quote_id=qi.quote_id')
            ->willReturnSelf();
        $this->selectMock->expects($this->exactly(2))->method('where')
            ->withConsecutive(
                ['qi.product_id=?', $productId],
                [
                    'nq.status IN (?)', [
                        NegotiableQuoteInterface::STATUS_CREATED,
                        NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN
                    ]
                ]
            )
            ->willReturnSelf();

        $this->connectionMock->expects($this->once())->method('fetchOne')->with($this->selectMock)->willReturn(1);

        $this->helperManageCatalogItems->checkNegotiableQuote($productId);
    }

    /**
     * @test testCheckNegotiableQuoteWithCountZero
     *
     * @return void
     */
    public function testCheckNegotiableQuoteWithCountZero()
    {
        $productId = 334;
        $mainTableName = 'quote_item';
        $tableName = 'negotiable_quote';

        $this->resourceConnection->expects($this->any())->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->any())->method('select')->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())
            ->method('from')
            ->with(['qi' => $mainTableName])
            ->willReturnSelf();
        $this->selectMock->expects($this->any())
            ->method('join')
            ->with(['nq' => $tableName], 'nq.quote_id=qi.quote_id')
            ->willReturnSelf();
        $this->selectMock->expects($this->any())->method('where')
            ->withConsecutive(
                ['qi.product_id=?', $productId],
                [
                    'nq.status IN (?)', [
                        NegotiableQuoteInterface::STATUS_CREATED,
                        NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN
                    ]
                ]
            )
            ->willReturnSelf();
        $this->connectionMock->expects($this->any())->method('fetchOne')->with($this->selectMock)->willReturn(0);
        $this->assertEquals(null, $this->helperManageCatalogItems->checkNegotiableQuote($productId));
    }

    /**
     *
     * @test testDeleteCatalogProduct
     *
     * @return void
     */
    public function testDeleteCatalogProduct()
    {
        $notAvailableProductsIdsInApiResponse = [1, 2, 3];
        $categoryId = 1;
        $catalogSyncQueueId = 1;
        $sharedCatalogId = 1;
        $productId = 1;
        $lastInsertedId = 1;

        $this->testCheckNegotiableQuoteWithCountZero();

        $this->catalogSyncQueueProcessFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueProcessMock);
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('setCatalogSyncQueueId')
            ->with($catalogSyncQueueId)
            ->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('setSharedCatalogId')
            ->with($sharedCatalogId)
            ->willReturnSelf();
        $this->catalogSyncQueueProcessMock
            ->expects($this->any())
            ->method('setCategoryId')
            ->with($sharedCatalogId)
            ->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('setStatus')->with(self::STATUS_PENDING)
            ->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('setJsonData')->willReturnSelf();
        $this->catalogSyncQueueProcessMock->expects($this->any())->method('setCatalogType')->with('product')
            ->willReturnSelf();

        $catalogSyncQueueProcessItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $this->catalogSyncQueueProcessMock
            ->expects($this->any())
            ->method('save')
            ->willReturnSelf();
        $this->catalogSyncQueueProcessMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn($lastInsertedId);
        $this->messageMock
            ->expects($this->any())
            ->method('setMessage')
            ->with($lastInsertedId)
            ->willReturnSelf();
        $this->publisherMock
            ->expects($this->any())
            ->method('publish')
            ->withConsecutive(['product'], [$this->messageMock])
            ->willReturnSelf();
        $this->assertEquals(null, $this->helperManageCatalogItems->deleteCatalogProduct(
            $notAvailableProductsIdsInApiResponse,
            $categoryId,
            $catalogSyncQueueId,
            $sharedCatalogId
        ));
    }

    /**
     *
     * @test testCleanUpCatalogProductQueue
     *
     * @return void
     */
    public function testCleanUpCatalogProductQueue()
    {
        $notAvailableProductsIdsInApiResponse = [1];
        $productId = 1;
        $productSku = 'test';
        $categoryId = 1;
        $catalogSyncQueueId = 1;
        $sharedCatalogId = 1;

        $this->productRepositoryInterfaceMock->expects($this->any())->method('getById')->with($productId)
            ->willReturn($this->productInterfaceMock);

        $this->productInterfaceMock->expects($this->any())->method('getCategoryIds')->willReturn([$categoryId]);
        $this->productInterfaceMock->expects($this->any())->method('getSku')->willReturn($productSku);

        $this->categoryLinkRepositoryMock->expects($this->any())->method('deleteByIds')->with($categoryId, $productSku)
            ->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCleanupProcessMock);

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setCatalogSyncQueueId')
            ->with($catalogSyncQueueId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setSharedCatalogId')
            ->with($sharedCatalogId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setCategoryId')
            ->with($categoryId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setJsonData')
            ->with($productId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setProductId')
            ->with($productId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setSku')
            ->with($productSku)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setCatalogType')->with('product')
            ->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setStatus')
            ->with(self::STATUS_PENDING)
            ->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('save');
        $this->assertEquals(null, $this->helperManageCatalogItems->cleanUpCatalogProductQueue(
            $notAvailableProductsIdsInApiResponse,
            $categoryId,
            $catalogSyncQueueId,
            $sharedCatalogId
        ));
    }

    /**
     *
     * @test testCleanUpCatalogProductQueueWithException
     *
     * @return void
     */
    public function testCleanUpCatalogProductQueueWithException()
    {
        $notAvailableProductsIdsInApiResponse = [1];
        $productId = 1;
        $productSku = 'test';
        $categoryId = 1;
        $catalogSyncQueueId = 1;
        $sharedCatalogId = 1;

        $this->productRepositoryInterfaceMock->expects($this->any())->method('getById')
            ->with($productId)
            ->willReturn($this->productInterfaceMock);

        $this->productInterfaceMock->expects($this->any())->method('getCategoryIds')->willReturn([$categoryId]);
        $this->productInterfaceMock->expects($this->any())->method('getSku')->willReturn($productSku);

        $this->categoryLinkRepositoryMock->expects($this->any())->method('deleteByIds')->with($categoryId, $productSku)
            ->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCleanupProcessMock);

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setCatalogSyncQueueId')
            ->with($catalogSyncQueueId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setSharedCatalogId')
            ->with($sharedCatalogId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setCategoryId')
            ->with($categoryId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setJsonData')
            ->with($productId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setProductId')
            ->with($productId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setSku')
            ->with($productSku)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setCatalogType')->with('product')
            ->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setStatus')
            ->with(self::STATUS_PENDING)
            ->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('save')
            ->willThrowException(new \Exception());

        $this->assertEquals(null, $this->helperManageCatalogItems->cleanUpCatalogProductQueue(
            $notAvailableProductsIdsInApiResponse,
            $categoryId,
            $catalogSyncQueueId,
            $sharedCatalogId
        ));
    }

    /**
     *
     * @test testCleanUpCatalogProductQueueWithException1
     *
     * @return void
     */
    public function testCleanUpCatalogProductQueueWithException1()
    {
        $notAvailableProductsIdsInApiResponse = [1];
        $productId = 1;
        $productSku = 'test';
        $categoryId = 1;
        $catalogSyncQueueId = 1;
        $sharedCatalogId = 1;

        $this->productRepositoryInterfaceMock->expects($this->any())->method('getById')
            ->with($productId)
            ->willReturn($this->productInterfaceMock);

        $this->productInterfaceMock->expects($this->any())->method('getCategoryIds')->willReturn([$categoryId]);
        $this->productInterfaceMock->expects($this->any())->method('getSku')->willReturn($productSku);

        $this->categoryLinkRepositoryMock->expects($this->any())->method('deleteByIds')
            ->willThrowException(
                new \Magento\Framework\Exception\InputException(
                    new \Magento\Framework\Phrase('Unable to delete')
                )
            );

        $this->catalogSyncQueueCleanupProcessFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCleanupProcessMock);

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setCatalogSyncQueueId')
            ->with($catalogSyncQueueId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setSharedCatalogId')
            ->with($sharedCatalogId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setCategoryId')
            ->with($categoryId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setJsonData')
            ->with($productId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setProductId')
            ->with($productId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setSku')
            ->with($productSku)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setCatalogType')->with('product')
            ->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setStatus')
            ->with(self::STATUS_PENDING)
            ->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('save');

        $this->assertEquals(null, $this->helperManageCatalogItems->cleanUpCatalogProductQueue(
            $notAvailableProductsIdsInApiResponse,
            $categoryId,
            $catalogSyncQueueId,
            $sharedCatalogId
        ));
    }

    /**
     *
     * @test testCleanUpCatalogProductQueueWithException2
     *
     * @return void
     */
    public function testCleanUpCatalogProductQueueWithException2()
    {
        $notAvailableProductsIdsInApiResponse = [1];
        $productId = 1;
        $productSku = '';
        $categoryId = 1;
        $catalogSyncQueueId = 1;
        $sharedCatalogId = 1;

        $this->productRepositoryInterfaceMock->expects($this->any())->method('getById')
            ->with($productId)
            ->willReturn($this->productInterfaceMock);

        $this->productInterfaceMock->expects($this->any())->method('getCategoryIds')->willReturn([$categoryId]);
        $this->productInterfaceMock->expects($this->any())->method('getSku')->willReturn($productSku);

        $this->categoryLinkRepositoryMock->expects($this->any())->method('deleteByIds')
            ->willThrowException(
                new \Magento\Framework\Exception\CouldNotSaveException(
                    new \Magento\Framework\Phrase('Unable to delete')
                )
            );

        $this->catalogSyncQueueCleanupProcessFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCleanupProcessMock);

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setCatalogSyncQueueId')
            ->with($catalogSyncQueueId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setSharedCatalogId')
            ->with($sharedCatalogId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setCategoryId')
            ->with($categoryId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setJsonData')
            ->with($productId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setProductId')
            ->with($productId)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setSku')
            ->with($productSku)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setCatalogType')->with('product')
            ->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('setStatus')
            ->with(self::STATUS_PENDING)
            ->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('save');

        $this->assertEquals(null, $this->helperManageCatalogItems->cleanUpCatalogProductQueue(
            $notAvailableProductsIdsInApiResponse,
            $categoryId,
            $catalogSyncQueueId,
            $sharedCatalogId
        ));
    }

    /**
     *
     * @test getProductCollectionByCategories
     *
     * @return void
     */
    public function testGetProductCollectionByCategories()
    {
        $categoryLevelSkus = ['e9dc8dad-f438-4ff1-866c-4177a3055f1d', '12588730764'];
        $categoryId = 1;

        $this->productCollectionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollectionMock);
        $this->productCollectionMock
            ->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturnSelf();
        $this->productCollectionMock
            ->expects($this->any())
            ->method('addAttributeToFilter')->with('sku', ['nin' => $categoryLevelSkus])
            ->willReturnSelf();
        $this->productCollectionMock
            ->expects($this->any())
            ->method('addCategoriesFilter')->with(['eq' => $categoryId])
            ->willReturnSelf();
        $item = new DataObject(
            [
                'id' => 1,
                'sku' => 'sku_4',
                'name' => 'product_1'
            ]
        );
        $this->productCollectionMock->addItem($item);
        $this->productCollectionMock->expects($this->any())->method('getSize')->willReturn(1);
        $this->assertIsArray($this->helperManageCatalogItems->
        getProductCollectionByCategories($categoryLevelSkus, $categoryId));
    }

    /**
     * Anuj || B-885038 || Code Refactor - Removed curl and called model method directly
     * Test for getAdminTokenWithoutCurl method.
     * @return string|array
     */
    public function testGetAdminTokenWithoutCurl()
    {
        $username = 'admin';
        $password = '0:3:57b1KnIoivsjoETztgLcO39loK87yHi/ItNANgjH2idUh2Gv';

        $actualResult = $this->helperManageCatalogItems->getAdminTokenWithoutCurl();
        $this->assertNull($actualResult);
    }

    public function testGetAdminToken()
    {
        $this->getBaseUrl();

        $this->assertNull($this->helperManageCatalogItems->getAdminToken());
    }

    /**
     * Anuj || B-885038 || Code Refactor - Removed curl and called model method directly
     * @test sharedCatalogAssignProductWithoutCurl
     *
     * @return void
     */
    public function testSharedCatalogAssignProductWithoutCurl()
    {
        $sku = '296acbf8-7f59-4434-92f3-6d8b2590e689';
        $sharedCatId = 7;
        $this->assertEquals(
            null,
            $this->helperManageCatalogItems->sharedCatalogAssignProductWithoutCurl(
                $sku,
                $sharedCatId
            )
        );
    }

    /**
     * Anuj || B-885038 || Code Refactor - Removed curl and called model method directly
     * @test sharedCatalogUnAssignProductWithoutCurl
     *
     * @return void
     */
    public function testSharedCatalogUnAssignProductWithoutCurl()
    {
        $sku = '296acbf8-7f59-4434-92f3-6d8b2590e689';
        $sharedCatId = 7;
        $this->assertEquals(
            null,
            $this->helperManageCatalogItems->sharedCatalogUnAssignProductWithoutCurl(
                $sku,
                $sharedCatId
            )
        );
    }

    /**
     * @test testRemoveProductsByCategory
     *
     * @return void
     */
    public function testRemoveProductsByCategorys()
    {
        $categoryId = 1;
        $catalogSyncQueueId = 1;
        $sharedCatalogId = 1;

        $this->productCollectionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->productCollectionMock);
        $this->productCollectionMock->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->productCollectionMock->expects($this->any())->method('addCategoriesFilter')->with(['eq' => $categoryId])
            ->willReturnSelf();
        $item = new DataObject(
            [
                'id' => 1,
                'sku' => 'sku_4',
                'name' => 'product_1'
            ]
        );

        $this->productCollectionMock->addItem($item);
        $this->productCollectionMock->expects($this->any())->method('getSize')->willReturn(1);
        $this->catalogMvpMock->expects($this->any())->method('isProductPodEditAbleById')->willReturn(0);
        $this->testCleanUpCatalogProductQueue();
        $this->assertEquals(null, $this->helperManageCatalogItems->removeProductsByCategory(
            $categoryId,
            $catalogSyncQueueId,
            $sharedCatalogId
        ));
    }

    /**
     * @test testManageDocsLifeExpire
     *
     * @return void
     */
    public function testManageDocsLifeExpire()
    {
        $sku = 'SKU_1';
        $externalProductData = '{"contentAssociations": [{"contentReference": "doc2"},{"contentReference": "doc4"}]}';
        $this->productRepositoryInterfaceMock->expects($this->any())->method('get')
            ->willReturn($this->productInterfaceMock);
        $this->productInterfaceMock->expects($this->any())->method('getExternalProd')
            ->willReturn($externalProductData);

        $this->assertNull($this->helperManageCatalogItems->manageDocsLifeExpire($sku));
    }

    /**
     * @test testManageDocsLifeExpireWithToggleEnable
     *
     * @return void
     */
    public function testManageDocsLifeExpireWithToggleEnabled()
    {
        $sku = 'SKU_1';
        $externalProductData = '{"contentAssociations": [{"contentReference": "doc2"},{"contentReference": "doc4"}]}';
        $this->productRepositoryInterfaceMock->expects($this->any())->method('get')
            ->willReturn($this->productInterfaceMock);
        $this->productInterfaceMock->expects($this->any())->method('getExternalProd')
            ->willReturn($externalProductData);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->productInterfaceMock->expects($this->any())->method('getIsDocumentExpire')
            ->willReturn(1);

        $this->assertNull($this->helperManageCatalogItems->manageDocsLifeExpire($sku));
    }

    /**
     * @test method for testHandlePageGroups
     * @return void
     */
    public function testHandlePageGroups()
    {
        $contentAssociation = '{"contentAssociations": [{
            "parentContentReference" : "3ab57d6a-7003-11ef-8c27-9b9ee2ea0864",
            "contentReference" : "3ab57d6a-7003-11ef-8c27-9b9ee2ea0864",
            "pageGroups" : []
        }]}';

        $pageGroup = '[{"start":1,"end":1,"width":8.5,"height":11}]';

        $this->fXOCMHelperMock->expects($this->any())
            ->method('getPageGroupsPrintReady')
            ->with('3ab57d6a-7003-11ef-8c27-9b9ee2ea0864')
            ->willReturn($pageGroup);

        $this->assertNotNull(
            $this->helperManageCatalogItems->handlePageGroups($contentAssociation)
        );
    }
}
