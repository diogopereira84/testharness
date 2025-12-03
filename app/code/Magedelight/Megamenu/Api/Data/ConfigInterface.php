<?php

namespace Magedelight\Megamenu\Api\Data;

interface ConfigInterface
{
    /**
     * @return int
     */
    public function getMenuId();

    /**
     * @return string
     */
    public function getMenuDesignType();

    /**
     * @return string
     */
    public function getMenuName();

    /**
     * @return string
     */
    public function getMenuAlignment();

    /**
     * @return boolean
     */
    public function getIsActive();

    /**
     * @return int
     */
    public function getMenuType();

    /**
     * @return int
     */
    public function getIsSticky();

    /**
     * @return string
     */
    public function getCustomerGroups();

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
    public function getStoreId();

    /**
     * @return string
     */
    public function getMenuStyle();

    /**
     * @return \Magedelight\Megamenu\Api\Data\MenuItemsInterface[]
     */
    public function getMenuItems();
}