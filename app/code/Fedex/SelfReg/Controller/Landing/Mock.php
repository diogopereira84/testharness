<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Controller\Landing;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Mock extends \Magento\Framework\App\Action\Action
{
    /**
     * Constructor
     * @param Context  $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        protected PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Create landing login mock
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        return $this->resultPageFactory->create();
    }
}
