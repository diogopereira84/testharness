<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SubmitOrderSidebar\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Checkout\Model\ConfigProviderInterface;
use Fedex\Delivery\Helper\Data;

class SubmitOrderSidebarConfigProvider implements ConfigProviderInterface
{
    /**
     * Enable toggle for Enabling Customer Friendly Cc Message
     */
    public const TOGGLE_FEATURE_KEY = 'enable_customer_friendly_cc_msg';

    /**
     * @param ToggleConfig $toggleConfig
     * @param Data $deliveryHelper
     */
    public function __construct(
        private ToggleConfig $toggleConfig,
        private Data $deliveryHelper
    )
    {
    }

    /**
     * Getting State list to show in checkout page in Billing address for pickup
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'tiger_customer_friendly_cc_msg_toggle' => $this->isCustomerFriendlyCcMsgToggleEnabled(),
            'promise_time_warning_enabled' => $this->isPromiseTimeWarningToggleEnabled()
        ];
    }

    /**
     * @inheritDoc
     */
    public function isCustomerFriendlyCcMsgToggleEnabled()
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TOGGLE_FEATURE_KEY);
    }

    /**
     * Check if Promise Time Warning toggle is enabled
     */
    public function isPromiseTimeWarningToggleEnabled()
    {
        return $this->deliveryHelper->isPromiseTimeWarningToggleEnabled();
    }
}
