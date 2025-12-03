<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SubmitOrderSidebar\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Checkout\Model\CartFactory;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;

/*
 * OrderSuccess viewmodel class
*/
class OrderSuccess implements ArgumentInterface
{
    public const XML_PATH_UPSELLIT_TRACKING_SCRIPT = 'web/upsellit/upsellit_order_success_script';

    public const XML_PATH_ACTIVE_UPSELLIT = 'web/upsellit/upsellit_active';
     
    public const XML_PATH_MODEL_POPUP = 'environment_toggle_configuration/environment_toggle/sgc_new_model_popup_order_submission';

    /**
     * OrderSuccess Constructor.
     *
     * @param ToggleConfig $toggleConfig
     * @param CartFactory $cartFactory
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param SsoConfiguration $ssoConfiguration
     */
    public function __construct(
        protected ToggleConfig $toggleConfig,
        protected CartFactory $cartFactory,
        protected ScopeConfigInterface $scopeConfigInterface,
        protected SsoConfiguration $ssoConfiguration
    )
    {
    }

    /**
     * Get quote current id
     *
     * @return int $quoteId
     */
    public function getQuoteId()
    {
        $quote = $this->cartFactory->create()->getQuote();
        
        return $quote->getId();
    }

    /**
     * Gets UpSellIt tracking script
     *
     * @return string
     */
    public function getUpSellItTrackingScript()
    {
        return $this->scopeConfigInterface->getValue(self::XML_PATH_UPSELLIT_TRACKING_SCRIPT);
    }

    /**
     * To identify the retail store
     *
     * @return boolean true|false
     */
    public function getIsRetail()
    {
        return $this->ssoConfiguration->isRetail();
    }

    /**
     * To identify if UpSellIt is active for site
     *
     * @return boolean true|false
     */
    public function getIsUpsellitActive()
    {
        return $this->scopeConfigInterface->isSetFlag(self::XML_PATH_ACTIVE_UPSELLIT);
    }

    /**
     * Check if the modal popup toggle is enabled in Magento configuration.
     */
    public function isPopupEnabled(): bool
    {
        return $this->scopeConfigInterface->isSetFlag(self::XML_PATH_MODEL_POPUP);
    }
}
