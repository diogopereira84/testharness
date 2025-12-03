<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\Account;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Controller\AbstractAccount;
use Fedex\Ondemand\Model\Config;
use Fedex\EnhancedProfile\Helper\Account as AccountHelper;
use Magento\Framework\Controller\Result\RedirectFactory;

/**
 * AccountsAndCreditCards Controller class
 */
class AccountsAndCreditCards extends AbstractAccount
{
    /**
     * B-2107362 SGC Tab Name Updates Toggle Xpath
     */
    protected const SGC_TAB_NAME_UPDATES = 'sgc_b_2107362';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ToggleConfig $toggleConfig
     * @param Config $config
     * @param AccountHelper $accountHelper
     */
    public function __construct(
        Context $context,
        protected PageFactory $resultPageFactory,
        protected ToggleConfig $toggleConfig,
        public Config $config,
        protected AccountHelper $accountHelper
    ) {
        parent::__construct($context);
    }

    /**
     * Account & Credit Card information
     *
     * @return void
     */
    public function execute()
    {
        $loginAsAdmin = $this->accountHelper->getAdminIdByLoginAsCustomer();
        if ($loginAsAdmin && $this->toggleConfig->getToggleConfigValue('mazegeeks_ctc_admin_impersonator')) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($this->accountHelper->getCurrentBaseUrl());
            return $resultRedirect;
        }else{
            /** @var \Magento\Framework\View\Result\Page $resultPage */
            $resultPage = $this->resultPageFactory->create();
            $isUpdateTabNameToggleEnabled = (bool) $this->toggleConfig->getToggleConfigValue(self::SGC_TAB_NAME_UPDATES);

            if ($isUpdateTabNameToggleEnabled) {
                $tabNameTitle = $this->config->getMyAccountTabNameValue();
                $resultPage->getConfig()->getTitle()->set(__($tabNameTitle));
            }

            return $resultPage;
        }
    }
}
