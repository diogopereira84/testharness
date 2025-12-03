<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\Account;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Fedex\EnhancedProfile\Block\EnhancedProfile;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;

class GetDefaultAddress implements ActionInterface
{

    /**
     * Initialize dependencies.
     *
     * @param PageFactory $resultPageFactory
     * @param Context $context
     * @param RequestInterface $request
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        protected PageFactory $resultPageFactory,
        protected Context $context,
        protected RequestInterface $request,
        protected ResultFactory $resultFactory
    )
    {
    }

    /**
     * Set preferred payment method
     *
     * @return json
     */
    public function execute()
    {
        $locationId = $this->request->getPost('locationId');
        $resultPage = $this->resultPageFactory->create();
        $blockClass = EnhancedProfile::class;
        $block = $resultPage->getLayout()
                        ->createBlock($blockClass)
                        ->setTemplate('Fedex_EnhancedProfile::default_pickup_address.phtml')
                        ->setData("locationId", $locationId)
                        ->toHtml();
        return $this->context->getResponse()->setBody($block);
    }
}
