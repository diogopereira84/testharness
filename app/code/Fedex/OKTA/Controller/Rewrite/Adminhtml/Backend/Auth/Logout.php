<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\OKTA\Controller\Rewrite\Adminhtml\Backend\Auth;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Controller\Adminhtml\Auth\Logout as MagentoLogout;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Logout extends MagentoLogout
{
    /**
     * Logout constructor.
     * @param OktaHelper $oktaHelper
     * @param PageFactory $resultPageFactory
     * @param Context $context
     */
    public function __construct(
        private OktaHelper $oktaHelper,
        private PageFactory $resultPageFactory,
        Context $context,
        readonly private ToggleConfig $toggleConfig,
    ) {
        parent::__construct($context);
    }

    /**
     * @return Redirect|Page
     */
    public function execute(): Page|Redirect
    {
        if (!$this->toggleConfig->getToggleConfigValue('tigers_b2185176_remove_adobe_commerce_overrides')) {
            /**
             * If OKTA module is disabled, execute the OOTB method
             */
            if (!$this->oktaHelper->isEnabled()) {
                return parent::execute();
            }

            $this->_auth->logout();
            $this->messageManager->addSuccessMessage(__('You have logged out.'));
            return $this->resultPageFactory->create();
        }
        return parent::execute();
    }
}
