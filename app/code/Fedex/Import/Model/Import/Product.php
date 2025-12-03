<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Model\Import;

use Fedex\Import\Api\UrlKeyManagerInterface;
use Magento\CatalogImportExport\Model\Import\Product\TaxClassProcessor;
use Magento\Framework\Stdlib\DateTime;
use Magento\CatalogImportExport\Model\Import\Product as MagentoProduct;
use Magento\ImportExport\Model\Import;
use Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface as ValidatorInterface;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Psr\Log\LoggerInterface;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Store\Model\Store;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\DriverInterface as File;

/**
 * Class Product
 * @package Fedex\Import\Model\Import
 * @codeCoverageIgnore
 */
class Product extends MagentoProduct
{

    public $logger;
    public $eavEntityFactory;
    /**
     * Default website id
     */
    const DEFAULT_WEBSITE_ID = 1;

    /**
     * Used when create new attributes in column name
     */
    const ATTRIBUTE_SET_GROUP = 'attribute_set_group';

    /**
     * Attribute sets column name
     */
    const ATTRIBUTE_SET_COLUMN = 'attribute_set';

    /**
     * @var \Fedex\Import\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Fedex\Import\Model\Source\Type\AbstractType
     */
    protected $_sourceType;

    /**
     * @var array
     */
    protected $_attributeSetGroupCache;

    /**
     * @var ProductRepositoryInterface
     */
    protected $collection;

    /**
     * Product entity link field
     *
     * @var string
     */
    private $productEntityLinkField;
    /**
     * Valid column names
     */
    protected $validColumnNames = [
        'company_id',
        'shared_catalog'
    ];

    /**
     * Shared Catlog Data
     */
    protected $sharedCatalogData = [];

    /**
     * Company Id column error message
     */
    const COMPANY_REQUIRE = 'The company id cannot be empty';
    /**
     * Shared Catalog column error message
     */
    const SHARED_CATALOG_REQUIRE = 'The shared catalog cannot be empty';
     /**
     * Column product store.
     */
    const COL_STORE = '_store';
    /**
     * Data row scopes.
     */
    const SCOPE_DEFAULT = 1;
    const SCOPE_WEBSITE = 2;
    const SCOPE_STORE = 0;
    const SCOPE_NULL = -1;


    /**
     * Product constructor.
     * @param \Fedex\Import\Helper\Data $helper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
     * @param \Magento\CatalogInventory\Model\Spi\StockStateProviderInterface $stockStateProvider
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param Import\Config $importConfig
     * @param \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory $resourceFactory
     * @param MagentoProduct\OptionFactory $optionFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setColFactory
     * @param MagentoProduct\Type\Factory $productTypeFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\LinkFactory $linkFactory
     * @param \Magento\CatalogImportExport\Model\Import\Proxy\ProductFactory $proxyProdFactory
     * @param \Magento\CatalogImportExport\Model\Import\UploaderFactory $uploaderFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $stockResItemFac
     * @param DateTime\TimezoneInterface $localeDate
     * @param DateTime $dateTime
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param LoggerInterface $logger
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param MagentoProduct\StoreResolver $storeResolver
     * @param MagentoProduct\SkuProcessor $skuProcessor
     * @param MagentoProduct\CategoryProcessor $categoryProcessor
     * @param MagentoProduct\Validator $validator
     * @param ObjectRelationProcessor $objectRelationProcessor
     * @param TransactionManagerInterface $transactionManager
     * @param TaxClassProcessor $taxClassProcessor
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\Product\Url $productUrl
     * @param \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollectionFactory
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param UrlKeyManagerInterface $urlKeyManager
     * @param Fedex\Import\Helper\Data   $fedexHelper
     * @param File $file
     * @param array $data
     */
    public function __construct(
        \Fedex\Import\Helper\Data $helper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface $errorAggregator,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\CatalogInventory\Model\Spi\StockStateProviderInterface $stockStateProvider,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\ImportExport\Model\Import\Config $importConfig,
        \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory $resourceFactory,
        \Magento\CatalogImportExport\Model\Import\Product\OptionFactory $optionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setColFactory,
        \Magento\CatalogImportExport\Model\Import\Product\Type\Factory $productTypeFactory,
        \Magento\Catalog\Model\ResourceModel\Product\LinkFactory $linkFactory,
        \Magento\CatalogImportExport\Model\Import\Proxy\ProductFactory $proxyProdFactory,
        \Magento\CatalogImportExport\Model\Import\UploaderFactory $uploaderFactory,
        private \Magento\Framework\Filesystem $filesystem,
        \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $stockResItemFac,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        DateTime $dateTime,
        LoggerInterface $logger,
        protected \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\CatalogImportExport\Model\Import\Product\StoreResolver $storeResolver,
        \Magento\CatalogImportExport\Model\Import\Product\SkuProcessor $skuProcessor,
        \Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor $categoryProcessor,
        \Magento\CatalogImportExport\Model\Import\Product\Validator $validator,
        ObjectRelationProcessor $objectRelationProcessor,
        TransactionManagerInterface $transactionManager,
        TaxClassProcessor $taxClassProcessor,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\Url $productUrl,
        protected \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory,
        protected \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollectionFactory,
        protected \Magento\Catalog\Helper\Product $productHelper,
        protected ProductUrlRewriteGenerator $productUrlRewriteGenerator,
        protected UrlPersistInterface $urlPersist,
        Collection $collection,
        protected UrlKeyManagerInterface $urlKeyManager,
        private \Magento\Framework\Filesystem\DirectoryList $dir,
        private \Fedex\Import\Helper\Data|Fedex\Import\Helper\Data $fedexHelper,
        private readonly File $file,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->collection = $collection;

        parent::__construct(
            $jsonHelper,
            $importExportData,
            $importData,
            $config,
            $resource,
            $resourceHelper,
            $string,
            $errorAggregator,
            $eventManager,
            $stockRegistry,
            $stockConfiguration,
            $stockStateProvider,
            $catalogData,
            $importConfig,
            $resourceFactory,
            $optionFactory,
            $setColFactory,
            $productTypeFactory,
            $linkFactory,
            $proxyProdFactory,
            $uploaderFactory,
            $this->filesystem,
            $stockResItemFac,
            $localeDate,
            $dateTime,
            $logger,
            $indexerRegistry,
            $storeResolver,
            $skuProcessor,
            $categoryProcessor,
            $validator,
            $objectRelationProcessor,
            $transactionManager,
            $taxClassProcessor,
            $scopeConfig,
            $productUrl,
            $data
        );
    }

    /**
     * Initialize source type model
     *
     * @param $type
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _initSourceType($type)
    {
        if (!$this->_sourceType) {
            $this->_sourceType = $this->_helper->getSourceModelByType($type);
            $this->_sourceType->setData($this->_parameters);
        }
    }

    /**
     * Get available columns
     *
     * @return array
     */
    public function getValidColumnNames(): array
    {
        return $this->validColumnNames;
    }


    /**
     * Get available columns
     *
     * @return array
     */
    private function getAvailableColumns(): array
    {
        return $this->validColumnNames;
    }

    /**
     * import product data
     */
    public function importData()
    {
        $this->_validatedRows = null;

        if (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
            $this->_replaceFlag = true;
            $this->replaceProducts();
        } elseif (Import::BEHAVIOR_DELETE == $this->getBehavior()) {
            $this->_deleteProducts();
        } else {
            $this->saveProductsData();
        }
        $this->_eventManager->dispatch('catalog_product_import_finish_before', ['adapter' => $this]);
        return true;
    }

    /**
     * Replace imported products.
     *
     * @return $this
     */
    protected function replaceProducts()
    {
        $this->deleteProductsForReplacement();
        $this->_oldSku = $this->skuProcessor->reloadOldSkus()->getOldSkus();
        $this->_validatedRows = null;
        $this->setParameters(array_merge(
            $this->getParameters(),
            ['behavior' => Import::BEHAVIOR_APPEND]
        ));
        $this->saveProductsData();

        return $this;
    }

    /**
     * Save products data.
     *
     * @return $this
     */
    protected function saveProductsData()
    {
        $this->saveProducts();
        foreach ($this->_productTypeModels as $productTypeModel) {
            $productTypeModel->saveData();
        }
        $this->_saveLinks();
        $this->_saveStockItem();
        if ($this->_replaceFlag) {
            $this->getOptionEntity()->clearProductsSkuToId();
        }
        $this->getOptionEntity()->importData();
        $this->saveSharedCatalogData();

        return $this;
    }

    /**
     * Gather and save information about product entities.
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function saveProducts()
    {
        /** @var $resource \Magento\CatalogImportExport\Model\Import\Proxy\Product\Resource */

        if (isset($this->_parameters['import_source']) && $this->_parameters['import_source'] != 'file') {
            $this->_initSourceType($this->_parameters['import_source']);
        }
        $isPriceGlobal = $this->_catalogData->isPriceGlobal();
        $productLimit = null;
        $productsQty = null;
        $entityLinkField = $this->getProductEntityLinkField();

        while ($nextBunch = $this->_dataSourceModel->getNextBunch()) {
            $entityRowsIn = $entityRowsUp = [];
            $attributes = [];
            $this->websitesCache = $this->categoriesCache = [];
            $mediaGallery = $uploadedImages = [];
            $tierPrices = [];
            $previousType = $prevAttributeSet = null;
            $existingImages = $this->getExistingImages($nextBunch);

            if ($this->_sourceType) {
                $nextBunch = $this->prepareImagesFromSource($nextBunch);
            }

            foreach ($nextBunch as $rowNum => $rowData) {
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }
                $rowScope = $this->getRowScope($rowData);
                $rowSku = $rowData[static::COL_SKU];
                $storeIds = $this->storeManager->getStore()->getId();
                $storeId = !empty($rowData[self::COL_STORE])
                    ? $this->getStoreIdByCode($rowData[self::COL_STORE])
                    : Store::DEFAULT_STORE_ID;
                $urlKey = isset($rowData[static::URL_KEY])
                    ? $this->productUrl->formatUrlKey($rowData[static::URL_KEY])
                    : $this->productUrl->formatUrlKey($rowData[static::COL_NAME]);
                $isDuplicate = $this->isDuplicateUrlKey($urlKey, $rowSku, $storeId);
                if ($isDuplicate || $this->urlKeyManager->isUrlKeyExist($rowSku, $urlKey)) {
                    $urlKey = $this->productUrl->formatUrlKey(
                        $rowData[static::COL_NAME] . '-' . $rowData[static::COL_SKU]
                    );
                }
                $rowData[static::URL_KEY] = $urlKey;
                $this->urlKeyManager->addUrlKeys($rowSku, $urlKey);
                $this->urlKeys = [];
                if (!$rowSku) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                } elseif (self::SCOPE_STORE == $rowScope) {
                    // set necessary data from SCOPE_DEFAULT row
                    $rowData[static::COL_TYPE] = $this->skuProcessor->getNewSku($rowSku)['type_id'];
                    $rowData['attribute_set_id'] = $this->skuProcessor->getNewSku($rowSku)['attr_set_id'];
                    $rowData[static::COL_ATTR_SET] = $this->skuProcessor->getNewSku($rowSku)['attr_set_code'];
                }
                $this->sharedCatalogData['shared_catalog'][] = $rowData['shared_catalog'];
                $this->sharedCatalogData['company_ids'][] = $rowData['company_id'];
                $this->sharedCatalogData['product_skus'][] = $rowSku;


                // Entity phase
                if (!isset($this->_oldSku[$rowSku])) {
                    // new row
                    if (!$productLimit || $productsQty < $productLimit) {
                        if (isset($rowData['has_options'])) {
                            $hasOptions = $rowData['has_options'];
                        } else {
                            $hasOptions = 0;
                        }
                        $entityRowsIn[$rowSku] = [
                            'attribute_set_id' => $this->skuProcessor->getNewSku($rowSku)['attr_set_id'],
                            'type_id' => $this->skuProcessor->getNewSku($rowSku)['type_id'],
                            'sku' => $rowSku,
                            'has_options' =>  $hasOptions,
                            'created_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
                            'updated_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
                        ];
                        $productsQty++;
                    } else {
                        $rowSku = null;
                        // sign for child rows to be skipped
                        $this->getErrorAggregator()->addRowToSkip($rowNum);
                        continue;
                    }
                } else {
                    // existing row
                    $entityRowsUp[] = [
                        'updated_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
                        'attribute_set_id' => $this->skuProcessor->getNewSku($rowSku)['attr_set_id'],
                        $entityLinkField => $this->_oldSku[strtolower($rowSku)][$entityLinkField]
                    ];
                }


                // Categories phase
                if (!array_key_exists($rowSku, $this->categoriesCache)) {
                    $this->categoriesCache[$rowSku] = [];
                }
                $rowData['rowNum'] = $rowNum;
                $categoryIds = $this->processRowCategories($rowData);
                $compIds = [];
                foreach ($categoryIds as $id) {
                    $this->categoriesCache[$rowSku][$id] = true;
                    $compIds[] = $id;
                }
                // create global array to hold shared data information
                $this->sharedCatalogData['category_ids'][] = $compIds;

                unset($rowData['rowNum']);

                if (!array_key_exists($rowSku, $this->websitesCache)) {
                    $this->websitesCache[$rowSku] = [];
                }
                // Product-to-Website phase
                if (!empty($rowData[static::COL_PRODUCT_WEBSITES])) {
                    $websiteCodes = explode($this->getMultipleValueSeparator(), $rowData[static::COL_PRODUCT_WEBSITES]);
                    foreach ($websiteCodes as $websiteCode) {
                        $websiteId = $this->storeResolver->getWebsiteCodeToId($websiteCode);
                        $this->websitesCache[$rowSku][$websiteId] = true;
                    }
                }

                // Tier prices phase
                if (!empty($rowData['_tier_price_website'])) {
                    $tierPrices[$rowSku][] = [
                        'all_groups' => $rowData['_tier_price_customer_group'] == static::VALUE_ALL,
                        'customer_group_id' => $rowData['_tier_price_customer_group'] ==
                        static::VALUE_ALL ? 0 : $rowData['_tier_price_customer_group'],
                        'qty' => $rowData['_tier_price_qty'],
                        'value' => $rowData['_tier_price_price'],
                        'website_id' => static::VALUE_ALL == $rowData['_tier_price_website'] ||
                        $isPriceGlobal ? 0 : $this->storeResolver->getWebsiteCodeToId($rowData['_tier_price_website']),
                    ];
                }

                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }

                // Media gallery phase
                $disabledImages = [];
                [$rowImages, $rowLabels] = $this->getImagesFromRow($rowData);
                if (isset($rowData['_media_is_disabled'])) {
                    $disabledImages = array_flip(
                        explode($this->getMultipleValueSeparator(), $rowData['_media_is_disabled'])
                    );
                }
                $rowData[static::COL_MEDIA_IMAGE] = [];
                [$rowImages, $rowData] = $this->clearNoSelectionImages($rowImages, $rowData);
                $position = 0;
                foreach ($rowImages as $column => $columnImages) {
                    foreach ($columnImages as $position => $columnImage) {
                        if (isset($columnImage)) {
                            $productTitle = substr(preg_replace("/[\W\s\/\.\-]/", "_", $rowData['name']), 0, 31);
                            $productTitle = strtolower($productTitle);
                            $imageName = $productTitle.$rowData[static::COL_SKU].'_'.$column;
                            $columnImage = $this->getImageUrl($columnImage, $imageName);
                        }

                        if (isset($uploadedImages[$columnImage])) {
                            $uploadedFile = $uploadedImages[$columnImage];
                        } else {
                            $uploadedFile = $this->uploadMediaFiles($columnImage, true);
                            $uploadedFile = $uploadedFile ?: $this->getSystemFile($columnImage);
                            if ($uploadedFile) {
                                $uploadedImages[$columnImage] = $uploadedFile;

                            } else {
                                $this->addRowError(
                                    ValidatorInterface::ERROR_MEDIA_URL_NOT_ACCESSIBLE,
                                    $rowNum,
                                    null,
                                    null,
                                    ProcessingError::ERROR_LEVEL_NOT_CRITICAL
                                );
                            }
                        }

                        if ($uploadedFile && $column !== static::COL_MEDIA_IMAGE) {
                            $rowData[$column] = $uploadedFile;
                        }

                        $imageNotAssigned = !isset($existingImages[$rowSku][$uploadedFile]);

                        if ($uploadedFile && $imageNotAssigned) {
                            if ($column == static::COL_MEDIA_IMAGE) {
                                $rowData[$column][] = $uploadedFile;
                            }
                            $mediaGallery[$storeId][$rowSku][] = [
                                'attribute_id' => $this->getMediaGalleryAttributeId(),
                                'label' => isset($rowLabels[$column][$position]) ? $rowLabels[$column][$position] : '',
                                'position' => $position+1,
                                'disabled' => isset($disabledImages[$columnImage]) ? '1' : '0',
                                'value' => $uploadedFile,
                            ];
                            $existingImages[$rowSku][$uploadedFile] = true;
                        }
                    }


                }

                // 6. Attributes phase
                $rowStore = (self::SCOPE_STORE == $rowScope)
                    ? $this->storeResolver->getStoreCodeToId($rowData[self::COL_STORE])
                    : 0;
                $productType = isset($rowData[static::COL_TYPE]) ? $rowData[static::COL_TYPE] : null;
                if (!is_null($productType)) {
                    $previousType = $productType;
                }
                if (isset($rowData[static::COL_ATTR_SET])) {
                    $prevAttributeSet = $rowData[static::COL_ATTR_SET];
                }
                if (self::SCOPE_NULL == $rowScope) {
                    // for multiselect attributes only
                    if (!is_null($prevAttributeSet)) {
                        $rowData[static::COL_ATTR_SET] = $prevAttributeSet;
                    }
                    if (is_null($productType) && !is_null($previousType)) {
                        $productType = $previousType;
                    }
                    if (is_null($productType)) {
                        continue;
                    }
                }

                $productTypeModel = $this->_productTypeModels[$productType];
                if (!empty($rowData['tax_class_name'])) {
                    $rowData['tax_class_id'] =
                        $this->taxClassProcessor->upsertTaxClass($rowData['tax_class_name'], $productTypeModel);
                }

                if ($this->getBehavior() == Import::BEHAVIOR_APPEND ||
                    empty($rowData[static::COL_SKU])
                ) {
                    $rowData = $productTypeModel->clearEmptyData($rowData);
                }

                $createValuesAllowed = (bool) $this->scopeConfig->getValue(
                    \Fedex\Import\Model\Import::CREATE_ATTRIBUTES_CONF_PATH,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );

                if ($createValuesAllowed) {
                    $rowData = $this->createAttributeValues(
                        $productTypeModel,
                        $rowData
                    );
                }

                $rowData = $productTypeModel->prepareAttributesWithDefaultValueForSave(
                    $rowData,
                    !isset($this->_oldSku[$rowSku])
                );
                $product = $this->_proxyProdFactory->create(['data' => $rowData]);

                foreach ($rowData as $attrCode => $attrValue) {
                    $attribute = $this->retrieveAttributeByCode($attrCode);

                    if ('multiselect' != $attribute->getFrontendInput() && self::SCOPE_NULL == $rowScope) {
                        // skip attribute processing for SCOPE_NULL rows
                        continue;
                    }
                    $attrId = $attribute->getId();
                    $backModel = $attribute->getBackendModel();
                    $attrTable = $attribute->getBackend()->getTable();
                    $storeIds = [0];

                    if ('datetime' == $attribute->getBackendType() && strtotime($attrValue)) {
                        $attrValue = $this->dateTime->gmDate(
                            'Y-m-d H:i:s',
                            $this->_localeDate->date($attrValue)->getTimestamp()
                        );
                    } elseif ($backModel) {
                        $attribute->getBackend()->beforeSave($product);
                        $attrValue = $product->getData($attribute->getAttributeCode());
                    }
                    if (self::SCOPE_STORE == $rowScope) {
                        if (self::SCOPE_WEBSITE == $attribute->getIsGlobal()) {
                            // check website defaults already set
                            if (!isset($attributes[$attrTable][$rowSku][$attrId][$rowStore])) {
                                $storeIds = $this->storeResolver->getStoreIdToWebsiteStoreIds($rowStore);
                            }
                        } elseif (self::SCOPE_STORE == $attribute->getIsGlobal()) {
                            $storeIds = [$rowStore];
                        }
                        if (!isset($this->_oldSku[$rowSku])) {
                            $storeIds[] = 0;
                        }
                    }
                    foreach ($storeIds as $storeId) {
                        if (!isset($attributes[$attrTable][$rowSku][$attrId][$storeId])) {
                            $attributes[$attrTable][$rowSku][$attrId][$storeId] = $attrValue;
                        }
                    }
                    // restore 'backend_model' to avoid 'default' setting
                    $attribute->setBackendModel($backModel);
                }
            }

            if (method_exists($this, '_saveProductEntity')) {
                $this->_saveProductEntity(
                    $entityRowsIn,
                    $entityRowsUp
                );
            } else {
                $this->saveProductEntity(
                    $entityRowsIn,
                    $entityRowsUp
                );
            }
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Imported: ' . count($entityRowsIn) . ' rows');
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Updated: ' . count($entityRowsUp) . ' rows');

            foreach ($nextBunch as $rowNum => $rowData) {
                if ($this->getErrorAggregator()->isRowInvalid($rowNum)) {
                    unset($nextBunch[$rowNum]);
                }
            }

            $this->_saveProductWebsites(
                $this->websitesCache
            )->_saveProductCategories(
                $this->categoriesCache
            )->_saveProductTierPrices(
                $tierPrices
            )->_saveMediaGallery(
                $mediaGallery
            )->_saveProductAttributes(
                $attributes
            );
            $this->_eventManager->dispatch(
                'catalog_product_import_bunch_save_after',
                ['adapter' => $this, 'bunch' => $nextBunch]
            );
        }

        return $this;
    }

    /**
     * Import images via initialized source type
     *
     * @param $bunch
     * @return mixed
     */
    protected function prepareImagesFromSource($bunch)
    {
        foreach ($bunch as &$rowData) {
            $rowData = $this->customFieldsMapping($rowData);
            foreach ($this->_imagesArrayKeys as $image) {
                if (empty($rowData[$image])) {
                    continue;
                }
                $dispersionPath =
                    \Magento\Framework\File\Uploader::getDispretionPath($rowData[$image]);
                $importImages = explode($this->getMultipleValueSeparator(), $rowData[$image]);
                foreach ($importImages as $importImage) {
                    $imageSting = mb_strtolower(
                        $dispersionPath . '/' . preg_replace('/[^a-z0-9\._-]+/i', '', $importImage)
                    );

                    if ($this->_sourceType) {
                        $this->_sourceType->importImage($importImage, $imageSting);
                    }
                    $rowData[$image] = $this->_sourceType->getCode() . $imageSting;
                }
            }
        }
        return $bunch;
    }

    /**
     * Retrieving images from all columns and rows
     *
     * @param $bunch
     * @return array
     */
    protected function getBunchImages($bunch)
    {
        $allImagesFromBunch = [];
        foreach ($bunch as $rowData) {
            $rowData = $this->customFieldsMapping($rowData);
            foreach ($this->_imagesArrayKeys as $image) {
                if (empty($rowData[$image])) {
                    continue;
                }
                $dispersionPath =
                    \Magento\Framework\File\Uploader::getDispretionPath($rowData[$image]);
                $importImages = explode($this->getMultipleValueSeparator(), $rowData[$image]);
                foreach ($importImages as $importImage) {
                    $imageSting = mb_strtolower(
                        $dispersionPath . '/' . preg_replace('/[^a-z0-9\._-]+/i', '', $importImage)
                    );
                    /**
                     * TODO: check source type 'file'. Compare code with default Magento\CatalogImportExport\Model\Import\Product
                     */
                    if (isset($this->_parameters['import_source']) && $this->_parameters['import_source'] != 'file') {
                        $allImagesFromBunch[$this->_sourceType->getCode() . $imageSting] = $imageSting;
                    } else {
                        $allImagesFromBunch[$importImage] = $imageSting;
                    }
                }
            }
        }
        return $allImagesFromBunch;
    }

    /**
     * Convert attribute string syntax to array.
     *
     * @param $columnData
     *
     * @return array
     * @throws \Exception
     */
    protected function prepareAttributeData($columnData)
    {
        $result = [];
        foreach ($columnData as $field) {
            $field = explode(':', $field);
            if (isset($field[1])) {
                if (preg_match('/^(frontend_label_)[0-9]+/', $field[0])) {
                    $result['frontend_label'][intval(substr($field[0], -1))] = $field[1];
                } else {
                    $result[$field[0]] = $field[1];
                }
            }
        }

        if (!empty($result)) {
            $attributeCode = isset($result['attribute_code']) ? $result['attribute_code']:null;
            $frontendLabel = $result['frontend_label'][0];
            $attributeCode = $attributeCode ?: $this->generateAttributeCode($frontendLabel);
            $result['attribute_code'] = $attributeCode;

            $entityTypeId = $this->eavEntityFactory->create()->setType(
                \Magento\Catalog\Model\Product::ENTITY
            )->getTypeId();
            $result['entity_type_id'] = $entityTypeId;
            $result['is_user_defined'] = 1;
        }

        return $result;
    }

    /**
     * Generate code from label
     *
     * @param string $label
     * @return string
     */
    protected function generateAttributeCode($label)
    {
        $code = substr(
            preg_replace(
                '/[^a-z_0-9]/',
                '_',
                $this->productUrl->formatUrlKey($label)
            ),
            0,
            30
        );
        $validatorAttrCode = new \Laminas\Validator\Regex(['pattern' => '/^[a-z][a-z_0-9]{0,29}[a-z0-9]$/']);
        if (!$validatorAttrCode->isValid($code)) {
            $code = 'attr_' . ($code ?: substr(md5(time()), 0, 8));
        }
        return $code;
    }

    /**
     * Custom fields mapping for changed purposes of fields and field names.
     *
     * @param array $rowData
     *
     * @return array
     */
    private function customFieldsMapping($rowData)
    {
        foreach ($this->_fieldsMap as $systemFieldName => $fileFieldName) {
            if (array_key_exists($fileFieldName, $rowData)) {
                $rowData[$systemFieldName] = $rowData[$fileFieldName];
            }
        }

        $rowData = $this->_parseAdditionalAttributes($rowData);

        $rowData = $this->setStockUseConfigFieldsValues($rowData);
        if (array_key_exists('status', $rowData)
            && $rowData['status'] != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
        ) {
            if ($rowData['status'] == 'yes') {
                $rowData['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
            } elseif (!empty($rowData['status']) || $this->getRowScope($rowData) == self::SCOPE_DEFAULT) {
                $rowData['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
            }
        }
        return $rowData;
    }

    /**
     * Parse attributes names and values string to array.
     *
     * @param array $rowData
     *
     * @return array
     */
    private function _parseAdditionalAttributes($rowData)
    {
        if (empty($rowData['additional_attributes'])) {
            return $rowData;
        }

        $valuePairs = explode(
            $this->getMultipleValueSeparator(),
            $rowData['additional_attributes']
        );
        foreach ($valuePairs as $valuePair) {
            $separatorPosition = strpos($valuePair, self::PAIR_NAME_VALUE_SEPARATOR);
            if ($separatorPosition !== false) {
                $key = substr($valuePair, 0, $separatorPosition);
                $value = substr(
                    $valuePair,
                    $separatorPosition + strlen(self::PAIR_NAME_VALUE_SEPARATOR)
                );
                $rowData[$key] = $value === false ? '' : $value;
            }
        }
        return $rowData;
    }

    /**
     * Set values in use_config_ fields.
     *
     * @param array $rowData
     *
     * @return array
     */
    private function setStockUseConfigFieldsValues($rowData)
    {
        $useConfigFields = [];
        foreach ($rowData as $key => $value) {
            if (isset($this->defaultStockData[$key]) && isset($this->defaultStockData[self::INVENTORY_USE_CONFIG_PREFIX . $key]) && !empty($value)) {
                $useConfigFields[self::INVENTORY_USE_CONFIG_PREFIX . $key] = ($value == self::INVENTORY_USE_CONFIG) ? 1 : 0;
            }
        }
        $rowData = array_merge($rowData, $useConfigFields);
        return $rowData;
    }

    /**
     * Validate data
     *
     * @return ProcessingErrorAggregatorInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateData()
    {
        if (!$this->_dataValidated) {
            $this->getErrorAggregator()->clear();
            // do all permanent columns exist?
            $absentColumns = array_diff($this->replaceFields($this->_permanentAttributes), $this->getSource()->getColNames());
            $this->addErrors(self::ERROR_CODE_COLUMN_NOT_FOUND, $absentColumns);

            // check attribute columns names validity
            $columnNumber = 0;
            $emptyHeaderColumns = [];
            $invalidColumns = [];
            $invalidAttributes = [];
            foreach ($this->getSource()->getColNames() as $columnName) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Checked column '.$columnNumber);
                $columnNumber++;
                if (!$this->isAttributeParticular($columnName)) {

                    /**
                     * Check syntax when attribute should be created on the fly
                     */
                    $createValuesAllowed = (bool) $this->scopeConfig->getValue(
                        \Fedex\Import\Model\Import::CREATE_ATTRIBUTES_CONF_PATH,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    );
                    $isNewAttribute = false;

                    if ($createValuesAllowed && preg_match('/^(attribute\|).+/', $columnName)) {
                        $isNewAttribute = true;
                        $columnData = explode('|', $columnName);
                        $columnData = $this->prepareAttributeData($columnData);
                        $attribute = $this->attributeFactory->create();
                        $attribute->loadByCode(\Magento\Catalog\Model\Product::ENTITY, $columnData['attribute_code']);
                        if (!$attribute->getId()) {
                            $attribute->setBackendType($attribute->getBackendTypeByInput($columnData['frontend_input']));
                            $defaultValueField = $attribute->getDefaultValueByInput($columnData['frontend_input']);
                            if (!$defaultValueField && isset($columnData['default_value'])) {
                                unset($columnData['default_value']);
                            }
                            $columnData['source_model'] = $this->productHelper->getAttributeSourceModelByInputType(
                                $columnData['frontend_input']
                            );
                            $columnData['backend_model'] = $this->productHelper->getAttributeBackendModelByInputType(
                                $columnData['frontend_input']
                            );

                            $attribute->addData($columnData);
                            try {
                                $attribute->save();
                            } catch (\Exception $e) {
                                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                                $invalidColumns[] = $columnName;
                            }

                            $attributeSetCodes = explode(',', $columnData[self::ATTRIBUTE_SET_COLUMN]);
                            foreach ($attributeSetCodes as $attributeSetCode) {
                                if (isset($this->_attrSetNameToId[$attributeSetCode])) {
                                    $attributeSetId = $this->_attrSetNameToId[$attributeSetCode];
                                    $attributeGroupCode = isset($columnData[self::ATTRIBUTE_SET_GROUP]) ? $columnData[self::ATTRIBUTE_SET_GROUP] : 'product-details';
                                    if (!isset($this->_attributeSetGroupCache[$attributeSetId])) {
                                        $groupCollection = $this->groupCollectionFactory->create()->setAttributeSetFilter($attributeSetId)->load();
                                        foreach ($groupCollection as $group) {
                                            $this->_attributeSetGroupCache[$attributeSetId][$group->getAttributeGroupCode()] = $group->getAttributeGroupId();
                                        }
                                    }

                                    foreach ($this->_attributeSetGroupCache[$attributeSetId] as $groupCode => $groupId) {
                                        if ($groupCode == $attributeGroupCode) {
                                            $attribute->setAttributeSetId($attributeSetId);
                                            $attribute->setAttributeGroupId($groupId);
                                            try {
                                                $attribute->save();
                                            } catch (\Exception $e) {
                                                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                                            }
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (trim($columnName) == '') {
                        $emptyHeaderColumns[] = $columnNumber;
                    } elseif (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $columnName) && !$isNewAttribute) {
                        $invalidColumns[] = $columnName;
                    } elseif ($this->needColumnCheck && !in_array($columnName, $this->getValidColumnNames())) {
                        $invalidAttributes[] = $columnName;
                    }
                }
            }
            $this->addErrors(self::ERROR_CODE_INVALID_ATTRIBUTE, $invalidAttributes);
            $this->addErrors(self::ERROR_CODE_COLUMN_EMPTY_HEADER, $emptyHeaderColumns);
            $this->addErrors(self::ERROR_CODE_COLUMN_NAME_INVALID, $invalidColumns);

            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Finish checking columns');
            if (!$this->getErrorAggregator()->getErrorsCount()) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Start saving bunches');
                $this->mergeFieldsMap();
                $this->_saveValidatedBunches();
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Finish saving bunches');
                $this->_dataValidated = true;
            } else {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Errors count: ' . $this->getErrorAggregator()->getErrorsCount());
            }
        }
        return $this->getErrorAggregator();
    }

    /**
     * Add custom field mapping.
     * $this->_fieldsMap – system magento mapping. Merge it with custom admin mapping.
     *
     *
     */
    protected function mergeFieldsMap()
    {
        if (isset($this->_parameters['map'])) {
            $newAttributes = [];

            foreach ($this->_parameters['map'] as $field) {
                $attributeCode = $field['system'];
                if (is_numeric($field['system'])) {
                    $attribute = $this->getResource()->getAttribute((int)$field['system']);
                    $attributeCode = $attribute->getAttributeCode();
                }
                $newAttributes[$attributeCode] = $field['import'];
            }

            $this->_fieldsMap = array_merge($this->_fieldsMap, $newAttributes);
        }
    }

    protected function replaceFields($fields)
    {
        $newAttributes = [];

        if (isset($this->_parameters['map'])) {
            $mapAttributes = $newAttributes = [];

            foreach ($this->_parameters['map'] as $field) {
                $attributeCode = $field['system'];

                if (is_numeric($field['system'])) {
                    $attribute = $this->getResource()->getAttribute((int)$field['system']);
                    $attributeCode = $attribute->getAttributeCode();
                }

                $mapAttributes[$attributeCode] = $field['import'];
            }

            foreach ($fields as $field) {
                if (isset($field, $mapAttributes) && isset($mapAttributes[$field])) {
                    $newAttributes[] = $mapAttributes[$field];
                } else {
                    $newAttributes[] = $field;
                }
            }
        }

        return $newAttributes ? $newAttributes : $fields;
    }

    public function getSpecialAttributes()
    {
        return $this->_specialAttributes;
    }

    /**
     * @param $productTypeModel
     * @param $rowData
     *
     * @return mixed
     */
    public function createAttributeValues($productTypeModel, $rowData)
    {
        $options = [];
        $attributeSet = $rowData[\Magento\CatalogImportExport\Model\Import\Product::COL_ATTR_SET];
        foreach ($rowData as $attrCode => $attrValue) {
            /**
             * Add attribute to set & set's group
             */
            if (preg_match('/^(attribute\|).+/', $attrCode)) {
                $columnData = explode('|', $attrCode);
                $columnData = $this->prepareAttributeData($columnData);
                $rowData[$columnData['attribute_code']] = $rowData[$attrCode];
                unset($rowData[$attrCode]);
                $attrCode = $columnData['attribute_code'];
            }

            /**
             * Prepare new values
             */
            $attrParams = $productTypeModel->retrieveAttribute($attrCode, $attributeSet);

            if (!empty($attrParams)) {
                if (!$attrParams['is_static'] && isset($rowData[$attrCode]) && strlen($rowData[$attrCode])) {
                    switch ($attrParams['type']) {
                        case 'select':
                            if (!isset($attrParams['options'][strtolower($rowData[$attrCode])])) {
                                $options[$attrParams['id']][] = [
                                    'sort_order'    => count($attrParams['options']) + 1,
                                    'value'         => $rowData[$attrCode],
                                    'code'          => $attrCode
                                ];
                            }
                            break;
                        case 'multiselect':
                            foreach (explode(Product::PSEUDO_MULTI_LINE_SEPARATOR, $rowData[$attrCode]) as $value) {
                                if (!isset($attrParams['options'][strtolower($value)])) {
                                    $options[$attrParams['id']][] = [
                                        'sort_order'    => count($attrParams['options']) + 1,
                                        'value'         => $value,
                                        'code'          => $attrCode
                                    ];
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        /**
         * Create new values
         */
        if (!empty($options)) {
            foreach ($options as $attributeId => $optionsArray) {
                foreach ($optionsArray as $option) {
                    /**
                     * @see \Magento\Eav\Model\ResourceModel\Entity\Attribute::_updateAttributeOption()
                     */
                    $connection = $this->_connection;
                    $resource = $this->_resourceFactory->create();
                    $table = $resource->getTable('eav_attribute_option');
                    $data = ['attribute_id' => $attributeId, 'sort_order' => $option['sort_order']];
                    $connection->insert($table, $data);
                    $intOptionId = $connection->lastInsertId($table);
                    /**
                     * @see \Magento\Eav\Model\ResourceModel\Entity\Attribute::_updateAttributeOptionValues()
                     */
                    $table = $resource->getTable('eav_attribute_option_value');
                    $data = ['option_id' => $intOptionId, 'store_id' => 0, 'value' => $option['value']];
                    $connection->insert($table, $data);
                    $productTypeModel->addAttributeOption($option['code'], strtolower($option['value']), $intOptionId);
                }
            }
        }

        return $rowData;
    }

    /**
     * @param $urlKey
     * @param $sku
     * @param $storeId
     *
     * @return string
     */
    protected function isDuplicateUrlKey($urlKey, $sku, $storeId)
    {
        $result = false;
        $urlKeyHtml = $urlKey . $this->getProductUrlSuffix();
        $resource = $this->getResource();
        $select = $this->_connection->select()->from(
            ['url_rewrite' => $resource->getTable('url_rewrite')],
            ['request_path', 'store_id']
        )->joinLeft(
            ['cpe' => $resource->getTable('catalog_product_entity')],
            'cpe.entity_id = url_rewrite.entity_id'
        )->where("request_path='$urlKey' OR request_path='$urlKeyHtml'")
            ->where('store_id IN (?)', $storeId)
            ->where('cpe.sku not in (?)', $sku);
        $isDuplicate = $this->_connection->fetchAssoc(
            $select
        );
        if (!empty($isDuplicate)) {
            $result = true;
        }
        return $result;
    }

    /**
     * Get product entity link field
     *
     * @return string
     * @throws \Exception
     */
    private function getProductEntityLinkField()
    {
        if (!$this->productEntityLinkField) {
            $this->productEntityLinkField = $this->getMetadataPool()
                ->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
                ->getLinkField();
        }
        return $this->productEntityLinkField;
    }

    public function getImageUrl($columnImage, $imageName)
    {

        $ch = curl_init();
        $headers = ["Content-Type: image/jpeg"];
        curl_setopt($ch, CURLOPT_URL, trim($columnImage));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            // echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        $imgName = $imageName.'.jpg';
        $imgDir = $this->dir->getPath('var').'/import/images';
        if (!is_dir($imgDir)) {
            mkdir($imgDir, 0777, true);
        }
        $imageFile = $this->file->fileOpen($imgDir . '/' . $imgName, 'w');
        $this->file->fileWrite($imageFile, $result);
        $this->file->fileClose($imageFile);

        return $imgName;
    }

    /**
     * @param array $rowData
     * @param $rowNum
     * @return bool
     */
    public function validateRow(array $rowData, $rowNum)
    {

        if (isset($this->_validatedRows[$rowNum])) {
            // check that row is already validated
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }
        $this->_validatedRows[$rowNum] = true;

        $rowScope = $this->getRowScope($rowData);
        $sku = $rowData[static::COL_SKU];

        // BEHAVIOR_DELETE and BEHAVIOR_REPLACE use specific validation logic
        if (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
            if (self::SCOPE_DEFAULT == $rowScope && !$this->isSkuExist($sku)) {
                $this->skipRow($rowNum, ValidatorInterface::ERROR_SKU_NOT_FOUND_FOR_DELETE);
                return false;
            }
        }
        if (Import::BEHAVIOR_DELETE == $this->getBehavior()) {
            if (self::SCOPE_DEFAULT == $rowScope && !$this->isSkuExist($sku)) {
                $this->skipRow($rowNum, ValidatorInterface::ERROR_SKU_NOT_FOUND_FOR_DELETE);
                return false;
            }
            return true;
        }

        // if product doesn't exist, need to throw critical error else all errors should be not critical.
        $errorLevel = $this->getValidationErrorLevel($sku);

        if (!$this->validator->isValid($rowData)) {
            foreach ($this->validator->getMessages() as $message) {
                $this->skipRow($rowNum, $message, $errorLevel, $this->validator->getInvalidAttribute());
            }
        }

        $sharedCatalog = $rowData['shared_catalog'] ?? '';
        $companyId = (int) $rowData['company_id'] ?? 0;
        if (!$companyId) {
            $this->skipRow($rowNum, self::COMPANY_REQUIRE, $errorLevel);
        }
        if (empty($sharedCatalog)) {
             $this->skipRow($rowNum, self::SHARED_CATALOG_REQUIRE, $errorLevel);
        }

        if (null === $sku) {
            $this->skipRow($rowNum, ValidatorInterface::ERROR_SKU_IS_EMPTY, $errorLevel);
        } elseif (false === $sku) {
            $this->skipRow($rowNum, ValidatorInterface::ERROR_ROW_IS_ORPHAN, $errorLevel);
        } elseif (self::SCOPE_STORE == $rowScope
            && !$this->storeResolver->getStoreCodeToId($rowData[self::COL_STORE])
        ) {
            $this->skipRow($rowNum, ValidatorInterface::ERROR_INVALID_STORE, $errorLevel);
        }

        // SKU is specified, row is SCOPE_DEFAULT, new product block begins
        $this->_processedEntitiesCount++;

        if ($this->isSkuExist($sku) && Import::BEHAVIOR_REPLACE !== $this->getBehavior()) {
            // can we get all necessary data from existent DB product?
            // check for supported type of existing product
            if (isset($this->_productTypeModels[$this->getExistingSku($sku)['type_id']])) {
                $this->skuProcessor->addNewSku(
                    $sku,
                    $this->prepareNewSkuData($sku)
                );
            } else {
                $this->skipRow($rowNum, ValidatorInterface::ERROR_TYPE_UNSUPPORTED, $errorLevel);
            }
        } else {
            // validate new product type and attribute set
            if (!isset($rowData[static::COL_TYPE], $this->_productTypeModels[$rowData[static::COL_TYPE]])) {
                $this->skipRow($rowNum, ValidatorInterface::ERROR_INVALID_TYPE, $errorLevel);
            } elseif (!isset($rowData[static::COL_ATTR_SET], $this->_attrSetNameToId[$rowData[static::COL_ATTR_SET]])
            ) {
                $this->skipRow($rowNum, ValidatorInterface::ERROR_INVALID_ATTR_SET, $errorLevel);
            } elseif ($this->skuProcessor->getNewSku($sku) === null) {
                $this->skuProcessor->addNewSku(
                    $sku,
                    [
                        'row_id' => null,
                        'entity_id' => null,
                        'type_id' => $rowData[static::COL_TYPE],
                        'attr_set_id' => $this->_attrSetNameToId[$rowData[static::COL_ATTR_SET]],
                        'attr_set_code' => $rowData[static::COL_ATTR_SET],
                    ]
                );
            }
        }

        if (!$this->getErrorAggregator()->isRowInvalid($rowNum)) {
            $newSku = $this->skuProcessor->getNewSku($sku);
            // set attribute set code into row data for followed attribute validation in type model
            $rowData[static::COL_ATTR_SET] = $newSku['attr_set_code'];

            // isRowValid can add error to general errors pull if row is invalid
            $productTypeValidator = $this->_productTypeModels[$newSku['type_id']];
            $productTypeValidator->isRowValid(
                $rowData,
                $rowNum,
                !($this->isSkuExist($sku) && Import::BEHAVIOR_REPLACE !== $this->getBehavior())
            );
        }
        // validate custom options
        $this->getOptionEntity()->validateRow($rowData, $rowNum);

        if ($this->isNeedToValidateUrlKey($rowData)) {
            $urlKey = strtolower($this->getUrlKey($rowData));
            $storeCodes = empty($rowData[static::COL_STORE_VIEW_CODE])
                ? array_flip($this->storeResolver->getStoreCodeToId())
                : explode($this->getMultipleValueSeparator(), $rowData[static::COL_STORE_VIEW_CODE]);
            foreach ($storeCodes as $storeCode) {
                $storeId = $this->storeResolver->getStoreCodeToId($storeCode);
                $productUrlSuffix = $this->getProductUrlSuffix($storeId);
                $urlPath = $urlKey . $productUrlSuffix;
                if (empty($this->urlKeys[$storeId][$urlPath])
                    || ($this->urlKeys[$storeId][$urlPath] == $sku)
                ) {
                    $this->urlKeys[$storeId][$urlPath] = $sku;
                    $this->rowNumbers[$storeId][$urlPath] = $rowNum;
                } else {
                    $message = sprintf(
                        $this->retrieveMessageTemplate(ValidatorInterface::ERROR_DUPLICATE_URL_KEY),
                        $urlKey,
                        $this->urlKeys[$storeId][$urlPath]
                    );
                    $this->addRowError(
                        ValidatorInterface::ERROR_DUPLICATE_URL_KEY,
                        $rowNum,
                        $urlKey,
                        $message,
                        $errorLevel
                    )
                        ->getErrorAggregator()
                        ->addRowToSkip($rowNum);
                }
            }
        }

        if (!empty($rowData['new_from_date']) && !empty($rowData['new_to_date'])
        ) {
            $newFromTimestamp = strtotime($this->dateTime->formatDate($rowData['new_from_date'], false));
            $newToTimestamp = strtotime($this->dateTime->formatDate($rowData['new_to_date'], false));
            if ($newFromTimestamp > $newToTimestamp) {
                $this->skipRow(
                    $rowNum,
                    'invalidNewToDateValue',
                    $errorLevel,
                    $rowData['new_to_date']
                );
            }
        }

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }
    /**
     * Returns errorLevel for validation
     *
     * @param string $sku
     * @return string
     */
    private function getValidationErrorLevel($sku): string
    {
        return (!$this->isSkuExist($sku) && Import::BEHAVIOR_REPLACE !== $this->getBehavior())
            ? ProcessingError::ERROR_LEVEL_CRITICAL
            : ProcessingError::ERROR_LEVEL_NOT_CRITICAL;
    }

    /**
     * Check if product exists for specified SKU
     *
     * @param string $sku
     * @return bool
     */
    private function isSkuExist($sku)
    {
        $sku = strtolower($sku);
        return isset($this->_oldSku[$sku]);
    }

    /**
     * Get existing product data for specified SKU
     *
     * @param string $sku
     * @return array
     */
    private function getExistingSku($sku)
    {
        return $this->_oldSku[strtolower($sku)];
    }
    /**
     * Check if need to validate url key.
     *
     * @param array $rowData
     * @return bool
     */
    private function isNeedToValidateUrlKey($rowData)
    {
        if (!empty($rowData[static::COL_SKU]) && empty($rowData[static::URL_KEY])
            && $this->getBehavior() === Import::BEHAVIOR_APPEND
            && $this->isSkuExist($rowData[static::COL_SKU])) {
            return false;
        }

        return (!empty($rowData[static::URL_KEY]) || !empty($rowData[static::COL_NAME]))
            && (empty($rowData[static::COL_VISIBILITY])
                || $rowData[static::COL_VISIBILITY]
                !== (string)Visibility::getOptionArray()[Visibility::VISIBILITY_NOT_VISIBLE]);
    }


    /**
     * Add row as skipped
     *
     * @param int $rowNum
     * @param string $errorCode Error code or simply column name
     * @param string $errorLevel error level
     * @param string|null $colName optional column name
     * @return $this
     */
    private function skipRow(
        $rowNum,
        string $errorCode,
        string $errorLevel = ProcessingError::ERROR_LEVEL_NOT_CRITICAL,
        $colName = null
    ): self {
        $this->addRowError($errorCode, $rowNum, $colName, null, $errorLevel);
        $this->getErrorAggregator()
            ->addRowToSkip($rowNum);
        return $this;
    }

    /**
     * Prepare new SKU data
     *
     * @param string $sku
     * @return array
     */
    private function prepareNewSkuData($sku)
    {
        $data = [];
        foreach ($this->getExistingSku($sku) as $key => $value) {
            $data[$key] = $value;
        }

        $data['attr_set_code'] = $this->_attrSetIdToName[$this->getExistingSku($sku)['attr_set_id']];

        return $data;
    }


    /**
     * Whether a url key is needed to be change.
     *
     * @param array $rowData
     * @return bool
     */
    private function isNeedToChangeUrlKey(array $rowData): bool
    {
        $urlKey = $this->getUrlKey($rowData);
        $productExists = $this->isSkuExist($rowData[static::COL_SKU]);
        $markedToEraseUrlKey = isset($rowData[static::URL_KEY]);
        // The product isn't new and the url key index wasn't marked for change.
        if (!$urlKey && $productExists && !$markedToEraseUrlKey) {
            // Seems there is no need to change the url key
            return false;
        }

        return true;
    }


    /**
     * Retrieve url key from provided row data.
     *
     * @param array $rowData
     * @return string
     *
     * @since 100.0.3
     */
    protected function getUrlKey($rowData)
    {
        if (!empty($rowData[static::URL_KEY])) {
            $urlKey = (string) $rowData[static::URL_KEY];
            return trim(strtolower($urlKey));
        }

        if (!empty($rowData[static::COL_NAME])
            && (array_key_exists(static::URL_KEY, $rowData) || !$this->isSkuExist($rowData[static::COL_SKU]))) {
            return $this->productUrl->formatUrlKey($rowData[static::COL_NAME]);
        }

        return '';
    }

    /**
     * Clears entries from Image Set and Row Data marked as no_selection
     *
     * @param array $rowImages
     * @param array $rowData
     * @return array
     */
    private function clearNoSelectionImages($rowImages, $rowData)
    {
        foreach ($rowImages as $column => $columnImages) {
            foreach ($columnImages as $key => $image) {
                if ($image == 'no_selection') {
                    unset($rowImages[$column][$key]);
                    unset($rowData[$column]);
                }
            }
        }

        return [$rowImages, $rowData];
    }

    /**
     * Try to find file by it's path.
     *
     * @param string $fileName
     * @return string
     */
    private function getSystemFile($fileName)
    {
        $filePath = 'catalog' . DIRECTORY_SEPARATOR . 'product' . DIRECTORY_SEPARATOR . $fileName;
        /** @var \Magento\Framework\Filesystem\Directory\ReadInterface $read */
        $read = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

        return $read->isExist($filePath) && $read->isReadable($filePath) ? $fileName : '';
    }

    /**
     * Retrieve id of media gallery attribute.
     *
     * @return int
     */
    public function getMediaGalleryAttributeId()
    {
        if (!$this->_mediaGalleryAttributeId) {
            /** @var $resource \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModel */
            $resource = $this->_resourceFactory->create();
            $this->_mediaGalleryAttributeId = $resource->getAttribute(self::MEDIA_GALLERY_ATTRIBUTE_CODE)->getId();
        }
        return $this->_mediaGalleryAttributeId;
    }


    /**
     * Get store id by code.
     *
     * @param string $storeCode
     * @return array|int|null|string
     */
    public function getStoreIdByCode($storeCode)
    {
        if (empty($storeCode)) {
            return self::SCOPE_DEFAULT;
        }
        return $this->storeResolver->getStoreCodeToId($storeCode);
    }

    public function saveSharedCatalogData()
    {

        $sharedCatalogDatas = $this->sharedCatalogData;
        $sharedCatalogAssignCat = [];

        foreach ($sharedCatalogDatas['shared_catalog'] as $key => $sharedCatalogName) {
            $sharedCatIdExist = $this->fedexHelper->getByName($sharedCatalogName);
            $cateIdsList = [];
            $sharedCatProdSku = $sharedCatalogDatas['product_skus'][$key];
            $sharedCatCompId = $sharedCatalogDatas['company_ids'][$key];
            if ($sharedCatalogDatas['category_ids'][$key]) {

                foreach ($sharedCatalogDatas['category_ids'][$key] as $Catekeys => $catId) {
                    $cateIdsList[] = ['id'=>$catId];
                }
                $sharedCatalogAssignCat['categories'] = $cateIdsList;
            }
            if ($sharedCatIdExist) {
                $this->sharedCatalogAssignCategories($sharedCatalogAssignCat, $sharedCatIdExist);
                $this->sharedCatalogAssignProduct($sharedCatProdSku, $sharedCatIdExist);
                $this->sharedCatalogAssignCompany($sharedCatCompId, $sharedCatIdExist);
            } else {
                $sharedCatId = $this->createSharedCatalog($sharedCatalogName);
                $this->sharedCatalogAssignCategories($sharedCatalogAssignCat, $sharedCatId);
                $this->sharedCatalogAssignProduct($sharedCatProdSku, $sharedCatId);
                $this->sharedCatalogAssignCompany($sharedCatCompId, $sharedCatId);
            }
            $sharedCatalogAssignCat['categories'] = '';
        }
    }

    public function createSharedCatalog($sharedCatalogName)
    {
        $data_string= trim($this->fedexHelper->getAdminToken());
        $setupURL= $this->fedexHelper->getBaseUrl()."rest/V1/sharedCatalog/";
        $headers=["Content-Type: application/json", "Authorization: Bearer ".$data_string];
        $sharedCatalogDatas = [];
        $sharedCatalogDatas['sharedCatalog'] = ["name"=> $sharedCatalogName,"type"=> 0,"store_id"=> 0,"tax_class_id"=> 3];
        $postSharedCatalogData = json_encode($sharedCatalogDatas);

        $ch = curl_init($setupURL);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postSharedCatalogData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);

        if ($result === false) {
            return false;
        } else {

            $response = curl_getinfo($ch);
            curl_close($ch);

            if ($response['http_code']==200) {
                //$array_data = json_decode($result, true);
                return $result;
            }
        }
    }


    public function sharedCatalogAssignCategories($sharedCatalogCategories, $sharedCatId)
    {
        $data_string= trim($this->fedexHelper->getAdminToken());
        $setupURL= $this->fedexHelper->getBaseUrl()."rest/V1/sharedCatalog/".$sharedCatId."/assignCategories";
        $headers=["Content-Type: application/json", "Authorization: Bearer ".$data_string];
        $postSharedCatalogAssignData = json_encode($sharedCatalogCategories);

        $ch = curl_init($setupURL);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postSharedCatalogAssignData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);

        if ($result === false) {
            return false;
        } else {

            $response = curl_getinfo($ch);
            curl_close($ch);
            if ($response['http_code']==200) {
                return $result;
            }
        }
    }

    public function sharedCatalogAssignProduct($sharedCatalogProduct, $sharedCatId)
    {
        $data_string= trim($this->fedexHelper->getAdminToken());
        $setupURL= $this->fedexHelper->getBaseUrl()."rest/V1/sharedCatalog/".$sharedCatId."/assignProducts";
        $headers=["Content-Type: application/json", "Authorization: Bearer ".$data_string];

        $ch = curl_init($setupURL);
        $postSharedCatalogAssignData['products'][]  = ['sku'=> $sharedCatalogProduct];
        $postSharedCatalogAssignData = json_encode($postSharedCatalogAssignData);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postSharedCatalogAssignData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        if ($result === false) {
            return false;
        } else {
            $response = curl_getinfo($ch);
            curl_close($ch);

            if ($response['http_code']==200) {
                return $result;
            }
        }
    }

    public function sharedCatalogAssignCompany($sharedCatalogCompanyId, $sharedCatId)
    {
        $data_string= trim($this->fedexHelper->getAdminToken());
        $setupURL= $this->fedexHelper->getBaseUrl()."rest/V1/sharedCatalog/".$sharedCatId."/assignCompanies";
        $headers=["Content-Type: application/json", "Authorization: Bearer ".$data_string];

        $ch = curl_init($setupURL);
        $postSharedCatalogAssignData['companies'][]  = ['id'=> $sharedCatalogCompanyId];
        $postSharedCatalogAssignData = json_encode($postSharedCatalogAssignData);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postSharedCatalogAssignData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        if ($result === false) {
            return false;
        } else {
            $response = curl_getinfo($ch);
            curl_close($ch);

            if ($response['http_code']==200) {
                return $result;
            }
        }
    }
}
