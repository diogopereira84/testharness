<?php
declare(strict_types=1);

namespace Fedex\Login\Plugin\Adminhtml;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\LoginAsCustomerAdminUi\Controller\Adminhtml\Login\Login;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Controller\ResultFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Message\ManagerInterface;

class LoginPlugin
{
    /**
     * Constructor.
     *
     * @param CookieManagerInterface $customerSession
     * @param ResultFactory $resultFactory
     * @param ToggleConfig $toggleConfig
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        protected CookieManagerInterface $cookieManager,
        protected ResultFactory $resultFactory,
        protected ToggleConfig $toggleConfig,
        protected ManagerInterface $messageManager
    )
    {
    }

    /**
     * after execute plugin for Login action
     *
     * @param Login $subject
     * @return array|null
     */
    public function afterExecute(Login $subject, $result)
    {
        if ($this->toggleConfig->getToggleConfigValue('mazegeeks_ctc_admin_impersonator') && $this->cookieManager->getCookie('mage-cache-sessid')) {
            $jsonResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $customerId = $subject->getRequest()->getParam('customer_id');
            $query_params = ['id' => $customerId];
            $jsonResult->setData([
                'redirectUrl' => $subject->getUrl("customer/index/edit/", ['_query' => $query_params]),
                'login_error' => true
            ]);
            $this->messageManager->addError(__("The user is already logged in. Please log out or close the browser."));
            return $jsonResult;
        }
        return $result;
    }
}
