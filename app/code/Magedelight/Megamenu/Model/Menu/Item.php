<?php

namespace Magedelight\Megamenu\Model\Menu;

use Magedelight\Megamenu\Api\Data\MenuItemsInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;

class Item extends \Magedelight\Megamenu\Model\MenuItems implements MenuItemsInterface
{
    /**
     * @return int
     */
    public function getItemId()
    {
        return $this->_getData(MenuItemsInterface::ID);
    }

    /**
     * @return string
     */
    public function getItemType()
    {
        return $this->_getData(MenuItemsInterface::TYPE);
    }

    /**
     * @return string
     */
    public function getItemName()
    {
        return $this->_getData(MenuItemsInterface::NAME);
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->_getData(MenuItemsInterface::SORT);
    }

    /**
     * @return int
     */
    public function getItemParentId()
    {
        return $this->_getData(MenuItemsInterface::PARENT_ID);
    }

    /**
     * @return int
     */
    public function getMenuId()
    {
        return $this->_getData(MenuItemsInterface::MENU_ID);
    }

    /**
     * @return int
     */
    public function getObjectId()
    {
        return $this->_getData(MenuItemsInterface::OBJECT_ID);
    }

    /**
     * @return string
     */
    public function getCreationTime()
    {
        return $this->_getData(MenuItemsInterface::CREATED);
    }

    /**
     * @return string
     */
    public function getUpdateTime()
    {
        return $this->_getData(MenuItemsInterface::UPDATED);
    }

    /**
     * @return string
     */
    public function getItemLink()
    {
        return $this->_getData(MenuItemsInterface::LINK);
    }

    /**
     * @return string
     */
    public function getItemColumns()
    {
        return $this->_getData(MenuItemsInterface::COLUMNS);
    }

    /**
     * @return string
     */
    public function getItemFontIcon()
    {
        return $this->_getData(MenuItemsInterface::ICON);
    }

    /**
     * @return string
     */
    public function getItemClass()
    {
        return $this->_getData(MenuItemsInterface::ITEM_CLASS);
    }

    /**
     * @return string
     */
    public function getAnimationOption()
    {
        return $this->_getData(MenuItemsInterface::ANIMATION);
    }

    /**
     * @return string
     */
    public function getCategoryVerticalMenu()
    {
        return $this->_getData(MenuItemsInterface::VERTICAL_MENU);
    }

    /**
     * @return string
     */
    public function getCategoryVerticalMenuBg()
    {
        return $this->_getData(MenuItemsInterface::VERTICAL_MENU_BG);
    }

    /**
     * @return string
     */
    public function getCategoryDisplay()
    {
        return $this->_getData(MenuItemsInterface::DISPLAY);
    }

    /**
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface[]
     */
    public function getCategoryColumns()
    {
        return $this->_getData(MenuItemsInterface::CAT_COLUMNS);
    }

    /**
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface[]
     */
    public function getChildrens()
    {
        return $this->_getData(MenuItemsInterface::CHILDRENS);
    }

    /**
     * @param int $id
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setItemId($id)
    {
        $this->setData(MenuItemsInterface::ID, $id);
        return $this;
    }

    /**
     * @param string $type
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setItemType($type)
    {
        $this->setData(MenuItemsInterface::TYPE, $type);
        return $this;
    }

    /**
     * @param string $name
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setItemName($name)
    {
        $this->setData(MenuItemsInterface::NAME, $name);
        return $this;
    }

    /**
     * @param int $sort
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setSortOrder($sort)
    {
        $this->setData(MenuItemsInterface::SORT, $sort);
        return $this;
    }

    /**
     * @param int $parentId
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setItemParentId($parentId)
    {
        $this->setData(MenuItemsInterface::PARENT_ID, $parentId);
        return $this;
    }

    /**
     * @param int $menuId
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setMenuId($menuId)
    {
        $this->setData(MenuItemsInterface::MENU_ID, $menuId);
        return $this;
    }

    /**
     * @param int $objectId
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setObjectId($objectId)
    {
        $this->setData(MenuItemsInterface::OBJECT_ID, $objectId);
        return $this;
    }

    /**
     * @param string $created
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setCreationTime($created)
    {
        $this->setData(MenuItemsInterface::CREATED, $created);
        return $this;
    }

    /**
     * @param string $updated
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setUpdateTime($updated)
    {
        $this->setData(MenuItemsInterface::UPDATED, $updated);
        return $this;
    }

    /**
     * @param string $link
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setItemLink($link)
    {
        $this->setData(MenuItemsInterface::LINK, $link);
        return $this;
    }

    /**
     * @param string $columns
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setItemColumns($columns)
    {
        $this->setData(MenuItemsInterface::COLUMNS, $columns);
        return $this;
    }

    /**
     * @param string $icon
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setItemFontIcon($icon)
    {
        $this->setData(MenuItemsInterface::ICON, $icon);
        return $this;
    }

    /**
     * @param string $class
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setItemClass($class)
    {
        $this->setData(MenuItemsInterface::ITEM_CLASS, $class);
        return $this;
    }

    /**
     * @param string $animation
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setAnimationOption($animation)
    {
        $this->setData(MenuItemsInterface::ANIMATION, $animation);
        return $this;
    }

    /**
     * @param string $verticalMenu
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setCategoryVerticalMenu($verticalMenu)
    {
        $this->setData(MenuItemsInterface::VERTICAL_MENU, $verticalMenu);
        return $this;
    }

    /**
     * @param string $verticalMenuBg
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setCategoryVerticalMenuBg($verticalMenuBg)
    {
        $this->setData(MenuItemsInterface::VERTICAL_MENU_BG, $verticalMenuBg);
        return $this;
    }

    /**
     * @param string $display
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setCategoryDisplay($display)
    {
        $this->setData(MenuItemsInterface::DISPLAY, $display);
        return $this;
    }

    /**
     * @param \Magedelight\Megamenu\Api\Data\MenuItemsInterface[] $menuColumns
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setCategoryColumns($menuColumns)
    {
        $this->setData(MenuItemsInterface::CAT_COLUMNS, $menuColumns);
        return $this;
    }

    /**
     * @param \Magedelight\Megamenu\Api\Data\MenuItemsInterface[] $childrens
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setChildrens($childrens)
    {
        $this->setData(MenuItemsInterface::CHILDRENS, $childrens);
        return $this;
    }
}