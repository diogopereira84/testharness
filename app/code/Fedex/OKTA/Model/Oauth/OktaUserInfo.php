<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Model\Oauth;

use Fedex\OKTA\Model\Backend\UserInfoProvider;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class OktaUserInfo implements OktaUserInfoInterface
{
    /**
     * OktaUserInfo constructor.
     * @param UserInfoProvider $userInfoProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        private UserInfoProvider $userInfoProvider,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * @param string $accessToken
     * @return mixed
     */
    public function getUserInfo(string $accessToken)
    {
        return $this->userInfoProvider->getUserInfo($accessToken);
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

        return true;
    }
}
