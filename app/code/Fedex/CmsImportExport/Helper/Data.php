<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CmsImportExport\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\PageFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;

/**
 * Class  to define functions which are used to import cms data
 */
class Data extends AbstractHelper
{
    /**
     * @var ThemeFactory
     */
    protected $themeFactory;
    public const TO_UPDATE_IN = ', to update in ';
    private CollectionFactory $collectionFactory;

    /**
     * @param Context $context
     * @param Filesystem $fileSystem
     * @param File $driverInterface
     * @param BlockFactory $blockFactory
     * @param PageFactory $pageFactory
     * @param CollectionFactory $collectionFactory
     * @param StoreRepositoryInterface $storeRepositoryInterface
     * @param LoggerInterface $logger,
     * @param ProductRepositoryInterface $productRepository
     * @param ThemeFactory $themeFactory
     */
    public function __construct(
        Context $context,
        protected Filesystem $fileSystem,
        protected ?\Magento\Framework\Filesystem\Driver\File $driverInterface,
        protected BlockFactory $blockFactory,
        protected PageFactory $pageFactory,
        CollectionFactory $collectionFactory,
        protected StoreRepositoryInterface $storeRepositoryInterface,
        protected LoggerInterface $logger,
        protected ProductRepositoryInterface $productRepository,
        ThemeCollectionFactory $themeFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->themeFactory = $themeFactory;
        parent::__construct($context);
    }

    /**
     * Get filesystem directory path to save import Csv
     *
     * @return string
     * @throws FileSystemException
     */
    public function getDestinationPath()
    {
        return $this->fileSystem
            ->getDirectoryWrite(DirectoryList::VAR_DIR)
            ->getAbsolutePath('cms');
    }

    /**
     * Get Import Csv header row data
     *
     * @return array
     */
    public function getCsvHeader($uploadedFile)
    {
        $header = null;
        if (isset($uploadedFile)) {
            if (($handle = $this->driverInterface->fileOpen($uploadedFile, 'r')) !== false) {
                while (($row = $this->driverInterface->fileGetCsv($handle, 100000)) !== false) {
                    if (!$header) {
                        $row = array_map('trim', $row);
                        $header = $this->filterHeader($row);
                        break;
                    }
                }
                $this->driverInterface->fileClose($handle);
                return $header;
            }
        } else {
            return $header;
        }
    }

    /**
     * Filter Header
     * @return array
     */
    public function filterHeader($row)
    {
        if ($row!="") {
            return array_filter($row);
        }
    }

    /**
     * Get Import Csv column row data
     *
     * @return array
     */
    public function convertCsvToArray($uploadedFile)
    {
        $data = [];
        $header = null;
        if (isset($uploadedFile)) {
            if (($handle = $this->driverInterface->fileOpen($uploadedFile, 'r')) !== false) {
                while (($row = $this->driverInterface->fileGetCsv($handle, 100000)) !== false) {
                    if (!$header) {
                        $row = array_map('trim', $row);
                        $header = $row;
                    } else {
                        $row = array_map('trim', $row);
                        $this->checkRowHeader($header, $row);
                        $data[] = array_combine($header, $row);
                    }
                }
                $this->driverInterface->fileClose($handle);

                return $data;
            }
        } else {
            return $data;
        }
    }
    /**
     * Check Header and Row
     *
     * @return string
     */
    public function checkRowHeader($header, $row)
    {
        if (count($header)<count($row)) {
            return "Row column count should be equal to header column count";
        }
    }

    /**
     * Update import csv content to save based on
     * block id identifier
     *
     * @return string
     */
    public function getBlockUpdateContent($content, $blockIdentifier, $type)
    {
        try {
            $blockIdentifierArray = explode("|", $blockIdentifier);
            $identifierArray = [];
            foreach ($blockIdentifierArray as $key => $blockValue) {
                $block = explode("=>", $blockValue);
                $blockData = $this->blockFactory->create()->load(trim($block[1]), 'identifier');
                if ($blockData->getId()) {
                    $identifierArray[trim($block[0])] = $blockData->getId();
                } else {
                    $identifierArray[trim($block[0])] = trim($block[0]);
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Block identifier, ' .
                    $blockData->getId() . self::TO_UPDATE_IN .$type.' does not exist : '.trim($block[1]));
                }
            }
            if (isset($content) && !empty($identifierArray)) {
                    foreach ($identifierArray as $key => $value) {
                        if ($type=="widget") {
                            $content = str_replace('"block_id":"'.$key.'"', '"block_id":"'.$value.'"', $content);
                        } else {
                            $content = str_replace('block_id="'.$key.'"', 'block_id="'.$value.'"', $content);
                        }
                    }
            }
            return $content;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * Update import csv content to save based on
     * block id identifier
     *
     * @return string
     */
    public function getPageUpdateContent($content, $pageIdentifier, $type)
    {
        try {
            $pageIdentifierArray = explode("|", $pageIdentifier);
            $identifierArray = [];
            foreach ($pageIdentifierArray as $key => $pageValue) {
                $page = explode("=>", $pageValue);
                $pageData = $this->pageFactory->create()->load(trim($page[1]), 'identifier');
                if ($pageData->getId()) {
                    $identifierArray[trim($page[0])] = $pageData->getId();
                } else {
                    $identifierArray[trim($page[0])] = trim($page[0]);
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Page identifier, ' .
                    $pageData->getId() . self::TO_UPDATE_IN . $type . ' does not exist : ' . trim($page[1]));
                }
            }
            if (isset($content) && !empty($identifierArray)) {
                    foreach ($identifierArray as $key => $value) {
                        if ($type=="widget") {
                            $content = str_replace('"page_id":"'.$key.'"', '"page_id":'.$value.'"', $content);
                        } else {
                            $content = str_replace('page_id="'.$key.'"', 'page_id="'.$value.'"', $content);
                        }
                    }
            }
            return $content;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * Update import csv content to save based on
     * category id path
     *
     * @return string
     */
    public function getCategoryUpdateContent($content, $categoryName, $type)
    {
        try {
            $categoryNameArray = explode("|", (string)$categoryName);
            $identifierArray = [];
            foreach ($categoryNameArray as $key => $categoryValue) {
                $categoryExplodeData = explode("=>", $categoryValue);
                if (isset($categoryExplodeData[1])) {
                    $categoryPathData = explode("/", $categoryExplodeData[1]);
                    $parentId = "";
                    foreach ($categoryPathData as $key => $categoryValue) {
                        if ($key==0) {
                            $categoryData = $this->collectionFactory->create()
                            ->addAttributeToFilter('name', $categoryValue)
                            ->addAttributeToFilter('level', $key+1)
                            ->setPageSize(1);
                            $categoryPath = $this->getCategoryPath($categoryData, $categoryExplodeData, $type);
                        } else {
                            $categoryData = $this->collectionFactory->create()
                            ->addAttributeToFilter('name', $categoryValue)
                            ->addAttributeToFilter('level', $key+1)
                            ->addAttributeToFilter('parent_id', $parentId)
                            ->setPageSize(1);
                            $categoryPath = $this->getCategoryPath($categoryData, $categoryExplodeData, $type);
                        }
                    }
                    $identifierArray[trim($categoryExplodeData[0])] = $categoryPath;
                }
            }
            $content = $this->checkContentIdentifier($content, $identifierArray, $type);
            return $content;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * Get Category Path
     * @return string
     */
    public function getCategoryPath($categoryData, $categoryExplodeData, $type)
    {
        if (count($categoryData)>0) {
            foreach ($categoryData as $category) {
                $categoryPath = $category->getEntityId();
            }
        } else {
            $categoryPath = $categoryExplodeData[0];
            $this->logger->info(__METHOD__ . ':' . __LINE__ .
            ' No category data being returned in ' . $type . ' : ' . $categoryExplodeData[1]);
        }
        return $categoryPath;
    }

    /**
     * Check Content Identifier
     * @return string
     */
    public function checkContentIdentifier($content, $identifierArray, $type)
    {
        if (isset($content) && !empty($identifierArray)) {
            foreach ($identifierArray as $key => $value) {
                    if ($type=="widget") {
                        return str_replace('category\/'.$key, 'category\/'.$value, $content);
                    } else {
                        return str_replace('category/'.$key.'"', 'category/'.$value.'"', $content);
                    }
            }
        }
    }

    /**
     * Update import csv content to save based on
     * product sku
     *
     * @return string
     */
    public function getProductUpdateContent($content, $productSku, $type)
    {
        try {
            $productSkuArray = explode("|", $productSku);
            $identifierArray = [];
            foreach ($productSkuArray as $key => $productValue) {
                $product = explode("=>", $productValue);
                $productData = $this->productRepository->get(trim($product[1]));
                if ($productData->getId()) {
                    $identifierArray[trim($product[0])] = $productData->getId();
                } else {
                    $identifierArray[trim($product[0])] = trim($product[0]);
                    $this->logger->info(__METHOD__ . ':' . __LINE__ .
                    ' Product sku, ' . $productData->getId() . self::TO_UPDATE_IN . $type . ' does not exist :
                    ' . trim($product[1]));
                }
            }
            if (isset($content) && !empty($identifierArray)) {
                foreach ($identifierArray as $key => $value) {
                    if ($type=="widget") {
                            $content = str_replace('product\/'.$key, 'product\/'.$value, $content);
                    } else {
                            $content = str_replace('product/'.$key.'"', 'product/'.$value.'"', $content);
                    }
                }
            }
            return $content;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * Update import csv content to save for widget based on
     * product sku
     *
     * @return string
     */
    public function getProductWidgetUpdateContent($widgetEntitiesData)
    {
        try {
            $widgetEntitiesDataArray = explode(",", $widgetEntitiesData);
            $identifierArray = [];
            $content = "";
            foreach ($widgetEntitiesDataArray as $widgetEntitiesValue) {
                $productData = $this->productRepository->get(trim($widgetEntitiesValue));
                if ($productData->getId()) {
                    $identifierArray[] = $productData->getId();
                } else {
                    $this->logger->info(__METHOD__ . ':' . __LINE__ .
                    ' Product sku, ' . $productData->getId() . ', to update in widget does not exist');
                }
            }
            if (!empty($identifierArray)) {
                $content = implode(",", $identifierArray);
            }
            return $content;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * Update import csv content to save for widget based on
     * category id path
     *
     * @return string
     */
    public function getCategoryWidgetUpdateContent($widgetEntitiesData)
    {
        try {
            $widgetEntitiesDataArray = explode(",", (string)$widgetEntitiesData);
            $identifierArray = [];

            foreach ($widgetEntitiesDataArray as $widgetEntitiesData) {
                if (isset($widgetEntitiesData)) {
                    $categoryPathData = explode("/", $widgetEntitiesData);
                    $categoryPath = "";
                    $parentId = "";
                    foreach ($categoryPathData as $key => $categoryValue) {
                        if ($key==0) {
                            $categoryData = $this->collectionFactory->create()
                            ->addAttributeToFilter('name', $categoryValue)
                            ->addAttributeToFilter('level', $key+1)
                            ->setPageSize(1);
                            $categoryPath = $this->getWidgetsCategoryPath($categoryData, $widgetEntitiesData);
                        } else {
                            $categoryData = $this->collectionFactory->create()
                            ->addAttributeToFilter('name', $categoryValue)
                            ->addAttributeToFilter('level', $key+1)
                            ->addAttributeToFilter('parent_id', $parentId)
                            ->setPageSize(1);
                            $categoryPath = $this->getWidgetsCategoryPath($categoryData, $widgetEntitiesData);
                        }
                    }
                    if (isset($categoryPath)) {
                        $identifierArray[] = $categoryPath;
                    }
                }
            }

            return $this->returnContent($identifierArray);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * Return Content
     * @return string
     */
    public function returnContent($identifierArray)
    {
        if (!empty($identifierArray)) {
            return implode(",", $identifierArray);
        }
    }

    /**
     * Get Widget Category Path
     * @return string
     */
    public function getWidgetsCategoryPath($categoryData, $widgetEntitiesData)
    {
        if (count($categoryData)>0) {
            foreach ($categoryData as $category) {
                return $category->getEntityId();
            }
        } else {
            $this->logger->info(__METHOD__ . ':' . __LINE__ .
            ' Category to update in widget does not exist :
            ' . $widgetEntitiesData);
        }
    }

    /**
     * Get store data in array form
     *
     * @return array
     */
    public function getStoreData($stores)
    {
        try {
            $storeData = explode("|", (string)$stores);
            $storeDataValue = [];
            foreach ($storeData as $store) {
                if ($store!==0) {
                    $storeData = $this->storeRepositoryInterface->get($store);
                    if (!empty($storeData)) {
                        $storeDataValue[] = $storeData->getId();
                    } else {
                        $storeDataValue[] = 0;
                        $this->logger->info(__METHOD__ . ':' . __LINE__ .
                        ' Store does not exist with store code : '.$store);
                    }
                } else {
                    $storeDataValue[] = 0;
                }
            }
            return $storeDataValue;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * Get theme id based on theme code
     *
     * @return array
     */
    public function getThemeDetail($code)
    {
        try {
            $theme = $this->themeFactory->create()->addFieldToFilter("code", $code)->setPageSize(1);
            $themeId = "2";
            if (count((array)$theme)>0) {
                foreach ($theme as $themeData) {
                    $themeId = $themeData->getId();
                }
            }
            return $themeId;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .  ' ' . $e->getMessage());
        }
    }

    /**
     * Get Theme Code By Id
     * @return string
     */
    public function getThemeCodeById($themeId)
    {
        try {
            $theme = $this->themeFactory->create()->addFieldToFilter("theme_id", $themeId)->setPageSize(1);
            $themeCode = "Magento/luma";
            if (count((array)$theme)>0) {
                foreach ($theme as $themeData) {
                    $themeCode = $themeData->getCode();
                }
            }
            return $themeCode;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * Get Store Code By Id
     * @return string
     */
    public function getStoreCodeById($storeId)
    {
        try {
            $storeCode = "";
            $storeData = $this->storeRepositoryInterface->get($storeId);
            if (!empty($storeData->getCode())) {
                $storeCode = $storeData->getCode();
            }
            return $storeCode;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }
}
