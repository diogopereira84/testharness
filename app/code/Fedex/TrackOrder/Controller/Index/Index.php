<?php

/**
 * Copyright © FedEx  All rights reserved.
 * See COPYING.txt for license details.
 * @author Adithya Adithya <5174169@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\TrackOrder\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\View\Result\PageFactory;
use Fedex\TrackOrder\Model\Config;

class Index extends Action
{
    /**
     * Track Order Constructor
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Config $Config
     */
    public function __construct(
        Context $context,
        protected PageFactory $resultPageFactory,
        private Config $config
    )
    {
        parent::__construct($context);
    }

    /**
     * Execute track order action
     */
    public function execute()
    { 
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set($this->config->getMetaTitle());
        $resultPage->getConfig()->setDescription($this->config->getMetaDescription());
        return $resultPage;
    }
}
