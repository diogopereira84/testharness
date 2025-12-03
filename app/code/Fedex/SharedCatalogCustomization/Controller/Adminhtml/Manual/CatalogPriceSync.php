<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SharedCatalogCustomization\Controller\Adminhtml\Manual;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Backend\Model\Auth\Session;
use Fedex\CatalogMvp\Helper\CatalogPriceSyncHelper;

/**
 * CatalogSync Controller
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CatalogPriceSync extends \Magento\Framework\App\Action\Action
{
    /**
     * @var RedirectFactory
     */
    protected $redirectFactory;

    /**
     * @var Data
     */
    protected $catalogPriceSyncHelper;
    private RedirectFactory $resultRedirect;

    /**
     * Manual Sync Catalog Shared Catalog constructor.
     *
     * @param Context $context
     * @param RedirectFactory $redirectFactory
     * @param Session $authSession
     * @param Data $catalogSyncQueueHelper
     */
    public function __construct(
        Context $context,
        RedirectFactory $redirectFactory,
        protected Session $authSession,
        CatalogPriceSyncHelper $catalogPriceSyncHelper
    ) {
        $this->resultRedirect = $redirectFactory;
        $this->catalogPriceSyncHelper = $catalogPriceSyncHelper;

        return parent::__construct($context);
    }

    /**
     * Check Folder Id exist and then intialize queue shared catalog
     *
     * @return string url shared_catalog/sharedCatalog/index
     */
    public function execute()
    {

        $resultRedirect = $this->resultRedirect->create();
        $userData = $this->authSession->getUser();
        $userName = $userData->getFirstname() . ' ' . $userData->getLastname();
        $emailId   = $userData->getEmail();

        $manualSchedule = true;

        $sharedCatalogName = $this->getRequest()->getParam('name') ?? null;
        $sharedCatalogCustomerGroupId = (int) $this->getRequest()->getParam('customer_group_id') ?? null;
        $sharedCatalogId = $this->getRequest()->getParam('shared_catalog_id') ?? null;
        $categoryId = $this->getRequest()->getParam('category_id') ?? null;

        $this->catalogPriceSyncHelper->createSyncCatalogPriceQueue(
            $sharedCatalogCustomerGroupId,
            $sharedCatalogId,
            $categoryId,
            $sharedCatalogName,
            $userName,
            $manualSchedule,
            $emailId
        );
        return $resultRedirect->setPath('shared_catalog/sharedCatalog/index');
    }
}
