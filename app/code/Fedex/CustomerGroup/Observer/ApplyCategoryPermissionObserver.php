<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomerGroup\Observer;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Helper\Data;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\CatalogPermissions\Observer\ApplyPermissionsOnCategory;
use Magento\CatalogPermissions\Observer\ApplyCategoryPermissionObserver as CoreApplyCategoryPermissionObserver;
use Fedex\SelfReg\Helper\SelfReg as SelfRegHelper;
use Magento\Store\Model\StoreManagerInterface;

class ApplyCategoryPermissionObserver extends CoreApplyCategoryPermissionObserver
{
    /**
     * Constructor
     *
     * @param ConfigInterface $permissionsConfig
     * @param StoreManagerInterface $storeManager
     * @param Session $customerSession
     * @param Index $permissionIndex
     * @param Data $catalogPermData
     * @param ApplyPermissionsOnCategory $applyPermissionsOnCategory
     * @param SelfRegHelper $selfRegHelper
     */
    public function __construct(
        protected ConfigInterface $permissionsConfig,
        protected StoreManagerInterface $storeManager,
        protected Session $customerSession,
        protected Index $permissionIndex,
        protected Data $catalogPermData,
        ApplyPermissionsOnCategory $applyPermissionsOnCategory,
        protected SelfRegHelper $selfRegHelper
    ) {
        parent::__construct(
            $permissionsConfig,
            $storeManager,
            $customerSession,
            $permissionIndex,
            $catalogPermData,
            $applyPermissionsOnCategory
        );
    }

    /**
     * Applies category permission on model afterload
     *
     * @param EventObserver $observer
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(EventObserver $observer)
    {
        return parent::execute($observer);
    }
}
