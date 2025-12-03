<?php
/**
 * Copyright © Fedex. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SSO\Plugin\Result;

use Fedex\EnhancedProfile\Helper\Account;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Element\Context;
use Fedex\Delivery\Helper\Data;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Page
{
    /**
     * @var ToggleConfig
     */
    protected $toggleConfig;
    private ToggleConfig $_toggleConfig;

    /**
     * Dependancy Initilization
     *
     * @param Context $context
     * @param Data $deliveryHelper
     * @param ToggleConfig $toggleConfig
     * @param Account $accountHelper
     */
    public function __construct(
        private Context $context,
        private Data $deliveryHelper,
        ToggleConfig $toggleConfig,
        protected Account $accountHelper
    ) {
        $this->_toggleConfig = $toggleConfig;
    }

    /**
     * Add class for FCL login
     *
     * @param   object $subject
     * @param   object $response
     * @return  object
     */
    public function beforeRenderResult(
        \Magento\Framework\View\Result\Page $subject,
        ResponseInterface $response
    ) {
        $toggleEnableRetailUniqueClass = (bool) $this->_toggleConfig->getToggleConfigValue("enable_retail_unique_class");
        if($toggleEnableRetailUniqueClass) {
            if ($this->context->getRequest()->getRouteName() == 'customer'
                && (!$this->deliveryHelper->isCommercialCustomer()
                    || $this->accountHelper->getCompanyLoginType() == 'FCL')
            ) {
                $subject->getConfig()->addBodyClass('is-fcl');
            } elseif ($this->context->getRequest()->getRouteName() != 'customer'
                && (!$this->deliveryHelper->isCommercialCustomer()
                    || $this->accountHelper->getCompanyLoginType() == 'FCL')
            ) {
                $subject->getConfig()->addBodyClass('is-fcl-global');
            }

            $subject->getConfig()->addBodyClass('globalize-login-mobile');

            return [$response];
        }
    }
}
