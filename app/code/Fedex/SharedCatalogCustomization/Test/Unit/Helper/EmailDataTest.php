<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\App\Helper\Context;
use Fedex\SharedCatalogCustomization\Helper\ManageCatalogItems;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueue;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueFactory;
use Magento\Company\Model\CompanyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\Io\File as FileIo;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Fedex\Punchout\Helper\Data;
use Fedex\SharedCatalogCustomization\Helper\EmailData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Company\Model\ResourceModel\Company\Collection as CompanyCollection;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueProcess\CollectionFactory;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueProcess\Collection;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueCleanupProcess\Collection as CleanupCollection;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueCleanupProcess\CollectionFactory as
CleanupCollectionFactory;
use Magento\Framework\DB\Select;

/**
 * Test for EmailData Helper
 */
class EmailDataTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    protected $catalogSyncQueue;
    protected $directoryWriteMock;
    /**
     * @var (\Magento\Framework\Filesystem\Io\File & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fileIo;
    protected $configInterface;
    protected $helper;
    protected $selectMock;
    protected $catalogSyncQueueCollectionFactory;
    protected $companyCollection;
    protected $curl;
    /**
     * @var (\Magento\Framework\Filesystem\Driver\File & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $file;
    protected $resourceConnectionMock;
    protected $connectionMock;
    protected $collectionItem;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $emailDataMock;
    /**
     * @var CompanyFactory
     */
    protected $companyFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CatalogSyncQueueFactory
     */
    protected $catalogSyncQueueFactory;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ManageCatalogItems
     */
    protected $manageCatalogItems;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var CollectionFactory
     */
    protected $catalogSyncQueueProcessCollectionFactory;

    /**
     * @var Collection
     */
    protected $catalogSyncQueueProcessCollection;

    /**
     * @var CleanupCollectionFactory
     */
    protected $cleanupCollectionFactory;

    /**
     * @var CleanupCollection
     */
    protected $cleanupCollection;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyFactory = $this->getMockBuilder(CompanyFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogSyncQueue = $this->getMockBuilder(CatalogSyncQueue::class)
            ->setMethods(['load', 'save','setStatus'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogSyncQueueFactory = $this->getMockBuilder(CatalogSyncQueueFactory::class)
            ->setMethods(['create','load', 'setStatus'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->directoryList = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPath'])
            ->getMock();

        $this->filesystem = $this->createMock(Filesystem::class);

        $this->directoryWriteMock = $this->getMockBuilder(Write::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDirectory','lock','create','openFile','writeCsv'])
            ->getMock();

        $this->createMock(Write::class);

        $this->filesystem->expects($this->any())->method('getDirectoryWrite')->willReturn($this->directoryWriteMock);

        $this->fileIo = $this->getMockBuilder(FileIo::class)
            ->setMethods(['writeFile', 'stat','create','getPath','isDirectory'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->manageCatalogItems = $this->getMockBuilder(ManageCatalogItems::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configInterface = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->helper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogSyncQueueCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create','getId', 'getSize'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheTypeList = $this->getMockBuilder(TypeListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->catalogSyncQueueProcessCollection = $this
            ->getMockBuilder(Collection::class)
            ->setMethods([
                'addAttributeToSelect',
                'load',
                'setData',
                'getData',
                'getId',
                'getSize',
                'addFieldToFilter',
                'getFirstItem',
                'getEmailSent',
                'getCreatedBy',
                'getCompanyId',
                'getEmailId',
                'setStatus',
                'getStatus',
                'getCatalogSyncQueueId',
                'getCatalogType',
                'getSharedCatalogId',
                'getCategoryId',
                'getJsonData',
                'getErrorMsg'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyCollection = $this->getMockBuilder(CompanyCollection::class)
            ->setMethods(['load','getCompanyName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->curl = $this->getMockBuilder(Curl::class)
            ->setMethods(['getBody','post'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->file = $this->getMockBuilder(File::class)
            ->setMethods(['fileGetContents'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cleanupCollectionFactory = $this->getMockBuilder(CleanupCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->cleanupCollection = $this->createMock(CleanupCollection::class, ['addFieldToFilter', 'getSize']);

        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->setMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['fetchCol', 'fetchAll','select','from'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->collectionItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyName'])
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->emailDataMock = $this->objectManager->getObject(
            EmailData::class,
            [
                'context' => $this->context,
                'companyFactory' => $this->companyFactory,
                'logger' => $this->logger,
                'catalogSyncQueueFactory' => $this->catalogSyncQueueFactory,
                'directoryList' => $this->directoryList,
                'filesystem' => $this->filesystem,
                'fileIo' => $this->fileIo,
                'manageCatalogItems' => $this->manageCatalogItems,
                'configInterface' => $this->configInterface,
                'helper' => $this->helper,
                'catalogSyncQueueProcessCollectionFactory' => $this->catalogSyncQueueCollectionFactory,
                'cleanupCollectionFactory' => $this->cleanupCollectionFactory,
                'cacheTypeList' => $this->cacheTypeList,
                'curl' => $this->curl,
                'file' => $this->file,
                'resourceConnection' => $this->resourceConnectionMock,
                'directory' => $this->directoryWriteMock
            ]
        );
    }

    /**
     * Check Queue Items status
     */
    public function testCheckQueueItemStatus()
    {
        $id = 2620;
        $processPendingCountQuery = 'SELECT catalog_sync_queue_process.catalog_sync_queue_id FROM catalog_sync_queue_process WHERE (catalog_sync_queue_id ="'.$id.'" AND status IN ("pending","processing")) UNION ALL SELECT catalog_sync_queue_cleanup_process.catalog_sync_queue_id FROM catalog_sync_queue_cleanup_process WHERE (catalog_sync_queue_id ="'.$id.'" AND status IN ("pending","processing"))';

        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
            ->willReturn($this->connectionMock);


        $completedCountQuery = 'SELECT catalog_sync_queue_process.catalog_sync_queue_id FROM catalog_sync_queue_process WHERE (catalog_sync_queue_id ="'.$id.'" AND status ="completed") UNION ALL SELECT catalog_sync_queue_cleanup_process.catalog_sync_queue_id FROM catalog_sync_queue_cleanup_process WHERE (catalog_sync_queue_id ="'.$id.'" AND catalog_type="product" AND status ="completed")';


        $failedCollectionQuery = 'SELECT catalog_sync_queue_process.id,catalog_sync_queue_process.catalog_sync_queue_id,catalog_sync_queue_process.shared_catalog_id,catalog_sync_queue_process.category_id,catalog_sync_queue_process.json_data,catalog_sync_queue_process.status,catalog_sync_queue_process.error_msg FROM catalog_sync_queue_process WHERE (catalog_sync_queue_id ="'.$id.'" AND catalog_type="product" AND status ="failed") UNION ALL SELECT catalog_sync_queue_cleanup_process.id,catalog_sync_queue_cleanup_process.catalog_sync_queue_id,catalog_sync_queue_cleanup_process.shared_catalog_id,catalog_sync_queue_cleanup_process.category_id,catalog_sync_queue_cleanup_process.json_data,catalog_sync_queue_cleanup_process.status,catalog_sync_queue_cleanup_process.error_msg FROM catalog_sync_queue_cleanup_process WHERE (catalog_sync_queue_id ="'.$id.'" AND catalog_type="product" AND status ="failed")';
        $this->connectionMock->expects($this->any())->method('fetchCol')->withConsecutive([$processPendingCountQuery],[$completedCountQuery])
            ->willReturnOnConsecutiveCalls([],[1]);

        $this->connectionMock->expects($this->any())->method('fetchAll')->withConsecutive([$failedCollectionQuery])
            ->willReturnOnConsecutiveCalls([[
                'id' => $id,
                'catalog_sync_queue_id' => '323',
                'shared_catalog_id' => '32',
                'category_id' => '43',
                'json_data' => '34',
                'status' => '42',
                'error_msg' => '2342'
            ]]);
        
        $this->catalogSyncQueueCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueProcessCollection);

        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getId')->willReturn($id);

        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('addFieldToFilter')->withConsecutive(
            ['catalog_sync_queue_id',['eq' => $id] ],
            ['status', ['neq' => 'failed' ] ],
            ['catalog_type',['eq' => 'product'] ],
            ['action_type', ['eq' => 'new' ] ],
            ['catalog_sync_queue_id',['eq' => $id] ],
            ['status', ['neq' => 'failed' ] ],
            ['catalog_type',['eq' => 'product'] ],
            ['action_type', ['eq' => 'update' ] ]
        )->willReturnSelf();

        $this->catalogSyncQueueProcessCollection->expects($this->exactly(2))->method('getSize')->willReturn('5', '6');

        $this->cleanupCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->cleanupCollection);

        $this->cleanupCollection->expects($this->any())->method('addFieldToFilter')->withConsecutive(
            ['catalog_sync_queue_id', ['eq' => $id] ],
            ['catalog_type', ['eq' => 'product'] ],
            ['status', ['eq' => 'completed' ] ],
        )->willReturnSelf();

        $this->cleanupCollection->expects($this->exactly(1))->method('getSize')->willReturn('1');

        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getEmailSent')->willReturn(null);
        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getCreatedBy')->willReturn('true');
        $this->directoryList->expects($this->any())->method('getPath')->willReturn('/catalogQueue/');
        $this->directoryWriteMock->expects($this->any())->method('create')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('openFile')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('lock')->willReturn(true);
        $this->directoryWriteMock->expects($this->any())->method('writeCsv')->willReturn(true);
        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getCompanyId')->willReturn(13);
        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getEmailId')
            ->willReturn('test@fdxx.com');
        $this->catalogSyncQueueFactory->expects($this->any())->method('create')->willReturn($this->catalogSyncQueue);
        $this->catalogSyncQueue->expects($this->any())->method('load')->willReturnSelf();
        $this->catalogSyncQueue->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->testgetCompanyName();

        $this->emailDataMock->checkQueueItemStatus($this->catalogSyncQueueProcessCollection);
    }

    /**
     * Check Queue Items status with Exception
     */
    public function testCheckQueueItemStatusWithException()
    {
        $id = 2620;
        $processPendingCountQuery = 'SELECT catalog_sync_queue_process.catalog_sync_queue_id FROM catalog_sync_queue_process WHERE (catalog_sync_queue_id ="'.$id.'" AND status IN ("pending","processing")) UNION ALL SELECT catalog_sync_queue_cleanup_process.catalog_sync_queue_id FROM catalog_sync_queue_cleanup_process WHERE (catalog_sync_queue_id ="'.$id.'" AND status IN ("pending","processing"))';

        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
            ->willReturn($this->connectionMock);

        $completedCountQuery = 'SELECT catalog_sync_queue_process.catalog_sync_queue_id FROM catalog_sync_queue_process WHERE (catalog_sync_queue_id ="'.$id.'" AND status ="completed") UNION ALL SELECT catalog_sync_queue_cleanup_process.catalog_sync_queue_id FROM catalog_sync_queue_cleanup_process WHERE (catalog_sync_queue_id ="'.$id.'" AND catalog_type="product" AND status ="completed")';

        $failedCollectionQuery = 'SELECT catalog_sync_queue_process.id,catalog_sync_queue_process.catalog_sync_queue_id,catalog_sync_queue_process.shared_catalog_id,catalog_sync_queue_process.category_id,catalog_sync_queue_process.json_data,catalog_sync_queue_process.status,catalog_sync_queue_process.error_msg FROM catalog_sync_queue_process WHERE (catalog_sync_queue_id ="'.$id.'" AND catalog_type="product" AND status ="failed") UNION ALL SELECT catalog_sync_queue_cleanup_process.id,catalog_sync_queue_cleanup_process.catalog_sync_queue_id,catalog_sync_queue_cleanup_process.shared_catalog_id,catalog_sync_queue_cleanup_process.category_id,catalog_sync_queue_cleanup_process.json_data,catalog_sync_queue_cleanup_process.status,catalog_sync_queue_cleanup_process.error_msg FROM catalog_sync_queue_cleanup_process WHERE (catalog_sync_queue_id ="'.$id.'" AND catalog_type="product" AND status ="failed")';

        $this->connectionMock->expects($this->any())->method('fetchCol')->withConsecutive([$processPendingCountQuery],[$completedCountQuery])
            ->willReturnOnConsecutiveCalls([],[1]);

        $this->connectionMock->expects($this->any())->method('fetchAll')->withConsecutive([$failedCollectionQuery])
            ->willReturnOnConsecutiveCalls([[
                'id' => $id,
                'catalog_sync_queue_id' => '323',
                'shared_catalog_id' => '32',
                'category_id' => '43',
                'json_data' => '34',
                'status' => '42',
                'error_msg' => '2342'
            ]]);

        $this->catalogSyncQueueCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueProcessCollection);

        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getId')->willReturn($id);

        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('addFieldToFilter')->withConsecutive(
            ['catalog_sync_queue_id',['eq' => $id] ],
            ['status', ['neq' => 'failed' ] ],
            ['catalog_type',['eq' => 'product'] ],
            ['action_type', ['eq' => 'new' ] ],
            ['catalog_sync_queue_id',['eq' => $id] ],
            ['status', ['neq' => 'failed' ] ],
            ['catalog_type',['eq' => 'product'] ],
            ['action_type', ['eq' => 'update' ] ]
        )->willReturnSelf();

        $this->catalogSyncQueueProcessCollection->expects($this->exactly(2))->method('getSize')->willReturn('5', '6');

        $this->cleanupCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->cleanupCollection);

        $this->cleanupCollection->expects($this->any())->method('addFieldToFilter')->withConsecutive(
            ['catalog_sync_queue_id', ['eq' => $id] ],
            ['catalog_type', ['eq' => 'product'] ],
            ['status', ['eq' => 'completed' ] ],
        )->willReturnSelf();

        $this->cleanupCollection->expects($this->exactly(1))->method('getSize')->willReturn('1');

        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getEmailSent')->willReturn(null);
        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getCreatedBy')->willReturn('true');
        $this->directoryList->expects($this->any())->method('getPath')->willReturn('/catalogQueue/');
        $this->directoryWriteMock->expects($this->any())->method('create')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('openFile')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('lock')->willReturn(true);
        $this->directoryWriteMock->expects($this->any())->method('writeCsv')->willReturn(true);
        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getCompanyId')->willReturn(13);
        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getEmailId')
            ->willReturn('test@fdxx.com');
        $this->catalogSyncQueueFactory->expects($this->any())->method('create')->willReturn($this->catalogSyncQueue);
        $this->catalogSyncQueue->expects($this->any())->method('load')->willReturnSelf();
        $this->catalogSyncQueue->expects($this->any())->method('setStatus')->willThrowException(new \Exception());
        $this->testgetCompanyName();

        $this->emailDataMock->checkQueueItemStatus($this->catalogSyncQueueProcessCollection);
    }

    /**
     * Testcase Get Company Name By Id
     */
    public function testgetCompanyName()
    {
        $companyId = 12;
        $companyName = 'Company';
        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->companyCollection);
        $this->companyCollection->expects($this->any())->method('load')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('getCompanyName')->willReturn($companyName);
        $this->assertEquals('Company',$this->emailDataMock->getCompanyName($companyId));
    }

    /**
     * Testcase Get Company Name By Id with Exception
     */
    public function testgetCompanyNameWithException()
    {
        $companyName = 'Company';
        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->companyCollection);
        $this->companyCollection->expects($this->any())->method('load')->willThrowException(new \Exception());
        $this->collectionItem->expects($this->any())->method('getCompanyName')->willThrowException(new \Exception());
        $this->assertEquals(null,$this->emailDataMock->getCompanyName($this->collectionItem));
    }

    /**
     * Send Mail
     *
     * @param $templateVars
     * @param $attachFile
     * @param $fileName
     *
     * @return boolean|string
     */
    public function testsendMail()
    {
        $token = '{"access_token":"token","token_type":"type"}';
        $templateVars = [
            'email_id'=>'tests@infogain.com',
            'adminName'=>'admin',
            'deleteItem'=>'3',
            'failedItem'=>'3',
            'updateItem'=>'3',
            'newItem'=>'4',
            'companyName'=>'Company'
        ];
        $attachFile = '"attachment":[
            {
                "mimeType":"text/csv",
                "fileName":"import.csv",
                "content":"byteData"
            }
        ],';

        $output ='{
            "transactionId" : "696c0a47-ee79-4e7b-95e6-4d4697c41254",
            "errors" : "error",
            "outputs" : {
              "deliveryOptions" : [ {
                "deliveryReference" : "default",
                "shipmentOptions" : [ {
                  "serviceType" : "LOCAL_DELIVERY_AM",
                  "serviceDescription" : "FedEx Local Delivery",
                  "currency" : "USD",
                  "estimatedShipmentRate" : "19.99",
                  "estimatedShipDate" : "2021-09-22",
                  "estimatedDeliveryLocalTime" : "2021-09-23T12:00:00",
                  "priceable" : true
                }, {
                  "serviceType" : "LOCAL_DELIVERY_PM",
                  "serviceDescription" : "FedEx Local Delivery",
                  "currency" : "USD",
                  "estimatedShipmentRate" : "19.99",
                  "estimatedShipDate" : "2021-09-22",
                  "estimatedDeliveryLocalTime" : "2021-09-23T17:00:00",
                  "priceable" : true
                } ]
              } ]
            }
        }';

        $this->helper->expects($this->any())->method('getTazToken')->willReturn($token);
        $this->helper->expects($this->any())->method('getAuthGatewayToken')->willReturn($token);
        $gatewayToken = $this->helper->getAuthGatewayToken();
        $access_token = '';
        $token_type = '';
        $auth_token = '';
        $this->testgetTazEmailUrl();
        $this->curl->expects($this->any())->method('post')->willReturnSelf();
        $this->curl->expects($this->any())->method('getBody')->willreturn($output);
        $attachment = '';
        $this->configInterface->expects($this->any())->method('getValue')->willReturn('Url');
        $this->assertEquals(true,$this->emailDataMock->sendMail($templateVars, $attachFile, 'import.csv'));
    }

    /**
     * Send Mail False
     *
     * @param $templateVars
     * @param $attachFile
     * @param $fileName
     *
     * @return boolean|string
     */
    public function testsendMailFalse()
    {
        $token = '{"access_token":"token","token_type":"type"}';
        $templateVars = [
            'email_id'=>'tests@infogain.com',
            'adminName'=>'admin',
            'deleteItem'=>'3',
            'failedItem'=>'3',
            'updateItem'=>'3',
            'newItem'=>'4',
            'companyName'=>'Company'
        ];
        $attachFile = '"attachment":[
            {
                "mimeType":"text/csv",
                "fileName":"import.csv",
                "content":"byteData"
            }
        ],';
        $this->helper->expects($this->any())->method('getTazToken')->willReturn($token);
        $this->helper->expects($this->any())->method('getAuthGatewayToken')->willReturn($token);
        $gatewayToken = $this->helper->getAuthGatewayToken();
        $access_token = '';
        $token_type = '';
        $auth_token = '';
        $this->testgetTazEmailUrlFalse();
        $attachment = '';
        $this->configInterface->expects($this->any())->method('getValue')->willReturn('Url');
        $this->assertEquals(true,$this->emailDataMock->sendMail($templateVars, $attachFile, 'import.csv'));
    }

    /**
     * Send Mail WithOutput
     *
     * @param $templateVars
     * @param $attachFile
     * @param $fileName
     *
     * @return boolean|string
     */
    public function testsendMailWithOutput()
    {
        $token = '{"access_token":"token","token_type":"type"}';
        $templateVars = [
            'email_id'=>'tests@infogain.com',
            'adminName'=>'admin',
            'deleteItem'=>'3',
            'failedItem'=>'3',
            'updateItem'=>'3',
            'newItem'=>'4',
            'companyName'=>'Company'
        ];
        $attachFile = '"attachment":[
            {
                "mimeType":"text/csv",
                "fileName":"import.csv",
                "content":"byteData"
            }
        ],';

        $output ='{
            "transactionId" : "696c0a47-ee79-4e7b-95e6-4d4697c41254",
            "output" : {
              "deliveryOptions" : [ {
                "deliveryReference" : "default",
                "shipmentOptions" : [ {
                  "serviceType" : "LOCAL_DELIVERY_AM",
                  "serviceDescription" : "FedEx Local Delivery",
                  "currency" : "USD",
                  "estimatedShipmentRate" : "19.99",
                  "estimatedShipDate" : "2021-09-22",
                  "estimatedDeliveryLocalTime" : "2021-09-23T12:00:00",
                  "priceable" : true
                }, {
                  "serviceType" : "LOCAL_DELIVERY_PM",
                  "serviceDescription" : "FedEx Local Delivery",
                  "currency" : "USD",
                  "estimatedShipmentRate" : "19.99",
                  "estimatedShipDate" : "2021-09-22",
                  "estimatedDeliveryLocalTime" : "2021-09-23T17:00:00",
                  "priceable" : true
                } ]
              } ]
            }
          }';

        $this->helper->expects($this->any())->method('getTazToken')->willReturn($token);
        $this->helper->expects($this->any())->method('getAuthGatewayToken')->willReturn($token);
        $gatewayToken = $this->helper->getAuthGatewayToken();
        $access_token = '';
        $token_type = '';
        $auth_token = '';
        $this->testgetTazEmailUrlFalse();
        $this->curl->expects($this->any())->method('post')->willReturnSelf();
        $this->curl->expects($this->any())->method('getBody')->willreturn($output);
        $attachment = '';
        $this->configInterface->expects($this->any())->method('getValue')->willReturn('Url');
        $this->assertEquals(true,$this->emailDataMock->sendMail($templateVars, $attachFile, 'import.csv'));
    }

    /**
     * Testcase Taz Email Url
     *
     */
    public function testgetTazEmailUrl()
    {
        $this->configInterface->expects($this->any())->method('getValue')
            ->willReturn('https://apitest.fedex.com/email/fedexoffice/v2/email');
        $this->assertEquals('https://apitest.fedex.com/email/fedexoffice/v2/email',$this->emailDataMock->getTazEmailUrl($this->catalogSyncQueueProcessCollection));
    }

    /**
     * Testcase Taz Email Url False
     *
     */
    public function testgetTazEmailUrlFalse()
    {
        $this->configInterface->expects($this->any())->method('getValue')->willReturn("fedex.com");
        $this->assertEquals("fedex.com",$this->emailDataMock->getTazEmailUrl($this->catalogSyncQueueProcessCollection));
    }

     /**
     * Check Queue Items status
     */
    public function testCheckImportQueueItemStatus()
    {
        $id = 2620;
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
           ->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->any())->method('select')->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())->method('from')->willReturnSelf();
        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getId')->willReturn($id);
        $this->selectMock->expects($this->any())->method('where')->withConsecutive(['csqp.catalog_sync_queue_id = ?', $id])->willReturnSelf();
        $this->catalogSyncQueueCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueProcessCollection);

        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getId')->willReturn($id);

        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('addFieldToFilter')->withConsecutive(
            ['catalog_sync_queue_id',['eq' => $id] ],
            ['status', ['eq' => 'completed' ]],
            ['catalog_sync_queue_id',['eq' => $id] ],
            ['status', ['eq' => 'failed'] ],
            ['catalog_sync_queue_id',['eq' => $id] ],
            ['status', ['neq' => 'failed']],
        )->willReturnSelf();
        $this->cleanupCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->cleanupCollection);
        $this->catalogSyncQueue->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->testCheckImportQueueItemStatusWithException();
        $this->emailDataMock->checkImportQueueItemStatus($this->catalogSyncQueueProcessCollection);
    }

    /**
     * test handle failed Items
     */
    public function testHandleFailedItems()
    {
        $id = 2620;
        $failedItemsCount =2;
        $newActionTypeCount = 0;
        $completedItemsCount =1;
        $updateActionTypeCount = 1;
        $emailFlag =1;
        $this->catalogSyncQueueCollectionFactory->expects($this->any())->method('create')
                ->willReturn($this->catalogSyncQueueProcessCollection);
    
        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getId')->willReturn($id);
         $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getSize')->willReturnSelf();
        $this->cleanupCollection->expects($this->any())->method('getSize')->willReturn('1');
        $this->directoryList->expects($this->any())->method('getPath')->willReturn('/catalogQueue/');
        $this->directoryWriteMock->expects($this->any())->method('create')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('openFile')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('lock')->willReturn(true);
        $this->directoryWriteMock->expects($this->any())->method('writeCsv')->willReturn(true);
        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getCompanyId')->willReturn(13);
        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getEmailId')
            ->willReturn('test@fdxx.com');
        $this->catalogSyncQueue->expects($this->any())->method('load')->willReturnSelf();
        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getCreatedBy')->willReturn('true');
        $this->testgetCompanyName();
        $this->testCheckImportQueueItemStatusWithException();
        $this->emailDataMock->handleCompletionEmailSend($this->catalogSyncQueueProcessCollection,$this->catalogSyncQueueProcessCollection,
        $failedItemsCount,$newActionTypeCount,$completedItemsCount,$updateActionTypeCount,$emailFlag);
    }


    /**
     * Check Queue Items status with Exception
     */
    public function testCheckImportQueueItemStatusWithException()
    {
        $id = 2620;
        $emailFlag =1;
        $this->catalogSyncQueueCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueProcessCollection);
        $this->catalogSyncQueue->expects($this->any())->method('load')->willReturnSelf();
        $this->catalogSyncQueueProcessCollection->expects($this->any())->method('getId')->willReturn($id);
        $this->directoryList->expects($this->any())->method('getPath')->willReturn('/catalogQueue/');
        $this->directoryWriteMock->expects($this->any())->method('create')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('openFile')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('lock')->willReturn(true);
        $this->directoryWriteMock->expects($this->any())->method('writeCsv')->willReturn(true);
        $this->catalogSyncQueueFactory->expects($this->any())->method('create')->willReturn($this->catalogSyncQueue);
        $this->catalogSyncQueue->expects($this->any())->method('load')->willReturnSelf();
        $this->catalogSyncQueue->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->catalogSyncQueue->expects($this->any())->method('save')->willThrowException(new \Exception());
        $this->testgetCompanyName();
        $this->emailDataMock->updateSyncStatus($this->catalogSyncQueueProcessCollection,$emailFlag);
    }

}
