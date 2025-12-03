<?php

/**
 * Fedex
 * Copyright (C) 2021 Fedex <info@fedex.com>
 *
 * PHP version 7
 *
 * @category  Fedex
 * @package   Fedex_CustomizedMegamenu
 * @author    Fedex <info@fedex.com>
 * @copyright 2006-2021 Fedex (http://www.fedex.com/)
 * @license   http://opensource.org/licenses/gpl-3.0.html
 * GNU General Public License,version 3 (GPL-3.0)
 * @link      http://fedex.com
 */

declare(strict_types=1);

namespace Fedex\CustomizedMegamenu\Block\Html;

use Fedex\Ondemand\Api\Data\ConfigInterface as OndemandConfigInterface;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Data\Tree\Node\Collection;
use Magento\Framework\Data\Tree\NodeFactory;
use Magento\Framework\Data\TreeFactory;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magedelight\Megamenu\Block\Topmenu as MegaMenuTopmenu;
use Magedelight\Megamenu\Model\Menu;
use Magento\Framework\Registry;
use Fedex\CatalogDocumentUserSettings\Helper\Data as HelperData;
use Magento\Customer\Model\SessionFactory;
use Magento\Cms\Model\Page;
use Magedelight\Megamenu\Helper\Data;
use Magedelight\Megamenu\Model\MegamenuManagement;
use Magento\Catalog\Helper\Output;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Ondemand\Helper\Ondemand;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\Base\Helper\Auth as AuthHelper;
use Magento\Customer\Model\Session;
use Fedex\CustomizedMegamenu\Model\MenuTreeManagement;

/**
 * Class Topmenu Block
 *
 * @category  Fedex
 * @package   Fedex_CustomizedMegamenu
 * @author    Fedex <info@fedex.com>
 * @copyright 2006-2021 Fedex (http://www.fedex.com/)
 * @license   http://opensource.org/licenses/gpl-3.0.html
 * GNU General Public License,version 3 (GPL-3.0)
 * @link      http://fedex.com
 */
class Topmenu extends MegaMenuTopmenu
{
    const MEGA_MENU_TEMPLATE = 'Fedex_CustomizedMegamenu::menu/new-topmenu.phtml';
    public const BROWSE_CATALOG = 'Browse Catalog';
    public const PRINT_PRODUCTS = 'Print Products';
    public const ANCHOR_ELEMENT = '<a href="';
    public const SPAN_OPEN_ELEMENT = '><span>';
    public const CLOSE_SPAN_ANCHOR = '</span></a>';
    public const LI_CLOSE_ELEMENT = '</li>';
    public const TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE = 'nfr_catelog_performance_improvement_phase_one';


    /**
     * {@inheritdoc}
     *
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * {@inheritdoc}
     *
     * @var TreeFactory
     */
    protected $treeFactory;

    /**
     * {@inheritdoc}
     *
     * @param Template\Context $context
     * @param NodeFactory $nodeFactory
     * @param TreeFactory $treeFactory
     * @param Registry $registry
     * @param SessionFactory $session
     * @param Page $page
     * @param Data $helper
     * @param MegamenuManagement $megamenuManagement
     * @param Output $output
     * @param HelperData $helperData
     * @param \Fedex\Delivery\Helper\Data $deliveryHelper
     * @param \Fedex\Orderhistory\Helper\Data $orderHistoryDataHelper
     * @param \Fedex\SDE\Helper\SdeHelper $sdeHelper
     * @param CatalogMvp $catalogMvpHelper
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param ToggleConfig $toggleConfig
     * @param Ondemand $ondemandHelper
     * @param Session $customerSession
     * @param \Magento\Search\ViewModel\ConfigProvider $configProvider
     * @param \Magento\Search\ViewModel\AdditionalSearchFormData $additionalSearchFormData
     * @param AuthHelper $authHelper
     * @param MenuTreeManagement $menuTreeManagement
     * @param OndemandConfigInterface $ondemandConfig
     * @param array $data
     */
    public function __construct(
        Template\Context                                                    $context,
        NodeFactory                                                         $nodeFactory,
        TreeFactory                                                         $treeFactory,
        Registry                                                            $registry,
        SessionFactory                                                      $session,
        Page                                                                $page,
        Data                                                                $helper,
        MegamenuManagement                                                  $megamenuManagement,
        Output                                                              $output,
        public HelperData                                                   $helperData,
        public \Fedex\Delivery\Helper\Data                                  $deliveryHelper,
        public \Fedex\Orderhistory\Helper\Data                              $orderHistoryDataHelper,
        protected \Fedex\SDE\Helper\SdeHelper                               $sdeHelper,
        private \Fedex\CatalogMvp\Helper\CatalogMvp                         $catalogMvpHelper,
        protected \Magento\Catalog\Model\CategoryFactory                    $categoryFactory,
        private ToggleConfig                                                $toggleConfig,
        private Ondemand                                                    $ondemandHelper,
        Session                                                             $customerSession,
        private readonly \Magento\Search\ViewModel\ConfigProvider           $configProvider,
        private readonly \Magento\Search\ViewModel\AdditionalSearchFormData $additionalSearchFormData,
        protected AuthHelper                                                $authHelper,
        protected MenuTreeManagement                                        $menuTreeManagement,
        private readonly OndemandConfigInterface                            $ondemandConfig,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $nodeFactory,
            $treeFactory,
            $registry,
            $session,
            $page,
            $helper,
            $megamenuManagement,
            $output,
            $data
        );
        $this->customerSession = $customerSession;
        $this->session = $session;
    }

    /**
     * Disable cache for commerical user
     * @codeCoverageIgnore
     */
    protected function getCacheLifetime()
    {
        if ($this->catalogMvpHelper->isMvpSharedCatalogEnable()) {
            return 0;
        }
        return 3600;
    }

    /**
     * Get data of denied category for current user group
     * @param int $sharedCatalogCategoryId
     * @param int|array $printProductCategoryId
     * @param boolean $isAdminUser
     * @return array
     */
    public function getDenyCategoryIds($sharedCatalogCategoryId, $printProductCategoryId, $isAdminUser)
    {

        if($isAdminUser) {
            return [];
        }

        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $groupId = $this->getOrCreateCustomerSession()->getCustomer()->getData('group_id');
        } else {
            $groupId = $this->session->create()->getCustomer()->getData('group_id');
        }

        if ($this->ondemandConfig->isTigerD239305ToggleEnabled() && is_array($printProductCategoryId)) {

            $attributeFilter = [
                ['like' => '%'.$sharedCatalogCategoryId],
                ['like' => '%'.$sharedCatalogCategoryId.'/%']
            ];
            foreach ($printProductCategoryId as $categoryId) {
                $attributeFilter[] = ['like' => '%'.$categoryId];
                $attributeFilter[] = ['like' => '%'.$categoryId.'/%'];
            }
        } else {

            $attributeFilter = [
                ['like' => '%'.$sharedCatalogCategoryId],
                ['like' => '%'.$printProductCategoryId],
                ['like' => '%'.$sharedCatalogCategoryId.'/%'],
                ['like' => '%'.$printProductCategoryId.'/%'],
            ];
        }

        $categoryCollection = $this->categoryFactory->create()->getCollection()->addAttributeToSelect('*');
        $categoryCollection->addAttributeToFilter('is_active', ['eq' => '1']);
        $categoryCollection->addAttributeToFilter('path', $attributeFilter);
        $allCategoryIds = [];
        foreach($categoryCollection as $category)
        {
            $allCategoryIds[] = $category->getId();
        }
        $implodedIds = implode(',',$allCategoryIds);
        if ($this->catalogMvpHelper->isRestictedFoldersSyncEnabled()) {
            return $this->catalogMvpHelper->getDenyCategoryIds($implodedIds, $groupId, $sharedCatalogCategoryId);
        } else {
            return $this->catalogMvpHelper->getDenyCategoryIds($implodedIds, $groupId);
        }
    }

    /**
     * Get Child Category Html
     * @param int $categoryId
     * @param string $node
     * @param int $parentLevel
     * @param array $denyCategoryIds
     * @param boolean $isAdminUser
     * @param string $companyType
     * @param boolean $isPrintProduct
     * @return string
     */
    public function getChildCategoryHtml($categoryId, $node, $parentLevel, $denyCategoryIds, $isAdminUser, $companyType, $isPrintProduct)
    {
        $categoryCollection = $this->categoryFactory->create()->getCollection()->addAttributeToSelect('*');
        $categoryCollection->addAttributeToFilter('is_active', ['eq' => '1']);
        if(!empty($denyCategoryIds)) {
            $categoryCollection->addAttributeToFilter('entity_id', ['nin' => $denyCategoryIds]);
        }
        if(!$isAdminUser && ($companyType == "selfreg" || $companyType == 'epro'))  {
            $categoryCollection->addAttributeToFilter('is_publish',['eq' => 1]);
        }
        $customProductCategoryId =
                $this->toggleConfig->getToggleConfig('ondemand_setting/category_setting/epro_print_custom_product');
        $skuOnlyProductCategoryId =
                $this->toggleConfig->getToggleConfig('ondemand_setting/category_setting/epro_print_skuonly_product');
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
        $counter = 0;
        $countCategory = $categoryCollection->count();
        $html = '<ul class="level'.$parentLevel.' submenu">';
        foreach($categoryCollection as $category)
        {
            $productCount = $category->getProductCollection()->count();
            if(!$productCount && $isPrintProduct)
            {
                continue;
            }
            $childrenCount = $category->getChildrenCount();
            $level = (int) $category->getLevel();
            $level = $level - 2;
            $parent = "";
            $first = "";
            $last = "";
            if($counter == 0)
            {
                $first = "first";
            }
            $counter++;
            if($counter == $countCategory)
            {
                $last = "last";
            }
            if($childrenCount != 0)
            {
                $parent = "parent";
            }
            $nav = $node."-".$counter;
            $html .= '<li class="level'.$level.' '.$nav.' category-item '.$first.' '.$parent.' '.$last.'">';
            $html .= '<a href="'.$category->getUrl().'" class="">
                        <span>'.$category->getName().'</span>
                    </a>';
            if($childrenCount != 0) {
                $html .= $this->getChildCategoryHtml($category->getId(), $nav, $level, $denyCategoryIds,$isAdminUser, $companyType, $isPrintProduct);
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }
    /**
     * Megamenu html
     *
     * @param $outermostClass    outermostClass
     * @param $childrenWrapClass childrenWrapClass
     * @param $limit             limit
     *
     * @return string
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMegaMenuHtml($outermostClass, $childrenWrapClass, $limit)
    {
        if ($this->deliveryHelper->isCommercialCustomer()) {
            $html = "";
            $printProductCategoryId = 0;
            $b2bGeneralCategories = [];
            $sharedCatalogCategoryId = 0;
            $categoryName = [];
            $companyConfiguration = $this->helperData->getCompanyConfiguration();
            $allowOwnDocument = (bool) $companyConfiguration->getAllowOwnDocument();
            $allowSharedCatalog = (bool) $companyConfiguration->getAllowSharedCatalog();
            $isAdminUser = $this->catalogMvpHelper->isSharedCatalogPermissionEnabled();

            if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                $customerCompanyInfo = $this->getOrCreateCustomerSession()->getOndemandCompanyInfo();
            } else {
                $customerCompanyInfo = $this->session->create()->getOndemandCompanyInfo();
            }

            $companyType = $customerCompanyInfo['company_type'] ?? "";
            if ($allowOwnDocument) {
                if ($this->ondemandConfig->isTigerD239305ToggleEnabled()) {
                    $printProductCategoryId = $this->ondemandConfig->getB2bPrintProductsCategory();
                    if ($printProductCategoryId !== null) {
                        $b2bGeneralCategories[] = $printProductCategoryId;
                        $categoryName[$printProductCategoryId] = "Print Products";
                    }

                    $officeSuppliesCategoryId = $this->ondemandConfig->getB2bOfficeSuppliesCategory();
                    if ($officeSuppliesCategoryId !== null && $companyConfiguration->getOfficeSuppliesEnabled()) {
                        $b2bGeneralCategories[] = $officeSuppliesCategoryId;
                        $categoryName[$officeSuppliesCategoryId] =
                            $this->ondemandConfig->getB2bOfficeSuppliesCategoryLabel() ?: "Office Supplies";
                    }

                    $spmSuppliesCategoryId = $this->ondemandConfig->getB2bSPMSuppliesCategory();
                    if ($spmSuppliesCategoryId !== null && $companyConfiguration->getShippingPackingMailingEnabled()) {
                        $b2bGeneralCategories[] = $spmSuppliesCategoryId;
                        $categoryName[$spmSuppliesCategoryId] = $this->ondemandConfig->getB2bSPMSuppliesCategoryLabel()
                            ?: "Shipping & Mailing Supplies";
                    }
                } else {
                    $printProductCategoryId = $this->toggleConfig->getToggleConfig("ondemand_setting/category_setting/epro_print");
                    $categoryName[$printProductCategoryId] = "Print Products";
                }
            }
            if ($allowSharedCatalog) {
                $sharedCatalogCategoryId = $this->getCommericalSharedCatalogCategory();
                $categoryName[$sharedCatalogCategoryId] = "Shared Catalog";
            }

            //$b2bGeneralCategories will be array of category ids IF Tiger D-239305 is enabled otherwise it will be empty
            $finalCategoriesList = (!empty($b2bGeneralCategories) ? $b2bGeneralCategories : $printProductCategoryId);

            $denyCategoryIds = $this->getDenyCategoryIds(
                $sharedCatalogCategoryId,
                $finalCategoriesList,
                $isAdminUser
            );
            if($this->toggleConfig->getToggleConfigValue('tiger_d_217896')){
                return $this->menuTreeManagement->renderMegaMenuHtmlOptimized(
                    $outermostClass,
                    $childrenWrapClass,
                    $limit,
                    $sharedCatalogCategoryId,
                    $finalCategoriesList,
                    $isAdminUser,
                    $denyCategoryIds,
                    $categoryName
                );
            }
            $categoryCollection = $this->categoryFactory->create()->getCollection()->addAttributeToSelect('*');
            $categoryCollection->addAttributeToFilter('is_active', ['eq' => '1']);
            $categoryCollection->addAttributeToFilter('entity_id', ['in' => [$sharedCatalogCategoryId, $printProductCategoryId]]);
            if(!$isAdminUser && $companyType == "selfreg")  {
                $categoryCollection->addAttributeToFilter('is_publish',['eq' => 1]);
            }
            if(!empty($denyCategoryIds)) {
                $categoryCollection->addAttributeToFilter('entity_id', ['nin' => $denyCategoryIds]);
            }
            $categoryCollection->setOrder('position', 'ASC');
            //echo $categoryCollection->getSelect()->__toString();exit;
            $counter = 0;
            $countCategory = $categoryCollection->count();

            foreach ($categoryCollection as $category) {
                $isPrintProduct = false;
                $childrenCount = $category->getChildrenCount();
                $level = (int) $category->getLevel();
                $level = $level - 2;
                $parent = "";
                $first = "";
                $last = "";
                if ($counter == 0) {
                    $first = "first";
                }
                $counter++;
                if ($counter == $countCategory) {
                    $last = "last";
                }
                if ($childrenCount != 0) {
                    $parent = "parent";
                }
                $displayCategoryName = $categoryName[$category->getId()] ?? $category->getName();
                $nav = 'nav-' . $counter;
                $html .= '<li class="level' . $level . ' ' . $nav . ' category-item ' . $first . ' ' . $parent . ' ' . $last . '">';
                $html .= '<a href="' . $category->getUrl() . '" class="level-top">
                        <span>' . $displayCategoryName . '</span>
                    </a>';
                if ($childrenCount != 0) {
                    if($category->getId() == $printProductCategoryId) {
                        $isPrintProduct = true;
                    }
                    $html .= $this->getChildCategoryHtml($category->getId(), $nav, $level, $denyCategoryIds, $isAdminUser, $companyType, $isPrintProduct);
                }
                $html .= '</li>';
            }
            return $html;
        }

        $html = '';
        $this->_eventManager->dispatch(
            'page_block_html_topmenu_gethtml_before',
            [
                'menu' => $this->_menu,
                'block' => $this,
                'request' => $this->getRequest()
            ]
        );

        $this->_menu->setOutermostClass($outermostClass);
        $this->_menu->setChildrenWrapClass($childrenWrapClass);

        if ($this->helper->isEnabled() && $this->primaryMenu->getIsActive()) {
            $menuItems = $this->megamenuManagement->loadMenuItems(0, 'ASC');
            if ($this->primaryMenu->getMenuType() == Menu::MEGA_MENU) {
                $html .= $this->menuItemsIteration($menuItems);
            } else {
                $parent = 'root';
                $level = 0;
                $html = $this->setPrimaryMenu(
                    $menuItems,
                    $level,
                    $parent,
                    $outermostClass
                );
            }
        } else {
            $sharedCatalogCategoryId = $this->getCommericalSharedCatalogCategory();
            if ($this->ondemandConfig->isTigerD239305ToggleEnabled()) {
                $b2bGeneralCategories = $this->ondemandConfig->getGlobalB2BCategories();
                $allowCategoryNodes = ['category-node-' . $sharedCatalogCategoryId];
                foreach ($b2bGeneralCategories as $b2bGeneralCategory) {
                    $allowCategoryNodes[] = 'category-node-' . $b2bGeneralCategory;
                }
            } else {
                $printProductCategoryId = $this->toggleConfig->getToggleConfig("ondemand_setting/category_setting/epro_print");
                $allowCategoryNodes = ['category-node-' . $sharedCatalogCategoryId, 'category-node-' . $printProductCategoryId];
            }
            if($sharedCatalogCategoryId) {
                $children = $this->_menu->getChildren();
                $unsecategoryIds = [];
                $arrayChildren = json_decode(json_encode((array)$children),true);

                foreach($arrayChildren as $key => $_arrayChildren) {
                    $unsecategoryIds = array_keys($_arrayChildren);
                    if(!empty($unsecategoryIds)) {
                        break;
                    }
                }
                foreach($unsecategoryIds as $unsecategoryId) {
                    if(!in_array($unsecategoryId,$allowCategoryNodes)) {
                        unset($children[$unsecategoryId]);
                    }
                }
                $this->_menu->setChildren($children);
            }
            $html = $this->_getHtml($this->_menu, $childrenWrapClass, $limit);
        }

        $transportObject = new \Magento\Framework\DataObject(['html' => $html]);

        $this->_eventManager->dispatch(
            'page_block_html_topmenu_gethtml_after',
            ['menu' => $this->_menu, 'transportObject' => $transportObject]
        );

        return $transportObject->getHtml();
    }

    /**
     * @return false
     */
    public function getCommericalSharedCatalogCategory() {
        $companyConfiguration = $this->helperData->getCompanyConfiguration();
        if(is_object($companyConfiguration)) {
            return $companyConfiguration->getData('shared_catalog_id');
        }
        return false;
    }

    /**
     * MenuItemIteration
     *
     * @param object $menuItems
     * @return string $html
     */
    public function menuItemsIteration($menuItems) {

        $html = '';

        foreach ($menuItems as $item) {
            $childrenWrapClass = "level0 nav-1 first parent main-parent";
            $companyConfiguration = $this->helperData
                ->getCompanyConfiguration();
            $allowOwnDocument = (bool) $companyConfiguration
            ->getAllowOwnDocument();
            $allowSharedCatalog = (bool) $companyConfiguration
            ->getAllowSharedCatalog();
            // check expression if print product cat disabled from company setting
            $isPrintProductsCatDisabled = ($item->getItemName() === static::PRINT_PRODUCTS
            && !$allowOwnDocument);
            // check expression if Browse product cat disabled from company setting
            $isBrowseCatDisabled = ($item->getItemName() === static::BROWSE_CATALOG
            || !empty(strpos($item->getItemName(), static::BROWSE_CATALOG))
            && !$allowSharedCatalog);

            if ($isPrintProductsCatDisabled || $isBrowseCatDisabled
            ) {
                continue;
            }

            $html .= $this->setMegamenu($item, $childrenWrapClass);
        }

        return $html;
    }

    /**
     * Recursively generates top menu html from data that is specified in $menuTree
     *
     * @param Node   $menuTree          menuTree
     * @param string $childrenWrapClass childrenWrapClass
     * @param int    $limit             limit
     * @param array  $colBrakes         colBrakes
     *
     * @return string
     */
    protected function _getHtml(
        Node $menuTree,
        $childrenWrapClass,
        $limit,
        array $colBrakes = []
    ) {
        $html = '';
        $children = $menuTree->getChildren();
        $childLevel = $this->getChildLevel($menuTree->getLevel());
        $this->removeChildrenWithoutActiveParent($children, $childLevel);

        $counter = 1;
        $childrenCount = $children->count();

        $parentPositionClass = $menuTree->getPositionClass();
        $itemPositionClassPrefix = $parentPositionClass ?
        $parentPositionClass . '-' : 'nav-';

        if ($this->deliveryHelper->isCommercialCustomer() && $childLevel > 0) {
            $children = $this->getReorderCateogry($children);
        }
        /**
         * {@inheritdoc}
         *
         * @var Node $child
         */
        foreach ($children as $child) {
            // Remove custom product from mega menu item
            $customProductCategoryId =
                $this->toggleConfig->getToggleConfig('ondemand_setting/category_setting/epro_print_custom_product');
            $isNonStandardCatalogToggleEnable = $this->toggleConfig->getToggleConfigValue(
                'explorers_non_standard_catalog'
            );
            if ($isNonStandardCatalogToggleEnable &&
                ((int)$customProductCategoryId == (int)str_replace('category-node-','',$child->getId()))) {
                continue;
            }
            /** Comment this code for testing purpose if every thing will work will remove commented code */
            $isShowPrintProduct = $this->ondemandHelper->isProductAvailable($child, $childLevel);
            if (!$isShowPrintProduct) {
                continue;
            }
            $isPublish = $this->ondemandHelper->isPublishCategory($child, $childLevel);
            if (!$isPublish) {
                continue;
            }

            $child->setLevel($childLevel);
            $child->setIsFirst($counter === 1);
            $child->setIsLast($counter === $childrenCount);
            $child->setPositionClass($itemPositionClassPrefix . $counter);

            $outermostClassCode = '';
            $outermostClass = $menuTree->getOutermostClass();

            if ($childLevel === 0 && $outermostClass) {
                $outermostClassCode = ' class="' . $outermostClass . '" ';
                $this->setCurrentClass($child, $outermostClass);
            }

            if ($this->shouldAddNewColumn($colBrakes, $counter)) {
                $html .= '</ul></li><li class="column"><ul>';
            }

            $html .= $this->getCreatedMenu($child, $outermostClassCode, $childrenWrapClass, $childLevel, $limit);
            $counter++;
        }

        if (is_array($colBrakes) && !empty($colBrakes) && $limit) {
            $html = '<li class="column"><ul>' . $html . '</ul></li>';
        }

        return $html;
    }

    /**
     * isEnabledPrintOrBrowseCateForSde
     *
     * @param obj $child
     * @param string $outermostClassCode
     * @param string $childrenWrapClass
     * @param string $childLevel
     * @param int $limit
     * @param obj $companyConfiguration
     *
     * @return string
     */
    public function isEnabledPrintOrBrowseCateForSde(
        $child,
        $outermostClassCode,
        $childrenWrapClass,
        $childLevel,
        $limit,
        $companyConfiguration
    )
    {
        $html = '';
        $printProducts = "print products";
        $browseProducts = "Browse Catalog";
        if ((strtolower($child->getName()) === $printProducts ||
        !empty(strpos($child->getName(), $printProducts))) &&
        $companyConfiguration->getAllowOwnDocument()) {
            $html .= '<li ' . $this->_getRenderedMenuItemAttributes($child)
            . '>';
            $html .= static::ANCHOR_ELEMENT . $child->getUrl()
            . '" ' . $outermostClassCode .
            static::SPAN_OPEN_ELEMENT . trim(static::PRINT_PRODUCTS) .
            static::CLOSE_SPAN_ANCHOR . $this->_addSubMenu(
                $child,
                $childLevel,
                $childrenWrapClass,
                $limit
            ) . static::LI_CLOSE_ELEMENT;
        }
        if (($companyConfiguration->getAllowSharedCatalog() &&
        strtolower($child->getName()) === $browseProducts) ||
        ($childLevel >= 1)) {
            $html .= '<li ' . $this->_getRenderedMenuItemAttributes($child)
            . '>';
            $html .= static::ANCHOR_ELEMENT . $child->getUrl()
            . '" ' . $outermostClassCode . '>
            <span>' . trim($child->getName()) . '</span>
            </a>' . $this->_addSubMenu($child, $childLevel, $childrenWrapClass, $limit) .
            static::LI_CLOSE_ELEMENT;
        }

        return $html;
    }

    /**
     * GetCreatedMenu
     *
     * @param obj $child
     * @param string $outermostClassCode
     * @param string $childrenWrapClass
     * @param string $childLevel
     * @param int $limit
     *
     * @return string
     */
    public function getCreatedMenu($child, $outermostClassCode, $childrenWrapClass, $childLevel, $limit)
    {
        $html = '';
        $companyConfiguration = $this->helperData->getCompanyConfiguration();
        $allowOwnDocument = (bool) $companyConfiguration->getAllowOwnDocument();
        $allowSharedCatalog = (bool) $companyConfiguration
        ->getAllowSharedCatalog();
        $isSdeStore = $this->sdeHelper->getIsSdeStore();

        if ($isSdeStore != '1' && ($child->getName() === static::PRINT_PRODUCTS ||
            !empty(strpos($child->getName(), static::PRINT_PRODUCTS)))
                && $allowOwnDocument === true
        ) {
            $html .= '<li ' . $this->_getRenderedMenuItemAttributes($child)
            . '>';
            $html .= static::ANCHOR_ELEMENT . $child->getUrl() . '"
            ' . $outermostClassCode . '><span>' . trim(static::PRINT_PRODUCTS) .
            '</span></a>' . $this->_addSubMenu($child, $childLevel, $childrenWrapClass, $limit) .
            static::LI_CLOSE_ELEMENT;
        } elseif ($isSdeStore != '1' && $child->getName() === static::BROWSE_CATALOG ||
        !empty(strpos($child->getName(), static::BROWSE_CATALOG))
            && $allowSharedCatalog === true
        ) {
            $html .= '<li ' . $this->_getRenderedMenuItemAttributes($child)
            . '>';
            $html .= static::ANCHOR_ELEMENT . $child->getUrl() .
            '" ' . $outermostClassCode .
            static::SPAN_OPEN_ELEMENT . trim(static::BROWSE_CATALOG) .
            static::CLOSE_SPAN_ANCHOR . $this->_addSubMenu(
                $child,
                $childLevel,
                $childrenWrapClass,
                $limit
            ) . static::LI_CLOSE_ELEMENT;
        } elseif ($isSdeStore && $this->orderHistoryDataHelper->isSDEHomepageEnable()//B-1145888
        ) {

            $html .= $this->isEnabledPrintOrBrowseCateForSde(
                $child,
                $outermostClassCode,
                $childrenWrapClass,
                $childLevel,
                $limit,
                $companyConfiguration
            );
        } else {
            if ($child->getName() !== static::BROWSE_CATALOG &&
            empty(strpos($child->getName(), static::BROWSE_CATALOG))
                && $child->getName() !== static::PRINT_PRODUCTS &&
                empty(strpos($child->getName(), static::PRINT_PRODUCTS))
            ) {
                $isCommercialCustomer = $this->deliveryHelper->isCommercialCustomer();
                $childId = $this->catalogMvpHelper->getIdFromNode($child);
                $childIdstr = strval($childId);
                if (str_contains($childIdstr, 'category-node-') && $isSdeStore != '1' && $isCommercialCustomer) {
                    $catId = str_replace('category-node-', '', $childIdstr);
                    $category = $this->categoryFactory->create()->load($catId);
                    $catLevel = $category->getLevel()-2;
                    if ($childLevel == $catLevel) {
                        $html .= '<li ' . $this->_getRenderedMenuItemAttributes($child). '>';
                        $html .= static::ANCHOR_ELEMENT . $child->getUrl()
                        . '" ' . $outermostClassCode . '>
                        <span>' . trim($child->getName()) . '</span>
                        </a>' . $this->_addSubMenu($child, $childLevel, $childrenWrapClass, $limit) .
                        static::LI_CLOSE_ELEMENT;
                    }
                } else {
                    $html .= '<li ' . $this->_getRenderedMenuItemAttributes($child)
                    . '>';
                    $html .= static::ANCHOR_ELEMENT . $child->getUrl()
                    . '" ' . $outermostClassCode . '>
                    <span>' . trim($child->getName()) . '</span>
                    </a>' . $this->_addSubMenu($child, $childLevel, $childrenWrapClass, $limit) .
                    static::LI_CLOSE_ELEMENT;
                }
            }
        }

        return $html;
    }

    /**
     * Set custom template
     *
     * @param $template template
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setCustomTemplate($template)
    {
        $this->setTemplate($template);
        if ($this->helper->isEnabled()) {

            if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                $customerSession = $this->getOrCreateCustomerSession();
            } else {
                $customerSession = $this->session->create();
            }

            if ($this->authHelper->isLoggedIn()) {
                $this->primaryMenu = $this->megamenuManagement
                    ->getMenuData($customerSession->getCustomerId())
                    ->getMenu();
            } else {
                $this->primaryMenu = $this->megamenuManagement
                    ->getMenuData()
                    ->getMenu();
            }
            $this->primaryMenuId = $this->primaryMenu->getMenuId();
            if ($this->primaryMenu->getIsActive()) {
                if ($this->helper->isHumbergerMenu()) {
                    $this->setTemplate(static::BURGER_MENU_TEMPLATE);
                } else {
                    if ($this->primaryMenu->getMenuType() == Menu::MEGA_MENU) {
                        $this->setTemplate(self::MEGA_MENU_TEMPLATE);
                    }
                }
            }
        }
    }

    /**
     * Retrieve child level based on parent level
     *
     * @param int $parentLevel parentLevel
     *
     * @return int
     */
    protected function getChildLevel($parentLevel): int
    {
        return $parentLevel === null ? 0 : $parentLevel + 1;
    }

    /**
     * Check if new column should be added.
     *
     * @param array $colBrakes colBrakes
     * @param int   $counter   counter
     *
     * @return bool
     */
    protected function shouldAddNewColumn(array $colBrakes, int $counter): bool
    {
        return count($colBrakes) && $colBrakes[$counter]['colbrake'];
    }

    /**
     * Remove children from collection when the parent is not active
     *
     * @param Collection $children   childrenCollection
     * @param int        $childLevel childLevel
     *
     * @return void
     */
    protected function removeChildrenWithoutActiveParent(
        Collection $children,
        int $childLevel
    ): void {
        /**
         * {@inheritdoc}
         *
         * Remove children from collection when the parent is not active
         *
         * @var Node $child
         */
        foreach ($children as $child) {
            if ($childLevel === 0 && $child->getData('is_parent_active') === false) {
                $children->delete($child);
            }
        }
    }

     /**
     * Reorder category
     *
     * @param Node $children
     * @return array
     */
    public function getReorderCateogry($children)
    {
        $newOrderCat = $newOrderCatObj = [];
        foreach ($children as $child) {
            $alphaChar = preg_replace('/\s+/', '_', $child->getName());
            $newOrderCat[$alphaChar] = $child;
        }
        ksort($newOrderCat);
        foreach ($newOrderCat as $newOrderCatKey => $newOrderCatVal) {
            $newOrderCatObj[] = $newOrderCatVal;
        }
        return $newOrderCatObj;
    }

    /**
     * Get toggle value
     *
     * @param String $value
     * @return String
     */
    public function getToggleValue($value)
    {
        return $this->toggleConfig->getToggleConfigValue($value);
    }

    /**
     * Toggle for Catalog Performance Improvement Phase Two
     * @return bool
     */
    public function getToggleStatusForPerformanceImprovmentPhasetwo()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE);
    }

    /**
     * Get Customer Session Catalog Improvement Phase Two
     * @return Session
     */
    public function getOrCreateCustomerSession()
    {
        if(!$this->customerSession->isLoggedIn()){
            $this->customerSession = $this->session->create();
        }
        return $this->customerSession;
    }

    /**
     * @param $outermostClass
     * @param $childrenWrapClass
     * @param $limit
     * @return mixed
     * @throws LocalizedException
     * @throws LocalizedException
     */
    public function getDefaultMenuHtml($outermostClass,$childrenWrapClass,$limit){
        $html = '';
        $this->_eventManager->dispatch(
            'page_block_html_topmenu_gethtml_before',
            [
                'menu' => $this->_menu,
                'block' => $this,
                'request' => $this->getRequest()
            ]
        );

        $this->_menu->setOutermostClass($outermostClass);
        $this->_menu->setChildrenWrapClass($childrenWrapClass);

        if ($this->helper->isEnabled() && $this->primaryMenu->getIsActive()) {
            $menuItems = $this->megamenuManagement->loadMenuItems(0, 'ASC');
            if ($this->primaryMenu->getMenuType() == Menu::MEGA_MENU) {
                $html .= $this->menuItemsIteration($menuItems);
            } else {
                $parent = 'root';
                $level = 0;
                $html = $this->setPrimaryMenu(
                    $menuItems,
                    $level,
                    $parent,
                    $outermostClass
                );
            }
        } else {
            $sharedCatalogCategoryId = $this->getCommericalSharedCatalogCategory();
            if ($this->ondemandConfig->isTigerD239305ToggleEnabled()) {
                $b2bGeneralCategories = $this->ondemandConfig->getGlobalB2BCategories();
                $allowCategoryNodes = ['category-node-' . $sharedCatalogCategoryId];
                foreach ($b2bGeneralCategories as $b2bGeneralCategory) {
                    $allowCategoryNodes[] = 'category-node-' . $b2bGeneralCategory;
                }
            } else {
                $printProductCategoryId = $this->toggleConfig->getToggleConfig("ondemand_setting/category_setting/epro_print");
                $allowCategoryNodes = ['category-node-' . $sharedCatalogCategoryId, 'category-node-' . $printProductCategoryId];
            }
            if($sharedCatalogCategoryId) {
                $children = $this->_menu->getChildren();
                $unsecategoryIds = [];
                $arrayChildren = json_decode(json_encode((array)$children),true);

                foreach($arrayChildren as $key => $_arrayChildren) {
                    $unsecategoryIds = array_keys($_arrayChildren);
                    if(!empty($unsecategoryIds)) {
                        break;
                    }
                }
                foreach($unsecategoryIds as $unsecategoryId) {
                    if(!in_array($unsecategoryId,$allowCategoryNodes)) {
                        unset($children[$unsecategoryId]);
                    }
                }
                $this->_menu->setChildren($children);
            }
            $html = $this->_getHtml($this->_menu, $childrenWrapClass, $limit);
        }

        $transportObject = new \Magento\Framework\DataObject(['html' => $html]);

        $this->_eventManager->dispatch(
            'page_block_html_topmenu_gethtml_after',
            ['menu' => $this->_menu, 'transportObject' => $transportObject]
        );

        return $transportObject->getHtml();
    }
}

