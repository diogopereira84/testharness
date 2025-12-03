<?php

 /**
 * Fedex_CatalogMigration
 *
 * @category   Fedex
 * @package    Fedex_CatalogMigration
 * @author     Bhairav Singh
 * @email      bhairav,singh.osv@fedex.com
 * @copyright  © FedEx, Inc. All rights reserved.
 */

declare(strict_types=1);

namespace Fedex\CatalogMigration\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Fedex\SharedCatalogCustomization\Helper\Data;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory;
use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\CatalogMigration\Model\CatalogMigrationFactory;
use Psr\Log\LoggerInterface;
use Magento\Backend\Model\Auth\Session;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueProcessFactory;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Fedex\CatalogMvp\Helper\CatalogMvp;

/**
 * CatalogMigrationHelper Helper Class
 */
class CatalogMigrationHelper extends AbstractHelper
{
    public const B2B_ROOT_CATEGORY = 'B2B Root Category';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    private Session $authSession;
    private CollectionFactory $sharedCatalogCollectionFactory;

    /**
     * CatalogMigrationHelper constructor
     *
     * @param Context $context
     * @param CatalogSyncQueueFactory $catalogSyncQueueFactory
     * @param CompanyRepositoryInterface $companyRepositoryInterface
     * @param Data $dataHelper
     * @param CollectionFactory $collectionFactory
     * @param MessageInterface $message
     * @param PublisherInterface $publisher
     * @param CatalogMigrationFactory $migrationProcess
     * @param LoggerInterface $logger
     * @param Session $session
     * @param CatalogSyncQueueProcessFactory $catalogSyncQueueProcessFactory
     * @param CategoryLinkManagementInterface $categoryLinkManagementInterface
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param CustomerRepositoryInterface $customerRepository
     * @param ProductRepositoryInterface $productRepository
     * @param Processor $mediaGalleryProcessor
     * @param CatalogMvp $catalogMvp
     */
    public function __construct(
        Context $context,
        private CatalogSyncQueueFactory $catalogSyncQueueFactory,
        private CompanyRepositoryInterface $companyRepositoryInterface,
        private Data $dataHelper,
        CollectionFactory $collectionFactory,
        private MessageInterface $message,
        private PublisherInterface $publisher,
        private CatalogMigrationFactory $migrationProcess,
        private LoggerInterface $logger,
        Session $session,
        private CatalogSyncQueueProcessFactory $catalogSyncQueueProcessFactory,
        private CategoryLinkManagementInterface $categoryLinkManagementInterface,
        private ProductRepositoryInterface $productRepositoryInterface,
        private CustomerRepositoryInterface $customerRepository,
        private Processor $mediaGalleryProcessor,
        private CatalogMvp $catalogMvp
    ) {
        parent::__construct($context);
        $this->sharedCatalogCollectionFactory = $collectionFactory;
        $this->authSession = $session;
    }

    /**
     * Validates the data from a sheet.
     *
     * This function checks each row of data to ensure that required columns have non-empty values.
     * If any errors are found, it constructs an error message indicating the affected rows and columns.
     *
     * @param array $datas An array of sheet data, where each element represents a row.
     * @param int $compId company id
     * @param int $sharedCatId shared catalog id
     * @param string $extUrl Company url to verify sheet url and company form url should
     *
     * @return array An associative array with the validation result.
     * - status (bool): True if all rows are valid, false otherwise.
     * - message (string): A message indicating the validation result.
     * If all rows are valid, it contains a success message.
     * If there are errors, it contains an error message with details about the affected rows and columns.
     */
    public function validateSheetData($datas, $compId, $sharedCatId, $extUrl)
    {
        $errorMessage = '';
        unset($datas[0]);
        foreach ($datas as $key => $catalogData) {
            $errorColumns = '';
            $columnErrorFlag = false;
            // Create unique sku if not
            if (!$catalogData[0]) {
                $datas[$key][0] = $this->generate32BitSKU();
            }
            // Check the provided catalog data for missing values in specific columns
            $emptyColumns = $this->checkColumnData($datas[$key], $extUrl);
            $errorColumns = $emptyColumns["errorColumns"];
            $columnErrorFlag = $emptyColumns["columnErrorFlag"];
            $rowCommaIndex = ', <b>Row</b> '.($key + 1).': '. $errorColumns;
            $rowIndex = (($columnErrorFlag) ? '<b>Row</b> '.($key + 1).': '. $errorColumns: '');

            $errorMessage .= (!empty($errorMessage) && $columnErrorFlag) ? $rowCommaIndex: $rowIndex;
        }

        if (count($datas)) {
            $result = [
                "status" => empty($errorMessage) ? true: false,
                "message" => empty($errorMessage) ? 'Catalog migration successfully queued for processing.':
                 'Missing data in some rows columns ' . $errorMessage . '.'
            ];
        } else {
            $result = [
                "status" => false,
                "message" => 'Catalog migration csv sheet with empty data.'
            ];
        }

        if ($result["status"]) {
            // Process import
            $this->processImport($datas, $compId, $sharedCatId);
        }

        return $result;
    }

    /**
     * Check the provided catalog data for missing values in specific columns.
     *
     * @param array $catalogData An array representing catalog data where each index corresponds to a column.
     * @param string $extUrl Company url to verify sheet url and company form url should
     *
     * @return array An associative array with two keys:
     *               - 'columnErrorFlag': A boolean indicating if there are missing values in any column.
     *               - 'errorColumns': A string listing the columns with missing values (comma-separated).
     */
    public function checkColumnData($catalogData, $extUrl)
    {
        $columnErrorFlag = false;
        $errorColumns = '';

        $checkEmpty = function ($index, $columnName) use ($catalogData, &$errorColumns, &$columnErrorFlag, &$extUrl) {
            if (empty($catalogData[$index])) {
                $errorColumns .= ($columnErrorFlag ? ', ' : '') . $columnName;
                $columnErrorFlag = true;
            }
            if (!empty($catalogData[$index]) && $index == 4 && $catalogData[$index] != $extUrl) {
                $errorColumns .= ($columnErrorFlag ? ', ' : '') . '<b style="color: #eb5202;">'
                . $columnName.' Invalid </b>';
                $columnErrorFlag = true;
            }
        };

        // Check specific columns for missing values
        $checkEmpty(0, 'sku');
        $checkEmpty(1, 'name');
        $checkEmpty(3, 'Price');
        $checkEmpty(4, 'company url');

        return [
            "columnErrorFlag" => $columnErrorFlag,
            "errorColumns" => $errorColumns
        ];
    }

    /**
     * Process Catalog Import
     * @param array $rowsData
     * @param int $companyId
     * @param int $browseCatalogCatId
     */
    public function processImport($rowsData, $companyId, $browseCatalogCatId)
    {
        $categoryProducts = [];
        unset($rowsData[0]);
        $isImport = 1;
        $userName = '';
        $emailId = '';

        $companyData = $this->companyRepositoryInterface->get($companyId);
        $customerGroupId = $companyData->getCustomerGroupId();
        $companySuperUserId = $companyData->getSuperUserId();

        // find store Id
        $storeId = $this->dataHelper->getStoreId($companyId);
        $browseCatalogCatName = $this->dataHelper->getBrowseCatalogCategoryName($browseCatalogCatId);

        // Get Shared Catalog Id by Customer group id
        $collection = $this->sharedCatalogCollectionFactory->create();
        $collection->addFieldToFilter('customer_group_id', ['eq' => $customerGroupId]);
        $sharedCatalog = $collection->getFirstItem();
        $sharedCatalogId = $sharedCatalog->getId();

        $userData = $this->authSession->getUser();
        if ($userData !== null) {
            $userName = $userData->getFirstname() . ' ' . $userData->getLastname();
            $emailId  = $userData->getEmail();
        } else {
            $customerObj = $this->customerRepository->getById($companySuperUserId);
            if ($customerObj !== null) {
                $userName = $customerObj->getFirstname() . ' ' . $customerObj->getLastname();
                $emailId  = $customerObj->getEmail();
            }
        }

        $catalogSynchQueue = $this->catalogSyncQueueFactory->create();
        $catalogSynchQueue->setCompanyId($companyId)
            ->setStoreId($storeId)
            ->setSharedCatalogId($sharedCatalogId)
            ->setStatus(static::STATUS_PROCESSING)
            ->setCreatedBy($userName)
            ->setEmailId($emailId)
            ->setIsImport($isImport)
            ->save();

        $lastInsertedSyncQueueId = $catalogSynchQueue->getId();

        foreach ($rowsData as $row) {
            // Check if productInstance not available then don't import it and log
            if (empty(trim($row[5]))) {
                $this->logger->error('Product with sku ' . $row[0] . '
                could not imported due to empty product Instance with sync id :'. $lastInsertedSyncQueueId);
                continue;
            }
            // prepare 2D[] with categories and products
            $categoryProducts[$row[7]][] = $this->prepareProductData(
                $row,
                $companyId,
                $sharedCatalogId,
                $storeId,
                $lastInsertedSyncQueueId
            );
        }
        $rooatCategoryDeatail = $this->catalogMvp->getRootCategoryDetailFromStore('ondemand');
        $b2bCategoryName = $rooatCategoryDeatail['name'] ?? static::B2B_ROOT_CATEGORY;
        foreach ($categoryProducts as $key => $categoryProduct) {

            if ($this->dataHelper->isMigrationFixToggle()) {
                $categoriesArr = explode(',', trim($key));
                $categoriesCount = count($categoriesArr);
                $categoryPath = [];
                if ($categoriesCount > 0 && !empty($categoriesArr[0])) {
                    for ($count = 0; $count < $categoriesCount; $count++)
                    {
                        $categoryPathValue = trim($categoriesArr[$count]);
                        $categoryPathValue = ltrim($categoryPathValue, "/");
                        $categoryPath[] = trim($b2bCategoryName) .'/'. trim($browseCatalogCatName) . '/' . $categoryPathValue;
                    }
                } else {
                    $categoryPath[] = trim($b2bCategoryName) .'/'. trim($browseCatalogCatName);
                }

                $categoryProductData = [
                    'category_path' => implode(',', $categoryPath),
                    'products'      => $categoryProduct
                ];
            } else {
                $categoryPath = $b2bCategoryName .'/'. $browseCatalogCatName . trim($key);
                // create chunks of categories/products into migration queue.
                $categoryProductData = [
                    'category_path' => $categoryPath,
                    'products'      => $categoryProduct
                ];
            }

            $categoryProductJsonData = json_encode($categoryProductData);

            try {
                $migrationProcess = $this->migrationProcess->create();
                $migrationProcess->setCatalogSyncQueueId($lastInsertedSyncQueueId)
                    ->setStatus(static::STATUS_PENDING)
                    ->setJsonData($categoryProductJsonData)
                    ->save();
                $lastMigrationProcessId = $migrationProcess->getId();

                $categoryProductJsonDataDecoded = json_decode($categoryProductJsonData, true);
                $categoryProductJsonDataDecoded['lastMigrationProcessId'] = $lastMigrationProcessId;

                // publish into migration queue
                $this->message->setMessage(json_encode($categoryProductJsonDataDecoded));
                $this->publisher->publish('catalogMigration', $this->message);
            } catch (\Exception $e) {
                $this->logger->error(
                    __METHOD__.':'.__LINE__.
                    ' Error with migration process queue creation with sync queue id ' .
                     $lastInsertedSyncQueueId .' is: ' . var_export($e->getMessage(), true)
                     . ' with category products: ' . json_encode($categoryProduct)
                );
            }
        }
    }

    /**
     * Update migration queue status
     * @param int    $lastMigrationProcessId
     * @param string $status
     */
    public function updateCatalogMigrationQueueStatus($lastMigrationProcessId, $status)
    {
        try {
            $migrationProcess = $this->migrationProcess->create();
            $migrationProcess->setId($lastMigrationProcessId)
                ->setStatus($status)
                ->save();
            $this->logger->info(__METHOD__.':'.__LINE__. ' Migration process queue status: ' . $status);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__. ' Error with migration process queue ' .
                $lastMigrationProcessId .' status update is: ' . var_export($e->getMessage(), true));
        }
    }

    /**
     * Prepare product data array schema
     *
     * @param array $rowColumnsData
     * @param int $companyId
     * @param int $sharedCatalogId
     * @param int $storeId
     * @param int $catalogSyncQueueId
     */
    public function prepareProductData($rowColumnsData, $companyId, $sharedCatalogId, $storeId, $catalogSyncQueueId)
    {
        $customizableFields = trim($rowColumnsData[9]);
        if (strtolower(trim($rowColumnsData[8])) == 'true' && !empty($customizableFields)) {
            $customizableFieldsArray = $this->formatCustomizedFields($customizableFields);
            $customizableFields = json_encode($customizableFieldsArray);
            $customizableFields = '['. $customizableFields .']';
        }

        return [
            "id" => trim($rowColumnsData[0]),
            "version" => "",
            "name" => trim($rowColumnsData[1]),
            "description" => trim($rowColumnsData[2]),
            "createdBy" => "",
            "creationTime" => "",
            "modifiedBy" => "",
            "modifiedTime" => "",
            "routingLocation" => !empty($rowColumnsData[12]) ? trim($rowColumnsData[12]) : '',
            "links" => [
                ["href" => "", "rel" => "detail"],
                ["href" => trim($rowColumnsData[6]), "rel" => "thumbnail"]
            ],
            "type" => "PRODUCT",
            "catalogProductSummary" => [
                "productRateTotal" => [
                    "currency" => "USD",
                    "price" => trim($rowColumnsData[3]),
                ],
                "customizable" => strtolower(trim($rowColumnsData[8])) == 'true' ? 1 : 0,
                "availability" => [
                    "available" => true,
                    "dateRange" => [
                        "startDateTime" => ""
                    ]
                ],
                "editable" => strtolower(trim($rowColumnsData[10])) == 'true' ? 1 : 0,
            ],
            "additionalData" => [
                "is_catalog_migration" => true,
                "productInstance" => trim($rowColumnsData[5]),
                "customizeInstance" => trim($customizableFields),
                "DltData" => $this->getDltJson(trim($rowColumnsData[11])),
                "storeId" => $storeId,
                "compId" => $companyId,
                "sharedCatId" => $sharedCatalogId,
                "catalogSyncQueueId" => $catalogSyncQueueId
            ]
        ];
    }

    /**
     * Create product queue
     *
     * @param array $migrationCatalogData
     * @param array $categoryIds
     */
    public function createProductCreateUpdateQueue($migrationCatalogData, $categoryIds)
    {
        $itemSummaries = $migrationCatalogData["products"];
        $isNew = 0;

        foreach ($itemSummaries as $itemSummary) {
            $itemSku = $itemSummary['id'];
            $additionalData = $itemSummary["additionalData"];
            // Check if product is exist by SKU & assign to category if not
            try {
                $product = $this->productRepositoryInterface->get($itemSku);
                // Check if exist product not assign to category then assign
                $this->checkProductAndAssignToCategory($product, $categoryIds);
                $itemSummary["productId"] = $product->getId();
                $isNew = 0;
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error found under createProductCreateUpdateQueue: ' . $e->getMessage());
                $isNew = 1;
            }

            // Process the item with the determined action
            $this->createItemProccessDataWithQueue($itemSummary, $additionalData, $categoryIds, $isNew);
        }
    }

    /**
     * Check if exist product not assign to category then assign
     *
     * @param object $product
     * @param array $categryIds
     */
    public function checkProductAndAssignToCategory($product, $categoryIds)
    {
        if ($this->dataHelper->isMigrationFixToggle()) {
            $this->categoryLinkManagementInterface->assignProductToCategories(
                $product->getSku(),
                $categoryIds
            );
        } else {
            foreach ($categoryIds as $categoryId) {
                if (!in_array($categoryId, $product->getCategoryIds())) {
                    $this->categoryLinkManagementInterface->assignProductToCategories(
                        $product->getSku(),
                        [$categoryId]
                    );
                }
            }
        }
    }

    /**
     * Create Queue to process item creation and updation.
     *
     * @param array $itemSummary
     * @param array $additionalData
     * @param array $categoryIds
     * @param string $isNew
     */
    public function createItemProccessDataWithQueue($itemSummary, $additionalData, $categoryIds, $isNew)
    {
        // Determine whether to create or update the product
        $actionType = $isNew && isset($itemSummary['id']) ? 'new' : 'update';

        $catalogSyncQueueId = $additionalData["catalogSyncQueueId"];
        $sharedCatalogId = $additionalData["sharedCatId"];
        $storeId = $additionalData["storeId"];
        $itemSummaryJson = json_encode($itemSummary);

        if ($this->dataHelper->isMigrationFixToggle()) {
            $categoryIdStr = implode(",", $categoryIds);
            $catalogSyncQueue = $this->catalogSyncQueueProcessFactory->create();
            $catalogSyncQueue->setCatalogSyncQueueId($catalogSyncQueueId);
            $catalogSyncQueue->setSharedCatalogId($sharedCatalogId);
            $catalogSyncQueue->setCategoryId($categoryIdStr);
            $catalogSyncQueue->setStoreId($storeId);
            $catalogSyncQueue->setStatus(self::STATUS_PENDING);
            $catalogSyncQueue->setCatalogType('product');
            $catalogSyncQueue->setJsonData($itemSummaryJson);
            $catalogSyncQueue->setActionType($actionType);

            try {
                $catalogSyncQueue->save();
                $lastInsertedId = $catalogSyncQueue->getId();
                // Publish into message Queue.
                $this->message->setMessage($lastInsertedId);
                $this->publisher->publish('product', $this->message);
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ':Product queue created for sku: '. $itemSummary['id']);
            } catch (\Exception $exception) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ':Product queue creation error for sku: '.
                $itemSummary['id']. ' ' . $exception->getMessage());
            }
        } else {
            foreach ($categoryIds as $categoryId) {
                $catalogSyncQueue = $this->catalogSyncQueueProcessFactory->create();
                $catalogSyncQueue->setCatalogSyncQueueId($catalogSyncQueueId);
                $catalogSyncQueue->setSharedCatalogId($sharedCatalogId);
                $catalogSyncQueue->setCategoryId($categoryId);
                $catalogSyncQueue->setStoreId($storeId);
                $catalogSyncQueue->setStatus(self::STATUS_PENDING);
                $catalogSyncQueue->setCatalogType('product');
                $catalogSyncQueue->setJsonData($itemSummaryJson);
                $catalogSyncQueue->setActionType($actionType);

                try {
                    $catalogSyncQueue->save();
                    $lastInsertedId = $catalogSyncQueue->getId();
                    // Publish into message Queue.
                    $this->message->setMessage($lastInsertedId);
                    $this->publisher->publish('product', $this->message);
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ':Product queue created for sku: '. $itemSummary['id']);
                } catch (\Exception $exception) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ':Product queue creation error for sku: '.
                    $itemSummary['id']. ' ' . $exception->getMessage());
                }
            }
        }
    }

    /**
     * getDltJson Method.
     * @param string $dltJson
     * return null|jsonString
     */
    public function getDltJson($dltJson)
    {
        $dltDataJsonString = null;
        $dltData = [];
        if (!empty($dltJson)) {
            $dltJson = json_decode($dltJson);
            $count = 0;
            if (isset($dltJson->DLT)) {
                foreach ($dltJson->DLT as $dlt) {
                    if (isset($dlt->start, $dlt->end, $dlt->production_hours)) {
                        $dltData[] = '{"record_id":' . $count . ',"dlt_start":' . $dlt->start . ',
                            "dlt_end":' . $dlt->end . ',"dlt_hours":' . $dlt->production_hours . '}';
                    }
                    $count++;
                }

                if (!empty($dltData)) {
                    $dltDataJsonString = '{"dlt_threshold_field":[' . implode(",", $dltData) . ']}';
                }
            }
        }

        return $dltDataJsonString;
    }

    /**
     * generate32BitSKU Method.
     * return string
     */
    public function generate32BitSKU()
    {
        $productSku = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));

        return $productSku;
    }

     /**
     * Remove not role selected image
     * @param string $sku
     */
    public function removeProductImage($sku) {

        try {
            // Load the product by SKU
            $product = $this->productRepositoryInterface->get($sku);

            // Get the media gallery from the product
            $mediaGalleryEntries = $product->getMediaGalleryEntries();

            foreach ($mediaGalleryEntries as $key => $mediaGalleryEntry) {

                //Remove the  image entry from the media gallery
                if (is_array($mediaGalleryEntry->getTypes()) && count($mediaGalleryEntry->getTypes()) < 1) {
                    $this->mediaGalleryProcessor->removeImage($product, $mediaGalleryEntry->getFile());
                }
            }

            // Save the product
            $this->productRepositoryInterface->save($product);
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ':Product image deleted for sku: '. $sku);

        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ':Product image deletion error for sku: '.
                $sku. ' ' . $e->getMessage());
        }
    }

    /**
     * Format custimizable fields
     * @param string $customizableFields
     * @param string
     */
    public function formatCustomizedFields($customizableFields)
    {
        $customizableFieldsArray = json_decode($customizableFields, true);

        $updatedCustomFields = [];

        foreach ($customizableFieldsArray['customizableFields'] as $key => $customizableField) {

            $updatedCustomFields['documentId'] =
                $customizableField['documentAssociations'][0]['documentId'];
            $updatedCustomFields['formFields'][$key]['fieldName'] = $customizableField['id'];
            $updatedCustomFields['formFields'][$key]['fieldType'] = $customizableField['inputType'];
            $updatedCustomFields['formFields'][$key]['pageNumber'] =
                $customizableField['documentAssociations'][0]['pageNumber'];
            $updatedCustomFields['formFields'][$key]['label'] = $customizableField['name'];
            $updatedCustomFields['formFields'][$key]['description'] = $customizableField['description'] ?? null;
            $updatedCustomFields['formFields'][$key]['hintText'] = $customizableField['description'] ?? null;
            $updatedCustomFields['formFields'][$key]['userInputDetails']['required'] =
                $customizableField['mandatory'] == 1 ? 'true' : 'false';

            if ($customizableField['inputType'] == 'TEXT' && $customizableField['inputMethod'] == 'FREEFORM') {

                $updatedCustomFields['formFields'][$key]['userInputDetails']['inputMethod'] = 'FREEFORM';
                $updatedCustomFields['formFields'][$key]['defaultValue']['textValue'] =
                    $customizableField['defaultValue']['textValue']  ?? null;
            } elseif (
                $customizableField['inputType'] == 'TEXT' &&
                ($customizableField['inputMethod'] == 'SELECT' ||
                $customizableField['inputMethod'] == 'EITHER')
            ) {

                $updatedCustomFields['formFields'][$key]['userInputDetails']['inputMethod'] =
                    $customizableField['inputMethod'];
                $updatedCustomFields['formFields'][$key]['userInputDetails']['options'] =
                    $customizableField['options'] ?? null;
                $updatedCustomFields['formFields'][$key]['defaultValue']['textValue'] =
                    $customizableField['defaultValue']['textValue'] ?? null;
            } elseif (
                $customizableField['inputType'] == 'IMAGE'
            ) {
                $updatedCustomFields['formFields'][$key]['userInputDetails']['inputMethod'] =
                    $customizableField['inputMethod'];
                if (!empty($customizableField['options'])) {
                    foreach ($customizableField['options'] as $opKey => $option) {
                        $updatedCustomFields['formFields'][$key]['userInputDetails']['options'][$opKey]
                            ['imageValue']['documentId'] = $option['imageValue']['documentId'];
                        $updatedCustomFields['formFields'][$key]['userInputDetails']['options'][$opKey]
                            ['imageValue']['documentName'] = $option['imageValue']['fileName'];
                    }
                }
            }
        }

        return $updatedCustomFields;
    }
}
