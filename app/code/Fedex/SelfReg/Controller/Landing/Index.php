<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Controller\Landing;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * Constructor
     * @param Context  $context
     * @param SelfReg $selfRegHelper
     * @param RedirectFactory $resultRedirectFactory
     * @param ToggleConfig $toggleConfig
     * @param StoreManagerInterface $storeManagerInterface
     * @param PageFactory $resultPageFactory
     */

    public function __construct(
        Context $context,
        protected SelfReg $selfRegHelper,
        RedirectFactory $resultRedirectFactory,
        protected ToggleConfig $toggleConfig,
        protected StoreManagerInterface $storeManagerInterface,
        protected PageFactory $resultPageFactory
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        parent::__construct($context);
    }

    /**
     * Execute action
     * B-1326203 - Create landing page for WLGN customers
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $isSelfRegCompany = $this->selfRegHelper->isSelfRegCompany(true);
        $isSelfRegCustomer = $this->selfRegHelper->isSelfRegCustomer();

        $storeObj = $this->storeManagerInterface->getStore();
        $baseUrl = $storeObj->getBaseUrl();

        $resultRedirect = $this->resultRedirectFactory->create();

        if ($isSelfRegCompany && $isSelfRegCustomer) {
            $resultRedirect->setUrl($baseUrl);
            return $resultRedirect;
        }

        if (!$isSelfRegCompany) {
            $resultRedirect->setUrl($baseUrl);
            return $resultRedirect;
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        return $this->resultPageFactory->create();
    }
}
