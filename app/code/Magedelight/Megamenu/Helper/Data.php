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

namespace Magedelight\Megamenu\Helper;

use \Magento\Customer\Model\GroupFactory;
use Magento\Customer\Model\Session;
use \Magento\Framework\App\Helper\Context;
use \Magento\Framework\App\Helper\AbstractHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Data extends AbstractHelper
{
    /**
     * @var GroupFactory
     */
    public $customerGroupFactory;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $_state;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryCollection;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    /**
     * Data constructor.
     * @param Context $context
     * @param GroupFactory $customerGroupFactory
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        Context $context,
        GroupFactory $customerGroupFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\State $state,
        \Magento\Catalog\Model\CategoryFactory $categoryCollection,
        Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ToggleConfig $toggleConfig
    ) {
        parent::__construct($context);
        $this->customerGroupFactory = $customerGroupFactory;
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
        $this->_state = $state;
        $this->categoryCollection = $categoryCollection;
        $this->_customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->toggleConfig = $toggleConfig;
    }

    /**
     * @return string
     */
    public function getCustomerGroupsOptions()
    {
        $groupCollection = $this->customerGroupFactory->create()->getCollection()
            ->load()
            ->toOptionHash();
        $optionString = '';
        foreach ($groupCollection as $groupId => $code) {
            $optionString .= '<option value="'.$groupId.'">'.$code.'</option>';
        }
        return $optionString;
    }

    /**
     * @return array
     */
    public function getCustomerGroups()
    {
        $groupCollection = $this->customerGroupFactory->create()->getCollection()
            ->load()
            ->toOptionHash();
        return $groupCollection;
    }

    /**
     * @return mixed
     */
    public function isEnabled()
    {
        return $this->getConfig('magedelight/general/megamenu_status');
    }

    /**
     * @return bool
     */
    public function isHumbergerMenu()
    {
        return (bool) $this->getConfig('magedelight/general/hamburger_menu');
    }

    /**
     * @param $config_path
     * @return mixed
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return array
     */
    public function menuTypes()
    {
        return [
          'megamenu'=>'Mega Menu Block',
          'category'=>'Category Selection',
          'pages'=>'Page Selection',
          'link'=>'External Links'
        ];
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getMenuName($key)
    {
        $menuTypes = $this->menuTypes();
        return $menuTypes[$key];
    }

    /**
     * @return bool
     */
    public function isCatalogPermissionExist()
    {
        return (bool) $this->moduleManager->isEnabled('Magento_CatalogPermissions');
    }

    /**
     * @param $instanceName
     * @return mixed
     */
    public function injectPermissionClass($instanceName)
    {
        return $this->objectManager->create($instanceName);
    }

    /**
     * @return bool
     */
    public function permissionEnabled()
    {
        if($this->isCatalogPermissionExist()) {
            $config = $this->injectPermissionClass('Magento\CatalogPermissions\App\ConfigInterface');
            return (bool) $config->isEnabled();
        }
        return false;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getArea()
    {
        return $this->_state->getAreaCode();
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getExcludeCategoryIds($customerGroup = null)
    {
        return [];

        if(!$this->permissionEnabled()) {
            return [];
        }
        if($customerGroup == null)  {
            $customerGroup = $this->_customerSession->getCustomerGroupId();
        }
        $excludeCategoryIds = [];
        $categoryCollection = $this->categoryCollection->create()->getCollection();
        $categoryIds = $categoryCollection->getColumnValues('entity_id');
        if ($categoryIds) {
            $_permissionIndex = $this->injectPermissionClass(
                'Magento\CatalogPermissions\Model\Permission\Index'
            );
            $permissions = $_permissionIndex->getIndexForCategory(
                $categoryIds,
                $customerGroup,
                $this->storeManager->getStore()->getWebsiteId()
            );
            foreach ($permissions as $categoryId => $permission) {
                $categoryCollection->getItemById($categoryId)->setPermissions($permission);
            }
            $_catalogPermData = $this->injectPermissionClass('Magento\CatalogPermissions\Helper\Data');
            foreach ($categoryCollection as $category) {
                if ($category->getData('permissions/grant_catalog_category_view') == -2
                    || $category->getData('permissions/grant_catalog_category_view') != -1
                    && !$_catalogPermData->isAllowedCategoryView()
                ) {
                    $excludeCategoryIds[] = $category->getId();
                }
            }
        }
        return $excludeCategoryIds;
    }
}
