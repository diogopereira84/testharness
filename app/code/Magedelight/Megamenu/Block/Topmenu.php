<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace Magedelight\Megamenu\Block;

use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Data\Tree\NodeFactory;
use Magento\Framework\Data\TreeFactory;
use Magento\Framework\View\Element\Template;
use Magento\Theme\Block\Html\Topmenu as MagentoTopmenu;
use Magedelight\Megamenu\Model\Menu;

/**
 * Class Topmenu
 *
 * @package Magedelight\Megamenu\Block
 */
class Topmenu extends MagentoTopmenu
{
    const MEGA_MENU_TEMPLATE = 'Magedelight_Megamenu::menu/new-topmenu.phtml';
    const BURGER_MENU_TEMPLATE = 'Magedelight_Megamenu::menu/burger.phtml';

    protected $registry;

    protected $helper;

    protected $customerSession;

    protected $megamenuManagement;

    protected $_page;

    /**
     * @var \Magedelight\Megamenu\Api\Data\ConfigInterface
     */
    public $primaryMenu;

    public $primaryMenuId = 0;

    public $categoryData;

    protected $mdColumnCount = 10;

    protected $getDescription;

    protected $output;

    /**
     * Topmenu constructor.
     * @param Template\Context $context
     * @param NodeFactory $nodeFactory
     * @param TreeFactory $treeFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\SessionFactory $session
     * @param \Magento\Cms\Model\Page $page
     * @param \Magedelight\Megamenu\Helper\Data $helper
     * @param \Magedelight\Megamenu\Model\MegamenuManagement $megamenuManagement
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        NodeFactory $nodeFactory,
        TreeFactory $treeFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\SessionFactory $session,
        \Magento\Cms\Model\Page $page,
        \Magedelight\Megamenu\Helper\Data $helper,
        \Magedelight\Megamenu\Model\MegamenuManagement $megamenuManagement,
        \Magento\Catalog\Helper\Output $output,
        array $data = []
    ) {
        parent::__construct($context, $nodeFactory, $treeFactory, $data);
        $this->registry = $registry;
        $this->customerSession = $session;
        $this->helper = $helper;
        $this->megamenuManagement = $megamenuManagement;
        $this->_page = $page;
        $this->output = $output;
    }

    /**
     * @return int
     */
    protected function getCacheLifetime()
    {
        return parent::getCacheLifetime() ?: 3600;
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

    /**
     * @return mixed
     */
    public function getCurrentCat()
    {
        $category = $this->registry->registry('current_category');
        if (isset($category) and ! empty($category->getId())) {
            return $category->getId();
        }
        return '';
    }

    /**
     * @return int
     */
    public function getCurentPage()
    {
        if ($this->_page->getId()) {
            return $pageId = $this->_page->getId();
        }
        return '';
    }

    /**
     * @param $template
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setCustomTemplate($template)
    {
        $this->setTemplate($template);
        if($this->helper->isEnabled()) {
            $_customerSession = $this->customerSession->create();
            if($_customerSession->isLoggedIn()) {
                $this->primaryMenu = $this->megamenuManagement->getMenuData($_customerSession->getCustomerId())->getMenu();
            } else {
                $this->primaryMenu = $this->megamenuManagement->getMenuData()->getMenu();
            }
            $this->primaryMenuId = $this->primaryMenu->getMenuId();
            if($this->primaryMenu->getIsActive()) {
                if($this->helper->isHumbergerMenu()) {
                    $this->setTemplate(self::BURGER_MENU_TEMPLATE);
                } else {
                    if ($this->primaryMenu->getMenuType() == Menu::MEGA_MENU) {
                        $this->setTemplate(self::MEGA_MENU_TEMPLATE);
                    }
                }
            }
        }
    }

    /**
     * @param string $outermostClass
     * @param string $childrenWrapClass
     * @param int $limit
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getHtml($outermostClass = '', $childrenWrapClass = '', $limit = 0)
    {
            if ($this->helper->isHumbergerMenu()) {
                return $this->getHumbergerMenuHtml($outermostClass,$childrenWrapClass,$limit);
            }
            return $this->getMegaMenuHtml($outermostClass,$childrenWrapClass,$limit);
        }

        /**
         * @param $outermostClass
         * @param $childrenWrapClass
         * @param $limit
         * @return string
         */
    public function getHumbergerMenuHtml($outermostClass,$childrenWrapClass,$limit)
    {
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

    /**
     * @param $outermostClass
     * @param $childrenWrapClass
     * @param $limit
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMegaMenuHtml($outermostClass,$childrenWrapClass,$limit)
    {
        $this->_eventManager->dispatch(
            'page_block_html_topmenu_gethtml_before',
            ['menu' => $this->_menu, 'block' => $this,'request' => $this->getRequest()]
        );
        $this->_menu->setOutermostClass($outermostClass);
        $this->_menu->setChildrenWrapClass($childrenWrapClass);
        if($this->helper->isEnabled() && $this->primaryMenu->getIsActive())  {
            $menuItems = $this->megamenuManagement->loadMenuItems(0,'ASC');
            if ($this->primaryMenu->getMenuType() == Menu::MEGA_MENU) {
                $html = '';
                foreach ($menuItems as $item) {
                    $childrenWrapClass = "level0 nav-1 first parent main-parent";
                    $html .= $this->setMegamenu($item, $childrenWrapClass);
                }
            } else {
                $parent = 'root';
                $level = 0;
                $html = $this->setPrimaryMenu($menuItems, $level, $parent, $outermostClass);
            }
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
     * @param $menuItems
     * @param $level
     * @param $parent
     * @param $outermostClass
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setPrimaryMenu($menuItems, $level = 0, $parent = '', $outermostClass = '')
    {
        $megaMenuItemData = [
            'menu_block' => $this,
            'menu_items' => $menuItems,
            'level' => $level,
            'parent_node' => $parent,
            'menu_management' => $this->megamenuManagement
        ];
        $megaMenuItemBlock = $this->getLayout()->createBlock('Magento\Framework\View\Element\Template');
        /** @var $megaMenuItemBlock \Magento\Framework\View\Element\Template */
        $megaMenuItemBlock->setData($megaMenuItemData);
        $megaMenuItemBlock->setTemplate('Magedelight_Megamenu::menu/items/primaryMenu.phtml');
        return trim(preg_replace('/\s\s+/', ' ', (string)$megaMenuItemBlock->toHtml()));
    }

    /**
     * @param $item \Magedelight\Megamenu\Model\MenuItems|\Magedelight\Megamenu\Api\Data\MenuItemsInterface
     * @param $key
     * @param $value
     * @return mixed
     */
    public function getCmsBlockConfig($item,$key,$value)
    {
        $blockType = ['header','bottom','left','right'];
        if($value == 'enable') {
            $initValue = 0;
        }
        if($value == 'block') {
            $initValue = "";
        }
        if($value == 'title') {
            $initValue = "0";
        }
        $config[$key] = [$value => $initValue];
        if ($item->getCategoryColumns()) {
            $categoryColumns = json_decode($item->getCategoryColumns());
            foreach ($categoryColumns as $categoryColumn) {
                foreach ($blockType as $type) {
                    if ($categoryColumn->type === $type) {
                        $config[$type] = [
                            'enable' => (int) $categoryColumn->enable,
                            'block' => $categoryColumn->value,
                            'title' => $categoryColumn->showtitle
                        ];
                    }
                }
            }
        }
        return $config[$key][$value];
    }

    /**
     * @param $item \Magedelight\Megamenu\Model\MenuItems|\Magedelight\Megamenu\Api\Data\MenuItemsInterface
     * @param $childrenWrapClass
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setMegamenu($item, $childrenWrapClass)
    {
        $html = '';
        $megaMenuItemData = [
            'menu_block' => $this,
            'menu_item' => $item,
            'menu_management' => $this->megamenuManagement
        ];
        $megaMenuItemBlock = $this->getLayout()->createBlock('Magento\Framework\View\Element\Template');
        /** @var $megaMenuItemBlock \Magento\Framework\View\Element\Template */
        $megaMenuItemBlock->setData($megaMenuItemData);
        if($item->getItemType() == 'megamenu') {
            $megaMenuItemBlock->setTemplate('Magedelight_Megamenu::menu/items/megaMenuItemBlock.phtml');
            $html .= trim(preg_replace('/\s\s+/', ' ', (string)$megaMenuItemBlock->toHtml()));
        } else {
            $megaMenuItemBlock->setTemplate('Magedelight_Megamenu::menu/items/menuItemBlock.phtml');
            $html .= trim(preg_replace('/\s\s+/', ' ', (string)$megaMenuItemBlock->toHtml()));
        }
        return $html;
    }

    /**
     * @return string
     */
    public function getMenuClass()
    {
        $class = "menu ";
        $class .= $this->primaryMenu->getMenuDesignType().' ';
        $class .= $this->primaryMenu->getMenuDesignType() == 'horizontal' ?
            $this->primaryMenu->getMenuAlignment().' ' :
            '';
        $class .= $this->primaryMenu->getIsSticky() == '1' ? 'stickymenu ' : '';
        return $class;
    }

    /**
     * @param $menuItem \Magedelight\Megamenu\Model\MenuItems|\Magedelight\Megamenu\Api\Data\MenuItemsInterface
     * @return string
     */
    public function getActiveClass($menuItem)
    {
        if ($menuItem->getItemType() == 'category') {
            if ($menuItem->getObjectId() == $this->getCurrentCat()) {
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
     * @param $menuItems
     * @param $key
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getChildColumnForMenuType($menuItems,$key)
    {
        $megaMenuItemData = [
            'menu_block' => $this,
            'menu_items' => $menuItems,
            'items_key' => $key,
            'menu_management' => $this->megamenuManagement
        ];
        $megaMenuItemBlock = $this->getLayout()->createBlock('Magento\Framework\View\Element\Template');
        /** @var $megaMenuItemBlock \Magento\Framework\View\Element\Template */
        $megaMenuItemBlock->setData($megaMenuItemData);
        $megaMenuItemBlock->setTemplate('Magedelight_Megamenu::menu/items/megaMenuItemBlock/typeMenu.phtml');
        return trim(preg_replace('/\s\s+/', ' ', (string)$megaMenuItemBlock->toHtml()));
    }

    /**
     * @param $menuItems
     * @param $key
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getChildColumnForMenuTypeBlock($menuItems,$key)
    {
        $megaMenuItemData = [
            'menu_block' => $this,
            'menu_items' => $menuItems,
            'items_key' => $key,
            'menu_management' => $this->megamenuManagement
        ];
        $megaMenuItemBlock = $this->getLayout()->createBlock('Magento\Framework\View\Element\Template');
        /** @var $megaMenuItemBlock \Magento\Framework\View\Element\Template */
        $megaMenuItemBlock->setData($megaMenuItemData);
        $megaMenuItemBlock->setTemplate('Magedelight_Megamenu::menu/items/megaMenuItemBlock/typeBlock.phtml');
        return trim(preg_replace('/\s\s+/', ' ', (string)$megaMenuItemBlock->toHtml()));
    }

    /**
     * @param $menuItems
     * @param $key
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getChildColumnForMenuTypeCategory($menuItems,$key)
    {
        $megaMenuItemData = [
            'menu_block' => $this,
            'menu_items' => $menuItems,
            'items_key' => $key,
            'menu_management' => $this->megamenuManagement
        ];
        $megaMenuItemBlock = $this->getLayout()->createBlock('Magento\Framework\View\Element\Template');
        /** @var $megaMenuItemBlock \Magento\Framework\View\Element\Template */
        $megaMenuItemBlock->setData($megaMenuItemData);
        $megaMenuItemBlock->setTemplate('Magedelight_Megamenu::menu/items/megaMenuItemBlock/typeCategory.phtml');
        return trim(preg_replace('/\s\s+/', ' ', (string)$megaMenuItemBlock->toHtml()));
    }

    /**
     * @param $menuItems
     * @param $key
     * @param $category
     * @param $subCats
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getChildColumnForSubCategory($menuItems,$key,$category,$subCats, $skipTitle = false, $level = 1)
    {
        $childHtml = '';
        if(!$skipTitle) {
            $categoryArray = $this->prepareCategoryItemsForMenuColumn($subCats,$menuItems[$key]);
        } else {
            $categoryArray = $subCats;
        }
        /** @var $category \Magento\Catalog\Model\Category */
        if ($menuItems[$key]->showtitle == '1' && !$skipTitle) {
            $childHtml .= '<h2>' . __($category->getName()) . '</h2>';
        }

        $childHtml .= '<ul class="child-column-megamenu-block child-level-'.$level.'">';
        foreach ($categoryArray as $cat) {
            $verticalclass = $cat['id'] == $this->getCurrentCat() ? 'active' : '';
            $liClass = count($cat['childrens']) > 0 ? 'cat-has-child' : 'cat-no-child';
            $childHtml .= '<li class="'.$liClass.' '.$verticalclass.'">';
            $childHtml .= '<a href="'.$cat['url'].'">'.__($cat['label']).'</a>';
            if(!empty($cat['childrens'])) {
                $childHtml .= $this->getChildColumnForSubCategory($menuItems,$key,$category,$cat['childrens'],true,$level+1);
            }
            $childHtml .= '</li>';
        }
        $childHtml .= '</ul>';
        return $childHtml;
    }

    /**
     * @param $subcats
     * @param $item
     * @param int $level
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepareCategoryItemsForMenuColumn($subcats,$item,$level=1)
    {
        $leftArray = [];
        foreach ($subcats as $subcat) {
            $maxLevel = $item->categoryLevel ? (int) $item->categoryLevel : 2;
            if($maxLevel < $level) {
                break;
            }
            $_category = $this->megamenuManagement->getCategoryById($subcat->getId());
            $childrenCats = $this->megamenuManagement->getChildrenCategories($_category);
            $group = [
                'id' => $subcat->getId(),
                'label' => $_category->getName(),
                'url' => $_category->getUrl(),
                'position' => $_category->getPosition(),
                'childrens' => $this->prepareCategoryItemsForMenuColumn($childrenCats,$item,$level+1)
            ];
            $leftArray[] = $group;
        }
        return $this->sortByOrder($leftArray,$item);
    }

    /**
     * @param $categoryArray
     * @param $item
     * @return mixed
     */
    public function sortByOrder($categoryArray,$item)
    {
        usort($categoryArray, function ($x, $y) {
            return strcasecmp($x['position'], $y['position']);
        });
        if($item->catSortBy && $item->catSortOrder) {
            if($item->catSortBy == 'name' && $item->catSortOrder == 'asc') {
                usort($categoryArray, function ($x, $y) {
                    return strcasecmp($x['label'], $y['label']);
                });
            }
            if($item->catSortBy == 'name' && $item->catSortOrder == 'desc') {
                usort($categoryArray, function ($x, $y) {
                    return strcasecmp($y['label'], $x['label']);
                });
            }
            if($item->catSortBy == 'position' && $item->catSortOrder == 'desc') {
                usort($categoryArray, function ($x, $y) {
                    return strcasecmp($y['position'], $x['position']);
                });
            }
        }
        return $categoryArray;
    }

    /**
     * @param $subcats
     * @param $item
     * @param bool $childs
     * @param int $level
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setChildCategoryColumn($subcats,$item,$columnCount = 0,$childs = false,$level = 1)
    {
        if(!$childs) {
            $categoryArray = $this->prepareCategoryItems($subcats,$item);
        } else {
            $categoryArray = $subcats;
        }

        if(!$categoryArray) {
            return '';
        }
        $html = '';
        $ulClass = '';
        if($columnCount !== 0) {
            $ulClass .= 'column'.$columnCount.' child-level-1';
        } else {
            $ulClass .= 'child-level-'.$level;
        }
        $html .= '<ul class="'.$ulClass.'">';
        foreach ($categoryArray as $cat) {
            $verticalclass = $cat['id'] == $this->getCurrentCat() ? 'active' : '';
            $uniqueClass = 'category-item nav-'.$item->getItemId().'-'.$cat['id'];
            $liClass = $uniqueClass.' '.$verticalclass;
            $html .= '<li class="'.$liClass.'">';
            $html .= '<a href="'.$cat['url'].'">'.__($cat['label']).'</a>';
            $html .= $this->setChildCategoryColumn($cat['childrens'],$item,0,true,$level+1);
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     * @param $subcats
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepareCategoryItems($subcats,$item,$level=1)
    {
        $leftArray = [];
        /** @var $subcats \Magento\Catalog\Model\ResourceModel\Category\Collection */
        if($item->getVerticalCatSortby() && $item->getVerticalCatSortorder()) {
            $subcats->addAttributeToSort(
                $item->getVerticalCatSortby(),
                $item->getVerticalCatSortorder()
            );
        }
        foreach ($subcats as $subcat) {
            if(in_array($subcat->getId(),$this->getExcludeCategoryItemId($item))) {
                continue;
            }
            $maxLevel = $item->getVerticalCatLevel() ? (int) $item->getVerticalCatLevel() : 2;
            if($maxLevel < $level) {
                break;
            }
            $_category = $this->megamenuManagement->getCategoryById($subcat->getId());
            $childrenCats = $this->megamenuManagement->getChildrenCategories($_category);
            $group = [
                'id' => $subcat->getId(),
                'label' => $_category->getName(),
                'url' => $_category->getUrl(),
                'childrens' => $this->prepareCategoryItems($childrenCats,$item,$level+1)
            ];
            $leftArray[] = $group;
        }
        return $leftArray;
    }

    /**
     * @param $item
     * @return array
     */
    public function getExcludeCategoryItemId($item)
    {
        $categories = [];
        $excludeCategory = $item->getVerticalCatExclude();
        if($excludeCategory) {
            $categories = explode(',',$excludeCategory);
        }
        return $categories;
    }

    /**
     * @param $item
     * @param $subcats
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setVerticalCategoryItem($item,$subcats)
    {
        $leftArray = $this->prepareCategoryItems($subcats,$item);
        $childHtml = '<div class="col-menu-9 vertical-menu-content">';
        $html = '<div class="col-menu-3 vertical-menu-left" style="background:#'.$item->getCategoryVerticalMenuBg().';">';
        $html .= '<ul class="vertical-menu-left-nav">';
        foreach ($leftArray as $key => $subcat) {
            $verticalclass = $subcat['id'] == $this->getCurrentCat() ? 'active' : '';
            $addDropdownClass = !empty($childrenCats) ? " dropdown" : "";
            $uniqueClass = 'menu-vertical-items nav-'.$item->getItemId();
            $liClass = $uniqueClass.' '.$verticalclass.' '.$addDropdownClass;
            $datToggle = 'subcat-tab-'.$subcat['id'];
            $html .= '<li class="'.$liClass.'" data-toggle="'.$datToggle.'">';
            $html .= '<a href="'.$subcat['url'].'">'.__($subcat['label']).'</a>';
            $html .= '</li>';
            $childHtml .= $this->setVerticalRightParentItem($subcat);
        }
        $html .= '</ul>';
        // End Left Column
        $html .= '</div>';
        $childHtml .= '</div>';
        return $html.$childHtml;
    }

    /**
     * @param $childrens
     * @return string
     */
    public function setVerticalRightParentItem($childrens)
    {
        $html = '';
        $columnCountForVerticalMenu = count($childrens['childrens']) >= 3 ? 3 : count($childrens['childrens']);
        $html .= '<div id="subcat-tab-' . $childrens['id'] . '" class="vertical-subcate-content">';
        $html .= '<ul class="menu-vertical-child child-level-3 column' . $columnCountForVerticalMenu . '">';
        foreach ($childrens['childrens'] as $child) {
            $verticalclass = $child['id'] == $this->getCurrentCat() ? 'active' : '';
            $html .= '<li class="' . $verticalclass . '">';
            $html .= '<h4 class="level-3-cat">';
            $html .= '<a href="' . $child['url'] . '">' . $child['label'] . '</a>';
            $html .= '</h4>';
            $html .= $this->setVerticalRightChildItem($child);
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '</div>';
        return $html;
    }

    /**
     * @param $childrens
     * @return string
     */
    public function setVerticalRightChildItem($childrens,$level = 4)
    {
        $html = '';
        if(empty($childrens['childrens'])) {
           return '';
        }
        $html .= '<ul class="menu-vertical-child-item child-level-'.$level.'">';
        foreach ($childrens['childrens'] as $child) {
            $verticalclass = $child['id'] == $this->getCurrentCat() ? 'active' : '';
            $html .= '<li class="' . $verticalclass . '">';
            $html .= '<a href="' . $child['url'] . '">' . $child['label'] . '</a>';
            if(!empty($child['childrens'])) {
                $html .= $this->setVerticalRightChildItem($child,$level+1);
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBlockObjectHtml($id)
    {
        $blockObject = $this->getLayout()->createBlock('Magento\Cms\Block\Block');
        $blockObject->setBlockId($id);
        return $blockObject->toHtml();
    }

    /**
     * @param $id
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createCmsBlockHtml($id,$title,$class)
    {
        $html = '';
        $headerblock = $this->megamenuManagement->loadCmsBlock($id);
        $html .= '<li class="'.$class.'">';
        if ($title === '1') {
            $html .= '<h2>' . $headerblock->getTitle() . '</h2>';
        }
        $html .= '<ul><li>' . $this->getBlockObjectHtml($id) . '</li>';
        $html .= '</ul></li>';
        return $html;
    }

    /**
     * @return string
     */
    public function menuStyleHtml()
    {
        if (!empty(trim((string)$this->primaryMenu->getMenuStyle()))) {
            return '<style>' . $this->primaryMenu->getMenuStyle() . '</style>';
        }
        return '';
    }

    /**
     * @return mixed
     */
    public function animationTime()
    {
        return $this->helper->getConfig('magedelight/general/animation_time');
    }

    /**
     * @return bool
     */
    public function getConfigBurgerStatus()
    {
        if ($this->helper->isEnabled() && $this->helper->isHumbergerMenu()) {
            return true;
        }

        return false;
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
            $this->categoryData = $this->megamenuManagement->getCategoryById(end($catIdArray));
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
}
