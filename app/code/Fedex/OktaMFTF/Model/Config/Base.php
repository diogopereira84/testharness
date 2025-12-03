<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

abstract class Base
{
    private const XPATH_SEPARATOR = '/';

    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private UrlInterface $urlInterface
    )
    {
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

    /**
     * @param string $key
     * @return string
     */
    protected function getConfigPath(string $key): string
    {
        return $this->getConfigPrefix() . self::XPATH_SEPARATOR . $key;
    }

    /**
     * @return string
     */
    abstract protected function getConfigPrefix(): string;
}
