<?php
/**
 * @category Fedex
 * @package FedexRate
 * @copyright Fedex (c) 2021.
 * @author Iago Lima <ilima@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Rate\Model;

use Fedex\Rate\Api\Data\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ConfigInterface
{
    public const XPATH_FEDEX_GENERAL_RATE_API_URL = 'fedex/general/rate_api_url';

    /**
     * Config construct.
     *
     * @param ScopeConfigInterface $scopConfig
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig
    )
    {
    }

    /**
     * @inheritDoc
     * @return string
     */
    public function getRateApiUrl(): string
    {
        return $this->scopeConfig->getValue(
            self::XPATH_FEDEX_GENERAL_RATE_API_URL,
            ScopeInterface::SCOPE_STORE
        );
    }
}
