<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CatalogMvp\ViewModel\MvpHelper;



/**
 * Controller for obtaining stores suggestions by query.
 */
class AddBodyClass extends Action
{
    /**
     * constructor function
     *
     * @param Context $context
     * @return void
     */
    public function __construct(
        Context $context,
        ResultFactory $resultFactory,
        private PageFactory $resultPageFactory,
        private JsonFactory $resultJsonFactory,
        private Registry $registry,
        private CatalogMvp $mvpHelper,
    ) {
        parent::__construct($context);
        $this->resultFactory = $resultFactory;
    }
    /**
     * Get Store/Store view list
     *
     * @return Json
     */
    public function execute()
    {
        $isCustomerAdmin = $this->mvpHelper->isSharedCatalogPermissionEnabled();
       /** @var Raw $rawResult */
       $resultJsonData = $this->resultJsonFactory->create();
       $response = $resultJsonData->setData(['isAdmin' => $isCustomerAdmin]);
       return $response;
    }
}
