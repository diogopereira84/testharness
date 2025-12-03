<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
*/

namespace Fedex\EnhancedProfile\Controller\Account;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Ondemand\Model\Config;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Controller\AbstractAccount;

/**
 * SiteSettings Controller class
 */
class SiteSettings extends AbstractAccount
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
     */
    public function __construct(
        Context $context,
        protected PageFactory $resultPageFactory,
        protected ToggleConfig $toggleConfig,
        protected Config $config
    ) {
        parent::__construct($context);
    }
        
    /**
     * Preferences information
     *
     * @return void
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();

        $isUpdateTabNameToggleEnabled = (bool) $this->toggleConfig->getToggleConfigValue(self::SGC_TAB_NAME_UPDATES);

        if ($isUpdateTabNameToggleEnabled) {
            $tabNameTitle = $this->config->getMyAccountTabNameValue();
            $resultPage->getConfig()->getTitle()->set(__($tabNameTitle));
        }

        return $resultPage;
    }
}
