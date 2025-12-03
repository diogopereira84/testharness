<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SSO\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Fedex\SSO\Model\Login as LoginModal;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * CustomerRedirect class used to redirect customer login,
 * create, forgot password and company create page to home
 * page
 */
class CustomerLogin implements ObserverInterface
{
    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param LoginModal $login
     */    
    public function __construct(
        protected LoginModal $login,
        protected ToggleConfig $toogleConfigInterface
    )
    {
    }

    /**
     * Redirect on home page for visitor
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer){
        $layout = $observer->getData('layout'); 
        $currentHandles = $layout->getUpdate()->getHandles();
        if (in_array('checkout_cart_index', $currentHandles)) {
            $this->login->isCustomerLoggedIn();
        }
        if($observer->getFullActionName()=='company_users_index' && $this->toogleConfigInterface->getToggleConfigValue('change_customer_roles_and_permissions'))
        {
            $layout->getUpdate()->addHandle('company_users_index_move');
        }
        return $this;
    }
}
