<?php

/**
 * Magedelight
 * Copyright (C) 2017 Magedelight <info@magedelight.com>
 *
 * @category Magedelight
 * @package Magedelight_Megamenu
 * @copyright Copyright (c) 2017 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 */

namespace Magedelight\Megamenu\Block;

use Magento\Framework\Data\Tree\Node;
use Magento\Store\Model\ScopeInterface;
use Magento\Theme\Block\Html\Topmenu as MagentoTopmenu;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Data\TreeFactory;
use Magento\Framework\Data\Tree\NodeFactory;
use Magedelight\Megamenu\Model\MenuFactory;
use Magedelight\Megamenu\Model\MenuItemsFactory;
use Magento\Cms\Model\BlockFactory;
use Magento\CatalogPermissions\Model\Permission;

/**
 * Class Topmenu
 *
 * @package Magedelight\Megamenu\Block
 */
class TopmenuTest extends MagentoTopmenu
{
    /**
     * @var \Magedelight\Megamenu\Model\MenuFactory
     */
    protected $menuFactory;

    /**
     * @var \Magedelight\Megamenu\Model\MenuItemsFactory
     */
    protected $menuItemsFactory;

    /**
     * @var int
     */
    protected $primaryMenuId;

    /**
     * @var \Magedelight\Megamenu\Model\Menu
     */
    protected $primaryMenu;

    /**
     * @var \Magento\Cms\Model\BlockFactory
     */
    private $blockFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    public $customerSession;

    /**
     * @var \Magento\Framework\Registry
     */
    public $registry;

    /**
     * @var \Magento\Catalog\Model\Category
     */
    protected $categoryRepository;
    /**
     * @var \Magento\Catalog\Helper\Category
     */
    private $categoryHelper;
    /**
     * @var \Magento\Catalog\Model\Indexer\Category\Flat\State
     */
    private $categoryFlatState;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $categoryFactory;

    public $output;

    protected $mdColumnCount = 10;

    protected $helper;

    /**
     * Topmenu constructor.
     * @param Template\Context $context
     * @param NodeFactory $nodeFactory
     * @param TreeFactory $treeFactory
     * @param MenuFactory $menuFactory
     * @param MenuItemsFactory $menuItemsFactory
     * @param BlockFactory $blockFactory
     * @param \Magento\Cms\Model\Page $page
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        NodeFactory $nodeFactory,
        TreeFactory $treeFactory,
        MenuFactory $menuFactory,
        MenuItemsFactory $menuItemsFactory,
        BlockFactory $blockFactory,
        \Magento\Cms\Model\Page $page,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Catalog\Model\Indexer\Category\Flat\State $categoryFlatState,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Helper\Output $output,
        \Magedelight\Megamenu\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $nodeFactory, $treeFactory, $data);
        $this->menuFactory = $menuFactory;
        $this->menuItemsFactory = $menuItemsFactory;
        $this->_scopeInterface = $context->getScopeConfig();
        $this->blockFactory = $blockFactory;
        $this->_page = $page;
        $this->customerSession = $session;
        $this->registry = $registry;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $context->getStoreManager();
        $this->categoryHelper = $categoryHelper;
        $this->categoryFlatState = $categoryFlatState;
        $this->categoryFactory = $categoryFactory;
        $this->output = $output;
        $this->helper = $helper;
    }

    /**         /**
     * Get block cache life time
     *
     * @return int
     * @since 100.1.0
     */
    protected function getCacheLifetime()
    {
        return parent::getCacheLifetime() ?: 3600;
    }

    /**
     * Get current category id
     */
    public function getCurentCat()
    {
        $category = $this->registry->registry('current_category'); //get current category
        if (isset($category) and ! empty($category->getId())) {
            return $category->getId();
        }
    }

    /**
     * Get current page id
     */
    public function getCurentPage()
    {
        if ($this->_page->getId()) {
            return $pageId = $this->_page->getId();
        }
    }

    /**
     * Set Template for menubased on its type
     *
     * @param string
     */
    public function setCustomTemplate($template)
    {
        if (!$this->getConfigBurgerStatus()) {
            $this->primaryMenuId = $this->getStoreMenuId();
            $this->primaryMenu = $this->menuFactory->create()->load($this->primaryMenuId);
            if (($this->primaryMenu->getMenuType() == 2) and ( $this->primaryMenu->getIsActive() == 1) and
                ( $this->getConfigMenuStatus() == 1)) {
                /* Set Megamenu Custom Template */
                $this->setTemplate('Magedelight_Megamenu::menu/topmenu.phtml');
            } else {
                /* Set Magento Custom Template */
                $this->setTemplate($template);
            }
        } else {
            $this->setTemplate('Magedelight_Megamenu::menu/burger.phtml');
        }
    }

    /**
     * Get store identifier
     *
     * @return  int
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Retrieve Menu Id for the current store
     *
     * @return int
     */
    public function getConfigMenuStatus()
    {
        return $this->_scopeInterface->getValue(
            'magedelight/general/megamenu_status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve Menu Id for the current store
     *
     * @return int
     */
    public function getStoreMenuId()
    {
        $menu_id = $this->_scopeInterface->getValue(
            'magedelight/general/primary_menu',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $currentCustomerGroupId = $this->customerSession->getCustomerGroupId();

        $menu = $this->menuFactory->create()->load($menu_id);
        $customerGroupsArray = explode(',', trim($menu->getCustomerGroups()));
        if (!in_array($currentCustomerGroupId, $customerGroupsArray) || $menu->getIsActive() != 1) {
            $menu_id = '';
        }
        if (empty($menu_id)) {
            $current_store_id = $this->getStoreId();
            $menuCollection = $this->menuFactory->create()->getCollection()
                    ->addStoreFilter($current_store_id)
                    ->addFieldToFilter('is_active', '1')
                    ->addFieldToFilter('customer_groups', ['finset' => $currentCustomerGroupId])
                    ->setPageSize(1)
                    ->setCurPage(1);
            foreach ($menuCollection as $singleCollection) {
                return $menu_id = $singleCollection->getMenuId();
            }
        }
        if (empty($menu_id)) {
            $menuCollection = $this->menuFactory->create()->getCollection()
                    ->addStoreFilter(0)
                    ->addFieldToFilter('is_active', '1')
                    ->addFieldToFilter('customer_groups', ['finset' => $currentCustomerGroupId])
                    ->setPageSize(1)
                    ->setCurPage(1);
            foreach ($menuCollection as $singleCollection) {
                return $menu_id = $singleCollection->getMenuId();
            }
        }

        return $menu_id;
    }

    /**
     * Check item children
     *
     * @param int
     * @return int
     */
    protected function hasChildrenItems($parentId)
    {
        $count = $this->menuItemsFactory->create()->getCollection()
                ->addFieldToFilter('menu_id', $this->primaryMenuId)
                ->addFieldToFilter('item_parent_id', $parentId)
                ->count();
        return $count;
    }

    /**
     * Retrieve Inline menu Style for extra css
     *
     * @return string
     */
    public function menuStyleHtml()
    {
        if (!empty(trim($this->primaryMenu->getMenuStyle()))) {
            return '<style>' . $this->primaryMenu->getMenuStyle() . '</style>';
        }
        return '';
    }

    /**
     * Get top menu html
     *
     * @param string $outermostClass
     * @param string $childrenWrapClass
     * @param int $limit
     * @return string
     */
    public function getHtml($outermostClass = '', $childrenWrapClass = '', $limit = 0)
    {
        /* Burger menu for desktop */
        if ($this->getConfigBurgerStatus()) {
            /* Start Burgermenu code */
                $this->_eventManager->dispatch(
                    'page_block_html_topmenu_gethtml_before',
                    ['menu' => $this->_menu, 'block' => $this,'request' => $this->getRequest()]
                );

                $this->_menu->setOutermostClass($outermostClass);
                $this->_menu->setChildrenWrapClass($childrenWrapClass);

                $html = $this->_getHtml($this->_menu, $childrenWrapClass, $limit);

                 $transportObject = new \Magento\Framework\DataObject(['html' => $html]);

                 $this->_eventManager->dispatch(
                     'page_block_html_topmenu_gethtml_after',
                     ['menu' => $this->_menu, 'transportObject' => $transportObject]
                 );
                $html = $transportObject->getHtml();
                return $html;
        }

        /* Start Megamenu code */
        $this->_eventManager->dispatch(
            'page_block_html_topmenu_gethtml_before',
            ['menu' => $this->_menu, 'block' => $this,'request' => $this->getRequest()]
        );

        $this->_menu->setOutermostClass($outermostClass);
        $this->_menu->setChildrenWrapClass($childrenWrapClass);

        if (($this->primaryMenu->getMenuType() == 2) and ( $this->primaryMenu->getIsActive() == 1) and
            ( $this->getConfigMenuStatus() == 1)) {
            $menuItems = $this->menuItemsFactory->create()->getCollection()
                    ->addFieldToFilter('menu_id', $this->primaryMenuId)
                    ->addFieldToFilter('item_parent_id', 0)
                    ->setOrder('sort_order', 'ASC');
            $html = '';
            foreach ($menuItems as $item) {
                $childrenWrapClass = "level0 nav-1 first parent main-parent";
                $html .= $this->setMegamenu($item, $childrenWrapClass);
            }
        } elseif (($this->primaryMenu->getMenuType() == 1) and
            ( $this->primaryMenu->getIsActive() == 1) and ( $this->getConfigMenuStatus() == 1)) {
            $menuItems = $this->menuItemsFactory->create()->getCollection()
                    ->addFieldToFilter('menu_id', $this->primaryMenuId)
                    ->addFieldToFilter('item_parent_id', 0);
            $parent = 'root';
            $level = 0;
            $html = $this->setPrimaryMenu($menuItems, $level, $parent, $outermostClass);
        } else {
            $html = $this->_getHtml($this->_menu, $childrenWrapClass, $limit);
        }

        $transportObject = new \Magento\Framework\DataObject(['html' => $html]);
        $this->_eventManager->dispatch(
            'page_block_html_topmenu_gethtml_after',
            ['menu' => $this->_menu, 'transportObject' => $transportObject]
        );
        $html = $transportObject->getHtml();
        return $html;
    }

    /**
     * Recursively generates top menu html from data that is specified in $menuTree
     *
     * @param array $menuItems
     * @param int $level
     * @param int $parent
     * @param string $outermostClass
     * @return string
     */
    public function setPrimaryMenu($menuItems, $level = 0, $parent = '', $outermostClass = '')
    {
        $html = '';
        $class = 'level0 level-top parent ui-menu-item';
        $linkClass = 'level-top ';
        if ($parent != 'root') {
            $html .= '<ul class="level' . $level . ' submenu">';
            $linkClass = '';
        }
        foreach ($menuItems as $menuItem) {
            $menuItemId = $menuItem->getItemId();
            $linkurl = $menuItem->getItemLink();
            $dataclass = $menuItem->getItemClass();

            if (!$linkurl) {
                $linkurl = $this->generateMenuUrl($menuItem);
            }

            $hasChildren = $this->hasChildrenItems($menuItemId);

            if ($hasChildren) {
                $class = 'level' . $level . ' parent';
            } else {
                $class = 'level' . $level;
            }

            if ($menuItem->getItemType() == 'category') {
                if ($menuItem->getObjectId() == $this->getCurentCat()) {
                    $class .= ' active';
                }
            } elseif ($menuItem->getItemType() == 'pages') {
                if ($menuItem->getObjectId() == $this->getCurentPage()) {
                    $class .= ' active';
                }
            }

            $html .= '<li class="' . $class . ' ' . $linkClass . ' ' . $dataclass . '">';

            if ($hasChildren) {
                $html .= '<a href="' . $linkurl . '" class="' . $linkClass .
                    ' ui-corner-all"><span class="megaitemicons">' .
                    $menuItem->getItemFontIcon() . '</span> <span>' .
                    $this->escapeHtml($this->generateMenuName($menuItem)) . '</span></a>';

                $menuItems = $this->menuItemsFactory->create()->getCollection()
                        ->addFieldToFilter('menu_id', $this->primaryMenuId)
                        ->addFieldToFilter('item_parent_id', $menuItemId);

                //Get list of child menu
                $html .= $this->setPrimaryMenu($menuItems, $level + 1);
            } else {
                $html .= '<a href="' . $linkurl . '" class="' . $linkClass .
                    ' ui-corner-all "><span class="megaitemicons">' .
                    $menuItem->getItemFontIcon() . '</span> <span>' .
                    $this->escapeHtml($this->generateMenuName($menuItem)) . '</span></a>';
            }
            $html .= '</li>';
        }
        if ($parent != 'root') {
            $html .= '</ul>';
        }
        return $html;
    }

    /**
     * Retrieve menu url based on there type
     *
     * @param MenuFactory $menuItem
     * @return string
     */
    public function generateMenuUrl($menuItem)
    {
        $linkurl = $menuItem->getItemLink();
        $url = '';
        if ($menuItem->getItemType() == "link" && !empty($linkurl)) {
            return $linkurl;
        }
        if ($menuItem->getItemType() == "category") {
            $url = $this->categoryRepository->get($menuItem->getObjectId(), $this->getStoreId())->getUrl();
        }
        if ($menuItem->getItemType() == "pages") {
            $url = $this->storeManager->getStore()->getBaseUrl() . $menuItem->getItemLink();
        }
        return $url;
    }

    /**
     * Retrieve menu name based on there type
     *
     * @param MenuFactory $menuItem
     * @return string
     */
    public function generateMenuName($menuItem)
    {
        if ($menuItem->getItemType() == "category") {
            $name = $this->categoryRepository->get(
                $menuItem->getObjectId(),
                $this->getStoreId()
            )->getName();
        } else {
            $name = $menuItem->getItemName();
        }
        return $name;
    }

    /**
     * Retrive Active Class
     */
    public function getActiveClass($menuItem)
    {
        if ($menuItem->getItemType() == 'category') {
            if ($menuItem->getObjectId() == $this->getCurentCat()) {
                return ' active';
            }
        } elseif ($menuItem->getItemType() == 'pages') {
            if ($menuItem->getObjectId() == $this->getCurentPage()) {
                return ' active';
            }
        }
        return '';
    }

    /**
     * Retrieve Html for Mega block
     *
     */
    protected function setMegamenu($menuTree, $childrenWrapClass)
    {
        $html = '';
        $parentId = $menuTree->getItemId();
        $dataclass = $menuTree->getItemClass();
        $animationOption = $menuTree->getAnimationOption();
        $class = $this->getActiveClass($menuTree);
        if ($menuTree->getItemType() == 'megamenu') {
            $class .= ' dropdown';
            $megaMenuLink = $menuTree->getItemLink()?$menuTree->getItemLink():'#';
            $html .= '<li class="menu-dropdown-icon category-item nav-'.$menuTree->getItemId().' ' . $class . ' ' . $dataclass . '"><a href="'.$megaMenuLink.'" class=""><span class="megaitemicons">' . $menuTree->getItemFontIcon() . '</span> ' . $this->generateMenuName($menuTree) . '</a>';
        }
        else {
            $sub_cat_disaply = $menuTree->getCategoryDisplay();
            $cat_vertical_menu = $menuTree->getCategoryVerticalMenu();
            $catVerticalMenuBg = $menuTree->getCategoryVerticalMenuBg();
            $header_enable = 0;
            $header_block = "";
            $left_enable = 0;
            $left_block = "";
            $right_enable = 0;
            $right_block = "";
            $footer_enable = 0;
            $footer_block = "";
            $header_title = "0";
            $left_title = "0";
            $right_title = "0";
            $footer_title = "0";
            if ($menuTree->getCategoryColumns()) {
                $categoryColumns = json_decode($menuTree->getCategoryColumns());
                foreach ($categoryColumns as $categoryColumn) {
                    if ($categoryColumn->type === 'header') {
                        $header_enable = (int) $categoryColumn->enable;
                        $header_block = $categoryColumn->value;
                        $header_title = $categoryColumn->showtitle;
                    }

                    if ($categoryColumn->type === 'left') {
                        $left_enable = (int) $categoryColumn->enable;
                        $left_block = $categoryColumn->value;
                        $left_title = $categoryColumn->showtitle;
                    }

                    if ($categoryColumn->type === 'right') {
                        $right_enable = (int) $categoryColumn->enable;
                        $right_block = $categoryColumn->value;
                        $right_title = $categoryColumn->showtitle;
                    }

                    if ($categoryColumn->type === 'bottom') {
                        $footer_enable = (int) $categoryColumn->enable;
                        $footer_block = $categoryColumn->value;
                        $footer_title = $categoryColumn->showtitle;
                    }
                }
            }
            $columnCount = 0;
            if ($left_enable || $right_enable) {
                $columnCount++;
            }

            $catDisplay = false;
            $menuAdd = false;
            $verticalMenu = false;
            $verticalMenuClass = '';
            $rightContentClass = '';
            $subcats = [];
            if ($menuTree->getItemType() === 'category' && (int) $sub_cat_disaply === (int) 1) {
                $categoryLoad = $this->categoryRepository->get($menuTree->getObjectId(), $this->getStoreId());
                $subcats = $this->getChildrenCategories($categoryLoad);
                if (!empty($subcats)) {
                    $catDisplay = true;
                    $menuAdd = true;
                    if ((int) $cat_vertical_menu === (int) 1) {
                        $verticalMenu = true;
                        $verticalMenuClass = 'menu-vertical-wrapper';
                        $rightContentClass = 'col-menu-3';
                    }
                }
            }

            if ($header_enable || $left_enable || $right_enable || $footer_enable) {
                $catDisplay = true;
            }

            $linkurl = $this->generateMenuUrl($menuTree);

            if ($catDisplay) {
                $class .= ' dropdown';
                if ($verticalMenu) {
                    $columnCount = 1;
                } else {
                    $columnCount++;
                }

                $menuColumnCount = 1;
                if ($columnCount === 3) {
                    $menuColumnCount = $columnCount - 1;
                }
                if ($columnCount === 2) {
                    $columnCount++;
                }
                if ($columnCount === 1) {
                    $menuColumnCount = 4;
                }
                $html .= '<li class="menu-dropdown-icon category-item nav-'.$menuTree->getItemId().' '. $class . ' ' . $dataclass . '"><a href="' . $linkurl . '"><span class="megaitemicons">' . $menuTree->getItemFontIcon() . '</span> ' . $this->generateMenuName($menuTree) . '</a>';

                $html .= '<ul class="animated ' . $animationOption . ' column' . $columnCount . " " . $verticalMenuClass . '" style="animation-duration: 0.7s;">';

                if ($header_enable) {
                    $headerblockObject = $this->getLayout()->createBlock('Magento\Cms\Block\Block')
                        ->setBlockId($header_block);
                    $headerblock = $this->blockFactory->create()->load($header_block);
                    $html .= '<li class="megaStaticBlock menu-header">';
                    if ($header_title === '1') {
                        $html .= '<h2>' . $headerblock->getTitle() . '</h2>';
                    }
                    $html .= '<ul><li>' . $headerblockObject->toHtml() . '</li>';
                    $html .= '</ul></li>';
                }

                if ($left_enable && !$verticalMenu) {
                    $leftblockObject = $this->getLayout()->createBlock('Magento\Cms\Block\Block')
                        ->setBlockId($left_block);
                    $leftblock = $this->blockFactory->create()->load($left_block);
                    $html .= '<li class="megaStaticBlock menu-sidebar-left">';
                    if ($left_title === '1') {
                        $html .= '<h2>' . $leftblock->getTitle() . '</h2>';
                    }
                    $html .= '<ul><li>' . $leftblockObject->toHtml() . '</li>';
                    $html .= '</ul></li>';
                }
                if ($right_enable) {
                    $colClass = 'col-menu-9';
                } else {
                    $colClass = '';
                }
                $html .= '<li class="megaStaticBlock menu-content ' . $colClass . '">';

                if (!empty($subcats)) {
                    if ($verticalMenu) {
                        $verticalHtml = '<div class="col-menu-9 vertical-menu-content">';
                        $html .= '<div class="col-menu-3 vertical-menu-left" style="background:#'.$catVerticalMenuBg.';">';
                        $html .= '<ul class="">';
                        $firstVerticalMenu = 1;
                        $firstVerticalMenuClass = '';
                        $verticalclass = '';
                        foreach ($subcats as $subcat) {
                            if ($subcat->getId() == $this->getCurentCat()) {
                                $verticalclass = 'active';
                            }
                            $_category = $this->categoryRepository->get($subcat->getId(), $this->getStoreId());
                            $childrenCats = $this->getChildrenCategories($_category);

                            if (!empty($childrenCats)) {
                                $addDropdownClass = " dropdown";
                            } else {
                                $addDropdownClass = "";
                            }

                            $html .= '<li class="menu-vertical-items nav-'.$menuTree->getItemId().' '. $verticalclass . $addDropdownClass . '" data-toggle="subcat-tab-' . $_category->getId() . '"><a href="' . $_category->getUrl() . '">' . $_category->getName() . '</a></li>';
                            $verticalHtml .= '';

                            if (!empty($childrenCats)) {
                                if (count($childrenCats) >= 3) {
                                    $columnCountForVerticalMenu = 3;
                                } else {
                                    $columnCountForVerticalMenu = count($childrenCats);
                                }

                                $verticalHtml .= '<div id="subcat-tab-' . $_category->getId() . '" class="vertical-subcate-content ' . $firstVerticalMenuClass . '"><ul class="menu-vertical-child column' . $columnCountForVerticalMenu . '">';
                                foreach ($childrenCats as $childrenCat) {
                                    $verticalclass = '';
                                    if ($childrenCat->getId() == $this->getCurentCat()) {
                                        $verticalclass = 'active';
                                    }
                                    $childrenCatLoad = $this->categoryRepository->get($childrenCat->getId(), $this->getStoreId());
                                    $verticalHtml .= '<li class="' . $verticalclass . '"><h4 class="level-3-cat"><a href="' . $childrenCatLoad->getUrl() . '">' . $childrenCatLoad->getName() . '</a></h4>';
                                    $childrenCatsNew = $this->getChildrenCategories($childrenCatLoad);
                                    if (count($childrenCatsNew) > 0) {
                                        $verticalHtml .= '<ul>';
                                        foreach ($childrenCatsNew as $childrenCatNew) {
                                            $verticalclass = '';
                                            if ($childrenCatNew->getId() == $this->getCurentCat()) {
                                                $verticalclass = 'active';
                                            }
                                            $childrenCatNewLoad = $this->categoryRepository->get($childrenCatNew->getId(), $this->getStoreId());
                                            $verticalHtml .= '<li class="' . $verticalclass . '"><a href="' . $childrenCatNewLoad->getUrl() . '">' . $childrenCatNewLoad->getName() . '</a>';
                                            $verticalHtml .= '</li>';
                                        }
                                        $verticalHtml .= '</ul>';
                                    }
                                    $verticalHtml .= '</li>';
                                }
                                $verticalHtml .= '</ul></div>';
                            }
                            $firstVerticalMenu++;
                        }

                        $verticalHtml .= '</div>';
                        $html .= '</ul>';
                        $html .= '</div>' . $verticalHtml;
                    } else {
                        $html .= '<ul class="column' . $menuColumnCount . '">';
                        foreach ($subcats as $subcat) {
                            $verticalclass = '';
                            if ($subcat->getId() == $this->getCurentCat()) {
                                $verticalclass = 'active';
                            }
                            $_category = $this->categoryRepository->get($subcat->getId(), $this->getStoreId());
                            $childrenCats = $this->getChildrenCategories($_category);

                            $html .= '<li class="category-item nav-'.$menuTree->getItemId().'-'.$subcat->getId().' ' . $verticalclass . '"><a href="' . $_category->getUrl() . '">' . $_category->getName() . '</a>';
                            if (!empty($childrenCats)) {
                                $html .= '<ul class="level3">';
                                foreach ($childrenCats as $childrenCat) {
                                    $verticalclass = '';
                                    if ($childrenCat->getId() == $this->getCurentCat()) {
                                        $verticalclass = 'active';
                                    }
                                    $childrenCatLoad = $this->categoryRepository->get($childrenCat->getId(), $this->getStoreId());
                                    $html .= '<li class="' . $verticalclass . '"><a href="' . $childrenCatLoad->getUrl() . '">' . $childrenCatLoad->getName() . '</a></li>';
                                }
                                $html .= '</ul>';
                            }
                            $html .= '</li>';
                        }
                        $html .= '</ul>';
                    }
                }

                $html .= '</li>';

                if ($right_enable) {
                    $rightblockObject = $this->getLayout()->createBlock('Magento\Cms\Block\Block')
                        ->setBlockId($right_block);
                    $rightblock = $this->blockFactory->create()->load($right_block);
                    $html .= '<li class="megaStaticBlock menu-sidebar-right ' . $rightContentClass . '">';
                    if ($right_title === '1') {
                        $html .= '<h2>' . $rightblock->getTitle() . '</h2>';
                    }
                    $html .= '<ul><li>' . $rightblockObject->toHtml() . '</li>';
                    $html .= '</ul></li>';
                }

                if ($footer_enable) {
                    $footerblockObject = $this->getLayout()->createBlock('Magento\Cms\Block\Block')
                        ->setBlockId($footer_block);
                    $footerblock = $this->blockFactory->create()->load($footer_block);
                    $html .= '<li class="megaStaticBlock menu-footer">';
                    if ($footer_title === '1') {
                        $html .= '<h2>' . $footerblock->getTitle() . '</h2>';
                    }
                    $html .= '<ul><li>' . $footerblockObject->toHtml() . '</li>';
                    $html .= '</ul></li>';
                }

                $html .= '</ul></li>';
            } else {
                $html .= '<li class="' . $class . ' ' . $dataclass . '"><a href="' . $linkurl . '"><span class="megaitemicons">' . $menuTree->getItemFontIcon() . '</span> ' . $this->generateMenuName($menuTree) . '</a></li>';
            }
        }
        $hasChildrenMenu = $menuTree->getItemColumns();
        $menuitemtype = $menuTree->getItemType();
        if (!empty($hasChildrenMenu)) {
            if ($menuitemtype == 'megamenu') {
                $html .= $this->setChildMegamenuColumn($hasChildrenMenu, $animationOption);
                $html .= '</ul></li>';
            }
        }
        return $html;
    }

    /**
     * Retrieve Html for Mega block
     *
     */
    public function setChildMegamenuColumn($childrenMenu, $animationOption)
    {
        $menuitems = json_decode($childrenMenu);
        $totalColumn = count($menuitems);

        $childHtml = '<ul class="animated ' . $animationOption . ' column' . $totalColumn . '">';

        for ($i = 0; $i < $totalColumn; $i++) {
            $type = $menuitems[$i]->type;
            if ($type == 'menu') {
                $subMenuId = $menuitems[$i]->value;

                $menus = $this->menuFactory->create()->load($subMenuId)->getData();
                $showtitle = $menuitems[$i]->showtitle;
                $childHtml .= '<li class="megaNormalMenu">';
                if ($showtitle == '1') {
                    if (isset($menus['menu_name']) and ! empty($menus['menu_name'])) {
                        $childHtml .= '<h2>' . $menus['menu_name'] . '</h2>';
                    }
                }
                $childHtml .= '<ul>';
                $menuItems = $this->menuItemsFactory->create()->getCollection()
                        ->addFieldToFilter('menu_id', $subMenuId)
                        ->addFieldToFilter('item_parent_id', 0)
                        ->setOrder('sort_order', 'ASC');

                foreach ($menuItems as $menuitem) {
                    $class = $this->getActiveClass($menuitem);
                    $linkurl = $this->generateMenuUrl($menuitem);
                    $dataclass = $menuitem->getItemClass();

                    $childHtml .= '<li class="' . $class . ' ' . $dataclass . '"><a href="' . $linkurl . '"><span class="megaitemicons">' . $menuitem->getItemFontIcon() . '</span> ' . $this->generateMenuName($menuitem) . '</a></li>';
                }
                $childHtml .= '</ul></li>';
            }
            if ($type == 'block') {
                $subBlockId = $menuitems[$i]->value;
                $blockObject = $this->getLayout()->createBlock('Magento\Cms\Block\Block')
                    ->setBlockId($subBlockId);
                $block = $this->blockFactory->create()->load($subBlockId);
                $childHtml .= '<li class="megaStaticBlock">';
                $showtitle = $menuitems[$i]->showtitle;
                if ($showtitle == '1') {
                    $childHtml .= '<h2>' . $block->getTitle() . '</h2>';
                }
                $childHtml .= '<ul><li>' . $blockObject->toHtml() . '</li></ul></li>';
            }
            if ($type == 'category') {
                $category_id = $menuitems[$i]->value;
                $category = $this->categoryRepository->get($category_id, $this->getStoreId());
                if($this->helper->permissionEnabled() && in_array($category->getId(), $this->helper->getExcludeCategoryIds())) {
                    $childHtml .= '';
                } else {
                    $subcats = $this->getChildrenCategories($category);
                    $childHtml .= '<li class="megaStaticBlock">';
                    $showtitle = $menuitems[$i]->showtitle;
                    if (count($subcats) > 0) {
                        if ($showtitle == '1') {
                            $childHtml .= '<h2>' . $category->getName() . '</h2>';
                        }

                        $childHtml .= '<ul>';
                        foreach ($subcats as $subcat) {
                            $_category = $this->categoryRepository->get($subcat->getId(), $this->getStoreId());
                            $_categoryName = $_category->getName();
                            $_categoryUrl = $_category->getUrl();
                            $childHtml .= '<li><a href="' . $_categoryUrl . '">' . $_categoryName . '</a></li>';
                        }
                        $childHtml .= '</ul>';
                    } else {
                        $categoryUrl = $category->getUrl();
                        $categoryName = $category->getName();
                        $childHtml .= '<ul><li><a href="' . $categoryUrl . '">' . $categoryName . '</a></li></ul>';
                    }
                    $childHtml .= '</li>';
                }
            }
        }
        return $childHtml;
    }

    public function getMenuDesign()
    {
        return $this->primaryMenu->getMenuDesignType();
    }

    public function getMenuAlignment()
    {
        return $this->primaryMenu->getMenuAlignment();
    }

    public function isSticky()
    {
        return $this->primaryMenu->getIsSticky();
    }

    public function animationTime()
    {
        return $this->_scopeInterface->getValue(
            'magedelight/general/animation_time',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get cache key informative items
     *
     * @return array
     * @since 100.1.0
     */
    public function getCacheKeyInfo()
    {
        $keyInfo = parent::getCacheKeyInfo();
        $keyInfo[] = $this->getUrl('*/*/*', ['_current' => true, '_query' => '']);
        return $keyInfo;
    }

    /**
     * Get tags array for saving cache
     *
     * @return array
     * @since 100.1.0
     */
    protected function getCacheTags()
    {
        return array_merge(parent::getCacheTags(), $this->getIdentities());
    }


    public function getConfigBurgerStatus()
    {
        $burgerMenuStatus =  $this->_scopeInterface->getValue(
            'magedelight/general/hamburger_menu',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($this->getConfigMenuStatus() && $burgerMenuStatus) {
            return true;
        }

        return false;
    }

     /**
      * Return categories helper
      */
    public function getCategoryHelper()
    {
        return $this->categoryHelper;
    }

    /**
     * Retrieve current store categories
     *
     * @param bool|string $sorted
     * @param bool $asCollection
     * @param bool $toLoad
     * @return \Magento\Framework\Data\Tree\Node\Collection|\Magento\Catalog\Model\Resource\Category\Collection|array
     */
    public function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
        return $this->categoryHelper->getStoreCategories($sorted, $asCollection, $toLoad);
    }
    /**
     * Retrieve child store categories
     *
     */
    public function getChildCategories($category)
    {
        if ($this->categoryFlatState->isFlatEnabled() && $category->getUseFlatResource()) {
             $subcategories = (array)$category->getChildrenNodes();
        } else {
            $subcategories = $category->getChildren();
        }
            return $subcategories;
    }


    /**
     * @param Node $item
     * @return array
     */
    protected function _getMenuItemClasses(Node $item)
    {
        $classes = parent::_getMenuItemClasses($item);

         /* Burger menu for desktop */
        if ($this->getConfigBurgerStatus()) {
            if ($item->getLevel() == 1) {
                if (!empty($this->mdColumnCount) && $this->mdColumnCount != 0) {
                    $classes[] = 'col-'. $this->mdColumnCount;
                }
            }
        }
        return $classes;
    }

    /**
     * @param \Magento\Framework\Data\Tree\Node $child
     * @param string $childLevel
     * @param string $childrenWrapClass
     * @param int $limit
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _addSubMenu($child, $childLevel, $childrenWrapClass, $limit)
    {
        /* Burger menu for desktop */
        if (!$this->getConfigBurgerStatus()) {
            return parent::_addSubMenu($child, $childLevel, $childrenWrapClass, $limit);
        }

        $html = '';

        if ($childLevel == 0) {
            $catIdArray = explode('-', $child->getId());
            // $this->categoryData = $this->categoryFactory->create()->load(end($catIdArray));
            $this->categoryData = $this->categoryFactory->create()->setStoreId($this->getStoreId())->load(end($catIdArray));
            $getLabel = $this->categoryData->getData('md_label');
            $this->getDescription = $this->categoryData->getData('md_category_editor');
            $color = $this->categoryData->getData('md_label_text_color');
            $backgroundColor = $this->categoryData->getData('md_label_background_color');
            $this->mdColumnCount = $this->categoryData->getData('md_column_count');
            if (isset($getLabel) && $getLabel != '') {
                $html .= '<span class="md-label-text" style="color:'. $color .'!important;background-color:'.
                    $backgroundColor .'!important; ">' .__($getLabel).'</span>';
            }
        }

        if (!$child->hasChildren()) {
            return $html;
        }

        $colStops = [];
        if ($childLevel == 0 && $limit) {
            $colStops = $this->_columnBrake($child->getChildren(), $limit);
        }

        if ($childLevel == 0) {
            $html .= '<ul class="level' . $childLevel . ' ' . $childrenWrapClass . '"><li class="md-submenu-container"><ul class="md-categories">';
            $html .= $this->_getHtml($child, $childrenWrapClass, $limit, $colStops);
            $html .= '</ul><ul class="md-categories-image"><li>' . $this->output->categoryAttribute($this->categoryData, $this->getDescription, 'md_category_editor') . '</li></ul></li></ul>';
        } else {
            $html .= '<ul class="level' . $childLevel . ' ' . $childrenWrapClass . '">';
            $html .= $this->_getHtml($child, $childrenWrapClass, $limit, $colStops);
            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @return mixed
     */
    public function getChildrenCategories($category)
    {
        if($this->helper->permissionEnabled()) {
            return $category->getChildrenCategories()
                ->addIsActiveFilter()
                ->addAttributeToFilter('include_in_menu',array('eq' => 1));
        }
        return $category->getChildrenCategories()->addAttributeToFilter('include_in_menu',array('eq' => 1));
    }
}
