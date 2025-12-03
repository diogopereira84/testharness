<?php

namespace Fedex\SubmitOrderSidebar\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class OrderSuccess extends \Magento\Framework\App\Action\Action
{
    /**
     * Order Success Costructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        protected PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Execute Method
     */
    public function execute()
    {
        return $this->resultPageFactory->create();
    }
}
