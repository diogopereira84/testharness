<?php
/**
 * @category  Fedex
 * @package   Fedex_OKTA
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\OKTA\Plugin\Controller\Adminhtml\Auth;

use Magento\Backend\Controller\Adminhtml\Auth\Logout;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\View\Result\PageFactory;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Magento\Backend\Model\Auth;
use Magento\Framework\Message\ManagerInterface;

class LogoutPlugin
{
    public function __construct(
        readonly private ToggleConfig     $toggleConfig,
        readonly private OktaHelper       $oktaHelper,
        readonly private PageFactory      $resultPageFactory,
        readonly private Auth             $auth,
        readonly private ManagerInterface $messageManager
    )
    {
    }

    public function aroundExecute(Logout $subject, callable $proceed)
    {
        if ($this->toggleConfig->getToggleConfigValue('tigers_b2185176_remove_adobe_commerce_overrides')) {
            if (!$this->oktaHelper->isEnabled()) {
                return $proceed();
            }
            $this->auth->logout();
            $this->messageManager->addSuccessMessage(__('You have logged out.'));
            return $this->resultPageFactory->create();
        }
        return $proceed();
    }
}
