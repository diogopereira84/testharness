<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\OKTA\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\UrlInterface;

abstract class AbstractConfig
{
    public const XPATH_ENABLED   = 'enabled';
    public const XPATH_CLIENT_ID = 'client_id';
    public const XPATH_CLIENT_SECRET = 'client_secret';
    public const XPATH_DOMAIN    = 'domain';
    public const XPATH_REDIRECT_URI    = 'redirect_uri';
    public const XPATH_SCOPE    = 'scope';
    public const XPATH_STATE    = 'state';
    public const XPATH_RESPONSE_MODE    = 'response_mode';
    public const XPATH_RESPONSE_TYPE    = 'response_type';
    public const XPATH_ENHANCED_LOG = 'b_1949452_okta_enhanced_logging';

    /**
     * AbstractConfig constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param CookieManagerInterface $cookieManager
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private CookieMetadataFactory $cookieMetadataFactory,
        private CookieManagerInterface $cookieManager,
        private UrlInterface $urlInterface
    )
    {
    }

    /**
     * @return CookieManagerInterface
     */
    public function getCookieManager()
    {
        return $this->cookieManager;
    }

    /**
     * @return CookieMetadataFactory
     */
    public function getCookieMetadataFactory()
    {
        return $this->cookieMetadataFactory;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool) $this->getScopeValue(self::XPATH_ENABLED);
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return (string) $this->getScopeValue(self::XPATH_CLIENT_ID);
    }

    /**
     * @return string
     */
    public function getClientSecret(): string
    {
        return (string) $this->getScopeValue(self::XPATH_CLIENT_SECRET);
    }
    /**
     * @return string
     */
        public function getDomain(): string
    {
        return $this->getScopeValue(self::XPATH_DOMAIN);
    }

    /**
     * @return string
     */
    public function getOktaRedirectUri(): string
    {
        return $this->getScopeValue(self::XPATH_REDIRECT_URI) ?? '';
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return $this->getScopeValue(self::XPATH_SCOPE);
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->getScopeValue(self::XPATH_STATE);
    }

    /**
     * @return string
     */
    public function getResponseMode(): string
    {
        return $this->getScopeValue(self::XPATH_RESPONSE_MODE);
    }

    /**
     * @return string
     */
    public function getResponseType(): string
    {
        return $this->getScopeValue(self::XPATH_RESPONSE_TYPE);
    }

    /**
     * @return string
     */
    public function getNonceCookieName(): string
    {
        return $this->getConfigPath('nonce');
    }

    /**
     * @return bool
     */
    public function isToggleForEnhancedLoggingEnabled(): bool
    {
        return (bool) $this->getScopeValue(self::XPATH_ENHANCED_LOG);
    }

    /**
     * Additional security layer
     *
     * @return string
     * @throws \Exception
     */
    public function getNonce(): string
    {
        $sessionNonce = $this->getCookieManager()->getCookie($this->getNonceCookieName());
        if (!$sessionNonce) {
            $metadata = $this->getCookieMetadataFactory()->createPublicCookieMetadata();
            $metadata->setDuration(180)->setPath('/');
            $sessionNonce = uniqid();
            $this->getCookieManager()->setPublicCookie($this->getNonceCookieName(), $sessionNonce, $metadata);
        }

        return $sessionNonce;
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
        return $this->getConfigPrefix() . '/' . $key;
    }

    /**
     * @return string
     */
    public function getRedirectUrl()
    {
        $this->urlInterface->setNoSecret(true);
        return $this->urlInterface->getUrl('admin/auth/login') . $this->getOktaRedirectUri();
    }

    /**
     * @return string
     */
    abstract protected function getConfigPrefix(): string;
}
