<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomerGroup\Observer;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\CatalogPermissions\Observer\ApplyCategoryPermissionOnIsActiveFilterToCollectionObserver as CoreApplyCategoryPermissionOnIsActiveFilterToCollectionObserver;
use Fedex\SelfReg\Helper\SelfReg as SelfRegHelper;

class ApplyCategoryPermissionOnIsActiveFilterToCollectionObserver extends CoreApplyCategoryPermissionOnIsActiveFilterToCollectionObserver
{
    /**
     * Permissions index instance
     *
     * @var Index
     */
    protected $_permissionIndex;

    /**
     * Customer session instance
     *
     * @var Session
     */
    protected $_customerSession;

    /**
     * Store manager instance
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Permissions configuration instance
     *
     * @var ConfigInterface
     */
    protected $_permissionsConfig;


    /**
     * Constructor
     *
     * @param ConfigInterface $permissionsConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Session $customerSession
     * @param Index $permissionIndex
     * @param SelfRegHelper $selfRegHelper
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Session $customerSession,
        Index $permissionIndex,
        protected SelfRegHelper $selfRegHelper
    ) {
        parent::__construct(
            $permissionsConfig,
            $storeManager,
            $customerSession,
            $permissionIndex
        );
    }

    /**
     * Apply category permissions for category collection
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        return parent::execute($observer);
    }
}
