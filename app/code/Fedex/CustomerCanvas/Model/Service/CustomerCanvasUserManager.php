<?php
declare(strict_types=1);
namespace Fedex\CustomerCanvas\Model\Service;

use Fedex\CustomerCanvas\Model\Service\CustomerCanvasTokenService;
use Fedex\CustomerCanvas\Model\Service\StoreFrontUserIdService;
use Magento\Framework\Exception\LocalizedException;

class CustomerCanvasUserManager
{
    public function __construct(
       private readonly CustomerCanvasTokenService $tokenService,
       private readonly StoreFrontUserIdService $storeFrontUserIdService
    ) {
    }

    /**
     * @return string|null
     * @throws LocalizedException
     */
    public function getOrCreateToken(): ?string
    {
        $sessionToken = $this->storeFrontUserIdService->getUserTokenFromSession()['token'] ?? null;

        if (!empty($sessionToken)) {
            return $sessionToken;
        }

        $storefrontUserId = $this->storeFrontUserIdService->getStoreFrontUserId();
        $fetchedToken = $this->tokenService->fetchToken($storefrontUserId);

        if (!empty($fetchedToken)) {
            $this->storeFrontUserIdService->saveTokenInSession($fetchedToken, $storefrontUserId);
        }

        return $fetchedToken;
    }
}
