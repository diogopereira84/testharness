<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Model;

use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use \Psr\Log\LoggerInterface;
use Fedex\Import\Helper\Data;
use Magento\Framework\Filesystem;
use Magento\ImportExport\Helper\Data as ImportExportData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\ImportExport\Model\Import\ConfigInterface;
use Magento\ImportExport\Model\Import\Entity\Factory;
use Magento\ImportExport\Model\ResourceModel\Import\Data as ImportData;
use Magento\ImportExport\Model\Export\Adapter\CsvFactory;
use Magento\Framework\HTTP\Adapter\FileTransferFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\ImportExport\Model\Source\Import\Behavior\Factory as BehaviorFactory;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\ImportExport\Model\History;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\AbstractSource;
use Magento\Framework\Exception\LocalizedException;

class Import extends \Magento\ImportExport\Model\Import
{

    /**
     * Limit displayed errors on Import History page.
     */
    public const LIMIT_VISIBLE_ERRORS = 5;

    /**
     * Create attributes configuration path.
     */
    public const CREATE_ATTRIBUTES_CONF_PATH = 'fedex_import/general/create_attributes';

    /**
     * @var Data $_helper
     */
    protected $_helper;

    /**
     * @var Timezone $_timezone
     */
    protected $_timezone;

    /**
     * @var mixed $_source
     */
    protected $_source;

    /**
     * Import Class constructor
     *
     * @param Data $helper
     * @param LoggerInterface $logger
     * @param Filesystem $filesystem
     * @param ImportExportData $importExportData
     * @param ScopeConfigInterface $coreConfig
     * @param ConfigInterface $importConfig
     * @param Factory $entityFactory
     * @param ImportData $importData
     * @param CsvFactory $csvFactory
     * @param FileTransferFactory $httpFactory
     * @param UploaderFactory $uploaderFactory
     * @param BehaviorFactory $behaviorFactory
     * @param IndexerRegistry $indexerRegistry
     * @param History $importHistoryModel
     * @param DateTime $localeDate
     * @param array $data
     */
    public function __construct(
        Data $helper,
        protected LoggerInterface $logger,
        Filesystem $filesystem,
        ImportExportData $importExportData,
        ScopeConfigInterface $coreConfig,
        ConfigInterface $importConfig,
        Factory $entityFactory,
        ImportData $importData,
        CsvFactory $csvFactory,
        FileTransferFactory $httpFactory,
        UploaderFactory $uploaderFactory,
        BehaviorFactory $behaviorFactory,
        IndexerRegistry $indexerRegistry,
        protected History $importHistoryModel,
        DateTime $localeDate,
        array $data = []
    ) {
        $this->_helper = $helper;

        parent::__construct(
            $this->logger,
            $filesystem,
            $importExportData,
            $coreConfig,
            $importConfig,
            $entityFactory,
            $importData,
            $csvFactory,
            $httpFactory,
            $uploaderFactory,
            $behaviorFactory,
            $indexerRegistry,
            $this->importHistoryModel,
            $localeDate,
            $data
        );
        $this->_debugMode = $helper->getDebugMode();
    }

    /**
     * Prepare source type class name
     *
     * @param string $sourceType
     * @return string
     * @codeCoverageIgnore
     */
    protected function _prepareSourceClassName($sourceType)
    {
        return 'Fedex\Import\Model\Source\Type\\' . ucfirst(strtolower($sourceType));
    }

    /**
     * Check if remote file was modified since the last import
     *
     * @param string $timestamp
     * @return bool
     */
    public function checkModified($timestamp)
    {
        if ($this->getSource()) {
            return $this->getSource()->checkModified($timestamp);
        }

        /**
         * @TODO: check if file on current server was modified since the last import process
         */
        if ($this->getImportSource() == 'file') {

        }

        return true;
    }

    /**
     * Download remote source file to temporary directory
     *
     * @TODO change the code to show exceptions on frontend instead of 503 error.
     * @return null|string
     * @throws LocalizedException
     * @codeCoverageIgnore
     */
    public function uploadSource()
    {
        $result = null;
        if ($this->getImportSource() && $this->getImportSource() != 'file') {
            $source = $this->getSource();
            try {
                $result = $source->uploadSource();
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__.':'.__LINE__.' '.$e->getMessage());
                throw new LocalizedException(__($e->getMessage()));
            }
        } else {
            $uploader = $this->_uploaderFactory->create(['fileId' => static::FIELD_NAME_SOURCE_FILE]);
            $extension = $uploader->getFileExtension();
            if ($extension == 'tar') {
                $uploader->skipDbProcessing(true);
                $archiveData = $uploader->save($this->getWorkingDir());
                $phar = new \PharData($archiveData['path'] . $archiveData['file']);
                $phar->extractTo($archiveData['path'], null, true);
                $fileName = $phar->getFilename();
                $result = $result['path'] . $fileName;
            } elseif ($extension == 'txt') {
                $fileData = $uploader->save($this->getWorkingDir());
                $result = $fileData['path'] . $fileData['file'];
            }

        }

        if ($result) {
            $sourceFileRelative = $this->_varDirectory->getRelativePath($result);
            $entity = $this->getEntity();
            $this->createHistoryReport($sourceFileRelative, $entity);

            return $result;
        }

        return parent::uploadSource();
    }

    /**
     * Validates source file and returns validation result.
     *
     * @param AbstractSource $source
     * @return bool
     * @codeCoverageIgnore
     */
    public function validateSource(AbstractSource $source)
    {
        $this->addLogComment(__('Begin data validation'));
        try {
            $adapter = $this->_getEntityAdapter()->setSource($source);
            $errorAggregator = $adapter->validateData();
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__.':'.__LINE__.' '.$e->getMessage());
            $errorAggregator = $this->getErrorAggregator();
            $errorAggregator->addError(
                AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION
                    . '. ' . $e->getMessage(),
                ProcessingError::ERROR_LEVEL_CRITICAL,
                null,
                null,
                null,
                $e->getMessage()
            );
        }

        $messages = $this->getOperationResultMessages($errorAggregator);
        $this->addLogComment($messages);

        $result = !$errorAggregator->getErrorsCount();
        if ($result) {
            $this->addLogComment(__('Import data validation is complete.'));
        } else {
            if ($this->isReportEntityType()) {
                $this->importHistoryModel->load($this->importHistoryModel->getLastItemId());
                $summary = '';
                if ($errorAggregator->getErrorsCount() > self::LIMIT_VISIBLE_ERRORS) {
                    $summary = __('Too many errors. Please check your debug log file.') . '<br />';
                    $this->logger->error(__METHOD__.':'.__LINE__.' Error count surpassed error limit');
                } else {
                    if ($this->getJobId()) {
                        $summary = __('Import job #' . $this->getJobId() . ' failed.') . '<br />';
                        $this->logger->error(__METHOD__.':'.__LINE__.' Import job #: '.$this->getJobId().' failed');
                    }

                    foreach ($errorAggregator->getRowsGroupedByErrorCode() as $errorMessage => $rows) {
                        $error = $errorMessage . ' ' . __('in rows') . ': ' . implode(', ', $rows);
                        $summary .= $error . '<br />';
                        $this->logger->error(__METHOD__.':'.__LINE__.' '.$error);
                    }
                }
                $date = $this->_timezone->formatDateTime(
                    new \DateTime(),
                    \IntlDateFormatter::MEDIUM,
                    \IntlDateFormatter::MEDIUM,
                    null,
                    null
                );
                $summary .= '<i>' . $date . '</i>';
                $this->importHistoryModel->setSummary($summary);
                $this->importHistoryModel->setExecutionTime(History::IMPORT_FAILED);
                $this->importHistoryModel->save();
            }
        }
        return $result;
    }

    /**
     * Get source
     */
    public function getSource()
    {
        if (!$this->_source) {
            $sourceType = $this->getImportSource();
            try {
                $this->_source = $this->_helper->getSourceModelByType($sourceType);
                $this->_source->setData($this->getData());
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__.':'.__LINE__.' '.$e->getMessage());
            }
        }

        return $this->_source;
    }

    /**
     * Get Import History Model obj
     *
     * @return mixed
     */
    public function getImportHistoryModel()
    {
        return $this->importHistoryModel;
    }

    /**
     * CreateHistoryReport
     *
     * @param string $sourceFileRelative
     * @param string $entity
     * @param mixed|null $extension
     * @param mixed|null $result
     * @codeCoverageIgnoreStart
     * @return $this
     */
    protected function createHistoryReport($sourceFileRelative, $entity, $extension = null, $result = null)
    {
        return parent::createHistoryReport($sourceFileRelative, $entity, $extension = null, $result = null);
    }
}
