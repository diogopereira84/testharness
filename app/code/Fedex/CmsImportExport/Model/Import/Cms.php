<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CmsImportExport\Model\Import;

use Magento\Framework\Exception\LocalizedException;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\PageFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use \Fedex\CmsImportExport\Helper\Data;
use Magento\PageBuilder\Model\TemplateFactory;
use Magento\Widget\Model\Widget\InstanceFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Filesystem\Driver\File;

class Cms extends \Magento\Framework\Model\AbstractModel
{
    protected const UPDATE = " Updated ";

    /**
     * @var fileId  $fileId
     */
    protected $fileId = 'import_file';

    /**
     * @var allowedExtensions  $allowedExtensions
     */
    protected $allowedExtensions = ['csv'];

    /**
     * @param UploaderFactory $uploaderFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $resultFactory
     * @param File $driverInterface
     * @param BlockFactory $blockFactory
     * @param PageFactory $pageFactory
     * @param TemplateFactory $templateFactory
     * @param ManagerInterface $messageManager
     * @param InstanceFactory $instanceFactory
     * @param Data $helper
     * @param Http $request
     */
    public function __construct(
        protected UploaderFactory $uploaderFactory,
        protected LoggerInterface $logger,
        protected ResultFactory $resultFactory,
        protected File $driverInterface,
        protected BlockFactory $blockFactory,
        protected PageFactory $pageFactory,
        protected TemplateFactory $templateFactory,
        protected ManagerInterface $messageManager,
        protected InstanceFactory $instanceFactory,
        protected Data $helper,
        protected Http $request
    )
    {
    }

    /**
     * Call function to import CMS data in database
     *
     * @return string
     */
    public function importData()
    {
        return $this->saveData();
    }
    
    /**
     * To import CMS Data in database and count
     *
     * Blocks, Pages, Templates and Widgets
     *
     * @return string
     */
    public function saveData()
    {
        $destinationPath = $this->helper->getDestinationPath();
        
        try {
            $uploadedFile = "";
            $file = $this->request->getFiles('import_file');
            if (!empty($file) && isset($file["name"])) {
                $uploadedFileName = $destinationPath.$file["name"];
            }
            if (!$this->driverInterface->isExists($uploadedFileName)) {
                $uploader = $this->uploaderFactory->create(['fileId' => $this->fileId])
                ->setAllowCreateFolders(true)
                ->setAllowedExtensions($this->allowedExtensions);
                $uploader->setAllowRenameFiles(false);
                $this->saveUploader($uploader, $destinationPath);
                $result = "";
                $uploadedFile = $destinationPath . '/' . $uploader->getUploadedFileName();
            }
            $csvData = $this->helper->convertCsvToArray($uploadedFile);
            $blockUpdateData = [];
            $blockUpdateData["new"] = 0;
            $blockUpdateData["update"] = 0;

            $cmsUpdateData = [];
            $cmsUpdateData["new"] = 0;
            $cmsUpdateData["update"] = 0;

            $templateUpdateData = [];
            $templateUpdateData["new"] = 0;
            $templateUpdateData["update"] = 0;

            $widgetUpdateData = [];
            $widgetUpdateData["new"] = 0;
            $widgetUpdateData["update"] = 0;

            $resultMessage = [];
            
            if (!empty($csvData)) {
                foreach ($csvData as $rowNum => $data) {
                    $data = $this->filterData($data);
                    if ($data["type"]=="cms_block") {
                        $blockUpdateData = $this->importBlock($rowNum, $data, $blockUpdateData);
                    } elseif ($data["type"]=="cms_page") {
                        $cmsUpdateData = $this->importPage($rowNum, $data, $cmsUpdateData);
                    } elseif ($data["type"]=="template") {
                        $data["template"] = $data["content"];
                        $templateUpdateData = $this->importTemplate($rowNum, $data, $templateUpdateData);
                    } elseif ($data["type"]=="widget") {
                        $widgetUpdateData = $this->importWidget($rowNum, $data, $widgetUpdateData);
                    }
                }

                $resultMessage[] = "CMS Blocks Created ".$blockUpdateData["new"].self::UPDATE.
                $blockUpdateData["update"]."</br>";
                $resultMessage[] = "CMS Pages Created ".$cmsUpdateData["new"].self::UPDATE.
                $cmsUpdateData["update"]."<br/>";
                $resultMessage[] = "Pagebuilder Template Created ".$templateUpdateData["new"].self::UPDATE.
                $templateUpdateData["update"]."<br/>";
                $resultMessage[] = "Widget Created ".$widgetUpdateData["new"].self::UPDATE.
                $widgetUpdateData["update"];
                $successMessage = implode("", $resultMessage);
                $result = $this->messageManager->addSuccess($successMessage);
        
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $successMessage);
            }
            return $result;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());

            return $this->messageManager->addError(
                __($e->getMessage())
            );
        }
    }

    /**
     * Save Uploader
     * @return void
     */
    public function saveUploader($uploader, $destinationPath)
    {
        if (!$uploader->checkAllowedExtension($uploader->getFileExtension())) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Invalid file type.');
            throw new \Magento\Framework\Exception\LocalizedException(
                new \Magento\Framework\Phrase('Invalid file type.')
            );
        }
        if (!$uploader->save($destinationPath)) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' File cannot be saved to path.');
            throw new LocalizedException(
                __('File cannot be saved to path: $1', $destinationPath)
            );
        }
    }

    /**
     * Filter Data
     * @return array
     */
    public function filterData($data)
    {
        if (isset($data["stores"])) {
            $data["stores"] = $this->helper->getStoreData($data["stores"]);
        }
        if (isset($data["block_id_identifier"])
           && trim($data["block_id_identifier"])!="") {
            $data["content"] = $this->helper->getBlockUpdateContent(
                $data["content"],
                $data["block_id_identifier"],
                $data["type"]
            );
        }
        if (isset($data["page_id_identifier"])
           && trim($data["page_id_identifier"])!="") {
            $data["content"] = $this->helper->getPageUpdateContent(
                $data["content"],
                $data["page_id_identifier"],
                $data["type"]
            );
        }
        if (isset($data["category_id_name"])
            && trim($data["category_id_name"])!="") {
            $data["content"] = $this->helper->getCategoryUpdateContent(
                $data["content"],
                $data["category_id_name"],
                $data["type"]
            );
        }
        if (isset($data["product_id_sku"])
           && trim($data["product_id_sku"])!="") {
            $data["content"] = $this->helper->getProductUpdateContent(
                $data["content"],
                $data["product_id_sku"],
                $data["type"]
            );
        }
        return $data;
    }

    /**
     * To import and update CMS Block Data in database
     *
     * @param int $rowNum
     * @param array $data
     * @param array $blockUpdateData
     * @return string
     */
    public function importBlock($rowNum, $data, $blockUpdateData)
    {
        try {
            $cmsBlock = $this->blockFactory->create()->getCollection()
            ->addFieldToFilter("identifier", $data["identifier"])
            ->addFieldToFilter("store_id", $data["stores"])->load();
            if (count($cmsBlock)>0) {
                foreach ($cmsBlock as $cmsBlockData) {
                    $cmsBlockData->setContent($data["content"]);
                    if (count($cmsBlock)==1 && isset($data["stores"])) {
                        $cmsBlockData->setStores($data["stores"]);
                    }
                    $cmsBlockData->setTitle($data["title"]);
                    if (isset($data["is_active"])) {
                        $cmsBlockData->setIsActive($data["is_active"]);
                    }
                    $cmsBlockData->save();
                    $blockUpdateData["update"] = $blockUpdateData["update"]+1;
                }
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' CMS Block updated : '.$data["identifier"]);
            } else {
                $cmsBlock = $this->blockFactory->create();
                $cmsBlock->setData($data);
                $cmsBlock->save();
                $blockUpdateData["new"] = $blockUpdateData["new"]+1;
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' CMS Block created : '.$data["identifier"]);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
        
        return $blockUpdateData;
    }

    /**
     * To import and update CMS Page Data in database
     *
     * @param int $rowNum
     * @param array $data
     * @param array $cmsUpdateData
     * @return string
     */
    public function importPage($rowNum, $data, $cmsUpdateData)
    {
        try {
            $identifire = trim(strtolower($data["identifier"]));
            $data["identifier"] = $identifire;
            $cmsPage = $this->pageFactory->create()->getCollection()
            ->addFieldToFilter("identifier", $data["identifier"])
            ->addFieldToFilter("store_id", $data["stores"])->load();
            if (count($cmsPage)>0) {
                foreach ($cmsPage as $cmsPageData) {
                    $this->saveCmsPageData($cmsPageData, $cmsPage, $data);
                    $cmsUpdateData["update"] = $cmsUpdateData["update"]+1;
                }

                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' CMS Page updated : '.$data["identifier"]);
            } else {
                $cmsPage = $this->pageFactory->create();
                $cmsPage->setData($data);
                $cmsPage->save();
                $cmsUpdateData["new"] = $cmsUpdateData["new"]+1;
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' CMS Page created : '.$data["identifier"]);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
        
        return $cmsUpdateData;
    }

    /**
     * Save CMS Page Data
     * @return void
     */
    public function saveCmsPageData($cmsPageData, $cmsPage, $data)
    {
        $cmsPageData->setContent($data["content"]);
        if (count($cmsPage)==1 && isset($data["stores"])) {
            $cmsPageData->setStores($data["stores"]);
        }
        $cmsPageData->setTitle($data["title"]);
        if (isset($data["is_active"])) {
            $cmsPageData->setIsActive($data["is_active"]);
        }
        if (isset($data["content_heading"])) {
            $cmsPageData->setContentHeading($data["content_heading"]);
        }
        if (isset($data["meta_title"])) {
            $cmsPageData->setMetaTitle($data["meta_title"]);
        }
        if (isset($data["meta_keywords"])) {
            $cmsPageData->setMetaKeywords($data["meta_keywords"]);
        }
        if (isset($data["meta_description"])) {
            $cmsPageData->setMetaDescription($data["meta_description"]);
        }
        if (isset($data["layout_update_xml"])) {
            $cmsPageData->setLayoutUpdateXml($data["layout_update_xml"]);
        }
        if (isset($data["custom_theme"])) {
            $cmsPageData->setCustomTheme($data["custom_theme"]);
        }
        if (isset($data["custom_root_template"])) {
            $cmsPageData->setCustomRootTemplate($data["custom_root_template"]);
        }
        if (isset($data["custom_theme_from"])) {
            $cmsPageData->setCustomThemeFrom($data["custom_theme_from"]);
        }
        if (isset($data["custom_theme_to"])) {
            $cmsPageData->setCustomThemeTo($data["custom_theme_to"]);
        }
        if (isset($data["page_layout"])) {
            $cmsPageData->setPageLayout($data["page_layout"]);
        }
        $cmsPageData->save();
    }

    /**
     * To import and update Template Data in database
     *
     * @param int $rowNum
     * @param array $data
     * @param array $templateUpdateData
     * @return string
     */
    public function importTemplate($rowNum, $data, $templateUpdateData)
    {
        try {
            $cmsTemplate = $this->templateFactory->create()->getCollection()
            ->addFieldToFilter("name", $data["name"])->load()->setPageSize(1);

            if (count($cmsTemplate)>0) {
                foreach ($cmsTemplate as $cmsTemplateData) {
                    $cmsTemplateData->setTemplate($data["template"]);
                    $cmsTemplateData->setCreatedFor($data["created_for"]);
                    $cmsTemplateData->setPreviewImage($data["preview_image"]);
                    $cmsTemplateData->save();
                    $templateUpdateData["update"] = $templateUpdateData["update"]+1;
                }
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Template updated : '.$data["name"]);
            } else {
                $cmsTemplate = $this->templateFactory->create();
                $cmsTemplate->setData($data);
                $cmsTemplate->save();
                $templateUpdateData["new"] = $templateUpdateData["new"]+1;

                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Template created : '.$data["name"]);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return $templateUpdateData;
    }

    /**
     * Update widget paramets based on page identifier,block identifier, category name and product sku
     *
     * @param array $data
     * @return string
     */
    public function getWidgetParametersUpdate($data)
    {
        if (isset($data["widget_parameters"]) && trim($data["widget_parameters"])!=""
           && isset($data["block_id_identifier"]) && trim($data["block_id_identifier"])!="") {
            $data["widget_parameters"] = $this->helper->getBlockUpdateContent(
                $data["widget_parameters"],
                $data["block_id_identifier"],
                "widget"
            );
        }

        if (isset($data["widget_parameters"]) && trim($data["widget_parameters"])!=""
           && isset($data["page_id_identifier"]) && trim($data["page_id_identifier"])!="") {
            $data["widget_parameters"] = $this->helper->getPageUpdateContent(
                $data["widget_parameters"],
                $data["page_id_identifier"],
                "widget"
            );
        }

        if (isset($data["widget_parameters"]) && trim($data["widget_parameters"])!=""
           && isset($data["category_id_name"]) && trim($data["category_id_name"])!="") {
            $data["widget_parameters"] = $this->helper->getCategoryUpdateContent(
                $data["widget_parameters"],
                $data["category_id_name"],
                "widget"
            );
        }

        if (isset($data["widget_parameters"]) && trim($data["widget_parameters"])!=""
           && isset($data["product_id_sku"]) && trim($data["product_id_sku"])!="") {
            $data["widget_parameters"] = $this->helper->getProductUpdateContent(
                $data["widget_parameters"],
                $data["product_id_sku"],
                "widget"
            );
        }

        return $data["widget_parameters"];
    }

    /**
     * Update widget entities data based on category name and product sku
     *
     * @param array $pageGroupsData
     * @return string
     */
    public function getWidgetEntitiesUpdate($pageGroupsData)
    {
        foreach ((array) $pageGroupsData as $key => $pageGroup) {
            if (strpos($pageGroup["page_group"], "products") !== false && isset($pageGroup["all_pages"]["for"])
                && ($pageGroup["all_pages"]["for"]=="specific")) {
                    $pageGroup["all_pages"]["entities"] =  $this->helper
                    ->getProductWidgetUpdateContent($pageGroup["all_pages"]["entities"]);
                    $pageGroupsData[$key]["all_pages"]["entities"] = $pageGroup["all_pages"]["entities"];
            }

            if (strpos($pageGroup["page_group"], "categories") !== false && isset($pageGroup["all_pages"]["for"])
            && ($pageGroup["all_pages"]["for"]=="specific")) {
                    $pageGroup["all_pages"]["entities"] =  $this->helper
                        ->getCategoryWidgetUpdateContent($pageGroup["all_pages"]["entities"]);
                    $pageGroupsData[$key]["all_pages"]["entities"] = $pageGroup["all_pages"]["entities"];
            }
        }

        return $pageGroupsData;
    }

    /**
     * Import and update widget data in database
     *
     * @param int $rowNum
     * @param array $data
     * @param array $widgetUpdateData
     * @return string
     */
    public function importWidget($rowNum, $data, $widgetUpdateData)
    {
        try {
            $widgetData = $this->instanceFactory->create()->getCollection()
            ->addFieldToFilter("title", $data["title"])->setPageSize(1);
            
            $themeId = $this->helper->getThemeDetail($data["theme_id"]);

            if (isset($data["page_groups_json"])) {
                $pageGroupsData = json_decode($data["page_groups_json"], true);
                $pageGroupsData = $this->getWidgetEntitiesUpdate($pageGroupsData);
            } else {
                $pageGroupsData = [];
            }

            $data["widget_parameters"] = $this->getWidgetParametersUpdate($data);

            if (count($widgetData)>0) {
                foreach ($widgetData as $widgetDataList) {
                    $this->saveWidgetListData($widgetDataList, $data, $pageGroupsData);
                    $widgetUpdateData["update"] = $widgetUpdateData["update"]+1;
                }

                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Widget created : '.$data["title"]);
            } else {
                $widgetData = $this->instanceFactory->create();
                $widgetDataValue = [
                    'instance_type' => $data["instance_type"],
                    'theme_id' => $themeId,
                    'title' => $data["title"],
                    'store_ids' => isset($data["stores"]) ? $data["stores"] : "0",
                    'widget_parameters' => isset($data["widget_parameters"]) ?
                    $data["widget_parameters"]: "",
                    'sort_order' => 0,
                    'page_groups' => !empty($pageGroupsData) ? $pageGroupsData : ""
                ];

                $widgetData->setData($widgetDataValue)->save();
                $widgetUpdateData["new"] = $widgetUpdateData["new"]+1;

                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Widget created : '.$data["title"]);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return $widgetUpdateData;
    }

    /**
     * Save Widget List Data
     * @return void
     */
    public function saveWidgetListData($widgetDataList, $data, $pageGroupsData)
    {
        $widgetDataList->setInstanceType($data["instance_type"]);
        $widgetDataList->setThemeId($themeId);
        $widgetDataList->setTitle($data["title"]);
            if (!empty($data["stores"])) {
                $widgetDataList->setStoreIds($data["stores"]);
            } else {
                $widgetDataList->setStoreIds(0);
            }
            if (isset($data["widget_parameters"])) {
                $widgetDataList->setWidgetParameters($data["widget_parameters"]);
            }
            $widgetDataList->setSortOrder(0);
            if (!empty($pageGroupsData)) {
                $widgetDataList->setPageGroups($pageGroupsData);
            }
        $widgetDataList->save();
    }
}
