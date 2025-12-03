<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\ProductCustomAtrribute\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

abstract class AbstractConfig
{
    public const XPATH_CANVA_LINK   = 'default_canva_link';

    public function __construct(
        private ScopeConfigInterface $scopeConfig
    )
    {
    }

    public function getCanvaLink(): string
    {
        return (string) $this->getScopeValue(self::XPATH_CANVA_LINK);
    }

    /**
     * @param string $path
     * @param null $storeId
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

    protected function getConfigPath(string $key): string
    {
        return $this->getConfigPrefix() . '/' . $key;
    }

    /**
     * @return string
     */
    abstract protected function getConfigPrefix(): string;
}
