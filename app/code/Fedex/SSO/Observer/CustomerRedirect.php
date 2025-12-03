<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Event\Observer;

/**
 * CustomerRedirect class used to redirect customer login,
 * create, forgot password and company create page to home
 * page
 */
class CustomerRedirect implements ObserverInterface
{
    /**
     * Redirect on home page for visitor Constructor
     *
     * @param \Magento\Framework\App\ResponseFactory $responseFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        private \Magento\Framework\App\ResponseFactory $responseFactory,
        protected \Magento\Store\Model\StoreManagerInterface $storeManager,
        protected \Psr\Log\LoggerInterface $logger
    )
    {
    }

    /**
     * Redirect on home page for visitor
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        try {
            $redirectionUrl = $this->storeManager->getStore()->getBaseUrl();
            $this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
            return $this;
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__.':'.__LINE__.':Error While Customer Redirection : ' . $e->getMessage());
        }
    }
}
