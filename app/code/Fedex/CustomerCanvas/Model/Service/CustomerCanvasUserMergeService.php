<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Model\Service;

use Fedex\CustomerCanvas\Model\Config\CanvasConfig;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Fedex\CustomerCanvas\Model\Service\StoreFrontUserIdService;
use Fedex\CustomerCanvas\Model\Service\CustomerCanvasUserInfo;

class CustomerCanvasUserMergeService
{
    /**
     * @param Curl $curl
     * @param LoggerInterface $logger
     * @param CanvasConfig $canvasConfig
     * @param \Fedex\CustomerCanvas\Model\Service\StoreFrontUserIdService $frontUserIdService
     * @param \Fedex\CustomerCanvas\Model\Service\CustomerCanvasUserInfo $customerCanvasUserInfo
     */
    public function __construct(
        private readonly Curl $curl,
        private readonly LoggerInterface $logger,
        private readonly CanvasConfig $canvasConfig,
        private readonly StoreFrontUserIdService $frontUserIdService,
        private readonly CustomerCanvasUserInfo $customerCanvasUserInfo
    ) {}

    /**
     * @param $loggedInUserId
     * @param $guestUserID
     * @return bool
     */
    public function merge($loggedInUserId,$guestUserID)
    {
        try {
            $baseUrl = rtrim($this->canvasConfig->getApiUrl(), '/');
            $storefrontId = $this->canvasConfig->getCanvasStoreId();
            $tenantId = $this->canvasConfig->getCanvasTenantId();
            $url = "{$baseUrl}/storefront-users/merge-anonymous?storefrontId={$storefrontId}&tenantId={$tenantId}";

            $userDetails = $this->customerCanvasUserInfo->getUserInfo(true);
            $customer = $this->frontUserIdService->getCustomerById($loggedInUserId);
            $registerUserId = $this->frontUserIdService->getOrCreateCanvasUuid($customer);

            $payload = [
                'anonymousStorefrontUserId' => $guestUserID,
                'regularStorefrontUserId'   => $registerUserId,
            ];

            $this->curl->setOptions([
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_ENCODING => '',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $this->canvasConfig->getAccessToken(),
                    'Content-Type: application/json',
                ]
            ]);

            $this->curl->post($url, json_encode($payload));
            $status = $this->curl->getStatus();
            if ($status !== 200) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ .': ' . "Request failed with status code: $status");
                return false;
            }
            return $registerUserId;
        } catch (\Throwable $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .': ' . $e->getMessage());
        }

        return false;
    }

}

