<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Helper;

use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueCleanupProcessFactory;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Filesystem\Driver\File as FilesystemDriver;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CatalogMvp\Model\DocRefMessage;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Fedex\FXOCMConfigurator\ViewModel\FXOCMHelper;
use Magento\Catalog\Model\Product as ProductModel;

class ManageCatalogItems
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_FAILED = 'failed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PARTIALLY_COMPLETED = 'partially_completed';
    public const ATTRIBUTE_SET_NAME = 'PrintOnDemand';
    public const D_184380_EPRO_CUSTOMIZE_SEARCH_ACTION_FIX = 'd_184380_epro_customize_search_action_fix';
    public const REMOVE_CACHE_CLEAN_CODE = 'tech_titans_remove_cache_code';
    public const PRINT_READY_CALL_CATALOG_MIGRATION = 'explorers_call_print_ready_catalog_migration';

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

    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'gif', 'png'];

    /**
     * @var FilesystemDriver $filesystemDriver
     */
    protected $fileDriver;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Gallery $gallery
     */
    protected $productGallery;

    /**
     * @var Registry
     */
    protected $registry;

   /**
    * @var \Magento\SharedCatalog\Api\ProductManagement $productManagement
    */
    protected $productManagement;

    /**
     * ManageCatalogItems constructor.
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollectionFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $configInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface
     * @param ProductFactory $productFactory
     * @param \Magento\Framework\App\ResourceConnection $connection
     * @param \Magento\Framework\App\Filesystem\DirectoryList $dir
     * @param \Magento\Framework\Filesystem\Io\File $file
     * @param \Magento\Framework\Filesystem $fileSystem
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueProcessFactory $catalogSyncQueueProcessFactory
     * @param CatalogSyncQueueCleanupProcessFactory $catalogSyncQueueCleanupProcessFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\CategoryLinkRepository $categoryLinkRepository
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $itemCollectionFactory
     * @param \Magento\Quote\Api\Data\CartInterfaceFactory $cartFactory
     * @param \Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete $itemDeleter
     * @param \Magento\Catalog\Model\ResourceModel\Product\Gallery $gallery
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResourceModel
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Fedex\Punchout\Helper\Data $punchoutHelperData
     * @param \Magento\Framework\File\Mime $fileMime
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     * @param \Fedex\SharedCatalogCustomization\Api\MessageInterface $message
     * @param ToggleConfig $toggleConfig
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Integration\Model\AdminTokenService $adminTokenService
     * @param \Magento\SharedCatalog\Model\ProductManagement $productManagement
     * @param CategoryLinkManagementInterface $categoryLinkManagementInterface
     * @param FilesystemDriver $filesystemDriver
     * @param CatalogMvp $catalogMvpHelper
     * @param DocRefMessage $docRefMessage
     * @param Processor $mediaGalleryProcessor
     */
    public function __construct(
        protected \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollectionFactory,
        protected ScopeConfigInterface $configInterface,
        protected StoreManagerInterface $storeManager,
        protected EncryptorInterface $encryptor,
        protected \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        protected ProductFactory $productFactory,
        protected \Magento\Framework\App\ResourceConnection $connection,
        protected DirectoryList $dir,
        protected File $file,
        protected \Magento\Framework\Filesystem $fileSystem,
        protected LoggerInterface $logger,
        protected \Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueProcessFactory $catalogSyncQueueProcessFactory,
        protected CatalogSyncQueueCleanupProcessFactory $catalogSyncQueueCleanupProcessFactory,
        protected \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        protected \Magento\Catalog\Model\CategoryLinkRepository $categoryLinkRepository,
        protected \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $itemCollectionFactory,
        protected \Magento\Quote\Api\Data\CartInterfaceFactory $cartFactory,
        protected \Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete $itemDeleter,
        \Magento\Catalog\Model\ResourceModel\Product\Gallery $gallery,
        protected Product $productResourceModel,
        protected \Magento\Framework\HTTP\Client\Curl $curl,
        protected \Fedex\Punchout\Helper\Data $punchoutHelperData,
        protected \Magento\Framework\File\Mime $fileMime,
        protected PublisherInterface $publisher,
        protected MessageInterface $message,
        protected ToggleConfig $toggleConfig,
        \Magento\Framework\Registry $registry,
        protected \Magento\Integration\Model\AdminTokenService $adminTokenService,
        \Magento\SharedCatalog\Model\ProductManagement $productManagement,
        protected CategoryLinkManagementInterface $categoryLinkManagementInterface,
        FilesystemDriver $filesystemDriver,
        protected CatalogMvp $catalogMvpHelper,
        protected DocRefMessage $docRefMessage,
        private Processor $mediaGalleryProcessor,
        protected FXOCMHelper $fxoCMHelper
    ) {
        $this->fileDriver = $filesystemDriver;
        $this->productGallery = $gallery;
        $this->registry = $registry;
        $this->productManagement = $productManagement;
    }

    /**
     * Create products add/update/delete queues.
     *
     * @param array $responseDatas
     * @param Int $catalogSyncQueueId
     * @param Int $rootParentCateId
     * @param Int $sharedCatalogId
     * @param Int $storeId
     */
    public function createQueues($responseDatas, $catalogSyncQueueId, $rootParentCateId, $sharedCatalogId, $storeId)
    {
        $categoryLevelSkus = [];
        // Check if items exist in API response.
        if (isset($responseDatas['output']['folder']['itemSummaries'])) {
            //Read Json for itemSummaries
            $itemSummaries = $responseDatas['output']['folder']['itemSummaries'];

            foreach ($itemSummaries as $itemSummary) {
                $productVersionArray = explode("_", $itemSummary['version']);
                $versionSku = $productVersionArray[2];
                // Check if product is exist by SKU
                //(Using from an API version as stored earlier and also by ID as per new way)
                try {
                    $product = $this->productRepositoryInterface->get($versionSku);
                    $isNew = 0;
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                    try {
                        $product = $this->productRepositoryInterface->get($itemSummary['id']);
                        $isNew = 0;
                        if (!in_array($rootParentCateId, $product->getCategoryIds())) {
                            $this->categoryLinkManagementInterface->assignProductToCategories(
                                $itemSummary['id'],
                                [$rootParentCateId]
                            );
                        }
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                        $isNew = 1;
                    }
                }

                if ($isNew &&
                $itemSummary['type'] == 'PRODUCT' &&
                isset($itemSummary['version']) &&
                isset($itemSummary['catalogProductSummary']['productRateTotal']['price']) &&
                isset($itemSummary['catalogProductSummary']['availability']['available'])
                ) {
                    $itemSummaryJson = json_encode($itemSummary, true);
                    $catalogSyncQueue = $this->catalogSyncQueueProcessFactory->create();
                    $catalogSyncQueue->setCatalogSyncQueueId($catalogSyncQueueId);
                    $catalogSyncQueue->setSharedCatalogId($sharedCatalogId);
                    $catalogSyncQueue->setCategoryId($rootParentCateId);
                    $catalogSyncQueue->setStoreId($storeId);
                    $catalogSyncQueue->setStatus(self::STATUS_PENDING);
                    $catalogSyncQueue->setCatalogType('product');
                    $catalogSyncQueue->setJsonData($itemSummaryJson);
                    $catalogSyncQueue->setActionType('new');

                    try {
                        $catalogSyncQueue->save();
                        $lastInsrtedId = $catalogSyncQueue->getId();
                        // Publish into message Queue.
                        $this->message->setMessage($lastInsrtedId);
                        $this->publisher->publish('product', $this->message);
                        $this->logger->info(__METHOD__.':'.__LINE__.':Product create queue completed.');
                    } catch (\Exception $exception) {
                        $this->logger->error(__METHOD__.':'.__LINE__.':Product create queue error:' . $exception->getMessage());
                    }
                } else {
                    // Update existing product details
                    if (isset($itemSummary['version']) &&
                    isset($itemSummary['catalogProductSummary']['productRateTotal']['price']) &&
                    isset($itemSummary['catalogProductSummary']['availability']['available'])
                    ) {
                        $existingjsonData = $product->getExternalProd();
                        $itemSummary['existingjsonData'] = json_decode($existingjsonData, true);
                        $existingjsonData = json_decode($existingjsonData, true);
                        $version = $itemSummary['version'];

                        $itemSummary['productId'] = $product->getId();

                        $isCustomizable = $itemSummary['catalogProductSummary']['customizable'] ?? 0;
                        if ((isset($existingjsonData['catalogReference']['version']) &&
                        $existingjsonData['catalogReference']['version'] != $version) || ($product->getCustomizable() != $isCustomizable)) {

                            //update queues creation.
                            $itemSummaryJson = json_encode($itemSummary, true);
                            $catalogSyncQueue = $this->catalogSyncQueueProcessFactory->create();
                            $catalogSyncQueue->setCatalogSyncQueueId($catalogSyncQueueId);
                            $catalogSyncQueue->setSharedCatalogId($sharedCatalogId);
                            $catalogSyncQueue->setCategoryId($rootParentCateId);
                            $catalogSyncQueue->setStoreId($storeId);
                            $catalogSyncQueue->setStatus(self::STATUS_PENDING);
                            $catalogSyncQueue->setCatalogType('product');
                            $catalogSyncQueue->setJsonData($itemSummaryJson);
                            $catalogSyncQueue->setActionType('update');

                            try {
                                $catalogSyncQueue->save();
                                $lastInsrtedId = $catalogSyncQueue->getId();
                                // Publish into message Queue.
                                $this->message->setMessage($lastInsrtedId);
                                $this->publisher->publish('product', $this->message);
                                $this->logger->info(__METHOD__.':'.__LINE__.':Product queue created.');
                            } catch (\Exception $exception) {
                                $this->logger->error(__METHOD__.':'.__LINE__.':Product update queue creation error:' . $exception->getMessage());
                            }
                        }
                    }
                }
                $categoryLevelSkus[] = $itemSummary['id'];
                $categoryLevelSkus[] = $versionSku;
            }
        }
        // Use sku's array to compare with Magento with NOT IN condition and category ID.
        $notAvailableProductsIdsInApiResponse = $this->getProductCollectionByCategories(
            $categoryLevelSkus,
            $rootParentCateId
        );

        // unassigned product from previous category or remove and moved in new category
        $this->cleanUpCatalogProductQueue(
            $notAvailableProductsIdsInApiResponse,
            $rootParentCateId,
            $catalogSyncQueueId,
            $sharedCatalogId
        );
    }

    /**
     * Set status of queue items under catalog_sync_queue_process table
     *
     * @param Int    $catalogSyncQueueProcessId
     * @param String $status
     * @param String $errorMsg
     */
    public function setQueueStatus($catalogSyncQueueProcessId, $status, $errorMsg = '')
    {
        $catalogSyncQueueProcess = $this->catalogSyncQueueProcessFactory->create();

        try {
            $catalogSyncQueueProcess->setId($catalogSyncQueueProcessId);
            $catalogSyncQueueProcess->setErrorMsg(json_encode($errorMsg));
            $catalogSyncQueueProcess->setStatus($status);
            $catalogSyncQueueProcess->save();
            $this->logger->info(__METHOD__.':'.__LINE__.':Queue updated successfully.');
        } catch (\Exception $exception) {
            $this->logger->error(__METHOD__.':'.__LINE__.':Queue update error:' . $exception->getMessage());
        }
    }

    /**
     * Upload image
     *
     * @param String $imageLink
     * @param String $imageName
     * @param String $newFileName
     *
     * @return String $filesize
     */
    public function uploadImage($imageLink, $imageName, $newFileName)
    {
        $tazToken = $this->punchoutHelperData->getTazToken();
        if ($this->toggleConfig->getToggleConfigValue('explorers_catalog_migration')) {
            $gateWayToken = $this->punchoutHelperData->getAuthGatewayToken();
            $headers = [
                "Cookie: Bearer=". $tazToken,
                "client_id:". $gateWayToken
            ];
        } else {
            $headers = ["Cookie: Bearer=" . $tazToken];
        }

        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
            ]
        );

        $this->curl->get($imageLink);
        $apiOutput = $this->curl->getBody();

        if ($this->file->fileExists($newFileName)) {
            $this->file->rm($newFileName);
        }

        $media = $this->fileSystem->getDirectoryWrite($this->dir::MEDIA);
        $media->writeFile("tmp/" . $imageName, $apiOutput);
        $filesize = $media->stat("tmp/".$imageName);

        return $filesize['size'];
    }

    /**
     * Check and remove existing product images with product update
     *
     * @param Object $productFactoryDataObject
     * @return Object $this
     */
    public function removeProductImages($productFactoryDataObject)
    {
        try {
            // check and remove existing images if present
            $gallery = $productFactoryDataObject->getMediaGalleryImages();
            if (count((array)$gallery) > 0) {
                $existingMediaGalleryEntries = $productFactoryDataObject->getMediaGalleryEntries();
                foreach ($existingMediaGalleryEntries as $key => $entry) {
                    unset($existingMediaGalleryEntries[$key]);
                }
                foreach ($gallery as $image) {
                    $this->productGallery->deleteGallery($image->getValueId());
                }
                $productFactoryDataObject->setMediaGalleryEntries($existingMediaGalleryEntries);
                $this->productRepositoryInterface->save($productFactoryDataObject);
            }
            $this->logger->info(__METHOD__.':'.__LINE__.':Catalog sync image successfully updated.');
        } catch (\Exception $exception) {
            $this->logger->error(__METHOD__.':'.__LINE__.':Catalog Sync image save error while update:' . $exception->getMessage());
        }

        return $this;
    }

    /**
     * Manage product Image with Create/Update
     *
     * @param array $itemSummary
     * @param ProductModel $productFactoryDataObject
     * @param String $mode
     * @return $this
     * @throws LocalizedException
     * @codeCoverageIgnore
     */
    public function manageProductImage($itemSummary, $productFactoryDataObject, $mode = 'create')
    {
        if (!$this->toggleConfig->getToggleConfigValue('explorers_catalog_migration') && $mode == 'update') {
           $this->removeProductImages($productFactoryDataObject);
        }

        if (!empty($itemSummary['links'][1]['href']) && $itemSummary['links'][1]['rel'] == 'thumbnail') {
            $imageType = ['image', 'small_image', 'thumbnail'];
            $imageLink = str_replace('\/', '/', $itemSummary['links'][1]['href']);
            $tmpDir = $this->getMediaDirTmpDir();
            $this->file->checkAndCreateFolder($tmpDir);

            $fileInfo = $this->file->getPathInfo($imageLink);
            if (
                $this->toggleConfig->getToggleConfigValue('explorers_catalog_migration') &&
                isset($itemSummary["additionalData"]) &&
                !empty($itemSummary["additionalData"]["is_catalog_migration"])
            ) {
                $imageName = $fileInfo['basename'].time();
            } else {
                $imageName = $fileInfo['basename'];
            }

            $newFileName = $tmpDir . $imageName;

            $filesize = $this->uploadImage($imageLink, $imageName, $newFileName);
            if (!empty($filesize)) {
                try {
                    $mimeInfo = $this->fileMime->getMimeType($newFileName);
                    $imgMimeInfo = explode("/", $mimeInfo);
                    $imgExtension = $imgMimeInfo[1];
                    if (in_array($imgExtension, self::IMAGE_EXTENSIONS)) {
                        try {
                            $newFileNameWithExtension = $newFileName . '.' . $imgExtension;
                            $newFileNameWithExtension = str_replace('?', '', $newFileNameWithExtension);
                            $this->fileDriver->rename($newFileName, $newFileNameWithExtension);
                            if ($mode == 'update') {
                                try {
                                    $this->storeManager->setCurrentStore(0);
                                    if ($this->toggleConfig->getToggleConfigValue('explorers_catalog_migration')) {
                                        $this->mediaGalleryProcessor->addImage(
                                                $productFactoryDataObject,
                                                $newFileNameWithExtension,
                                                $imageType,
                                                false,
                                                false
                                            );

                                        $productFactoryDataObject->save();

                                        $this->message->setMessage($itemSummary['id']);
                                        $this->publisher->publish('productImgRemove', $this->message);
                                    } else {

                                        $productFactoryDataObject->addImageToMediaGallery(
                                            $newFileNameWithExtension,
                                            $imageType,
                                            false,
                                            false
                                        );

                                        $this->productRepositoryInterface->save($productFactoryDataObject);
                                    }

                                    $this->logger->info(__METHOD__.':'.__LINE__.':Product image was saved for the sku: ' . $itemSummary['id']);
                                } catch (\Exception $exception) {
                                    $this->logger->error(__METHOD__.':'.__LINE__.':Product Image save error while update:'
                                    . $exception->getMessage() . ' for the sku: ' . $itemSummary['id']);
                                }
                            } else {
                                $productFactoryDataObject->addImageToMediaGallery(
                                    $newFileNameWithExtension,
                                    $imageType,
                                    false,
                                    false
                                );
                            }
                            $this->logger->info(__METHOD__.':'.__LINE__.':Catalog sync image success for the sku: ' . $itemSummary['id']);
                        } catch (LocalizedException $e) {
                            $this->logger->error(__METHOD__.':'.__LINE__.':Catalog Sync Image Error for the product '
                            . $itemSummary['name'] . ':' . $e->getMessage());
                        }
                    } else {
                        $this->logger->error(__METHOD__.':'.__LINE__.':Catalog sync error, invalid image extension was found for the product '
                        . $itemSummary['name']);
                    }
                } catch (\Exception $exception) {
                    $this->logger->error(__METHOD__.':'.__LINE__.':Catalog Sync Error, Invalid Image file found :'
                    . $exception->getMessage() . 'for the product ' . $itemSummary['name']);
                }
                if (!empty($newFileNameWithExtension)) {
                    $this->file->rm($newFileNameWithExtension);
                }
            }
        }

        return $this;
    }

    /**
     * Process new Items Queue.
     *
     * @param String $productJson
     * @param Int    $sharedCatalogId
     * @param Int    $categoryId
     * @param Int    $catalogSyncQueueProcessId
     *
     * @codeCoverageIgnore
     */
    public function createItem($productJson, $sharedCatalogId, $categoryId, $catalogSyncQueueProcessId, $storeId = null)
    {
        // Change status under catalog_sync_queue_process table
        $this->setQueueStatus($catalogSyncQueueProcessId, self::STATUS_PROCESSING);

        // Get Attribute Set ID by Name.
        $attributeSetId = $this->getAttrSetId(self::ATTRIBUTE_SET_NAME);

        $itemSummary = json_decode($productJson, true);
        // Get ExternalProd Data.
        $externalProdData = $this->getExternalProdData($itemSummary['id'], $itemSummary['version']);

        $product = $this->productFactory->create();

        // Upload Product Image
        $this->manageProductImage($itemSummary, $product, 'create');

        $description = $itemSummary['description'] ?? '';
        $customizable = $itemSummary['catalogProductSummary']['customizable'] ?? '';
        $routingLocation = $itemSummary['routingLocation']??null;
        $product->setName($itemSummary['name']);
        $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
        $product->setAttributeSetId($attributeSetId);
        $product->setSku($itemSummary['id']);
        $product->setCatalogDescription($description);
        $product->setShortDescription($description);
        if ($this->toggleConfig->getToggleConfigValue('explorers_d185410_fix')) {
            $product->setCategoryIds(explode(",", $categoryId));
        } else {
            $product->setCategoryIds([$categoryId]);
        }

        $product->setStatus(Status::STATUS_ENABLED);
        $product->setProductLocationBranchNumber($routingLocation);
        $product->setPrice($itemSummary['catalogProductSummary']['productRateTotal']['price']);
        $product->setWebsiteIds([1]);
        $product->setVisibility(1);
        // Get ExternalProd Data.
        if($this->toggleConfig->getToggleConfigValue('explorers_catalog_migration') &&
            isset($itemSummary["additionalData"]) &&
            !empty($itemSummary["additionalData"]["is_catalog_migration"])) {
                $externalProdData = $itemSummary["additionalData"]["productInstance"];
                $product->setPublished(1);
                $product->setData('pod2_0_editable', $itemSummary['catalogProductSummary']['editable']);
                $product->setDltThresholds($itemSummary["additionalData"]["DltData"]);
                $product->setCustomizationFields($itemSummary["additionalData"]["customizeInstance"]);

                if ($this->toggleConfig->getToggleConfigValue(self::PRINT_READY_CALL_CATALOG_MIGRATION)) {
                    $externalProdData = $this->handlePageGroups($externalProdData);
                }
        }
        $product->setCustomizable($customizable);
        if($this->isD184380ToggleEnabled()){
            $product->setCustomizeSearchAction(true);
        }
        if ($this->toggleConfig->getToggleConfigValue('tech_titans_d_217178')) {
            $currentDateTime = date("Y-m-d H:i:s");
            $created_at = $currentDateTime;
            $updated_at = $currentDateTime;
            $product->setProductCreatedDate($created_at);
            $product->setProductUpdatedDate($updated_at);
        }
        $product->setExternalProd($externalProdData);
        $product->setUrlKey($itemSummary['id']);
        $product->setStockData([
            'use_config_manage_stock' => 0, //'Use config settings' checkbox
            'manage_stock' => 0, //manage stock
            'is_in_stock' => 1, //Stock Availability
        ]);
        // Shared_catalogs product attribute save
        $product->setSharedCatalogs($sharedCatalogId);

        // Shared_catalogs product attribute save
        try {
            $this->productRepositoryInterface->save($product);

            // Change status under catalog_sync_queue_process table
            $this->setQueueStatus($catalogSyncQueueProcessId, self::STATUS_PARTIALLY_COMPLETED);
            /*Publish product in catalogEnableStoreQueue to enable for store*/
            $this->publishCatalogEnableStoreQueue($catalogSyncQueueProcessId, $itemSummary['id'], $storeId);

            // Manage Docs Life expiry
            if (
                $this->toggleConfig->getToggleConfigValue('explorers_catalog_migration') &&
                isset($itemSummary["additionalData"]) &&
                !empty($itemSummary["additionalData"]["is_catalog_migration"])
            ) {
                $this->manageDocsLifeExpire($itemSummary['id']);
            }

            $this->logger->info(__METHOD__.':'.__LINE__.':Catalog Sync Queue processed for the item sku: '. $itemSummary['id']);

        } catch (\Exception $exception) {
            $this->logger->error(
                __METHOD__.':'.__LINE__.':Product save error while syncing:' . $exception->getMessage()
                . ' for the item sku: ' . $itemSummary['id']
            );
            // Change status under catalog_sync_queue_process table
            $this->setQueueStatus($catalogSyncQueueProcessId, self::STATUS_FAILED, $exception->getMessage());
        }

        // Assign the new item into Shared Catalog without Curl.
        $this->sharedCatalogAssignProductWithoutCurl($product, $sharedCatalogId);

    }

    /**
     * Publish item in catalogEnableStoreQueue
     *
     * @param int $catalogSyncQueueProcessId
     * @param string $productSku
     * @param int $storeId
     *
     * @codeCoverageIgnore
     */
    public function publishCatalogEnableStoreQueue($catalogSyncQueueProcessId, $productSku, $storeId)
    {
        $productEnableStoreMessage = [
            "catalogSyncQueueProcessId" => $catalogSyncQueueProcessId,
            "productSku" => $productSku,
            "storeId" => $storeId
        ];
        $this->message->setMessage(json_encode($productEnableStoreMessage));
        $this->publisher->publish('catalogEnableStore', $this->message);
        $this->logger->info(__METHOD__.':'.__LINE__.':Catalog are publish in catalogEnableStoreQueue to enable item for store');
    }

    /**
     * Enable item status for store
     *
     * @param int $catalogSyncQueueProcessId
     * @param string $productSku
     * @param int $storeId
     */
    public function itemEnableStore($catalogSyncQueueProcessId, $productSku, $storeId)
    {
        try {
            $product = $this->productRepositoryInterface->get($productSku);
            $product->setStoreId($storeId);
            $product->setVisibility(4);
            $product->save();
            $this->setQueueStatus($catalogSyncQueueProcessId, self::STATUS_COMPLETED);
            $this->logger->info(__METHOD__.':'.__LINE__.': Product status change successfully');
        } catch (\Exception $exception) {
            $this->logger->error(__METHOD__.':'.__LINE__.': Product change status error:' . $exception->getMessage());
            // Change status under catalog_sync_queue_process table
            $this->setQueueStatus($catalogSyncQueueProcessId, self::STATUS_FAILED, $exception->getMessage());
        }
    }

    /**
     * Update product details
     *
     * @param object $itemSummary
     * @param int $catalogSyncQueueProcessId
     * @param int $storeId
     */
    public function updateItem($itemSummary, $catalogSyncQueueProcessId, $storeId)
    {
        // Change status under catalog_sync_queue_process table
        $this->setQueueStatus($catalogSyncQueueProcessId, self::STATUS_PROCESSING);

        $itemSummary = json_decode($itemSummary, true);

        $productFactoryObject = $this->productFactory->create();
        $product = $productFactoryObject->load($itemSummary['productId']);
        // Upload Product Image
        $this->manageProductImage($itemSummary, $product, 'update');

        $product = $this->productRepositoryInterface->getById($itemSummary['productId']);
        $product->setSku($itemSummary['id']);
            $product->setStoreId($storeId);
        $description = $itemSummary['description'] ?? '';
        $customizable = $itemSummary['catalogProductSummary']['customizable'] ?? '';

        $price = $itemSummary['catalogProductSummary']['productRateTotal']['price'];
        $status = isset($itemSummary['catalogProductSummary']['availability']['available'])
        && $itemSummary['catalogProductSummary']['availability']['available'] == 'true'
        ? Status::STATUS_ENABLED : Status::STATUS_DISABLED;

        if ($this->toggleConfig->getToggleConfigValue('explorers_catalog_migration') &&
            isset($itemSummary["additionalData"]) &&
            !empty($itemSummary["additionalData"]["is_catalog_migration"])) {
            $externalProdData = $itemSummary['additionalData']['productInstance'];
            $product->setPublished(1);
            $product->setData('pod2_0_editable', $itemSummary['catalogProductSummary']['editable']);
            $product->setDltThresholds($itemSummary["additionalData"]["DltData"]);
            $product->setCustomizationFields($itemSummary["additionalData"]["customizeInstance"]);
        } else {
            $externalProd = $itemSummary['existingjsonData'];
            $externalProd['catalogReference']['version'] = $itemSummary['version'];
            $externalProdData = json_encode($externalProd);
        }

        $product->setVisibility(4);
        $routingLocation = $itemSummary['routingLocation']??null;

        // Handle pagegroup update
        if ($this->toggleConfig->getToggleConfigValue(self::PRINT_READY_CALL_CATALOG_MIGRATION)) {
            $externalProdData = $this->handlePageGroups($externalProdData);
        }
        $product->setExternalProd($externalProdData);
        $product->setName($itemSummary['name']);
        $product->setCatalogDescription($description);
        $product->setShortDescription($description);
        $product->setCustomizable($customizable);

        $printOnDemandAttrSetId = $this->getAttrSetId(self::ATTRIBUTE_SET_NAME);
        if($this->isD184380ToggleEnabled() && ($product->getAttributeSetId() == $printOnDemandAttrSetId)) {
            $product->setCustomizeSearchAction(true);
        }
        $product->setStatus($status);
        $product->setProductLocationBranchNumber($routingLocation);
        $product->setPrice($price);
        if ($this->toggleConfig->getToggleConfigValue('tech_titans_d_217178')) {
            $updated_at = date("Y-m-d H:i:s");
            $product->setProductUpdatedDate($updated_at);
        }

        try {
            $this->productResourceModel->save($product);
            // Change status under catalog_sync_queue_process table
            $this->setQueueStatus($catalogSyncQueueProcessId, self::STATUS_COMPLETED);
            $this->logger->info(__METHOD__.':'.__LINE__.':Product was updated for the sku: ' . $itemSummary['id']);

            // Manage Docs Life expiry
            if (
                $this->toggleConfig->getToggleConfigValue('explorers_catalog_migration') &&
                isset($itemSummary["additionalData"]) &&
                !empty($itemSummary["additionalData"]["is_catalog_migration"])
            ) {
                $this->manageDocsLifeExpire($itemSummary['id']);
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                __METHOD__.':'.__LINE__.':Product Update error while syncing:'
                . $exception->getMessage() . ' for the item sku: ' . $itemSummary['id']
            );
            // Change status under catalog_sync_queue_process table
            $this->setQueueStatus($catalogSyncQueueProcessId, self::STATUS_FAILED, $exception->getMessage());
        }
    }

    /**
     * Get Media tmp folder path for product images
     *
     * @return String
     */
    public function getMediaDirTmpDir()
    {
        return $this->dir->getPath('media') . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
    }

    /**
     * Get Base URL.
     *
     * @return String.
     */
    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
    }

    /**
     * Get Admin API User credentials
     *
     * @return array
     */
    public function getApiUserCredentials()
    {
        $username = $this->configInterface->getValue("fedex/authentication/username");
        $password = $this->configInterface->getValue("fedex/authentication/password");
        $stringPassword = $this->encryptor->decrypt($password);
        $apiUser = [
            'username' => $username,
            'password' => $stringPassword,
        ];

        return $apiUser;
    }

    /**
     * Get Admin Token
     *
     * @return String.
     */
    public function getAdminToken()
    {
        $token_url = $this->getBaseUrl() . "index.php/rest/V1/integration/admin/token";

        $data_string = $this->getApiUserCredentials();
        $data_string = json_encode($data_string);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $token_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $token = curl_exec($ch);
        $adminToken = json_decode($token);
        return $adminToken;
    }

    /**
     * Get admin token for API's Authentications.
     *
     * @return string
     *
     * Anuj || B-885038 || Code Refactor - Removed curl and called model method directly
     */
    public function getAdminTokenWithoutCurl()
    {
        $data_string = $this->getApiUserCredentials();

        $username = $data_string['username'];
        $password = $data_string['password'];
        return $this->adminTokenService->createAdminAccessToken($username, $password);
    }

    /**
     * Assign the SKU with SHared Catalog.
     *
     * @param String $sku
     * @param Int $sharedCatId
     *
     * @return Bool|Array
     */
    public function sharedCatalogAssignProduct($sku, $sharedCatId)
    {
        $responseData = [];
        //Anuj || B-885038 || Code Refactor - Removed curl and called model method directly
        $data_string = trim((string)$this->getAdminTokenWithoutCurl());

        $apiURL = $this->getBaseUrl() . "rest/V1/sharedCatalog/" . $sharedCatId . "/assignProducts";
        $headers = ["Content-Type: application/json", "Authorization: Bearer " . $data_string];

        $postSharedCatalogAssignData['products'][] = ['sku' => $sku];
        $postSharedCatalogAssignDataString = json_encode($postSharedCatalogAssignData);

        try {
            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $postSharedCatalogAssignDataString,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]
            );
            $this->curl->post($apiURL, $postSharedCatalogAssignData);
            $response = $this->curl->getBody();
            $responseData = json_decode((string)$response, true);
            if (isset($responseData['error'])) {
                $this->logger->critical(__METHOD__.':'.__LINE__.':Shared Catalog:'
                . $sharedCatId . ' Assign Product Id: '
                . $sku . ' Error: ' . print_r($responseData, true));
            } else {
                return $response;
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__.':'.__LINE__.':Shared Catalog:'
            . $sharedCatId . ' Assign Product Id: '
            . $sku . ' Error: ' . print_r($responseData, true));
        }
    }

    /**
     * Assign the SKU with SHared Catalog.
     *
     * @param Object $product
     * @param Int $sharedCatId
     * @return Bool|Array
     * Anuj || B-885038 || Code Refactor - Removed curl and called model method directly
     */
    public function sharedCatalogAssignProductWithoutCurl($product, $sharedCatId)
    {
        return $this->productManagement->assignProducts($sharedCatId, [$product]);
    }

    /**
     * Un-Assign the SKU from Shared Catalog.
     *
     * @param String $sku
     * @param Int $sharedCatId
     *
     * @return Bool|Array
     */
    public function sharedCatalogUnAssignProduct($sku, $sharedCatId)
    {
        $responseData = [];
        //Anuj || B-885038 || Code Refactor - Removed curl and called model method directly
        $data_string = trim((string)$this->getAdminTokenWithoutCurl());

        $apiURL = $this->getBaseUrl() . "rest/V1/sharedCatalog/" . $sharedCatId . "/unassignProducts";
        $headers = ["Content-Type: application/json", "Authorization: Bearer " . $data_string];

        $postSharedCatalogUnAssignData['products'][] = ['sku' => $sku];
        $postSharedCatalogUnAssignDataString = json_encode($postSharedCatalogUnAssignData);

        try {
            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $postSharedCatalogUnAssignDataString,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]
            );
            $this->curl->post($apiURL, $postSharedCatalogUnAssignData);
            $response = $this->curl->getBody();
            $responseData = json_decode((string)$response, true);
            if (isset($responseData['error'])) {
                $this->logger->critical(__METHOD__.':'.__LINE__.':Shared Catalog:'
                . $sharedCatId . ' Un-Assign Product Id: '
                . $sku . ' Error: '. print_r($responseData, true));
            } else {
                return $response;
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__.':'.__LINE__.':Shared Catalog:'
            . $sharedCatId . ' Un-Assign Product Id: '
            . $sku . ' Error: ' . print_r($responseData, true));
        }
    }

    /**
     * Un-Assign the SKU from Shared Catalog.
     *
     * @param Object $product
     * @param Int $sharedCatId
     * @return Bool|Array
     * Anuj || B-885038 || Code Refactor - Removed curl and called model method directly
     */
    public function sharedCatalogUnAssignProductWithoutCurl($product, $sharedCatId)
    {
        return $this->productManagement->unassignProducts($sharedCatId, [$product]);
    }

    /**
     * Prepare external Prod attribute data of Product
     *
     * @param  Int $id
     * @param  String $version
     * @return Json String.
     */
    public function getExternalProdData($id = null, $version = null)
    {
        $externalProd = [
            'id' => self::EXTERNAL_PROD['id'],
            'version' => self::EXTERNAL_PROD['version'],
            'name' => self::EXTERNAL_PROD['name'],
            'qty' => self::EXTERNAL_PROD['qty'],
            'priceable' => self::EXTERNAL_PROD['priceable'],
            'proofRequired' => self::EXTERNAL_PROD['proofRequired'],
            'catalogReference' => [
                'catalogProductId' => $id,
                'version' => $version,
            ],
            'isOutSourced' => self::EXTERNAL_PROD['isOutSourced'],
            'instanceId' => self::EXTERNAL_PROD['instanceId'],
        ];
        return json_encode($externalProd);
    }

    /**
     * Get Attribute set ID by Name
     *
     * @param string $attrSetName
     *
     * @return Int $attributeSetId
     */
    public function getAttrSetId($attrSetName = '')
    {
        $attributeSetCollection = $this->attributeSetCollectionFactory->create()->addFieldToSelect(
            '*'
        )->addFieldToFilter(
            'attribute_set_name',
            $attrSetName
        );

        $attributeSetId = 0;
        if ($attributeSetCollection->getSize()) {
            $data = $attributeSetCollection->getFirstItem();
            $attributeSetId = $data->getAttributeSetId();
        }

        return $attributeSetId;
    }

    /**
     * Check is NegotiableQuote exist or not
     *
     * @param Int $productId
     * @return boolean
     */
    public function checkNegotiableQuote($productId)
    {
        $conn = $this->connection->getConnection();
        $select = $conn->select()
        ->from(
            ['qi' => 'quote_item']
        )
        ->join(
            ['nq' => 'negotiable_quote'],
            'nq.quote_id=qi.quote_id'
        )
        ->where('qi.product_id=?', $productId)
        ->where('nq.status IN (?)', [
                NegotiableQuoteInterface::STATUS_CREATED,
                NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN
                ]);
        $counts = $conn->fetchOne($select);
        if ($counts > 0) {
            return true;
        }
        return false;
    }

    /**
     * CheckForCleanUp and create clean up queues
     *
     * @param array $notAvailableProductsIdsInApiResponse
     * @param int $currentCategoryId
     * @param int $catalogSyncQueueId
     * @param int $sharedCatalogId
     *
     * @return boolean
     */
    public function cleanUpCatalogProductQueue(
        $notAvailableProductsIdsInApiResponse,
        $currentCategoryId,
        $catalogSyncQueueId,
        $sharedCatalogId
    ) {
        if (isset($notAvailableProductsIdsInApiResponse) && count($notAvailableProductsIdsInApiResponse) > 0) {
            foreach ($notAvailableProductsIdsInApiResponse as $productId) {
                // start code for unassign product from the categories
                $product = $this->productRepositoryInterface->getById($productId);
                $currentCategories = $product->getCategoryIds();
                foreach ($currentCategories as $categoryId) {
                    if ($currentCategoryId == $categoryId) {
                        try {
                            $this->categoryLinkRepository->deleteByIds($categoryId, $product->getSku());
                            $this->logger->info(__METHOD__.':'.__LINE__.':Delete successful. '.$product->getSku());
                        } catch (\Magento\Framework\Exception\InputException $e) {
                            $this->logger->error(__METHOD__.':'.__LINE__.':InputException: SKU:' . $product->getSku()
                            .' un-asssign from category: '.$categoryId.' Error with Delete Queue Process:'
                            . $e->getMessage());
                        } catch (\Magento\Framework\Exception\CouldNotSaveException $e) {
                            $this->logger->error(__METHOD__.':'.__LINE__.':CouldNotSaveException: SKU:' . $product->getSku()
                            .' un-asssign from category: '.$categoryId.' Error with Delete Queue Process:'
                            . $e->getMessage());
                        }
                    }
                }
                //end code for unassign product from the categories

                // insert product in clean up queue.
                $catalogSyncQueueCleanup = $this->catalogSyncQueueCleanupProcessFactory->create();
                $catalogSyncQueueCleanup->setCatalogSyncQueueId($catalogSyncQueueId);
                $catalogSyncQueueCleanup->setSharedCatalogId($sharedCatalogId);
                $catalogSyncQueueCleanup->setCategoryId($currentCategoryId);
                $catalogSyncQueueCleanup->setJsonData($productId);
                $catalogSyncQueueCleanup->setProductId($productId);
                $catalogSyncQueueCleanup->setSku($product->getSku());
                $catalogSyncQueueCleanup->setCatalogType('product');
                $catalogSyncQueueCleanup->setStatus(self::STATUS_PENDING);
                try {
                    $catalogSyncQueueCleanup->save();
                    $this->logger->info(__METHOD__.':'.__LINE__.':Product clean up queue created.');
                } catch (\Exception $exception) {
                    $this->logger->error(__METHOD__.':'.__LINE__.':Product clean up queue creation error:' . $exception->getMessage());
                }
            }
        }
    }

    /**
     * CheckForDelete and create delete queues
     *
     * @param array $notAvailableProductsIdsInApiResponse
     * @param Int $categoryId
     * @param Int $catalogSyncQueueId
     * @param Int $sharedCatalogId
     *
     * @return boolean
     */
    public function deleteCatalogProduct(
        $notAvailableProductsIdsInApiResponse,
        $categoryId,
        $catalogSyncQueueId,
        $sharedCatalogId
    ) {
        if (isset($notAvailableProductsIdsInApiResponse) && count($notAvailableProductsIdsInApiResponse) > 0) {
            foreach ($notAvailableProductsIdsInApiResponse as $productId) {
                $isQuoteAvailable = $this->checkNegotiableQuote($productId);
                if (!$isQuoteAvailable) {
                    // Put in delete queue.
                    $catalogSyncQueue = $this->catalogSyncQueueProcessFactory->create();
                    $catalogSyncQueue->setCatalogSyncQueueId($catalogSyncQueueId);
                    $catalogSyncQueue->setSharedCatalogId($sharedCatalogId);
                    $catalogSyncQueue->setCategoryId($categoryId);
                    $catalogSyncQueue->setStatus(self::STATUS_PENDING);
                    $catalogSyncQueue->setJsonData($productId);
                    $catalogSyncQueue->setCatalogType('product');
                    $catalogSyncQueue->setActionType('delete');

                    try {
                        $catalogSyncQueue->save();
                        $lastInsrtedId = $catalogSyncQueue->getId();
                        // Publish into message Queue.
                        $this->message->setMessage($lastInsrtedId);
                        $this->publisher->publish('product', $this->message);
                        $this->logger->info(__METHOD__.':'.__LINE__.':Product delete queue created.');
                    } catch (\Exception $exception) {
                        $this->logger->error(__METHOD__.':'.__LINE__.':Product delete queue creation error:' . $exception->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Get products assigned in specific category those are not in api response
     *
     * @param array $categoryLevelSkus
     * @param Int $categoryId
     * @return array $notAvailableProductsInApiResponse
     */
    public function getProductCollectionByCategories($categoryLevelSkus, $categoryId)
    {
        $categoryProductsCollection = $this->productCollectionFactory->create();
        $categoryProductsCollection->addAttributeToSelect(['id', 'sku']);
        if (count($categoryLevelSkus) > 0) {
            $categoryProductsCollection->addAttributeToFilter('sku', ['nin' => $categoryLevelSkus]);
        }
        $categoryProductsCollection->addCategoriesFilter(['eq' => $categoryId]);
        $notAvailableProductsInApiResponse = [];
        if (!empty($categoryProductsCollection->getSize())) {
            foreach ($categoryProductsCollection as $product) {
                $notAvailableProductsInApiResponse[] = $product->getId();
            }
        }
        return $notAvailableProductsInApiResponse;
    }

    /**
     * Get products assigned in specific category and create delete queues
     *
     * @param Int $categoryId
     * @param Int $catalogSyncQueueId
     * @param int $sharedCatalogId
     */
    public function removeProductsByCategory($categoryId, $catalogSyncQueueId, $sharedCatalogId)
    {
        $categoryProductsCollection = $this->productCollectionFactory->create();
        $categoryProductsCollection->addAttributeToSelect(['id', 'sku']);
        $categoryProductsCollection->addCategoriesFilter(['eq' => $categoryId]);

        $removeProductsIds = [];

        if (!empty($categoryProductsCollection->getSize())) {
            foreach ($categoryProductsCollection as $product) {
                $isPodProductEditAble  = $this->catalogMvpHelper->isProductPodEditAbleById($product->getId());
                if (!$isPodProductEditAble) {
                    $removeProductsIds[] = $product->getId();
                }

            }
            $this->cleanUpCatalogProductQueue(
                $removeProductsIds,
                $categoryId,
                $catalogSyncQueueId,
                $sharedCatalogId
            );
        }
    }

    /**
     * Manage docs life expiry for migrated catalog items imported through CSV with companies
     * @param string $sku
     */
    public function manageDocsLifeExpire($sku)
    {
        $rabbitMqJson = [];
        $product = $this->productRepositoryInterface->get($sku);

        // Fix issue D-209736
        $expirationDocumentDateNullToggle = $this->toggleConfig->getToggleConfigValue(
            'techtitans_D209736_migrated_document_expire_date_null_fix'
        );
        // If document already expired and removed from system that case it will no more set for retry
        if ($expirationDocumentDateNullToggle && $product->getIsDocumentExpire()) {
            return;
        }

        $externalProductData =  $product->getExternalProd();
        if ($externalProductData) {
            $externalProd = json_decode((string)$externalProductData, true);
            if (isset($externalProd['contentAssociations'])) {
                $arrProData = (array) $externalProd['contentAssociations'];
                foreach ($arrProData as $proData) {
                    if (array_key_exists('contentReference', $proData)) {
                        $rabbitMqJson[] = [
                            'documentId' => $proData['contentReference'],
                            'produtId' => $product->getId(),
                        ];
                    }
                }

                if (!empty($rabbitMqJson)) {
                    $this->docRefMessage->setMessage(json_encode($rabbitMqJson));
                    $this->publisher->publish('docRefExtandExpire', $this->docRefMessage);
                }
            }
        }
    }

    /**
     * Return Toggle Tiger Team - D-184380 - epro_Able to see Add to cart for custom docs in adobe live search
     *
     * @return bool|int|null
     */
    public function isD184380ToggleEnabled()
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::D_184380_EPRO_CUSTOMIZE_SEARCH_ACTION_FIX);
    }

    /**
     * Handle page group
     * @param string $externalProdData
     * @return string $externalProdData
     */
    public function handlePageGroups($externalProdData)
    {
        $externalProdDataArray = [];

        if ($externalProdData) {
            $externalProdDataArray = json_decode($externalProdData);
            if (isset($externalProdDataArray->contentAssociations) && is_array($externalProdDataArray->contentAssociations) && count($externalProdDataArray->contentAssociations) > 0) {
                foreach ($externalProdDataArray->contentAssociations as $key => $content) {
                    if (!isset($content->pageGroups) || empty($content->pageGroups)) {
                        $pageGroup = $this->fxoCMHelper->getPageGroupsPrintReady($content->contentReference, true);

                        $this->logger->info(
                            __METHOD__.':'.__LINE__. ' : Page Group retrieved from
                             printReady API for document id: ' . $content->contentReference .' is:
                             ' . json_encode($pageGroup)
                        );
                        $externalProdDataArray->contentAssociations[$key]->pageGroups = $pageGroup ?? [];
                    }
                }
            }
        }

        return json_encode($externalProdDataArray);
    }
}
