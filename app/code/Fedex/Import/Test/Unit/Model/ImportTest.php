<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Test\Unit\Helper;

use Fedex\Import\Model\Source\Factory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory;
use PHPUnit\Framework\TestCase;
use Fedex\B2b\Model\NegotiableQuoteManagement;
use Fedex\Import\Model\Import;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem;
use Magento\ImportExport\Helper\Data as ImportExportData;
use Magento\ImportExport\Model\Import\ConfigInterface as ImportConfig;
use Magento\ImportExport\Model\Import\Entity\Factory as EntityFactory;
use Magento\ImportExport\Model\ResourceModel\Import\Data as ImportData;
use Magento\ImportExport\Model\Export\Adapter\CsvFactory as CsvFactory;
use Magento\Framework\HTTP\Adapter\FileTransferFactory as HttpFactory;
use Magento\MediaStorage\Model\File\UploaderFactory as UploaderFactory;
use Magento\ImportExport\Model\Source\Import\Behavior\Factory as BehaviorFactory;
use Magento\Framework\Indexer\IndexerRegistry as IndexerRegistry;
use Magento\ImportExport\Model\History as ImportHistoryModel;
use Magento\Framework\Stdlib\DateTime\DateTime as LocaleDate;
use Fedex\Import\Helper\Data as ImportDataHelper;
use Fedex\Import\Model\Source\Type\AbstractType;
use Magento\ImportExport\Model\Import\AbstractSource;
use Magento\ImportExport\Model\Source\Import as ImportSource;
use Magento\Framework\Phrase;
use Magento\ImportExport\Model\Import as ImportExportImport;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\ImportExport\Model\Import as VendorImport;
use Magento\ImportExport\Model\Import\ConfigInterface;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\HTTP\Adapter\FileTransferFactory;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\ImportExport\Helper\Data;
use Magento\ImportExport\Model\History;
use Magento\ImportExport\Model\Import\Config;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\Import\Source\Csv;
use Magento\ImportExport\Test\Unit\Model\Import\AbstractImportTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ImportTest extends TestCase
{
    /**
     * @var (\Magento\ImportExport\Model\Import\ConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $importConfig;
    /**
     * @var (\Magento\ImportExport\Model\Import & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $VendorImport;
    /**
     * @var (\Magento\Framework\Exception\LocalizedException & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $localizedException;
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $ContextMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $LoggerMock;
    /**
     * @var (\Magento\Framework\Filesystem & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $FileSystemMock;
    /**
     * @var (\Magento\ImportExport\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $ImportExportDataMock;
    /**
     * @var (\Magento\Framework\App\Config\ScopeConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $ScopeConfigInterfaceMock;
    /**
     * @var (\Magento\ImportExport\Model\Import\ConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $ImportConfigMock;
    /**
     * @var (\Magento\ImportExport\Model\Import\Entity\Factory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $EntityFactoryMock;
    /**
     * @var (\Magento\ImportExport\Model\ResourceModel\Import\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $ImportDataMock;
    /**
     * @var (\Magento\ImportExport\Model\Export\Adapter\CsvFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $CsvFactoryMock;
    /**
     * @var (\Magento\Framework\HTTP\Adapter\FileTransferFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $HttpFactoryMock;
    /**
     * @var (\Magento\MediaStorage\Model\File\UploaderFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $UploaderFactoryMock;
    /**
     * @var (\Magento\MediaStorage\Model\File\Uploader & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $UploaderMock;
    /**
     * @var (\Magento\ImportExport\Model\Source\Import\Behavior\Factory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $BehaviorFactoryMock;
    /**
     * @var (\Magento\Framework\Indexer\IndexerRegistry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $IndexerRegistryMock;
    /**
     * @var (\Magento\ImportExport\Model\History & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $ImportHistoryModelMock;
    /**
     * @var (\Magento\Framework\Stdlib\DateTime\DateTime & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $LocaleDateMock;
    protected $_helper;
    protected $AbstractTypeMock;
    /**
     * @var (\Magento\ImportExport\Model\Import\AbstractSource & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $SourceMock;
    /**
     * @var (\Magento\ImportExport\Model\Source\Import & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $importSourceMock;
    /**
     * @var (\Magento\Framework\Filesystem\Directory\WriteInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $writeInterface;
    protected $MockImport;
    /**
     * @var object
     */
    protected $import;
    /**
     * @var ProcessingErrorAggregatorInterface|MockObject
     */
    private $errorAggregatorMock;

    /**
     * AbstractEntity Mock entityAdapter.
     *
     * @var AbstractEntity|MockObject
     */
    protected $_entityAdapter;

    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->importConfig = $this->getMockBuilder(ConfigInterface::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->VendorImport = $this->getMockBuilder(VendorImport::class)
        ->setMethods(['getWorkingDir','_getEntityAdapter'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->localizedException = $this->getMockBuilder(LocalizedException::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ContextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getScopeConfig'])
            ->getMock();

        $this->LoggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->FileSystemMock = $this->getMockBuilder(Filesystem::class)
            ->setMethods(['getDirectoryWrite','getAbsolutePath'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->ImportExportDataMock = $this->getMockBuilder(ImportExportData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ScopeConfigInterfaceMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->ImportConfigMock = $this->getMockBuilder(ImportConfig::class)
            ->setMethods(['getEntities','getEntityTypes','getRelatedIndexers'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->EntityFactoryMock = $this->getMockBuilder(EntityFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ImportDataMock = $this->getMockBuilder(ImportData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->CsvFactoryMock = $this->getMockBuilder(CsvFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->HttpFactoryMock = $this->getMockBuilder(HttpFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->UploaderFactoryMock = $this->getMockBuilder(UploaderFactory::class)
        ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->UploaderMock = $this->getMockBuilder(Uploader::class)
            ->setMethods(['getFileExtension','save','skipDbProcessing'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->BehaviorFactoryMock = $this->getMockBuilder(BehaviorFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->IndexerRegistryMock = $this->getMockBuilder(IndexerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ImportHistoryModelMock = $this->getMockBuilder(ImportHistoryModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->LocaleDateMock = $this->getMockBuilder(LocaleDate::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->_helper = $this->getMockBuilder(ImportDataHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSourceModelByType','setData','checkModified'])
            ->getMock();

        $this->AbstractTypeMock = $this->getMockBuilder(AbstractType::class)
        ->setMethods(['checkModified','uploadSource','importImage','getSourceClient','setData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->SourceMock = $this->getMockBuilder(\Magento\ImportExport\Model\Import\AbstractSource::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->importSourceMock = $this->getMockBuilder(ImportSource::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->writeInterface = $this->getMockBuilder(WriteInterface::class)
        ->setMethods(['getAbsolutePath'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->_entityAdapter = $this->getMockBuilder(AbstractEntity::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'importData',
                    '_saveValidatedBunches',
                    'getErrorAggregator',
                    'setSource',
                    'validateData',
                ]
            )
            ->getMockForAbstractClass();
        $objectManagerHelper = new ObjectManager($this);
        $this->MockImport = $objectManagerHelper->getObject(
            Import::class,
            [
                'logger' => $this->LoggerMock,
                'filesystem' => $this->FileSystemMock,
                'importExportData' => $this->ImportExportDataMock,
                'coreConfig' => $this->ScopeConfigInterfaceMock,
                'importConfig' => $this->ImportConfigMock,
                'entityFactory' => $this->EntityFactoryMock,
                'importData' => $this->ImportDataMock,
                'csvFactory' => $this->CsvFactoryMock,
                'httpFactory' => $this->HttpFactoryMock,
                'uploaderFactory' => $this->UploaderFactoryMock,
                'behaviorFactory' => $this->BehaviorFactoryMock,
                'indexerRegistry' => $this->IndexerRegistryMock,
                'importHistoryModel' => $this->ImportHistoryModelMock,
                'localeDate' => $this->LocaleDateMock,
                '_helper' => $this->_helper,
                '_varDirectory' => $this->writeInterface,
                'importConfig' => $this->importConfig
            ]
        );
        $this->import = $objectManagerHelper->getObject(Import::class);
    }

    /**
     * Test method for getSource
     *
     * @return void
     */
    public function testGetSource()
    {
        $this->_helper->expects($this->any())->method('getSourceModelByType')->willReturn($this->AbstractTypeMock);
        $this->AbstractTypeMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->MockImport->getSource();
    }

    /**
     * Test method for GetSourceWithException
     *
     * @return void
     */
    public function testGetSourceWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->_helper->expects($this->any())->method('getSourceModelByType')->willThrowException($exception);
        $this->_helper->expects($this->any())->method('setData')->willReturnSelf();
        $this->MockImport->getSource();
    }

    /**
     * Test method for getImportHistoryModel
     *
     * @return void
     */
    public function testgetImportHistoryModel()
    {
        $this->MockImport->getImportHistoryModel();
    }

    /**
     * Test method for CheckModifiedWithException
     *
     * @return void
     */
    public function testCheckModifiedWithException()
    {
        $timestamp = '10-06-26 02:31:29,573';
        $this->testGetSourceWithException();
        $this->AbstractTypeMock->expects($this->any())->method('checkModified')->with($timestamp)->willReturn(true);
        $this->MockImport->checkModified($timestamp);
    }
}
