<?php
/**
 * @category  Fedex
 * @package   Fedex_SDE
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SDE\Model;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;

class ForgeRock
{
    /**
     * Id token
     */
    private const ID_TOKEN = 'id_token';

    /**
     * Cookie timeout
     */
    private const COOKIE_TIMEOUT = 1800;

    /**
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RequestInterface $request,
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory
    ) {
    }

    /**
     * If there is an id_token param in the request, set it in a cookie
     *
     * @return ?string
     */
    public function getCookie(): ?string
    {
        try {
            $idToken = $this->request->getParam(self::ID_TOKEN);
            if ($idToken) {
                $this->cookieManager->setPublicCookie(
                    self::ID_TOKEN,
                    $idToken,
                    $this->cookieMetadataFactory->createPublicCookieMetadata()
                        ->setDuration(self::COOKIE_TIMEOUT)
                        ->setPath('/')
                        ->setHttpOnly(false)
                );
                return $idToken;
            }
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__ . ':'
                . __LINE__
                . ' Error while reading cookie '
                .self::ID_TOKEN
                . ': '
                . $e->getMessage()
            );
        }

        return null;
    }
}
