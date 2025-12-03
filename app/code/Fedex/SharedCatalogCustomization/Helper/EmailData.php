<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Helper;

use Fedex\Header\Helper\Data as HeaderData;
use Magento\Framework\App\Helper\Context;
use Fedex\SharedCatalogCustomization\Helper\ManageCatalogItems;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueFactory;
use Magento\Company\Model\CompanyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\Io\File as FileIo;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\HTTP\Client\Curl;
use Fedex\Punchout\Helper\Data as PunchoutDataHelper;
use Magento\Framework\App\ResourceConnection;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueProcess\CollectionFactory;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueCleanupProcess\CollectionFactory as
CleanupCollectionFactory;

/**
 * Data Helper
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class EmailData extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const COUNT = 'count(*)';
    private Filesystem\Directory\WriteInterface $directory;

    /**
     * EmailData constructor
     *
     * @param Context $context
     * @param CompanyFactory $companyFactory
     * @param LoggerInterface  $logger
     * @param CatalogSyncQueueFactory $catalogSyncQueueFactory
     * @param DirectoryList $directoryList
     * @param Filesystem $filesystem
     * @param FileIo $fileIo
     * @param ManageCatalogItems $manageCatalogItems
     * @param ScopeConfigInterface $configInterface
     * @param PunchoutDataHelper $helper
     * @param CollectionFactory $catalogSyncQueueProcessCollectionFactory
     * @param CleanupCollectionFactory $cleanupCollectionFactory
     * @param TypeListInterface $cacheTypeList
     * @param Curl $curl
     * @param File $file
     * @param ResourceConnection $resourceConnection
     * @Param HeaderData $headerData
     */
    public function __construct(
        Context $context,
        protected CompanyFactory $companyFactory,
        protected LoggerInterface $logger,
        protected CatalogSyncQueueFactory $catalogSyncQueueFactory,
        protected DirectoryList $directoryList,
        protected Filesystem $filesystem,
        private FileIo $fileIo,
        protected ManageCatalogItems $manageCatalogItems,
        protected ScopeConfigInterface $configInterface,
        private PunchoutDataHelper $helper,
        protected CollectionFactory $catalogSyncQueueProcessCollectionFactory,
        protected CleanupCollectionFactory $cleanupCollectionFactory,
        protected TypeListInterface $cacheTypeList,
        protected Curl $curl,
        protected File $file,
        private ResourceConnection $resourceConnection,
        protected HeaderData $headerData
    ) {
        parent::__construct($context);
        $this->directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    /**
     * Check Queue Items status
     *
     * @param object $sharedCatalogSyncQueueData
     */
    public function checkQueueItemStatus(object $sharedCatalogSyncQueueData)
    {
        $connection = $this->resourceConnection->getConnection();
        //For Select query
        $processPendingCountQuery = 'SELECT catalog_sync_queue_process.catalog_sync_queue_id FROM catalog_sync_queue_process WHERE (catalog_sync_queue_id ="'.$sharedCatalogSyncQueueData->getId().'" AND status IN ("pending","processing")) UNION ALL SELECT catalog_sync_queue_cleanup_process.catalog_sync_queue_id FROM catalog_sync_queue_cleanup_process WHERE (catalog_sync_queue_id ="'.$sharedCatalogSyncQueueData->getId().'" AND status IN ("pending","processing"))';

        $processPendingCount = count($connection->fetchCol($processPendingCountQuery));
        if ($processPendingCount == 0) {
             //For Select query
            $completedCountQuery = 'SELECT catalog_sync_queue_process.catalog_sync_queue_id FROM catalog_sync_queue_process WHERE (catalog_sync_queue_id ="'.$sharedCatalogSyncQueueData->getId().'" AND status ="completed") UNION ALL SELECT catalog_sync_queue_cleanup_process.catalog_sync_queue_id FROM catalog_sync_queue_cleanup_process WHERE (catalog_sync_queue_id ="'.$sharedCatalogSyncQueueData->getId().'" AND catalog_type="product" AND status ="completed")';

            $completedCount = count($connection->fetchCol($completedCountQuery));

            if ($completedCount > 0) {

                $failedCollectionQuery = 'SELECT catalog_sync_queue_process.id,catalog_sync_queue_process.catalog_sync_queue_id,catalog_sync_queue_process.shared_catalog_id,catalog_sync_queue_process.category_id,catalog_sync_queue_process.json_data,catalog_sync_queue_process.status,catalog_sync_queue_process.error_msg FROM catalog_sync_queue_process WHERE (catalog_sync_queue_id ="'.$sharedCatalogSyncQueueData->getId().'" AND catalog_type="product" AND status ="failed") UNION ALL SELECT catalog_sync_queue_cleanup_process.id,catalog_sync_queue_cleanup_process.catalog_sync_queue_id,catalog_sync_queue_cleanup_process.shared_catalog_id,catalog_sync_queue_cleanup_process.category_id,catalog_sync_queue_cleanup_process.json_data,catalog_sync_queue_cleanup_process.status,catalog_sync_queue_cleanup_process.error_msg FROM catalog_sync_queue_cleanup_process WHERE (catalog_sync_queue_id ="'.$sharedCatalogSyncQueueData->getId().'" AND catalog_type="product" AND status ="failed")';

                $failedCollection = $connection->fetchAll($failedCollectionQuery);
                $failedCount = count($failedCollection);

                $newActionTypeCount = $this->catalogSyncQueueProcessCollectionFactory->create()
                    ->addFieldToFilter('catalog_sync_queue_id', ['eq' => $sharedCatalogSyncQueueData->getId()])
                    ->addFieldToFilter('status', ['neq' => 'failed'])
                    ->addFieldToFilter('catalog_type', ['eq' => 'product'])
                    ->addFieldToFilter('action_type', ['eq' => 'new'])
                    ->getSize();

                $updateActionTypeCount = $this->catalogSyncQueueProcessCollectionFactory->create()
                    ->addFieldToFilter('catalog_sync_queue_id', ['eq' => $sharedCatalogSyncQueueData->getId()])
                    ->addFieldToFilter('status', ['neq' => 'failed'])
                    ->addFieldToFilter('catalog_type', ['eq' => 'product'])
                    ->addFieldToFilter('action_type', ['eq' => 'update'])
                    ->getSize();

                $deleteActionTypeCount = $this->cleanupCollectionFactory->create()
                    ->addFieldToFilter('catalog_sync_queue_id', ['eq' => $sharedCatalogSyncQueueData->getId()])
                    ->addFieldToFilter('catalog_type', ['eq' => 'product'])
                    ->addFieldToFilter('status', ['eq' => 'completed'])
                    ->getSize();

                $isEmailSent = $sharedCatalogSyncQueueData->getEmailSent();
                $createdBy = $sharedCatalogSyncQueueData->getCreatedBy();
                $attachFile = '';
                $fileName = '';
                $emailFlag = 0;

                if (($completedCount > 0 || $failedCount > 0) && empty($isEmailSent) && $createdBy != 'System') {
                    $emailFlag = 1;

                    if (!$this->directory->isDirectory('catalogQueue')) {
                        $this->directory->create('catalogQueue');
                        $directoryPath = $this->directoryList->getPath('var') . 'catalogQueue';
                    }

                    if ($failedCount > 0) {
                        $this->fileIo->mkdir('var/catalogQueue', 0775);
                        $fileName = 'catalog_sync' . date("Ymd_His") . '.csv';
                        $filepath = '/catalogQueue/' . $fileName;

                        $stream = $this->directory->openFile($filepath, 'w+');
                        $stream->lock();
                        $header = [
                            'Id',
                            'Catalog Sync Queue Id',
                            'Shared Catalog Id',
                            'Category Id',
                            'Json Data',
                            'Status',
                            'Reason for Failed'
                        ];
                        $stream->writeCsv($header);
                        // @codeCoverageIgnoreStart
                        foreach ($failedCollection as $failedQueueData) {
                            $data = [];
                            $data[] = $failedQueueData['id'];
                            $data[] = $failedQueueData['catalog_sync_queue_id'];
                            $data[] = $failedQueueData['shared_catalog_id'];
                            $data[] = $failedQueueData['category_id'];
                            $data[] = $failedQueueData['json_data'];
                            $data[] = $failedQueueData['status'];
                            $data[] = $failedQueueData['error_msg'];

                            $stream->writeCsv($data);

                            $attachFile = $this->file->fileGetContents(
                                $this->directoryList->getPath('var') . $filepath
                            );
                        }
                        // @codeCoverageIgnoreEnd
                    }

                    $companyName = $this->getCompanyName($sharedCatalogSyncQueueData->getCompanyId());
                    $createdByUserEmail = $sharedCatalogSyncQueueData->getEmailId();

                    $templateVars = [];

                    $templateVars['companyName'] = $companyName;
                    $templateVars['adminName'] = $createdBy;
                    $templateVars['failedItem'] = $failedCount;
                    $templateVars['completedItem'] = $completedCount;
                    $templateVars['newItem'] = $newActionTypeCount;
                    $templateVars['updateItem'] = $updateActionTypeCount;
                    $templateVars['deleteItem'] = $deleteActionTypeCount;
                    $templateVars['email_id'] = $createdByUserEmail;

                    // calling send Mail method for sending mail
                    $this->sendMail($templateVars, $attachFile, $fileName);
                }

                try {

                    $catalogSynchQueue = $this->catalogSyncQueueFactory->create()
                        ->load($sharedCatalogSyncQueueData->getId());

                    $catalogSynchQueue->setStatus($this->manageCatalogItems::STATUS_COMPLETED)
                        ->setemailSent($emailFlag)
                        ->save();
                    $this->logger->info(__METHOD__.':'.__LINE__.':Catalog sync queue completed.');
                } catch (\Exception $e) {
                    $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage() . ' id for ' . $sharedCatalogSyncQueueData->getId());
                }
            }
        }
    }

    /**
     * Get Company Name By Id
     *
     * @param int $companyId
     * @return string $companyName
     */
    public function getCompanyName($companyId)
    {
        $companyName = '';
        try {
            $companyData = $this->companyFactory->create()->load($companyId);
            $companyName = $companyData->getCompanyName();
            $this->logger->info(__METHOD__.':'.__LINE__.':'.$companyId.' company name found.');
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
        }
        return $companyName;
    }

    /**
     * Send Mail
     *
     * @param array $templateVars
     * @param string $attachFile
     * @param string $fileName
     *
     * @return boolean
     */
    public function sendMail($templateVars, $attachFile, $fileName)
    {
        $tazToken = $this->helper->getTazToken();
        $gatewayToken = $this->helper->getAuthGatewayToken();

        $setupURL = $this->getTazEmailUrl();
        // @codeCoverageIgnoreStart
        $byteData = base64_encode((string)$attachFile);
        // @codeCoverageIgnoreEnd
        $attachment = '';
        if (!empty($attachFile)) {
            $attachment = '"attachment":[
                {
                    "mimeType":"text/csv",
                    "fileName":"' . $fileName . '",
                    "content":"' . $byteData . '"
                }
            ],';
        }
        if ($tazToken) {
            $jdata = '{
                "email":{
                    "from":{
                        "address":"'
                        .$this->configInterface
                        ->getValue('trans_email/ident_general/email', ScopeInterface::SCOPE_STORE).'"
                    },
                    "to":[{
                        "address":"' . $templateVars['email_id'] . '",
                        "name":"' . $templateVars['adminName'] . '"
                    }],
                    "templateId":"ePro_catalog_sync_confirmation",
                    "templateData":"{\"channel\":\"Web\",\"producingCompany\":{\"name\":\"FedEx Corp\"},\"targetCompany\":{\"name\":\"' . $templateVars['companyName'] . '\",\"productsAdded\":\"' . $templateVars['newItem'] . '\",\"productsUpdated\":\"' . $templateVars['updateItem'] . '\",\"productsFailed\":\"' . $templateVars['failedItem'] . '\",\"productsDeleted\":\"' . $templateVars['deleteItem'] . '\",\"primaryContact\":{\"firstLastName\":\"' . $templateVars['adminName'] . '\"}},\"order\":{\"productionCostAmount\":\"$0.00\"},\"user\":{\"emailaddr\":\"' . $templateVars['email_id'] . '\"}}",
                    '.$attachment.'
                    "directSMTPFlag":"false"
                }
            }';
            $authHeaderVal = $this->headerData->getAuthHeaderValue();
            $headers = [
                "Content-Type: application/json",
                "Content-Length: " . strlen($jdata),
                $authHeaderVal . $gatewayToken,
                "Cookie: Bearer=" . $tazToken
            ];

            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $jdata,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => ''
                ]
            );
            $this->curl->post($setupURL, $jdata);
            $output = (string)$this->curl->getBody();
            $outputData = json_decode($output, true);

            try {
                if (isset($outputData['errors']) || !isset($outputData['output'])) {
                    $this->logger->critical(__METHOD__.':'.__LINE__.':Taz Email API Error: '. $templateVars['email_id'] . ' : '
                    . json_encode($outputData['errors']));
                }
            } catch (\Exception $e) {
                $this->logger->critical("Taz Email API Error: " . $templateVars['email_id'] . ' : ' . $e->getMessage());
            }

            return true;
        }
    }

    /**
     * Taz Email Url
     *
     * @return string
     */
    public function getTazEmailUrl()
    {
        return $this->configInterface->getValue("fedex/taz/taz_email_api_url");
    }

     /**
     * Check Queue Items status
     *
     * @param object $sharedCatalogSyncQueueData
     */
    public function checkImportQueueItemStatus(object $sharedCatalogSyncQueueData)
    {
        $connection = $this->resourceConnection->getConnection();
        $condition = array('pending', 'processing');

        $catalogSyncSelectedObj = $connection->select()->from(['csqp' => 'catalog_sync_queue_process'],
            'csqp.catalog_sync_queue_id')->where('csqp.catalog_sync_queue_id = ?', $sharedCatalogSyncQueueData->getId())
            ->where('csqp.status  IN (?)', $condition);

        $catalogMigrationSelectedObj = $connection->select()->from(['csqcp' => 'catalog_migration_process'],
            'csqcp.catalog_sync_queue_id')->where('csqcp.catalog_sync_queue_id = ?',
            $sharedCatalogSyncQueueData->getId())->where('csqcp.status IN (?)', $condition);

        $selectUnionObj = $connection->select();

        $selectUnionObj->union(array($catalogSyncSelectedObj, $catalogMigrationSelectedObj));
        // Wrap a SELECT count(*) statement around the union
        $selectCount = $connection->select();
        $processItemFromObj = $selectCount->from($selectUnionObj, self::COUNT);
        $processPendingCountArray = $connection->fetchAll($processItemFromObj);

        $pendingProcessItemsCount = isset($processPendingCountArray[0][self::COUNT]) ?
        $processPendingCountArray[0][self::COUNT] : 0;

        if ($pendingProcessItemsCount == 0) {
            $completedItemsCollection = $this->catalogSyncQueueProcessCollectionFactory->create()
                ->addFieldToFilter('catalog_sync_queue_id', ['eq' => $sharedCatalogSyncQueueData->getId()])
                ->addFieldToFilter('status', ['eq' => 'completed']);
            $completedItemsCount = $completedItemsCollection->getSize();

            $failedItemsCollection = $this->catalogSyncQueueProcessCollectionFactory->create()
                ->addFieldToFilter('catalog_sync_queue_id', ['eq' => $sharedCatalogSyncQueueData->getId()])
                ->addFieldToFilter('status', ['eq' => 'failed']);
            $failedItemsCount = $failedItemsCollection->getSize();

            if ($completedItemsCount > 0 || $failedItemsCount > 0) {
                $newActionTypeCount = $this->catalogSyncQueueProcessCollectionFactory->create()
                    ->addFieldToFilter('catalog_sync_queue_id', ['eq' => $sharedCatalogSyncQueueData->getId()])
                    ->addFieldToFilter('status', ['neq' => 'failed'])
                    ->addFieldToFilter('catalog_type', ['eq' => 'product'])
                    ->addFieldToFilter('action_type', ['eq' => 'new'])
                    ->getSize();

                $updateActionTypeCount = $this->catalogSyncQueueProcessCollectionFactory->create()
                    ->addFieldToFilter('catalog_sync_queue_id', ['eq' => $sharedCatalogSyncQueueData->getId()])
                    ->addFieldToFilter('status', ['neq' => 'failed'])
                    ->addFieldToFilter('catalog_type', ['eq' => 'product'])
                    ->addFieldToFilter('action_type', ['eq' => 'update'])
                    ->getSize();
                $emailFlag = 0;

                if (empty($emailFlag)) {
                    $emailFlag = 1;
                    // handle completion email
                    $this->handleCompletionEmailSend(
                        $sharedCatalogSyncQueueData,
                        $failedItemsCollection,
                        $failedItemsCount,
                        $newActionTypeCount,
                        $completedItemsCount,
                        $updateActionTypeCount,
                        $emailFlag
                    );
                }
            }
        }
    }

    /**
     * Handle Failed Items
     * @param object $sharedCatalogSyncQueueData
     * @param object $failedItemsCollection
     * @param int $failedItemsCount
     * @param int $newActionTypeCount
     * @param int $completedItemsCount
     * @param int $updateActionTypeCount
     */
    public function handleCompletionEmailSend($sharedCatalogSyncQueueData, $failedItemsCollection,
        $failedItemsCount, $newActionTypeCount, $completedItemsCount, $updateActionTypeCount, $emailFlag) {

        if (!$this->directory->isDirectory('catalogQueue')) {
            $this->directory->create('catalogQueue');
        }

        // In case of Failed Items send an email attachment
        $attachFile = '';
        $fileName = '';
        if ($failedItemsCount > 0) {
            $this->fileIo->mkdir('var/catalogQueue', 0775);
            $fileName = 'catalog_sync' . date("Ymd_His") . '.csv';
            $filepath = '/catalogQueue/' . $fileName;

            $stream = $this->directory->openFile($filepath, 'w+');
            $stream->lock();
            $header = [
                'Id',
                'Catalog Sync Queue Id',
                'Json Data',
                'Status',
                'Reason for Failed'
            ];
            $stream->writeCsv($header);
            // @codeCoverageIgnoreStart
            foreach ($failedItemsCollection as $failedQueueData) {
                $data = [];
                $data[] = $failedQueueData['id'];
                $data[] = $failedQueueData['catalog_sync_queue_id'];
                $data[] = $failedQueueData['json_data'];
                $data[] = $failedQueueData['status'];
                $data[] = $failedQueueData['error_msg'];

                $stream->writeCsv($data);

                $attachFile = $this->file->fileGetContents(
                    $this->directoryList->getPath('var') . $filepath
                );
            }
        }
        // @codeCoverageIgnoreEnd

        $companyName = $this->getCompanyName($sharedCatalogSyncQueueData->getCompanyId());
        $createdByUserEmail = $sharedCatalogSyncQueueData->getEmailId();
        $createdBy = $sharedCatalogSyncQueueData->getCreatedBy();

        $templateVars = [];

        $templateVars['companyName'] = $companyName;
        $templateVars['adminName'] = $createdBy;
        $templateVars['failedItem'] = $failedItemsCount;
        $templateVars['completedItem'] = $completedItemsCount;
        $templateVars['newItem'] = $newActionTypeCount;
        $templateVars['updateItem'] = $updateActionTypeCount;
        $templateVars['deleteItem'] = 0;
        $templateVars['email_id'] = $createdByUserEmail;

        // calling send Mail method for sending mail
        $this->sendMail($templateVars, $attachFile, $fileName);

        $this->updateSyncStatus($sharedCatalogSyncQueueData, $emailFlag);
    }

    /**
     * Handle completion status update
     * @param object $sharedCatalogSyncQueueData
     */
    public function updateSyncStatus($sharedCatalogSyncQueueData, $emailFlag)
    {
        try {
            $catalogSynchQueue = $this->catalogSyncQueueFactory->create()
                ->load($sharedCatalogSyncQueueData->getId());
            $catalogSynchQueue->setStatus($this->manageCatalogItems::STATUS_COMPLETED)
                ->setemailSent($emailFlag)
                ->save();
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ':Catalog sync queue completed
                 for the sync request: ' . $sharedCatalogSyncQueueData->getId());
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ':' .
                $e->getMessage() . ' id for ' . $sharedCatalogSyncQueueData->getId());
        }
    }
}
