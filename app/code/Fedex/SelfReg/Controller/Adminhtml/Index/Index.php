<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Controller\Adminhtml\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Controller\ResultFactory;

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
        protected PageFactory $resultPageFactory,
        private ToggleConfig $toggleConfig,
        private ResultFactory $resultFactory
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
        if ($this->toggleConfig->getToggleConfigValue('e_451182_order_approver_ctc_admin')) {
            $resultPage = $this->resultPageFactory->create();
            $resultPage->setActiveMenu('Fedex_SelfReg::order_approver');
            $resultPage->getConfig()->getTitle()->prepend(__('Approver Groups'));
            return $resultPage;
        } else {
            return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('noroute');
        }
    }
}
