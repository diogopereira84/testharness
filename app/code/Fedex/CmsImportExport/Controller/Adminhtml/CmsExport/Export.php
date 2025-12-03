<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CmsImportExport\Controller\Adminhtml\CmsExport;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Fedex\CmsImportExport\Model\CmsPageFactory;
use Fedex\CmsImportExport\Model\CmsBlockFactory;
use Fedex\CmsImportExport\Model\CmsBuilderFactory;
use Fedex\CmsImportExport\Model\CmsWidgetFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Widget\Model\Widget\Instance;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Framework\File\Csv;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use \Fedex\CmsImportExport\Helper\Data;
use \Psr\Log\LoggerInterface;
use \Datetime;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;

class Export extends \Magento\Backend\App\Action
{
    /**
     * @var PageFactory
     */
    private $pageFactory;

    protected $collectionFactory;

    /**
     * @var InstanceFactory  $instanceFactory
     */
    protected $instanceFactory;

    /**
     * @var Data  $helper
     */
    protected $helperData;

    /**
     * @var logger
     */
    protected $logger;
    private CollectionFactory $_collectionFactory;
    private StoreManagerInterface $_storeManager;

    /**
     * @param Context $context
     * @param PageFactory $rawFactory
     * @param CmsPageFactory $modelCmsPageFactory
     * @param CmsBlockFactory $modelCmsBlockFactory
     * @param CmsBuilderFactory $modelCmsBuilderFactory
     * @param CmsWidgetFactory $modelCmsWidgetFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FileFactory $fileFactory
     * @param ProductRepository $productRepository
     * @param LayoutFactory $resultLayoutFactory
     * @param Csv $csvProcessor
     * @param PageRepositoryInterface $pageRepository
     * @param StoreManagerInterface $storeManager
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CategoryFactory $categoryFactory
     * @param ManagerInterface $messageManager
     * @param BlockRepositoryInterface $blockRepository
     * @param CollectionFactory $collectionFactory
     * @param Data $helper
     * @param Instance $instanceFactory
     * @param DirectoryList $directoryList
     * @param LoggerInterface $objLoggerInterface
     * @param File $fileDriver
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        PageFactory $rawFactory,
        protected CmsPageFactory $modelCmsPageFactory,
        protected CmsBlockFactory $modelCmsBlockFactory,
        protected CmsBuilderFactory $modelCmsBuilderFactory,
        protected CmsWidgetFactory $modelCmsWidgetFactory,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        protected FileFactory $fileFactory,
        protected ProductRepository $productRepository,
        protected LayoutFactory $resultLayoutFactory,
        protected Csv $csvProcessor,
        protected PageRepositoryInterface $pageRepository,
        StoreManagerInterface $storeManager,
        private CategoryRepositoryInterface $categoryRepository,
        protected CategoryFactory $categoryFactory,
        ManagerInterface $messageManager,
        private BlockRepositoryInterface $blockRepository,
        CollectionFactory $collectionFactory,
        Data $helper,
        Instance $instanceFactory,
        protected DirectoryList $directoryList,
        LoggerInterface $objLoggerInterface,
        private File $fileDriver,
        private Filesystem $filesystem
    ) {
        $this->pageFactory              = $rawFactory;
        $this->_storeManager            = $storeManager;
        $this->helperData               = $helper;
        $this->instanceFactory          = $instanceFactory;
	    $this->_collectionFactory       = $collectionFactory;
        $this->logger                  = $objLoggerInterface;
        parent::__construct($context);
    }

    /**
     * CSV Create and Download
     *
     * @return ResponseInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        try {
                set_time_limit(0);
                /**
                 * Function member variable declarion
                 */
                $themeId = $widgetTitle = $widgetStoreIds = $widgetParameters =
                $sortOrder = $pageGroups = "";

                $now = new DateTime();
                $date = $now->format('Y_m_d_H_i_s');

                /*Request post parameter*/
                $contentpageid      = $this->getRequest()->getPostValue("content-id");
                $contentblockid     = $this->getRequest()->getPostValue("content-block-id");
                $contenttemplateid  = $this->getRequest()->getPostValue("content-template-id");
                $contentwidgetid    = $this->getRequest()->getPostValue("content-widget-id");

                /*Calling model collection by id's*/
                $pagecollection     = ($this->modelCmsPageFactory->create())->getCollection()
                                        ->addFieldToFilter('page_id', array('in' => $contentpageid));

                $blockcollection    = ($this->modelCmsBlockFactory->create())->getCollection()
                                            ->addFieldToFilter('block_id', array('in' => $contentblockid));

                $buildercollection  = ($this->modelCmsBuilderFactory->create())->getCollection()
                                            ->addFieldToFilter(
                                                'template_id',
                                                array('in' => $contenttemplateid)
                                            );

            /** Add your header name here */
            $content[] = $this->getContentHeaderArray();

            /*Preparing CMS Block data to write in CSV*/
            foreach ((array) $blockcollection->getData() as $cmsBlock) {
                $template = strip_tags($cmsBlock['content']);
                $arrResponseData = $this->getCSVDataByTemplate($template);

                $content[] = $this->getCmsBlockArray($cmsBlock, $arrResponseData);
            }

            /*Preparing CMS page data to write in CSV*/
            foreach ((array) $pagecollection->getData() as $cmsPage) {
                $template = strip_tags($cmsPage['content']);
                $arrResponseData = $this->getCSVDataByTemplate($template);

                $type = 'cms_page';
                $content[] = $this->getCmsPageArray($cmsPage, $arrResponseData, $type);
            }

            /*Preparing CMS template data to write in CSV*/
            foreach ((array) $buildercollection->getData() as $cmsBuilder) {
                $template = strip_tags($cmsBuilder['template']);
                $arrResponseData = $this->getCSVDataByTemplate($template);

                $content[] = [
                'template',
                $cmsBuilder['name'],
                '',
                $cmsBuilder['template'],
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                $cmsBuilder['name'],
                '',
                $cmsBuilder['preview_image'],
                $cmsBuilder['created_for'],
                '',
                '',
                '',
                '',
                $arrResponseData['pageIdentifier'],
                $arrResponseData['blockIdentifier'],
                $arrResponseData['catName'],
                $arrResponseData['Sku']
            ];
            }

            /*Preparing CMS widget data to write in CSV*/
            if (!empty($contentwidgetid)) {
                foreach ($contentwidgetid as $widgetInstanceId) {
                    $widgetStoreIdsData = $pageGroup = [];

                    $widgetData     = $this->instanceFactory->load($widgetInstanceId);
                    $instanceTypes   = $widgetData->getInstanceType();
                    $themeId        = $this->helperData->getThemeCodeById($widgetData->getThemeId());
                    $widgetTitle    = $widgetData->getTitle();

                    $widgetStoreIdsData = $this->getWidgetStoreIds($widgetData);

                    $widgetStoreIds = implode(",", $widgetStoreIdsData);
                    $widgetParameters = (array) $widgetData->getWidgetParameters();

                    $widgetParameters = $this->checkWidgetParameters($widgetParameters);

		            $widgetParametersJson = json_encode($widgetParameters);

                    $sortOrder = $widgetData->getSortOrder();
                    $pageGroups = $widgetData->getPageGroups();

                    $pageGroup = (array) $this->getPageGroup($pageGroups);

                    if (!empty($pageGroup)) {
                        $jsonPageGroup = json_encode($pageGroup);
                    } else {
                        $jsonPageGroup = null;
                    }

                    $content[] = [
                        'widget',
                        $widgetTitle,
                        '',
                        '',
                        '',
                        $widgetStoreIds,
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        $instanceTypes,
                        $themeId,
                        $widgetParametersJson,
                        $jsonPageGroup,
                        '',
                        '',
                        '',
                        ''
                    ];
                }
            }


            $fileName = $date.'_cms_content_export.csv'; // Add Your CSV File name
            $filePath =  $this->directoryList->getPath(DirectoryList::VAR_DIR) . "/export/" . $fileName;
            $this->csvProcessor->setEnclosure('"')->setDelimiter(',')->saveData($filePath, $content);

            $path = 'export/' . $fileName;
            if ($this->fileDriver->isExists($filePath)) {
                $directory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
                if ($directory->isFile($path)) {
                    $content = [];
		            $content['type'] = 'filename'; // must keep filename
        	        $content['value'] = $path;
        	        $content['rm'] = '1';
                    return $this->fileFactory->create(
                        $fileName,
                        $content,
                        DirectoryList::VAR_DIR
                    );
                }
            } else {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' File path does not exist.');
                return false;
            }
        } catch (\Exception $error) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $error->getMessage());
        }
    }

    /**
     * Get Widget Store Ids
     * @return array
     */
    public function getWidgetStoreIds($widgetData)
    {
        $widgetStoreIdsData = [];
        if (!empty($widgetData->getStoreIds())) {
            foreach ($widgetData->getStoreIds() as $widgetStoreId) {
                $widgetStoreIdsData[] = $this->helperData->getStoreCodeById($widgetStoreId);
            }
        }
        return $widgetStoreIdsData;
    }

    /**
     * Check Widget Parameters
     * @return array
     */
    public function checkWidgetParameters($widgetParameters)
    {
        if (array_key_exists('block_id', $widgetParameters) &&
                $widgetParameters['block_id']!= null) {
                $blockIdentifier = $this->getBlockById($widgetParameters['block_id']);
                $widgetParameters['block_id'] = $blockIdentifier;
        }

        if (array_key_exists('page_id', $widgetParameters) &&
                $widgetParameters['page_id']!= null) {
                $pageIdentifier = $this->getPageById($widgetParameters['page_id']);
                $widgetParameters['page_id'] = $pageIdentifier;
        }

        if (array_key_exists('id_path', $widgetParameters) &&
                $widgetParameters['id_path']!= null) {
                $idpath = explode('/', $widgetParameters['id_path']);
                if ($idpath[0] == 'category') {
                    $categoryName = $this->getCatName($idpath[1]);
                    $widgetParameters['id_path'] = 'category/' .$categoryName;
                } elseif ($idpath[0] == 'product') {
                    $productName = $this->getProSku($idpath[1]);
                    $widgetParameters['id_path'] = 'product/' .$productName;
                }
        }
        return $widgetParameters;
    }

    /**
     * Get Content Header
     * @return array
     */
    public function getContentHeaderArray()
    {
        return [
            'type' => __('type'),
            'title' => __('title'),
            'identifier' => __('identifier'),
            'content' => __('content'),
            'is_active' => __('is_active'),
            'store_id' => __('stores'),
            'content_heading' => __('content_heading'),
            'meta_title' => __('meta_title'),
            'meta_keywords' => __('meta_keywords'),
            'meta_description' => __('meta_description'),
            'layout_update_xml'=> __('layout_update_xml'),
            'custom_theme'=> __('custom_theme'),
            'custom_root_template'=> __('custom_root_template'),
            'custom_theme_from'=> __('custom_theme_from'),
            'custom_theme_to'=> __('custom_theme_to'),
            'page_layout'=> __('page_layout'),
            'name'=> __('name'),
            'template' => __('template'),
            'preview_image' => __('preview_image'),
            'created_for' => __('created_for'),
            'instance_type' => __('instance_type'),
            'theme_id' => __('theme_id'),
            'widget_parameters' => __('widget_parameters'),
            'page_group' => __('page_groups_json'),
            'page_id' => __('page_id_identifier'),
            'block_id' => __('block_id_identifier'),
            'category_id_name' => __('category_id_name'),
            'product_id_sku' => __('product_id_sku')
        ];
    }

    /**
     * Get CMS Block Array
     * @return array
     */
    public function getCmsBlockArray($cmsBlock, $arrResponseData)
    {
        return [
            'cms_block',
            $cmsBlock['title'],
            $cmsBlock['identifier'],
            $cmsBlock['content'],
            $cmsBlock['is_active'],
            $cmsBlock['code'],
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            $arrResponseData['pageIdentifier'],
            $arrResponseData['blockIdentifier'],
            $arrResponseData['catName'],
            $arrResponseData['Sku']
        ];
    }

    /**
     * Get CMS Page Array
     * @return array
     */
    public function getCmsPageArray($cmsPage, $arrResponseData, $type)
    {
        return [
            $type,
            $cmsPage['title'],
            $cmsPage['identifier'],
            $cmsPage['content'],
            $cmsPage['is_active'],
            $cmsPage['code'],
            $cmsPage['content_heading'],
            $cmsPage['meta_title'],
            $cmsPage['meta_keywords'],
            $cmsPage['meta_description'],
            $cmsPage['layout_update_xml'],
            $cmsPage['custom_theme'],
            $cmsPage['custom_root_template'],
            $cmsPage['custom_theme_from'],
            $cmsPage['custom_theme_to'],
            $cmsPage['page_layout'],
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            $arrResponseData['pageIdentifier'],
            $arrResponseData['blockIdentifier'],
            $arrResponseData['catName'],
            $arrResponseData['Sku']
        ];
    }

    /**
    * This function will return the category name
    * @return string
    */
    public function getCategoryNameById($widgetContent)
    {
        try {
            $name = $categoryPath = [];
            if (!empty($widgetContent)) {
                $currentCategoryId = '';
                foreach ($widgetContent as $data) {
                    $word = 'id_path="category/';
                    $findData = rtrim($data, '"');

                    //varify if string contains the word
                    if (strpos($findData, $word) !== false) {
                        $cat = explode('id_path="category/', $findData);
                        if ($cat[1] != '') {
                            $currentCategoryId = $cat[1];
                            $category = $this->categoryFactory->create()->load($cat[1]);
                            $name = $this->getName($category, $cat);
                        }
                    }
                    if ($currentCategoryId != '') {
                        $categoryPath[] = $currentCategoryId.'=>'.implode('/', $name);
                    }
                }
                $categoryPath = array_unique($categoryPath);
                $categoryPath = implode('|', $categoryPath);
            }
            return  $categoryPath;
        } catch (\Exception $error) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $error->getMessage());
        }
    }

    /**
     * Get Name
     * @return string
     */
    public function getName($category, $cat)
    {
        $name = [];
        if ($category) {
            $parentIdsArray = $category->getParentIds($cat[1]);
            // Load Category Name from Parent Category ID
            foreach ($parentIdsArray as $parentId) {
                if ($parentId != 1): // if Not Root Catalog Category
                    $parentCategory = $this->categoryFactory->create()->load($parentId);
                    $name[$parentCategory->getId()] = $parentCategory->getName();
                endif;
            }
            $name[$category->getId()] = $category->getName();
        }
        return $name;
    }

    /**
    * This function will return the Product SKU for Widget
    * @return string
    */
    public function getProductsById($ids)
    {
        try {
            $id = explode(",", $ids);
            $name = array();
            if (!empty($id)) {
            $collection = $this->_collectionFactory->create()->addAttributeToSelect('sku')->load();
                if ($collection) {
                    foreach ($collection as $product) {
                        $name[] = $product->getSku();
                    }
                }
            }
            return implode(',', $name);

        } catch (\Exception $error) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $error->getMessage());
        }
    }

    /**
    * This function will return the Product SKU
    * @return string
    */
    public function getProductSKUById($widgetContent)
    {
        try {
            $name = [];
            if (!empty($widgetContent)) {
                foreach ($widgetContent as $data) {
                    $word = 'id_path="product/';
                    $findData = rtrim($data, '"');

                    //Test if string contains the word
                    if (strpos($findData, $word) !== false) {
                        $prod = explode('id_path="product/', $findData);
                        try {
                        $proName =  $this->productRepository->getById($prod[1]);
                        $productSKU = $prod[1] ."=>". $proName->getSku();
                    } catch (\Exception $error) {
                        $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $error->getMessage());
                    }
                        $name[] = $productSKU;
                    }
                }
            }
            return implode('|', $name);
        } catch (\Exception $error) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $error->getMessage());
        }
    }

    /**
     * This function will return the Page Identifierduct
     * @return string
     */
    public function getPageIdentifierById($pageContent)
    {
        try {
            $name = [];
            if (!empty($pageContent)) {
                foreach ($pageContent as $data) {
                    $word = 'page_id="';
                    $findData = rtrim($data, '"');

                    //Test if string contains the word
                    if (strpos($findData, $word) !== false) {
                        $pageId = explode('page_id="', $findData);
                        try {
                        $page = $this->pageRepository->getById($pageId[1]);
                        $pageName = $pageId[1] ."=>". $page->getIdentifier();
                        } catch (\Exception $error) {
                            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $error->getMessage());
                        }
                        $name[] = $pageName;
                    }
                }
            }
            return implode('|', $name);

        } catch (\Exception $error) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $error->getMessage());
        }
    }

    /**
    * This function will return the Block Identifierduct
    * @return string
    */
    public function getBlockIdentifierById($blockContent)
    {
        try {
            $name = [];
            $blockName = "";
            if (!empty($blockContent)) {
                foreach ($blockContent as $data) {
                    $word = 'block_id="';
                    $findData = rtrim($data, '"');

                    //Test if string contains the word
                    if (strpos($findData, $word) !== false) {
                        $blockId = (int) explode('"', explode("block_id", $findData)[1])[1];
                        try {
                            $block = $this->blockRepository->getById($blockId);
                            $blockName = $blockId ."=>". $block->getIdentifier();
                        } catch (\Exception $error) {
                            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $error->getMessage());
                        }
                        $name[] = $blockName;
                    }
                }
            }

            return implode('|', $name);

        } catch (\Exception $error) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $error->getMessage());
        }
    }

    /**
    * This function will return the category name
    * @return string
    */
    public function getCatName($id)
    {
        try {
            $category = $this->categoryFactory->create()->load($id);
            return  $category->getName();
        } catch (\Exception $error) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $error->getMessage());
        }
    }

    /**
     * This function will return the product SKU
     * @return string
     * */
    public function getProSku($id)
    {
        try {
            $product = $this->productRepository->getById($id);
            return $product->getSku();
        } catch (\Exception $error) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $error->getMessage());
        }
    }

    /**
    * This function will return the Page
    * @return string
    */
    public function getPageById($id)
    {
        try {
            $page = $this->pageRepository->getById($id);
            return $page->getIdentifier();
        } catch (\Exception $error) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $error->getMessage());
        }
    }

    /**
    * This function will return the block
    * @return string
    */
    public function getBlockById($id)
    {
        try {
            $block = $this->blockRepository->getById($id);
            return $block->getIdentifier();
        } catch (\Exception $error) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $error->getMessage());
        }
    }

    /**
     * This function will return the template date
     * @return string
     */
    public function getCSVDataByTemplate($strTemplateDetails)
    {
        try {
            $arrResonceData =['widgetContent' =>'', 'Sku' => '', 'catName' => '',
            'pageIdentifier' => '', 'blockIdentifier'=>'' ];
            $substr = "{{widget type=";
            if (strpos($strTemplateDetails, $substr) !== false) {
                $widgetContent = $this->getContents($strTemplateDetails, '{{widget type=', '}}');
                unset($arrResonceData);

                $arrResonceData =[
                    'Sku'               => $this->getProductSKUById($widgetContent),
                    'catName'           => $this->getCategoryNameById($widgetContent),
                    'pageIdentifier'    => $this->getPageIdentifierById($widgetContent),
                    'blockIdentifier'   => $this->getBlockIdentifierById($widgetContent)
                ];
            }

            return $arrResonceData;
        } catch (\Exception $error) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $error->getMessage());
        }
    }

    /**
     * Get Categories Name
     * @return string
     */
    public function getCategoriesName($categoryIds)
    {
        try {
            $categoryIdss = explode(",", $categoryIds);
            $name = array();
            $currentCategoryId = '';
            $categoryPathArray = array();
            foreach ($categoryIdss as $categoryId) {
                $currentCategoryId = $categoryId;
                $category = $this->categoryFactory->create()->load($categoryId);
                if ($category) {
                    $parentIdsArray = $category->getParentIds($categoryId);
                    // Load Category Name from Parent Category ID
                    foreach ($parentIdsArray as $parentId) {
                        if ($parentId != 1): // if Not Root Catalog Category
                                    $parentCategory = $this->categoryFactory->create()->load($parentId);
                        $name[$parentCategory->getId()] = $parentCategory->getName();
                        endif;
                    }
                    $name[$category->getId()] = $category->getName();
                    if ($currentCategoryId != '') {
                        $categoryPathArray[] = implode('/', $name);
                    }
                }
            }
            return implode('|', $categoryPathArray);
        } catch (\Exception $error) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $error->getMessage());
        }
    }

    /**
     * Get Contents
     * @return string
     */
    public function getContents($str, $startDelimiter, $endDelimiter)
    {
        try {
            $contents = [];
            $startFrom = $contentStart = $contentEnd = 0;

            $startDelimiterLength = strlen($startDelimiter);
            $endDelimiterLength = strlen($endDelimiter);

            while (false !== ($contentStart = strpos($str, $startDelimiter, $startFrom))) {
                $contentStart += $startDelimiterLength;
                $contentEnd = strpos($str, $endDelimiter, $contentStart);
                if (false === $contentEnd) {
                    break;
                }
                $contents[] = substr($str, $contentStart, $contentEnd - $contentStart);
                $startFrom = $contentEnd + $endDelimiterLength;
            }

            return $contents;
        } catch (\Exception $error) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $error->getMessage());
        }
    }

    /**
     * Get Page Group
     * @return string
     */
    public function getPageGroup($pageGroups)
    {
        try {
            $pageGroup = [];

            $arrStrCatalog=[
                'catalog_product_view',
                'catalog_product_view_type_simple',
                'catalog_product_view_type_virtual',
                'catalog_product_view_type_bundle',
                'catalog_product_view_type_downloadable',
                'catalog_product_view_type_giftcard',
                'catalog_product_view_type_configurable'
            ];
        if (!empty($pageGroups)) {
            foreach ((array) $pageGroups as $key => $pageGroupsData) {
                if (!empty($pageGroupsData["entities"])) {
                    if ($pageGroupsData['layout_handle'] == 'catalog_category_view_type_layered') {
                        $pageGroupsData["entities"] = $this->getCategoriesName($pageGroupsData['entities']);
                    } elseif (in_array($pageGroupsData['layout_handle'], $arrStrCatalog)) {
                        $pageGroupsData["entities"] = $this->getProductsById($pageGroupsData['entities']);
                    }
                }
                $pageGroup[$key]["page_group"] = $pageGroupsData["page_group"];
                $pageGroup[$key][$pageGroupsData["page_group"]]["page_id"] = null;
                $pageGroup[$key][$pageGroupsData["page_group"]]
                ["layout_handle"] = $pageGroupsData["layout_handle"];
                $pageGroup[$key][$pageGroupsData["page_group"]]["block"] = $pageGroupsData["block_reference"];
                $pageGroup[$key][$pageGroupsData["page_group"]]["for"] = $pageGroupsData["page_for"];
                $pageGroup[$key][$pageGroupsData["page_group"]]["template"] = $pageGroupsData["page_template"];
                $pageGroup[$key][$pageGroupsData["page_group"]]["entities"] = $pageGroupsData["entities"];
            }
        }
        return $pageGroup;
        } catch (\Exception $error) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $error->getMessage());
        }
    }
}
