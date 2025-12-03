<?php
/**
 * @category    Fedex
 * @package     Fedex_Mars
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Mars\Model;

use Fedex\Mars\Api\Data\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ConfigInterface
{
    public const MARS_QUEUE_ID = 'id';
    public const MARS_QUEUE_TYPE = 'type';

    private const MARS_IS_ENABLED = 'mars/general/is_enabled';
    private const MARS_API_URL = 'mars/general/api_url';
    private const MARS_API_MAX_TRIES = 'mars/general/api_max_tries';
    private const MARS_API_SUCCESS_CODES = 'mars/general/success_codes';
    private const MARS_API_ENABLE_LOGGING = 'mars/general/json_content';
    private const MARS_ENABLE_QUOTE_IDENTIFIER =
        'environment_toggle_configuration/environment_toggle/mars_quote_identifier_d241246';
    private const MARS_ENABLE_MAZEGEEKS_B2743693 =
        'environment_toggle_configuration/environment_toggle/mazegeeks_mars_b2743693';

    private const MARS_TOKEN_CLIENT_SECRET = 'mars/token/client_secret';
    private const MARS_TOKEN_RESOURCE = 'mars/token/resource';
    private const MARS_TOKEN_GRANT_TYPE = 'mars/token/grant_type';
    private const MARS_TOKEN_CLIENT_ID = 'mars/token/client_id';
    private const MARS_TOKEN_API_URL = 'mars/token/api_url';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Get configuration value
     *
     * @param string $configPath
     * @return mixed
     */
    public function getConfigValue(string $configPath): mixed
    {
        return $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if module enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->getConfigValue(self::MARS_IS_ENABLED);
    }

    /**
     * Get client id
     *
     * @return string
     */
    public function getClientId(): string
    {
        return $this->getConfigValue(self::MARS_TOKEN_CLIENT_ID);
    }

    /**
     * Get secret
     *
     * @return string
     */
    public function getSecret(): string
    {
        return $this->getConfigValue(self::MARS_TOKEN_CLIENT_SECRET);
    }

    /**
     * Get resource
     *
     * @return string
     */
    public function getResource(): string
    {
        return $this->getConfigValue(self::MARS_TOKEN_RESOURCE);
    }

    /**
     * Get grant type
     *
     * @return string
     */
    public function getGrantType(): string
    {
        return $this->getConfigValue(self::MARS_TOKEN_GRANT_TYPE);
    }

    /**
     * Get token url
     *
     * @return string
     */
    public function getTokenApiUrl(): string
    {
        return $this->getConfigValue(self::MARS_TOKEN_API_URL);
    }

    /**
     * Get api url
     *
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->getConfigValue(self::MARS_API_URL);
    }

    /**
     * Get max retries
     *
     * @return int
     */
    public function getMaxRetries(): int
    {
        return (int)$this->getConfigValue(self::MARS_API_MAX_TRIES);
    }

    /**
     * Get success codes
     */
    public function getSuccessCodes()
    {
        return $this->getConfigValue(self::MARS_API_SUCCESS_CODES);
    }

    /**
     * Enable logs
     */
    public function isLoggingEnabled(): bool
    {
        return (bool)$this->getConfigValue(self::MARS_API_ENABLE_LOGGING);
    }

    /**
     * Check if D-241246 MARS quote identifier is enabled
     *
     * @return bool
     */
    public function isQuoteIdentifierEnabled(): bool
    {
        return (bool)$this->getConfigValue(self::MARS_ENABLE_QUOTE_IDENTIFIER);
    }

    /**
     * Check if MazeGeeks MARS B-2743693 is enabled
     *
     * @return bool
     */
    public function isMazeGeeksB2743693Enabled(): bool
    {
        return (bool)$this->getConfigValue(self::MARS_ENABLE_MAZEGEEKS_B2743693);
    }
}
