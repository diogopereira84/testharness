<?php

namespace Fedex\Recaptcha\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use \Magento\Framework\App\Config\ScopeConfigInterface;

class EnabledRecaptchaSettings extends Template
{
    protected $_template = 'Fedex_Recaptcha::enabledRecaptchaSettings.phtml';

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        protected ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
    }

    /**
     * Retrieve reCAPTCHA settings from the configuration
     *
     * @return false|string
     */
    public function getRecaptchaSettings()
    {
        return json_encode($this->scopeConfig->getValue('recaptcha_frontend/type_for'));
    }
}

