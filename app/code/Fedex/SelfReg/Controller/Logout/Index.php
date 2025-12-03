<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Controller\Logout;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class Index extends \Magento\Framework\App\Action\Action

{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @param Context $context
     * @param UrlInterface $url
     * @param RedirectFactory $resultRedirectFactory
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param Session $customerSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        protected UrlInterface $url,
        RedirectFactory $resultRedirectFactory,
        protected CookieMetadataFactory $cookieMetadataFactory,
        protected Session $customerSession,
        protected LoggerInterface $logger
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        parent::__construct($context);
    }

    /**
     * Execute action
     * B-1320022 - WLGN integration for selfReg customer
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $customerId = $this->customerSession->getId();
            if ($customerId) {
                $this->customerSession->logout()->setLastCustomerId($customerId);
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                ':Unable to do logout for ' . $this->customerSession->getCustomerId() . ' with error: ' .
                $e->getMessage());
        }
        $url = $this->url->getUrl('selfreg/landing');
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($url);
        return $resultRedirect;
    }
}
