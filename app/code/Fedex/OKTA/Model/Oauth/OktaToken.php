<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\OKTA\Model\Oauth;

use Fedex\OKTA\Model\Backend\TokenProvider;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class OktaToken implements OktaTokenInterface
{
    public function __construct(
        private TokenProvider $tokenProvider,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * @param string $code
     * @return mixed
     */
    public function getToken(string $code)
    {
        return $this->tokenProvider->getOktaToken($code);
    }

    /**
     * @param array $response
     * @return bool
     * @throws LocalizedException
     */
    public function validate(array $response): bool
    {
        if (empty($response)) {
            $this->logger->critical(__METHOD__.':'.__LINE__.' Authentication Error');
            throw new LocalizedException(__('Authentication Error'));
        }
        if (! empty($response['error'])) {
            $this->logger->critical(__METHOD__.':'.__LINE__.' '.$response['error_description']);
            throw new LocalizedException(__($response['error_description']));
        }
        if (empty($response['access_token'])) {
            $this->logger->critical(__METHOD__.':'.__LINE__.' Authentication Error (Empty Access Token)');
            throw new LocalizedException(__('Authentication Error'));
        }

        return true;
    }
}
