<?php

namespace Magedelight\Megamenu\Api\Data;

interface MenuItemsInterface
{
    const ID = 'item_id';
    const TYPE = 'item_type';
    const NAME = 'item_name';
    const SORT = 'sort_order';
    const PARENT_ID = 'item_parent_id';
    const MENU_ID = 'menu_id';
    const OBJECT_ID = 'object_id';
    const CREATED = 'creation_time';
    const UPDATED = 'update_time';
    const LINK = 'item_link';
    const COLUMNS = 'item_columns';
    const ICON = 'item_font_icon';
    const ITEM_CLASS = 'item_class';
    const ANIMATION = 'animation_option';
    const VERTICAL_MENU = 'category_vertical_menu';
    const VERTICAL_MENU_BG = 'category_vertical_menu_bg';
    const DISPLAY = 'category_display';
    const CAT_COLUMNS = 'category_columns';
    const CHILDRENS = 'childrens';
    /**
     * @return int
     */
    public function getItemId();

    /**
     * @return string
     */
    public function getItemType();

    /**
     * @return string
     */
    public function getItemName();

    /**
     * @return int
     */
    public function getSortOrder();

    /**
     * @return int
     */
    public function getItemParentId();

    /**
     * @return int
     */
    public function getMenuId();

    /**
     * @return int
     */
    public function getObjectId();

    /**
     * @return string
     */
    public function getCreationTime();

    /**
     * @return string
     */
    public function getUpdateTime();

    /**
     * @return string
     */
    public function getItemLink();

    /**
     * @return string
     */
    public function getItemColumns();

    /**
     * @return string
     */
    public function getItemFontIcon();

    /**
     * @return string
     */
    public function getItemClass();

    /**
     * @return string
     */
    public function getAnimationOption();

    /**
     * @return string
     */
    public function getCategoryVerticalMenu();

    /**
     * @return string
     */
    public function getCategoryVerticalMenuBg();

    /**
     * @return string
     */
    public function getCategoryDisplay();

    /**
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface[]
     */
    public function getCategoryColumns();

    /**
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface[]
     */
    public function getChildrens();

    /**
     * @param int $id
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setItemId($id);

    /**
     * @param string $type
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setItemType($type);

    /**
     * @param string $name
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setItemName($name);

    /**
     * @param int $sort
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setSortOrder($sort);

    /**
     * @param int $parentId
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setItemParentId($parentId);

    /**
     * @param int $menuId
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setMenuId($menuId);

    /**
     * @param int $objectId
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setObjectId($objectId);

    /**
     * @param string $created
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setCreationTime($created);

    /**
     * @param string $updated
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setUpdateTime($updated);

    /**
     * @param string $link
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setItemLink($link);

    /**
     * @param string $columns
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setItemColumns($columns);

    /**
     * @param string $icon
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setItemFontIcon($icon);

    /**
     * @param string $class
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setItemClass($class);

    /**
     * @param string $animation
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setAnimationOption($animation);

    /**
     * @param string $verticalMenu
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setCategoryVerticalMenu($verticalMenu);

    /**
     * @param string $verticalMenuBg
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setCategoryVerticalMenuBg($verticalMenuBg);

    /**
     * @param string $display
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setCategoryDisplay($display);

    /**
     * @param \Magedelight\Megamenu\Api\Data\MenuItemsInterface[] $menuColumns
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setCategoryColumns($menuColumns);

    /**
     * @param \Magedelight\Megamenu\Api\Data\MenuItemsInterface[] $childrens
     *
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface
     */
    public function setChildrens($childrens);
}