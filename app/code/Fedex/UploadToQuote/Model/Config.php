<?php
/**
 * @category    Fedex
 * @package     Fedex_UploadToQuote
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\UploadToQuote\Model;

use Fedex\UploadToQuote\Api\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ConfigInterface
{
    private const DEFAULT_LOGIN_MODAL_HEADING = "Don't lose your quote";
    private const DEFAULT_LOGIN_MODAL_COPY = "Log in or create a user ID to request a priced quote from our team. With an account, you can easily access your quote for thirty days and check out whenever you're ready.";

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    public function getLoginModalHeading(string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return $this->scopeConfig->getValue(
            static::XML_PATH_UPLOAD_TO_QUOTE_LOGIN_MODAL_HEADING,
            $scope
        ) ?: self::DEFAULT_LOGIN_MODAL_HEADING;
    }

    public function getLoginModalCopy(string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return $this->scopeConfig->getValue(
            static::XML_PATH_UPLOAD_TO_QUOTE_LOGIN_MODAL_COPY,
            $scope
        ) ?: self::DEFAULT_LOGIN_MODAL_COPY;
    }

    public function isTk4673962ToggleEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_4673962_WRONG_LOCATION_QUOTE,
            $scope
        );
    }

    public function isTk4674396ToggleEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_4674396_QUOTES_NOT_VISIBLE,
            $scope
        );
    }
}
