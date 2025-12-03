<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Controller\Adminhtml\shipment;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\ActionInterface;

class Index implements ActionInterface
{
    /**
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        private readonly PageFactory $resultPageFactory
    ) {
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Fedex_Shipment::shipment');
        $resultPage->addBreadcrumb(__('Fedex'), __('Fedex'));
        $resultPage->addBreadcrumb(__('Manage item'), __('Manage Shipment Status'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Shipment Status'));

        return $resultPage;
    }
}
