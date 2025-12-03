<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Helper;

use Fedex\Header\Helper\Data as HeaderData;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\SharedCatalog\Api\CategoryManagementInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Magento\SharedCatalog\Model\SharedCatalogFactory;
use Magento\Framework\Registry;
use Magento\Framework\App\ResourceConnection;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue\CollectionFactory;
use Magento\Company\Model\ResourceModel\Company\CollectionFactory as CompanyCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\Category;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Data Helper
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_FAILED = 'failed';
    public const STATUS_COMPLETED = 'completed';

    /**
     * @var \Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueFactory $catalogSynchQueue
     */
    protected $catalogSynchQueue;

    /**
     * @var object \Magento\Catalog\Model\CategoryFactory $categoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var Magento\SharedCatalog\Model\SharedCatalogFactory
     */
    protected $sharedCatalogFactory;

    /**
     * @var Magento\Store\Api\StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * Data Construct
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueFactory $catalogSynchQueueFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param CollectionFactory $catalogSyncCollectionFactory
     * @param CompanyCollectionFactory $companyCollectionFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryRepositoryInterface $categoryRepositoryInterface
     * @param LoggerInterface $logger
     * @param CategoryManagementInterface $categoryManagement
     * @param ScopeConfigInterface $configInterface
     * @param \Fedex\Punchout\Helper\Data $punchoutHelperData
     * @param \Fedex\SharedCatalogCustomization\Helper\ManageCatalogItems $manageCatalogItems
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param SharedCatalogFactory $sharedCatalogFactory
     * @param \Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueProcessFactory $catalogSyncQueueProcessFactory
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     * @param PublisherInterface $publisher
     * @param MessageInterface $message
     * @param \Magento\Framework\Registry $registry
     * @param ResourceConnection $resourceConnection
     * @param AttributeRepositoryInterface $attributeRepository
     * @param SharedCatalogSyncQueueConfigurationRepository $sharedCatalogConfRepository
     * @param ToggleConfig $toggleConfig
     * @param HeaderData $headerData
     *
     * @return void
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        private \Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueFactory $catalogSynchQueueFactory,
        protected \Magento\Framework\Message\ManagerInterface $messageManager,
        protected CollectionFactory $catalogSyncCollectionFactory,
        protected CompanyCollectionFactory $companyCollectionFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        protected CategoryCollectionFactory $categoryCollectionFactory,
        protected CategoryRepositoryInterface $categoryRepositoryInterface,
        protected LoggerInterface $logger,
        protected CategoryManagementInterface $categoryManagement,
        private ScopeConfigInterface $configInterface,
        protected \Fedex\Punchout\Helper\Data $punchoutHelperData,
        public ManageCatalogItems $manageCatalogItems,
        protected \Magento\Framework\HTTP\Client\Curl $curl,
        SharedCatalogFactory $sharedCatalogFactory,
        private \Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueProcessFactory $catalogSyncQueueProcessFactory,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        private PublisherInterface $publisher,
        private MessageInterface $message,
        protected \Magento\Framework\Registry $registry,
        protected ResourceConnection $resourceConnection,
        protected AttributeRepositoryInterface $attributeRepository,
        protected SharedCatalogSyncQueueConfigurationRepository $sharedCatalogConfRepository,
        protected ToggleConfig $toggleConfig,
        protected HeaderData $headerData
    ) {
        parent::__construct($context);
        $this->_categoryFactory = $categoryFactory;
        $this->sharedCatalogFactory = $sharedCatalogFactory;
        $this->storeRepository = $storeRepository;
    }

    /**
     * Get current shared catalog associate company Id.
     *
     * @param int $customGroupId
     * @return int company Id
     */
    public function getCompanyId(int $customGroupId)
    {
        $companyId = null;
        $companyObj = $this->companyCollectionFactory->create();
        $companyDataList = $companyObj->addFieldToFilter('customer_group_id', ['eq' => $customGroupId]);
        if ($companyDataList->getSize()) {
            $companyId = $companyDataList->getFirstItem()->getId();
        }

        return $companyId;
    }

    /**
     * Create catalog sync queues.
     *
     * @param string $legacyCatalogRootFolderId
     * @param int $sharedCatalogCustomerGroupId
     * @param int $sharedCatalogId
     * @param int $sharedCatalogCategoryId
     * @param string $sharedCatalogName
     * @param string $userName
     * @param boolean $manualSchedule
     * @param string $emailId
     *
     * @return void
     */
    public function createSyncCatalogQueue(
        $legacyCatalogRootFolderId,
        $sharedCatalogCustomerGroupId,
        $sharedCatalogId,
        $sharedCatalogCategoryId = null,
        $sharedCatalogName = null,
        $userName = 'System',
        $manualSchedule = false,
        $emailId = null
    ) {
        $sharedCatalogCompanyID = null;
        try {
            if (isset($legacyCatalogRootFolderId)) {
                $sharedCatalogCompanyID = $this->getCompanyId((int) $sharedCatalogCustomerGroupId);

                if (!empty($sharedCatalogCategoryId)) {
                    if (!empty($sharedCatalogCompanyID)) {
                        $alreadyInQueue = false;
                        $catalogSynchQueueCollection = $this->catalogSyncCollectionFactory->create();

                        $catalogSyncQueueCollection = $catalogSynchQueueCollection
                            ->addFieldToFilter('status', ['eq' => self::STATUS_PENDING])
                            ->addFieldToFilter('legacy_catalog_root_folder_id', [
                                'eq' => $legacyCatalogRootFolderId]);

                        if ($catalogSyncQueueCollection->getSize()) {
                            $alreadyInQueue = true;
                        }
                        if (empty($alreadyInQueue)) {

                            // @codeCoverageIgnoreStart
                            $storeId = $this->getStoreId();
                            $catalogSynchQueue = $this->catalogSynchQueueFactory->create();

                            $catalogSynchQueue->setCompanyId($sharedCatalogCompanyID)
                                ->setStoreId($storeId)
                                ->setLegacyCatalogRootFolderId($legacyCatalogRootFolderId)
                                ->setSharedCatalogId($sharedCatalogId)
                                ->setStatus(self::STATUS_PENDING)
                                ->setCreatedBy($userName)
                                ->setEmailId($emailId)
                                ->save();
                            if ($manualSchedule) {
                                $this->messageManager->addSuccessMessage(__(
                                    $sharedCatalogName . ' shared catalog added in queue as with pending status.'
                                ));
                            }
                            // @codeCoverageIgnoreEnd
                        } else {
                            if ($manualSchedule) {
                                $this->messageManager->addErrorMessage(__(
                                    $sharedCatalogName . ' shared catalog already added in queue.'
                                ));
                            }
                        }
                    } else {
                        if ($manualSchedule) {
                            $this->messageManager->addErrorMessage(__(
                                'No company is assigned with ' . $sharedCatalogName . ' shared Catalog.'
                            ));
                        }
                    }
                } else {
                    if ($manualSchedule) {
                            $this->messageManager->addErrorMessage(__(
                                'No root category is assigned with ' . $sharedCatalogName
                            ));
                    }
                }
            } else {
                if ($manualSchedule) {
                    $this->messageManager->addErrorMessage(__(
                        'Legacy catalog root folder id is not configured for ' . $sharedCatalogName . ' shared Catalog.'
                    ));
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            if ($manualSchedule) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
    }

    /**
     * Create new category if not exist
     *
     * @param string $categoryName
     * @param int $parentCategoryId
     * @param string $legacyCatalogFolderId
     * @param int $catalogSyncQueueProcessId
     *
     * @return int $currentCategoryId
     */
    public function createCategory($categoryName, $parentCategoryId, $legacyCatalogFolderId, $catalogSyncQueueProcessId)
    {
        $currentCategoryId = 0;
        try {
            $category = $this->_categoryFactory->create();
            $category->setName($categoryName);
            $category->setParentId($parentCategoryId);
            $category->setIsActive(true);
            $category->setCustomAttributes([
                'legacy_catalog_root_folder_id' => $legacyCatalogFolderId,
            ]);
            $category->setData('url_key', $legacyCatalogFolderId);
            $currentCategoryId = $this->categoryRepositoryInterface->save($category)->getId();
            $this->logger->info(__METHOD__.':'.__LINE__.':Category '.$currentCategoryId.' created.');
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':Error found while creating category: ' . $categoryName . ' ' . $e->getMessage());
            // Mark queue status Failed
            $this->manageCatalogItems->setQueueStatus(
                $catalogSyncQueueProcessId,
                $this->manageCatalogItems::STATUS_FAILED,
                "Error found while creating category: " . $categoryName . " " . $e->getMessage()
            );
        }

        return $currentCategoryId;
    }

    /**
     * Update category
     *
     * @param string $categoryName
     * @param int $parentCategoryId
     * @param int $categoryId
     * @param string $legacyCatalogFolderId
     * @param int $catalogSyncQueueProcessId
     *
     * @return int $currentCategoryId
     */
    public function updateCategory(
        $categoryName,
        $parentCategoryId,
        $categoryId,
        $legacyCatalogFolderId,
        $catalogSyncQueueProcessId
    ) {
        $currentCategoryId = 0;
        try {

            $category = $this->_categoryFactory->create()->load($categoryId);
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()->from('catalog_category_entity')->where('entity_id=?', $categoryId);
            $result = $connection->fetchRow($select);
            $catalog_category_entity_varchar = $connection->getTableName("catalog_category_entity_varchar");
                $catalog_category_entity_int = $connection->getTableName("catalog_category_entity_int");

            $attributes_code = [
                'name' => $categoryName,
                'is_active' => true,
                'legacy_catalog_root_folder_id' => $legacyCatalogFolderId
            ];

            foreach ($attributes_code as $key => $attribute_value) {
                $attributeId = $this->attributeRepository->get(Category::ENTITY, $key)->getAttributeId();
                if ($attributeId) {
                    $update = ["value" => $attribute_value];
                    $where = 'row_id ='.(int)$result['row_id'].' AND attribute_id ='.$attributeId;
                    if ($key == 'is_active') {
                        $connection->update($catalog_category_entity_int, $update, $where);
                    } else {
                        $connection->update($catalog_category_entity_varchar, $update, $where);
                    }
                }
            }

            $category->setParentId($parentCategoryId);
            $currentCategoryId = $this->categoryRepositoryInterface->save($category)->getId();
            $this->logger->info(__METHOD__.':'.__LINE__.':Category '.$currentCategoryId.' updated.');
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':Error found while updating category: ' . $categoryName . ' '. $e->getMessage());
            // Mark queue status Failed
            $this->manageCatalogItems->setQueueStatus(
                $catalogSyncQueueProcessId,
                $this->manageCatalogItems::STATUS_FAILED,
                "Error found while updating category: " . $categoryName . " ". $e->getMessage()
            );
        }

        return $currentCategoryId;
    }

    /**
     * Set CatalogSyncRequest
     *
     * @param int $catalogSyncQueueId
     * @param int $sharedCatalogId
     * @param string $legacyCatalogRootFolderId
     * @param int $storeId
     *
     * @return void
     */
    public function setCatalogSyncRequest($catalogSyncQueueId, $sharedCatalogId, $legacyCatalogRootFolderId, $storeId)
    {
        $lastInsrtedId = null;
        $rootParentCateId = null;
        try {
            $sharedCatalogData = $this->sharedCatalogConfRepository->getBySharedCatalogId($sharedCatalogId);
            $rootParentCateId =  $sharedCatalogData->getCategoryId();
            $this->logger->info(__METHOD__.':'.__LINE__.':Category sync request set.');
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.print_r($e->getMessage(), true));
        }

        // Put the API call request into Rabbit MQ i.e. insert the record into catalog_sync_queue_processs table.
        $catalogSyncQueueProcess = $this->catalogSyncQueueProcessFactory->create();
        $catalogSyncQueueProcess->setCatalogSyncQueueId($catalogSyncQueueId);
        $catalogSyncQueueProcess->setSharedCatalogId($sharedCatalogId);
        $catalogSyncQueueProcess->setCategoryId($rootParentCateId);
        $catalogSyncQueueProcess->setStoreId($storeId);
        $catalogSyncQueueProcess->setStatus($this->manageCatalogItems::STATUS_PENDING);
        $catalogSyncQueueProcess->setJsonData($legacyCatalogRootFolderId);
        $catalogSyncQueueProcess->setCatalogType('root_category');

        try {
            $catalogSyncQueueProcess->save();
            $lastInsrtedId = $catalogSyncQueueProcess->getId();
            // Publish into message Queue.
            $this->message->setMessage($lastInsrtedId);
            $this->publisher->publish('category', $this->message);
            $this->logger->info(__METHOD__.':'.__LINE__.':Category queue created.');
        } catch (\Exception $exception) {
            $this->logger->error(__METHOD__.':'.__LINE__.':Category create queue error:' . $exception->getMessage());
        }
    }

    /**
     * Process Categories queues
     *
     * @param array $responseDatas
     * @param int $catalogSyncQueueId
     * @param int $rootParentCateId
     * @param int $sharedCatalogId
     * @param string $catalogType
     * @param int $storeId
     * @param int $catalogSyncQueueProcessId
     */
    public function processCategories(
        $responseDatas,
        $catalogSyncQueueId,
        $rootParentCateId,
        $sharedCatalogId,
        $catalogType,
        $storeId,
        $catalogSyncQueueProcessId
    ) {
        $this->manageCatalogItems->createQueues(
            $responseDatas,
            $catalogSyncQueueId,
            $rootParentCateId,
            $sharedCatalogId,
            $storeId
        );

        if (isset($responseDatas['output']['folder']['folderSummaries'])) {
            $subCategoriesFolderIds = [];
            $subCategoriesNames = [];
            foreach ($responseDatas['output']['folder']['folderSummaries'] as $key => $responseData) {
                if ($responseData['name'] != '__ImageLibrary') {
                    // collect all subcategories Folder Ids
                    $subCategoriesFolderIds[] = trim($responseData['id']);
                    // collect all subcategories Name
                    $subCategoriesNames[] = trim($responseData['name']);

                    // Check if category is exist in Magento or not
                    $categoryId = $this->getCategoryDetails(
                        $responseData['id'],
                        $responseData['name'],
                        $rootParentCateId
                    );

                    if (!isset($categoryId)) {
                        $returnCurrentCatId = $this->createCategory(
                            $responseData['name'],
                            $rootParentCateId,
                            $responseData['id'],
                            $catalogSyncQueueProcessId
                        );

                        $categories[] = $this->categoryRepositoryInterface->get($returnCurrentCatId);
                        $this->categoryManagement->assignCategories($sharedCatalogId, $categories);
                    } else {
                        $returnCurrentCatId = (int) $this->updateCategory(
                            $responseData['name'],
                            $rootParentCateId,
                            $categoryId,
                            $responseData['id'],
                            $catalogSyncQueueProcessId
                        );
                    }

                    // Insert the record into catalog_sync_queue_processs table.
                    $catalogSyncQueueProcess = $this->catalogSyncQueueProcessFactory->create();
                    $catalogSyncQueueProcess->setCatalogSyncQueueId($catalogSyncQueueId);
                    $catalogSyncQueueProcess->setSharedCatalogId($sharedCatalogId);
                    $catalogSyncQueueProcess->setCategoryId($returnCurrentCatId);
                    $catalogSyncQueueProcess->setStoreId($storeId);
                    $catalogSyncQueueProcess->setStatus(self::STATUS_PENDING);
                    $catalogSyncQueueProcess->setJsonData($responseData['id']);
                    $catalogSyncQueueProcess->setCatalogType('category');

                    try {
                        $catalogSyncQueueProcess->save();
                        $lastInsrtedId = $catalogSyncQueueProcess->getId();
                        // Publish into message Queue.
                        $this->message->setMessage($lastInsrtedId);
                        $this->publisher->publish('category', $this->message);
                        $this->logger->info(__METHOD__.':'.__LINE__.':Create category queue successful.');
                    } catch (\Exception $exception) {
                        $this->logger->error(__METHOD__.':'.__LINE__.':Category create queue error:' . $exception->getMessage());
                    }
                }
            }
            $this->deleteCategory(
                $subCategoriesFolderIds,
                $subCategoriesNames,
                $rootParentCateId,
                $catalogSyncQueueId,
                $sharedCatalogId
            );
        } else {
            // Empty folderId and empty folderName for delete subcategories
            // in case category no any child category
            $emptyFolderId = [''];
            $emptyFolderName = [''];
            $this->deleteCategory(
                $emptyFolderId,
                $emptyFolderName,
                $rootParentCateId,
                $catalogSyncQueueId,
                $sharedCatalogId
            );
        }
    }

    /**
     *  Delete Legacy Deleted Category
     *
     * @param array $subCategoriesFolderIds
     * @param array $subCategoriesNames
     * @param int $rootParentCateId
     * @param int $catalogSyncQueueId
     * @param int $sharedCatalogId
     *
     * @return void
     */
    public function deleteCategory(
        $subCategoriesFolderIds,
        $subCategoriesNames,
        $rootParentCateId,
        $catalogSyncQueueId,
        $sharedCatalogId
    ) {
        $categoryCollection = null;
        $deletedCategoriesIds = [];
        $categoryFactory = $this->_categoryFactory->create();
        $rootParentCateId = (int) $rootParentCateId;
        try {
            $categoryCollection = $this->categoryCollectionFactory->create()
                ->addFieldToFilter([
                        ['attribute'=> 'legacy_catalog_root_folder_id', ['nin' => $subCategoriesFolderIds]],
                        ['attribute'=> 'legacy_catalog_root_folder_id', ['null' => true]]
                ])
                ->addFieldToFilter('name', ['nin' => $subCategoriesNames])
                ->addFieldToFilter('parent_id', ['eq' => $rootParentCateId]);

	    // put condition to avoid pod editable category
            $categoryCollection->addAttributeToFilter('pod2_0_editable',false);

            foreach ($categoryCollection as $categoryData) {
                // remove associated products if there is no active quote
                $this->manageCatalogItems->removeProductsByCategory(
                    $categoryData->getId(),
                    $catalogSyncQueueId,
                    $sharedCatalogId
                );
                $this->registry->unregister('isSecureArea');
                $this->registry->register('isSecureArea', true);
                if ($categoryData->getProductCollection()->count() == 0) {
                    $categoryFactory->load($categoryData->getId())->delete();
                }

                $this->registry->unregister('isSecureArea');
            }
            $this->logger->info(__METHOD__.':'.__LINE__.':Category delete success.');
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.json_encode($e->getMessage()));
        }
    }

    /**
     * Get Store Id by company Id
     *
     * @return int $storeId
     */
    public function getStoreId()
    {
        $storeId = '';
        try {
            $storeCode = $this->configInterface->getValue(
                "ondemand_setting/category_setting/b2b_default_store"
            );
            $store = $this->storeRepository->get($storeCode);
            $storeId = $store->getId();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ':Store not found error: ' . $e->getMessage());
        }

        return $storeId;
    }

    /**
     * Set catalogSyncApiRequest
     *
     * @param string $legacyCatalogFolderId
     *
     * @return array $folderApiResponse
     */
    public function catalogSyncApiRequest($legacyCatalogFolderId)
    {
        $folderApiResponse = [];
        $gatewayToken = $this->punchoutHelperData->getAuthGatewayToken();
        $tazToken = $this->punchoutHelperData->getTazToken();

        $url = $this->getProductApiUrl().'/'. $legacyCatalogFolderId;
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        if (!empty($gatewayToken) && $tazToken) {
            $headers = [
                "Content-Type: application/json",
                "Accept: application/json",
                "Accept-Language: json",
                "Cookie: Bearer=" . $tazToken,
                $authHeaderVal . $gatewayToken
            ];

            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers
                ]
            );

            $this->curl->get($url);
            $folderApiResponseOutput = $this->curl->getBody();
            $folderApiResponse = json_decode($folderApiResponseOutput, true);

            $this->logger->info(__METHOD__.':'.__LINE__.':Catalog Sync API Response ' . print_r($folderApiResponse, true));

            if (isset($folderApiResponse['errors'])) {
                $this->logger->error(__METHOD__.':'.__LINE__.':Catalog Sync API Response Error: ' . print_r($folderApiResponse['errors'], true));
            }
        }

        return $folderApiResponse;
    }

    /**
     *  GetCategoryDetails
     *
     * @param string $legacyCatalogRootFolderId
     * @param string $categoryName
     * @param int $rootParentCateId
     *
     * @return array $categoryDatas
     */
    public function getCategoryDetails($legacyCatalogRootFolderId, $categoryName, $rootParentCateId)
    {
        $categoryCollection = null;
        $categoryId = null;
        $path = "%/".$rootParentCateId."/%";

        try {
            $categoryCollection = $this->categoryCollectionFactory->create()->addAttributeToSelect('*')
            ->addFieldToFilter([
                        ['attribute'=> 'legacy_catalog_root_folder_id', ['eq' => trim($legacyCatalogRootFolderId)]],
                        ['attribute'=> 'name', ['eq' => trim($categoryName)]],
            ])
            ->addFieldToFilter('path', ['like' => $path]);

            if ($categoryCollection->getSize()) {
                $categoryId = $categoryCollection->getFirstItem()->getId();
            }
            $this->logger->info(__METHOD__.':'.__LINE__.':'.$categoryId.' retrieved.');
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.print_r($e->getMessage(), true));
        }

        return $categoryId;
    }

    /**
     * Get Product API Url
     *
     * @return String.
     */
    public function getProductApiUrl()
    {
        $productApiUrl = $this->configInterface->getValue("fedex/general/product_api_url");
        return $productApiUrl;
    }

    /**
     * Get BrowseCatalog CategoryName
     * @param int $browseCatalogCategoryId
     * @return null|string $categoryName
     */
    public function getBrowseCatalogCategoryName($browseCatalogCategoryId)
    {
        try {
            $category = $this->categoryRepositoryInterface->get($browseCatalogCategoryId);

            return $category->getName();
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.': Error in retrieving browse catalog category name is: '
                . var_export($e->getMessage(), true));
        }

        return null;
    }

    /**
     * Retirive Toggle value for migration bug fix (D-185410)
     */
    public function isMigrationFixToggle()
    {
        return $this->toggleConfig->getToggleConfigValue('explorers_d185410_fix');
    }
}
