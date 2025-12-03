<?php

/**
 * Copyright © FedEx  All rights reserved.
 * See COPYING.txt for license details.
 * @author Adithya Adithya <5174169@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\Company\Controller\User;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Fedex\Ondemand\Model\Config;

class Groups implements ActionInterface
{
    /**
     * B-2107362 SGC Tab Name Updates Toggle Xpath
     */
    protected const SGC_TAB_NAME_UPDATES = 'sgc_b_2107362';

    /**
     * Manage user groups Constructor
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Config $Config
     * @param ToggleConfig $toggleConfig
     * @param Config $config
     */
    public function __construct(
        Context $context,
        protected PageFactory $resultPageFactory,
        protected ToggleConfig $toggleConfig,
        public Config $config
    ) {
    }

    /**
     * Execute manage user groups action
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
