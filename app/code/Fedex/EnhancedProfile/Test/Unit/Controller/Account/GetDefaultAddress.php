<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 
namespace Fedex\EnhancedProfile\Controller\Account;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Fedex\EnhancedProfile\Block\EnhancedProfile;

class GetDefaultAddress extends Action
{

    /**
     * @var PageFactory $resultPageFactory
     */
    protected $resultPageFactory;

    /**
     * Initialize dependencies.
     *
     * @param PageFactory $resultPageFactory
     * @param Context $context
     */
    public function __construct(
        PageFactory $resultPageFactory,
        Context $context
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Set preferred payment method
     *
     * @return json
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $blockClass = EnhancedProfile::class;
        $block = $resultPage->getLayout()
                        ->createBlock($blockClass)
                        ->setTemplate('Fedex_EnhancedProfile::default_pickup_address.phtml')
                        ->toHtml();
        $this->getResponse()->setBody($block);
    }
}
