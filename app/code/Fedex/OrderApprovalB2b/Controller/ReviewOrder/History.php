<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Controller\ReviewOrder;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Result\Page;
use Fedex\OrderApprovalB2b\Helper\RevieworderHelper;

/**
 * History Controller
 */
class History extends Action
{
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RevieworderHelper $revieworderHelper
     * @param RedirectFactory $resultRedirectFactory
     */
    public function __construct(
        Context $context,
        protected PageFactory $resultPageFactory,
        protected RevieworderHelper $revieworderHelper,
        RedirectFactory $resultRedirectFactory
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        parent::__construct($context);
    }

    /**
     * Review orders history layout
     *
     * @return Page
     */
    public function execute()
    {
        if (!$this->revieworderHelper->checkIfUserHasReviewOrderPermission()) {
            return $this->resultRedirectFactory->create()->setPath('');
        }

        return $this->resultPageFactory->create();
    }
}
