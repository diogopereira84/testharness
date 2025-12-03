<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SubmitOrderSidebar\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\SessionFactory;

class OrderConfirmation extends \Magento\Framework\View\Element\Template
{
    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param SessionFactory $customerSessionFactory
     * @param Data $data
     */
    public function __construct(
        Context $context,
        public ScopeConfigInterface $scopeConfig,
        public StoreManagerInterface $storeManager,
        public SessionFactory $customerSessionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get the Scope Config Value from Database
     *
     * @param key $key
     * @return scopeConfig
     */
    public function getScopeConfigValue($key)
    {
        return $this->scopeConfig->getValue(
            $key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()
        );
    }

    /**
     * Passing the Cj Object Script
     * @return array
     */
    public function addCJOrderObjectScript()
    {
        $cjPluginEnabled = $this->getScopeConfigValue("universaltag/general/customer_status");
        if ($cjPluginEnabled) {
            $enterpriseId = $this->getScopeConfigValue("universaltag/general/enterprise_id");
            $actionId = $this->getScopeConfigValue("universaltag/general/action_id");
            $tagId = $this->getScopeConfigValue("universaltag/general/tag_id");
            $customer = $this->customerSessionFactory->create();
            $userId = $customer->getCustomer()->getId();
            $customerEmail = $customer->getCustomer()->getEmail();

            return [
                'enterprise_id' => !empty($enterpriseId) ? $enterpriseId : '',
                'action_id' => !empty($actionId) ? $actionId : '',
                'tag_id' => !empty($tagId) ? $tagId : '',
                'user_id' => !empty($userId) ? $userId : '',
                'email_id' => !empty($customerEmail) ? $customerEmail :''
            ];
        }
    }
}
