<?php
/**
 * @category Fedex
 * @package  Fedex_Customer
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Model;

use Fedex\Customer\Api\Data\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config.
 * Provide access to Customer Module database configuration.
 */
class Config implements ConfigInterface
{

    /**
     * Marketing Opt-In Enabled path
     */
    public const XML_PATH_MARKETING_OPT_IN_ENABLED = 'promo/marketing_opt_in/enabled';

    /**
     * Marketing Opt-In API URL path
     */
    public const XML_PATH_MARKETING_OPT_IN_API_URL = 'fedex/general/sales_force_api_url';

    /**
     * Marketing Opt-In URL for Success Page
     */
    public const XML_PATH_MARKETING_OPT_IN_URL_SUCCESS_PAGE = 'promo/marketing_opt_in/url_success_page';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function isMarketingOptInEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_MARKETING_OPT_IN_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getMarketingOptInApiUrl(): string|null
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MARKETING_OPT_IN_API_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getMarketingOptInUrlSuccessPage(): string|null
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MARKETING_OPT_IN_URL_SUCCESS_PAGE,
            ScopeInterface::SCOPE_STORE
        );
    }
}
