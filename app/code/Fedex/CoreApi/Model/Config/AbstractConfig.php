<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\CoreApi\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

abstract class AbstractConfig
{
    public const XPATH_API_TIMEOUT   = 'api_timeout';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig
    )
    {
    }

    /**
     * Get timeout information from configuration
     *
     * @return int
     */
    public function getApiTimeOut(): int
    {
        return (int) $this->getScopeValue(self::XPATH_API_TIMEOUT);
    }

    /**
     * Get information from configuration
     *
     * @param string $path
     * @param null|int|string $storeId
     * @return mixed
     */
    protected function getScopeValue(string $path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $this->getConfigPath($path),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get config path
     *
     * @param string $key
     * @return string
     */
    protected function getConfigPath(string $key): string
    {
        return $this->getConfigPrefix() . '/' . $key;
    }

    /**
     * Get config prefix
     *
     * @return string
     */
    abstract protected function getConfigPrefix(): string;
}
