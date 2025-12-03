<?php

namespace Fedex\CustomizedMegamenu\Block\Html;

use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Fedex\CatalogDocumentUserSettings\Helper\Data;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory as SharedCatalogCollectionFactory;
use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Ondemand\Helper\Ondemand;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use \Magento\SharedCatalog\Model\CategoryManagement;
use Fedex\Base\Helper\Auth as AuthHelper;

/**
 * CategoryList Block
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CategoryList extends \Magento\Framework\View\Element\Template
{
    public const BROWSE_CATALOG = 'Browse Catalog';
    public const PRINT_PRODUCTS = 'Print Products';

    /**
     * @param Context $context
     * @param CategoryFactory $categoryFactory
     * @param StoreManagerInterface $storeManager
     * @param CategoryHelper $categoryHelper
     * @param Data $helperData
     * @param Session $customerSession
     * @param SharedCatalogCollectionFactory $sharedCatalogCollectionFactory
     * @param SharedCatalogSyncQueueConfigurationRepository $sharedCatalogConfRepository
     * @param LoggerInterface $logger
     * @param SdeHelper $sdeHelper
     * @param ToggleConfig $toggleConfig
     * @param Ondemand $ondemandHelper
     * @param CatalogMvp $catalogMvp
     * @param DeliveryHelper $deliveryHelper
     * @param CategoryManagement $categoryManagement
     * @param AuthHelper $authHelper
     */

    public function __construct(
        Context $context,
        protected CategoryFactory $categoryFactory,
        protected StoreManagerInterface $storeManager,
        protected CategoryHelper $categoryHelper,
        protected Data $helperData,
        protected Session $customerSession,
        private SharedCatalogCollectionFactory $sharedCatalogCollectionFactory,
        private SharedCatalogSyncQueueConfigurationRepository $sharedCatalogConfRepository,
        private LoggerInterface $logger,
        private SdeHelper $sdeHelper,
        private ToggleConfig $toggleConfig,
        private Ondemand $ondemandHelper,
        private CatalogMvp $catalogMvp,
        private DeliveryHelper $deliveryHelper,
        private CategoryManagement $categoryManagement,
        protected AuthHelper $authHelper,
    ) {
        parent::__construct($context);
    }

    /**
     * Get data of denied category for current user group
     * @param int $sharedCatalogCategoryId
     * @param int $printProductCategoryId
     * @param boolean $isAdminUser
     * @return array
     */
    public function getDenyCategoryIds($sharedCatalogCategoryId, $printProductCategoryId, $isAdminUser)
    {

        if($isAdminUser) {
            return [];
        }

        $groupId = $this->customerSession->getCustomer()->getData('group_id');
        $categoryCollection = $this->categoryFactory->create()->getCollection()->addAttributeToSelect('*');
        $categoryCollection->addAttributeToFilter('is_active', ['eq' => '1']);
        $categoryCollection->addAttributeToFilter('path',
            [
                ['like' => '%'.$sharedCatalogCategoryId],
                ['like' => '%'.$printProductCategoryId],
                ['like' => '%'.$sharedCatalogCategoryId.'/%'],
                ['like' => '%'.$printProductCategoryId.'/%'],
            ]
        );
        $allCategoryIds = [];
        foreach($categoryCollection as $category)
        {
            $allCategoryIds[] = $category->getId();
        }
        $implodedIds = implode(',',$allCategoryIds);
        if($this->catalogMvp->isD212350FixEnabled()){
            return $this->catalogMvp->getDenyCategoryIds($implodedIds, $groupId, $sharedCatalogCategoryId);
        }
        else{
            return $this->catalogMvp->getDenyCategoryIds($implodedIds, $groupId);
        }
    }

    /**
     * Get Child Category Html
     * @param int $categoryId
     * @param array $denyCategoryIds
     * @param boolean $isAdminUser
     * @param string $companyType
     * @param boolean $isPrintProduct
     * @param array $currentcategoryPath
     * @param string $parentActive
     * @param array $companyUserGroups
     * @return string
     */
    public function getChildCategoryHtml($categoryId, $denyCategoryIds, $isAdminUser, $companyType, $isPrintProduct, $currentcategoryPath, $parentActive, $companyUserGroups, $isTopLevel = true)
    {
        $categoryCollection = $this->categoryFactory->create()->getCollection()->addAttributeToSelect('*');
        $categoryCollection->addAttributeToFilter('is_active', ['eq' => '1']);
        if(!empty($denyCategoryIds)) {
            $categoryCollection->addAttributeToFilter('entity_id', ['nin' => $denyCategoryIds]);
        }
        if(!$isAdminUser && ($companyType == "selfreg" || $companyType == "epro")) {
            $categoryCollection->addAttributeToFilter('is_publish',['eq' => 1]);
        }
        $customProductCategoryId =
                $this->toggleConfig->getToggleConfig('ondemand_setting/category_setting/epro_print_custom_product');
        $skuOnlyProductCategoryId = $this->toggleConfig->getToggleConfig('ondemand_setting/category_setting/epro_print_skuonly_product');
        $isNonStandardCatalogToggleEnable = $this->toggleConfig->getToggleConfigValue(
            'explorers_non_standard_catalog'
        );
        if($isNonStandardCatalogToggleEnable && !empty($customProductCategoryId)) {
            $categoryCollection->addAttributeToFilter('entity_id', ['nin' => [$customProductCategoryId]]);
        }
        if($isNonStandardCatalogToggleEnable && !empty( $skuOnlyProductCategoryId)){
            $categoryCollection->addAttributeToFilter('entity_id', ['nin' => [$skuOnlyProductCategoryId]]);
        }
        $categoryCollection->addFieldToFilter('parent_id', ['eq' => $categoryId]);
        $categoryCollection->setOrder('name','ASC');
        if($parentActive == "") {
            $html = '<ul style="display:none;">';
        } else {
            if($isTopLevel) {
                $html = '<ul class="top-level-parent">';
            } else {
                $html = '<ul>';
            }
        }

        foreach($categoryCollection as $category)
        {
            $productCount = $category->getProductCollection()->count();
            if(!$productCount && $isPrintProduct)
            {
                continue;
            }
            $isRestictedFoldersSyncEnabled = $this->catalogMvp->isRestictedFoldersSyncEnabled();
            if (!$isRestictedFoldersSyncEnabled) {
                $isEditFolderAccessEnabled = $this->catalogMvp->isEditFolderAccessEnabled();
                if ($isEditFolderAccessEnabled) {
                    $isCategoryRestricted = $this->catalogMvp->getCategoryPermission($category->getId(), $companyUserGroups);
                    if ($isCategoryRestricted && !$isAdminUser && $this->catalogMvp->isFolderRestrictedToUser($category->getId(), $companyUserGroups)) {
                        continue;
                    }
                }
            }
            $childrenCount = $category->getChildrenCount();
            $expend = $active = "";
            if (in_array($category->getId(), $currentcategoryPath)) {
                $active = "active";
            }
            if ($childrenCount != 0) {
                $expend = "expend-class";
                if ($isRestictedFoldersSyncEnabled) {
                    $childArr = explode(',', $category->getChildren());
                    $allowedChildren = array_diff($childArr, $denyCategoryIds);
                }
            }

            if(!$isPrintProduct) {
                $sharedCatalogFolderIconUrl = $this->getViewFileUrl('Fedex_CustomizedMegamenu::images/shared_catalog_folder_icon.png');
                $sharedCatalogChildFolderIconUrl = $this->getViewFileUrl('Fedex_CustomizedMegamenu::images/shared_catalog_child_folder_icon.png');
                $sharedCatalogClosedIconUrl = $this->getViewFileUrl('Fedex_CustomizedMegamenu::images/shared_catalog_closed_icon.png');
                $sharedCatalogOpenIconUrl = $this->getViewFileUrl('Fedex_CustomizedMegamenu::images/shared_catalog_open_icon.png');

                $folderIconUrl = $sharedCatalogFolderIconUrl;
                if ($childrenCount != 0 &&
                    (($isRestictedFoldersSyncEnabled && $allowedChildren) || !$isRestictedFoldersSyncEnabled)) {
                    if($isTopLevel) {
                        $subParent = "";
                    } else {
                        $subParent = "sub-parent";
                        $folderIconUrl = $sharedCatalogChildFolderIconUrl;
                    }
                    $html .= '<li class="shared-catalog-list '.$subParent.'"><p><img alt="has-child-image" class="has-child-image" src="'.$sharedCatalogClosedIconUrl.'">
                                          <img alt="catalog-folder-icon" class="catalog-folder-icon" src="'.$folderIconUrl.'">
                                          <a class="shared-catalog-folder-name" href="' . $category->getUrl() . '">'.$category->getName().'</a><span class="shared-catalog-hover-folder-name">'.$category->getName().'</span>';
                    $html .= '</p>';
                    $html .= $this->getChildCategoryHtml($category->getId(), $denyCategoryIds,$isAdminUser, $companyType, $isPrintProduct, $currentcategoryPath, $active, $companyUserGroups, false);
                } else {
                    if(!$isTopLevel) {
                        $folderIconUrl = $sharedCatalogChildFolderIconUrl;
                        $html .= '<li class="shared-catalog-list"><p style="margin-left: 5.5rem;"><img alt="catalog-folder-icon" class="catalog-folder-icon" src="'.$sharedCatalogChildFolderIconUrl.'">
                                <a class="shared-catalog-folder-name" href="' . $category->getUrl() . '">'.$category->getName().'</a><span class="shared-catalog-hover-folder-name">'.$category->getName().'</span></p>';
                    } else {
                        $html .= '<li class="shared-catalog-list"><p style="margin-left: 2.5rem;"><img alt="catalog-folder-icon" class="catalog-folder-icon" src="'.$sharedCatalogFolderIconUrl.'">
                                <a class="shared-catalog-folder-name" href="' . $category->getUrl() . '">'.$category->getName().'</a><span class="shared-catalog-hover-folder-name">'.$category->getName().'</span></p>';
                    }
                }
                $html .= '</li>';
            } else {
                    if (!$this->deliveryHelper->toggleEnableIcons()) {
                        $html .= '<li class="'.$active.'"><p>';
                        $html .= '<a href="' . $category->getUrl() . '">
                                ' . $category->getName() . '
                            </a>';
                        if ($expend) {
                            $html .= '<i class="'.$expend.'"></i>';
                        }
                        $html .= '</p>';

                        if($childrenCount != 0) {
                            $html .= $this->getChildCategoryHtml($category->getId(), $denyCategoryIds,$isAdminUser, $companyType, $isPrintProduct, $currentcategoryPath, $active, $companyUserGroups);
                        }
                        $html .= '</li>';
                    }

                if ($this->deliveryHelper->toggleEnableIcons()) {
                    $iconUrls = [
                        'folder' => $this->getViewFileUrl('Fedex_CustomizedMegamenu::images/shared_catalog_folder_icon.png'),
                        'childFolder' => $this->getViewFileUrl('Fedex_CustomizedMegamenu::images/shared_catalog_child_folder_icon.png'),
                        'closedIcon' => $this->getViewFileUrl('Fedex_CustomizedMegamenu::images/shared_catalog_closed_icon.png'),
                    ];

                    $html = $this->makeMenuConditions($iconUrls, $childrenCount, $isTopLevel, $html, $category);

                    if ($childrenCount != 0) {
                        $html .= $this->getChildCategoryHtml($category->getId(), $denyCategoryIds, $isAdminUser, $companyType, $isPrintProduct, $currentcategoryPath, $active, $companyUserGroups, false);
                    }

                    $html .= '</li>';
                }
            }
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     * @param array $iconUrls
     * @param $childrenCount
     * @param mixed $isTopLevel
     * @param string $html
     * @param mixed $category
     * @return string
     */
    public function makeMenuConditions(
        array $iconUrls,
        $childrenCount,
        mixed $isTopLevel,
        string $html,
        mixed $category
    ): string  {
        $folderIconUrl = $iconUrls['folder'];
        $subParentClass = ($childrenCount != 0 && !$isTopLevel) ? 'sub-parent' : '';

        $html .= '<li class="shared-catalog-list ' . $subParentClass . '">';
        $html .= '<p style="margin-left: ' . ($isTopLevel ? '1.5rem' : '3.5rem') . ';">';
        $html .= '<img alt="has-child-image" class="has-child-image" src="' . ($childrenCount != 0 ? $iconUrls['closedIcon'] : '') . '">';
        $html .= '<img alt="catalog-folder-icon" class="catalog-folder-icon" src="' . $folderIconUrl . '">';
        $html .= '<a class="shared-catalog-folder-name" href="' . $category->getUrl() . '">' . $category->getName() . '</a>';
        $html .= '<span class="shared-catalog-hover-folder-name">' . $category->getName() . '</span>';
        $html .= '</p>';
        return $html;
    }

    /**
     * Get current store left navigation categories tree
     *
     * @return string
     */
    public function getLeftNavigationCategories()
    {
        if ($this->deliveryHelper->isCommercialCustomer()) {

            $html = '<div class="category-tree"><ul>';
            $printProductCategoryId = 0;
            $sharedCatalogCategoryId = 0;
            $categoryName = [];
            $companyConfiguration = $this->helperData->getCompanyConfiguration();
            $allowOwnDocument = (bool) $companyConfiguration->getAllowOwnDocument();
            $allowSharedCatalog = (bool) $companyConfiguration->getAllowSharedCatalog();
            $isAdminUser = $this->catalogMvp->isSharedCatalogPermissionEnabled();
            $isEditFolderAccessEnabled = $this->catalogMvp->isEditFolderAccessEnabled();
            if ($isEditFolderAccessEnabled) {
                $companyUserGroups = $this->catalogMvp->getUserGroupsforCompany();
            } else {
                $companyUserGroups = [];
            }
            $customerCompanyInfo = $this->customerSession->getOndemandCompanyInfo();
            $companyType = $customerCompanyInfo['company_type'] ?? "";
            $currentCategory = $this->catalogMvp->getCurrentCategory();
            $currentcategoryPath = [];
            if (is_object($currentCategory)) {
                $currentcategoryPath = $currentCategory->getPath();
                $currentcategoryPath = explode("/",$currentcategoryPath);
            }
            if($currentCategory && $currentCategory->getId() > 0)
            {
                $currentCurrentId = $currentCategory->getId();
                $currentCurrentId;
            }

            if ($allowSharedCatalog) {
                $sharedCatalogCategoryId = $this->getCommericalSharedCatalogCategory();
                $categoryName[$sharedCatalogCategoryId] = "Shared Catalog";
            }
            $denyCategoryIds = $this->getDenyCategoryIds($sharedCatalogCategoryId, $printProductCategoryId, $isAdminUser);

            $categoryCollection = $this->categoryFactory->create()->getCollection()->addAttributeToSelect('*');
            $categoryCollection->addAttributeToFilter('is_active', ['eq' => '1']);
            $categoryCollection->addAttributeToFilter('entity_id', ['in' => [$sharedCatalogCategoryId, $printProductCategoryId]]);
            if(!$isAdminUser && ($companyType == "selfreg" || $companyType == "epro"))  {
                $categoryCollection->addAttributeToFilter('is_publish',['eq' => 1]);
            }
            if(!empty($denyCategoryIds)) {
                $categoryCollection->addAttributeToFilter('entity_id', ['nin' => $denyCategoryIds]);
            }
            $categoryCollection->setOrder('position', 'ASC');

            foreach ($categoryCollection as $category) {
                $isPrintProduct = false;
                $childrenCount = $category->getChildrenCount();
                $expend = $active = "";

                if (in_array($category->getId(), $currentcategoryPath)) {
                    $active = "active";
                }
                if ($childrenCount != 0) {
                    $expend = "expend-class";
                }
                $displayCategoryName = $categoryName[$category->getId()] ?? $category->getName();
                $html .= '<li class="'.$active.'"><p>';
                $html .= '<a href="' . $category->getUrl() . '">
                        ' . $displayCategoryName . '
                    </a>';
                if ($expend) {
                    $html .= '<i class="'.$expend.'"></i>';
                }
                $html .= '</p>';
                if ($childrenCount != 0) {
                    if($category->getId() == $printProductCategoryId) {
                        $isPrintProduct = true;
                    }
                    $html .= $this->getChildCategoryHtml($category->getId(), $denyCategoryIds, $isAdminUser, $companyType, $isPrintProduct, $currentcategoryPath, $active, $companyUserGroups);
                }
                $html .= '</li>';
            }
            $html .= '</ul></div>';
            return $html;
        }


        $storeId = $this->storeManager->getStore()->getStoreId();

        // check if store id is exit or not and by default set store id is 0
        if (!$storeId) {
            $collection = $this->categoryFactory->create()
                ->getCollection()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('include_in_menu', ['eq' => 1])
                ->addFieldToFilter('is_active', ['eq' => 1])
                ->setOrder('entity_id', 'ASC');
        } else {

            $rootId = $this->storeManager->getStore($storeId)->getRootCategoryId();
            $companyRootCategories = $this->getCompanyCategoriesRootIds($rootId, $storeId);
            $collection = $this->categoryFactory->create()
                ->getCollection()
                ->setStoreId($storeId)
                ->addAttributeToSelect('*')
                ->addFieldToFilter('include_in_menu', ['eq' => 1])
                ->addFieldToFilter('is_active', ['eq' => 1]);
            if (!empty($companyRootCategories['browse_catalog_root_id'])
                && empty($companyRootCategories['print_product_root_id'])) {
                $collection->addFieldToFilter(
                    'path',
                    ['like' => '1/' . $rootId . '/' . $companyRootCategories['browse_catalog_root_id'] .'%']
                );
            }
            if (empty($companyRootCategories['browse_catalog_root_id'])
                && !empty($companyRootCategories['print_product_root_id'])) {
                $collection->addFieldToFilter(
                    'path',
                    ['like' => '1/' . $rootId . '/' . $companyRootCategories['print_product_root_id'] .'%']
                );
            }
            if (!empty($companyRootCategories['browse_catalog_root_id'])
                && !empty($companyRootCategories['print_product_root_id'])) {
                $collection->addFieldToFilter('path', [
                        ['like' => '1/' . $rootId . '/' . $companyRootCategories['browse_catalog_root_id'] .'%'],
                        ['like' => '1/' . $rootId . '/' . $companyRootCategories['print_product_root_id'] .'%']
                ]);
            }
            $collection->setOrder('position','ASC');
        }
        if ($this->authHelper->isLoggedIn()) {
            $parentCategory = '<ul>';
            if (!empty($collection)) {
                $parentCategory .= $this->menuCatItemsIteration($collection);
            }
            $parentCategory .= '</ul>';

            return '<div class="category-tree">'.$parentCategory.'</div>';
        }
    }

    /**
     * MenuCatItemsIteration
     *
     * @param object $collection
     * @return string $html
     */
    public function menuCatItemsIteration($collection)
    {
        $parentCategory = '';
        foreach ($collection as $_category) {
            $childLevel = $_category->getLevel();
            if ($childLevel >= 2) {
                $childLevel = $childLevel -2;
            }
            /** Comment this code for testing purpose if every thing will work will remove commented code */
            $isShowPrintProduct = $this->ondemandHelper->isProductAvailable($_category, $childLevel);
            if (!$isShowPrintProduct) {
                continue;
            }
            $isPublish = $this->ondemandHelper->isPublishCategory($_category, $childLevel);
            if (!$isPublish) {
                continue;
            }
            $categoryName = ucwords(trim($_category->getName()));
            if (strpos($categoryName, self::PRINT_PRODUCTS) !== false) {
                $categoryName = self::PRINT_PRODUCTS;
            }

            $categoryData = $this->checkIfBrowsePrintCat($_category, $categoryName);

            if (!empty($categoryData['loop_continue'])) {
                continue;
            }

            $parentCategory .= $categoryData['parent_category_html'];
        }

        return $parentCategory;
    }

    /**
     * checkIfBrowseCat
     *
     * @param string $categoryName
     * @return string $categoryName
     */
    public function checkIfBrowseCat($categoryName)
    {
        $companyCatName = $this->catalogMvp->getCompanySharedCatName();
        $sharedCatalogChnagesStatus = 0;

        if ($companyCatName) {
            if ($companyCatName == $categoryName) {
                $sharedCatalogChnagesStatus = 1;
            }
        }
        if ($categoryName === self::BROWSE_CATALOG ||
        strpos($categoryName, self::BROWSE_CATALOG) !== false || $sharedCatalogChnagesStatus) {
            if ($this->catalogMvp->isMvpSharedCatalogEnable()) {
                return $categoryName = 'Shared Catalog';
            }
            return $categoryName = self::BROWSE_CATALOG;
        }

        return $categoryName;
    }

    /**
     * checkIfBrowsePrintCat disabled then continue loop
     *
     * @param string $categoryName
     * @return string $categoryName
     */
    public function checkIfBrowsePrintCat($category, $categoryName)
    {
        $continueLoop = false;
        $parentCategory = '';
        $isPrintProducts = false;
        $companyCatName = $this->catalogMvp->getCompanySharedCatName();
        $sharedCatalogChnagesStatus = 0;

        if ($companyCatName) {
            if ($companyCatName == $categoryName) {
                $sharedCatalogChnagesStatus = 1;
            }
        }

        if ((strpos($categoryName, self::BROWSE_CATALOG) !== false ||
            $sharedCatalogChnagesStatus || $categoryName === self::PRINT_PRODUCTS ||
             strpos($categoryName, self::PRINT_PRODUCTS) !== false) && $category->getLevel() == '2') {
            if(strpos($categoryName, self::PRINT_PRODUCTS) !== false){
                $isPrintProducts = true;
            }
            $subCategory = $this->getChildCategories($category , $isPrintProducts);
            $companyConfiguration = $this->helperData->getCompanyConfiguration();
            $allowOwnDocument = (bool) $companyConfiguration->getAllowOwnDocument();
            $allowSharedCatalog = (bool) $companyConfiguration->getAllowSharedCatalog();
            $isBrowseCatDisabled = (($categoryName === self::BROWSE_CATALOG
            || strpos($categoryName, self::BROWSE_CATALOG) !== false)
            && !$allowSharedCatalog);
            // check expression if Browse product cat disabled from company setting
            $isPrintProductsCatDisabled = (($categoryName === self::PRINT_PRODUCTS ||
            strpos($categoryName, self::PRINT_PRODUCTS) !== false) && !$allowOwnDocument);

            if ($isPrintProductsCatDisabled || $isBrowseCatDisabled
            ) {
                $continueLoop = true;
            }
            // add caret icon if children category is available
            $expendArrow = $this->addCarretIcon($category->hasChildren());
            $categoryName = $this->checkIfBrowseCat($categoryName);
            $activeClass = $this->canOpenCategoryFilterByDefault($category->getId()) ? 'active' : null;
            $parentCategory .= '<li class="'.$activeClass.'"><p>
            <a href="'.$this->categoryHelper->getCategoryUrl($category).'">'
            .$categoryName.'</a>'.$expendArrow.'</p>';
            $parentCategory .= $subCategory;
            $parentCategory .= '</li>';
        }

        return [
            'loop_continue' => $continueLoop,
            'parent_category_html' => $parentCategory
        ];
    }

    public function getCommericalSharedCatalogCategory() {
        $companyConfiguration = $this->helperData->getCompanyConfiguration();
        if(is_object($companyConfiguration)) {
            return $companyConfiguration->getData('shared_catalog_id');
        }
        return false;
    }

    /**
     * Get Company Categories Root Ids
     *
     * @param int $rootId
     *
     * @return array
     */
    protected function getCompanyCategoriesRootIds($rootId, $storeId)
    {
        $browseCatalogCategoryId = $printProductsCategoryId = null;

        if ($this->authHelper->isLoggedIn()) {
            $customerGroupId = $this->customerSession->getCustomer()->getGroupId();
            $collection = $this->sharedCatalogCollectionFactory->create();
            $collection->addFieldToFilter('customer_group_id', ['eq' => $customerGroupId]);

            if(!$collection->getSize()) {
                $parentGroupId = $this->catalogMvp->getParentGroupId($customerGroupId);
                $collection = $this->sharedCatalogCollectionFactory->create();
                $collection->addFieldToFilter('customer_group_id', ['eq' => $parentGroupId]);
            }

            // To ensure if company is created with shared catalog or not
            if ($collection->getSize()) {
                $sharedCatalog = $collection->getFirstItem();
                $id = $sharedCatalog->getId();
                $isEproCustomer = $this->deliveryHelper->isEproCustomer();
                $browseCatalogCategoryId = $this->getCommericalSharedCatalogCategory();
                if(!$browseCatalogCategoryId) {
                    if($isEproCustomer) {
                        try {
                            $sharedCatalogConfData = $this->sharedCatalogConfRepository->getBySharedCatalogId($id);
                            $browseCatalogCategoryId = $sharedCatalogConfData->getStatus() ?
                                                            $sharedCatalogConfData->getCategoryId() : null;
                        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                            ' Error under Left Menu Category fetch: ' . $exception->getMessage());
                        }
                    } else {
                        $categories = $this->categoryManagement->getCategories($id);
                        $categoryCollection = $this->categoryFactory->create()
                            ->getCollection()
                            ->setStoreId($storeId)
                            ->addAttributeToSelect('*')
                            ->addFieldToFilter('include_in_menu', ['eq' => 1])
                            ->addFieldToFilter('level', 2)
                            ->addFieldToFilter('entity_id', ['in' => $categories])
                            ->addFieldToFilter('is_active', ['eq' => 1])
                            ->addFieldToFilter('name', ['neq' => self::PRINT_PRODUCTS])
                            ->addFieldToFilter(
                                'path',
                                ['like' => '1/' . $rootId . '/%']
                            );
                        $categoryCollection->getFirstItem();
                        if(is_object($categoryCollection) && is_array($categoryCollection->getData())) {
                            $browseCatalogCategoryId = $categoryCollection->getData()['0']['entity_id'] ?? null;
                        }
                    }
                }
            }

            $collection = $this->categoryFactory->create()
                ->getCollection()
                ->addFieldToFilter('name', ['eq' => self::PRINT_PRODUCTS])
                ->addFieldToFilter('path', ['like' => '1/' . $rootId .'/%']);

            if ($collection->getSize()) {
                $printProductsCategoryId = $collection->getFirstItem()->getId();
            }
        }

        return [
            'browse_catalog_root_id' => $browseCatalogCategoryId,
            'print_product_root_id' => $printProductsCategoryId
        ];
    }

    /**
     * Get children category
     *
     * @param object $category
     * @param boolean $isPrintProducts
     *
     * @return object
     */
    protected function getChildCategories($category, $isPrintProducts)
    {
        if (!$category->hasChildren()) {
            return null;
        }

        $currentCategory = $this->catalogMvp->getCurrentCategory();
        $childCategory = $this->canOpenCategoryFilterByDefault($category->getId())
                            ? '<ul>'
                            :'<ul style="display:none;">';

        $sortedChildCategories = $this->sortedCategories($category->getChildrenCategories());
        $isCustomerAdmin = $this->catalogMvp->isSelfRegCustomerAdmin();
        foreach ($sortedChildCategories as $childrenCategory) {
            $isFolderPermissionAllowed = $this->catalogMvp->isFolderPermissionAllowed($childrenCategory->getId());
            if (!$isFolderPermissionAllowed && !$isPrintProducts && !$isCustomerAdmin) {
                continue;
            }
            /*B-1598909 : RT-ECVS-Feedback-Do not show Print Product Category, if product not available*/
            $childLevel = ($childrenCategory->getLevel() - 2);
            /** Comment this code for testing purpose if every thing will work will remove commented code */
            $isShowPrintProduct = $this->ondemandHelper->isProductAvailable($childrenCategory, $childLevel);
            if(!$isShowPrintProduct){
                continue;
            }
            $isPublish = $this->ondemandHelper->isPublishCategory($childrenCategory, $childLevel);
            if (!$isPublish) {
                continue;
            }

            // SDE active category should be highlighted.
            $activeClass = '';
            $additionalClass = $this->canOpenCategoryFilterByDefaultForChild($childrenCategory->getId());
            if ($this->sdeHelper->getIsSdeStore() &&
            ($currentCategory && $currentCategory->getId() == $childrenCategory->getId())) {
                $activeClass = 'class="selected-category"';
            }
            else if($additionalClass == "active") {
                $activeClass = 'class="active"';
            }
            else if($additionalClass == "expand") {
                $activeClass = 'class="expand"';
            }
            $subCategory = $this->getChildCategories($childrenCategory ,$isPrintProducts);
            $expendArrow = $this->addCarretIcon($childrenCategory->hasChildren());

            $childCategory .= '<li '.$activeClass.'><p>
                <a href="'.$this->categoryHelper->getCategoryUrl($childrenCategory)
                .'">'.$childrenCategory->getName().'</a>'.$expendArrow;

            $childCategory .= $subCategory;
            $childCategory .= '</li>';
        }
        $childCategory .= '</ul>';
        return $childCategory;
    }

    /**
     * Add carret icon
     *
     * @param boolen $hasCategoryChildren
     * @return string
     */
    public function addCarretIcon($hasCategoryChildren)
    {
        if ($hasCategoryChildren) {
            return "<i class='expend-class'></i>";
        }

        return null;
    }

    /**
     * Get SortedCategories
     *
     * @param  object $categories
     * @return object
     */
    protected function sortedCategories($categories)
    {
        $catArray = [];
        foreach ($categories as $category) {
            $catArray[$category->getName()] = $category;
        }

        ksort($catArray, SORT_NATURAL | SORT_FLAG_CASE);
               $specialArray = [];
        foreach ($catArray as $key => $val) {
            if (!empty($key[0]) && $key[0] == '@') {
                $specialArray[$key] = $val;
            }
        }
        if (!empty($specialArray)) {
            foreach ($specialArray as $key => $val) {
                unset($catArray[$key]);
            }
            $catArray = $specialArray + $catArray;
        }
        return $catArray;
    }

    /**
     * Check if category filter can be opened by default
     * For SDE it should be open by default
     *
     * @return bool
     */
    public function canOpenCategoryFilterByDefault($categoryId = null)
    {
        if ($this->sdeHelper->getIsSdeStore()) {
            return $this->sdeHelper->getIsSdeStore();
        } else if($categoryId){
            $currentCategory = $this->catalogMvp->getCurrentCategory();
            if (is_object($currentCategory)) {
                $categoryPath = $currentCategory->getPath();
                $categoryPath = explode("/",$categoryPath);
                if (in_array($categoryId, $categoryPath)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function canOpenCategoryFilterByDefaultForChild($categoryId = null)
    {
        if($categoryId){
            $currentCategory = $this->catalogMvp->getCurrentCategory();
            if (is_object($currentCategory)) {
                $categoryPath = $currentCategory->getPath();
                $categoryPath = explode("/",$categoryPath);
                $categoryLast = end($categoryPath);
                if ($categoryId == $categoryLast) {
                    return "active";
                }
                else if (in_array($categoryId, $categoryPath)) {
                    return "expand";
                }
            }
        }
        return false;
    }

    /*B-1598909 : RT-ECVS-Feedback-Do not show Print Product Category, if product not available*/
    /**
     * @return Ondemand
     */
    public function getOndemandHelper(): Ondemand
    {
        return $this->ondemandHelper;
    }
}
