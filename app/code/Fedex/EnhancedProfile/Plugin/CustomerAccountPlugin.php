<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
*/

namespace Fedex\EnhancedProfile\Plugin;

use Fedex\EnhancedProfile\Helper\Account as AccountHelper;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * CustomerAccountPlugin Plugin class
 */
class CustomerAccountPlugin
{
    /**
     * Constructor with Dependency Injection
     *
     * @param AccountHelper $accountHelper
     * @param RedirectFactory $resultRedirectFactory
     * @param StoreManagerInterface $storeManager
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected AccountHelper $accountHelper,
        protected RedirectFactory $resultRedirectFactory,
        protected StoreManagerInterface $storeManager,
        protected ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * After Execute Plugin
     *
     * @param \Magento\Customer\Controller\Account\Index $subject
     * @param \Magento\Framework\Controller\ResultInterface $result
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function afterExecute(\Magento\Customer\Controller\Account\Index $subject, $result)
    {
        $loginAsAdmin = $this->accountHelper->getAdminIdByLoginAsCustomer();
        if ($this->toggleConfig->getToggleConfigValue('mazegeeks_ctc_admin_impersonator') && $loginAsAdmin) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($this->accountHelper->getCurrentBaseUrl());
            return $resultRedirect;
        }else{
            return $result;
        }
       
    }
}
