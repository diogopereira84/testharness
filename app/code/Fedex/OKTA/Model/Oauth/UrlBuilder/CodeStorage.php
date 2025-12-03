<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Model\Oauth\UrlBuilder;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Session\SessionManagerInterface;

class CodeStorage
{
    /**
     * Name of cookie that holds private content version
     */
    private const COOKIE_NAME = 'oktaverifier';

    /**
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param SessionManagerInterface $sessionManager
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        private CookieManagerInterface $cookieManager,
        private CookieMetadataFactory $cookieMetadataFactory,
        private SessionManagerInterface $sessionManager,
        private EncryptorInterface $encryptor
    )
    {
    }

    /**
     * Get form key cookie
     *
     * @return string
     */
    public function retrieve(): string
    {
        return $this->encryptor->decrypt($this->cookieManager->getCookie(self::COOKIE_NAME));
    }

    /**
     * Store code info to be used latter
     *
     * @param string $value
     * @return void
     * @throws InputException
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     */
    public function store(string $value): void
    {
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration($this->sessionManager->getCookieLifetime())
            ->setPath($this->sessionManager->getCookiePath())
            ->setDomain($this->sessionManager->getCookieDomain());

        $this->cookieManager->setPublicCookie(
            self::COOKIE_NAME,
            $this->encryptor->encrypt($value),
            $metadata
        );
    }
}
