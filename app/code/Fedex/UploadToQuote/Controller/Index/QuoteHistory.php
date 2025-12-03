<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Controller\Index;

use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Ondemand\Model\Config;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
/**
 * QuoteHistory Controller
 */
class QuoteHistory implements ActionInterface
{
    /**
     * B-2107362 SGC Tab Name Updates Toggle Xpath
     */
    protected const SGC_TAB_NAME_UPDATES = 'sgc_b_2107362';

    /**
     * QuoteHistory class constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param CustomerSession $customerSession
     * @param AuthHelper $authHelper
     * @param ToggleConfig $toggleConfig
     * @param Config $config
     */
    public function __construct(
        protected Context $context,
        protected PageFactory $resultPageFactory,
        protected RedirectFactory $resultRedirectFactory,
        protected CustomerSession $customerSession,
        protected AuthHelper $authHelper,
        protected ToggleConfig $toggleConfig,
        protected Config $config
    )
    {
    }

    /**
     * Load quote history layout
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if (!$this->authHelper->isLoggedIn()) {
            return $this->resultRedirectFactory->create()->setPath('');
        }

        $resultPage = $this->resultPageFactory->create();

        $isUpdateTabNameToggleEnabled = (bool) $this->toggleConfig->getToggleConfigValue(self::SGC_TAB_NAME_UPDATES);

        if ($isUpdateTabNameToggleEnabled) {
            $tabNameTitle = $this->config->getMyAccountTabNameValue();
            $resultPage->getConfig()->getTitle()->set(__($tabNameTitle));
        }

        return $resultPage;
    }
}
