<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magedelight\Megamenu\Helper;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class Category
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var int
     */
    protected $group = 0;

    /**
     * Category constructor.
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        DataObjectFactory $dataObjectFactory,
        Data $helper
    ) {
        $this->_storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->helper = $helper;
    }

    /**
     * @param $menuItem \Magedelight\Megamenu\Model\MenuItems
     * @param $store
     * @return array
     * @throws LocalizedException
     */
    public function getCategoryTreeById($menuItem,$customerGroup = null)
    {
        if($customerGroup) {
            $this->group = $customerGroup;
        }
        $id = $menuItem->getData('object_id');
        $collection = $this->categoryCollectionFactory->create()
            ->addAttributeToFilter('is_active', '1')
            ->addAttributeToFilter('include_in_menu', '1')
            ->addAttributeToFilter('parent_id',['eq' => $id])
            ->addAttributeToSelect(['name', 'image']);
        $data = [];
        foreach ($collection as $category) {
            /** @var $category \Magento\Catalog\Model\Category */
            if ($category->getIsActive() && $category->getIncludeInMenu() && $this->isAllowPermission($category->getId())) {
                $data[] = $this->getTree($category, $menuItem,null, 0);
            }
        }
        return $data;
    }

    /**
     * @param $node
     * @param $depth
     * @param $currentLevel
     * @return array
     * @throws LocalizedException
     */
    protected function getChildren($node, $menuItem, $depth, $currentLevel)
    {
        /** @var $node \Magento\Catalog\Model\Category */
        if ($node->hasChildren() && $node->getLevel() <= 10) {
            $children = [];
            $childCategory = explode(',', $node->getChildren());
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect(['*'])
                ->addAttributeToFilter('is_active', '1')
                ->addAttributeToFilter('include_in_menu', '1')
                ->addAttributeToFilter('entity_id', ['in' => $childCategory])
                ->addAttributeToSort('position', 'asc');
            foreach ($collection as $child) {
                if ($depth !== null && $depth <= $currentLevel) {
                    break;
                }
                if ($child->getIsActive() && $child->getIncludeInMenu() && $this->isAllowPermission($child->getId())) {
                    if ($this->getTree($child,$menuItem, $depth, $currentLevel + 1)) {
                        $children[] = $this->getTree($child, $menuItem, $depth, $currentLevel + 1);
                    }
                }
            }
            return $children;
        }
        return [];
    }

    /**
     * @param $node
     * @param null $depth
     * @param int $currentLevel
     * @return mixed
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTree($node, $menuItem, $depth = null, $currentLevel = 0)
    {
        /** @var $node \Magento\Catalog\Model\Category */
        $children = $this->getChildren($node, $menuItem, $depth, $currentLevel);
        $tree = $this->dataObjectFactory->create();
        $data = [
            'item_id' => $node->getId(),
            'item_name' => $node->getName(),
            'item_type' => 'category',
            'sort_order' => $node->getPosition(),
            'item_parent_id' => $menuItem->getData('item_id'),
            'menu_id' => $menuItem->getData('menu_id'),
            'object_id' => $node->getId(),
            'item_link' => $node->getUrl(),
            'item_columns' => null,
            'item_font_icon' => null,
            'item_class' => null,
            'animation_option' => '',
            'category_display' => $menuItem->getData('category_display'),
            'category_vertical_menu' => $menuItem->getData('category_vertical_menu'),
            'category_vertical_menu_bg' => $menuItem->getData('category_vertical_menu_bg'),
            'category_columns' => [],
            'creation_time' => $menuItem->getData('creation_time'),
            'update_time' => $menuItem->getData('update_time'),
        ];
        $tree->setData($data);
        if ($children) {
            $tree->setData('childrens',$children);
        }
        return $tree->getData();
    }

    /**
     * @param $id
     * @param $customerGroup
     * @return bool
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isAllowPermission($id)
    {
        $customerGroup = $this->group;
        if (!$this->helper->permissionEnabled()) {
            return true;
        }
        $excludeCategoryIds = $this->helper->getExcludeCategoryIds($customerGroup);
        if (in_array($id, $excludeCategoryIds)) {
            return false;
        }
        return true;
    }
}