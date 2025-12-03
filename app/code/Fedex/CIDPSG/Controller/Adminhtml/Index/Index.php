<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Controller\Adminhtml\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Index controller class
 */
class Index implements ActionInterface
{
    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        protected PageFactory $resultPageFactory
    )
    {
    }

    /**
     * Execute method for showing psg customer details
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Fedex_CIDPSG::psg_customers');
        $resultPage->getConfig()->getTitle()->prepend(__('PSG Customers Details'));

        return $resultPage;
    }
}
